<?php
require_once __DIR__ . "/includes/header.php";
$clarityAtivo = function_exists('temaConfig') ? (temaConfig('clarity_project_id', '') !== '') : false;
?>
<style>
.legal-hero{background:linear-gradient(135deg,var(--cor-principal),#11151B 70%,#071c2e);color:#fff;padding:80px 0 96px}
.legal-hero .container{max-width:900px}
.legal-kicker{display:inline-flex;background:rgba(212,170,0,.16);border:1px solid rgba(212,170,0,.38);color:#F0D060;padding:8px 14px;border-radius:999px;font-weight:900;text-transform:uppercase;letter-spacing:.7px;font-size:12px}
.legal-hero h1{font-size:clamp(34px,5vw,58px);margin:16px 0 10px;line-height:1.05}
.legal-hero p{color:#dbeafe;font-size:18px;margin:0}
.legal-shell{max-width:900px;margin:-46px auto 60px;position:relative;z-index:3;padding:0 16px}
.legal-card{background:#fff;border:1px solid #e5e7eb;border-radius:26px;box-shadow:0 18px 50px rgba(15,23,42,.08);padding:34px 38px}
.legal-card h2{color:#11151B;font-size:24px;margin:30px 0 10px}
.legal-card h2:first-child{margin-top:0}
.legal-card p,.legal-card li{color:#334155;line-height:1.75;font-size:16px}
.legal-card ul{padding-left:22px}
.legal-card a{color:var(--cor-principal);font-weight:800}
.legal-meta{color:#64748b;font-size:14px;margin-top:8px}
.legal-table{width:100%;border-collapse:collapse;margin:12px 0;font-size:15px}
.legal-table caption{text-align:left;font-weight:800;color:#52606d;font-size:13px;margin-bottom:6px;caption-side:top}
.legal-table th,.legal-table td{border:1px solid #e5e7eb;padding:10px 12px;text-align:left;vertical-align:top}
.legal-table th{background:#f8fafc;color:#11151B}
</style>

<section class="legal-hero">
    <div class="container">
        <span class="legal-kicker"><i class="bi bi-cookie"></i> Cookies</span>
        <h1>Política de Cookies</h1>
        <p>O que são cookies, quais utilizamos e como pode gerir o seu consentimento.</p>
    </div>
</section>

<div class="legal-shell">
    <div class="legal-card">
        <p class="legal-meta">Última atualização: <?= date('m/Y') ?></p>

        <h2>1. O que são cookies</h2>
        <p>Cookies são pequenos ficheiros de texto guardados no seu dispositivo quando visita um site.
        Servem para o site funcionar corretamente e, nalguns casos, para carregar conteúdos de terceiros.</p>

        <h2>2. Cookies estritamente necessários</h2>
        <p>São essenciais ao funcionamento do site e <strong>não carecem de consentimento</strong>.</p>
        <table class="legal-table">
            <caption>Cookies estritamente necessários utilizados neste site</caption>
            <tr><th scope="col">Cookie</th><th scope="col">Finalidade</th><th scope="col">Duração</th></tr>
            <tr><td>PHPSESSID</td><td>Manter a sessão do utilizador (ex.: área de cidadão, formulários e, quando disponível, limitar o número de perguntas ao assistente virtual)</td><td>Sessão</td></tr>
            <tr><td>rm_cookie_consent</td><td>Guardar a sua preferência de cookies</td><td>Persistente (armazenamento local)</td></tr>
        </table>

        <h2>3. Cookies de terceiros (só com consentimento)</h2>
        <p>Só são ativados <strong>depois de aceitar</strong> no aviso de cookies:</p>
        <table class="legal-table">
            <caption>Cookies de terceiros carregados apenas após consentimento</caption>
            <tr><th scope="col">Origem</th><th scope="col">Finalidade</th><th scope="col">Mais informação</th></tr>
            <tr>
                <td>YouTube (Google)</td>
                <td>Reprodução do vídeo em destaque na página inicial. Utilizamos o modo de privacidade
                melhorada (<em>youtube-nocookie.com</em>); ainda assim, ao reproduzir, o YouTube pode
                definir cookies.</td>
                <td><a href="https://policies.google.com/privacy" target="_blank" rel="noopener">Privacidade Google</a></td>
            </tr>
            <?php if ($clarityAtivo): ?>
            <tr>
                <td>Microsoft Clarity</td>
                <td>Estatísticas de utilização do site (páginas visitadas, cliques, mapas de calor) para
                nos ajudar a melhorar a navegação. Não recolhe dados que identifiquem diretamente o
                visitante.</td>
                <td><a href="https://privacy.microsoft.com/pt-pt/privacystatement" target="_blank" rel="noopener">Privacidade Microsoft</a></td>
            </tr>
            <?php endif; ?>
        </table>
        <p>Alguns recursos são carregados de serviços externos que <strong>não definem cookies</strong>, mas
        podem registar o endereço IP para servir o conteúdo:</p>
        <ul>
            <li><strong>Mapa</strong>: usamos <em>OpenStreetMap</em> e a biblioteca <em>Leaflet</em> (mapa aberto,
            sem rastreio) para mostrar os pontos de interesse.</li>
            <li><strong>Ícones/tipos de letra</strong>: carregados de uma rede de distribuição (CDN).</li>
            <li><strong>Assistente virtual</strong> (quando disponível no site): o texto que escrever é enviado
            à Google (serviço Gemini) para gerar a resposta — não define cookies próprios, mas constitui uma
            transferência de dados para os EUA. Detalhe completo na
            <a href="/politica-privacidade.php">Política de Privacidade</a>, ponto 5.1.</li>
        </ul>

        <h2>4. Como gerir o consentimento</h2>
        <p>Na primeira visita é apresentado um aviso onde pode <strong>"Aceitar todos"</strong> ou escolher
        <strong>"Só essenciais"</strong>. Pode alterar a sua escolha a qualquer momento limpando os dados do
        navegador para este site (o aviso volta a aparecer) ou através das definições do seu navegador.</p>

        <h2>5. Mais informação</h2>
        <p>Consulte também a nossa <a href="/politica-privacidade.php">Política de Privacidade</a>.</p>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
