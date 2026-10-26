</main>

<?php
/* Rodapé institucional (redesign 10/2026, do modelo novo) — CORE.
   Fotografia do território por trás, com véu escuro forte (texto sempre legível).
   A foto vem de tema_config.footer_imagem; se não houver, usa a 1.ª fotografia ativa do
   slider da página inicial — nunca uma imagem fixa no código. Mantém todas as ligações
   do rodapé anterior; redes sociais só as preenchidas em configuracoes_site. */
$rodapeImagem = temaConfig('footer_imagem', '');
if ($rodapeImagem === '' && isset($pdo)) {
    try {
        $rodapeImagem = (string) $pdo->query("SELECT imagem FROM slides_homepage WHERE ativo = 1 AND imagem <> '' ORDER BY COALESCE(ordem, 999999), id LIMIT 1")->fetchColumn();
    } catch (Exception $e) { $rodapeImagem = ''; }
}
// as mesmas redes do menu (includes/redes.php, configuradas em Sistema › Configurações)
$rodapeRedes = array_map(fn($r) => [$r['nome'], $r['url'], $r['icone']], function_exists('redesSociais') ? redesSociais() : []);
$rodapeFreguesia = trim(preg_replace('/^(Junta de Freguesia|Uni[aã]o das Freguesias)\s+(de|do|da|dos|das)\s+/iu', '', (string) siteConfig('nome_site', '')));
?>
<?php
/* Links úteis (10/2026, migração 040): logótipos de entidades e parceiros, geridos no admin
   (Página Inicial → Links Úteis). Os marcados como "patrocinado" são publicidade paga: levam
   a etiqueta "Patrocinado" (transparência) e rel="sponsored". Aparece em todas as páginas. */
require_once __DIR__ . '/logos.php';   // versão branca dos logótipos para o modo escuro
$linksUteis = [];
if (isset($pdo)) {
    try {
        $linksUteis = $pdo->query("SELECT nome, url, logo, patrocinado FROM links_uteis WHERE ativo = 1 ORDER BY ordem ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) { $linksUteis = []; }   // migração 040 ainda não corrida
}
?>
<?php if ($linksUteis): ?>
<section class="links-uteis" aria-labelledby="linksUteisTitulo" data-spy="nao">
    <div class="links-uteis__interior">
        <h2 class="links-uteis__titulo" id="linksUteisTitulo"><?= te('rodape.links_uteis.titulo') ?></h2>
        <ul class="links-uteis__lista">
            <?php foreach ($linksUteis as $lu): ?>
                <li>
                    <a class="links-uteis__item" href="<?= htmlspecialchars($lu['url']) ?>" target="_blank"
                       rel="noopener<?= $lu['patrocinado'] ? ' sponsored' : '' ?>"
                       title="<?= htmlspecialchars($lu['nome']) ?>">
                        <?php if (!empty($lu['logo'])): ?>
                            <?php
                            // Equilíbrio ótico: logótipos largos ficam mais largos e baixos, os quadrados
                            // mais pequenos — todos com uma área parecida (largura = √proporção × base).
                            $luDim = @getimagesize(__DIR__ . '/../assets/img/' . $lu['logo']);
                            $luRatio = ($luDim && $luDim[1] > 0) ? $luDim[0] / $luDim[1] : 2;
                            $luLarg = (int)round(min(190, max(56, sqrt($luRatio) * 66)));
                            ?>
                            <img class="links-uteis__logo" src="/assets/img/<?= htmlspecialchars($lu['logo']) ?>" alt="<?= htmlspecialchars($lu['nome']) ?> (abre numa nova janela)" loading="lazy" decoding="async" style="width:<?= $luLarg ?>px">
                            <?php if ($luBranco = logoBrancoUrl($lu['logo'])): ?>
                                <img class="links-uteis__logo links-uteis__logo--branco" src="<?= htmlspecialchars($luBranco) ?>" alt="" aria-hidden="true" loading="lazy" decoding="async" style="width:<?= $luLarg ?>px">
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="links-uteis__nome"><?= htmlspecialchars($lu['nome']) ?></span>
                        <?php endif; ?>
                        <?php if ($lu['patrocinado']): ?><span class="links-uteis__patrocinado"><?= te('rodape.links_uteis.patrocinado') ?></span><?php endif; ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
<?php endif; ?>

<footer class="rodape"<?= $rodapeImagem !== '' ? ' style="--rodape-imagem:url(\'/assets/img/' . htmlspecialchars($rodapeImagem, ENT_QUOTES) . '\')"' : '' ?>>
    <div class="rodape__interior">

        <div class="rodape__grelha">

            <!-- 1. Identidade -->
            <div class="rodape__col rodape__col--marca">
                <div class="rodape__brasao">
                    <?php if (!empty(siteConfig('logo'))): ?>
                        <img src="/assets/img/<?= htmlspecialchars(siteConfig('logo')) ?>" alt="" loading="lazy">
                    <?php else: ?>
                        <span><?= htmlspecialchars(temaConfig('logo_iniciais', '')) ?></span>
                    <?php endif; ?>
                </div>
                <h2 class="rodape__nome"><?= htmlspecialchars(siteConfig('nome_site', 'Junta de Freguesia')) ?></h2>
                <?php if (siteConfig('slogan', '') !== ''): ?>
                    <p class="rodape__lema"><?= htmlspecialchars(siteConfig('slogan')) ?></p>
                <?php endif; ?>
                <?php if ($rodapeFreguesia !== ''): ?>
                    <p class="rodape__texto">Autarquia local da freguesia de <?= htmlspecialchars($rodapeFreguesia) ?>, ao serviço da população, do território e da sua identidade.</p>
                <?php endif; ?>
            </div>

            <!-- 2. Contactos e horário -->
            <div class="rodape__col">
                <h3 class="rodape__titulo">Contactos</h3>
                <ul class="rodape__contactos">
                    <?php if (siteConfig('telefone', '') !== ''): ?>
                        <li><span class="rodape__ico" aria-hidden="true"><i class="bi bi-telephone-fill"></i></span>
                            <a href="tel:<?= htmlspecialchars(preg_replace('/[^\d+]/', '', siteConfig('telefone'))) ?>"><?= htmlspecialchars(siteConfig('telefone')) ?></a></li>
                    <?php endif; ?>
                    <?php if (siteConfig('email', '') !== ''): ?>
                        <li><span class="rodape__ico" aria-hidden="true"><i class="bi bi-envelope-fill"></i></span>
                            <a href="mailto:<?= htmlspecialchars(siteConfig('email')) ?>"><?= str_replace('@', '<wbr>@', htmlspecialchars(siteConfig('email'))) ?></a></li>
                    <?php endif; ?>
                    <?php if (siteConfig('morada', '') !== ''): ?>
                        <li><span class="rodape__ico" aria-hidden="true"><i class="bi bi-geo-alt-fill"></i></span>
                            <span><?= htmlspecialchars(siteConfig('morada')) ?></span></li>
                    <?php endif; ?>
                </ul>
                <div class="rodape__horario">
                    <span class="rodape__horario-tag"><i class="bi bi-clock-fill" aria-hidden="true"></i> Horário de atendimento</span>
                    <p><?= htmlspecialchars(siteConfig('horario', '09h00-17h00')) ?></p>
                </div>
            </div>

            <!-- 3. Junta Virtual e ligações (as mesmas do rodapé anterior) -->
            <nav class="rodape__col" aria-label="Junta Virtual e ligações úteis">
                <h3 class="rodape__titulo">Junta Virtual</h3>
                <ul class="rodape__links">
                    <li><a href="/pedidos.php">Pedidos à Junta</a></li>
                    <?php /* Balcão Virtual temporariamente oculto: <li><a href="/minha-area.php">Balcão Virtual</a></li> */ ?>
                    <li><a href="/requerimentos.php">Requerimentos</a></li>
                    <li><a href="/marcacoes.php">Marcações</a></li>
                    <li><a href="/livro-reclamacoes.php">Livro de Reclamações</a></li>
                </ul>
                <h3 class="rodape__titulo rodape__titulo--sub">Ligações úteis</h3>
                <ul class="rodape__links rodape__links--linha">
                    <li><a href="/transparencia.php">Transparência</a></li>
                    <li><a href="/noticias.php">Notícias</a></li>
                    <li><a href="/eventos.php">Eventos</a></li>
                    <li><a href="/contactos.php">Contactos</a></li>
                    <li><a href="/faq.php">FAQ's</a></li>
                </ul>
            </nav>

            <!-- 4. Newsletter e redes sociais -->
            <div class="rodape__col">
                <h3 class="rodape__titulo">Newsletter</h3>
                <p class="rodape__texto">Receba as notícias e avisos da Junta no seu email.</p>
                <?php if (isset($_GET['newsletter']) && $_GET['newsletter'] === 'ok'): ?>
                    <p class="rodape__ok" role="status"><i class="bi bi-check-circle-fill" aria-hidden="true"></i> Obrigado por subscrever!</p>
                <?php else: ?>
                    <form class="rodape__newsletter" method="POST" action="/newsletter-subscrever.php">
                        <input type="text" name="website" tabindex="-1" autocomplete="off" style="position:absolute;left:-9999px;width:1px;height:1px;opacity:0;" aria-hidden="true">
                        <label class="sr-only" for="rodapeEmail">O seu email</label>
                        <input id="rodapeEmail" type="email" name="email" placeholder="O seu email" autocomplete="email" required>
                        <button type="submit">Subscrever</button>
                    </form>
                <?php endif; ?>

                <?php if ($rodapeRedes): ?>
                    <h3 class="rodape__titulo rodape__titulo--sub">Siga-nos</h3>
                    <ul class="rodape__redes">
                        <?php foreach ($rodapeRedes as [$redeNome, $redeUrl, $redeIco]): ?>
                            <li><a href="<?= htmlspecialchars($redeUrl) ?>" target="_blank" rel="noopener" aria-label="<?= $redeNome ?> (abre numa nova janela)"><i class="bi <?= $redeIco ?>" aria-hidden="true"></i></a></li>
                        <?php endforeach; ?>
                    </ul>
                <?php endif; ?>
            </div>
        </div>

        <div class="rodape__fundo">
            <nav class="rodape__legal" aria-label="Ligações legais e institucionais">
                <a href="/politica-privacidade.php">Política de Privacidade</a>
                <a href="/politica-cookies.php">Política de Cookies</a>
                <a href="/protecao-dados.php">Proteção de Dados (DPO)</a>
                <a href="/declaracao-acessibilidade.php">Acessibilidade</a>
                <a href="/canal-denuncias.php">Canal de Denúncias</a>
                <a href="/livro-reclamacoes.php">Livro de Reclamações</a>
            </nav>
            <div class="rodape__creditos">
                <span><?= htmlspecialchars(siteConfig('footer', '© ' . date('Y') . ' ' . siteConfig('nome_site', 'Junta de Freguesia'))) ?></span>
                <span>Powered by <a href="https://www.gesgov.pt" target="_blank" rel="noopener">GesGov</a></span>
                <a class="rodape__reservada" href="/admin/login.php"><i class="bi bi-lock-fill" aria-hidden="true"></i> Área reservada</a>
            </div>
        </div>

    </div>
</footer>

<?php if (temaConfig('tema_camada', '1') === '1'): ?>
<style id="sd-tema-claro">
/* === Tema (camada final — sobrepõe o CSS das páginas) ===
   CORE: este bloco é IDÊNTICO em todos os sites. As cores da freguesia vêm dos
   tokens --tema-* emitidos no :root pelo includes/header.php a partir da tabela
   tema_config. Ver includes/tema.php e migrations/001_tema_config.sql.
   Não voltar a pôr cores fixas aqui: era isso que impedia portar correções
   entre sites sem lhes trocar a marca.

   tema_camada=0 desliga esta camada: é o caso do riomoinhos, que mantém o tema
   ESCURO original (cada página com o seu próprio herói). Sem a camada não há
   gradiente a tapar nada, por isso as imagens de fundo funcionam na mesma. */
body{ background:var(--tema-fundo); }

/* Barra de topo */
.top-bar{ background:var(--tema-topbar-bg) !important; color:var(--tema-topbar-texto) !important; border-bottom:3px solid var(--tema-topbar-borda) !important; }
.top-bar a, .top-bar i, .top-bar-inner, .top-bar span{ color:var(--tema-topbar-texto) !important; }

/* Heróis SEM imagem de fundo definida em "Separadores de Fundo" (backoffice).
   A guarda body:not(.tem-fundo-separador) é ESSENCIAL: sem ela este gradiente
   sobrepõe (com !important) a fotografia aplicada pelo header.php e a imagem
   de fundo nunca chega a aparecer. */
body:not(.tem-fundo-separador) section[class$="-hero"]:not(.home-video-hero),
body:not(.tem-fundo-separador) div[class$="-hero"]:not(.home-video-hero),
body:not(.tem-fundo-separador) header[class$="-hero"]:not(.home-video-hero){
  background:linear-gradient(135deg,var(--tema-hero-1),var(--tema-hero-2)) !important;
  color:var(--tema-hero-texto) !important;
  border-bottom:3px solid color-mix(in srgb, var(--tema-acento) 55%, transparent) !important;
}
body:not(.tem-fundo-separador) [class$="-hero"]:not(.home-video-hero) h1,
body:not(.tem-fundo-separador) [class$="-hero"]:not(.home-video-hero) h2,
body:not(.tem-fundo-separador) [class$="-hero"]:not(.home-video-hero) h3,
body:not(.tem-fundo-separador) [class$="-hero"]:not(.home-video-hero) h4,
body:not(.tem-fundo-separador) [class$="-hero"]:not(.home-video-hero) p,
body:not(.tem-fundo-separador) [class$="-hero"]:not(.home-video-hero) span,
body:not(.tem-fundo-separador) [class$="-hero"]:not(.home-video-hero) small,
body:not(.tem-fundo-separador) [class$="-hero"]:not(.home-video-hero) li,
body:not(.tem-fundo-separador) [class$="-hero"]:not(.home-video-hero) strong{ color:var(--tema-hero-texto) !important; }
body:not(.tem-fundo-separador) [class$="-hero"]:not(.home-video-hero) [class*="kicker"],
body:not(.tem-fundo-separador) [class$="-hero"]:not(.home-video-hero) [class*="badge"],
body:not(.tem-fundo-separador) [class$="-hero"]:not(.home-video-hero) [class*="tag"]{
  background:color-mix(in srgb, var(--tema-acento) 16%, transparent) !important;
  border:1px solid color-mix(in srgb, var(--tema-acento) 50%, transparent) !important;
  color:var(--tema-acento-escuro) !important;
}

/* Heróis COM imagem de fundo: mantém a foto + véu escuro aplicados no header.php,
   só repõe texto branco legível por cima. */
body.tem-fundo-separador [class$="-hero"]:not(.home-video-hero) h1,
body.tem-fundo-separador [class$="-hero"]:not(.home-video-hero) h2,
body.tem-fundo-separador [class$="-hero"]:not(.home-video-hero) h3,
body.tem-fundo-separador [class$="-hero"]:not(.home-video-hero) h4,
body.tem-fundo-separador [class$="-hero"]:not(.home-video-hero) p,
body.tem-fundo-separador [class$="-hero"]:not(.home-video-hero) span,
body.tem-fundo-separador [class$="-hero"]:not(.home-video-hero) small,
body.tem-fundo-separador [class$="-hero"]:not(.home-video-hero) li,
body.tem-fundo-separador [class$="-hero"]:not(.home-video-hero) strong{ color:#ffffff !important; }
body.tem-fundo-separador [class$="-hero"]:not(.home-video-hero) [class*="kicker"],
body.tem-fundo-separador [class$="-hero"]:not(.home-video-hero) [class*="badge"],
body.tem-fundo-separador [class$="-hero"]:not(.home-video-hero) [class*="tag"]{
  background:color-mix(in srgb, var(--tema-acento) 28%, transparent) !important;
  border:1px solid color-mix(in srgb, var(--tema-acento) 60%, transparent) !important;
  color:var(--tema-kicker-img-texto) !important;
}

/* Botões "secundários" (outline) dentro de heróis claros — eram texto branco, ficavam invisíveis */
[class$="-hero"]:not(.home-video-hero) a.secondary,
[class$="-hero"]:not(.home-video-hero) button.secondary,
[class$="-hero"]:not(.home-video-hero) [class*="btn"].secondary{
  background:#ffffff !important;
  color:var(--tema-hero-texto) !important;
  border:1px solid rgba(36,42,48,.22) !important;
}

/* Rodapé */
.rm-footer{ background:var(--tema-footer-bg) !important; color:var(--tema-hero-texto) !important; border-top:3px solid var(--cor-secundaria) !important; }
.rm-footer h3, .rm-footer h4{ color:var(--tema-acento-escuro) !important; }
.rm-footer a, .rm-footer p, .rm-footer span, .rm-footer i, .rm-footer button{ color:var(--tema-footer-texto) !important; }
.rm-footer a:hover{ color:var(--tema-acento-escuro) !important; }
.rm-footer-logo{ background:#fff !important; }
.rm-footer-grid > div{ background:#ffffff !important; border:1px solid rgba(36,42,48,.10) !important; }
</style>
<?php endif; ?>

<!-- ============ Consentimento de Cookies (RGPD / Lei 41/2004) ============ -->
<style>
.rm-footer-legal{display:flex;flex-wrap:wrap;gap:8px 20px;justify-content:center;padding:16px 0;border-top:1px solid rgba(36,42,48,.14);margin-top:8px}
.rm-footer-legal a{color:inherit;opacity:.85;text-decoration:none;font-weight:700;font-size:14px}
.rm-footer-legal a:hover{opacity:1;text-decoration:underline}
#rm-cookie-banner{position:fixed;left:16px;right:16px;bottom:16px;z-index:9999;max-width:920px;margin:0 auto;background:#fff;color:#11151B;border:1px solid #e5e7eb;border-radius:20px;box-shadow:0 22px 60px rgba(15,23,42,.28);padding:20px 22px;display:flex;gap:20px;align-items:center;flex-wrap:wrap}
#rm-cookie-banner[hidden]{display:none}
#rm-cookie-banner .rm-cookie-text{flex:1;min-width:260px}
#rm-cookie-banner strong{display:block;font-size:17px;margin-bottom:4px}
#rm-cookie-banner p{margin:0;color:#475569;font-size:14px;line-height:1.6}
#rm-cookie-banner a{color:var(--cor-principal,#242A30);font-weight:800}
#rm-cookie-banner .rm-cookie-actions{display:flex;gap:10px;flex-wrap:wrap}
.rm-cookie-btn{border:0;border-radius:12px;padding:12px 20px;font-weight:900;cursor:pointer;font-size:14px}
.rm-cookie-btn.accept{background:var(--cor-principal,#242A30);color:#fff}
.rm-cookie-btn.ghost{background:#fff;color:#475569;border:1.5px solid #dbe3ea}
.rm-cookie-btn.ghost:hover{background:#f8fafc;border-color:#94a3b8}
@media(max-width:640px){#rm-cookie-banner .rm-cookie-actions{width:100%}.rm-cookie-btn{flex:1}}
</style>

<?php $rmClarityAtivo = function_exists('temaConfig') && temaConfig('clarity_project_id', '') !== ''; ?>
<div id="rm-cookie-banner" hidden role="dialog" aria-label="Aviso de cookies">
    <div class="rm-cookie-text">
        <strong>Este site utiliza cookies</strong>
        <p>Usamos cookies estritamente necessários (essenciais ao funcionamento) e, apenas com o seu
        consentimento<?= $rmClarityAtivo ? ', dados analíticos/estatísticos de acesso ao site (ex.: páginas visitadas, cliques) e' : '' ?>
        conteúdos de terceiros como o vídeo do YouTube. Consulte a
        <a href="/politica-cookies.php">Política de Cookies</a>.</p>
    </div>
    <div class="rm-cookie-actions">
        <button type="button" id="rm-cookie-essential" class="rm-cookie-btn ghost">Só essenciais</button>
        <button type="button" id="rm-cookie-accept" class="rm-cookie-btn accept">Aceitar todos</button>
    </div>
</div>

<script>
(function(){
    var KEY = 'rm_cookie_consent';
    var banner = document.getElementById('rm-cookie-banner');

    // ID do projeto Microsoft Clarity (estatísticas/heatmap) — vive em
    // tema_config, nunca neste ficheiro CORE. Vazio = funcionalidade desligada
    // neste site, sem qualquer script a carregar.
    var CLARITY_ID = <?= json_encode(function_exists('temaConfig') ? temaConfig('clarity_project_id', '') : '') ?>;

    function carregarClarity(){
        if (!CLARITY_ID || window.__clarityCarregado) return;
        window.__clarityCarregado = true;
        (function(c,l,a,r,i,t,y){
            c[a]=c[a]||function(){(c[a].q=c[a].q||[]).push(arguments)};
            t=l.createElement(r);t.async=1;t.src="https://www.clarity.ms/tag/"+i;
            y=l.getElementsByTagName(r)[0];y.parentNode.insertBefore(t,y);
        })(window, document, "clarity", "script", CLARITY_ID);
    }

    function apply(state){
        var accepted = (state === 'accepted');
        // Carrega iframes de terceiros só com consentimento
        document.querySelectorAll('iframe[data-cookie-src]').forEach(function(f){
            if (accepted && !f.getAttribute('src')) {
                f.setAttribute('src', f.getAttribute('data-cookie-src'));
            }
        });
        // Mostra o botão "permitir vídeo" quando ainda não há consentimento aceite
        document.querySelectorAll('.rm-needs-consent').forEach(function(el){
            el.style.display = accepted ? 'none' : '';
        });
        // Estatísticas (Microsoft Clarity) só arrancam com consentimento total
        if (accepted) carregarClarity();
    }

    function choose(state){
        try { localStorage.setItem(KEY, state); } catch(e){}
        if (banner) banner.hidden = true;
        apply(state);
    }

    var saved = null;
    try { saved = localStorage.getItem(KEY); } catch(e){}

    // Só há necessidade de consentimento se a página tiver conteúdo de terceiros
    // (ex.: vídeo do YouTube) OU se este site tiver estatísticas (Clarity) ativas.
    var temTerceiros = document.querySelector('iframe[data-cookie-src]') !== null || !!CLARITY_ID;

    if (saved) {
        apply(saved);
    } else if (banner && temTerceiros) {
        banner.hidden = false;
    }

    var a = document.getElementById('rm-cookie-accept');
    if (a) a.addEventListener('click', function(){ choose('accepted'); });
    var e = document.getElementById('rm-cookie-essential');
    if (e) e.addEventListener('click', function(){ choose('essential'); });

    // Permite reabrir/aceitar a partir de outros botões (ex.: placeholder do vídeo)
    window.rmAcceptCookies = function(){ choose('accepted'); };
})();
</script>

<?php
/* Assistente de IA (chatbot) — só aparece em sites onde includes/ia_config.php
   foi criado no servidor com uma chave válida. Nos restantes, este bloco não
   imprime nada. Ver includes/ia_config.example.php. */
$iaConfigPath = __DIR__ . '/ia_config.php';
$iaDisponivel = false;
if (is_file($iaConfigPath)) {
    require_once $iaConfigPath;
    $iaDisponivel = defined('IA_API_KEY') && IA_API_KEY !== '';
}
?>
<?php if ($iaDisponivel): ?>
<style>
.chat-ia-fab{position:fixed;right:22px;bottom:22px;z-index:9000;width:58px;height:58px;border-radius:50%;background:var(--cor-principal);color:#fff;border:0;box-shadow:0 14px 34px rgba(0,0,0,.22);cursor:pointer;display:flex;align-items:center;justify-content:center;font-size:24px;transition:.2s;}
.chat-ia-fab:hover{transform:translateY(-3px);}
.chat-ia-panel{position:fixed;right:22px;bottom:92px;z-index:9000;width:min(360px,calc(100vw - 44px));max-height:min(520px,calc(100vh - 140px));background:#fff;border-radius:22px;box-shadow:0 24px 60px rgba(0,0,0,.24);display:none;flex-direction:column;overflow:hidden;border:1px solid #e5e7eb;}
.chat-ia-panel.open{display:flex;}
.chat-ia-head{background:var(--cor-principal);color:#fff;padding:16px 18px;display:flex;justify-content:space-between;align-items:center;}
.chat-ia-head strong{font-size:15px;}
.chat-ia-head small{display:block;opacity:.75;font-size:12px;font-weight:700;}
.chat-ia-close{background:rgba(255,255,255,.15);border:0;color:#fff;width:30px;height:30px;border-radius:50%;cursor:pointer;font-size:15px;line-height:1;}
.chat-ia-body{flex:1;overflow-y:auto;padding:16px;background:#f8fafc;display:flex;flex-direction:column;gap:10px;}
.chat-ia-msg{max-width:88%;padding:10px 14px;border-radius:14px;font-size:14px;line-height:1.5;}
.chat-ia-msg.bot{background:#fff;border:1px solid #e5e7eb;align-self:flex-start;color:#11151B;}
.chat-ia-msg.user{background:var(--cor-principal);color:#fff;align-self:flex-end;}
.chat-ia-msg.loading{color:#94a3b8;font-style:italic;}
.chat-ia-form{display:flex;gap:8px;padding:12px;border-top:1px solid #e5e7eb;background:#fff;}
.chat-ia-form input{flex:1;border:1px solid #e5e7eb;border-radius:12px;padding:10px 12px;font-size:14px;font-family:inherit;}
.chat-ia-form button{background:var(--cor-principal);color:#fff;border:0;border-radius:12px;padding:0 16px;font-weight:900;cursor:pointer;}
.chat-ia-hidden{display:none!important;}
.chat-ia-consent{padding:18px;background:#fff;display:flex;flex-direction:column;gap:12px;}
.chat-ia-consent p{margin:0;font-size:13px;line-height:1.6;color:#334155;}
.chat-ia-consent a{color:var(--cor-principal);font-weight:800;}
.chat-ia-consent-actions{display:flex;gap:8px;justify-content:flex-end;}
.chat-ia-consent-actions button{border-radius:12px;padding:8px 14px;font-weight:800;font-size:13px;cursor:pointer;border:1px solid #e5e7eb;background:#fff;color:#334155;}
.chat-ia-consent-actions button.accept{background:var(--cor-principal);color:#fff;border-color:var(--cor-principal);}
/* Em ecrãs até 900px o site mostra uma barra de navegação fixa no fundo
   (.bottom-app-nav, z-index 9999) — mais alta que o botão do chat (9000),
   por isso tapava-o por completo. Sobe-se o botão para cima dessa barra e
   encolhe-se, para não ocupar tanto espaço de ecrã. */
@media(max-width:900px){
    .chat-ia-fab{width:46px;height:46px;right:16px;bottom:94px;font-size:20px;}
    .chat-ia-panel{right:16px;bottom:150px;max-height:min(460px,calc(100vh - 200px));}
}
</style>

<button type="button" class="chat-ia-fab" id="chatIaFab" aria-label="Abrir assistente virtual">
    <i class="bi bi-chat-dots-fill"></i>
</button>

<div class="chat-ia-panel" id="chatIaPanel" role="dialog" aria-label="Assistente virtual">
    <div class="chat-ia-head">
        <div>
            <strong>Assistente virtual</strong>
            <small>Pergunte sobre serviços, horários, notícias...</small>
        </div>
        <button type="button" class="chat-ia-close" id="chatIaClose" aria-label="Fechar">&#x2715;</button>
    </div>
    <div class="chat-ia-consent" id="chatIaConsent">
        <p>As perguntas que escrever são enviadas à Google (serviço Gemini) para gerar a resposta —
        não guardamos o conteúdo das perguntas. Não escreva dados pessoais, sensíveis ou de terceiros.
        Saiba mais na <a href="/politica-privacidade.php" target="_blank" rel="noopener">Política de Privacidade</a>.</p>
        <div class="chat-ia-consent-actions">
            <button type="button" id="chatIaConsentClose">Fechar</button>
            <button type="button" id="chatIaConsentAccept" class="accept">Aceito, continuar</button>
        </div>
    </div>
    <div class="chat-ia-body chat-ia-hidden" id="chatIaBody">
        <div class="chat-ia-msg bot">Olá! Sou o assistente virtual da Junta. Em que posso ajudar?</div>
    </div>
    <form class="chat-ia-form chat-ia-hidden" id="chatIaForm">
        <input type="text" id="chatIaInput" placeholder="Escreva a sua pergunta..." maxlength="500" autocomplete="off">
        <button type="submit">Enviar</button>
    </form>
</div>

<script>
(function(){
    var KEY_IA = 'rm_ia_consent';
    // sessionStorage (não localStorage): só precisa durar enquanto o separador
    // estiver aberto. Guarda a conversa e se o painel estava aberto, para que
    // clicar num link do chat (ex. Contactos) e mudar de página não pareça
    // "apagar" o chat — ao carregar a página seguinte, a conversa reaparece.
    var KEY_HIST = 'chat_ia_historico';
    var KEY_OPEN = 'chat_ia_aberto';
    var fab = document.getElementById('chatIaFab');
    var panel = document.getElementById('chatIaPanel');
    var close = document.getElementById('chatIaClose');
    var consent = document.getElementById('chatIaConsent');
    var consentAccept = document.getElementById('chatIaConsentAccept');
    var consentClose = document.getElementById('chatIaConsentClose');
    var form = document.getElementById('chatIaForm');
    var input = document.getElementById('chatIaInput');
    var body = document.getElementById('chatIaBody');

    var historico = [];
    try { historico = JSON.parse(sessionStorage.getItem(KEY_HIST) || '[]'); } catch(e){ historico = []; }

    function iaConsentGiven(){
        try { return localStorage.getItem(KEY_IA) === 'accepted'; } catch(e){ return false; }
    }
    function showChat(){
        consent.classList.add('chat-ia-hidden');
        body.classList.remove('chat-ia-hidden');
        form.classList.remove('chat-ia-hidden');
        input.focus();
    }
    function showConsent(){
        consent.classList.remove('chat-ia-hidden');
        body.classList.add('chat-ia-hidden');
        form.classList.add('chat-ia-hidden');
    }
    function salvarHistorico(){
        try { sessionStorage.setItem(KEY_HIST, JSON.stringify(historico)); } catch(e){}
    }
    function restaurarHistorico(){
        if (!historico.length) return;
        // Substitui a mensagem de boas-vindas de exemplo pela conversa real guardada.
        body.textContent = '';
        historico.forEach(function(item){
            if (item.tipo === 'bot') {
                // já passou por htmlspecialchars() + allowlist no servidor quando chegou.
                body.insertAdjacentHTML('beforeend', item.conteudo);
            } else {
                var div = document.createElement('div');
                div.className = 'chat-ia-msg user';
                div.textContent = item.conteudo;
                body.appendChild(div);
            }
        });
        body.scrollTop = body.scrollHeight;
    }
    function definirAberto(aberto){
        try { sessionStorage.setItem(KEY_OPEN, aberto ? '1' : '0'); } catch(e){}
    }

    fab.addEventListener('click', function(){
        panel.classList.toggle('open');
        var aberto = panel.classList.contains('open');
        definirAberto(aberto);
        if (aberto) {
            if (iaConsentGiven()) { showChat(); } else { showConsent(); }
        }
    });
    close.addEventListener('click', function(){ panel.classList.remove('open'); definirAberto(false); });
    consentClose.addEventListener('click', function(){ panel.classList.remove('open'); definirAberto(false); });
    consentAccept.addEventListener('click', function(){
        try { localStorage.setItem(KEY_IA, 'accepted'); } catch(e){}
        showChat();
    });

    form.addEventListener('submit', function(e){
        e.preventDefault();
        var pergunta = input.value.trim();
        if (!pergunta) return;

        var userMsg = document.createElement('div');
        userMsg.className = 'chat-ia-msg user';
        userMsg.textContent = pergunta;
        body.appendChild(userMsg);
        historico.push({tipo: 'user', conteudo: pergunta});
        salvarHistorico();

        var loading = document.createElement('div');
        loading.className = 'chat-ia-msg bot loading';
        loading.textContent = 'A escrever...';
        body.appendChild(loading);
        body.scrollTop = body.scrollHeight;

        input.value = '';
        input.disabled = true;

        fetch('/ajax/chat_ia.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'pergunta=' + encodeURIComponent(pergunta)
        })
        .then(function(r){ return r.text(); })
        .then(function(html){
            loading.remove();
            body.insertAdjacentHTML('beforeend', html);
            body.scrollTop = body.scrollHeight;
            historico.push({tipo: 'bot', conteudo: html});
            salvarHistorico();
        })
        .catch(function(){
            loading.textContent = 'Não consegui responder agora. Tente novamente.';
            loading.classList.remove('loading');
        })
        .finally(function(){
            input.disabled = false;
            input.focus();
        });
    });

    // Ao carregar a página, restaura a conversa e o estado do painel de uma
    // navegação anterior (ex.: o visitante clicou num link que o chat deu).
    restaurarHistorico();
    try {
        if (sessionStorage.getItem(KEY_OPEN) === '1') {
            panel.classList.add('open');
            if (iaConsentGiven()) { showChat(); } else { showConsent(); }
        }
    } catch(e){}
})();
</script>
<?php endif; ?>

<script src="/assets/js/app.js?v=20261020h"></script>
</body>
</html>
