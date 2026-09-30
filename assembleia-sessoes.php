<?php require_once "includes/header.php"; ?>

<?php
$hoje = date('Y-m-d');

$stmtProxima = $pdo->prepare("
    SELECT * FROM assembleia_sessoes
    WHERE ativo = 1 AND estado = 'agendada' AND data_sessao >= ?
    ORDER BY data_sessao ASC, hora_sessao ASC
    LIMIT 1
");
$stmtProxima->execute([$hoje]);
$proxima = $stmtProxima->fetch(PDO::FETCH_ASSOC);

$stmtFuturas = $pdo->prepare("
    SELECT * FROM assembleia_sessoes
    WHERE ativo = 1 AND estado = 'agendada' AND data_sessao >= ?
    ORDER BY data_sessao ASC, hora_sessao ASC
    LIMIT 6
");
$stmtFuturas->execute([$hoje]);
$futuras = $stmtFuturas->fetchAll(PDO::FETCH_ASSOC);

$stmtHistorico = $pdo->prepare("
    SELECT * FROM assembleia_sessoes
    WHERE ativo = 1 AND (estado IN ('realizada','cancelada') OR data_sessao < ?)
    ORDER BY data_sessao DESC, hora_sessao DESC
    LIMIT 12
");
$stmtHistorico->execute([$hoje]);
$historico = $stmtHistorico->fetchAll(PDO::FETCH_ASSOC);

function sessaoEstadoLabel($estado) {
    if ($estado === 'realizada') return 'Realizada';
    if ($estado === 'cancelada') return 'Cancelada';
    return 'Agendada';
}

function sessaoEstadoIcon($estado) {
    if ($estado === 'realizada') return '<i class="bi bi-check-circle-fill"></i>';
    if ($estado === 'cancelada') return '<i class="bi bi-x-circle-fill"></i>';
    return '<i class="bi bi-calendar-event-fill"></i>';
}

function getDocumentosSessao($pdo, $sessaoId) {
    try {
        $stmt = $pdo->prepare("
            SELECT d.*
            FROM assembleia_sessao_documentos sd
            INNER JOIN assembleia_documentos d ON d.id = sd.documento_id
            WHERE sd.sessao_id = ? AND d.ativo = 1
            ORDER BY d.data_documento DESC, d.criado_em DESC
        ");
        $stmt->execute([$sessaoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    } catch(Exception $e) {
        return [];
    }
}
?>

<style>
.sessoes-hero{background:linear-gradient(135deg,#242A30,#11151B 65%,#071c2e);color:white;padding:84px 0 96px;position:relative;overflow:hidden}
.sessoes-hero:after{content:"";position:absolute;width:540px;height:540px;border-radius:50%;right:-220px;top:-250px;background:rgba(255,255,255,.07)}
.sessoes-hero .container{position:relative;z-index:2}
.sess-kicker{display:inline-flex;background:rgba(212,170,0,.16);border:1px solid rgba(212,170,0,.36);color:#F0D060;padding:9px 14px;border-radius:999px;font-weight:900;text-transform:uppercase;letter-spacing:.8px;font-size:12px}
.sessoes-hero h1{font-size:clamp(42px,5vw,70px);margin:18px 0 14px;line-height:1.02}
.sessoes-hero p{max-width:790px;color:#dbeafe;font-size:20px;line-height:1.75;margin:0}
.sess-shell{margin-top:-48px;position:relative;z-index:5}
.sess-next{background:white;border:1px solid #e5e7eb;border-radius:32px;padding:28px;box-shadow:0 24px 70px rgba(15,23,42,.14);display:grid;grid-template-columns:1.1fr .9fr;gap:24px;margin-bottom:24px}
.sess-next h2{margin:0 0 10px;color:#11151B;font-size:34px}
.sess-next p{color:#64748b;line-height:1.65;margin:0 0 18px}
.sess-meta{display:flex;gap:10px;flex-wrap:wrap;margin:15px 0}
.sess-meta span{background:#eef2ff;color:#242A30;border-radius:999px;padding:9px 12px;font-weight:900;font-size:13px}
.countdown-box{background:linear-gradient(135deg,#242A30,#11151B);color:white;border-radius:26px;padding:24px;position:relative;overflow:hidden}
.countdown-box strong{display:block;font-size:18px;margin-bottom:16px}
.countdown-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:10px}
.countdown-grid div{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.16);border-radius:18px;padding:14px 10px;text-align:center}
.countdown-grid b{display:block;font-size:30px}
.countdown-grid small{color:#dbeafe;font-weight:800}
.sess-section-card{background:white;border:1px solid #e5e7eb;border-radius:30px;padding:26px;box-shadow:0 18px 50px rgba(15,23,42,.08);margin-top:24px}
.sess-section-head{display:flex;justify-content:space-between;align-items:center;gap:16px;margin-bottom:18px}
.sess-section-head h2{margin:0;color:#11151B;font-size:30px}
.sess-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
.sess-card{background:linear-gradient(180deg,#fff,#f8fafc);border:1px solid #e5e7eb;border-radius:24px;padding:20px;transition:.22s ease}
.sess-card:hover{transform:translateY(-4px);box-shadow:0 20px 45px rgba(15,23,42,.10)}
.sess-card-top{display:flex;justify-content:space-between;gap:12px;margin-bottom:12px}
.sess-date{width:64px;height:64px;border-radius:20px;background:#242A30;color:white;display:grid;place-items:center;text-align:center;box-shadow:0 14px 28px rgba(36,42,50,.20)}
.sess-date b{display:block;font-size:23px;line-height:1}
.sess-date small{font-size:11px;font-weight:900;text-transform:uppercase}
.sess-status{height:30px;display:inline-flex;align-items:center;padding:0 10px;border-radius:999px;background:#eef2ff;color:#242A30;font-size:12px;font-weight:900}
.sess-card h3{color:#11151B;margin:0 0 10px;font-size:21px;line-height:1.25}
.sess-card p{color:#64748b;line-height:1.6;margin:0 0 15px}
.ordem-box{background:#f8fafc;border:1px solid #eef2f7;border-radius:18px;padding:14px;color:#334155;line-height:1.55;margin-top:14px}
.docs-ligados{display:grid;gap:8px;margin-top:14px}
.docs-ligados a{display:flex;justify-content:space-between;gap:10px;background:#eef2ff;color:#242A30;border-radius:14px;padding:10px 12px;text-decoration:none;font-weight:900;font-size:13px}
.empty-sess{padding:24px;border-radius:22px;border:1px dashed #cbd5e1;background:#f8fafc;color:#64748b;font-weight:800}
@media(max-width:1100px){.sess-next,.sess-grid{grid-template-columns:1fr}}
@media(max-width:700px){.countdown-grid{grid-template-columns:repeat(2,1fr)}}
</style>

<section class="sessoes-hero">
    <div class="container">
        <span class="sess-kicker"><i class="bi bi-bank2"></i> Assembleia Digital</span>
        <h1>Sessões da Assembleia</h1>
        <p>Consulte as próximas sessões da Assembleia de Freguesia, ordem de trabalhos, documentos associados e histórico das reuniões.</p>
    </div>
</section>

<section class="section sess-shell">
    <div class="container">

        <?php if ($proxima): ?>
            <div class="sess-next">
                <div>
                    <span class="sess-kicker">Próxima sessão</span>
                    <h2><?= htmlspecialchars($proxima['titulo']) ?></h2>

                    <?php if (!empty($proxima['descricao'])): ?>
                        <p><?= htmlspecialchars($proxima['descricao']) ?></p>
                    <?php endif; ?>

                    <div class="sess-meta">
                        <span><i class="bi bi-calendar-event-fill"></i> <?= date('d/m/Y', strtotime($proxima['data_sessao'])) ?></span>
                        <span><i class="bi bi-clock-fill"></i> <?= substr($proxima['hora_sessao'], 0, 5) ?></span>
                        <span><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($proxima['local_sessao'] ?: 'Local a definir') ?></span>
                        <span><?= sessaoEstadoIcon($proxima['estado']) ?> <?= sessaoEstadoLabel($proxima['estado']) ?></span>
                    </div>

                    <?php if (!empty($proxima['ordem_trabalhos'])): ?>
                        <div class="ordem-box"><strong>Ordem de trabalhos:</strong><br><?= nl2br(htmlspecialchars($proxima['ordem_trabalhos'])) ?></div>
                    <?php endif; ?>

                    <?php $docsProxima = getDocumentosSessao($pdo, $proxima['id']); ?>
                    <?php if (!empty($docsProxima)): ?>
























                    <?php endif; ?>
                </div>

                <div class="countdown-box">
                    <strong>Contagem decrescente</strong>
                    <div class="countdown-grid" id="sessCountdown" data-date="<?= htmlspecialchars($proxima['data_sessao'] . ' ' . $proxima['hora_sessao']) ?>">
                        <div><b id="cdDias">0</b><small>Dias</small></div>
                        <div><b id="cdHoras">0</b><small>Horas</small></div>
                        <div><b id="cdMinutos">0</b><small>Min.</small></div>
                        <div><b id="cdSegundos">0</b><small>Seg.</small></div>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <div class="sess-next"><div><span class="sess-kicker">Próxima sessão</span><h2>Sem sessões agendadas</h2><p>De momento não existem sessões futuras publicadas.</p></div><div class="countdown-box"><strong>A aguardar publicação</strong></div></div>
        <?php endif; ?>

        <div class="sess-section-card">
            <div class="sess-section-head"><h2>Próximas sessões</h2></div>

            <?php if (!empty($futuras)): ?>
                <div class="sess-grid">
                    <?php foreach ($futuras as $s): ?>


















                        
















                        <article class="sess-card">
                            <div class="sess-card-top">
                                <div class="sess-date"><div><b><?= date('d', strtotime($s['data_sessao'])) ?></b><small><?= date('M', strtotime($s['data_sessao'])) ?></small></div></div>
                                <span class="sess-status"><?= sessaoEstadoIcon($s['estado']) ?> <?= sessaoEstadoLabel($s['estado']) ?></span>
                            </div>
                            <h3><?= htmlspecialchars($s['titulo']) ?></h3>
                            <div class="sess-meta"><span><i class="bi bi-clock-fill"></i> <?= substr($s['hora_sessao'], 0, 5) ?></span><span><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($s['local_sessao'] ?: 'A definir') ?></span></div>
                            <?php if (!empty($s['descricao'])): ?><p><?= htmlspecialchars($s['descricao']) ?></p><?php endif; ?>
                            <?php if (!empty($s['ordem_trabalhos'])): ?><div class="ordem-box"><?= nl2br(htmlspecialchars($s['ordem_trabalhos'])) ?></div><?php endif; ?>
                            <?php if (!empty($docs)): ?><div class="docs-ligados"><?php foreach ($docs as $doc): ?><a href="/uploads/assembleia/<?= htmlspecialchars($doc['ficheiro']) ?>" target="_blank"><span><i class="bi bi-paperclip"></i> <?= htmlspecialchars($doc['titulo']) ?></span><small>Ver</small></a><?php endforeach; ?></div><?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-sess">Ainda não existem próximas sessões publicadas.</div>
            <?php endif; ?>
        </div>

        <div class="sess-section-card">
            <div class="sess-section-head"><h2>Histórico de sessões</h2></div>

            <?php if (!empty($historico)): ?>
                <div class="sess-grid">
                    <?php foreach ($historico as $s): ?>
                        <?php $docs = getDocumentosSessao($pdo, $s['id']); ?>
                        <article class="sess-card">
                            <div class="sess-card-top">
                                <div class="sess-date" style="background:#475569;"><div><b><?= date('d', strtotime($s['data_sessao'])) ?></b><small><?= date('M', strtotime($s['data_sessao'])) ?></small></div></div>
                                <span class="sess-status"><?= sessaoEstadoIcon($s['estado']) ?> <?= sessaoEstadoLabel($s['estado']) ?></span>
                            </div>
                            <h3><?= htmlspecialchars($s['titulo']) ?></h3>
                            <div class="sess-meta"><span><i class="bi bi-clock-fill"></i> <?= substr($s['hora_sessao'], 0, 5) ?></span><span><i class="bi bi-geo-alt-fill"></i> <?= htmlspecialchars($s['local_sessao'] ?: 'A definir') ?></span></div>
                            <?php if (!empty($s['ordem_trabalhos'])): ?><div class="ordem-box"><?= nl2br(htmlspecialchars($s['ordem_trabalhos'])) ?></div><?php endif; ?>
                            <?php if (!empty($docs)): ?><div class="docs-ligados"><?php foreach ($docs as $doc): ?><a href="/uploads/assembleia/<?= htmlspecialchars($doc['ficheiro']) ?>" target="_blank"><span><i class="bi bi-paperclip"></i> <?= htmlspecialchars($doc['titulo']) ?></span><small>Ver</small></a><?php endforeach; ?></div><?php endif; ?>
                        </article>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <div class="empty-sess">Ainda não existem sessões anteriores publicadas.</div>
            <?php endif; ?>
        </div>
    </div>
</section>

<script>
document.addEventListener("DOMContentLoaded", function () {
    const box = document.getElementById("sessCountdown");
    if (!box) return;
    const target = new Date(box.dataset.date.replace(" ", "T")).getTime();

    function updateCountdown() {
        const now = new Date().getTime();
        const diff = Math.max(0, target - now);
        document.getElementById("cdDias").innerText = Math.floor(diff / (1000 * 60 * 60 * 24));
        document.getElementById("cdHoras").innerText = Math.floor((diff / (1000 * 60 * 60)) % 24);
        document.getElementById("cdMinutos").innerText = Math.floor((diff / (1000 * 60)) % 60);
        document.getElementById("cdSegundos").innerText = Math.floor((diff / 1000) % 60);
    }

    updateCountdown();
    setInterval(updateCountdown, 1000);
});
</script>

<?php require_once "includes/footer.php"; ?>
