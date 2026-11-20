<?php
/*
 * Documentos da Freguesia e da Assembleia: o mesmo formulário, a mesma verificação dos ficheiros, os mesmos filtros e a
 * mesma paginação (20/11/2026, pedido da direção: «o formulário de documentos da Assembleia e da Freguesia deve ser
 * semelhante, para simplificar para quem o usa»). Cada área só acrescenta os seus campos próprios (prazo de afixação na
 * Freguesia; ano, palavras-chave e destaque na Assembleia). CORE.
 */

/** Categorias para escolher: as habituais da área + as que já existem nos documentos (+ a atual), sem repetidas. */
function docCategorias(PDO $pdo, string $tabela, array $padrao, string $atual = ''): array
{
    $lista = $padrao;
    try {
        foreach ($pdo->query("SELECT DISTINCT categoria FROM `$tabela` WHERE categoria IS NOT NULL AND categoria <> '' ORDER BY categoria ASC")->fetchAll(PDO::FETCH_COLUMN) as $c) {
            if (!in_array($c, $lista, true)) $lista[] = $c;
        }
    } catch (Throwable $e) {}
    if ($atual !== '' && !in_array($atual, $lista, true)) $lista[] = $atual;
    return $lista;
}

/** Formatos aceites: 'pdf' (só PDF) ou 'varios' (PDF, Word, Excel, ZIP e imagens). */
function docFormatos(string $aceitar): array
{
    return $aceitar === 'pdf' ? ['pdf'] : ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'zip', 'jpg', 'jpeg', 'png'];
}

function docFormatosTexto(string $aceitar): string
{
    return $aceitar === 'pdf' ? 'PDF' : 'PDF, Word, Excel, ZIP ou imagem (JPG, PNG)';
}

/**
 * Verifica o ficheiro enviado: extensão da lista E conteúdo a condizer (um «.pdf» tem de ser mesmo um PDF, etc.).
 * Devolve [extensão, ''] se estiver bem, ou ['', 'frase a dizer o que fazer'].
 */
function docVerificarFicheiro(array $f, string $aceitar, int $maxMb = 20): array
{
    $erro = (int)($f['error'] ?? UPLOAD_ERR_NO_FILE);
    if ($erro === UPLOAD_ERR_INI_SIZE || $erro === UPLOAD_ERR_FORM_SIZE) return ['', "O ficheiro é grande demais para o servidor. Reduza-o (ex.: PDF «comprimido») e volte a enviar."];
    if ($erro !== UPLOAD_ERR_OK) return ['', "O ficheiro não chegou ao servidor. Volte a escolhê-lo e a guardar."];
    if ((int)($f['size'] ?? 0) > $maxMb * 1024 * 1024) return ['', "O ficheiro tem mais de {$maxMb} MB. Reduza-o e volte a enviar."];
    $ext = strtolower(pathinfo((string)($f['name'] ?? ''), PATHINFO_EXTENSION));
    if (!in_array($ext, docFormatos($aceitar), true)) {
        return ['', $aceitar === 'pdf' ? "Escolha um ficheiro PDF. Se o documento estiver em Word, guarde-o como PDF e volte a enviar."
                                        : "Este tipo de ficheiro não é aceite. Use " . docFormatosTexto($aceitar) . "."];
    }
    $inicio = (string)@file_get_contents((string)$f['tmp_name'], false, null, 0, 8);
    $ok = match ($ext) {
        'pdf' => strncmp($inicio, '%PDF-', 5) === 0,
        'docx', 'xlsx', 'zip' => strncmp($inicio, "PK", 2) === 0,
        'doc', 'xls' => strncmp($inicio, "\xD0\xCF\x11\xE0", 4) === 0,
        'jpg', 'jpeg', 'png' => (bool)@getimagesize((string)$f['tmp_name']),
        default => false,
    };
    if (!$ok) return ['', "O ficheiro não é um " . strtoupper($ext) . " válido (pode estar danificado ou ter a extensão errada)."];
    return [$ext, ''];
}

/**
 * Campos do formulário, iguais nas duas áreas. $o:
 *   valor       callable(campo, omissão) → valor atual (do POST, se houve erro, ou do documento)
 *   categorias  lista de categorias (docCategorias)
 *   aceitar     'pdf' | 'varios'
 *   ficheiro    URL do ficheiro atual ('' num documento novo)
 *   extras      ['prazo' => bool, 'ano' => bool, 'tags' => bool, 'destaque' => bool]
 *   ativo, destaque  bool (caixas marcadas)
 */
function docCampos(array $o): void
{
    $v = $o['valor']; $x = $o['extras'] ?? []; $novo = ($o['ficheiro'] ?? '') === '';
    $h = fn($t) => htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8');
    $accept = implode(',', array_map(fn($e) => '.' . $e, docFormatos($o['aceitar'])));
    ?>
    <p class="doc-ajuda doc-obrig">Os campos com * são obrigatórios.</p>

    <label for="doc-titulo">Título *</label>
    <input type="text" id="doc-titulo" name="titulo" maxlength="255" value="<?= $h($v('titulo')) ?>" required>

    <label for="doc-descricao">Descrição (opcional)</label>
    <textarea id="doc-descricao" name="descricao" rows="3"><?= $h($v('descricao')) ?></textarea>

    <label for="doc-categoria">Categoria *</label>
    <select id="doc-categoria" name="categoria" required>
        <option value="">Escolha a categoria</option>
        <?php foreach ($o['categorias'] as $cat): ?>
            <option value="<?= $h($cat) ?>" <?= (string)$v('categoria') === $cat ? 'selected' : '' ?>><?= $h($cat) ?></option>
        <?php endforeach; ?>
    </select>

    <label for="doc-data">Data do documento (opcional)</label>
    <input type="date" id="doc-data" name="data_documento" value="<?= $h($v('data_documento')) ?>">

    <?php if (!empty($x['ano'])): ?>
        <label for="doc-ano">Ano (opcional)</label>
        <input type="number" id="doc-ano" name="ano" min="1900" max="2100" value="<?= $h($v('ano', $novo ? date('Y') : '')) ?>" aria-describedby="doc-ano-ajuda">
        <p class="doc-ajuda" id="doc-ano-ajuda">Se ficar vazio, usa o ano da data do documento.</p>
    <?php endif; ?>

    <?php if (!empty($x['prazo'])): ?>
        <label for="doc-prazo">Prazo de afixação ou de consulta pública (opcional)</label>
        <input type="date" id="doc-prazo" name="data_limite" value="<?= $h($v('data_limite')) ?>" aria-describedby="doc-prazo-ajuda">
        <p class="doc-ajuda" id="doc-prazo-ajuda">Para editais e consultas públicas: no dia seguinte a esta data o documento sai do site sozinho (fica inativo e pode voltar a ativá-lo). Sem data, fica sempre.</p>
    <?php endif; ?>

    <?php if (!empty($x['tags'])): ?>
        <label for="doc-tags">Palavras-chave (opcional)</label>
        <input type="text" id="doc-tags" name="tags" maxlength="255" value="<?= $h($v('tags')) ?>" aria-describedby="doc-tags-ajuda">
        <p class="doc-ajuda" id="doc-tags-ajuda">Ajudam a encontrar o documento na pesquisa. Separe-as por vírgulas, por exemplo: orçamento, reunião, edital.</p>
    <?php endif; ?>

    <label for="doc-ficheiro">Ficheiro<?= $novo ? ' *' : ' (só se quiser substituir o atual)' ?></label>
    <input type="file" id="doc-ficheiro" name="ficheiro" accept="<?= $h($accept) ?>" <?= $novo ? 'required' : '' ?> aria-describedby="doc-ficheiro-ajuda">
    <p class="doc-ajuda" id="doc-ficheiro-ajuda">Formatos aceites: <?= $h(docFormatosTexto($o['aceitar'])) ?>. Até 20 MB.
        <?php if (!$novo): ?>Ficheiro atual: <a href="<?= $h($o['ficheiro']) ?>" target="_blank" rel="noopener">abrir</a>.<?php endif; ?></p>

    <?php if (!empty($x['destaque'])): ?>
        <label class="doc-caixa"><input type="checkbox" name="destaque" value="1" <?= !empty($o['destaque']) ? 'checked' : '' ?>> Documento em destaque</label>
    <?php endif; ?>
    <label class="doc-caixa"><input type="checkbox" name="ativo" value="1" <?= !empty($o['ativo']) ? 'checked' : '' ?>> Documento visível no site</label>
    <?php
}

/** Barra de pesquisa e filtros por cima da lista (igual nas duas áreas). $o: url, pesquisa, categoria, ano, estado, categorias, anos, extra (HTML) */
function docFiltros(array $o): void
{
    $h = fn($t) => htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8');
    $filtrado = $o['pesquisa'] !== '' || $o['categoria'] !== '' || $o['ano'] !== '' || $o['estado'] !== '' || !empty($o['filtrado_extra']);
    ?>
    <form method="GET" class="doc-filtros" action="<?= $h($o['url']) ?>" data-sem-aviso>
        <input type="search" name="pesquisa" aria-label="Pesquisar documentos" placeholder="Pesquisar documentos" value="<?= $h($o['pesquisa']) ?>">
        <select name="categoria" aria-label="Categoria">
            <option value="">Categoria</option>
            <?php foreach ($o['categorias'] as $cat): ?><option value="<?= $h($cat) ?>" <?= $o['categoria'] === $cat ? 'selected' : '' ?>><?= $h($cat) ?></option><?php endforeach; ?>
        </select>
        <select name="ano" aria-label="Ano">
            <option value="">Ano</option>
            <?php foreach ($o['anos'] as $a): ?><option value="<?= $h($a) ?>" <?= (string)$o['ano'] === (string)$a ? 'selected' : '' ?>><?= $h($a) ?></option><?php endforeach; ?>
        </select>
        <select name="estado" aria-label="Estado">
            <option value="">Estado</option>
            <option value="ativo" <?= $o['estado'] === 'ativo' ? 'selected' : '' ?>>Só visíveis</option>
            <option value="inativo" <?= $o['estado'] === 'inativo' ? 'selected' : '' ?>>Só ocultos</option>
        </select>
        <?= $o['extra'] ?? '' ?>
        <button class="btn secondary" type="submit">Filtrar</button>
        <?php if ($filtrado): ?><a class="btn secondary" href="<?= $h($o['url']) ?>">Limpar</a><?php endif; ?>
    </form>
    <?php
}

/** Paginação (igual nas duas áreas). $url: callable(n.º da página) → endereço. */
function docPaginacao(int $atual, int $total, callable $url): void
{
    if ($total <= 1) return;
    echo '<nav class="doc-paginacao" aria-label="Páginas">';
    if ($atual > 1) echo '<a href="' . htmlspecialchars($url($atual - 1)) . '" aria-label="Página anterior">‹</a>';
    for ($i = 1; $i <= $total; $i++) {
        if ($i === 1 || $i === $total || abs($i - $atual) <= 2) {
            echo '<a class="' . ($i === $atual ? 'active' : '') . '" href="' . htmlspecialchars($url($i)) . '"' . ($i === $atual ? ' aria-current="page"' : '') . '>' . $i . '</a>';
        } elseif ($i === 2 || $i === $total - 1) {
            echo '<span>…</span>';
        }
    }
    if ($atual < $total) echo '<a href="' . htmlspecialchars($url($atual + 1)) . '" aria-label="Página seguinte">›</a>';
    echo '</nav>';
}

/** Botões «Ocultar/Mostrar» e «Eliminar» de uma linha da lista (POST com token, em vez de ligações). */
function docAcoesLinha(string $acaoUrl, int $id, bool $ativo, string $token): string
{
    $f = fn(string $acao, string $rotulo, string $classe, string $confirmar = '') =>
        '<form method="POST" action="' . htmlspecialchars($acaoUrl) . '" class="doc-acao" data-sem-aviso' . ($confirmar ? ' onsubmit="return confirm(\'' . $confirmar . '\')"' : '') . '>'
        . '<input type="hidden" name="doc_acao" value="' . $acao . '"><input type="hidden" name="id" value="' . $id . '"><input type="hidden" name="token" value="' . htmlspecialchars($token) . '">'
        . '<button class="btn ' . $classe . '" type="submit">' . $rotulo . '</button></form>';
    return $f('estado', $ativo ? 'Ocultar' : 'Mostrar', 'secondary') . $f('apagar', 'Eliminar', 'danger', 'Eliminar este documento? O ficheiro é apagado e não pode ser recuperado.');
}

function docToken(): string
{
    if (empty($_SESSION['doc_token'])) $_SESSION['doc_token'] = bin2hex(random_bytes(16));
    return $_SESSION['doc_token'];
}

function docEstilos(): void
{ ?>
<style>
/* Documentos (Freguesia e Assembleia), 20/11/2026 */
.doc-ajuda{margin:-6px 0 16px;font-size:13px;color:#64748b}
.doc-obrig{margin:0 0 14px}
.admin-main label.doc-caixa{display:flex;gap:10px;align-items:center;margin:0 0 12px;font-weight:600}
.admin-main label.doc-caixa input{width:auto;min-height:0;margin:0}
.doc-filtros{display:grid;grid-template-columns:minmax(220px,2fr) repeat(3,minmax(150px,1fr)) auto auto auto;gap:10px;margin:0 0 18px;align-items:center}
.admin-main .doc-filtros input:not([type=checkbox]),.admin-main .doc-filtros select{width:100%;height:46px;margin:0;box-sizing:border-box;min-width:0}
.admin-main .doc-filtros select{padding-right:36px;text-overflow:ellipsis}
.admin-main .doc-filtros label{display:inline-flex;align-items:center;gap:8px;margin:0;padding:0;line-height:1;font-weight:600;white-space:nowrap;cursor:pointer}
.admin-main .doc-filtros label input[type=checkbox]{width:18px;height:18px;margin:0;flex:0 0 auto}
.admin-main .doc-filtros .btn{height:46px;margin:0}
@media(max-width:1200px){.doc-filtros{grid-template-columns:1fr 1fr 1fr}.doc-filtros input[name=pesquisa]{grid-column:1/-1}}
@media(max-width:700px){.doc-filtros{grid-template-columns:1fr}}
.doc-paginacao{display:flex;gap:8px;flex-wrap:wrap;justify-content:center;margin-top:20px}
.doc-paginacao a,.doc-paginacao span{min-width:40px;height:40px;display:grid;place-items:center;border-radius:12px;border:1px solid #e5e7eb;text-decoration:none;font-weight:800;background:#fff}
.doc-paginacao a.active{background:var(--cor-principal);border-color:var(--cor-principal);color:#fff}
.doc-destaque{background:var(--cor-principal);color:#fff;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;white-space:nowrap}
.doc-oculto{background:#f1f5f9;color:#475569;padding:2px 8px;border-radius:999px;font-size:11px;font-weight:700;white-space:nowrap}
.doc-contagem{color:#64748b;margin:-6px 0 12px;font-size:14px}
.doc-linha-acoes{display:flex;gap:6px;flex-wrap:wrap;align-items:center}
.admin-main form.doc-acao{display:inline-flex;margin:0;padding:0;background:none;border:0;box-shadow:none;border-radius:0}
</style>
<?php }
