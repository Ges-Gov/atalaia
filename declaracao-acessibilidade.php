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
.legal-badge{display:inline-block;background:#dcfce7;color:#166534;border-radius:999px;padding:6px 14px;font-weight:900;font-size:14px}
.legal-note{background:#fffbeb;border:1px solid #fde68a;border-radius:14px;padding:14px 16px;color:#92400e;font-weight:700;font-size:14px}
</style>

<section class="legal-hero">
    <div class="container">
        <span class="legal-kicker"><i class="bi bi-universal-access"></i> Acessibilidade</span>
        <h1>Declaração de Acessibilidade e Usabilidade</h1>
        <p>Nos termos do Decreto-Lei n.º 83/2018, de 19 de outubro (Diretiva (UE) 2016/2102).</p>
    </div>
</section>

<div class="legal-shell">
    <div class="legal-card">
        <p>A <strong><?= htmlspecialchars(siteConfig('nome_site', 'Junta de Freguesia')) ?></strong> está
        empenhada em tornar o seu sítio web acessível, em conformidade com o Decreto-Lei n.º 83/2018.
        A presente declaração aplica-se ao sítio <strong><?= htmlspecialchars($_SERVER['HTTP_HOST'] ?? '') ?></strong>.</p>

        <h2>1. Estado de conformidade</h2>
        <p><span class="legal-badge">Parcialmente conforme</span></p>
        <p>Este sítio web está <strong>parcialmente conforme</strong> com as Diretrizes de Acessibilidade para o
        Conteúdo Web (WCAG) 2.1, nível AA, devido às situações identificadas no ponto 3.</p>

        <h2>2. Medidas de acessibilidade já adotadas</h2>
        <ul>
            <li>Identificação do idioma das páginas (português).</li>
            <li>Textos alternativos nas imagens de conteúdo.</li>
            <li>Estrutura de títulos e navegação consistente em todas as páginas.</li>
            <li>Esquema de cores com contraste e tipografia legível.</li>
            <li>Apresentação adaptável a diferentes dimensões de ecrã (dispositivos móveis).</li>
            <li>Carregamento de conteúdos de terceiros (ex.: vídeo) apenas mediante consentimento.</li>
        </ul>

        <h2>3. Conteúdo não acessível</h2>
        <p>Apesar dos esforços, poderão existir limitações pontuais, designadamente:</p>
        <ul>
            <li>Alguns documentos em formato PDF publicados anteriormente podem não estar totalmente etiquetados.</li>
            <li>Em secções com imagem ou vídeo de fundo, as ferramentas automáticas assinalam o contraste do
            texto; após <strong>verificação manual</strong> confirmou-se que o texto é legível sobre o fundo
            escurecido.</li>
            <li>Conteúdos multimédia de plataformas externas (ex.: vídeo do YouTube), sujeitos às limitações
            dessas plataformas.</li>
        </ul>
        <p>Comprometemo-nos a corrigir progressivamente estas situações.</p>

        <h2>4. Elaboração da presente declaração</h2>
        <p>Esta declaração foi elaborada em <strong><?= date('m/Y') ?></strong>, com base numa autoavaliação
        que combinou:</p>
        <ul>
            <li>avaliação automática com a ferramenta AccessMonitor;</li>
            <li>verificação manual — navegação por teclado, legibilidade do texto sobre fundos escuros/imagem,
            ampliação até 200% e etiquetas dos formulários.</li>
        </ul>

        <h2>5. Contactos e mecanismo de resposta (feedback)</h2>
        <p>Para comunicar problemas de acessibilidade ou solicitar informação em formato alternativo:</p>
        <ul>
            <?php if(siteConfig('email')): ?><li>Email: <?= htmlspecialchars(siteConfig('email')) ?></li><?php endif; ?>
            <?php if(siteConfig('telefone')): ?><li>Telefone: <?= htmlspecialchars(siteConfig('telefone')) ?></li><?php endif; ?>
            <li>Página de <a href="/contactos.php">Contactos</a>.</li>
        </ul>
        <p>Procuramos responder no prazo máximo de 15 dias úteis.</p>

        <h2>6. Procedimento de queixa (via de recurso)</h2>
        <p>Se não obtiver resposta satisfatória, pode apresentar queixa junto da
        <strong>Agência para a Modernização Administrativa (AMA)</strong>, entidade responsável pela
        monitorização — <a href="https://www.acessibilidade.gov.pt" target="_blank" rel="noopener">acessibilidade.gov.pt</a>.</p>

    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
