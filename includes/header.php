<?php
/*
 * Plataforma de sites de Juntas de Freguesia — © 2026 GesGov. Todos os direitos reservados.
 * Licença GG-ATALAIA-005E52FF — uso exclusivo de: Freguesias de Atalaia e Alto Estanqueiro-Jardia, concelho do Montijo.
 * Proibida a cópia, modificação, redistribuição ou venda sem autorização escrita da GesGov.
 */
// Output buffering: permite que header("Location: ...") funcione mesmo depois
// de a página começar a ser impressa (evita "headers already sent").
// Incondicional — o buffer padrão de 4KB esvaziava ao ser excedido.
ob_start();

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/config.php";
require_once __DIR__ . "/tema.php";
require_once __DIR__ . "/seo.php";     // dados estruturados schema.org (redesign 10/2026, do modelo novo)
require_once __DIR__ . "/redes.php";   // redes sociais preenchidas no admin (redesign 10/2026)
$redesMenu = redesSociais();

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
    <meta name="author" content="GesGov">
    <meta name="generator" content="Plataforma Juntas GesGov">
    <meta name="copyright" content="© 2026 GesGov — software licenciado (GG-ATALAIA-005E52FF)">
    <!-- Software © 2026 GesGov — todos os direitos reservados — licença GG-ATALAIA-005E52FF -->
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

    <script>
        // Modo claro/escuro (redesign 10/2026): aplicado antes de o CSS pintar, para não haver "flash".
        // O tema CLARO é o principal; o escuro só aparece se o visitante o escolher no botão lua/sol
        // (escolha guardada em localStorage). Não segue o modo do sistema operativo.
        (function () {
            var d = document.documentElement, escolha = null;
            try { escolha = localStorage.getItem('jf-tema'); } catch (e) {}
            function aplicar() {
                d.setAttribute('data-tema', escolha === 'escuro' ? 'escuro' : 'claro');
            }
            aplicar();
            // Brasão: entrada suave só na 1.ª página de cada visita (CSS .brasao-entrada)
            try {
                var semMov = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;
                if (!sessionStorage.getItem('jf-brasao') && !semMov) {
                    d.classList.add('brasao-entrada');
                    sessionStorage.setItem('jf-brasao', '1');
                }
            } catch (e) {}
            window.jfDefinirTema = function (t) {
                escolha = t;
                try { t ? localStorage.setItem('jf-tema', t) : localStorage.removeItem('jf-tema'); } catch (e) {}
                aplicar();
            };
        })();
    </script>

    <style>
        :root {
            --cor-principal: <?= htmlspecialchars(siteConfig('cor_principal', '#242A30')) ?> !important;
            --cor-secundaria: <?= htmlspecialchars(siteConfig('cor_secundaria', '#C8A02E')) ?> !important;
<?= temaVariaveisCss() ?>
        }
        :root[data-tema="escuro"] {
<?= temaVariaveisEscuroCss() ?>
        }
    </style>

    <link rel="preload" href="/assets/fonts/montserrat-latin-400-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="preload" href="/assets/fonts/montserrat-latin-700-normal.woff2" as="font" type="font/woff2" crossorigin>
    <link rel="stylesheet" href="/assets/css/style.css?v=20261030">
    <link rel="stylesheet" href="/assets/css/tema-escuro.css?v=20261020s">
    <link rel="stylesheet" href="/assets/css/tema-escuro-ajustes.css?v=20261020s">
    <link rel="stylesheet" href="/assets/css/compat-redesign.css?v=20261020r">
    <link rel="stylesheet" href="/assets/vendor/bootstrap-icons/bootstrap-icons.min.css?v=1.11.3"><?php /* ícones servidos pelo próprio site (antes da CDN jsdelivr) */ ?>

<?php $faviconFich = temaConfig('favicon', '') !== '' ? temaConfig('favicon') : siteConfig('logo', ''); ?>
<?php if ($faviconFich !== ''): ?>
    <link rel="icon" type="image/png" sizes="180x180" href="/assets/img/<?= htmlspecialchars($faviconFich) ?>">
    <link rel="apple-touch-icon" href="/assets/img/<?= htmlspecialchars($faviconFich) ?>">
<?php endif; ?>


<?php
// Uma página pode trazer o seu próprio fundo de separador (ex.: pagina.php, por página de conteúdo);
// senão vale o definido no backoffice para esta página (separadores_fundo).
$sepBgImg = !empty($fundoSeparadorPagina)
    ? $fundoSeparadorPagina
    : (function_exists('fundoSeparadorImg') ? fundoSeparadorImg($paginaAtual ?? '') : '');
?>
<?php if ($sepBgImg !== ''): ?>
    <?php
    // Proporção da imagem (largura/altura) para o banner ter a forma da foto: assim vê-se a
    // imagem quase inteira e só se corta o necessário para encher o ecrã (redesign 10/2026).
    $sepDim = @getimagesize(__DIR__ . '/..' . $sepBgImg);
    $sepRatio = ($sepDim && $sepDim[1] > 0) ? round($sepDim[0] / $sepDim[1], 4) : 1.5;
    // ponto de foco da foto: o modelo novo guarda-o na BD (fundoSeparadorFoco); aqui fica ao centro
    $sepFoco = function_exists('fundoSeparadorFoco') ? fundoSeparadorFoco($paginaAtual ?? '') : [50, 50];
    ?>
    <style>
        /* Imagem de fundo do separador (definida no backoffice), com véu escuro para o texto ficar legível.
           Altura = largura do ecrã ÷ proporção da foto, com limites: até 52% do ecrã e nunca menos do
           que o texto do banner precisa. "cover" corta só o que sobra, a partir do ponto de foco. */
        :root{ --sep-ratio: <?= $sepRatio ?>; }
        section[class*="hero"]{
            background-image: linear-gradient(rgba(17,21,27,var(--tema-separador-veu,.62)), rgba(17,21,27,var(--tema-separador-veu-fim,.78))), url('<?= htmlspecialchars($sepBgImg) ?>') !important;
            background-size: cover !important;
            background-position: <?= implode('% ', $sepFoco) ?>% !important;
            background-repeat: no-repeat !important;
        }
        section[class*="hero"]:not(.hero-slider):not(.home-video-hero){
            min-height: clamp(300px, min(calc(100vw / var(--sep-ratio)), 52vh), 680px) !important;
            padding-top: clamp(56px, 7vh, 86px) !important;
            padding-bottom: clamp(56px, 7vh, 86px) !important;
            box-sizing: border-box;
            display: grid !important;
            align-content: center;
        }
        /* banner que sobe para trás do menu de vidro: o espaço de cima inclui a altura do menu */
        section[class*="hero"].hero-sob-header:not(.hero-slider):not(.home-video-hero){
            padding-top: calc(var(--altura-header, 90px) + clamp(28px, 4vh, 56px)) !important;
            min-height: clamp(340px, min(calc(100vw / var(--sep-ratio)), 52vh + var(--altura-header, 90px) * .5), 720px) !important;
        }
        /* asterisco de campo obrigatório dentro do banner: vermelho claro, legível sobre a foto escurecida */
        section[class*="hero"] .req{ color: #fca5a5 !important; }
        /* círculos decorativos das páginas ficavam "cortados" por cima da fotografia */
        section[class*="hero"]:not(.hero-slider):not(.home-video-hero)::before,
        section[class*="hero"]:not(.hero-slider):not(.home-video-hero)::after{
            display: none !important;
        }
        @media (max-width: 850px){
            section[class*="hero"]:not(.hero-slider):not(.home-video-hero){
                min-height: clamp(260px, min(calc(100vw / var(--sep-ratio) + 120px), 56vh), 520px) !important;
                padding-top: 44px !important;
                padding-bottom: 44px !important;
            }
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

<?php /* SEO: a Junta como organização (schema.org) em todas as páginas — includes/seo.php */ seoOrganizacao(); ?>
</head>

<body<?= $sepBgImg !== '' ? ' class="tem-fundo-separador"' : '' ?>>

<style>
.skip-link{position:absolute;left:-9999px;top:0;z-index:10000;background:var(--cor-principal,#242A30);color:#fff;padding:12px 18px;border-radius:0 0 10px 0;font-weight:900;text-decoration:none}
.skip-link:focus{left:0}
</style>
<a class="skip-link" href="#conteudo-principal">Saltar para o conteúdo principal</a>

<?php require_once __DIR__ . "/alertas.php"; /* avisos à população: no topo, antes do menu (redesign) */ ?>

<header class="site-header">


    <div class="main-header">
        <div class="container header-inner">

            <?php $soLogo = temaConfig('cabecalho_so_logo', '0') === '1' && !empty($config['logo']); ?>
            <a href="/index.php" class="brand<?= $soLogo ? ' brand--so-logo' : '' ?>">
                <?php if (!empty($config['logo'])): ?>
                    


<img 
    src="/assets/img/<?= htmlspecialchars($config['logo']) ?>" 
    alt="<?= htmlspecialchars(siteConfig('nome_site')) ?>"
    style="height:78px;width:auto;object-fit:contain;"
>


                <?php else: ?>
                    <div class="brand-icon"><?= htmlspecialchars(temaConfig('logo_iniciais', '')) ?></div>
                <?php endif; ?>

                <?php /* tema_config.cabecalho_so_logo = 1: o logótipo já tem o nome da Junta escrito — não se repete */ ?>
                <?php if ($soLogo): ?>
                    <span class="sr-only"><?= htmlspecialchars(siteConfig('nome_site', 'Junta de Freguesia')) ?></span>
                <?php else: ?>
                <div>
                    <strong><?= htmlspecialchars(siteConfig('nome_site', 'Junta de Freguesia')) ?></strong>
                    <small><?= htmlspecialchars(siteConfig('municipio', 'Município')) ?></small>
                </div>
                <?php endif; ?>
            </a>

            <nav class="main-nav" id="mainNav">
                <button type="button" class="nav-search-toggle js-spotlight" aria-label="Pesquisar no site (Ctrl+K)"
                        aria-haspopup="dialog" aria-expanded="false" aria-keyshortcuts="Control+K"><i class="bi bi-search" aria-hidden="true"></i></button>
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
                        <a href="/sugestoes.php">Sugestões</a>
                         
                               





<div class="dropdown-submenu">
    <a href="/recursos-humanos.php" class="submenu-main" aria-expanded="false">
        Recursos Humanos ▸
    </a>
    <div class="dropdown-submenu-content">
        <a href="/recursos-humanos.php?categoria=mapa_pessoal">Mapa de Pessoal</a>
<a href="/procedimentos-concursais.php">Procedimentos Concursais</a>
    </div>
</div>

<a href="/livro-reclamacoes.php">Livro de Reclamações</a>


<a href="/contratacao-publica.php">Contratação Pública</a>

<!-- Canal de Denúncias temporariamente oculto do menu Junta Virtual (23/10/2026; a página continua a existir): <a href="/canal-denuncias.php">Canal de Denúncias</a> -->

<a href="/protecao-dados.php">Proteção de Dados</a>


                         <!-- Transparência temporariamente oculto do menu Junta Virtual (23/10/2026; a página continua a existir): <a href="/transparencia.php">Transparência</a> -->
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

                <?php if ($redesMenu): ?>
                    <?php /* No telemóvel as redes aparecem dentro do painel do menu */ ?>
                    <div class="menu-redes menu-redes--painel" aria-label="Redes sociais">
                        <?php foreach ($redesMenu as $r): ?>
                            <a href="<?= htmlspecialchars($r['url']) ?>" target="_blank" rel="noopener" aria-label="<?= $r['nome'] ?> (abre numa nova janela)"><i class="bi <?= $r['icone'] ?>" aria-hidden="true"></i><span><?= $r['nome'] ?></span></a>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </nav>

            <?php if ($redesMenu): ?>
                <ul class="menu-redes menu-redes--barra" aria-label="Redes sociais">
                    <?php foreach ($redesMenu as $r): ?>
                        <li><a href="<?= htmlspecialchars($r['url']) ?>" target="_blank" rel="noopener" aria-label="<?= $r['nome'] ?> (abre numa nova janela)" title="<?= $r['nome'] ?>"><i class="bi <?= $r['icone'] ?>" aria-hidden="true"></i></a></li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>

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

            <button type="button" class="mobile-search-toggle js-spotlight" aria-label="Pesquisar no site"
                    aria-haspopup="dialog" aria-expanded="false"><i class="bi bi-search" aria-hidden="true"></i></button>

            <button type="button" class="tema-toggle" id="temaToggle" aria-pressed="false" aria-label="Modo escuro" title="Modo escuro">
                <i class="bi bi-moon-stars-fill tema-toggle__lua" aria-hidden="true"></i>
                <i class="bi bi-sun-fill tema-toggle__sol" aria-hidden="true"></i>
            </button>

            <button class="menu-toggle" id="menuToggle" aria-label="Abrir menu"><i class="bi bi-list"></i></button>

        </div>
    </div>

    <?php /* A pesquisa é a "Spotlight" (Ctrl+K): janela criada pelo assets/js/app.js, com
             resultados de ajax/spotlight.php. Substituiu o antigo painel #globalSearchPanel (redesign 10/2026). */ ?>

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