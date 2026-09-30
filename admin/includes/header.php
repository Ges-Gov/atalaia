<?php
// Output buffering: permite redirects (header Location) depois de output iniciado.
// Incondicional — um buffer sem limite que só esvazia no fim do pedido
// (o buffer padrão de 4KB do PHP esvaziava ao ser excedido pelo header).
ob_start();

require_once __DIR__ . "/auth.php";
require_once __DIR__ . "/../../includes/db.php";
require_once __DIR__ . "/../../includes/config.php";
require_once __DIR__ . "/../../includes/tema.php";

$adminPageTitle = $adminPageTitle ?? "Painel Administrativo";
$adminActive = $adminActive ?? "";

// Brasão/logo da freguesia (mostrado no topo da sidebar)
$adminLogo = '';
try {
    $adminLogo = (string) $pdo->query("SELECT logo FROM configuracoes_site LIMIT 1")->fetchColumn();
} catch (Throwable $e) {
    $adminLogo = '';
}

// Contadores para os "avisos" (badges) da sidebar: pedidos/ocorrências e
// requerimentos do Balcão Virtual ainda por tratar. Aparecem de imediato
// assim que um cidadão submete algo — não é preciso ir ao dashboard ver.
$badgePedidos = 0;
$badgeRequerimentos = 0;
try {
    $badgePedidos = (int) $pdo->query("SELECT COUNT(*) FROM pedidos_junta WHERE estado = 'pendente'")->fetchColumn();
} catch (Throwable $e) {
    $badgePedidos = 0;
}
try {
    // Pendentes OU já fora do prazo definido no catálogo (mesmo que já
    // estejam "em análise") — o mesmo aviso que assinala pedidos novos
    // passa também a assinalar pedidos que ficaram esquecidos.
    // Também conta os que já foram aceites e faturados mas ficaram "presos"
    // — pagos e por emitir — que de outra forma só apareceriam neste aviso
    // se, por acaso, também estivessem fora do prazo.
    $badgeRequerimentos = (int) $pdo->query("
        SELECT COUNT(*)
        FROM requerimentos r
        LEFT JOIN balcao_documentos d ON d.id = r.documento_balcao_id
        WHERE r.estado NOT IN ('deferido', 'indeferido', 'concluido')
        AND (
            r.estado = 'pendente'
            OR (d.prazo_dias IS NOT NULL AND r.criado_em < DATE_SUB(NOW(), INTERVAL d.prazo_dias DAY))
            OR (
                r.documento_faturacao_id IS NOT NULL
                AND r.assinado_em IS NULL
                AND EXISTS (SELECT 1 FROM pagamentos pg WHERE pg.documento_faturacao_id = r.documento_faturacao_id AND pg.estado = 'pago')
            )
        )
    ")->fetchColumn();
} catch (Throwable $e) {
    $badgeRequerimentos = 0;
}
$badgeFaturacao = 0;
try {
    $badgeFaturacao = (int) $pdo->query("
        SELECT COUNT(*) FROM pagamentos WHERE estado = 'pendente'
    ")->fetchColumn();
} catch (Throwable $e) {
    $badgeFaturacao = 0;
}
?>

<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($adminPageTitle) ?> | Admin</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <style>
        :root {
            --cor-principal: <?= htmlspecialchars(siteConfig('cor_principal', '#242A30')) ?>;
            --cor-secundaria: <?= htmlspecialchars(siteConfig('cor_secundaria', '#D4AA00')) ?>;
<?= temaVariaveisCss() ?>            --tema-admin-escuro: <?= htmlspecialchars(temaConfig('admin_escuro', '#11151B')) ?>;
        }
    </style>

    <link rel="stylesheet" href="assets/admin.css?v=20260918a">
</head>

<body>

<button class="admin-mobile-btn" onclick="document.body.classList.toggle('admin-menu-open')"><i class="bi bi-list"></i></button>
<div class="admin-mobile-bg" onclick="document.body.classList.remove('admin-menu-open')"></div>

<div class="admin-layout">

    <aside class="admin-sidebar">
        <div class="admin-logo">
            <?php if (!empty($adminLogo)): ?>
                <img class="admin-logo-img" src="/assets/img/<?= htmlspecialchars($adminLogo) ?>" alt="Brasão da freguesia">
            <?php else: ?>
                <span><?= htmlspecialchars(temaConfig('logo_iniciais', '')) ?></span>
            <?php endif; ?>
            <div>
                <strong><?= htmlspecialchars(siteConfig('nome_site', 'Backoffice')) ?></strong>
                <small>Backoffice</small>
            </div>
        </div>

        <nav class="admin-menu">
            <?php if (isOperador()): ?>
                <a class="<?= $adminActive == 'pedidos' ? 'active' : '' ?>" href="pedidos.php">Ocorrências</a>
            <?php elseif (isVogal()): ?>
                <a class="<?= $adminActive == 'area_vogais' ? 'active' : '' ?>" href="../area-vogais.php"><i class="bi bi-check2-square"></i> Área dos Vogais</a>
            <?php elseif (isPresidenteAssembleia()): ?>
                <a class="<?= $adminActive == 'assembleia_composicao' ? 'active' : '' ?>" href="assembleia-composicao.php"><i class="bi bi-people"></i> Composição Assembleia</a>
                <a class="<?= $adminActive == 'assembleia_funcionamento' ? 'active' : '' ?>" href="assembleia-funcionamento.php"><i class="bi bi-gear"></i> Funcionamento Assembleia</a>
                <a class="<?= $adminActive == 'assembleia_competencias' ? 'active' : '' ?>" href="assembleia-competencias.php"><i class="bi bi-card-checklist"></i> Competências Assembleia</a>
                <a class="<?= $adminActive == 'assembleia_documentos' ? 'active' : '' ?>" href="assembleia-documentos.php"><i class="bi bi-folder2-open"></i> Docs Assembleia</a>
                <a class="<?= $adminActive == 'assembleia_sessoes' ? 'active' : '' ?>" href="assembleia-sessoes.php"><i class="bi bi-bank"></i> Sessões Assembleia</a>
                <a class="<?= $adminActive == 'assembleia_votacoes' ? 'active' : '' ?>" href="assembleia-votacoes.php"><i class="bi bi-ui-checks"></i> Votações Assembleia</a>

            <?php elseif (isAdminDenuncias()): ?>
                <a class="<?= $adminActive == 'denuncias' ? 'active' : '' ?>" href="denuncias.php"><i class="bi bi-exclamation-triangle"></i> Canal de Denúncias</a>

<?php else: ?>

    <?php
    // Grupos colapsáveis na mesma ordem do site público.
    // O grupo da página atual abre automaticamente.
    $gFreguesia  = ['executivo','heraldica','noticias','eventos','pontos','documentos','associacoes','comercio','faqs'];
    $gHome       = ['slides','homepage','galeria-albuns'];
    $gVirtual    = ['pedidos','ocorrencias_config','marcacoes','requerimentos','balcao_documentos','faturacao','modelos_requerimentos','recursos_humanos','procedimentos_concursais','contratacao_publica','denuncias','notificacoes','alertas'];
    $gAssembleia = ['assembleia_composicao','assembleia_funcionamento','assembleia_competencias','assembleia_documentos','assembleia_sessoes'];
    $gNewsletter = ['newsletter','newsletter_subscritores'];
    $gSistema    = ['utilizadores','configuracoes','separadores-fundo','perfis','estatisticas'];
    $abrir = function($keys) use ($adminActive) { return in_array($adminActive, $keys, true) ? 'open' : ''; };
    ?>

    <a class="<?= $adminActive == 'dashboard' ? 'active' : '' ?>" href="dashboard.php"><i class="bi bi-house-door"></i> Dashboard</a>

    <?php if (podeAceder('home')): ?>
    <div class="admin-group <?= $abrir($gHome) ?>">
        <button type="button" class="admin-group-toggle"><span><i class="bi bi-window-stack"></i> Página Inicial</span><span class="chev">▾</span></button>
        <div class="admin-group-items">
            <a class="<?= $adminActive == 'slides' ? 'active' : '' ?>" href="slides.php"><i class="bi bi-film"></i> Slides</a>
            <a href="homepage_destaque.php"><i class="bi bi-camera-reels"></i> Destaque Homepage</a>
            <a class="<?= $adminActive == 'galeria-albuns' ? 'active' : '' ?>" href="galeria-albuns.php"><i class="bi bi-images"></i> Galeria de Fotos</a>
            <a class="<?= $adminActive == 'homepage' ? 'active' : '' ?>" href="homepage.php"><i class="bi bi-pencil-square"></i> Textos Homepage</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (podeAceder('freguesia')): ?>
    <div class="admin-group <?= $abrir($gFreguesia) ?>">
        <button type="button" class="admin-group-toggle"><span><i class="bi bi-bank"></i> A Freguesia</span><span class="chev">▾</span></button>
        <div class="admin-group-items">
            <a class="<?= $adminActive == 'executivo' ? 'active' : '' ?>" href="executivo.php"><i class="bi bi-people"></i> Executivo</a>
            <a href="freguesia.php"><i class="bi bi-bank"></i> Página Freguesia</a>
            <a href="historia.php"><i class="bi bi-journal-text"></i> História e Heráldica</a>
            <a class="<?= $adminActive == 'heraldica' ? 'active' : '' ?>" href="heraldica.php"><i class="bi bi-shield"></i> Heráldica</a>
            <a class="<?= $adminActive == 'noticias' ? 'active' : '' ?>" href="noticias.php"><i class="bi bi-newspaper"></i> Notícias</a>
            <a class="<?= $adminActive == 'pontos' ? 'active' : '' ?>" href="pontos.php"><i class="bi bi-geo-alt"></i> Pontos de Interesse</a>
            <a class="<?= $adminActive == 'associacoes' ? 'active' : '' ?>" href="associacoes.php"><i class="bi bi-people-fill"></i> Associações</a>
            <a class="<?= $adminActive == 'comercio' ? 'active' : '' ?>" href="comercio.php"><i class="bi bi-shop"></i> Economia Local</a>
            <a class="<?= $adminActive == 'eventos' ? 'active' : '' ?>" href="eventos.php"><i class="bi bi-calendar-event"></i> Eventos</a>
            <a class="<?= $adminActive == 'documentos' ? 'active' : '' ?>" href="documentos.php"><i class="bi bi-collection"></i> Documentos Públicos</a>
            <a class="<?= $adminActive == 'faqs' ? 'active' : '' ?>" href="faqs.php"><i class="bi bi-question-circle"></i> FAQ's</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (podeAceder('virtual')): ?>
    <div class="admin-group <?= $abrir($gVirtual) ?>">
        <button type="button" class="admin-group-toggle"><span><i class="bi bi-laptop"></i> Junta Virtual</span><span class="chev">▾</span></button>
        <div class="admin-group-items">
            <a class="<?= $adminActive == 'pedidos' ? 'active' : '' ?>" href="pedidos.php" style="display:flex;align-items:center;justify-content:space-between;">
                <span><i class="bi bi-megaphone"></i> Ocorrências</span>
                <?php if ($badgePedidos > 0): ?><span class="menu-badge"><?= $badgePedidos ?></span><?php endif; ?>
            </a>
            <a class="<?= $adminActive == 'marcacoes' ? 'active' : '' ?>" href="marcacoes.php"><i class="bi bi-calendar-check"></i> Marcações</a>
            <a class="<?= $adminActive == 'requerimentos' ? 'active' : '' ?>" href="requerimentos.php" style="display:flex;align-items:center;justify-content:space-between;">
                <span><i class="bi bi-file-earmark-text"></i> Requerimentos (Balcão Virtual)</span>
                <?php if ($badgeRequerimentos > 0): ?><span class="menu-badge"><?= $badgeRequerimentos ?></span><?php endif; ?>
            </a>
            <a class="<?= $adminActive == 'balcao_documentos' ? 'active' : '' ?>" href="balcao-documentos.php"><i class="bi bi-journal-check"></i> Documentos do Balcão</a>
            <a class="<?= $adminActive == 'faturacao' ? 'active' : '' ?>" href="faturacao.php" style="display:flex;align-items:center;justify-content:space-between;">
                <span><i class="bi bi-receipt"></i> Faturação</span>
                <?php if ($badgeFaturacao > 0): ?><span class="menu-badge"><?= $badgeFaturacao ?></span><?php endif; ?>
            </a>
            <a class="<?= $adminActive == 'modelos_requerimentos' ? 'active' : '' ?>" href="modelos-requerimentos.php"><i class="bi bi-folder2-open"></i> Modelos de Requerimentos</a>
            <a class="<?= $adminActive == 'recursos_humanos' ? 'active' : '' ?>" href="recursos-humanos.php"><i class="bi bi-people"></i> Recursos Humanos</a>
            <a class="<?= $adminActive == 'procedimentos_concursais' ? 'active' : '' ?>" href="procedimentos-concursais.php"><i class="bi bi-megaphone"></i> Procedimentos Concursais</a>
            <a class="<?= $adminActive == 'contratacao_publica' ? 'active' : '' ?>" href="contratacao-publica.php"><i class="bi bi-briefcase"></i> Contratação Pública</a>
            <?php /* Canal de Denúncias agora exclusivo do tipo "Administrador Canal de Denúncias" */ ?>
            <a class="<?= $adminActive == 'notificacoes' ? 'active' : '' ?>" href="notificacoes.php"><i class="bi bi-bell"></i> Notificações</a>
            <a class="<?= $adminActive == 'alertas' ? 'active' : '' ?>" href="alertas.php"><i class="bi bi-exclamation-octagon"></i> Alertas</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (podeAceder('assembleia')): ?>
    <div class="admin-group <?= $abrir($gAssembleia) ?>">
        <button type="button" class="admin-group-toggle"><span><i class="bi bi-bank2"></i> Assembleia de Freguesia</span><span class="chev">▾</span></button>
        <div class="admin-group-items">
            <a class="<?= $adminActive == 'assembleia_composicao' ? 'active' : '' ?>" href="assembleia-composicao.php"><i class="bi bi-people"></i> Composição</a>
            <a class="<?= $adminActive == 'assembleia_funcionamento' ? 'active' : '' ?>" href="assembleia-funcionamento.php"><i class="bi bi-gear"></i> Funcionamento</a>
            <a class="<?= $adminActive == 'assembleia_competencias' ? 'active' : '' ?>" href="assembleia-competencias.php"><i class="bi bi-card-checklist"></i> Atribuições e Competências</a>
            <a class="<?= $adminActive == 'assembleia_documentos' ? 'active' : '' ?>" href="assembleia-documentos.php"><i class="bi bi-folder2-open"></i> Documentos</a>
            <a class="<?= $adminActive == 'assembleia_sessoes' ? 'active' : '' ?>" href="assembleia-sessoes.php"><i class="bi bi-bank"></i> Sessões da Assembleia</a>
        </div>
    </div>
    <?php endif; ?>

    <a class="<?= $adminActive == 'contactos' ? 'active' : '' ?>" href="contactos.php"><i class="bi bi-telephone"></i> Contactos</a>

    <div class="admin-group <?= $abrir($gNewsletter) ?>">
        <button type="button" class="admin-group-toggle"><span><i class="bi bi-envelope-paper"></i> Newsletter</span><span class="chev">▾</span></button>
        <div class="admin-group-items">
            <a class="<?= $adminActive == 'newsletter' ? 'active' : '' ?>" href="newsletter.php"><i class="bi bi-envelope-paper"></i> Newsletter</a>
            <a class="<?= $adminActive == 'newsletter_subscritores' ? 'active' : '' ?>" href="newsletter-subscritores.php"><i class="bi bi-people"></i> Subscritores da Newsletter</a>
        </div>
    </div>

    <?php if (podeAceder('sistema')): ?>
    <div class="admin-group <?= $abrir($gSistema) ?>">
        <button type="button" class="admin-group-toggle"><span><i class="bi bi-gear-wide-connected"></i> Sistema</span><span class="chev">▾</span></button>
        <div class="admin-group-items">
            <a class="<?= $adminActive == 'utilizadores' ? 'active' : '' ?>" href="utilizadores.php"><i class="bi bi-person-badge"></i> Utilizadores</a>
            <a class="<?= $adminActive == 'configuracoes' ? 'active' : '' ?>" href="configuracoes.php"><i class="bi bi-gear"></i> Configurações</a>
            <?php if (isGesGov()): /* Perfis: reservado à GesGov (ver migrations/007_perfis.sql) */ ?>
                <a class="<?= $adminActive == 'perfis' ? 'active' : '' ?>" href="perfis.php"><i class="bi bi-shield-lock"></i> Perfis</a>
            <?php endif; ?>
            <a class="<?= $adminActive == 'separadores-fundo' ? 'active' : '' ?>" href="separadores-fundo.php"><i class="bi bi-image"></i> Fundos dos Separadores</a>
            <a class="<?= $adminActive == 'estatisticas' ? 'active' : '' ?>" href="estatisticas.php"><i class="bi bi-bar-chart"></i> Estatísticas</a>
        </div>
    </div>
    <?php endif; ?>

<?php endif; ?>

        </nav>

        <a class="logout" href="logout.php">Sair</a>
    </aside>

    <main class="admin-main">
        <header class="admin-topbar">
            <div>
                <h1><?= htmlspecialchars($adminPageTitle) ?></h1>
                <p>
                    <?php if (isOperador()): ?>
                        Área operacional — acesso limitado às ocorrências
                    <?php else: ?>
                        Gestão dinâmica do site da freguesia
                    <?php endif; ?>
                </p>
            </div>

            <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;">
                <span style="background:#eef2ff;color:#242A30;padding:9px 13px;border-radius:999px;font-weight:900;">
                    <?= htmlspecialchars($adminNome ?? 'Administrador') ?>
                </span>

                <span style="background:#fef3c7;color:#92400e;padding:9px 13px;border-radius:999px;font-weight:900;text-transform:uppercase;">
                    <?= htmlspecialchars($adminTipo ?? 'admin') ?>
                </span>
            </div>
        </header>

<script>
document.querySelectorAll('.admin-group-toggle').forEach(function (btn) {
    btn.addEventListener('click', function () {
        btn.closest('.admin-group').classList.toggle('open');
    });
});
</script>
