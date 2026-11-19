<?php
/**
 * Glossário (CORE) — termos da vida autárquica usados no site, com uma definição curta (19/11/2026).
 * Checklist «Conteúdo» do Selo de Usabilidade e Acessibilidade, requisito 1.2: os termos menos correntes têm uma
 * definição e fazem parte de um glossário. Nos textos do site, a primeira ocorrência de cada termo liga para aqui
 * (#termo) — ver includes/glossario-termos.php (lista única) e app.js.
 */
require_once __DIR__ . "/includes/header.php";
require_once __DIR__ . "/includes/glossario-termos.php";
$termos = glossarioTermos();
?>
<style>
.legal-hero{background:linear-gradient(135deg,var(--cor-principal),#11151B 70%,#071c2e);color:#fff;padding:80px 0 96px}
.legal-hero .container{max-width:900px}
.legal-kicker{display:inline-flex;background:rgb(var(--ouro-rgb, 240 180 41) / .16);border:1px solid rgb(var(--ouro-rgb, 240 180 41) / .38);color:var(--ouro-claro, #f8d77a);padding:8px 14px;border-radius:999px;font-weight:900;text-transform:uppercase;letter-spacing:.8px;font-size:13.5px}
.legal-hero h1{font-size:clamp(34px,5vw,58px);margin:16px 0 10px;line-height:1.05}
.legal-hero p{color:#dbeafe;font-size:18px;margin:0;max-width:70ch}
.legal-shell{max-width:900px;margin:-46px auto 60px;position:relative;z-index:3;padding:0 16px}
.legal-card{background:#fff;border:1px solid #e5e7eb;border-radius:26px;box-shadow:0 18px 50px rgba(15,23,42,.08);padding:34px 38px}
.glossario{margin:0;display:grid;gap:4px}
.glossario dt{color:#11151B;font-weight:800;font-size:18px;margin-top:18px;scroll-margin-top:110px}
.glossario dt:first-child{margin-top:0}
.glossario dfn{font-style:normal}
.glossario dd{margin:0;color:#334155;line-height:1.7;font-size:16px;max-width:70ch}
.glossario-indice{display:flex;flex-wrap:wrap;gap:8px;margin:0 0 26px;padding:0;list-style:none}
.glossario-indice a{display:inline-flex;align-items:center;min-height:44px;padding:0 14px;border-radius:999px;border:1px solid #dbe4ee;color:var(--cor-principal);font-weight:700;text-decoration:none}
.glossario-indice a:hover{background:#f8fafc}
@media(max-width:700px){.legal-card{padding:24px 20px}}
:root[data-tema="escuro"] .legal-card{background:var(--escuro-superficie);border-color:var(--escuro-borda)}
:root[data-tema="escuro"] .glossario dt{color:var(--escuro-texto)}
:root[data-tema="escuro"] .glossario dd{color:var(--escuro-texto-2)}
:root[data-tema="escuro"] .glossario-indice a{border-color:var(--escuro-borda)}
</style>

<section class="legal-hero">
    <div class="container">
        <span class="legal-kicker"><i class="bi bi-book" aria-hidden="true"></i> Ajuda</span>
        <h1>Glossário</h1>
        <p>O que querem dizer os termos da vida da freguesia que aparecem neste site.</p>
    </div>
</section>

<div class="legal-shell">
    <div class="legal-card">
        <nav aria-label="Termos do glossário">
            <ul class="glossario-indice">
                <?php foreach ($termos as $id => [$termo]): ?>
                    <li><a href="#<?= $id ?>"><?= htmlspecialchars($termo) ?></a></li>
                <?php endforeach; ?>
            </ul>
        </nav>
        <dl class="glossario">
            <?php foreach ($termos as $id => [$termo, $definicao]): ?>
                <dt id="<?= $id ?>"><dfn><?= htmlspecialchars($termo) ?></dfn></dt>
                <dd><?= htmlspecialchars($definicao) ?></dd>
            <?php endforeach; ?>
        </dl>
    </div>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
