<?php
require_once "includes/db.php";
require_once "includes/galeria.php";
require_once "includes/config.php";
require_once "includes/mail_helper.php";

$id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

$stmt = $pdo->prepare("SELECT * FROM eventos WHERE id = ?");
$stmt->execute([$id]);
$evento = $stmt->fetch(PDO::FETCH_ASSOC);

// Inscrição no evento (ex.: excursão) — tratada ANTES do header.php para poder
// redirecionar com header("Location: ...") depois de gravar com sucesso.
$inscricaoErro = '';

$camposExtraEvento = [];
if ($evento && !empty($evento['inscricoes_ativas'])) {
    $stmtCampos = $pdo->prepare("SELECT * FROM eventos_campos_extra WHERE evento_id = ? ORDER BY ordem ASC, id ASC");
    $stmtCampos->execute([$id]);
    $camposExtraEvento = $stmtCampos->fetchAll(PDO::FETCH_ASSOC);
}

if ($evento && !empty($evento['inscricoes_ativas']) && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['inscrever_evento'])) {
    $nomeInsc = trim($_POST['nome'] ?? '');
    $emailInsc = trim($_POST['email'] ?? '');
    $telefoneInsc = trim($_POST['telefone'] ?? '');
    $numPessoasInsc = max(1, (int)($_POST['num_pessoas'] ?? 1));
    $observacoesInsc = trim($_POST['observacoes'] ?? '');

    $prazoInscricao = $evento['inscricoes_ate'] ?: $evento['data_evento'];

    // Contacto: email OU telefone chega, um dos dois é obrigatório (não os dois).
    if (!$nomeInsc) {
        $inscricaoErro = "Preencha o nome.";
    } elseif (!$emailInsc && !$telefoneInsc) {
        $inscricaoErro = "Indique um email ou um telefone, para ser possível contactá-lo(a).";
    } elseif ($emailInsc && !emailValido($emailInsc)) {
        $inscricaoErro = "O email indicado não é válido ou não existe.";
    } elseif ($telefoneInsc && !telefoneValido($telefoneInsc)) {
        $inscricaoErro = "Indique um número de telefone válido (ex.: 912 345 678, ou com o indicativo do país se for estrangeiro).";
    } elseif ($prazoInscricao && strtotime($prazoInscricao) < time()) {
        $inscricaoErro = "As inscrições para este evento já encerraram.";
    } elseif (!empty($evento['inscricoes_vagas'])) {
        $stmtVagas = $pdo->prepare("SELECT COALESCE(SUM(num_pessoas),0) FROM eventos_inscricoes WHERE evento_id = ?");
        $stmtVagas->execute([$id]);
        $restantes = (int)$evento['inscricoes_vagas'] - (int)$stmtVagas->fetchColumn();

        if ($restantes <= 0) {
            $inscricaoErro = "Lamentamos, as vagas para este evento já se esgotaram.";
        } elseif ($numPessoasInsc > $restantes) {
            $inscricaoErro = "Só restam {$restantes} vaga(s) — reduza o número de pessoas ou contacte a Junta.";
        }
    }

    // Campos extra deste evento (definidos no backoffice)
    $respostasExtra = [];
    $htmlCamposExtra = '';
    if (!$inscricaoErro) {
        foreach ($camposExtraEvento as $campo) {
            $nomeCampo = 'campo_' . $campo['id'];
            if ($campo['tipo'] === 'checkbox') {
                $valor = isset($_POST[$nomeCampo]) ? 'Sim' : 'Não';
            } else {
                $valor = trim($_POST[$nomeCampo] ?? '');
                if ($campo['obrigatorio'] && $valor === '') {
                    $inscricaoErro = "Preencha o campo \"" . $campo['label'] . "\".";
                    break;
                }
            }
            $respostasExtra[$campo['id']] = $valor;
            $htmlCamposExtra .= "<p><strong>" . htmlspecialchars($campo['label']) . ":</strong> " . htmlspecialchars($valor) . "</p>";
        }
    }

    if (!$inscricaoErro) {
        $ins = $pdo->prepare("INSERT INTO eventos_inscricoes (evento_id, nome, email, telefone, num_pessoas, observacoes, campos_extra) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([$id, $nomeInsc, $emailInsc, $telefoneInsc, $numPessoasInsc, $observacoesInsc, !empty($respostasExtra) ? json_encode($respostasExtra, JSON_UNESCAPED_UNICODE) : null]);

        $emailJunta = siteConfig('email_notificacoes', siteConfig('email'));
        if (!empty($emailJunta)) {
            $htmlJunta = "<h2>Nova inscrição em evento</h2>"
                . "<p><strong>Evento:</strong> " . htmlspecialchars($evento['titulo']) . "</p>"
                . "<p><strong>Nome:</strong> " . htmlspecialchars($nomeInsc) . "</p>"
                . ($emailInsc ? "<p><strong>Email:</strong> " . htmlspecialchars($emailInsc) . "</p>" : "")
                . ($telefoneInsc ? "<p><strong>Telefone:</strong> " . htmlspecialchars($telefoneInsc) . "</p>" : "")
                . "<p><strong>Nº de pessoas:</strong> {$numPessoasInsc}</p>"
                . $htmlCamposExtra
                . "<p><strong>Observações:</strong><br>" . nl2br(htmlspecialchars($observacoesInsc)) . "</p>";
            enviarEmailSistema($emailJunta, "Nova inscrição: " . $evento['titulo'], $htmlJunta);
        }

        if ($emailInsc) {
            $htmlCidadao = "<h2>Inscrição confirmada</h2>"
                . "<p>Olá " . htmlspecialchars($nomeInsc) . ",</p>"
                . "<p>A sua inscrição no evento <strong>" . htmlspecialchars($evento['titulo']) . "</strong> foi registada com sucesso.</p>"
                . "<p><strong>Data do evento:</strong> " . date('d/m/Y H:i', strtotime($evento['data_evento'])) . "</p>"
                . "<p><strong>Nº de pessoas:</strong> {$numPessoasInsc}</p>"
                . "<br><p><strong>" . siteConfig('nome_site') . "</strong></p>";
            enviarEmailSistema($emailInsc, "Inscrição confirmada: " . $evento['titulo'], $htmlCidadao);
        }

        header("Location: /evento.php?id=" . $id . "&inscrito=1");
        exit;
    }
}

// Meta tags Open Graph para partilhas (têm de ser calculadas ANTES do header.php,
// com o $evento já carregado — mesmo padrão usado em noticia.php)
$siteBaseUrl = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http') . '://' . ($_SERVER['HTTP_HOST'] ?? 'localhost');

$meta_titulo = $evento['titulo'] ?? 'Evento';

$meta_descricao = mb_substr(strip_tags($evento['descricao'] ?? ''), 0, 160);

$meta_url = $siteBaseUrl . "/evento.php?id=" . $id;

$meta_imagem = !empty($evento['imagem'])
    ? $siteBaseUrl . "/assets/img/" . $evento['imagem']
    : $siteBaseUrl . "/assets/img/" . (siteConfig('logo') ?: 'freguesia-1.jpg');

require_once "includes/header.php";
?>

<?php
if (!$evento) {
    echo '<section class="section"><div class="container content-box"><h1>Evento não encontrado</h1><p>O evento solicitado não existe.</p><a href="/eventos.php" class="btn">Voltar aos eventos</a></div></section>';
    require_once "includes/footer.php";
    exit;
}
require_once __DIR__ . "/includes/calendario.php";
seoEvento($evento);

$outros = $pdo->prepare("
    SELECT * FROM eventos
    WHERE id <> ?
    ORDER BY data_evento DESC, id DESC
    LIMIT 3
");
$outros->execute([$id]);
$outrosEventos = $outros->fetchAll(PDO::FETCH_ASSOC);

$imagem = !empty($evento['imagem']) ? "/assets/img/" . htmlspecialchars($evento['imagem']) : "";

$fotosGaleriaEvento = fotosSemRepetidas(imagensDaOrigem($pdo, 'evento', $id), $imagem);

$todasImagensEvento = [];
if ($imagem) $todasImagensEvento[] = $imagem;
foreach ($fotosGaleriaEvento as $g) $todasImagensEvento[] = imagemGaleriaUrl($g['ficheiro']);
$coverOffsetEvento = $imagem ? 1 : 0;

$protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https://' : 'http://';
$urlAtualEvento = $protocolo . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
$tituloPartilhaEvento = $evento['titulo'] ?? 'Evento';
$facebookShareEvento = 'https://www.facebook.com/sharer/sharer.php?u=' . urlencode($urlAtualEvento);
$whatsappShareEvento = 'https://wa.me/?text=' . urlencode($tituloPartilhaEvento . ' - ' . $urlAtualEvento);
$emailShareEvento = 'mailto:?subject=' . rawurlencode($tituloPartilhaEvento) . '&body=' . rawurlencode($tituloPartilhaEvento . "\n\n" . $urlAtualEvento);
?>

<style>
.evento-detalhe-hero{
    background:linear-gradient(135deg,#242A30,#11151B);
    color:white;
    padding:80px 0;
}
.evento-detalhe-hero span{
    color:var(--ouro, #C8A02E);
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.8px;
    font-size:13px;
}
.evento-detalhe-hero h1{
    font-size:clamp(34px,5vw,54px);
    margin:12px 0 14px;
    letter-spacing:-1px;
}
.evento-detalhe-hero .evento-detalhe-data{
    display:inline-flex;
    align-items:center;
    gap:9px;
    background:rgba(255,255,255,.16);
    border:1px solid rgba(255,255,255,.3);
    padding:10px 16px;
    border-radius:999px;
    font-weight:800;
    color:#ffffff;
}
.evento-detalhe-card{
    background:white;
    border-radius:30px;
    padding:28px;
    box-shadow:0 20px 60px rgba(0,0,0,.10);
    margin-top:-45px;
    position:relative;
    z-index:3;
}
/* Aparece inteira, sem cortes — mesmo critério do noticia.php/ponto.php:
   a caixa ajusta-se à imagem em vez de a obrigar a preencher e cortar. */
.evento-detalhe-img{
    display:block;
    width:auto;
    max-width:100%;
    max-height:480px;
    margin:0 auto 25px;
    border-radius:24px;
    cursor:zoom-in;
    transition:.25s;
}
.evento-detalhe-img:hover{opacity:.92;}
/* LIGHTBOX */
/* Galeria de fotos do evento */
.evento-galeria{margin:30px 0 6px;}
.evento-galeria h3{color:#11151B;margin:0 0 16px;}
.evento-galeria-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(180px,1fr));gap:14px;}
.evento-galeria-item{display:block;border-radius:16px;overflow:hidden;box-shadow:0 10px 26px rgba(0,0,0,.10);transition:.25s;cursor:zoom-in;}
.evento-galeria-item:hover{transform:translateY(-4px);box-shadow:0 18px 40px rgba(0,0,0,.16);opacity:.9;}
.evento-galeria-item img{width:100%;height:170px;object-fit:cover;display:block;}
/* Partilhar evento */
.evento-share-box{margin:28px 0 8px;padding:22px;border-radius:22px;background:linear-gradient(135deg,#f8fafc,#ffffff);border:1px solid #e5e7eb;box-shadow:0 14px 34px rgba(0,0,0,.06);}
.evento-share-head{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:16px;}
.evento-share-head strong{color:#11151B;font-size:18px;}
.evento-share-head span{color:#64748b;font-size:13px;font-weight:800;}
.evento-share-buttons{display:flex;gap:12px;flex-wrap:wrap;}
.share-btn{border:0;min-height:46px;padding:0 16px;border-radius:15px;display:inline-flex;align-items:center;gap:9px;text-decoration:none;color:white;font-weight:900;cursor:pointer;transition:.25s ease;box-shadow:0 12px 28px rgba(0,0,0,.12);font-family:inherit;font-size:14px;}
.share-btn:hover{transform:translateY(-4px);box-shadow:0 18px 40px rgba(0,0,0,.18);filter:brightness(1.04);}
.share-facebook{background:linear-gradient(135deg,#1877f2,#0d47a1);}
.share-whatsapp{background:linear-gradient(135deg,#25d366,#128c7e);}
.share-copy{background:linear-gradient(135deg,#242A30,#11151B);}
.share-email{background:linear-gradient(135deg,#64748b,#334155);}
.share-toast{display:none;margin-top:12px;background:#dcfce7;color:#166534;padding:10px 12px;border-radius:12px;font-weight:900;font-size:13px;}
.share-toast.show{display:block;}
.evento-detalhe-texto{
    color:#52606d;
    line-height:1.9;
    font-size:17px;
    white-space:pre-line;
}
.evento-inscricao-box{margin:28px 0;padding:24px;border-radius:22px;background:linear-gradient(135deg,#f8fafc,#ffffff);border:1px solid #e5e7eb;box-shadow:0 14px 34px rgba(0,0,0,.06);}
.evento-inscricao-head{display:flex;justify-content:space-between;align-items:center;gap:14px;flex-wrap:wrap;margin-bottom:14px;}
.evento-inscricao-head strong{color:#11151B;font-size:19px;display:flex;align-items:center;gap:8px;}
.evento-inscricao-head span{background:#dcfce7;color:#166534;font-weight:900;font-size:13px;padding:6px 12px;border-radius:999px;}
.evento-inscricao-form{display:flex;flex-direction:column;gap:14px;}
.evento-inscricao-nota{margin:0;color:#64748b;font-weight:700;font-size:13px;}
.evento-inscricao-grid{display:grid;grid-template-columns:1fr 1fr;gap:14px;}
.evento-campo{display:flex;flex-direction:column;gap:6px;}
.evento-campo label{font-weight:800;color:#11151B;font-size:13px;}
.evento-campo-checkbox{justify-content:flex-end;}
.evento-campo-checkbox label{display:flex;align-items:center;gap:8px;font-weight:800;color:#11151B;font-size:15px;}
.evento-campo-checkbox input[type=checkbox]{width:auto;flex-shrink:0;}
.evento-inscricao-form input,.evento-inscricao-form textarea{border:1px solid #dbe4ee;background:#f8fafc;border-radius:14px;padding:12px 14px;font-weight:700;font-family:inherit;font-size:15px;color:#11151B;box-sizing:border-box;width:100%;}
.evento-inscricao-form textarea{min-height:80px;resize:vertical;}
.evento-inscricao-erro{background:#fee2e2;color:#991b1b;padding:12px 14px;border-radius:12px;font-weight:800;margin-bottom:4px;}
.evento-inscricao-sucesso{background:#dcfce7;color:#166534;padding:14px 16px;border-radius:14px;font-weight:800;display:flex;align-items:center;gap:9px;}
.evento-inscricao-fechado{background:#f1f5f9;color:#475569;padding:14px 16px;border-radius:14px;font-weight:800;margin:0;}
@media(max-width:650px){.evento-inscricao-grid{grid-template-columns:1fr;}}
.evento-detalhe-actions{
    margin-top:28px;
    display:flex;
    gap:12px;
    flex-wrap:wrap;
}
.evento-outros{
    margin-top:34px;
}
.evento-outros h3{
    color:#11151B;
    margin-bottom:16px;
}
.evento-outros-item{
    display:block;
    background:white;
    border:1px solid #eef2f7;
    border-radius:16px;
    padding:14px 18px;
    margin-bottom:10px;
    text-decoration:none;
    color:#11151B;
    transition:.2s ease;
}
.evento-outros-item:hover{
    transform:translateX(4px);
    box-shadow:0 12px 28px rgba(0,0,0,.08);
}
.evento-outros-item strong{display:block;}
.evento-outros-item small{color:#64748b;font-weight:800;}
</style>

<section class="evento-detalhe-hero">
    <div class="container">
        <span><i class="bi bi-calendar-event-fill"></i> <?= te('evento.hero.kicker') ?><?= !empty($evento['categoria']) ? ' · ' . htmlspecialchars($evento['categoria']) : '' ?></span>
        <h1><?= htmlspecialchars($evento['titulo']) ?></h1>
        <?php if (!empty($evento['data_evento'])): require_once __DIR__ . "/includes/calendario.php"; ?>
            <?php $fmt = eventoDiaInteiro($evento) ? 'd/m/Y' : 'd/m/Y H:i'; /* sem hora: não mostrar "00:00" */ ?>
            <div class="evento-detalhe-data badge">
                <i class="bi bi-calendar-event-fill"></i>
                <?= date($fmt, strtotime($evento['data_evento'])) ?><?php if (!empty($evento['data_fim'])): ?> &ndash; <?= date($fmt, strtotime($evento['data_fim'])) ?><?php endif; ?>
                <?php if ($faltam = eventoFaltam($evento)): ?><span class="cal-faltam"><?= $faltam ?></span><?php endif; ?>
            </div>
            <?= botoesCalendario($evento) ?>
        <?php endif; ?>
    </div>
</section>

<section class="section">
    <div class="container">
        <div class="evento-detalhe-card">

            <?php if ($imagem): ?>
                <button type="button" class="lb-trigger" onclick="lbAbrir(todasImagensEvento, 0)" title="Clique para ver em tamanho completo" data-carrossel data-fotos="<?= htmlspecialchars(json_encode($todasImagensEvento, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)) ?>">
                    <img class="evento-detalhe-img" src="<?= $imagem ?>" alt="<?= htmlspecialchars($evento['titulo']) ?>" style="object-position:<?= (int)($evento['imagem_foco_x'] ?? 50) ?>% <?= (int)($evento['imagem_foco_y'] ?? 50) ?>%">
                </button>
            <?php endif; ?>

            <div class="evento-detalhe-texto"><?= nl2br(htmlspecialchars($evento['descricao'] ?? '')) ?></div>

            <?php if (!empty($evento['inscricoes_ativas'])):
                $prazoInscricaoView = $evento['inscricoes_ate'] ?: $evento['data_evento'];
                $prazoPassou = $prazoInscricaoView && strtotime($prazoInscricaoView) < time();

                $restantesView = null;
                if (!empty($evento['inscricoes_vagas'])) {
                    $stmtVagasView = $pdo->prepare("SELECT COALESCE(SUM(num_pessoas),0) FROM eventos_inscricoes WHERE evento_id = ?");
                    $stmtVagasView->execute([$id]);
                    $restantesView = (int)$evento['inscricoes_vagas'] - (int)$stmtVagasView->fetchColumn();
                }
                $esgotado = $restantesView !== null && $restantesView <= 0;
            ?>
                <div class="evento-inscricao-box">
                    <div class="evento-inscricao-head">
                        <strong><i class="bi bi-clipboard2-check"></i> <?= te('evento.inscricoes.titulo') ?></strong>
                        <?php if ($restantesView !== null && !$esgotado): ?>
                            <span><?= $restantesView ?> <?= te('evento.inscricoes.vagas') ?></span>
                        <?php endif; ?>
                    </div>

                    <?php if (isset($_GET['inscrito'])): ?>
                        <div class="evento-inscricao-sucesso"><i class="bi bi-check-circle-fill"></i> <?= te('evento.inscricoes.sucesso') ?></div>
                    <?php elseif ($prazoPassou): ?>
                        <p class="evento-inscricao-fechado"><?= te('evento.inscricoes.encerradas') ?></p>
                    <?php elseif ($esgotado): ?>
                        <p class="evento-inscricao-fechado"><?= te('evento.inscricoes.esgotadas') ?></p>
                    <?php else: ?>
                        <?php if ($inscricaoErro): ?>
                            <div class="evento-inscricao-erro"><?= htmlspecialchars($inscricaoErro) ?></div>
                        <?php endif; ?>
                        <form method="POST" class="evento-inscricao-form" id="formInscricaoEvento">
                            <input type="hidden" name="inscrever_evento" value="1">
                            <p class="evento-inscricao-nota"><?= te('evento.inscricoes.nota') ?></p>
                            <div class="evento-inscricao-grid">
                                <div class="evento-campo">
                                    <label for="inscNome"><?= te('evento.inscricoes.campo_nome') ?></label>
                                    <input type="text" id="inscNome" name="nome" required value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">
                                </div>
                                <div class="evento-campo">
                                    <label for="inscEmail">Email</label>
                                    <input type="email" id="inscEmail" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
                                </div>
                                <div class="evento-campo">
                                    <label for="inscTelefone">Telefone</label>
                                    <input type="tel" id="inscTelefone" name="telefone" value="<?= htmlspecialchars($_POST['telefone'] ?? '') ?>">
                                </div>
                                <div class="evento-campo">
                                    <label for="inscNumPessoas"><?= te('evento.inscricoes.campo_pessoas') ?></label>
                                    <input type="number" id="inscNumPessoas" name="num_pessoas" min="1" <?= $restantesView !== null ? 'max="' . $restantesView . '"' : '' ?> value="<?= htmlspecialchars($_POST['num_pessoas'] ?? '1') ?>">
                                </div>

                                <?php foreach ($camposExtraEvento as $campo): $nomeCampoView = 'campo_' . $campo['id']; ?>
                                    <?php if ($campo['tipo'] === 'checkbox'): ?>
                                        <div class="evento-campo evento-campo-checkbox">
                                            <label>
                                                <input type="checkbox" name="<?= $nomeCampoView ?>" <?= !empty($_POST[$nomeCampoView]) ? 'checked' : '' ?>>
                                                <?= htmlspecialchars($campo['label']) ?><?= $campo['obrigatorio'] ? ' *' : '' ?>
                                            </label>
                                        </div>
                                    <?php else: ?>
                                        <div class="evento-campo">
                                            <label for="<?= $nomeCampoView ?>"><?= htmlspecialchars($campo['label']) ?><?= $campo['obrigatorio'] ? ' *' : '' ?></label>
                                            <input type="text" id="<?= $nomeCampoView ?>" name="<?= $nomeCampoView ?>" <?= $campo['obrigatorio'] ? 'required' : '' ?> value="<?= htmlspecialchars($_POST[$nomeCampoView] ?? '') ?>">
                                        </div>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                            <div class="evento-campo">
                                <label for="inscObservacoes"><?= te('evento.inscricoes.campo_obs') ?></label>
                                <textarea id="inscObservacoes" name="observacoes"><?= htmlspecialchars($_POST['observacoes'] ?? '') ?></textarea>
                            </div>
                            <button type="submit" class="hero-btn"><i class="bi bi-send-fill"></i> <?= te('evento.inscricoes.botao') ?></button>
                        </form>
                        <script>
                        document.getElementById('formInscricaoEvento').addEventListener('submit', function (e) {
                            var email = document.getElementById('inscEmail').value.trim();
                            var telefone = document.getElementById('inscTelefone').value.trim();
                            if (!email && !telefone) {
                                e.preventDefault();
                                alert('Indique um email ou um telefone, para ser possível contactá-lo(a).');
                            }
                        });
                        </script>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <?php if (!empty($fotosGaleriaEvento)): ?>
                <div class="evento-galeria" data-carrossel-substitui>
                    <h3><?= te('evento.galeria.titulo') ?></h3>
                    <div class="evento-galeria-grid">
                        <?php foreach ($fotosGaleriaEvento as $gIdx => $g): ?>
                            <a class="evento-galeria-item" href="#" onclick="lbAbrir(todasImagensEvento, <?= $coverOffsetEvento + $gIdx ?>); return false;">
                                <img src="<?= htmlspecialchars(imagemGaleriaUrl($g['ficheiro'])) ?>" alt="<?= htmlspecialchars($evento['titulo']) ?>" loading="lazy">
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>

            <div class="evento-share-box">
                <div class="evento-share-head">
                    <strong><?= te('evento.partilha.titulo') ?></strong>
                    <span><?= te('evento.partilha.texto') ?></span>
                </div>
                <div class="evento-share-buttons">
                    <a class="share-btn share-facebook" href="<?= htmlspecialchars($facebookShareEvento) ?>" target="_blank" rel="noopener">
                        <i class="bi bi-facebook"></i> Facebook
                    </a>
                    <a class="share-btn share-whatsapp" href="<?= htmlspecialchars($whatsappShareEvento) ?>" target="_blank" rel="noopener">
                        <i class="bi bi-whatsapp"></i> WhatsApp
                    </a>
                    <button type="button" class="share-btn share-copy" onclick="copiarLinkEvento()">
                        <i class="bi bi-link-45deg"></i> <?= te('evento.partilha.copiar') ?>
                    </button>
                    <a class="share-btn share-email" href="<?= htmlspecialchars($emailShareEvento) ?>">
                        <i class="bi bi-envelope-fill"></i> Email
                    </a>
                </div>
                <div class="share-toast" id="shareToastEvento"><?= te('evento.partilha.copiado') ?></div>
            </div>

            <div class="evento-detalhe-actions">
                <a class="hero-btn" href="/eventos.php"><i class="bi bi-arrow-left"></i> <?= te('evento.navegacao.voltar') ?></a>
            </div>

            <?php if (!empty($outrosEventos)): ?>
                <div class="evento-outros">
                    <h3><?= te('evento.sidebar.titulo') ?></h3>
                    <?php foreach ($outrosEventos as $ev): ?>
                        <a class="evento-outros-item" href="/evento.php?id=<?= (int)$ev['id'] ?>">
                            <strong><?= htmlspecialchars($ev['titulo']) ?></strong>
                            <small><?= date('d/m/Y H:i', strtotime($ev['data_evento'])) ?></small>
                        </a>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

        </div>
    </div>
</section>

<script>
var todasImagensEvento = <?= json_encode($todasImagensEvento, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?>;

function copiarLinkEvento() {
    var link = <?= json_encode($urlAtualEvento, JSON_UNESCAPED_UNICODE) ?>;
    var toast = document.getElementById('shareToastEvento');
    if (navigator.clipboard) {
        navigator.clipboard.writeText(link).then(function() {
            toast.classList.add('show');
            setTimeout(function() { toast.classList.remove('show'); }, 2500);
        });
    }
}
</script>

<?php require_once "includes/footer.php"; ?>
