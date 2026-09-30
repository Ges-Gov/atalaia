<?php require_once __DIR__ . "/includes/header.php"; ?>
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
.legal-note{background:#fffbeb;border:1px solid #fde68a;border-radius:14px;padding:14px 16px;color:#92400e;font-weight:700;font-size:14px}
</style>

<section class="legal-hero">
    <div class="container">
        <span class="legal-kicker"><i class="bi bi-shield-lock"></i> Proteção de Dados</span>
        <h1>Política de Privacidade</h1>
        <p>Como tratamos e protegemos os seus dados pessoais, nos termos do RGPD e da Lei n.º 58/2019.</p>
    </div>
</section>

<div class="legal-shell">
    <div class="legal-card">
        <p class="legal-meta">Última atualização: <?= date('m/Y') ?></p>

        <h2>1. Responsável pelo tratamento</h2>
        <p>A <strong><?= htmlspecialchars(siteConfig('nome_site', 'Junta de Freguesia')) ?></strong> é a
        entidade responsável pelo tratamento dos dados pessoais recolhidos através deste sítio web.</p>
        <ul>
            <?php if(siteConfig('morada')): ?><li>Morada: <?= htmlspecialchars(siteConfig('morada')) ?></li><?php endif; ?>
            <?php if(siteConfig('telefone')): ?><li>Telefone: <?= htmlspecialchars(siteConfig('telefone')) ?></li><?php endif; ?>
            <?php if(siteConfig('email')): ?><li>Email: <?= htmlspecialchars(siteConfig('email')) ?></li><?php endif; ?>
        </ul>
        <p>Pode contactar o nosso <strong>Encarregado de Proteção de Dados (EPD)</strong> através do
        <a href="/protecao-dados.php">canal de Proteção de Dados</a>.</p>

        <h2>2. Que dados recolhemos e com que finalidade</h2>
        <p>Recolhemos apenas os dados necessários para prestar os serviços solicitados, designadamente:</p>
        <ul>
            <li><strong>Formulários e pedidos</strong> (denúncias, pedidos ao EPD, requerimentos, marcações,
            atestados, candidaturas a procedimentos concursais, registo de cidadão): nome, contactos e
            informação que decida fornecer — para dar resposta e tratamento ao pedido.</li>
            <li><strong>Dados de navegação</strong>: cookies estritamente necessários ao funcionamento do site
            (ver <a href="/politica-cookies.php">Política de Cookies</a>).</li>
            <li><strong>Assistente virtual (se disponível no site)</strong>: a pergunta que escrever — ver
            ponto 5.1 abaixo para mais detalhe sobre este tratamento em concreto.</li>
        </ul>

        <h2>3. Fundamento de licitude</h2>
        <p>O tratamento assenta, consoante o caso, no <strong>exercício de funções de interesse público</strong>
        e no <strong>cumprimento de obrigações legais</strong> a que a Junta está sujeita (art. 6.º, n.º 1, als. c) e e)
        do RGPD) e, quando aplicável, no seu <strong>consentimento</strong> (por exemplo, para cookies não essenciais).</p>

        <h2>4. Conservação dos dados</h2>
        <p>Os dados são conservados apenas pelo período necessário às finalidades para que foram recolhidos e ao
        cumprimento das obrigações legais e de arquivo aplicáveis à Administração Pública.</p>

        <h2>5. Partilha e destinatários</h2>
        <p>Os dados não são cedidos a terceiros para fins comerciais. Podem ser comunicados a autoridades ou
        entidades públicas quando exista obrigação legal para o efeito.</p>

        <h3>5.1 Assistente virtual (Inteligência Artificial)</h3>
        <p>Este site pode disponibilizar um <strong>assistente virtual</strong> (ícone de conversa, geralmente
        no canto do ecrã) que responde a perguntas sobre a freguesia com base na informação já publicada no
        site (FAQ, notícias, eventos, documentos, contactos e outras páginas públicas). A sua utilização é
        <strong>inteiramente opcional</strong>.</p>
        <p>Quando escreve uma pergunta, o texto é enviado a um serviço de inteligência artificial de terceiro
        (<strong>Google Gemini</strong>, operado pela Google Ireland Limited / Google LLC), para gerar a
        resposta. Isto constitui uma <strong>transferência de dados para fora da União Europeia</strong>
        (Estados Unidos da América), realizada com base nas garantias contratuais e mecanismos de
        adequação aplicáveis pelo fornecedor do serviço (cláusulas contratuais-tipo e/ou o
        <em>EU-U.S. Data Privacy Framework</em>, consoante o caso). Para mais informação, consulte a
        <a href="https://policies.google.com/privacy" target="_blank" rel="noopener">política de privacidade da Google</a>.</p>
        <p><strong>Não guardamos o conteúdo das perguntas nem das respostas</strong> em base de dados própria —
        a conversa não fica associada à sua identidade e não é usada para nenhuma outra finalidade além de
        responder no momento. É mantido apenas, de forma temporária e associada à sua sessão de navegação
        (cookie <code>PHPSESSID</code>, ver <a href="/politica-cookies.php">Política de Cookies</a>), um
        contador do número de perguntas feitas, para evitar utilização abusiva do serviço — este contador não
        contém o texto das perguntas.</p>
        <p>O fundamento de licitude deste tratamento é o <strong>consentimento</strong>, expresso pela decisão
        voluntária de usar o assistente. <strong>Recomendamos que não inclua dados pessoais, sensíveis ou de
        terceiros</strong> nas perguntas que colocar — o assistente destina-se a informação pública sobre a
        freguesia, e não a pedidos, reclamações ou denúncias, que devem continuar a ser feitos através dos
        canais próprios (<a href="/requerimentos.php">Requerimentos</a>, <a href="/canal-denuncias.php">Canal
        de Denúncias</a>, <a href="/protecao-dados.php">Proteção de Dados</a>).</p>

        <h2>6. Os seus direitos</h2>
        <p>Enquanto titular dos dados, tem direito de <strong>acesso, retificação, apagamento, limitação,
        oposição e portabilidade</strong>, nos termos do RGPD. Pode exercê-los através do
        <a href="/protecao-dados.php">canal de Proteção de Dados</a>.</p>
        <p>Tem ainda o direito de apresentar reclamação à autoridade de controlo, a
        <strong>Comissão Nacional de Proteção de Dados (CNPD)</strong> — <a href="https://www.cnpd.pt" target="_blank" rel="noopener">www.cnpd.pt</a>.</p>

        <h2>7. Segurança</h2>
        <p>Adotamos medidas técnicas e organizativas adequadas para proteger os dados contra acesso não
        autorizado, perda ou destruição.</p>

        <h2>8. Alterações</h2>
        <p>Esta política pode ser atualizada. A versão em vigor é a publicada nesta página.</p>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
