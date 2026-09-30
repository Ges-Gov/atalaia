<?php require_once "includes/header.php"; ?>

<?php
$configContactos = [
    'hero_kicker' => 'Contactos',
    'hero_titulo' => 'Contactos',
    'hero_subtitulo' => 'Encontre aqui os principais contactos, serviços úteis e informações de atendimento da freguesia.',
    'bloco_titulo' => 'Junta de Freguesia',
    'bloco_texto' => 'Estamos disponíveis para apoiar os cidadãos, responder a pedidos e encaminhar informações úteis.',
    'mapa_embed' => '',
    'horario_titulo' => 'Horário de atendimento',
    'horario_texto' => 'Segunda a sexta-feira, em horário definido pela Junta de Freguesia.'
];

try {
    $stmt = $pdo->query("SELECT * FROM contactos_pagina_config WHERE ativo = 1 ORDER BY id ASC LIMIT 1");
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) $configContactos = array_merge($configContactos, $row);
} catch(Exception $e) {}

$contactos = [];
try {
    $stmt = $pdo->query("SELECT * FROM contactos_uteis WHERE ativo = 1 ORDER BY destaque DESC, ordem ASC, nome ASC");
    $contactos = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch(Exception $e) {
    try { $contactos = $pdo->query("SELECT * FROM contactos_uteis ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC); } catch(Exception $e2) {}
}

$categorias = [];
foreach ($contactos as $c) {
    $cat = trim($c['categoria'] ?? '');
    if ($cat === '') $cat = 'Contactos úteis';
    $categorias[$cat][] = $c;
}
function txt($v){ return nl2br(htmlspecialchars($v ?? '')); }
?>

<style>
.contact-hero{background:radial-gradient(circle at 15% 18%,rgba(212,170,0,.24),transparent 30%),linear-gradient(135deg,#242A30,#11151B 65%,#071c2e);color:white;padding:92px 0 108px;position:relative;overflow:hidden}
.contact-hero::after{content:"";position:absolute;right:-250px;top:-280px;width:620px;height:620px;background:rgba(255,255,255,.07);border-radius:50%}
.contact-hero .container{position:relative;z-index:2}
.contact-kicker{display:inline-flex;background:rgba(212,170,0,.16);border:1px solid rgba(212,170,0,.38);color:#F0D060;padding:9px 14px;border-radius:999px;font-weight:900;text-transform:uppercase;letter-spacing:.8px;font-size:12px}
.contact-hero h1{font-size:clamp(42px,5vw,72px);margin:18px 0 14px;line-height:1.02;max-width:980px}
.contact-hero p{max-width:880px;color:#dbeafe;font-size:20px;line-height:1.75;margin:0}
.contact-shell{margin-top:-52px;position:relative;z-index:5}
.contact-top-grid{display:grid;grid-template-columns:1.1fr .9fr;gap:20px;margin-bottom:24px}
.contact-main-card,.contact-hours,.contact-section,.contact-map{background:white;border:1px solid #e5e7eb;border-radius:30px;padding:28px;box-shadow:0 18px 50px rgba(15,23,42,.08)}
.contact-main-card h2,.contact-hours h2,.contact-section h2,.contact-map h2{margin:0 0 14px;color:#11151B;font-size:30px}
.contact-main-card p,.contact-hours p{color:#64748b;line-height:1.75;margin:0 0 10px}
.contact-lines{display:grid;gap:10px;margin-top:18px}
.contact-line{display:flex;gap:10px;align-items:flex-start;background:#f8fafc;border:1px solid #e5e7eb;border-radius:18px;padding:13px;color:#334155;font-weight:800}
.contact-section{margin-top:24px}.contact-category{margin-top:22px}.contact-category h3{color:#11151B;margin:0 0 14px;font-size:24px}
.contact-grid{display:grid;grid-template-columns:repeat(3,1fr);gap:18px}
.contact-card{background:linear-gradient(180deg,#fff,#f8fafc);border:1px solid #e5e7eb;border-radius:26px;padding:22px;transition:.22s ease}
.contact-card:hover{transform:translateY(-4px);box-shadow:0 22px 50px rgba(15,23,42,.10)}
.contact-card.destaque{border-color:rgba(212,170,0,.55);box-shadow:0 18px 45px rgba(212,170,0,.10)}
.contact-icon{width:62px;height:62px;border-radius:20px;background:linear-gradient(135deg,#242A30,#11151B);color:white;display:grid;place-items:center;font-size:28px;margin-bottom:14px}
.contact-card h4{margin:0 0 8px;color:#11151B;font-size:21px}
.contact-badge{display:inline-flex;background:#eef2ff;color:#242A30;border-radius:999px;padding:6px 9px;font-size:11px;font-weight:900;margin-bottom:12px}
.contact-card p{margin:8px 0;color:#64748b;line-height:1.6;font-weight:700}
.contact-card a{color:#242A30;font-weight:900;text-decoration:none}
.contact-actions{display:flex;flex-wrap:wrap;gap:8px;margin-top:14px}
.contact-action{display:inline-flex;align-items:center;gap:7px;padding:10px 12px;border-radius:14px;background:#eef2ff;color:#242A30;font-size:13px;font-weight:900;text-decoration:none}
.contact-map{margin-top:24px;overflow:hidden}.contact-map iframe{width:100%;height:380px;border:0;border-radius:22px}
@media(max-width:1050px){.contact-top-grid,.contact-grid{grid-template-columns:1fr}}
</style>

<section class="contact-hero">
    <div class="container">
        <span class="contact-kicker"><?= htmlspecialchars($configContactos['hero_kicker']) ?></span>
        <h1><?= htmlspecialchars($configContactos['hero_titulo']) ?></h1>
        <p><?= htmlspecialchars($configContactos['hero_subtitulo']) ?></p>
    </div>
</section>

<section class="section contact-shell">
    <div class="container">
        <div class="contact-top-grid">
            <div class="contact-main-card">
                <h2><?= htmlspecialchars($configContactos['bloco_titulo']) ?></h2>
                <p><?= txt($configContactos['bloco_texto']) ?></p>
                <div class="contact-lines">
                    <?php if (siteConfig('email', '')): ?><div class="contact-line"><i class="bi bi-envelope-fill"></i> <span><?= htmlspecialchars(siteConfig('email', '')) ?></span></div><?php endif; ?>
                    <?php if (siteConfig('telefone', '')): ?><div class="contact-line"><i class="bi bi-telephone-fill"></i> <span><?= htmlspecialchars(siteConfig('telefone', '')) ?></span></div><?php endif; ?>
                    <?php if (siteConfig('morada', '')): ?><div class="contact-line"><i class="bi bi-geo-alt-fill"></i> <span><?= nl2br(htmlspecialchars(siteConfig('morada', ''))) ?></span></div><?php endif; ?>
                </div>
            </div>
            <div class="contact-hours">
                <h2><?= htmlspecialchars($configContactos['horario_titulo']) ?></h2>
                <p><?= txt($configContactos['horario_texto']) ?></p>
            </div>
        </div>

        <div class="contact-section">
            <h2>Contactos úteis</h2>
            <?php foreach($categorias as $categoria => $items): ?>
                <div class="contact-category">
                    <h3><?= htmlspecialchars($categoria) ?></h3>
                    <div class="contact-grid">
                        <?php foreach($items as $c): ?>
                            <article class="contact-card <?= !empty($c['destaque']) ? 'destaque' : '' ?>">
                                <div class="contact-icon"><i class="bi <?= htmlspecialchars(!empty($c['icone']) ? $c['icone'] : 'bi-telephone') ?>"></i></div>
                                <h4><?= htmlspecialchars($c['nome']) ?></h4>
                                <span class="contact-badge"><?= htmlspecialchars($categoria) ?></span>
                                <?php if(!empty($c['descricao'])): ?><p><?= txt($c['descricao']) ?></p><?php endif; ?>
                                <?php if(!empty($c['telefone'])): ?><p><strong>Telefone:</strong> <a href="tel:<?= htmlspecialchars($c['telefone']) ?>"><?= htmlspecialchars($c['telefone']) ?></a></p><?php endif; ?>
                                <?php if(!empty($c['email'])): ?><p><strong>Email:</strong> <a href="mailto:<?= htmlspecialchars($c['email']) ?>"><?= htmlspecialchars($c['email']) ?></a></p><?php endif; ?>
                                <?php if(!empty($c['morada'])): ?><p><strong>Morada:</strong><br><?= txt($c['morada']) ?></p><?php endif; ?>
                                <?php if(!empty($c['horario'])): ?><p><strong>Horário:</strong><br><?= txt($c['horario']) ?></p><?php endif; ?>
                                <div class="contact-actions">
                                    <?php if(!empty($c['telefone'])): ?><a class="contact-action" href="tel:<?= htmlspecialchars($c['telefone']) ?>"><i class="bi bi-telephone-fill"></i> Ligar</a><?php endif; ?>
                                    <?php if(!empty($c['email'])): ?><a class="contact-action" href="mailto:<?= htmlspecialchars($c['email']) ?>"><i class="bi bi-envelope-fill"></i> Email</a><?php endif; ?>
                                </div>
                            </article>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endforeach; ?>
            <?php if(empty($contactos)): ?><p>Ainda não existem contactos úteis publicados.</p><?php endif; ?>
        </div>

        <?php if(!empty($configContactos['mapa_embed'])): ?>
            <div class="contact-map">
                <h2>Localização</h2>
                <?= $configContactos['mapa_embed'] ?>
            </div>
        <?php endif; ?>
    </div>
</section>

<?php require_once "includes/footer.php"; ?>
