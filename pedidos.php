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
    background:#D4AA00;
    color:#11151B;
    font-size:18px;
    font-weight:800;
    cursor:pointer;
    transition:.25s;
    box-shadow:0 10px 25px rgba(212,170,0,.35);
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

    if ($siteWeb !== '') {
        // Bot apanhado no honeypot: finge sucesso, não processa nem envia nada.
        $sucesso = true;
    } elseif (!$nome || !$categoria || !$assunto || !$mensagem) {
        $erro = "Preencha todos os campos obrigatórios.";
    } elseif ($email !== '' && !emailValido($email)) {
        $erro = "Indique um email válido.";
    } elseif (!$latitude || !$longitude) {
        $erro = "Selecione o local do pedido no mapa.";
    } elseif ($dentroFreguesia !== '1') {
        $erro = "O local selecionado está fora da área da Freguesia de Atalaia e Alto Estanqueiro-Jardia.";
    } elseif (submissaoRecente('pedido')) {
        $erro = "Já recebemos um pedido seu há pouco. Aguarde uns instantes antes de submeter outro.";
    } else {

        do {
            $codigoPedido = "AAEJ-" . date('Y') . "-" . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
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
        $assuntoJunta = "Novo pedido à Junta - " . $assunto;

        $htmlJunta = "
            <h2>Novo pedido recebido</h2>
            <p><strong>Código:</strong> " . htmlspecialchars($codigoPedido) . "</p>
            <p><strong>Nome:</strong> " . htmlspecialchars($nome) . "</p>
            <p><strong>Email:</strong> " . htmlspecialchars($email) . "</p>
            <p><strong>Telefone:</strong> " . htmlspecialchars($telefone) . "</p>
            <p><strong>Categoria:</strong> " . htmlspecialchars($categoria) . "</p>
            <p><strong>Subcategoria:</strong> " . htmlspecialchars($subcategoriaFinal ?? '') . "</p>
            <p><strong>Assunto:</strong> " . htmlspecialchars($assunto) . "</p>
            <p><strong>Localização:</strong> " . htmlspecialchars($localizacao) . "</p>
            <p><strong>Coordenadas:</strong> " . htmlspecialchars($latitude) . ", " . htmlspecialchars($longitude) . "</p>
            <p><strong>Mensagem:</strong><br>" . nl2br(htmlspecialchars($mensagem)) . "</p>
            <p>Consulte este pedido no backoffice.</p>
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
?>

<section class="pedidos-hero-premium">
    <div class="container">
        <div class="pedidos-hero-content">
            <span>Junta Virtual</span>
            <h1>Pedidos à Junta</h1>
            <p>
                Envie pedidos, sugestões ou situações que pretenda comunicar à Junta de Freguesia.
                Indique o local no mapa e acompanhe o processo de forma simples e transparente.
            </p>

            <div class="pedidos-hero-actions">
                <a href="#formPedido" class="hero-btn">Criar pedido</a>
                <a href="/consultar-pedido.php" class="hero-btn">Consultar pedido</a>
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
                <h3>Escolha no mapa</h3>
                <p>Selecione o local exato do pedido dentro da área da freguesia.</p>
            </div>

            <div class="pedido-info-card">
                <div class="pedido-info-icon"><i class="bi bi-camera"></i></div>
                <h3>Anexe imagens</h3>
                <p>Adicione fotografias para ajudar a Junta a analisar melhor a situação.</p>
            </div>

            <div class="pedido-info-card">
                <div class="pedido-info-icon"><i class="bi bi-search"></i></div>
                <h3>Acompanhe online</h3>
                <p>Receba um código para consultar o estado do pedido posteriormente.</p>
            </div>
        </div>
    </div>
</section>

<section class="section pedidos-form-section">
    <div class="container pedidos-grid pedidos-premium-grid">

        <div class="content-box pedidos-form-premium-card">
            <span class="section-kicker">Participação cidadã</span>
            <h2>Submeter pedido</h2>

            <?php if ($cidadaoLogado): ?>
                <div class="alerta-sucesso">
                    Está autenticado como <strong><?= htmlspecialchars($cidadaoLogado['nome']) ?></strong>.
                    Este pedido ficará associado à sua área pessoal.
                </div>
            <?php endif; ?>

            <?php if ($sucesso): ?>
                <div class="alerta-sucesso">
                    Pedido enviado com sucesso.<br>
                    <strong>Código do pedido:</strong> <?= htmlspecialchars($codigoPedido) ?><br>
                    Guarde este código para acompanhar o estado do seu pedido.
                </div>
            <?php endif; ?>

            <?php if ($erro): ?>
                <div class="alerta-erro"><?= htmlspecialchars($erro) ?></div>
            <?php endif; ?>

            <form method="POST" enctype="multipart/form-data" class="form-publico form-pedido-premium" id="formPedido">
                <input type="text" name="site_web" value="" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;" aria-hidden="true">

                <div class="form-grid">
                    <input 
                        type="text" 
                        name="nome" 
                        placeholder="O seu nome *" 
                        value="<?= htmlspecialchars($nomeForm) ?>"
                        <?= $cidadaoLogado ? 'readonly' : '' ?>
                        required
                    >

                    <input 
                        type="email" 
                        name="email" 
                        placeholder="Email"
                        value="<?= htmlspecialchars($emailForm) ?>"
                        <?= $cidadaoLogado ? 'readonly' : '' ?>
                    >
                </div>

                <div class="form-grid">
                    <input 
                        type="text" 
                        name="telefone" 
                        placeholder="Telefone"
                        value="<?= htmlspecialchars($telefoneForm) ?>"
                    >

                    <select name="categoria" id="categoriaOcorrencia" required>
                        <option value="">Escolha a categoria *</option>
                        <?php foreach ($categoriasOcorrencia as $cat): ?>
                            <option value="<?= htmlspecialchars($cat['designacao']) ?>" <?= ($categoria ?? '') === $cat['designacao'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['designacao']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="form-grid">
                    <select name="subcategoria" id="subcategoriaOcorrencia">
                        <option value="">Subcategoria (opcional)</option>
                    </select>

                    <input type="text" name="assunto" placeholder="Assunto *" required>
                </div>

                <input type="text" name="localizacao" id="localizacao" placeholder="Localização / Rua / Lugar">

                <textarea name="mensagem" placeholder="Descreva o pedido *" required></textarea>

                <div class="mapa-pedido-block">
                    <div class="mapa-pedido-title">
                        <div>
                            <span><i class="bi bi-geo-alt"></i></span>
                            <strong>Localização no mapa</strong>
                        </div>
                        <small>Obrigatório</small>
                    </div>

                    <button type="button" class="btn-localizacao-atual" id="btnLocalizacaoAtual">
                        <i class="bi bi-crosshair"></i> Usar localização atual
                    </button>

                    <div class="mapa-form-info" id="mapaInfoTexto">
                        Clique dentro da área da Freguesia de Atalaia e Alto Estanqueiro-Jardia para indicar o local exato do pedido.
                    </div>

                    <div id="mapaPedidoForm"></div>
                </div>

                <input type="hidden" name="latitude" id="latitude">
                <input type="hidden" name="longitude" id="longitude">
                <input type="hidden" name="dentro_freguesia" id="dentro_freguesia" value="0">

                <label>Anexar imagens</label>

                <div class="upload-pro">
                    <input type="file" name="imagens[]" id="imagensPedido" multiple accept="image/*">
                    <label for="imagensPedido">
                        <span><i class="bi bi-camera"></i></span>
                        <strong>Escolher imagens</strong>
                        <small id="fileNamePedido">Pode selecionar várias imagens JPG, PNG ou WEBP</small>
                    </label>

                    <div id="previewPedido" class="preview-pedido"></div>
                </div>

                <button type="submit" class="btn-enviar-pedido">
    <i class="bi bi-upload"></i> Enviar Pedido
</button>
            </form>
        </div>

        <aside class="content-box pedido-info pedido-info-premium">
            <span class="section-kicker">Como funciona?</span>
            <h2>Processo simples e transparente</h2>

            <div class="pedido-step">
                <strong>1</strong>
                <p>Preenche o formulário com a informação do pedido.</p>
            </div>

            <div class="pedido-step">
                <strong>2</strong>
                <p>Clica dentro da área da freguesia no mapa.</p>
            </div>

            <div class="pedido-step">
                <strong>3</strong>
                <p>Se o ponto estiver fora da freguesia, o sistema bloqueia o envio.</p>
            </div>

            <div class="pedido-step">
                <strong>4</strong>
                <p>A Junta recebe o pedido no backoffice.</p>
            </div>

            <div class="pedido-step">
                <strong>5</strong>
                <p>Recebe um código para acompanhar o estado do pedido.</p>
            </div>

            <?php if ($cidadaoLogado): ?>
                <div class="pedido-step">
                    <strong>6</strong>
                    <p>O pedido fica disponível automaticamente na sua área pessoal.</p>
                </div>
            <?php endif; ?>

            <p class="nota">
                Os dados enviados são utilizados apenas para análise e eventual contacto sobre o pedido.
            </p>

            <a class="hero-btn" href="/consultar-pedido.php">
                Consultar pedido
            </a>

            <?php if ($cidadaoLogado): ?>
                <a class="btn" href="/minha-area.php" style="margin-top:10px;">
                    Minha Área
                </a>
            <?php endif; ?>
        </aside>

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

            mapa.fitBounds(freguesiaLayer.getBounds(), { padding: [30, 30] });
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

            limparSelecao("Este ponto está fora da área da Freguesia de Atalaia e Alto Estanqueiro-Jardia. Escolha um ponto dentro do limite marcado no mapa.");
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
                limparSelecao("Antes de enviar, selecione no mapa um ponto dentro da Freguesia de Atalaia e Alto Estanqueiro-Jardia.");
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

    function atualizarSubcategorias() {
        const lista = assuntosPorCategoria[selectCategoria.value] || [];

        selectSubcategoria.replaceChildren(criarOpcao('', 'Subcategoria (opcional)'));

        for (const designacao of lista) {
            selectSubcategoria.appendChild(criarOpcao(designacao, designacao));
        }

        selectSubcategoria.disabled = lista.length === 0;
    }

    selectCategoria.addEventListener('change', atualizarSubcategorias);
    atualizarSubcategorias();
});
</script>

<?php require_once "includes/footer.php"; ?>