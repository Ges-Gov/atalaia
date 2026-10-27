<?php
/*
 * Utilizadores do backoffice (equipa interna) e contas de fregueses do Balcão Virtual.
 * Lista em tabela + «Adicionar utilizador» / «Editar» em formulário próprio — o mesmo padrão
 * de Documentos e Notícias (27/10/2026; antes cada linha tinha os formulários todos lá dentro).
 */
$modoNovo = isset($_GET['novo']);
$idEditar = (int)($_GET['editar'] ?? 0);
$adminPageTitle = $modoNovo ? "Adicionar utilizador" : ($idEditar ? "Editar utilizador" : "Utilizadores");
$adminActive = "utilizadores";
require_once "includes/header.php";

requireAdminMaster();

$erro = '';
$sucesso = '';

// Utilizadores OCULTOS: existem e fazem login normalmente, mas não aparecem
// na gestão, não contam nos totais e não podem ser alterados/apagados pela interface.
// Para esconder/mostrar um utilizador, basta acrescentar/retirar o username aqui.
$utilizadoresOcultos = ['admin', 'admin@denuncias'];

// Perfis que se podem escolher (valor guardado em admin_utilizadores.tipo => nome mostrado)
$perfis = [
    'operador' => 'Operador',
    'vogal' => 'Vogal da Assembleia',
    'presidente_assembleia' => 'Presidente da Assembleia',
    'admin_denuncias' => 'Administrador do Canal de Denúncias',
    'admin' => 'Administrador',
];

$idEhOculto = function ($id) use ($pdo, $utilizadoresOcultos) {
    if (!$id) return false;
    $st = $pdo->prepare("SELECT username FROM admin_utilizadores WHERE id = ?");
    $st->execute([(int)$id]);
    $u = $st->fetchColumn();
    return $u !== false && in_array($u, $utilizadoresOcultos, true);
};

$mensagensOk = [
    'criado' => "Utilizador criado com sucesso.",
    'editado' => "Utilizador atualizado com sucesso.",
    'estado' => "Estado do utilizador atualizado.",
    'apagado' => "Utilizador apagado com sucesso.",
    'freguesia_estado' => "Estado do freguês atualizado.",
    'freguesia_apagado' => "Conta de freguês removida.",
];
if (isset($_GET['ok'], $mensagensOk[$_GET['ok']])) {
    $sucesso = $mensagensOk[$_GET['ok']];
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';

    // Proteção: ações sobre um utilizador oculto são recusadas (como se não existisse)
    if (in_array($acao, ['estado', 'editar', 'apagar'], true) && $idEhOculto($_POST['id'] ?? 0)) {
        $acao = '';
        $erro = "Utilizador não encontrado.";
    }

    if ($acao === 'criar') {
        $nome = trim($_POST['nome'] ?? '');
        $username = trim($_POST['username'] ?? '');
        $password = trim($_POST['password'] ?? '');
        $tipo = $_POST['tipo'] ?? 'operador';

        if (!$username || !$password || !isset($perfis[$tipo])) {
            $erro = "Preencha todos os campos obrigatórios.";
        } elseif (in_array($username, $utilizadoresOcultos, true)) {
            $erro = "Esse nome de utilizador não está disponível.";
        } else {
            $stmtCheck = $pdo->prepare("SELECT id FROM admin_utilizadores WHERE username = ?");
            $stmtCheck->execute([$username]);

            if ($stmtCheck->fetch()) {
                $erro = "Já existe um utilizador com esse nome de utilizador.";
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO admin_utilizadores (nome, username, password, tipo, ativo)
                    VALUES (?, ?, ?, ?, 1)
                ");
                $stmt->execute([$nome, $username, md5($password), $tipo]);

                header("Location: utilizadores.php?ok=criado");
                exit;
            }
        }
    }

    if ($acao === 'editar') {
        $id = (int)($_POST['id'] ?? 0);
        $nome = trim($_POST['nome'] ?? '');
        $tipo = $_POST['tipo'] ?? '';
        $novaPassword = trim($_POST['nova_password'] ?? '');
        $propria = $id === (int)$adminId;

        $st = $pdo->prepare("SELECT tipo FROM admin_utilizadores WHERE id = ?");
        $st->execute([$id]);
        $tipoAtual = $st->fetchColumn();

        if ($tipoAtual === false) {
            $erro = "Utilizador não encontrado.";
        } elseif (!$propria && !isset($perfis[$tipo])) {
            $erro = "Escolha um perfil válido.";
        } elseif (!$propria && $tipoAtual === 'admin' && $tipo !== 'admin'
            && (int)$pdo->query("SELECT COUNT(*) FROM admin_utilizadores WHERE tipo = 'admin'")->fetchColumn() <= 1) {
            $erro = "Não pode retirar o perfil de administrador ao último administrador.";
        } else {
            // a própria conta não muda de perfil (para não se trancar fora do backoffice)
            $pdo->prepare("UPDATE admin_utilizadores SET nome = ?, tipo = ? WHERE id = ?")
                ->execute([$nome, $propria ? $tipoAtual : $tipo, $id]);
            if ($novaPassword !== '') {
                $pdo->prepare("UPDATE admin_utilizadores SET password = ? WHERE id = ?")->execute([md5($novaPassword), $id]);
            }
            header("Location: utilizadores.php?ok=editado");
            exit;
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
            header("Location: utilizadores.php?ok=estado");
            exit;
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
                header("Location: utilizadores.php?ok=apagado");
                exit;
            }
        }
    }

    if ($acao === 'freguesia_estado') {
        $fid = (int)($_POST['id'] ?? 0);
        $fativo = (int)($_POST['ativo'] ?? 0);
        $pdo->prepare("UPDATE cidadaos SET ativo = ? WHERE id = ?")->execute([$fativo, $fid]);
        header("Location: utilizadores.php?ok=freguesia_estado&tab=fregueses");
        exit;
    }

    if ($acao === 'freguesia_apagar') {
        $fid = (int)($_POST['id'] ?? 0);
        $pdo->prepare("DELETE FROM cidadaos WHERE id = ?")->execute([$fid]);
        header("Location: utilizadores.php?ok=freguesia_apagado&tab=fregueses");
        exit;
    }
}

/* Utilizador a editar (nunca um oculto) */
$editar = null;
if ($idEditar && !$idEhOculto($idEditar)) {
    $st = $pdo->prepare("SELECT * FROM admin_utilizadores WHERE id = ?");
    $st->execute([$idEditar]);
    $editar = $st->fetch(PDO::FETCH_ASSOC) ?: null;
}
if ($idEditar && !$editar && !$erro) {
    $erro = "Utilizador não encontrado.";
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

// Fregueses (contas criadas no Balcão Virtual)
$fregueses = $pdo->query("SELECT * FROM cidadaos ORDER BY criado_em DESC, nome ASC")->fetchAll(PDO::FETCH_ASSOC);
$totalFregueses = count($fregueses);
$tabFregueses = ($_GET['tab'] ?? '') === 'fregueses';
?>

<style>
.user-type-badge{display:inline-flex;padding:5px 10px;border-radius:999px;font-weight:800;font-size:12px;white-space:nowrap}
.user-type-badge.admin{background:#dbeafe;color:#1d4ed8}
.user-type-badge.operador{background:var(--ouro-fundo, #fef3c7);color:var(--ouro-texto, #92400e)}
.user-type-badge.vogal{background:#ede9fe;color:#5b21b6}
.user-type-badge.presidente_assembleia{background:#ccfbf1;color:#0f766e}
.user-type-badge.admin_denuncias{background:#fee2e2;color:#991b1b}
.user-status{display:inline-flex;padding:5px 10px;border-radius:999px;font-weight:800;font-size:12px}
.user-status.active{background:#dcfce7;color:#166534}
.user-status.inactive{background:#fee2e2;color:#991b1b}
.user-propria{font-size:12px;color:#64748b;font-weight:700;margin-left:6px}
/* botões de ação que são formulários (POST): em linha, sem o estilo de «formulário como cartão» */
.acoes-linha{display:flex;gap:8px;flex-wrap:wrap;align-items:center}
.admin-main .acoes-linha form{display:inline-flex;margin:0;padding:0;background:none;box-shadow:none;border:0;border-radius:0}
.tabs-bar{display:flex;gap:10px;margin:0 0 18px;flex-wrap:wrap}
.tab-btn{border:1px solid #e5e7eb;background:#fff;color:#11151B;font-weight:800;padding:10px 16px;border-radius:12px;cursor:pointer;font-family:inherit;font-size:14px;display:inline-flex;align-items:center;gap:8px}
.tab-btn.active{background:var(--cor-principal);color:#fff;border-color:var(--cor-principal)}
.tab-panel{display:none}
.tab-panel.active{display:block}
.tab-intro{color:#64748b;margin:0 0 14px}
</style>

<?php if ($erro): ?>
    <div class="admin-erro" style="background:#fee2e2;color:#991b1b;border-radius:14px;padding:14px 18px;margin-bottom:18px;font-weight:800;">
        <?= htmlspecialchars($erro) ?>
    </div>
<?php endif; ?>

<?php if ($sucesso): ?>
    <div class="admin-ok" style="background:#dcfce7;color:#166534;border-radius:14px;padding:14px 18px;margin-bottom:18px;font-weight:800;">
        <?= htmlspecialchars($sucesso) ?>
    </div>
<?php endif; ?>

<?php if ($modoNovo): ?>

<form method="POST" autocomplete="off">
    <input type="hidden" name="acao" value="criar">

    <label>Nome</label>
    <input type="text" name="nome" placeholder="Ex.: João Silva" value="<?= htmlspecialchars($_POST['nome'] ?? '') ?>">

    <label>Nome de utilizador <small style="font-weight:400;color:#64748b;">(o que a pessoa escreve para entrar)</small></label>
    <input type="text" name="username" placeholder="Ex.: joao" value="<?= htmlspecialchars($_POST['username'] ?? '') ?>" required autocomplete="off">

    <label>Password inicial</label>
    <input type="password" name="password" required autocomplete="new-password">
    <p style="margin:-6px 0 16px;font-size:13px;color:#64748b;">A pessoa pode mudá-la depois, na sua conta.</p>

    <label>Perfil</label>
    <select name="tipo" required>
        <?php foreach ($perfis as $valor => $rotulo): ?>
            <option value="<?= $valor ?>" <?= ($_POST['tipo'] ?? 'operador') === $valor ? 'selected' : '' ?>><?= htmlspecialchars($rotulo) ?></option>
        <?php endforeach; ?>
    </select>

    <button class="btn">Criar utilizador</button>
    <a class="btn secondary" href="utilizadores.php">Voltar</a>
</form>

<?php elseif ($editar): ?>

<?php $propria = (int)$editar['id'] === (int)$adminId; ?>
<form method="POST" autocomplete="off">
    <input type="hidden" name="acao" value="editar">
    <input type="hidden" name="id" value="<?= (int)$editar['id'] ?>">

    <label>Nome de utilizador</label>
    <input type="text" value="<?= htmlspecialchars($editar['username']) ?>" disabled>
    <p style="margin:-6px 0 16px;font-size:13px;color:#64748b;">Não se altera. Para outro nome de utilizador, crie um utilizador novo.</p>

    <label>Nome</label>
    <input type="text" name="nome" placeholder="Ex.: João Silva" value="<?= htmlspecialchars($_POST['nome'] ?? ($editar['nome'] ?? '')) ?>">

    <label>Perfil</label>
    <?php if ($propria): ?>
        <input type="text" value="<?= htmlspecialchars($perfis[$editar['tipo']] ?? $editar['tipo']) ?>" disabled>
        <p style="margin:-6px 0 16px;font-size:13px;color:#64748b;">Não pode alterar o perfil da sua própria conta.</p>
    <?php else: ?>
        <select name="tipo" required>
            <?php foreach ($perfis as $valor => $rotulo): ?>
                <option value="<?= $valor ?>" <?= ($_POST['tipo'] ?? $editar['tipo']) === $valor ? 'selected' : '' ?>><?= htmlspecialchars($rotulo) ?></option>
            <?php endforeach; ?>
        </select>
    <?php endif; ?>

    <label>Nova password <small style="font-weight:400;color:#64748b;">(deixe vazio para manter a atual)</small></label>
    <input type="password" name="nova_password" autocomplete="new-password">

    <button class="btn">Guardar alterações</button>
    <a class="btn secondary" href="utilizadores.php">Voltar</a>
</form>

<?php else: ?>

<div class="admin-actions">
    <a class="btn" href="utilizadores.php?novo=1">Adicionar utilizador</a>
</div>

<div class="tabs-bar">
    <button type="button" class="tab-btn <?= $tabFregueses ? '' : 'active' ?>" data-tab="equipa"><i class="bi bi-people-fill"></i> Equipa interna (<?= count($utilizadores) ?>)</button>
    <button type="button" class="tab-btn <?= $tabFregueses ? 'active' : '' ?>" data-tab="fregueses"><i class="bi bi-person-badge"></i> Fregueses (<?= (int)$totalFregueses ?>)</button>
</div>

<div id="tab-equipa" class="tab-panel <?= $tabFregueses ? '' : 'active' ?>">
    <p class="tab-intro">Quem entra no backoffice e com que perfil.</p>
    <div class="table-box">
        <table>
            <tr>
                <th>Nome</th>
                <th>Utilizador</th>
                <th>Perfil</th>
                <th>Estado</th>
                <th>Ações</th>
            </tr>

            <?php foreach ($utilizadores as $u): ?>
                <?php $propria = (int)$u['id'] === (int)$adminId; ?>
                <tr>
                    <td><?= htmlspecialchars($u['nome'] ?: '-') ?><?php if ($propria): ?><span class="user-propria">(a sua conta)</span><?php endif; ?></td>
                    <td><?= htmlspecialchars($u['username']) ?></td>
                    <td><span class="user-type-badge <?= htmlspecialchars($u['tipo']) ?>"><?= htmlspecialchars($perfis[$u['tipo']] ?? $u['tipo']) ?></span></td>
                    <td><span class="user-status <?= !empty($u['ativo']) ? 'active' : 'inactive' ?>"><?= !empty($u['ativo']) ? 'Ativo' : 'Inativo' ?></span></td>
                    <td>
                        <div class="acoes-linha">
                            <a class="btn secondary" href="utilizadores.php?editar=<?= (int)$u['id'] ?>">Editar</a>
                            <?php if (!$propria): ?>
                                <form method="POST" data-sem-aviso>
                                    <input type="hidden" name="acao" value="estado">
                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                    <input type="hidden" name="ativo" value="<?= !empty($u['ativo']) ? 0 : 1 ?>">
                                    <button class="btn secondary" type="submit"><?= !empty($u['ativo']) ? 'Desativar' : 'Ativar' ?></button>
                                </form>
                                <form method="POST" data-sem-aviso onsubmit="return confirm('Eliminar definitivamente este utilizador? Esta ação não pode ser revertida.');">
                                    <input type="hidden" name="acao" value="apagar">
                                    <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                                    <button class="btn danger" type="submit">Eliminar</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($utilizadores)): ?>
                <tr><td colspan="5">Ainda não existem utilizadores.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<div id="tab-fregueses" class="tab-panel <?= $tabFregueses ? 'active' : '' ?>">
    <p class="tab-intro">Cidadãos que criaram conta no site para fazer pedidos, marcações e requerimentos online.</p>
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
                    <td><span class="user-status <?= !empty($f['ativo']) ? 'active' : 'inactive' ?>"><?= !empty($f['ativo']) ? 'Ativo' : 'Inativo' ?></span></td>
                    <td>
                        <div class="acoes-linha">
                            <form method="POST" data-sem-aviso>
                                <input type="hidden" name="acao" value="freguesia_estado">
                                <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                                <input type="hidden" name="ativo" value="<?= !empty($f['ativo']) ? 0 : 1 ?>">
                                <button class="btn secondary" type="submit"><?= !empty($f['ativo']) ? 'Desativar' : 'Ativar' ?></button>
                            </form>
                            <form method="POST" data-sem-aviso onsubmit="return confirm('Eliminar a conta deste freguês? Esta ação não pode ser revertida.');">
                                <input type="hidden" name="acao" value="freguesia_apagar">
                                <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
                                <button class="btn danger" type="submit">Eliminar</button>
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

<?php endif; ?>

<?php require_once "includes/footer.php"; ?>
