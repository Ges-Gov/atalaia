<?php
/**
 * Memória da Freguesia (CORE) — blocos de história, lendas, fé, tradições e
 * passado/presente/futuro mostrados na página A Freguesia. Tabela freguesia_memoria
 * (migração 044). Cada bloco deve ter fonte (de onde foi retirada a informação).
 */
$adminPageTitle = "Memória da Freguesia";
$adminActive = "memoria";
require_once "includes/header.php";
require_once __DIR__ . "/../includes/galeria.php";   // guardarImagemSimples()

if (!podeAceder('freguesia')) {
    echo '<p>Sem permissão para gerir esta secção.</p>';
    require_once "includes/footer.php";
    exit;
}

$secoes = [
    'arquivo'  => nomeFreguesia() . ' de antigamente (fotos e documentos antigos)',
    'lenda'    => 'Lendas e memórias',
    'fe'       => 'Fé e devoção',
    'tradicao' => 'Tradições, festas e feiras',
    'tempo'    => 'Passado, presente e futuro',
];
$erro = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'apagar') {
    $pdo->prepare("DELETE FROM freguesia_memoria WHERE id = ?")->execute([(int)($_POST['id'] ?? 0)]);
    header("Location: memoria-freguesia.php?ok=apagado");
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['acao'] ?? '') === 'gravar') {
    $id = (int)($_POST['id'] ?? 0);
    $c = [];
    foreach (['secao', 'titulo', 'subtitulo', 'texto', 'icone', 'imagem_legenda', 'imagem_credito', 'fonte_nome', 'fonte_url'] as $k) {
        $c[$k] = trim((string)($_POST[$k] ?? ''));
    }
    $c['ordem'] = (int)($_POST['ordem'] ?? 0);
    $c['ativo'] = isset($_POST['ativo']) ? 1 : 0;
    $atual = '';
    if ($id) {
        $st = $pdo->prepare("SELECT imagem FROM freguesia_memoria WHERE id = ?");
        $st->execute([$id]);
        $atual = (string)$st->fetchColumn();
    }
    if (!isset($secoes[$c['secao']]) || $c['titulo'] === '' || $c['texto'] === '') {
        $erro = "Escolha a secção e preencha o título e o texto.";
    } elseif ($c['fonte_url'] !== '' && !filter_var($c['fonte_url'], FILTER_VALIDATE_URL)) {
        $erro = "O endereço da fonte não é válido (ex.: https://www.exemplo.pt/pagina).";
    } else {
        // imagens iniciais vivem em subpastas (ex.: memoria/) e não são apagadas ao trocar
        [$imagem, $erroImg] = guardarImagemSimples($_FILES['imagem'] ?? [], 'memoria', strpos($atual, '/') === false ? $atual : '');
        if ($imagem === '' && empty($_FILES['imagem']['name'])) $imagem = $atual;
        if (isset($_POST['remover_imagem'])) $imagem = '';
        if ($erroImg !== '') {
            $erro = $erroImg;
        } else {
            $c['imagem'] = $imagem ?: null;
            foreach ($c as $k => $v) if ($v === '' && !in_array($k, ['titulo', 'texto', 'secao'], true)) $c[$k] = null;
            $cols = array_keys($c);
            if ($id) {
                $pdo->prepare("UPDATE freguesia_memoria SET " . implode(' = ?, ', $cols) . " = ? WHERE id = ?")->execute([...array_values($c), $id]);
            } else {
                $pdo->prepare("INSERT INTO freguesia_memoria (" . implode(', ', $cols) . ") VALUES (" . rtrim(str_repeat('?, ', count($cols)), ', ') . ")")->execute(array_values($c));
            }
            header("Location: memoria-freguesia.php?ok=gravado");
            exit;
        }
    }
}

try {
    $blocos = $pdo->query("SELECT * FROM freguesia_memoria ORDER BY FIELD(secao, 'arquivo', 'lenda', 'fe', 'tradicao', 'tempo'), ordem, id")->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    echo '<p>A tabela ainda não existe neste site. Corra as migrações: <code>php migrations/run.php</code></p>';
    require_once "includes/footer.php";
    exit;
}
$editar = null;
foreach ($blocos as $b) if ((int)$b['id'] === (int)($_GET['editar'] ?? 0)) $editar = $b;
$mostrarForm = $editar || isset($_GET['novo']) || $erro;
$f = $editar ?: array_merge(array_fill_keys(['id', 'secao', 'titulo', 'subtitulo', 'texto', 'icone', 'imagem', 'imagem_legenda', 'imagem_credito', 'fonte_nome', 'fonte_url', 'ordem'], ''), ['ativo' => 1], $_POST);
?>

<p style="color:#64748b;max-width:780px;">
    Conteúdo histórico e cultural mostrado na página <a href="/freguesia.php" target="_blank">A Freguesia</a>:
    fotografias antigas, lendas, igrejas e devoções, tradições ao longo do ano e passado/presente/futuro.
    <strong>Indique sempre a fonte</strong> (livro, site oficial, arquivo) e, nas imagens, o autor ou proprietário.
    Nas lendas sem base histórica, diga-o no texto.
</p>

<?php if (isset($_GET['ok'])): ?><div class="alerta-sucesso"><?= $_GET['ok'] === 'apagado' ? 'Bloco apagado.' : 'Bloco guardado.' ?></div><?php endif; ?>
<?php if ($erro): ?><div class="alerta-erro"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

<?php if ($mostrarForm): ?>
    <form method="POST" enctype="multipart/form-data" style="margin-bottom:28px;">
        <input type="hidden" name="acao" value="gravar">
        <input type="hidden" name="id" value="<?= (int)$f['id'] ?>">
        <h2><?= $f['id'] ? 'Editar bloco' : 'Novo bloco' ?></h2>

        <label for="mSecao">Secção *</label>
        <select id="mSecao" name="secao" required>
            <?php foreach ($secoes as $k => $v): ?><option value="<?= $k ?>" <?= $f['secao'] === $k ? 'selected' : '' ?>><?= $v ?></option><?php endforeach; ?>
        </select>

        <label for="mTitulo">Título *</label>
        <input id="mTitulo" type="text" name="titulo" required maxlength="200" value="<?= htmlspecialchars((string)$f['titulo']) ?>">

        <label for="mSub">Etiqueta / data <small>(ex.: "Séc. XV", "Segunda-feira de Páscoa", "Passado")</small></label>
        <input id="mSub" type="text" name="subtitulo" maxlength="120" value="<?= htmlspecialchars((string)$f['subtitulo']) ?>">

        <label for="mTexto">Texto *</label>
        <textarea id="mTexto" name="texto" required rows="6"><?= htmlspecialchars((string)$f['texto']) ?></textarea>

        <label for="mIcone">Ícone <small>(nome Bootstrap Icons, ex.: bi-bank, bi-flower3 — <a href="https://icons.getbootstrap.com/" target="_blank" rel="noopener">ver lista</a>)</small></label>
        <input id="mIcone" type="text" name="icone" maxlength="60" value="<?= htmlspecialchars((string)$f['icone']) ?>">

        <label for="mImagem">Imagem <?= !empty($f['imagem']) ? '(deixe vazio para manter a atual)' : '(opcional)' ?></label>
        <?php if (!empty($f['imagem'])): ?>
            <p><img src="/assets/img/<?= htmlspecialchars($f['imagem']) ?>" alt="" style="max-height:110px;border-radius:8px;">
               <label style="display:inline-flex;gap:6px;align-items:center;margin-left:10px;"><input type="checkbox" name="remover_imagem" value="1" style="width:auto;margin:0;"> Remover imagem</label></p>
        <?php endif; ?>
        <input id="mImagem" type="file" name="imagem" accept="image/jpeg,image/png,image/webp">

        <label for="mLegenda">Legenda da imagem</label>
        <input id="mLegenda" type="text" name="imagem_legenda" maxlength="255" value="<?= htmlspecialchars((string)$f['imagem_legenda']) ?>">

        <label for="mCredito">Crédito da imagem <small>(autor / arquivo / licença)</small></label>
        <input id="mCredito" type="text" name="imagem_credito" maxlength="255" value="<?= htmlspecialchars((string)$f['imagem_credito']) ?>">

        <label for="mFonte">Fonte da informação <small>(várias separadas por ";")</small></label>
        <input id="mFonte" type="text" name="fonte_nome" maxlength="255" value="<?= htmlspecialchars((string)$f['fonte_nome']) ?>">

        <label for="mFonteUrl">Endereço da fonte <small>(da primeira, se houver várias)</small></label>
        <input id="mFonteUrl" type="url" name="fonte_url" maxlength="500" value="<?= htmlspecialchars((string)$f['fonte_url']) ?>" placeholder="https://">

        <label for="mOrdem">Ordem dentro da secção</label>
        <input id="mOrdem" type="number" name="ordem" value="<?= (int)$f['ordem'] ?>">

        <label style="display:flex;gap:10px;align-items:center;margin:8px 0 18px;">
            <input type="checkbox" name="ativo" value="1" <?= !empty($f['ativo']) ? 'checked' : '' ?> style="width:auto;margin:0;"> Visível no site
        </label>

        <button class="btn">Guardar</button>
        <a class="btn secondary" href="memoria-freguesia.php">Cancelar</a>
    </form>
<?php else: ?>
    <div class="admin-actions"><a class="btn" href="memoria-freguesia.php?novo=1">Adicionar bloco</a></div>
<?php endif; ?>

<div class="table-box">
    <table>
        <tr><th>Secção</th><th>Título</th><th>Fonte</th><th>Estado</th><th>Ações</th></tr>
        <?php foreach ($blocos as $b): ?>
            <tr>
                <td><?= htmlspecialchars(explode(' (', $secoes[$b['secao']] ?? $b['secao'])[0]) ?></td>
                <td>
                    <?php if ($b['imagem']): ?><img src="/assets/img/<?= htmlspecialchars($b['imagem']) ?>" alt="" style="height:34px;width:48px;object-fit:cover;border-radius:6px;vertical-align:middle;margin-right:8px;"><?php endif; ?>
                    <strong><?= htmlspecialchars($b['titulo']) ?></strong>
                    <?= $b['subtitulo'] ? '<br><small>' . htmlspecialchars($b['subtitulo']) . '</small>' : '' ?>
                </td>
                <td style="font-size:13px;"><?= $b['fonte_nome'] ? htmlspecialchars($b['fonte_nome']) : '<span style="color:#b91c1c;">sem fonte</span>' ?></td>
                <td><?= $b['ativo'] ? 'Visível' : 'Oculto' ?></td>
                <td>
                    <a class="btn secondary" href="memoria-freguesia.php?editar=<?= (int)$b['id'] ?>">Editar</a>
                    <form method="POST" style="display:inline;margin:0;padding:0;background:none;box-shadow:none;" onsubmit="return confirm('Apagar este bloco?')">
                        <input type="hidden" name="acao" value="apagar"><input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
                        <button class="btn danger" type="submit">Apagar</button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$blocos): ?><tr><td colspan="5">Ainda não há blocos.</td></tr><?php endif; ?>
    </table>
</div>

<?php require_once "includes/footer.php"; ?>
