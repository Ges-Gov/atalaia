<?php
$adminPageTitle = "Economia Local";
$adminActive = "comercio";
require_once "includes/header.php";
require_once __DIR__ . "/includes/textos_bloco.php";
require_once __DIR__ . "/../includes/comercio_candidaturas.php";

// 06/11/2026 (Atalaia): modo de candidaturas — só a GesGov o liga/desliga
if (empty($_SESSION['comercio_modo_token'])) $_SESSION['comercio_modo_token'] = bin2hex(random_bytes(16));
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['modo_candidaturas']) && isGesGov()
    && hash_equals($_SESSION['comercio_modo_token'], (string)($_POST['token_modo'] ?? ''))) {
    $pdo->prepare("INSERT INTO tema_config (chave, valor, descricao) VALUES ('comercio_candidaturas', ?, 'Economia Local em modo de candidaturas (1) ou com a lista de estabelecimentos (0)')
                   ON DUPLICATE KEY UPDATE valor = VALUES(valor)")->execute([$_POST['modo_candidaturas'] === '1' ? '1' : '0']);
    header("Location: comercio.php");
    exit;
}
$modoCand = comercioEmCandidaturas();
$candNovas = null;
try { $candNovas = (int)$pdo->query("SELECT COUNT(*) FROM comercio_candidaturas WHERE estado = 'nova'")->fetchColumn(); } catch (Throwable $e) {}
?>
<?php if ($modoCand): ?>
    <div class="content-box" style="border-left:4px solid var(--cor-principal)">
        <h3 style="margin-top:0"><i class="bi bi-inbox"></i> Página em modo de candidaturas</h3>
        <p style="margin:0 0 10px;color:#475569">A página <a href="/comercio.php" target="_blank">Economia Local</a> mostra só a apresentação e o formulário de pedido de divulgação.
            Os estabelecimentos abaixo ficam guardados, mas não aparecem no site (nem no mapa, na pesquisa ou nos contactos).</p>
        <a class="btn" href="comercio-candidaturas.php">Ver as candidaturas<?= $candNovas ? " ($candNovas nova" . ($candNovas > 1 ? 's' : '') . ")" : '' ?></a>
    </div>
<?php endif; ?>
<?php if (isGesGov()): ?>
    <form method="post" class="content-box" style="display:flex;gap:12px;align-items:center;flex-wrap:wrap">
        <input type="hidden" name="token_modo" value="<?= htmlspecialchars($_SESSION['comercio_modo_token']) ?>">
        <strong>GesGov — modo de candidaturas:</strong> <?= $modoCand ? 'ligado' : 'desligado' ?>
        <button class="btn secondary" name="modo_candidaturas" value="<?= $modoCand ? '0' : '1' ?>"
                onclick="return confirm('<?= $modoCand ? 'Desligar o modo de candidaturas? A lista de estabelecimentos volta a aparecer no site.' : 'Ligar o modo de candidaturas? Os estabelecimentos deixam de aparecer no site.' ?>')">
            <?= $modoCand ? 'Desligar (voltar a mostrar os estabelecimentos)' : 'Ligar' ?></button>
    </form>
<?php endif; ?>
<?php
if ($modoCand) {
    textosBloco("Textos da página em modo de candidaturas", ['comercio.hero.kicker', 'comercio.cand.titulo', 'comercio.cand.intro', 'comercio.cand.chamada', 'comercio.cand.texto', 'comercio.cand.fecho'], '/comercio.php');
} else {
    textosBloco("Texto do topo da página «Economia Local»", ['comercio.hero.kicker','comercio.hero.titulo','comercio.hero.texto'], '/comercio.php');
}

$comercio = $pdo->query("SELECT * FROM comercio_local ORDER BY nome ASC")->fetchAll(PDO::FETCH_ASSOC);
?>

<style>
.admin-comercio-thumb{
    width:76px;
    height:56px;
    object-fit:cover;
    border-radius:14px;
    box-shadow:0 8px 18px rgba(0,0,0,.12);
}
.admin-comercio-noimg{
    width:76px;
    height:56px;
    border-radius:14px;
    background:linear-gradient(135deg,#242A30,#11151B);
    color:var(--ouro, #C8A02E);
    display:grid;
    place-items:center;
    font-weight:900;
    font-size:22px;
}
</style>

<div class="admin-actions">
    <a class="btn" href="adicionar_comercio.php">Adicionar comércio</a>
</div>

<div class="table-box">
    <table>
        <tr>
            <th>Imagem</th>
            <th>Nome</th>
            <th>Tipo</th>
            <th>Telefone</th>
            <th>Ações</th>
        </tr>

        <?php foreach ($comercio as $c): ?>
            <tr>
                <td>
                    <?php if (!empty($c['imagem'])): ?>
                        <img class="admin-comercio-thumb" src="../assets/img/<?= htmlspecialchars($c['imagem']) ?>" alt="<?= htmlspecialchars($c['nome']) ?>">
                    <?php else: ?>
                        <div class="admin-comercio-noimg"><i class="bi bi-shop"></i></div>
                    <?php endif; ?>
                </td>
                <td><?= htmlspecialchars($c['nome']) ?></td>
                <td><?= htmlspecialchars($c['tipo']) ?></td>
                <td><?= htmlspecialchars($c['telefone'] ?? '') ?></td>
                <td>
                    <a class="btn secondary" href="editar_comercio.php?id=<?= $c['id'] ?>">Editar</a>
                    <a class="btn danger" href="eliminar_comercio.php?id=<?= $c['id'] ?>" onclick="return confirm('Eliminar este comércio?')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>
    </table>
</div>

<?php require_once "includes/footer.php"; ?>
