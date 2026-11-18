<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
?>

<?php require_once "includes/header.php"; ?>
<?php require_once "includes/mail_helper.php"; ?>















<style>
    .btn-enviar-pedido{
    width:100%;
    padding:18px;
    border:none;
    border-radius:14px;
    background:var(--ouro, #C8A02E);
    color:#11151B;
    font-size:18px;
    font-weight:800;
    cursor:pointer;
    transition:.25s;
    box-shadow:0 10px 25px rgb(var(--ouro-rgb, 240 180 41) / .35);
}

.btn-enviar-pedido:hover{
    background:#ffc94d;
    transform:translateY(-3px);
}
</style>








<link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css">

<?php
$sucesso = false;
$erro = '';
$errosCampo = [];   // erros junto a cada campo (acessibilidade, 18/11/2026)
$codigoPedido = '';
$cidadaoLogado = null;

$categoriasOcorrencia = $pdo->query("
    SELECT id, designacao FROM ocorrencias_categorias WHERE ativo = 1 ORDER BY ordem ASC, designacao ASC
")->fetchAll(PDO::FETCH_ASSOC);

$assuntosPorCategoria = [];
$stmtAssuntos = $pdo->query("
    SELECT a.id, a.designacao, c.designacao AS categoria
    FROM ocorrencias_assuntos a
    JOIN ocorrencias_categorias c ON c.id = a.categoria_id
    WHERE a.ativo = 1
    ORDER BY a.ordem ASC, a.designacao ASC
");
foreach ($stmtAssuntos->fetchAll(PDO::FETCH_ASSOC) as $a) {
    $assuntosPorCategoria[$a['categoria']][] = $a['designacao'];
}

if (isset($_SESSION['cidadao_id'])) {
    $stmtCidadao = $pdo->prepare("SELECT * FROM cidadaos WHERE id = ? AND ativo = 1");
    $stmtCidadao->execute([$_SESSION['cidadao_id']]);
    $cidadaoLogado = $stmtCidadao->fetch(PDO::FETCH_ASSOC);
}

$nomeForm = $cidadaoLogado['nome'] ?? '';
$emailForm = $cidadaoLogado['email'] ?? '';
$telefoneForm = $cidadaoLogado['telefone'] ?? '';

// ?categoria=Sugestão abre o formulário com essa categoria já escolhida (ex.: um atalho da página inicial).
// Só aceita uma categoria que exista; o valor volta a ser escapado onde é mostrado.
if ($_SERVER['REQUEST_METHOD'] !== 'POST' && isset($_GET['categoria'])) {
    foreach ($categoriasOcorrencia as $cat) {
        if (mb_strtolower($cat['designacao']) === mb_strtolower(trim((string)$_GET['categoria']))) {
            $categoria = $cat['designacao'];
            break;
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cidadaoId = $cidadaoLogado['id'] ?? null;

    $nome = trim($_POST['nome'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $telefone = trim($_POST['telefone'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $subcategoria = trim($_POST['subcategoria'] ?? '');
    $assunto = trim($_POST['assunto'] ?? '');
    $mensagem = trim($_POST['mensagem'] ?? '');
    $localizacao = trim($_POST['localizacao'] ?? '');
    $latitude = trim($_POST['latitude'] ?? '');
    $longitude = trim($_POST['longitude'] ?? '');
    $dentroFreguesia = trim($_POST['dentro_freguesia'] ?? '0');

    if ($cidadaoLogado) {
        $nome = $cidadaoLogado['nome'];
        $email = $cidadaoLogado['email'];
        $telefone = $cidadaoLogado['telefone'] ?? $telefone;
    }

    $siteWeb = trim($_POST['site_web'] ?? ''); // honeypot: campo invisível, só bots o preenchem

    // Cada erro fica junto ao seu campo e diz o que fazer (checklist «Transação» da AMA, 18/11/2026)
    if (!$nome) $errosCampo['nome'] = 'Indique o seu nome.';
    if ($email !== '' && !emailValido($email)) $errosCampo['email'] = 'Indique um email válido e que exista (ex.: nome@exemplo.pt), ou deixe o campo vazio.';
    if ($telefone !== '' && !telefoneValido($telefone)) $errosCampo['telefone'] = 'Indique um número de telefone válido (ex.: 912 345 678, ou com o indicativo do país se for estrangeiro).';
    if (!$categoria) $errosCampo['categoria'] = 'Escolha a categoria da ocorrência.';
    if (!$assunto) $errosCampo['assunto'] = 'Escreva o assunto em poucas palavras (ex.: «Buraco na estrada»).';
    if (!$mensagem) $errosCampo['mensagem'] = 'Descreva a ocorrência: o que se passa e desde quando.';
    if (!$latitude || !$longitude) {
        $errosCampo['mapa'] = 'Escolha no mapa o local da ocorrência (carregue no mapa ou use «localização atual»).';
    } elseif ($dentroFreguesia !== '1') {
        $errosCampo['mapa'] = 'O local escolhido fica fora da Freguesia de ' . nomeFreguesia() . '. Escolha um ponto dentro da linha vermelha.';
    }

    if ($siteWeb !== '') {
        // Bot apanhado no honeypot: finge sucesso, não processa nem envia nada.
        $sucesso = true;
        $errosCampo = [];
    } elseif ($errosCampo) {
        // mostrados junto a cada campo e no resumo do topo do formulário
    } elseif (submissaoRecente('pedido')) {
        $erro = "Já recebemos um pedido seu há pouco. Aguarde uns instantes antes de submeter outro.";
    } else {

        do {
            $codigoPedido = prefixoPedidos() . "-" . date('Y') . "-" . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
            $stmtCheck = $pdo->prepare("SELECT id FROM pedidos_junta WHERE codigo = ?");
            $stmtCheck->execute([$codigoPedido]);
        } while ($stmtCheck->fetch());

        $subcategoriaFinal = $subcategoria !== '' ? $subcategoria : null;

        $stmt = $pdo->prepare("
            INSERT INTO pedidos_junta
            (codigo, cidadao_id, nome, email, telefone, categoria, subcategoria, assunto, mensagem, localizacao, latitude, longitude, origem,
             nome_original, email_original, telefone_original, categoria_original, subcategoria_original, assunto_original, mensagem_original, localizacao_original)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'website', ?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $codigoPedido,
            $cidadaoId,
            $nome,
            $email,
            $telefone,
            $categoria,
            $subcategoriaFinal,
            $assunto,
            $mensagem,
            $localizacao,
            $latitude,
            $longitude,
            $nome,
            $email,
            $telefone,
            $categoria,
            $subcategoriaFinal,
            $assunto,
            $mensagem,
            $localizacao
        ]);

        $pedido_id = $pdo->lastInsertId();

        $pdo->prepare("
            INSERT INTO pedido_timeline (pedido_id, tipo, titulo, descricao)
            VALUES (?, 'criacao', 'Ocorrência registada', ?)
        ")->execute([$pedido_id, 'Ocorrência submetida pelo cidadão através do site.']);

        if (!empty($_FILES['imagens']['name'][0])) {
            $permitidas = ['jpg', 'jpeg', 'png', 'webp'];

            foreach ($_FILES['imagens']['tmp_name'] as $key => $tmp) {
                if (empty($_FILES['imagens']['name'][$key])) {
                    continue;
                }

                $nomeOriginal = $_FILES['imagens']['name'][$key];
                $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

                if (!in_array($ext, $permitidas)) {
                    continue;
                }

                $novoNome = "pedido_" . $pedido_id . "_" . time() . "_" . $key . "." . $ext;

                if (move_uploaded_file($tmp, "assets/img/" . $novoNome)) {
                    $stmtImg = $pdo->prepare("
                        INSERT INTO pedidos_imagens (pedido_id, imagem)
                        VALUES (?, ?)
                    ");
                    $stmtImg->execute([$pedido_id, $novoNome]);
                }
            }
        }

        $sucesso = true;

        $emailJunta = siteConfig('email_notificacoes', siteConfig('email'));
        // 06/11/2026 (Atalaia, pedido do presidente): aviso à Junta com «Nova ocorrência», ligação direta para a
        // ocorrência no backoffice, mapa e um botão para responder ao freguês por email
        $assuntoJunta = "Nova ocorrência - " . $assunto;
        $baseSite = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https' ? 'https' : 'http')
            . '://' . preg_replace('/[^a-z0-9.\-:]/i', '', (string)($_SERVER['HTTP_HOST'] ?? ''));
        $linkBackoffice = $baseSite . '/admin/ver_pedido.php?id=' . (int)$pedido_id;
        $linkMapa = 'https://www.google.com/maps?q=' . rawurlencode($latitude . ',' . $longitude);
        $estiloBotao = 'display:inline-block;background:#1f3b63;color:#ffffff;text-decoration:none;padding:10px 18px;border-radius:8px;font-weight:bold';

        $htmlJunta = "
            <h2>Nova ocorrência recebida</h2>
            <p><strong>Assunto:</strong> " . htmlspecialchars($assunto) . "</p>
            <p><strong>Código:</strong> " . htmlspecialchars($codigoPedido) . "</p>
            <p><strong>Categoria:</strong> " . htmlspecialchars($categoria) . ($subcategoriaFinal ? " — " . htmlspecialchars($subcategoriaFinal) : "") . "</p>
            <p><strong>Mensagem:</strong><br>" . nl2br(htmlspecialchars($mensagem)) . "</p>
            <p><strong>Localização:</strong> " . htmlspecialchars($localizacao) . " (<a href=\"" . htmlspecialchars($linkMapa) . "\">ver no mapa</a>)</p>
            <hr>
            <p><strong>Comunicada por:</strong> " . htmlspecialchars($nome) . "</p>
            <p><strong>Email:</strong> " . ($email !== '' ? htmlspecialchars($email) : '—') . "</p>
            <p><strong>Telefone:</strong> " . ($telefone !== '' ? htmlspecialchars($telefone) : '—') . "</p>
            <p style=\"margin-top:22px\"><a href=\"" . htmlspecialchars($linkBackoffice) . "\" style=\"$estiloBotao\">Abrir a ocorrência no backoffice</a></p>
            " . ($email !== '' ? "<p><a href=\"mailto:" . htmlspecialchars(rawurlencode($email)) . "?subject=" . rawurlencode('Re: ' . $assunto) . "\">Responder ao freguês por email</a></p>" : "") . "
        ";

        if (!empty($emailJunta)) {
            enviarEmailSistema($emailJunta, $assuntoJunta, $htmlJunta);
        }

        if (!empty($email)) {
            $assuntoCidadao = "Pedido recebido - " . siteConfig('nome_site');

            $htmlCidadao = "
                <h2>Pedido recebido com sucesso</h2>
                <p>Olá " . htmlspecialchars($nome) . ",</p>
                <p>Recebemos o seu pedido:</p>
                <p><strong>" . htmlspecialchars($assunto) . "</strong></p>
                <p><strong>Código do pedido:</strong> " . htmlspecialchars($codigoPedido) . "</p>
                <p>Guarde este código para acompanhar o estado do seu pedido no site.</p>
                <p>A Junta de Freguesia irá analisar a sua comunicação.</p>
                <br>
                <p><strong>" . htmlspecialchars(siteConfig('nome_site')) . "</strong></p>
            ";

            enviarEmailSistema($email, $assuntoCidadao, $htmlCidadao);
        }
    }
}

// Depois de um erro, o formulário volta com o que a pessoa escreveu (antes perdia o assunto, a mensagem e o local).
$assuntoForm = $mensagemForm = $localizacaoForm = $subcategoriaForm = $latForm = $lngForm = '';
$dentroForm = '0';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !$sucesso) {
    if (!$cidadaoLogado) {
        $nomeForm = $nome;
        $emailForm = $email;
        $telefoneForm = $telefone;
    }
    $assuntoForm = $assunto;
    $mensagemForm = $mensagem;
    $localizacaoForm = $localizacao;
    $subcategoriaForm = $subcategoria;
    if (!isset($errosCampo['mapa'])) {
        $latForm = $latitude;
        $lngForm = $longitude;
        $dentroForm = $dentroFreguesia;
    }
}
?>

<section class="pedidos-hero-premium">
    <div class="container">
        <div class="pedidos-hero-content">
            <span><?= te('pedidos.hero.kicker') ?></span>
            <h1><?= te('pedidos.hero.titulo') ?></h1>
            <p>
                <?= te('pedidos.hero.texto') ?>

            </p>

            <div class="pedidos-hero-actions">
                <a href="#formPedido" class="hero-btn"><?= te('pedidos.hero.botao1') ?></a>
                <a href="/consultar-pedido.php" class="hero-btn"><?= te('pedidos.hero.botao2') ?></a>
            </div>
        </div>

        <div class="pedidos-hero-icon"><i class="bi bi-envelope-paper"></i></div>
    </div>
</section>

<section class="section pedidos-intro-section">
    <div class="container">
        <div class="pedidos-info-grid">
            <div class="pedido-info-card">
                <div class="pedido-info-icon"><i class="bi bi-geo-alt"></i></div>
                <h2 class="titulo-h3"><?= te('pedidos.destaques.d1_titulo') ?></h2>
                <p><?= te('pedidos.destaques.d1_texto') ?></p>
            </div>

            <div class="pedido-info-card">
                <div class="pedido-info-icon"><i class="bi bi-camera"></i></div>
                <h2 class="titulo-h3"><?= te('pedidos.destaques.d2_titulo') ?></h2>
                <p><?= te('pedidos.destaques.d2_texto') ?></p>
            </div>

            <div class="pedido-info-card">
                <div class="pedido-info-icon"><i class="bi bi-search"></i></div>
                <h2 class="titulo-h3"><?= te('pedidos.destaques.d3_titulo') ?></h2>
                <p><?= te('pedidos.destaques.d3_texto') ?></p>
            </div>
        </div>
    </div>
</section>

<section class="section pedidos-form-section">
    <div class="container pedidos-grid pedidos-premium-grid">

        <div class="content-box pedidos-form-premium-card">
            <span class="section-kicker"><?= te('pedidos.formulario.kicker') ?></span>
            <h2><?= te('pedidos.formulario.titulo') ?></h2>

            <?php if ($cidadaoLogado): ?>
                <div class="alerta-sucesso">
                    <?= te('pedidos.formulario.autenticado') ?> <strong><?= htmlspecialchars($cidadaoLogado['nome']) ?></strong>.
                    <?= te('pedidos.formulario.autenticado_nota') ?>
                </div>
            <?php endif; ?>

            <?php if ($sucesso): ?>
                <div class="alerta-sucesso">
                    <?= te('pedidos.formulario.sucesso') ?><br>
                    <strong><?= te('pedidos.formulario.sucesso_codigo') ?></strong> <?= htmlspecialchars($codigoPedido) ?><br>
                    <?= te('pedidos.formulario.sucesso_nota') ?>
                </div>
            <?php endif; ?>

            <?php if ($erro): ?>
                <div class="alerta-erro"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="form-publico form-pedido-premium" id="formPedido">
                <input type="text" name="site_web" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;" aria-hidden="true">

                <?php /* etiqueta visível por cima de cada campo (antes o nome do campo só estava no texto de exemplo);
                         o resumo dos erros e a nota ocupam a largura toda da primeira grelha */ ?>
                <div class="form-grid">
                    <?= resumoErros($errosCampo, ['nome' => 'pedNome', 'email' => 'pedEmail', 'telefone' => 'pedTelefone', 'categoria' => 'categoriaOcorrencia', 'assunto' => 'pedAssunto', 'mensagem' => 'pedMensagem', 'mapa' => 'btnLocalizacaoAtual']) ?>
                    <?= notaObrigatorios() ?>
                    <div class="campo">
                        <?= rotulo('pedNome', tx('pedidos.formulario.ph_nome'), true) ?>
                        <input type="text" id="pedNome" name="nome" autocomplete="name"
                               value="<?= htmlspecialchars($nomeForm) ?>"
                               <?= $cidadaoLogado ? 'readonly' : '' ?> required<?= erroAttrs($errosCampo, 'nome') ?>>
                        <?= erroCampo($errosCampo, 'nome') ?>
                    </div>

                    <div class="campo">
                        <?= rotulo('pedEmail', tx('pedidos.formulario.ph_email'), false) ?>
                        <input type="email" id="pedEmail" name="email" autocomplete="email"
                               value="<?= htmlspecialchars($emailForm) ?>"
                               <?= $cidadaoLogado ? 'readonly' : '' ?><?= erroAttrs($errosCampo, 'email') ?>>
                        <?= erroCampo($errosCampo, 'email') ?>
                    </div>
                </div>

                <div class="form-grid">
                    <div class="campo">
                        <?= rotulo('pedTelefone', tx('pedidos.formulario.ph_telefone'), false) ?>
                        <input type="tel" id="pedTelefone" inputmode="tel" autocomplete="tel" maxlength="20" name="telefone"
                               placeholder="Ex.: 912 345 678"
                               value="<?= htmlspecialchars($telefoneForm) ?>"<?= erroAttrs($errosCampo, 'telefone') ?>>
                        <?= erroCampo($errosCampo, 'telefone') ?>
                    </div>

                    <div class="campo">
                        <?= rotulo('categoriaOcorrencia', tx('pedidos.formulario.categoria'), true) ?>
                        <select name="categoria" id="categoriaOcorrencia" required<?= erroAttrs($errosCampo, 'categoria') ?>>
                            <option value="">Escolha…</option>
                            <?php foreach ($categoriasOcorrencia as $cat): ?>
                                <option value="<?= htmlspecialchars($cat['designacao']) ?>" <?= ($categoria ?? '') === $cat['designacao'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['designacao']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <?= erroCampo($errosCampo, 'categoria') ?>
                    </div>
                </div>

                <div class="form-grid">
                    <?php /* só aparece quando a categoria escolhida tem subcategorias (antes ficava desativada à vista) */ ?>
                    <div class="campo" id="campoSubcategoria" hidden>
                        <?= rotulo('subcategoriaOcorrencia', tx('pedidos.formulario.subcategoria'), false) ?>
                        <select name="subcategoria" id="subcategoriaOcorrencia" data-escolhida="<?= htmlspecialchars($subcategoriaForm) ?>">
                            <option value="">Escolha…</option>
                        </select>
                    </div>

                    <div class="campo">
                        <?= rotulo('pedAssunto', tx('pedidos.formulario.ph_assunto'), true) ?>
                        <input type="text" id="pedAssunto" name="assunto" value="<?= htmlspecialchars($assuntoForm) ?>" required<?= erroAttrs($errosCampo, 'assunto') ?>>
                        <?= erroCampo($errosCampo, 'assunto') ?>
                    </div>
                </div>

                <div class="campo">
                    <?= rotulo('localizacao', tx('pedidos.formulario.ph_localizacao'), false) ?>
                    <input type="text" name="localizacao" id="localizacao" value="<?= htmlspecialchars($localizacaoForm) ?>">
                </div>

                <div class="campo">
                    <?= rotulo('pedMensagem', tx('pedidos.formulario.ph_mensagem'), true) ?>
                    <textarea id="pedMensagem" name="mensagem" required<?= erroAttrs($errosCampo, 'mensagem') ?>><?= htmlspecialchars($mensagemForm) ?></textarea>
                    <?= erroCampo($errosCampo, 'mensagem') ?>
                </div>

                <div class="mapa-pedido-block">
                    <div class="mapa-pedido-title">
                        <div>
                            <span><i class="bi bi-geo-alt"></i></span>
                            <strong><?= te('pedidos.formulario.mapa_titulo') ?></strong>
                        </div>
                        <small><?= te('pedidos.formulario.mapa_obrigatorio') ?></small>
                    </div>

                    <button type="button" class="btn-localizacao-atual" id="btnLocalizacaoAtual">
                        <i class="bi bi-crosshair"></i> <?= te('pedidos.formulario.mapa_botao') ?>
                    </button>

                    <div class="mapa-form-info" id="mapaInfoTexto">
                        <?= te('pedidos.formulario.mapa_ajuda') ?>
                    </div>

                    <div id="mapaPedidoForm"></div>
                    <?= erroCampo($errosCampo, 'mapa') ?>
                </div>

                <input type="hidden" name="latitude" id="latitude" value="<?= htmlspecialchars($latForm) ?>">
                <input type="hidden" name="longitude" id="longitude" value="<?= htmlspecialchars($lngForm) ?>">
                <input type="hidden" name="dentro_freguesia" id="dentro_freguesia" value="<?= htmlspecialchars($dentroForm) ?>">

                <label><?= te('pedidos.formulario.anexos_titulo') ?></label>

                <div class="upload-pro">
                    <input type="file" name="imagens[]" id="imagensPedido" multiple accept="image/*">
                    <label for="imagensPedido">
                        <span><i class="bi bi-camera"></i></span>
                        <strong><?= te('pedidos.formulario.anexos_botao') ?></strong>
                        <small id="fileNamePedido"><?= te('pedidos.formulario.anexos_ajuda') ?></small>
                    </label>

                    <div id="previewPedido" class="preview-pedido"></div>
                </div>

                <button type="submit" class="btn-enviar-pedido">
    <i class="bi bi-upload"></i> <?= te('pedidos.formulario.botao') ?>
</button>
            </form>
        </div>

        <div class="content-box pedido-info pedido-info-premium">
            <span class="section-kicker"><?= te('pedidos.passos.titulo') ?></span>
            <h2><?= te('pedidos.passos.subtitulo') ?></h2>

            <div class="pedido-step">
                <strong>1</strong>
                <p><?= te('pedidos.passos.p1') ?></p>
            </div>

            <div class="pedido-step">
                <strong>2</strong>
                <p><?= te('pedidos.passos.p2') ?></p>
            </div>

            <div class="pedido-step">
                <strong>3</strong>
                <p><?= te('pedidos.passos.p3') ?></p>
            </div>

            <div class="pedido-step">
                <strong>4</strong>
                <p><?= te('pedidos.passos.p4') ?></p>
            </div>

            <div class="pedido-step">
                <strong>5</strong>
                <p><?= te('pedidos.passos.p5') ?></p>
            </div>

            <?php if ($cidadaoLogado): ?>
                <div class="pedido-step">
                    <strong>6</strong>
                    <p><?= te('pedidos.passos.p_autenticado') ?></p>
                </div>
            <?php endif; ?>

            <p class="nota">
                <?= te('pedidos.sidebar.aviso_dados') ?>
            </p>

            <a class="hero-btn" href="/consultar-pedido.php">
                <?= te('pedidos.sidebar.botao_consultar') ?>
            </a>

            <?php if ($cidadaoLogado): ?>
                <a class="btn" href="/minha-area.php" style="margin-top:10px;">
                    <?= te('pedidos.sidebar.botao_area') ?>
                </a>
            <?php endif; ?>
        </div>

    </div>
</section>

<script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const mapaDiv = document.getElementById('mapaPedidoForm');
    const formPedido = document.getElementById('formPedido');
    const inputLat = document.getElementById('latitude');
    const inputLng = document.getElementById('longitude');
    const inputDentro = document.getElementById('dentro_freguesia');
    const inputLocalizacao = document.getElementById('localizacao');
    const mapaInfoTexto = document.getElementById('mapaInfoTexto');

    if (!mapaDiv) return;

    const mapa = L.map('mapaPedidoForm').setView([37.9030, -8.5391], 12);

    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; OpenStreetMap'
    }).addTo(mapa);

    let marker = null;
    let geocodeTimeout = null;
    let geojsonData = null;
    let freguesiaLayer = null;

    function pontoEmPoligono(point, polygon) {
        const x = point[0];
        const y = point[1];
        let inside = false;

        for (let i = 0, j = polygon.length - 1; i < polygon.length; j = i++) {
            const xi = polygon[i][0], yi = polygon[i][1];
            const xj = polygon[j][0], yj = polygon[j][1];

            const intersect = ((yi > y) !== (yj > y)) &&
                (x < (xj - xi) * (y - yi) / ((yj - yi) || 0.0000001) + xi);

            if (intersect) inside = !inside;
        }

        return inside;
    }

    function pontoDentroGeoJson(lat, lng) {
        if (!geojsonData || !geojsonData.features) return true;

        const point = [lng, lat];

        for (const feature of geojsonData.features) {
            const geom = feature.geometry;

            if (!geom) continue;

            if (geom.type === 'Polygon') {
                for (const polygon of geom.coordinates) {
                    if (pontoEmPoligono(point, polygon)) return true;
                }
            }

            if (geom.type === 'MultiPolygon') {
                for (const multi of geom.coordinates) {
                    for (const polygon of multi) {
                        if (pontoEmPoligono(point, polygon)) return true;
                    }
                }
            }
        }

        return false;
    }

    function limparSelecao(mensagem) {
        inputLat.value = '';
        inputLng.value = '';
        inputDentro.value = '0';

        if (inputLocalizacao) {
            inputLocalizacao.value = '';
        }

        if (mapaInfoTexto) {
            mapaInfoTexto.innerHTML = mensagem;
            mapaInfoTexto.style.borderLeftColor = '#c92a2a';
            mapaInfoTexto.style.background = '#fff5f5';
            mapaInfoTexto.style.color = '#c92a2a';
        }
    }

    function mostrarInfoOk(mensagem) {
        if (mapaInfoTexto) {
            mapaInfoTexto.innerHTML = mensagem;
            mapaInfoTexto.style.borderLeftColor = 'var(--cor-principal)';
            mapaInfoTexto.style.background = '#f8fafc';
            mapaInfoTexto.style.color = '#4b5563';
        }
    }

    fetch('/assets/geo/freguesia.geojson')
        .then(res => res.json())
        .then(data => {
            geojsonData = data;

            freguesiaLayer = L.geoJSON(data, {
                style: {
                    color: '#ff3b30',
                    weight: 4,
                    opacity: 1,
                    dashArray: '8,6',
                    fillColor: '#ff3b30',
                    fillOpacity: 0.06
                }
            }).addTo(mapa);

            if (freguesiaLayer.getBounds().isValid()) mapa.fitBounds(freguesiaLayer.getBounds(), { padding: [30, 30] });
            freguesiaLayer.bringToFront();
        })
        .catch(() => {
            mostrarInfoOk("Não foi possível carregar o limite da freguesia. O mapa continua funcional.");
        });

    function selecionarPonto(latlng) {
        const lat = latlng.lat.toFixed(6);
        const lng = latlng.lng.toFixed(6);
        const dentro = pontoDentroGeoJson(parseFloat(lat), parseFloat(lng));

        if (marker) {
            marker.setLatLng(latlng);
        } else {
            marker = L.marker(latlng).addTo(mapa);
        }

        if (!dentro) {
            marker.bindPopup("Fora da área da freguesia").openPopup();

            limparSelecao("Este ponto está fora da área da Freguesia de <?= htmlspecialchars(nomeFreguesia()) ?>. Escolha um ponto dentro do limite marcado no mapa.");
            return;
        }

        inputLat.value = lat;
        inputLng.value = lng;
        inputDentro.value = '1';

        marker.bindPopup("A obter morada...").openPopup();

        mostrarInfoOk("A obter morada aproximada...");

        clearTimeout(geocodeTimeout);

        geocodeTimeout = setTimeout(function () {
            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${lat}&lon=${lng}&zoom=18&addressdetails=1`)
                .then(res => res.json())
                .then(data => {
                    const morada = data.display_name || `Coordenadas: ${lat}, ${lng}`;

                    inputLocalizacao.value = morada;
                    marker.setPopupContent(morada);

                    mostrarInfoOk("Local válido dentro da freguesia. A morada foi preenchida automaticamente.");
                })
                .catch(() => {
                    const fallback = `Coordenadas: ${lat}, ${lng}`;

                    inputLocalizacao.value = fallback;
                    marker.setPopupContent("Local selecionado");

                    mostrarInfoOk("Local válido dentro da freguesia. Não foi possível obter morada, mas as coordenadas foram guardadas.");
                });
        }, 300);
    }

    mapa.on('click', function (e) {
        selecionarPonto(e.latlng);
    });

    // depois de um erro no envio, volta a mostrar o ponto que já tinha sido escolhido
    if (inputLat.value && inputLng.value && inputDentro.value === '1') {
        marker = L.marker(L.latLng(parseFloat(inputLat.value), parseFloat(inputLng.value))).addTo(mapa);
        mostrarInfoOk("Local escolhido. Pode carregar noutro ponto do mapa para o mudar.");
    }

    const btnLocalizacaoAtual = document.getElementById('btnLocalizacaoAtual');

    if (btnLocalizacaoAtual) {
        if (!navigator.geolocation) {
            btnLocalizacaoAtual.style.display = 'none';
        } else {
            btnLocalizacaoAtual.addEventListener('click', function () {
                btnLocalizacaoAtual.disabled = true;
                btnLocalizacaoAtual.classList.add('a-localizar');
                mostrarInfoOk("A obter a localização atual...");

                navigator.geolocation.getCurrentPosition(
                    function (pos) {
                        const latlng = L.latLng(pos.coords.latitude, pos.coords.longitude);
                        mapa.setView(latlng, 17);
                        selecionarPonto(latlng);
                        btnLocalizacaoAtual.disabled = false;
                        btnLocalizacaoAtual.classList.remove('a-localizar');
                    },
                    function () {
                        limparSelecao("Não foi possível obter a localização atual. Verifique se deu permissão ao browser, ou escolha o local no mapa.");
                        btnLocalizacaoAtual.disabled = false;
                        btnLocalizacaoAtual.classList.remove('a-localizar');
                    },
                    { enableHighAccuracy: true, timeout: 10000, maximumAge: 0 }
                );
            });
        }
    }

    if (formPedido) {
        formPedido.addEventListener('submit', function (e) {
            if (!inputLat.value || !inputLng.value || inputDentro.value !== '1') {
                e.preventDefault();
                limparSelecao("Antes de enviar, selecione no mapa um ponto dentro da Freguesia de <?= htmlspecialchars(nomeFreguesia()) ?>.");
                mapa.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }
        });
    }

    setTimeout(function () {
        mapa.invalidateSize();
    }, 500);
});
</script>


<script>
document.addEventListener('DOMContentLoaded', function () {
    const assuntosPorCategoria = <?= json_encode($assuntosPorCategoria, JSON_UNESCAPED_UNICODE) ?>;
    const selectCategoria = document.getElementById('categoriaOcorrencia');
    const selectSubcategoria = document.getElementById('subcategoriaOcorrencia');

    if (!selectCategoria || !selectSubcategoria) return;

    function criarOpcao(valor, texto) {
        const opt = document.createElement('option');
        opt.value = valor;
        opt.textContent = texto;
        return opt;
    }

    // A subcategoria só aparece quando a categoria tem subcategorias (revelação progressiva, em vez de um campo
    // desativado à vista — checklist «Transação» da AMA, 18/11/2026). Depois de um erro, volta a escolhida.
    const campoSubcategoria = document.getElementById('campoSubcategoria');
    function atualizarSubcategorias() {
        const lista = assuntosPorCategoria[selectCategoria.value] || [];
        const escolhida = selectSubcategoria.value || selectSubcategoria.dataset.escolhida || '';
        selectSubcategoria.dataset.escolhida = '';

        selectSubcategoria.replaceChildren(criarOpcao('', 'Escolha…'));

        for (const designacao of lista) {
            const opt = criarOpcao(designacao, designacao);
            if (designacao === escolhida) opt.selected = true;
            selectSubcategoria.appendChild(opt);
        }

        selectSubcategoria.disabled = lista.length === 0;
        if (campoSubcategoria) campoSubcategoria.hidden = lista.length === 0;
    }

    selectCategoria.addEventListener('change', atualizarSubcategorias);
    atualizarSubcategorias();
});
</script>

<?php require_once "includes/footer.php"; ?>