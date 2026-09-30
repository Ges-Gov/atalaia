<?php require_once "includes/header.php"; ?>

<?php
$faqs = $pdo->query("SELECT * FROM faqs WHERE ativo = 1 ORDER BY ordem ASC, id ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.faq-hero{
    background:
        radial-gradient(circle at 15% 18%, rgba(46,125,50,.26), transparent 30%),
        radial-gradient(circle at 90% 12%, rgba(255,255,255,.12), transparent 30%),
        linear-gradient(135deg,#242A30,#11151B 65%,#071c2e);
    color:white;
    padding:92px 0 108px;
    position:relative;
    overflow:hidden;
}
.faq-hero::after{
    content:"";
    position:absolute;
    right:-250px;
    top:-280px;
    width:620px;
    height:620px;
    background:rgba(255,255,255,.07);
    border-radius:50%;
}
.faq-hero .container{position:relative;z-index:2;}
.faq-kicker{
    display:inline-flex;
    background:rgba(46,125,50,.16);
    border:1px solid rgba(46,125,50,.38);
    color:#81C784;
    padding:9px 14px;
    border-radius:999px;
    font-weight:900;
    text-transform:uppercase;
    letter-spacing:.8px;
    font-size:12px;
}
.faq-hero h1{
    font-size:clamp(42px,5vw,72px);
    margin:18px 0 14px;
    line-height:1.02;
}
.faq-hero p{
    max-width:780px;
    color:#dbeafe;
    font-size:20px;
    line-height:1.75;
    margin:0;
}
.faq-shell{margin-top:-52px;position:relative;z-index:5;}
.faq-list{
    max-width:880px;
    margin:0 auto;
    display:flex;
    flex-direction:column;
    gap:16px;
}
.faq-item{
    background:white;
    border:1px solid #e5e7eb;
    border-radius:24px;
    box-shadow:0 18px 50px rgba(0,0,0,.08);
    overflow:hidden;
}
.faq-question{
    width:100%;
    background:none;
    border:0;
    text-align:left;
    padding:22px 26px;
    display:flex;
    align-items:center;
    justify-content:space-between;
    gap:16px;
    cursor:pointer;
    font-weight:900;
    font-size:18px;
    color:#11151B;
}
.faq-toggle{
    flex-shrink:0;
    width:34px;
    height:34px;
    border-radius:50%;
    background:#eef2ff;
    color:#242A30;
    display:grid;
    place-items:center;
    font-size:20px;
    font-weight:900;
    transition:.25s ease;
}
.faq-item.open .faq-toggle{
    background:var(--cor-principal);
    color:white;
    transform:rotate(180deg);
}
.faq-answer{
    max-height:0;
    overflow:hidden;
    transition:max-height .3s ease;
}
.faq-answer-inner{
    padding:0 26px 24px;
    color:#475569;
    line-height:1.85;
    font-size:16px;
}
.faq-empty{
    max-width:880px;
    margin:0 auto;
    background:white;
    border-radius:24px;
    padding:30px;
    text-align:center;
    color:#64748b;
    font-weight:800;
}
</style>

<section class="faq-hero">
    <div class="container">
        <span class="faq-kicker">Ajuda</span>
        <h1>Perguntas Frequentes</h1>
        <p>Respostas rápidas às dúvidas mais comuns sobre os serviços da Junta de Freguesia.</p>
    </div>
</section>

<section class="section faq-shell">
    <div class="container">

        <?php if (!empty($faqs)): ?>
            <div class="faq-list">
                <?php foreach ($faqs as $f): ?>
                    <div class="faq-item">
                        <button type="button" class="faq-question">
                            <span><?= htmlspecialchars($f['pergunta']) ?></span>
                            <span class="faq-toggle">+</span>
                        </button>
                        <div class="faq-answer">
                            <div class="faq-answer-inner"><?= nl2br(htmlspecialchars($f['resposta'])) ?></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="faq-empty">Ainda não existem perguntas frequentes publicadas.</div>
        <?php endif; ?>

    </div>
</section>

<script>
document.querySelectorAll('.faq-question').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var item = btn.closest('.faq-item');
        var answer = item.querySelector('.faq-answer');
        var toggle = item.querySelector('.faq-toggle');
        var isOpen = item.classList.contains('open');

        if (isOpen) {
            item.classList.remove('open');
            answer.style.maxHeight = null;
            toggle.textContent = '+';
        } else {
            item.classList.add('open');
            answer.style.maxHeight = answer.scrollHeight + 'px';
            toggle.textContent = '−';
        }
    });
});
</script>

<?php require_once "includes/footer.php"; ?>
