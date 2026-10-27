<?php
/*
 * Plataforma de sites de Juntas de Freguesia — © 2026 GesGov. Todos os direitos reservados.
 * Licença GG-ATALAIA-005E52FF — uso exclusivo de: Freguesias de Atalaia e Alto Estanqueiro-Jardia, concelho do Montijo.
 * Proibida a cópia, modificação, redistribuição ou venda sem autorização escrita da GesGov.
 */
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
$badgeMensagens = 0;
try { $badgeMensagens = (int)$pdo->query("SELECT COUNT(*) FROM contactos_mensagens WHERE lida = 0")->fetchColumn(); } catch (Throwable $e) {}
$badgeReservas = 0;
try { $badgeReservas = (int)$pdo->query("SELECT COUNT(*) FROM reservas_espacos WHERE estado = 'pendente'")->fetchColumn(); } catch (Throwable $e) {}

// Menu lateral: definido em menu.php; aqui só se desenha
require_once __DIR__ . "/menu.php";
[$menuAdmin] = menuAdminDefinicao(compact('badgePedidos', 'badgeRequerimentos', 'badgeFaturacao', 'badgeMensagens', 'badgeReservas'));
[$menuVisivel, $menuAtual, $menuGrupoAtual] = menuAdminPreparar($menuAdmin, (string)$adminActive);

$adminPapeis = ['admin' => 'Administrador', 'operador' => 'Operador', 'vogal' => 'Vogal',
                'presidente_assembleia' => 'Presidente da Assembleia', 'admin_denuncias' => 'Canal de Denúncias'];
$adminPapel = $adminPapeis[$adminTipo ?? 'admin'] ?? ucfirst((string)($adminTipo ?? 'admin'));
$adminIniciais = (function (string $n): string {
    $p = preg_split('/\s+/', trim($n)) ?: ['A'];
    return mb_strtoupper(mb_substr($p[0], 0, 1) . (count($p) > 1 ? mb_substr(end($p), 0, 1) : ''));
})((string)($adminNome ?? 'Administrador'));

$adminSubtitulo = $adminSubtitulo ?? ($menuAtual['dica'] ?? (isOperador() ? 'Área operacional — acesso limitado às ocorrências' : 'Gestão do site da freguesia'));
$adminVerSite = $adminVerSite ?? ($menuAtual['site'] ?? null);

/* Desenha uma entrada do menu (link) */
$menuLink = function (array $i, bool $topo = false): string {
    $b = (int)($i['badge'] ?? 0);
    return '<a class="adm-item' . ($topo ? ' adm-item--topo' : '') . (!empty($i['ativo']) ? ' is-ativo' : '') . '" href="' . htmlspecialchars($i['href']) . '"'
        . (!empty($i['ativo']) ? ' aria-current="page"' : '')
        . ' data-dica="' . htmlspecialchars($i['dica'] ?? '') . '" aria-description="' . htmlspecialchars($i['dica'] ?? '') . '"'
        . ' data-procura="' . htmlspecialchars(mb_strtolower($i['rotulo'] . ' ' . ($i['dica'] ?? ''))) . '">'
        . '<i class="bi ' . htmlspecialchars($i['icone']) . '" aria-hidden="true"></i>'
        . '<span class="adm-item__rotulo">' . htmlspecialchars($i['rotulo']) . '</span>'
        . ($b > 0 ? '<span class="adm-badge" aria-label="' . $b . ' por tratar">' . $b . '</span>' : '')
        . '</a>';
};
?>
<!DOCTYPE html>
<html lang="pt">

<head>
    <meta charset="UTF-8">
    <meta name="author" content="GesGov">
    <meta name="generator" content="Plataforma Juntas GesGov">
    <meta name="copyright" content="© 2026 GesGov — software licenciado (GG-ATALAIA-005E52FF)">
    <!-- Software © 2026 GesGov — todos os direitos reservados — licença GG-ATALAIA-005E52FF -->
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($adminPageTitle) ?> | Backoffice</title>
    <script>
    // antes de pintar: menu recolhido (desktop) conforme a última escolha — evita o "salto"
    try { if (localStorage.getItem('adm-menu-recolhido') === '1') document.documentElement.classList.add('adm-recolhido'); } catch (e) {}
    </script>
    <link rel="stylesheet" href="/assets/vendor/bootstrap-icons/bootstrap-icons.min.css?v=1.11.3">
    <style>
        :root {
            --cor-principal: <?= htmlspecialchars(siteConfig('cor_principal', '#242A30')) ?>;
            --cor-secundaria: <?= htmlspecialchars(siteConfig('cor_secundaria', '#C8A02E')) ?>;
<?= temaVariaveisCss() ?>            --tema-admin-escuro: <?= htmlspecialchars(temaConfig('admin_escuro', '#11151B')) ?>;
        }
    </style>

    <link rel="preload" href="/assets/fonts/montserrat-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/assets/fonts/montserrat-latin-700-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="assets/fonte.css?v=20261013a">
    <link rel="stylesheet" href="assets/admin.css?v=20261012b">
    <link rel="stylesheet" href="assets/admin-ui.css?v=20261022a">
    <script src="assets/admin-ui.js?v=20261027a" defer></script>
</head>

<body>

<a class="adm-saltar" href="#admConteudo">Saltar para o conteúdo</a>

<div class="adm-barra-movel">
    <button type="button" class="adm-barra-movel__menu" data-adm-abrir-menu aria-controls="admSidebar" aria-expanded="false" aria-label="Abrir o menu"><i class="bi bi-list" aria-hidden="true"></i></button>
    <strong><?= htmlspecialchars($adminPageTitle) ?></strong>
</div>
<div class="adm-veu" data-adm-fechar-menu></div>

<div class="admin-layout">

    <aside class="admin-sidebar adm-sidebar" id="admSidebar" aria-label="Menu do backoffice">
        <div class="adm-marca">
            <a class="adm-marca__link" href="dashboard.php" title="Ir para o painel">
                <?php if (!empty($adminLogo)): ?>
                    <img class="adm-marca__logo" src="/assets/img/<?= htmlspecialchars($adminLogo) ?>" alt="">
                <?php else: ?>
                    <span class="adm-marca__logo adm-marca__logo--txt"><?= htmlspecialchars(temaConfig('logo_iniciais', 'JF')) ?></span>
                <?php endif; ?>
                <span class="adm-marca__texto">
                    <strong><?= htmlspecialchars(siteConfig('nome_site', 'Backoffice')) ?></strong>
                    <small>Backoffice</small>
                </span>
            </a>
            <button type="button" class="adm-recolher" data-adm-recolher aria-label="Recolher o menu" data-dica="Recolher o menu (fica só com ícones)"><i class="bi bi-chevron-double-left" aria-hidden="true"></i></button>
        </div>

        <div class="adm-procura" role="search">
            <i class="bi bi-search" aria-hidden="true"></i>
            <input type="search" id="admProcura" placeholder="Procurar no menu…" autocomplete="off" aria-label="Procurar no menu" aria-describedby="admProcuraAjuda">
            <kbd aria-hidden="true">/</kbd>
            <span id="admProcuraAjuda" class="adm-sr">Escreva para filtrar as opções do menu. Enter abre a primeira.</span>
        </div>

        <nav class="admin-menu adm-menu" aria-label="Secções do backoffice">
            <?php foreach ($menuVisivel as $e): ?>
                <?php if (!isset($e['itens'])): ?>
                    <?= $menuLink($e, true) ?>
                <?php else: $gid = 'admGrupo-' . $e['grupo']; ?>
                    <div class="adm-grupo<?= $e['aberto'] ? ' is-aberto' : '' ?>" data-grupo="<?= htmlspecialchars($e['grupo']) ?>">
                        <button type="button" class="adm-grupo__botao" aria-expanded="<?= $e['aberto'] ? 'true' : 'false' ?>" aria-controls="<?= $gid ?>"
                                data-dica="<?= htmlspecialchars($e['dica']) ?>" data-rotulo="<?= htmlspecialchars($e['rotulo']) ?>">
                            <span class="adm-grupo__icone" aria-hidden="true"><i class="bi <?= htmlspecialchars($e['icone']) ?>"></i></span>
                            <span class="adm-grupo__rotulo"><?= htmlspecialchars($e['rotulo']) ?></span>
                            <?php if ($e['badge'] > 0): ?><span class="adm-badge adm-badge--grupo" aria-label="<?= (int)$e['badge'] ?> por tratar"><?= (int)$e['badge'] ?></span><?php endif; ?>
                            <i class="bi bi-chevron-down adm-grupo__seta" aria-hidden="true"></i>
                        </button>
                        <div class="adm-grupo__painel" id="<?= $gid ?>">
                            <div class="adm-grupo__itens">
                                <?php foreach ($e['itens'] as $i): ?>
                                    <?php if (isset($i['sub'])): ?>
                                        <span class="adm-sub"><?= htmlspecialchars($i['sub']) ?></span>
                                    <?php else: ?>
                                        <?= $menuLink($i) ?>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endforeach; ?>
            <p class="adm-procura__vazio" hidden>Nada encontrado. Experimente outra palavra (ex.: «notícia», «foto», «contactos»).</p>
        </nav>

        <div class="adm-rodape">
            <a class="adm-ver-site" href="/" target="_blank" rel="noopener" data-dica="Abrir o site público num separador novo">
                <i class="bi bi-box-arrow-up-right" aria-hidden="true"></i><span class="adm-item__rotulo">Ver o site</span>
            </a>
            <div class="adm-utilizador">
                <span class="adm-utilizador__avatar" aria-hidden="true"><?= htmlspecialchars($adminIniciais) ?></span>
                <span class="adm-utilizador__texto">
                    <strong><?= htmlspecialchars($adminNome ?? 'Administrador') ?></strong>
                    <small><?= htmlspecialchars($adminPapel) ?></small>
                </span>
                <a class="adm-sair logout" href="logout.php" aria-label="Terminar sessão" data-dica="Terminar sessão (sair do backoffice)"><i class="bi bi-box-arrow-right" aria-hidden="true"></i></a>
            </div>
        </div>
    </aside>

    <main class="admin-main" id="admConteudo" tabindex="-1">
        <header class="admin-topbar adm-topo">
            <div class="adm-topo__texto">
                <nav class="adm-migalhas" aria-label="Localização">
                    <a href="dashboard.php"><i class="bi bi-house-door" aria-hidden="true"></i><span class="adm-sr">Painel</span></a>
                    <?php if ($menuGrupoAtual): ?><i class="bi bi-chevron-right" aria-hidden="true"></i><span><?= htmlspecialchars($menuGrupoAtual) ?></span><?php endif; ?>
                    <?php if ($menuAtual && ($menuAtual['rotulo'] ?? '') !== $adminPageTitle): ?><i class="bi bi-chevron-right" aria-hidden="true"></i><a href="<?= htmlspecialchars($menuAtual['href']) ?>"><?= htmlspecialchars($menuAtual['rotulo']) ?></a><?php endif; ?>
                </nav>
                <h1><?= htmlspecialchars($adminPageTitle) ?></h1>
                <p><?= htmlspecialchars($adminSubtitulo) ?></p>
            </div>
            <div class="adm-topo__acoes">
                <?php if ($adminVerSite): ?>
                    <a class="adm-botao adm-botao--suave" href="<?= htmlspecialchars($adminVerSite) ?>" target="_blank" rel="noopener"><i class="bi bi-eye" aria-hidden="true"></i> Ver no site</a>
                <?php endif; ?>
            </div>
        </header>

<?php /* Aviso de uma só vez, deixado por uma página antes de redirecionar (ex.: coordenadas obtidas da morada) */
if (!empty($_SESSION['aviso_admin'])): ?>
    <div class="admin-aviso adm-aviso adm-aviso--ok" role="status">
        <i class="bi bi-info-circle" aria-hidden="true"></i> <?= htmlspecialchars($_SESSION['aviso_admin']) ?>
    </div>
<?php unset($_SESSION['aviso_admin']); endif; ?>
