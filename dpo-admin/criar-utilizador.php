<?php
$pageTitle = "Gestão de Utilizadores DPO";
$active = "criar";
require_once "auth.php";

$erro = "";
$ok = "";
$userAtualId = (int)($_SESSION["dpo_user_id"] ?? 0);

if ($_SERVER["REQUEST_METHOD"] === "POST") {
    $acao = $_POST["acao"] ?? "";

    if ($acao === "criar") {
        $nome = trim($_POST["nome"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $password = trim($_POST["password"] ?? "");

        if (!$nome || !$email || !$password) {
            $erro = "Preencha todos os campos.";
        } else {
            try {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $stmt = $pdo->prepare("INSERT INTO dpo_utilizadores (nome, email, password, ativo) VALUES (?, ?, ?, 1)");
                $stmt->execute([$nome, $email, $hash]);
                $ok = "Utilizador criado com sucesso.";
            } catch(Exception $e) {
                $erro = "Erro ao criar utilizador. Verifique se o email já existe.";
            }
        }
    }

    if ($acao === "password") {
        $id = (int)($_POST["id"] ?? 0);
        $novaPassword = trim($_POST["nova_password"] ?? "");

        if (!$id || !$novaPassword) {
            $erro = "Indique a nova password.";
        } else {
            $hash = password_hash($novaPassword, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE dpo_utilizadores SET password = ? WHERE id = ?");
            $stmt->execute([$hash, $id]);
            $ok = "Password atualizada com sucesso.";
        }
    }

    if ($acao === "estado") {
        $id = (int)($_POST["id"] ?? 0);
        $ativo = (int)($_POST["ativo"] ?? 0);

        if ($id === $userAtualId) {
            $erro = "Não pode desativar a sua própria conta.";
        } else {
            $stmt = $pdo->prepare("UPDATE dpo_utilizadores SET ativo = ? WHERE id = ?");
            $stmt->execute([$ativo, $id]);
            $ok = "Estado do utilizador atualizado.";
        }
    }

    if ($acao === "eliminar") {
        $id = (int)($_POST["id"] ?? 0);

        if ($id === $userAtualId) {
            $erro = "Não pode eliminar a sua própria conta.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM dpo_utilizadores WHERE id = ?");
            $stmt->execute([$id]);
            $ok = "Utilizador eliminado com sucesso.";
        }
    }
}

$users = $pdo->query("SELECT id, nome, email, ativo, criado_em FROM dpo_utilizadores ORDER BY ativo DESC, nome ASC")->fetchAll(PDO::FETCH_ASSOC);

$total = count($users);
$ativos = 0;
$inativos = 0;

foreach ($users as $u) {
    if (!empty($u["ativo"])) $ativos++;
    else $inativos++;
}

require_once "layout-top.php";
?>

<style>
.users-hero{background:linear-gradient(135deg,#242A30,#11151B);color:white;border-radius:28px;padding:28px;display:flex;justify-content:space-between;gap:20px;align-items:center;margin-bottom:24px;box-shadow:0 20px 45px rgba(0,0,0,.14)}
.users-hero span{color:#D4AA00;text-transform:uppercase;font-weight:900;letter-spacing:.8px;font-size:13px}
.users-hero h2{margin:8px 0;font-size:34px}
.users-hero p{margin:0;color:#dbeafe}
.users-kpis{display:flex;gap:12px}
.users-kpis div{background:rgba(255,255,255,.13);border:1px solid rgba(255,255,255,.18);border-radius:18px;padding:16px;min-width:105px;text-align:center}
.users-kpis strong{display:block;font-size:28px}
.users-grid{display:grid;grid-template-columns:380px 1fr;gap:24px;align-items:start}
.card{background:white;border:1px solid #e5e7eb;border-radius:24px;padding:24px;box-shadow:0 16px 40px rgba(0,0,0,.08)}
.card h2{margin-top:0;color:#11151B}.card p{color:#64748b;line-height:1.6}
.form{display:grid;gap:12px}.form label{font-weight:900;color:#11151B}
.form input{width:100%;height:48px;border:1px solid #dbe4ee;background:#f8fafc;border-radius:14px;padding:0 12px;font-weight:800}
.alert{padding:14px 16px;border-radius:16px;margin-bottom:16px;font-weight:900}
.alert.ok{background:#dcfce7;color:#166534}.alert.err{background:#fee2e2;color:#991b1b}
.user-row{background:#f8fafc;border:1px solid #e5e7eb;border-radius:18px;padding:16px;margin-bottom:12px;display:grid;grid-template-columns:1fr auto;gap:16px;align-items:center}
.user-row h3{margin:0 0 6px;color:#11151B}.user-row p{margin:0;color:#64748b;font-weight:800}
.status{display:inline-flex;margin-top:8px;padding:6px 10px;border-radius:999px;font-size:12px;font-weight:900}
.status.active{background:#dcfce7;color:#166534}.status.inactive{background:#fee2e2;color:#991b1b}
.actions{display:flex;gap:8px;flex-wrap:wrap;align-items:center;justify-content:flex-end}
.actions form{display:inline-flex;gap:8px;margin:0;align-items:center}
.actions input{height:40px;border:1px solid #dbe4ee;border-radius:12px;padding:0 10px;font-weight:800}
@media(max-width:1000px){.users-grid{grid-template-columns:1fr}.users-hero{display:block}.users-kpis{margin-top:18px;flex-direction:column}.user-row{grid-template-columns:1fr}.actions{justify-content:flex-start}}
</style>

<div class="users-hero">
    <div>
        <span>Acessos reservados</span>
        <h2>Utilizadores do DPO / Proteção de Dados</h2>
        <p>Crie utilizadores, altere passwords, ative, desative ou remova acessos ao painel exclusivo do DPO.</p>
    </div>

    <div class="users-kpis">
        <div><strong><?= (int)$total ?></strong><small>Total</small></div>
        <div><strong><?= (int)$ativos ?></strong><small>Ativos</small></div>
        <div><strong><?= (int)$inativos ?></strong><small>Inativos</small></div>
    </div>
</div>

<?php if($ok): ?><div class="alert ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
<?php if($erro): ?><div class="alert err"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

<div class="users-grid">
    <div class="card">
        <h2>Novo utilizador</h2>
        <p>Crie uma conta para acesso exclusivo ao backoffice do DPO / Proteção de Dados.</p>

        <form method="POST" class="form">
            <input type="hidden" name="acao" value="criar">
            <label>Nome</label>
            <input type="text" name="nome" required>
            <label>Email</label>
            <input type="email" name="email" required>
            <label>Password</label>
            <input type="password" name="password" required>
            <button class="btn" type="submit">Criar utilizador</button>
        </form>
    </div>

    <div class="card">
        <h2>Utilizadores existentes</h2>
        <p>Gerir acessos ao painel exclusivo do DPO / Proteção de Dados.</p>

        <?php foreach($users as $u): ?>
            <div class="user-row">
                <div>
                    <h3><?= htmlspecialchars($u["nome"]) ?></h3>
                    <p><?= htmlspecialchars($u["email"]) ?></p>
                    <span class="status <?= !empty($u["ativo"]) ? "active" : "inactive" ?>">
                        <?= !empty($u["ativo"]) ? "Ativo" : "Inativo" ?>
                    </span>

                    <?php if((int)$u["id"] === $userAtualId): ?>
                        <span class="badge">A sua conta</span>
                    <?php endif; ?>
                </div>

                <div class="actions">
                    <form method="POST">
                        <input type="hidden" name="acao" value="password">
                        <input type="hidden" name="id" value="<?= (int)$u["id"] ?>">
                        <input type="password" name="nova_password" placeholder="Nova password" required>
                        <button class="btn" type="submit">Password</button>
                    </form>

                    <?php if((int)$u["id"] !== $userAtualId): ?>
                        <form method="POST">
                            <input type="hidden" name="acao" value="estado">
                            <input type="hidden" name="id" value="<?= (int)$u["id"] ?>">
                            <input type="hidden" name="ativo" value="<?= !empty($u["ativo"]) ? 0 : 1 ?>">
                            <button class="btn secondary" type="submit">
                                <?= !empty($u["ativo"]) ? "Desativar" : "Ativar" ?>
                            </button>
                        </form>

                        <form method="POST" onsubmit="return confirm('Eliminar este utilizador?')">
                            <input type="hidden" name="acao" value="eliminar">
                            <input type="hidden" name="id" value="<?= (int)$u["id"] ?>">
                            <button class="btn danger" type="submit">Eliminar</button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        <?php endforeach; ?>

        <?php if(empty($users)): ?>
            <p>Ainda não existem utilizadores.</p>
        <?php endif; ?>
    </div>
</div>

<?php require_once "layout-bottom.php"; ?>
