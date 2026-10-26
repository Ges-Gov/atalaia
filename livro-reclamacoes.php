<?php require_once "includes/header.php"; ?>

<style>
.lr-page{background:radial-gradient(circle at top left,rgb(var(--ouro-rgb, 240 180 41) / .18),transparent 34%),radial-gradient(circle at top right,rgba(36,42,50,.16),transparent 36%),linear-gradient(180deg,#f7f4ef 0%,#f8fafc 42%,#fff 100%)}
.lr-hero{position:relative;overflow:hidden;padding:96px 0 115px;background:linear-gradient(135deg,rgba(36,42,50,.98),rgba(17,21,28,.96)),url('/assets/img/freguesia-1.jpg') center/cover no-repeat;color:white}
.lr-hero:before{content:"";position:absolute;width:540px;height:540px;right:-170px;top:-220px;border-radius:50%;background:rgb(var(--ouro-rgb, 240 180 41) / .13)}
.lr-hero:after{content:"";position:absolute;width:430px;height:430px;left:-170px;bottom:-230px;border-radius:50%;background:rgba(255,255,255,.06)}
.lr-hero-grid{position:relative;z-index:2;display:grid;grid-template-columns:minmax(0,1.2fr) 360px;gap:42px;align-items:center}
.lr-kicker{display:inline-flex;align-items:center;gap:8px;padding:9px 14px;border-radius:999px;background:rgb(var(--ouro-rgb, 240 180 41) / .16);border:1px solid rgb(var(--ouro-rgb, 240 180 41) / .36);color:var(--ouro-claro, #f8d77a);font-weight:900;letter-spacing:.8px;text-transform:uppercase;font-size:13px}
.lr-hero h1{margin:20px 0 18px;font-size:clamp(42px,6vw,76px);line-height:1.02;letter-spacing:-1.8px}
.lr-hero p{color:#dbeafe;font-size:20px;line-height:1.8;max-width:780px;margin:0}
.lr-actions{display:flex;flex-wrap:wrap;gap:12px;margin-top:30px}
.lr-btn{display:inline-flex;align-items:center;justify-content:center;gap:9px;background:var(--cor-secundaria);color:#11151B;text-decoration:none;border-radius:16px;padding:15px 20px;font-weight:900;box-shadow:0 16px 34px rgba(0,0,0,.20);transition:.25s ease}
.lr-btn:hover{transform:translateY(-3px);filter:brightness(1.05)}
.lr-btn.secondary{background:rgba(255,255,255,.12);color:white;border:1px solid rgba(255,255,255,.22);backdrop-filter:blur(10px)}
.lr-orb{width:310px;min-height:310px;border-radius:50%;display:grid;place-items:center;text-align:center;margin:auto;background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.22);box-shadow:0 32px 80px rgba(0,0,0,.28),inset 0 0 0 18px rgba(255,255,255,.035);backdrop-filter:blur(14px)}
.lr-orb strong{display:block;font-size:74px;line-height:1}.lr-orb span{display:block;color:white;font-weight:900;font-size:18px;margin-top:8px}
.lr-shell{margin-top:-64px;position:relative;z-index:5}
.lr-info-strip{background:rgba(255,255,255,.96);backdrop-filter:blur(12px);border:1px solid rgba(229,231,235,.9);border-radius:28px;padding:18px;box-shadow:0 22px 65px rgba(0,0,0,.12);display:grid;grid-template-columns:repeat(3,1fr);gap:14px;margin-bottom:26px}
.lr-info-item{display:flex;align-items:center;gap:14px;padding:18px;border-radius:22px;background:#f8fafc;border:1px solid #eef2f7}
.lr-info-icon{width:50px;height:50px;border-radius:17px;background:var(--cor-principal);color:white;display:grid;place-items:center;font-size:24px;flex-shrink:0}
.lr-info-item strong{display:block;color:#11151B;font-size:18px}.lr-info-item span{display:block;color:#64748b;font-weight:800;font-size:13px;margin-top:4px}
.lr-section-head{display:flex;justify-content:space-between;align-items:flex-end;gap:22px;margin:34px 0 22px}
.lr-section-head span{color:var(--cor-principal);text-transform:uppercase;font-weight:900;letter-spacing:.8px;font-size:13px}
.lr-section-head h2{color:#11151B;font-size:38px;margin:8px 0 0;letter-spacing:-.7px}
.lr-section-head p{max-width:620px;margin:0;color:#64748b;line-height:1.7}
.lr-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:22px;margin-bottom:28px}
.lr-card,.lr-panel{background:white;border:1px solid #eef2f7;border-radius:28px;padding:26px;box-shadow:0 18px 45px rgba(0,0,0,.08)}
.lr-card{transition:.28s ease}.lr-card:hover{transform:translateY(-7px);box-shadow:0 26px 70px rgba(0,0,0,.13)}
.lr-card-icon{width:58px;height:58px;display:grid;place-items:center;border-radius:18px;background:#f1f5f9;font-size:28px;margin-bottom:18px}
.lr-card h3{margin:0 0 12px;color:#11151B;font-size:24px}.lr-card p,.lr-panel p{margin:0;color:#64748b;line-height:1.75;font-weight:700}
.lr-panel{border-radius:30px;padding:32px;margin-bottom:28px}.lr-panel h2{margin:0 0 14px;color:#11151B;font-size:32px}
.lr-steps{display:grid;gap:14px;margin-top:20px}.lr-step{display:grid;grid-template-columns:48px 1fr;gap:14px;align-items:start;background:#f8fafc;border:1px solid #eef2f7;border-radius:20px;padding:16px}
.lr-step b{width:48px;height:48px;border-radius:16px;background:var(--cor-principal);color:white;display:grid;place-items:center}.lr-step strong{display:block;color:#11151B;margin-bottom:5px}.lr-step span{display:block;color:#64748b;line-height:1.6;font-weight:700}
.lr-cta{position:relative;overflow:hidden;border-radius:30px;padding:34px;background:linear-gradient(135deg,var(--cor-principal),#11151B);color:white;box-shadow:0 22px 60px rgba(0,0,0,.16);margin-bottom:40px}
.lr-cta:after{content:"";position:absolute;right:-80px;bottom:-110px;width:250px;height:250px;border-radius:50%;background:rgb(var(--ouro-rgb, 240 180 41) / .14)}
.lr-cta h2,.lr-cta p,.lr-cta .lr-actions{position:relative;z-index:2}.lr-cta h2{margin:0 0 12px;font-size:32px}.lr-cta p{margin:0;color:#dbeafe;line-height:1.75;max-width:850px}
@media(max-width:950px){.lr-hero-grid,.lr-info-strip,.lr-grid{grid-template-columns:1fr}.lr-orb{width:250px;min-height:250px}.lr-section-head{flex-direction:column;align-items:flex-start}}
@media(max-width:650px){.lr-hero{padding:70px 0 90px}.lr-shell{margin-top:-48px}.lr-panel,.lr-cta{padding:22px;border-radius:24px}}
</style>

<main class="lr-page">
<section class="lr-hero">
    <div class="container lr-hero-grid">
        <div>
            <span class="lr-kicker"><i class="bi bi-book"></i> <?= te('livro_reclamacoes.hero.kicker') ?></span>
            <h1><?= te('livro_reclamacoes.hero.titulo') ?></h1>
            <p><?= te('livro_reclamacoes.hero.subtitulo') ?></p>
            <div class="lr-actions">
                <a class="lr-btn" href="https://www.livroreclamacoes.pt/Inicio/" target="_blank" rel="noopener"><i class="bi bi-book"></i> <?= te('livro_reclamacoes.hero.botao') ?></a>
                <a class="lr-btn secondary" href="/contactos.php"><i class="bi bi-telephone"></i> <?= te('livro_reclamacoes.hero.botao2') ?></a>
            </div>
        </div>
        <div class="lr-orb"><div><strong><i class="bi bi-book"></i></strong><span><?= te('livro_reclamacoes.hero.selo') ?></span></div></div>
    </div>
</section>

<section class="section lr-shell">
    <div class="container">
        <div class="lr-info-strip">
            <div class="lr-info-item"><div class="lr-info-icon"><i class="bi bi-card-checklist"></i></div><div><strong><?= te('livro_reclamacoes.destaques.d1_titulo') ?></strong><span><?= te('livro_reclamacoes.destaques.d1_texto') ?></span></div></div>
            <div class="lr-info-item"><div class="lr-info-icon"><i class="bi bi-shield-lock"></i></div><div><strong><?= te('livro_reclamacoes.destaques.d2_titulo') ?></strong><span><?= te('livro_reclamacoes.destaques.d2_texto') ?></span></div></div>
            <div class="lr-info-item"><div class="lr-info-icon"><i class="bi bi-envelope-paper"></i></div><div><strong><?= te('livro_reclamacoes.destaques.d3_titulo') ?></strong><span><?= te('livro_reclamacoes.destaques.d3_texto') ?></span></div></div>
        </div>

        <div class="lr-section-head">
            <div><span><?= te('livro_reclamacoes.utilizacao.kicker') ?></span><h2><?= te('livro_reclamacoes.utilizacao.titulo') ?></h2></div>
            <p><?= te('livro_reclamacoes.utilizacao.intro') ?></p>
        </div>

        <div class="lr-grid">
            <article class="lr-card"><div class="lr-card-icon"><i class="bi bi-megaphone"></i></div><h3><?= te('livro_reclamacoes.utilizacao.c1_titulo') ?></h3><p><?= te('livro_reclamacoes.utilizacao.c1_texto') ?></p></article>
            <article class="lr-card"><div class="lr-card-icon"><i class="bi bi-question-circle"></i></div><h3><?= te('livro_reclamacoes.utilizacao.c2_titulo') ?></h3><p><?= te('livro_reclamacoes.utilizacao.c2_texto') ?></p></article>
            <article class="lr-card"><div class="lr-card-icon"><i class="bi bi-hand-thumbs-up"></i></div><h3><?= te('livro_reclamacoes.utilizacao.c3_titulo') ?></h3><p><?= te('livro_reclamacoes.utilizacao.c3_texto') ?></p></article>
        </div>

        <div class="lr-panel">
            <h2><?= te('livro_reclamacoes.passos.titulo') ?></h2>
            <p><?= te('livro_reclamacoes.passos.intro') ?></p>
            <div class="lr-steps">
                <div class="lr-step"><b>1</b><div><strong><?= te('livro_reclamacoes.passos.p1_titulo') ?></strong><span><?= te('livro_reclamacoes.passos.p1_texto') ?></span></div></div>
                <div class="lr-step"><b>2</b><div><strong><?= te('livro_reclamacoes.passos.p2_titulo') ?></strong><span><?= te('livro_reclamacoes.passos.p2_texto') ?></span></div></div>
                <div class="lr-step"><b>3</b><div><strong><?= te('livro_reclamacoes.passos.p3_titulo') ?></strong><span><?= te('livro_reclamacoes.passos.p3_texto') ?></span></div></div>
            </div>
        </div>

        <div class="lr-cta">
            <h2><?= te('livro_reclamacoes.cta.titulo') ?></h2>
            <p><?= te('livro_reclamacoes.cta.texto') ?></p>
            <div class="lr-actions">
                <a class="lr-btn" href="https://www.livroreclamacoes.pt/Inicio/" target="_blank" rel="noopener"><i class="bi bi-book"></i> <?= te('livro_reclamacoes.cta.botao') ?></a>
                <a class="lr-btn secondary" href="/contactos.php"><i class="bi bi-telephone"></i> <?= te('livro_reclamacoes.cta.botao2') ?></a>
            </div>
        </div>
    </div>
</section>
</main>

<?php require_once "includes/footer.php"; ?>
