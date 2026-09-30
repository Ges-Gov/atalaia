<?php
$adminPageTitle = "Perfis";
$adminActive = "perfis";

require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/db.php";

// Só a GesGov cria perfis e os associa a utilizadores. Um administrador da junta
// que tente abrir esta página diretamente pelo URL é barrado aqui.
requireGesGov();

/** As secções do backoffice que um perfil pode permitir (= grupos da sidebar). */
$seccoes = [
    'freguesia'  => 'A Freguesia (executivo, notícias, eventos, pontos, documentos, galeria…)',
    'home'       => 'Homepage (slides e destaques)',
    'virtual'    => 'Junta Virtual (pedidos, marcações, requerimentos, RH, contratação…)',
    'assembleia' => 'Assembleia de Freguesia',
    'denuncias'  => 'Canal de Denúncias',
    'sistema'    => 'Sistema (utilizadores, configurações, separadores de fundo)',
];

$erro = '';
$sucesso = '';
$editar = null;

// Eliminar perfil (os utilizadores que o tinham ficam sem perfil, voltando ao
// comportamento antigo baseado no `tipo` — não perdem o acesso todo por engano).
if (isset($_GET['eliminar'])) {
    $id = (int)$_GET['eliminar'];
    $pdo->prepare("UPDATE admin_utilizadores SET perfil_id = NULL WHERE perfil_id = ?")->execute([$id]);
    $pdo->prepare("DELETE FROM perfis WHERE id = ?")->execute([$id]);

    header("Location: perfis.php?ok=eliminado");
    exit;
}

// Criar / atualizar perfil
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['guardar_perfil'])) {
    $id         = (int)($_POST['id'] ?? 0);
    $nome       = trim($_POST['nome'] ?? '');
    $descricao  = trim($_POST['descricao'] ?? '');
    $ativo      = isset($_POST['ativo']) ? 1 : 0;

    // Só aceita secções conhecidas (não confia no que vem do formulário).
    $escolhidas = array_values(array_intersect(
        array_keys($seccoes),
        (array)($_POST['permissoes'] ?? [])
    ));
    $permissoes = implode(',', $escolhidas);

    if ($nome === '') {
        $erro = "Dê um nome ao perfil.";
    } else {
        try {
            if ($id > 0) {
                $pdo->prepare("UPDATE perfis SET nome = ?, descricao = ?, permissoes = ?, ativo = ? WHERE id = ?")
                    ->execute([$nome, $descricao, $permissoes, $ativo, $id]);
                $sucesso = "Perfil atualizado.";
            } else {
                $pdo->prepare("INSERT INTO perfis (nome, descricao, permissoes, ativo) VALUES (?, ?, ?, ?)")
                    ->execute([$nome, $descricao, $permissoes, $ativo]);
                $sucesso = "Perfil criado.";
            }
        } catch (PDOException $e) {
            $erro = "Já existe um perfil com esse nome.";
        }
    }
}

// Associar perfil a um utilizador
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['associar'])) {
    $userId   = (int)($_POST['utilizador_id'] ?? 0);
    $perfilId = (int)($_POST['perfil_id'] ?? 0);

    $pdo->prepare("UPDATE admin_utilizadores SET perfil_id = ? WHERE id = ?")
        ->execute([$perfilId > 0 ? $perfilId : null, $userId]);

    $sucesso = "Perfil associado ao utilizador.";
}

if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM perfis WHERE id = ?");
    $stmt->execute([(int)$_GET['editar']]);
    $editar = $stmt->fetch(PDO::FETCH_ASSOC);
}

$perfis = $pdo->query("
    SELECT p.*, COUNT(u.id) AS n_utilizadores
    FROM perfis p
    LEFT JOIN admin_utilizadores u ON u.perfil_id = p.id
    GROUP BY p.id
    ORDER BY p.nome ASC
")->fetchAll(PDO::FETCH_ASSOC);

// Os utilizadores ocultos da GesGov não entram na lista de associação.
$ocultos = utilizadoresGesGov();
$marcas  = implode(',', array_fill(0, count($ocultos), '?'));

$stmt = $pdo->prepare("
    SELECT u.id, u.username, u.tipo, u.perfil_id, p.nome AS perfil_nome
    FROM admin_utilizadores u
    LEFT JOIN perfis p ON p.id = u.perfil_id
    WHERE u.username NOT IN ($marcas)
    ORDER BY u.username ASC
");
$stmt->execute($ocultos);
$utilizadores = $stmt->fetchAll(PDO::FETCH_ASSOC);

$permsEditar = !empty($editar['permissoes'])
    ? array_map('trim', explode(',', $editar['permissoes']))
    : [];

require_once __DIR__ . "/includes/header.php";
?>

<div style="background:#eff6ff;color:#1e40af;border-radius:14px;padding:14px 18px;margin-bottom:18px;font-weight:800;">
    <i class="bi bi-shield-lock"></i>
    Área reservada à GesGov. Os administradores da junta não veem este separador.
</div>

<?php if (!empty($_GET['ok']) || $sucesso !== ''): ?>
    <div style="background:#dcfce7;color:#166534;border-radius:14px;padding:14px 18px;margin-bottom:18px;font-weight:800;">
        <?= $sucesso !== '' ? htmlspecialchars($sucesso) : 'Perfil eliminado.' ?>
    </div>
<?php endif; ?>

<?php if ($erro !== ''): ?>
    <div style="background:#fee2e2;color:#991b1b;border-radius:14px;padding:14px 18px;margin-bottom:18px;font-weight:800;">
        <?= htmlspecialchars($erro) ?>
    </div>
<?php endif; ?>

<div class="table-box" style="margin-bottom:22px;">
    <h2 style="margin-top:0;"><?= $editar ? 'Editar perfil' : 'Novo perfil' ?></h2>

    <form method="POST">
        <input type="hidden" name="id" value="<?= (int)($editar['id'] ?? 0) ?>">

        <label>Nome do perfil *</label>
        <input type="text" name="nome" value="<?= htmlspecialchars($editar['nome'] ?? '') ?>"
               placeholder="Ex.: Secretaria" required>

        <label>Descrição</label>
        <input type="text" name="descricao" value="<?= htmlspecialchars($editar['descricao'] ?? '') ?>"
               placeholder="Para que serve este perfil">

        <label>Secções que este perfil pode gerir</label>
        <div style="display:grid;gap:10px;margin-bottom:16px;">
            <?php foreach ($seccoes as $chave => $rotulo): ?>
                <label style="display:flex;gap:10px;align-items:flex-start;background:#f8fafc;border:1px solid #e5e7eb;padding:12px 14px;border-radius:14px;font-weight:700;">
                    <input type="checkbox" name="permissoes[]" value="<?= $chave ?>"
                           <?= in_array($chave, $permsEditar, true) ? 'checked' : '' ?>
                           style="width:auto;margin:3px 0 0;">
                    <span><?= htmlspecialchars($rotulo) ?></span>
                </label>
            <?php endforeach; ?>
        </div>

        <label style="display:flex;gap:10px;align-items:center;margin-bottom:18px;">
            <input type="checkbox" name="ativo" value="1" <?= (!$editar || $editar['ativo']) ? 'checked' : '' ?> style="width:auto;margin:0;">
            Perfil ativo
        </label>

        <button class="btn" name="guardar_perfil" value="1"><?= $editar ? 'Guardar alterações' : 'Criar perfil' ?></button>
        <?php if ($editar): ?>
            <a class="btn secondary" href="perfis.php">Cancelar</a>
        <?php endif; ?>
    </form>
</div>

<div class="table-box" style="margin-bottom:22px;">
    <h2 style="margin-top:0;">Perfis</h2>

    <table>
        <tr>
            <th>Perfil</th>
            <th>Secções</th>
            <th>Utilizadores</th>
            <th>Estado</th>
            <th>Ações</th>
        </tr>

        <?php foreach ($perfis as $p): ?>
            <tr>
                <td>
                    <strong><?= htmlspecialchars($p['nome']) ?></strong>
                    <?php if (!empty($p['descricao'])): ?>
                        <br><small style="color:#64748b;"><?= htmlspecialchars($p['descricao']) ?></small>
                    <?php endif; ?>
                </td>
                <td>
                    <?php
                    $lista = array_filter(array_map('trim', explode(',', (string)$p['permissoes'])));
                    echo $lista ? htmlspecialchars(implode(', ', $lista)) : '<span style="color:#94a3b8;">nenhuma</span>';
                    ?>
                </td>
                <td><?= (int)$p['n_utilizadores'] ?></td>
                <td><?= $p['ativo'] ? 'Ativo' : 'Inativo' ?></td>
                <td>
                    <a class="btn secondary" href="perfis.php?editar=<?= (int)$p['id'] ?>">Editar</a>
                    <a class="btn danger" href="perfis.php?eliminar=<?= (int)$p['id'] ?>"
                       onclick="return confirm('Eliminar este perfil? Os utilizadores que o tinham ficam sem perfil.')">Eliminar</a>
                </td>
            </tr>
        <?php endforeach; ?>

        <?php if (empty($perfis)): ?>
            <tr><td colspan="5">Ainda não existem perfis.</td></tr>
        <?php endif; ?>
    </table>
</div>

<div class="table-box">
    <h2 style="margin-top:0;">Associar perfil a utilizador</h2>

    <table>
        <tr>
            <th>Utilizador</th>
            <th>Tipo</th>
            <th>Perfil atual</th>
            <th>Associar</th>
        </tr>

        <?php foreach ($utilizadores as $u): ?>
            <tr>
                <td><strong><?= htmlspecialchars($u['username']) ?></strong></td>
                <td><?= htmlspecialchars($u['tipo']) ?></td>
                <td>
                    <?= !empty($u['perfil_nome'])
                        ? htmlspecialchars($u['perfil_nome'])
                        : '<span style="color:#94a3b8;">sem perfil (usa o tipo)</span>' ?>
                </td>
                <td>
                    <form method="POST" style="display:flex;gap:8px;align-items:center;margin:0;">
                        <input type="hidden" name="utilizador_id" value="<?= (int)$u['id'] ?>">

                        <select name="perfil_id" style="min-width:170px;">
                            <option value="0">— sem perfil —</option>
                            <?php foreach ($perfis as $p): ?>
                                <option value="<?= (int)$p['id'] ?>" <?= ((int)$u['perfil_id'] === (int)$p['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($p['nome']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>

                        <button class="btn" name="associar" value="1">Associar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>

        <?php if (empty($utilizadores)): ?>
            <tr><td colspan="4">Não há utilizadores para associar.</td></tr>
        <?php endif; ?>
    </table>
</div>

<?php require_once __DIR__ . "/includes/footer.php"; ?>
