<?php require_once "auth.php"; ?>
<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8"><title><?= htmlspecialchars($pageTitle ?? 'Canal de Denúncias') ?></title><meta name="viewport" content="width=device-width, initial-scale=1.0"><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">

<style>
*{box-sizing:border-box}
body{margin:0;background:#f8fafc;color:#11151B;font-family:Arial,sans-serif}
.den-layout{display:grid;grid-template-columns:280px 1fr;min-height:100vh}
.den-sidebar{background:linear-gradient(180deg,#242A30,#11151B);color:white;padding:24px}
.den-logo{display:flex;gap:12px;align-items:center;margin-bottom:28px}.den-logo span{width:52px;height:52px;border-radius:18px;background:#D4AA00;color:#11151B;display:grid;place-items:center;font-size:24px}.den-logo strong{display:block;font-size:18px}.den-logo small{color:#dbeafe}
.den-menu{display:grid;gap:8px}.den-menu a{color:#dbeafe;text-decoration:none;padding:13px 14px;border-radius:15px;font-weight:900}.den-menu a.active,.den-menu a:hover{background:rgba(255,255,255,.12);color:white}
.den-logout{display:block;margin-top:26px;color:#fca5a5;text-decoration:none;font-weight:900}
.den-main{padding:28px}.den-topbar{background:white;border:1px solid #e5e7eb;border-radius:24px;padding:20px 22px;margin-bottom:24px;display:flex;justify-content:space-between;gap:18px;align-items:center;box-shadow:0 12px 30px rgba(15,23,42,.06)}
.den-topbar h1{margin:0;font-size:30px}.den-topbar p{margin:6px 0 0;color:#64748b;font-weight:800}.user-pill{background:#eef2ff;color:#242A30;border-radius:999px;padding:10px 13px;font-weight:900}
.btn{display:inline-flex;align-items:center;justify-content:center;gap:7px;background:#242A30;color:white!important;text-decoration:none;border:0;border-radius:14px;padding:11px 14px;font-weight:900;cursor:pointer}.btn.secondary{background:#eef2ff;color:#242A30!important}.btn.danger{background:#fee2e2;color:#991b1b!important}
.badge{display:inline-flex;background:#eef2ff;color:#242A30;border-radius:999px;padding:6px 9px;font-size:12px;font-weight:900;margin:3px}.badge.alta{background:#fee2e2;color:#991b1b}
@media(max-width:900px){.den-layout{grid-template-columns:1fr}.den-topbar{display:block}}
</style>

</head><body><div class="den-layout"><aside class="den-sidebar"><div class="den-logo"><span><i class="bi bi-exclamation-triangle"></i></span><div><strong>Denúncias</strong><small>Backoffice exclusivo</small></div></div><nav class="den-menu"><a class="<?= ($active ?? '') === 'dashboard' ? 'active' : '' ?>" href="index.php"><i class="bi bi-bar-chart"></i> Dashboard</a><a class="<?= ($active ?? '') === 'criar' ? 'active' : '' ?>" href="criar-utilizador.php"><i class="bi bi-person"></i> Utilizadores</a></nav><a class="den-logout" href="logout.php">Sair</a></aside><main class="den-main"><header class="den-topbar"><div><h1><?= htmlspecialchars($pageTitle ?? 'Canal de Denúncias') ?></h1><p>Área independente sem acesso ao backoffice geral.</p></div><span class="user-pill"><?= htmlspecialchars($denunciasUserNome) ?></span></header>