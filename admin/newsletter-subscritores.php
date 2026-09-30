<?php
$adminPageTitle = "Subscritores da Newsletter";
$adminActive = "newsletter_subscritores";
require_once "includes/header.php";

$erro = '';
$mensagem = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar'])) {
    $email = trim($_POST['email'] ?? '');
    $nome = trim($_POST['nome'] ?? '');

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $erro = "Email inválido.";
    } else {
        try {
            $pdo->prepare("INSERT INTO newsletter_subscribers (email, nome, origem) VALUES (?, ?, 'manual')")
                ->execute([$email, $nome ?: null]);
            $mensagem = "Subscritor adicionado.";
        } catch (PDOException $e) {
            $erro = "Já existe um subscritor com este email.";
        }
    }
}

if (isset($_GET['toggle'])) {
    $pdo->prepare("UPDATE newsletter_subscribers SET ativo = IF(ativo=1,0,1) WHERE id = ?")->execute([(int) $_GET['toggle']]);
    header("Location: newsletter-subscritores.php");
    exit;
}

if (isset($_GET['apagar'])) {
    $pdo->prepare("DELETE FROM newsletter_subscribers WHERE id = ?")->execute([(int) $_GET['apagar']]);
    header("Location: newsletter-subscritores.php");
    exit;
}

$subscritores = $pdo->query("SELECT * FROM newsletter_subscribers ORDER BY criado_em DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="news-grid-sub" style="display:grid;grid-template-columns:minmax(280px,.6fr) minmax(0,1.4fr);gap:20px;align-items:start;">

    <div class="content-box">
        <h3>Adicionar subscritor</h3>
        <?php if ($mensagem): ?><div class="news-alert-sub ok"><?= htmlspecialchars($mensagem) ?></div><?php endif; ?>
        <?php if ($erro): ?><div class="news-alert-sub err"><?= htmlspecialchars($erro) ?></div><?php endif; ?>
        <form method="POST">
            <label>Nome (opcional)</label>
            <input type="text" name="nome">
            <label>Email *</label>
            <input type="email" name="email" required>
            <button class="btn" type="submit" name="adicionar" value="1">Adicionar</button>
        </form>
    </div>

    <div class="table-box">
        <table>
            <tr>
                <th>Email</th>
                <th>Nome</th>
                <th>Origem</th>
                <th>Estado</th>
                <th>Desde</th>
                <th>Ações</th>
            </tr>
            <?php foreach ($subscritores as $s): ?>
                <tr>
                    <td><?= htmlspecialchars($s['email']) ?></td>
                    <td><?= htmlspecialchars($s['nome'] ?: '-') ?></td>
                    <td><?= $s['origem'] === 'site' ? 'Site' : 'Manual' ?></td>
                    <td>
                        <span class="estado-badge <?= $s['ativo'] ? 'estado-resolvido' : 'estado-arquivado' ?>">
                            <?= $s['ativo'] ? 'Ativo' : 'Inativo' ?>
                        </span>
                    </td>
                    <td><?= date('d/m/Y', strtotime($s['criado_em'])) ?></td>
                    <td>
                        <a class="btn secondary" href="?toggle=<?= (int) $s['id'] ?>"><?= $s['ativo'] ? 'Desativar' : 'Ativar' ?></a>
                        <a class="btn danger" href="?apagar=<?= (int) $s['id'] ?>" onclick="return confirm('Apagar este subscritor?')">Apagar</a>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($subscritores)): ?>
                <tr><td colspan="6">Ainda não há subscritores.</td></tr>
            <?php endif; ?>
        </table>
    </div>

</div>

<style>
.news-alert-sub{padding:12px 14px;border-radius:12px;margin-bottom:14px;font-weight:800;font-size:13px;}
.news-alert-sub.ok{background:#dcfce7;color:#166534;}
.news-alert-sub.err{background:#fee2e2;color:#991b1b;}
@media(max-width:1000px){.news-grid-sub{grid-template-columns:1fr!important;}}
</style>

<?php require_once "includes/footer.php"; ?>
