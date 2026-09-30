<?php
$adminPageTitle = "Configuração de Ocorrências";
$adminActive = "ocorrencias_config";
require_once "includes/header.php";

if (!isAdminMaster()) {
    die("Acesso reservado ao administrador.");
}

$config = [
    'categorias'  => ['tabela' => 'ocorrencias_categorias', 'titulo' => 'Categorias de Assuntos', 'campos' => ['designacao']],
    'assuntos'    => ['tabela' => 'ocorrencias_assuntos', 'titulo' => 'Assuntos (Subcategorias)', 'campos' => ['categoria_id', 'designacao']],
    'prioridades' => ['tabela' => 'ocorrencias_prioridades', 'titulo' => 'Prioridades', 'campos' => ['slug', 'designacao', 'cor']],
    'estados'     => ['tabela' => 'ocorrencias_estados', 'titulo' => 'Estados', 'campos' => ['slug', 'designacao', 'cor', 'valor_percentual']],
    'acoes'       => ['tabela' => 'ocorrencias_acoes', 'titulo' => 'Ações', 'campos' => ['designacao']],
    'entidades'   => ['tabela' => 'ocorrencias_entidades_externas', 'titulo' => 'Entidades Externas', 'campos' => ['designacao', 'email']],
    'tags'        => ['tabela' => 'ocorrencias_tags', 'titulo' => 'Tags', 'campos' => ['designacao']],
];

$tab = $_GET['tab'] ?? 'categorias';
if (!isset($config[$tab])) {
    $tab = 'categorias';
}
$atual = $config[$tab];
$tabela = $atual['tabela'];

/* Adicionar */
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['adicionar']) && ($_POST['tab'] ?? '') === $tab) {
    $colunas = [];
    $valores = [];

    foreach ($atual['campos'] as $campo) {
        $valor = trim($_POST[$campo] ?? '');
        if ($campo === 'categoria_id') {
            $valor = (int) $valor;
        }
        if ($campo === 'valor_percentual') {
            $valor = (int) $valor;
        }
        $colunas[] = $campo;
        $valores[] = $valor !== '' ? $valor : null;
    }

    $sql = "INSERT INTO $tabela (" . implode(', ', $colunas) . ") VALUES (" . implode(', ', array_fill(0, count($colunas), '?')) . ")";

    try {
        $pdo->prepare($sql)->execute($valores);
    } catch (Exception $e) {
        // designação duplicada ou outro erro de validação — ignora e volta à lista
    }

    header("Location: ocorrencias_config.php?tab=" . $tab);
    exit;
}

/* Ativar/desativar */
if (isset($_GET['toggle'])) {
    $pdo->prepare("UPDATE $tabela SET ativo = 1 - ativo WHERE id = ?")->execute([(int) $_GET['toggle']]);
    header("Location: ocorrencias_config.php?tab=" . $tab);
    exit;
}

/* Eliminar */
if (isset($_GET['eliminar'])) {
    $pdo->prepare("DELETE FROM $tabela WHERE id = ?")->execute([(int) $_GET['eliminar']]);
    header("Location: ocorrencias_config.php?tab=" . $tab);
    exit;
}

/* Listagem */
if ($tab === 'assuntos') {
    $itens = $pdo->query("
        SELECT a.*, c.designacao AS categoria_nome
        FROM ocorrencias_assuntos a
        JOIN ocorrencias_categorias c ON c.id = a.categoria_id
        ORDER BY c.ordem ASC, a.ordem ASC, a.designacao ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
    $categorias = $pdo->query("SELECT id, designacao FROM ocorrencias_categorias WHERE ativo = 1 ORDER BY ordem ASC, designacao ASC")->fetchAll(PDO::FETCH_ASSOC);
} else {
    $itens = $pdo->query("SELECT * FROM $tabela ORDER BY ordem ASC, designacao ASC")->fetchAll(PDO::FETCH_ASSOC);
}
?>

<div class="admin-actions">
    <?php foreach ($config as $key => $c): ?>
        <a class="btn <?= $tab === $key ? '' : 'secondary' ?>" href="ocorrencias_config.php?tab=<?= $key ?>"><?= htmlspecialchars($c['titulo']) ?></a>
    <?php endforeach; ?>
</div>

<div class="content-box">
    <h2><?= htmlspecialchars($atual['titulo']) ?></h2>

    <form method="POST" style="margin-bottom:20px;">
        <input type="hidden" name="adicionar" value="1">
        <input type="hidden" name="tab" value="<?= $tab ?>">

        <?php if ($tab === 'assuntos'): ?>
            <div class="form-grid">
                <select name="categoria_id" required>
                    <option value="">Escolha a categoria</option>
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= (int) $cat['id'] ?>"><?= htmlspecialchars($cat['designacao']) ?></option>
                    <?php endforeach; ?>
                </select>
                <input type="text" name="designacao" placeholder="Designação do assunto" required>
            </div>
        <?php elseif ($tab === 'prioridades' || $tab === 'estados'): ?>
            <div class="form-grid">
                <input type="text" name="slug" placeholder="Código interno (ex.: muito_urgente)" required>
                <input type="text" name="designacao" placeholder="Designação (ex.: Muito Urgente)" required>
            </div>
            <div class="form-grid">
                <input type="color" name="cor" value="#495057">
                <?php if ($tab === 'estados'): ?>
                    <input type="number" name="valor_percentual" placeholder="% da barra de progresso" min="0" max="100">
                <?php endif; ?>
            </div>
        <?php elseif ($tab === 'entidades'): ?>
            <div class="form-grid">
                <input type="text" name="designacao" placeholder="Designação (ex.: Câmara Municipal)" required>
                <input type="email" name="email" placeholder="Email de contacto (opcional)">
            </div>
        <?php else: ?>
            <input type="text" name="designacao" placeholder="Designação" required>
        <?php endif; ?>

        <button class="btn">Adicionar</button>
    </form>

    <div class="table-box">
        <table>
            <tr>
                <?php if ($tab === 'assuntos'): ?><th>Categoria</th><?php endif; ?>
                <th>Designação</th>
                <?php if ($tab === 'prioridades' || $tab === 'estados'): ?><th>Cor</th><?php endif; ?>
                <?php if ($tab === 'estados'): ?><th>%</th><?php endif; ?>
                <?php if ($tab === 'entidades'): ?><th>Email</th><?php endif; ?>
                <th>Estado</th>
                <th>Ações</th>
            </tr>

            <?php foreach ($itens as $item): ?>
                <tr>
                    <?php if ($tab === 'assuntos'): ?><td><?= htmlspecialchars($item['categoria_nome']) ?></td><?php endif; ?>
                    <td><?= htmlspecialchars($item['designacao']) ?></td>
                    <?php if ($tab === 'prioridades' || $tab === 'estados'): ?>
                        <td><span style="display:inline-block;width:18px;height:18px;border-radius:5px;background:<?= htmlspecialchars($item['cor']) ?>;vertical-align:middle;"></span></td>
                    <?php endif; ?>
                    <?php if ($tab === 'estados'): ?><td><?= (int) $item['valor_percentual'] ?>%</td><?php endif; ?>
                    <?php if ($tab === 'entidades'): ?><td><?= htmlspecialchars($item['email'] ?? '-') ?></td><?php endif; ?>
                    <td><?= !empty($item['ativo']) ? 'Ativo' : 'Inativo' ?></td>
                    <td>
                        <a class="btn secondary" href="ocorrencias_config.php?tab=<?= $tab ?>&toggle=<?= (int) $item['id'] ?>">
                            <?= !empty($item['ativo']) ? 'Desativar' : 'Ativar' ?>
                        </a>
                        <a class="btn danger" href="ocorrencias_config.php?tab=<?= $tab ?>&eliminar=<?= (int) $item['id'] ?>" onclick="return confirm('Eliminar definitivamente?')">Eliminar</a>
                    </td>
                </tr>
            <?php endforeach; ?>

            <?php if (empty($itens)): ?>
                <tr><td colspan="6">Ainda não existem registos.</td></tr>
            <?php endif; ?>
        </table>
    </div>
</div>

<?php require_once "includes/footer.php"; ?>
