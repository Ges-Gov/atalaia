<?php
// Output buffering: permite que header("Location: ...") funcione mesmo depois
// de a página começar a ser impressa (evita "headers already sent").
// Incondicional — o buffer padrão de 4KB esvaziava ao ser excedido.
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/config.php";
require_once __DIR__ . "/tema.php";

$paginaAtual = basename($_SERVER['PHP_SELF']);
$notificacoesHeader = 0;

if (!empty($_SESSION['cidadao_id'])) {
    try {
        $stmtHeaderNotif = $pdo->prepare("
            SELECT COUNT(*)
            FROM notificacoes n
            LEFT JOIN notificacoes_lidas nl
                ON nl.notificacao_id = n.id
                AND nl.cidadao_id = ?
            WHERE n.ativo = 1
            AND nl.id IS NULL
        ");
        $stmtHeaderNotif->execute([$_SESSION['cidadao_id']]);
        $notificacoesHeader = (int)$stmtHeaderNotif->fetchColumn();
    } catch (Exception $e) {
        $notificacoesHeader = 0;
    }
}
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <?php
        // Título e descrição para SEO: usa os da página (se definidos), senão os do site.
        $tituloPagina = !empty($meta_titulo)
            ? $meta_titulo . ' | ' . siteConfig('nome_site', 'Junta de Freguesia')
            : siteConfig('nome_site', 'Junta de Freguesia');
        $descricaoPagina = !empty($meta_descricao)
            ? $meta_descricao
            : siteConfig('nome_site', 'Junta de Freguesia') . ' — notícias, eventos, documentos e serviços ao munícipe da freguesia.';
    ?>
    <title><?= htmlspecialchars($tituloPagina) ?></title>
    <meta name="description" content="<?= htmlspecialchars($descricaoPagina) ?>">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <?php if (!empty($heroPreloadImage)): ?>
    <link rel="preload" as="image" href="/assets/img/<?= htmlspecialchars($heroPreloadImage) ?>">
    <?php endif; ?>

    <style>
        :root {
            --cor-principal: <?= htmlspecialchars(siteConfig('cor_principal', '#242A30')) ?> !important;
            --cor-secundaria: <?= htmlspecialchars(siteConfig('cor_secundaria', '#D4AA00')) ?> !important;
<?= temaVariaveisCss() ?>
        }
    </style>

    <link rel="stylesheet" href="/assets/css/style.css?v=20260918c">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<?php $faviconFich = temaConfig('favicon', '') !== '' ? temaConfig('favicon') : siteConfig('logo', ''); ?>
<?php if ($faviconFich !== ''): ?>
    <link rel="icon" type="image/png" sizes="180x180" href="/assets/img/<?= htmlspecialchars($faviconFich) ?>">
    <link rel="apple-touch-icon" href="/assets/img/<?= htmlspecialchars($faviconFich) ?>">
<?php endif; ?>


<?php $sepBgImg = function_exists('fundoSeparadorImg') ? fundoSeparadorImg($paginaAtual ?? '') : ''; ?>
<?php if ($sepBgImg !== ''): ?>
    <style>
        /* Imagem de fundo do separador (definida no backoffice), com véu escuro para o texto ficar legível */
        section[class*="hero"]{
            background-image: linear-gradient(rgba(17,21,27,.72), rgba(17,21,27,.82)), url('<?= htmlspecialchars($sepBgImg) ?>') !important;
            background-size: cover !important;
            background-position: center !important;
            background-repeat: no-repeat !important;
        }
    </style>
<?php endif; ?>

<?php if (!empty($meta_titulo)): ?>
    <meta property="og:title" content="<?= htmlspecialchars($meta_titulo) ?>">
    <meta property="og:description" content="<?= htmlspecialchars($meta_descricao ?? '') ?>">
    <meta property="og:image" content="<?= htmlspecialchars($meta_imagem ?? '') ?>">
    <meta property="og:url" content="<?= htmlspecialchars($meta_url ?? '') ?>">
    <meta property="og:type" content="article">

    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= htmlspecialchars($meta_titulo) ?>">
    <meta name="twitter:description" content="<?= htmlspecialchars($meta_descricao ?? '') ?>">
    <meta name="twitter:image" content="<?= htmlspecialchars($meta_imagem ?? '') ?>">
<?php endif; ?>

</head>

<body<?= $sepBgImg !== '' ? ' class="tem-fundo-separador"' : '' ?>>

<style>
.skip-link{position:absolute;left:-9999px;top:0;z-index:10000;background:var(--cor-principal,#242A30);color:#fff;padding:12px 18px;border-radius:0 0 10px 0;font-weight:900;text-decoration:none}
.skip-link:focus{left:0}
</style>
<a class="skip-link" href="#conteudo-principal">Saltar para o conteúdo principal</a>

<header class="site-header">

    <div class="top-bar">
        <div class="container top-bar-inner">
            <span><?= htmlspecialchars(siteConfig('email', '')) ?></span>
            <span><?= htmlspecialchars(siteConfig('telefone', '')) ?></span>
        </div>
    </div>

    <div class="main-header">
        <div class="container header-inner">

            <a href="/index.php" class="brand">
                <?php if (!empty($config['logo'])): ?>
                    


<img 
    src="/assets/img/<?= htmlspecialchars($config['logo']) ?>" 
    alt="<?= htmlspecialchars(siteConfig('nome_site')) ?>"
    style="height:78px;width:auto;object-fit:contain;"
>


                <?php else: ?>
                    <div class="brand-icon"><?= htmlspecialchars(temaConfig('logo_iniciais', '')) ?></div>
                <?php endif; ?>

                <div>
                    <strong><?= htmlspecialchars(siteConfig('nome_site', 'Junta de Freguesia')) ?></strong>
                    <small><?= htmlspecialchars(siteConfig('municipio', 'Município')) ?></small>
                </div>
            </a>

            <nav class="main-nav" id="mainNav">
                <a href="/index.php">Início</a>

                <div class="nav-dropdown">
                    <button class="nav-parent" type="button" aria-haspopup="true" aria-expanded="false">
                        A Freguesia <span>▾</span>
                    </button>

                    <nav class="submenu" aria-label="A Freguesia">
                        <a href="/executivo.php">Executivo</a>
                        <a href="/freguesia.php">Visão Geral</a>
                        <a href="/historia.php">História e Heráldica</a>
                   <!--<a href="/heraldica.php">Heráldica</a>-->
                        <a href="/noticias.php">Notícias</a>
                       
                        <a href="/pontos.php">Pontos de Interesse</a>
                        <a href="/associacoes.php">Associações</a>
                        <a href="/comercio.php">Economia Local</a>
                        <a href="/eventos.php">Eventos</a>
                        <a href="/galeria.php">Galeria de Imagens</a>
                        <a href="/freguesia-documentos.php">Documentos</a>
                        <a href="/mapa.php">Mapa</a>
                    </nav>
                </div>

                <div class="nav-dropdown">
                    <button class="nav-parent" type="button" aria-haspopup="true" aria-expanded="false">
                        Junta Virtual <span>▾</span>
                    </button>

                    <nav class="submenu" aria-label="Junta Virtual">
                        <a href="/pedidos.php">Ocorrências</a>
                         
                               





<div class="dropdown-submenu">
    <a href="/recursos-humanos.php" class="submenu-main">
        Recursos Humanos ▸
    </a>



<a href="/livro-reclamacoes.php">Livro de Reclamações</a>

    <div class="dropdown-submenu-content">
        <a href="/recursos-humanos.php?categoria=mapa_pessoal">Mapa de Pessoal</a>
<a href="/procedimentos-concursais.php">Procedimentos Concursais</a>
    </div>
</div>


<a href="/contratacao-publica.php">Contratação Pública</a>

<a href="/canal-denuncias.php">Canal de Denúncias</a>

<a href="/protecao-dados.php">Proteção de Dados</a>


                         <a href="/transparencia.php">Transparência</a>
                        <!-- Balcão Virtual temporariamente oculto (ainda não disponível ao público): <a href="/minha-area.php">Balcão Virtual</a> -->
                        <!--<a href="/ocorrencias-mapa.php">Mapa de Ocorrências</a>-->
                       
                        <!--<a href="/marcacoes.php">Marcação de Atendimento</a>
                        <a href="/requerimentos.php">Requerimentos Online</a>
                        <a href="/atestado-residencia.php">Atestado Automático</a>
                        <a href="/documentos-automaticos.php">Documentos Automáticos</a>
                        <a href="/notificacoes.php">Notificações</a>-->
                    </nav>
                </div>

                <div class="nav-dropdown">
                    <button class="nav-parent" type="button" aria-haspopup="true" aria-expanded="false">
                        Assembleia de Freguesia <span>▾</span>
                    </button>

                    <nav class="submenu" aria-label="Assembleia de Freguesia">
                        <a href="/assembleia-composicao.php">Composição</a>
                        <a href="/assembleia-funcionamento.php">Funcionamento</a>
                        <a href="/assembleia-competencias.php">Atribuições e Competências</a>
                        <a href="/assembleia-documentos.php">Documentos</a>
                        <a href="/assembleia-sessoes.php">Sessões da Assembleia</a>
                        <a href="/admin/login.php?redirect=/area-vogais.php">Área dos Vogais</a>
                    </nav>
                </div>

                <a href="/contactos.php">Contactos</a>
            </nav>

            <?php if (!empty($_SESSION['cidadao_id'])): ?>
                <div class="notif-header" id="notifHeader">
                    <button type="button" class="notif-btn" id="notifBtn" aria-label="Notificações">
                        <i class="bi bi-bell-fill"></i>
                        <span id="notifCount" <?= $notificacoesHeader <= 0 ? 'style="display:none;"' : '' ?>>
                            <?= (int)$notificacoesHeader ?>
                        </span>
                    </button>

                    <div class="notif-dropdown" id="notifDropdown">
                        <div class="notif-dropdown-top">
                            <strong>Notificações</strong>
                            <a href="/notificacoes.php">Ver todas</a>
                        </div>

                        <div id="notifListaHeader">
                            <p class="notif-empty">A carregar...</p>
                        </div>
                    </div>
                </div>
            <?php endif; ?>

            <button class="menu-toggle" id="menuToggle" aria-label="Abrir menu"><i class="bi bi-list"></i></button>

        </div>
    </div>

    <div class="global-search-strip">
        <div class="container">

            <div class="global-search-wrapper global-search-wide">

                <label for="globalSearchInput" class="global-search-label">Pesquisar no site</label>

                <div class="global-search-box">
                    <span class="global-search-icon"><i class="bi bi-search"></i></span>

                    <input
                        type="text"
                        id="globalSearchInput"
                        placeholder="Pesquisar notícias, documentos, eventos, serviços..."
                        autocomplete="off">
                </div>

                <div id="globalSearchResults" class="global-search-results"></div>

            </div>

        </div>
    </div>

</header>

<nav class="bottom-app-nav">
    <a href="/index.php" class="<?= $paginaAtual == 'index.php' ? 'active' : '' ?>">
        <span><i class="bi bi-house-fill"></i></span>
        <small>Início</small>
    </a>

    <a href="/noticias.php" class="<?= $paginaAtual == 'noticias.php' ? 'active' : '' ?>">
        <span><i class="bi bi-newspaper"></i></span>
        <small>Notícias</small>
    </a>

    <a href="/eventos.php" class="<?= $paginaAtual == 'eventos.php' ? 'active' : '' ?>">
        <span><i class="bi bi-calendar-event-fill"></i></span>
        <small>Eventos</small>
    </a>

    <a href="/mapa.php" class="<?= $paginaAtual == 'mapa.php' ? 'active' : '' ?>">
        <span><i class="bi bi-map-fill"></i></span>
        <small>Mapa</small>
    </a>

    <?php /* Balcão Virtual (Área) temporariamente oculto — descomentar quando estiver pronto
    <a href="/minha-area.php" class="<?= $paginaAtual == 'minha-area.php' ? 'active' : '' ?>">
        <span class="bottom-icon-wrap">
            <i class="bi bi-person-fill"></i>
            <?php if ($notificacoesHeader > 0): ?>
                <b><?= $notificacoesHeader ?></b>
            <?php endif; ?>
        </span>
        <small>Área</small>
    </a>
    */ ?>
</nav>

<main id="conteudo-principal" tabindex="-1">

<script>
document.addEventListener("DOMContentLoaded", function () {
    const globalSearchInput = document.getElementById("globalSearchInput");
    const globalSearchResults = document.getElementById("globalSearchResults");

    if (globalSearchInput && globalSearchResults) {
        globalSearchInput.addEventListener("input", async function () {
            const q = this.value.trim();

            if (q.length < 2) {
                globalSearchResults.innerHTML = "";
                globalSearchResults.style.display = "none";
                return;
            }

            try {
                const response = await fetch("/ajax/pesquisa_global.php?q=" + encodeURIComponent(q));
                const html = await response.text();

                globalSearchResults.innerHTML = html;
                globalSearchResults.style.display = "block";
            } catch (e) {
                globalSearchResults.innerHTML = '<div class="search-empty">Erro ao pesquisar.</div>';
                globalSearchResults.style.display = "block";
            }
        });

        document.addEventListener("click", function (e) {
            if (!e.target.closest(".global-search-wrapper")) {
                globalSearchResults.style.display = "none";
            }
        });

        globalSearchInput.addEventListener("focus", function () {
            if (globalSearchResults.innerHTML.trim() !== "") {
                globalSearchResults.style.display = "block";
            }
        });
    }

    const notifBtn = document.getElementById("notifBtn");
    const notifDropdown = document.getElementById("notifDropdown");
    const notifCount = document.getElementById("notifCount");
    const notifListaHeader = document.getElementById("notifListaHeader");

    if (notifBtn && notifDropdown) {
        notifBtn.addEventListener("click", function (e) {
            e.stopPropagation();
            notifDropdown.classList.toggle("open");
            carregarNotificacoesHeader();
        });

        document.addEventListener("click", function () {
            notifDropdown.classList.remove("open");
        });

        notifDropdown.addEventListener("click", function (e) {
            e.stopPropagation();
        });

        function atualizarContadorHeader() {
            fetch("/ajax/notificacoes_count.php")
                .then(res => res.text())
                .then(total => {
                    total = parseInt(total) || 0;

                    if (notifCount) {
                        notifCount.innerText = total;

                        if (total > 0) {
                            notifCount.style.display = "grid";
                        } else {
                            notifCount.style.display = "none";
                        }
                    }
                })
                .catch(() => {});
        }

        function carregarNotificacoesHeader() {
            fetch("/ajax/notificacoes_header.php")
                .then(res => res.text())
                .then(html => {
                    if (notifListaHeader) {
                        notifListaHeader.innerHTML = html;
                    }
                })
                .catch(() => {
                    if (notifListaHeader) {
                        notifListaHeader.innerHTML = '<p class="notif-empty">Erro ao carregar notificações.</p>';
                    }
                });
        }

        atualizarContadorHeader();
        setInterval(atualizarContadorHeader, 10000);
    }
});
</script>

<?php require_once __DIR__ . "/alertas.php"; ?>