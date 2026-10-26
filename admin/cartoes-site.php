<?php
/**
 * Cartões e atalhos da página inicial (12/10/2026): acesso rápido, serviços, «Perto de quem
 * precisa», «Descobrir» e destaques do Bem-vindo. Tabela cartoes_site (migração 058).
 * Cada grupo mostra só os campos que usa (cartoesGrupos()). As imagens antigas NÃO se apagam ao
 * trocar (algumas são partilhadas com outras partes do site).
 */
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/../includes/db.php';
require_once __DIR__ . '/../includes/galeria.php';   // guardarImagemSimples()
require_once __DIR__ . '/../includes/cartoes.php';

if (!podeAceder('home')) die('Sem permissão para editar a página inicial.');

$grupos = cartoesGrupos();
$grupo = isset($grupos[$_GET['grupo'] ?? '']) ? $_GET['grupo'] : array_key_first($grupos);
$def = $grupos[$grupo];
$erro = '';
$voltar = function (string $msg) use ($grupo): void {
    $_SESSION['aviso_admin'] = $msg;
    header('Location: cartoes-site.php?grupo=' . urlencode($grupo));
    exit;
};

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $acao = $_POST['acao'] ?? '';
    $id = (int)($_POST['id'] ?? 0);
    if ($acao === 'apagar' && $id) {
        $pdo->prepare("DELETE FROM cartoes_site WHERE id = ? AND grupo = ?")->execute([$id, $grupo]);
        $voltar('Cartão eliminado.');
    }
    if ($acao === 'estado' && $id) {
        $pdo->prepare("UPDATE cartoes_site SET ativo = 1 - ativo WHERE id = ? AND grupo = ?")->execute([$id, $grupo]);
        $voltar('Estado do cartão alterado.');
    }
    if (($acao === 'subir' || $acao === 'descer') && $id) {
        $lista = $pdo->prepare("SELECT id FROM cartoes_site WHERE grupo = ? ORDER BY ordem, id");
        $lista->execute([$grupo]);
        $ids = array_map('intval', $lista->fetchAll(PDO::FETCH_COLUMN));
        $i = array_search($id, $ids, true);
        $j = $acao === 'subir' ? $i - 1 : $i + 1;
        if ($i !== false && isset($ids[$j])) { [$ids[$i], $ids[$j]] = [$ids[$j], $ids[$i]]; }
        $up = $pdo->prepare("UPDATE cartoes_site SET ordem = ? WHERE id = ?");
        foreach ($ids as $n => $cid) $up->execute([$n + 1, $cid]);
        $voltar('Ordem atualizada.');
    }
    if ($acao === 'guardar') {
        $titulo = trim($_POST['titulo'] ?? '');
        $link = trim($_POST['link'] ?? '');
        $atual = $id ? $pdo->query("SELECT imagem FROM cartoes_site WHERE id = " . $id)->fetchColumn() : '';
        [$imagem, $erroImg] = in_array('imagem', $def['campos'], true) ? guardarImagemSimples($_FILES['imagem'] ?? [], 'cartao') : ['', ''];
        if ($imagem === '') $imagem = isset($_POST['tirar_imagem']) ? '' : (string)$atual;
        if ($titulo === '') $erro = 'Escreva o título do cartão.';
        elseif ($link === '') $erro = 'Indique para onde o cartão leva (ligação).';
        elseif ($erroImg !== '') $erro = $erroImg;
        if ($erro === '') {
            $v = [$titulo, trim($_POST['texto'] ?? '') ?: null, trim($_POST['icone'] ?? '') ?: null, $imagem ?: null, $link,
                  trim($_POST['etiqueta'] ?? '') ?: null, trim($_POST['acao_texto'] ?? '') ?: null, isset($_POST['ativo']) ? 1 : 0];
            if ($id) {
                $pdo->prepare("UPDATE cartoes_site SET titulo=?, texto=?, icone=?, imagem=?, link=?, etiqueta=?, acao=?, ativo=? WHERE id=? AND grupo=?")
                    ->execute(array_merge($v, [$id, $grupo]));
            } else {
                $ordem = (int)$pdo->query("SELECT COALESCE(MAX(ordem), 0) + 1 FROM cartoes_site WHERE grupo = " . $pdo->quote($grupo))->fetchColumn();
                $pdo->prepare("INSERT INTO cartoes_site (titulo, texto, icone, imagem, link, etiqueta, acao, ativo, grupo, ordem) VALUES (?,?,?,?,?,?,?,?,?,?)")
                    ->execute(array_merge($v, [$grupo, $ordem]));
            }
            $voltar($id ? 'Cartão guardado. Já aparece no site.' : 'Cartão criado. Já aparece no site.');
        }
    }
}

$stmt = $pdo->prepare("SELECT * FROM cartoes_site WHERE grupo = ? ORDER BY ordem, id");
$stmt->execute([$grupo]);
$cartoes = $stmt->fetchAll(PDO::FETCH_ASSOC);
$editarId = (int)($_GET['editar'] ?? ($_POST['id'] ?? 0));
$aEditar = null;
foreach ($cartoes as $c) if ((int)$c['id'] === $editarId) $aEditar = $c;
$novo = isset($_GET['novo']) || ($_SERVER['REQUEST_METHOD'] === 'POST' && !$editarId);
if ($erro && $_POST) $aEditar = array_merge($aEditar ?? [], ['titulo' => $_POST['titulo'] ?? '', 'texto' => $_POST['texto'] ?? '', 'icone' => $_POST['icone'] ?? '',
    'link' => $_POST['link'] ?? '', 'etiqueta' => $_POST['etiqueta'] ?? '', 'acao' => $_POST['acao_texto'] ?? '', 'ativo' => isset($_POST['ativo']) ? 1 : 0]);
$usa = fn(string $campo) => in_array($campo, $def['campos'], true);

$adminPageTitle = 'Cartões e atalhos';
$adminActive = 'cartoes_site';
$adminVerSite = '/';
require_once __DIR__ . '/includes/header.php';
?>
<style>
.cs-tabs{display:flex;flex-wrap:wrap;gap:8px;margin-bottom:18px}
.cs-tabs a{display:inline-flex;align-items:center;gap:8px;min-height:42px;padding:0 16px;border-radius:999px;border:1px solid var(--adm-borda);background:#fff;color:var(--adm-texto);font-weight:600;font-size:14px;text-decoration:none}
.cs-tabs a[aria-current="page"]{background:var(--adm-principal);border-color:var(--adm-principal);color:#fff}
.cs-tabs b{font-size:12px;padding:1px 8px;border-radius:999px;background:rgb(0 0 0 / .08)}
.cs-tabs a[aria-current="page"] b{background:rgb(255 255 255 / .22)}
.cs-onde{display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-bottom:16px;color:var(--adm-texto-2);font-size:14.5px}
.cs-lista{display:grid;gap:10px}
.cs-cartao{display:grid;grid-template-columns:auto 72px minmax(0,1fr) auto;gap:16px;align-items:center;background:#fff;border:1px solid var(--adm-borda);border-radius:16px;padding:12px 14px;box-shadow:var(--adm-sombra-1)}
.cs-cartao.off{opacity:.55}
.cs-ordem{display:flex;flex-direction:column;gap:4px}
.cs-ordem button{width:30px;height:26px;border:1px solid var(--adm-borda);border-radius:8px;background:#fff;cursor:pointer;color:var(--adm-texto-2);display:grid;place-items:center}
.cs-ordem button:hover:not(:disabled){border-color:var(--adm-principal);color:var(--adm-principal)}
.cs-ordem button:disabled{opacity:.35;cursor:default}
.cs-vis{width:72px;height:56px;border-radius:12px;background:color-mix(in srgb,var(--adm-principal) 12%,transparent) center/cover;display:grid;place-items:center;color:var(--adm-principal);font-size:22px}
.cs-info strong{display:block;font-size:15.5px}
.cs-info small{display:block;color:var(--adm-texto-2);font-size:13.5px;line-height:1.4;margin-top:2px}
.cs-info code{font-size:12px;color:var(--adm-texto-2);background:var(--adm-superficie-2);border-radius:6px;padding:1px 6px}
.cs-acoes{display:flex;gap:6px;flex-wrap:wrap;justify-content:flex-end}
.cs-acoes form{display:inline}
.cs-form{margin-top:22px}
.cs-form .grelha{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:0 18px}
.cs-form .grelha > div{min-width:0}
.cs-icones{display:flex;flex-wrap:wrap;gap:6px;margin:-6px 0 18px}
.cs-icones button{width:40px;height:40px;border:1px solid var(--adm-borda);border-radius:10px;background:#fff;cursor:pointer;font-size:18px;color:var(--adm-texto)}
.cs-icones button[aria-pressed="true"]{border-color:var(--adm-principal);background:color-mix(in srgb,var(--adm-principal) 12%,transparent);color:var(--adm-principal)}
.cs-icone-campo{display:flex;align-items:center;gap:10px}
.cs-icone-campo i{font-size:24px;color:var(--adm-principal);width:30px;text-align:center}
.cs-img-atual{display:flex;align-items:center;gap:14px;margin:-6px 0 16px}
.cs-img-atual img{width:120px;height:80px;object-fit:cover;border-radius:10px;border:1px solid var(--adm-borda)}
.cs-botoes{display:flex;gap:10px;flex-wrap:wrap;margin-top:6px}
@media (max-width:800px){.cs-cartao{grid-template-columns:auto 56px minmax(0,1fr)}.cs-acoes{grid-column:1/-1;justify-content:flex-start}.cs-form .grelha{grid-template-columns:1fr}}
</style>

<nav class="cs-tabs" aria-label="Grupos de cartões">
    <?php foreach ($grupos as $g => $gd):
        $n = (int)$pdo->query("SELECT COUNT(*) FROM cartoes_site WHERE grupo = " . $pdo->quote($g))->fetchColumn(); ?>
        <a href="?grupo=<?= $g ?>" <?= $g === $grupo ? 'aria-current="page"' : '' ?>><i class="bi <?= $gd['icone'] ?>" aria-hidden="true"></i> <?= htmlspecialchars($gd['rotulo']) ?> <b><?= $n ?></b></a>
    <?php endforeach; ?>
</nav>

<div class="cs-onde">
    <span><i class="bi bi-info-circle" aria-hidden="true"></i> Onde aparece: <?= htmlspecialchars($def['onde']) ?>. Use as setas para mudar a ordem.</span>
    <a class="btn" href="?grupo=<?= $grupo ?>&novo=1#csForm"><i class="bi bi-plus-lg" aria-hidden="true"></i> Novo cartão</a>
</div>

<div class="cs-lista">
    <?php if (!$cartoes): ?><p class="cs-onde">Ainda não há cartões neste grupo — a secção não aparece no site até haver pelo menos um.</p><?php endif; ?>
    <?php foreach ($cartoes as $k => $c): ?>
        <div class="cs-cartao<?= $c['ativo'] ? '' : ' off' ?>">
            <div class="cs-ordem">
                <form method="post"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button name="acao" value="subir" aria-label="Subir «<?= htmlspecialchars($c['titulo']) ?>»" <?= $k === 0 ? 'disabled' : '' ?>><i class="bi bi-chevron-up"></i></button></form>
                <form method="post"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button name="acao" value="descer" aria-label="Descer «<?= htmlspecialchars($c['titulo']) ?>»" <?= $k === count($cartoes) - 1 ? 'disabled' : '' ?>><i class="bi bi-chevron-down"></i></button></form>
            </div>
            <span class="cs-vis" <?= !empty($c['imagem']) ? 'style="background-image:url(\'/assets/img/' . htmlspecialchars($c['imagem'], ENT_QUOTES) . '\')"' : '' ?>><?php if (empty($c['imagem'])): ?><i class="bi <?= htmlspecialchars($c['icone'] ?: 'bi-square') ?>"></i><?php endif; ?></span>
            <div class="cs-info">
                <strong><?= htmlspecialchars($c['titulo']) ?><?= $c['ativo'] ? '' : ' <span class="estado-badge estado-arquivado">Escondido</span>' ?></strong>
                <?php if (!empty($c['texto'])): ?><small><?= htmlspecialchars($c['texto']) ?></small><?php endif; ?>
                <small><code><?= htmlspecialchars($c['link']) ?></code></small>
            </div>
            <div class="cs-acoes">
                <a class="btn secondary" href="?grupo=<?= $grupo ?>&editar=<?= $c['id'] ?>#csForm"><i class="bi bi-pencil" aria-hidden="true"></i> Editar</a>
                <form method="post"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="btn secondary" name="acao" value="estado"><i class="bi <?= $c['ativo'] ? 'bi-eye-slash' : 'bi-eye' ?>" aria-hidden="true"></i> <?= $c['ativo'] ? 'Esconder' : 'Mostrar' ?></button></form>
                <form method="post" onsubmit="return confirm('Eliminar este cartão? Esta ação não pode ser desfeita.')"><input type="hidden" name="id" value="<?= $c['id'] ?>"><button class="btn danger" name="acao" value="apagar"><i class="bi bi-trash" aria-hidden="true"></i> Eliminar</button></form>
            </div>
        </div>
    <?php endforeach; ?>
</div>

<?php if ($aEditar || $novo): $c = $aEditar ?? ['titulo' => '', 'texto' => '', 'icone' => '', 'imagem' => '', 'link' => '', 'etiqueta' => '', 'acao' => '', 'ativo' => 1]; ?>
<form method="post" enctype="multipart/form-data" class="cs-form" id="csForm">
    <h2><?= $aEditar && !empty($aEditar['id']) ? 'Editar cartão' : 'Novo cartão' ?> — <?= htmlspecialchars($def['rotulo']) ?></h2>
    <?php if ($erro): ?><div class="adm-aviso" style="background:#fef2f2;color:#991b1b;border:1px solid #fecaca"><i class="bi bi-exclamation-circle"></i> <?= htmlspecialchars($erro) ?></div><?php endif; ?>
    <input type="hidden" name="acao" value="guardar">
    <input type="hidden" name="id" value="<?= (int)($aEditar['id'] ?? 0) ?>">
    <div class="grelha">
        <div>
            <label for="csTitulo">Título *</label>
            <input type="text" id="csTitulo" name="titulo" maxlength="160" required value="<?= htmlspecialchars($c['titulo']) ?>">
        </div>
        <div>
            <label for="csLink">Leva para… *</label>
            <input type="text" id="csLink" name="link" maxlength="255" required value="<?= htmlspecialchars($c['link']) ?>" placeholder="/contactos.php ou https://…" list="csPaginas">
            <datalist id="csPaginas">
                <?php foreach (['/pedidos.php', '/contactos.php', '/executivo.php', '/pontos.php', '/mapa.php', '/comercio.php', '/associacoes.php', '/eventos.php', '/noticias.php', '/freguesia-documentos.php', '/assembleia-documentos.php', '/requerimentos.php', '/reservas.php', '/historia.php', '/freguesia.php', '/galeria.php', '/transparencia.php', '/faq.php'] as $pg): ?><option value="<?= $pg ?>"><?php endforeach; ?>
            </datalist>
        </div>
    </div>
    <small>Página do site (ex.: /contactos.php) ou endereço completo de outro site (abre numa janela nova).</small>
    <?php if ($usa('texto')): ?>
        <label for="csTexto">Texto curto</label>
        <textarea id="csTexto" name="texto" maxlength="400" rows="2"><?= htmlspecialchars((string)$c['texto']) ?></textarea>
    <?php endif; ?>
    <?php if ($usa('icone')): ?>
        <label for="csIcone">Ícone</label>
        <div class="cs-icone-campo"><i class="bi <?= htmlspecialchars($c['icone'] ?: 'bi-square') ?>" id="csIconeVer" aria-hidden="true"></i>
            <input type="text" id="csIcone" name="icone" value="<?= htmlspecialchars((string)$c['icone']) ?>" placeholder="bi-telephone-fill" style="margin:0"></div>
        <small style="margin-top:8px">Escolha um abaixo ou escreva o nome de qualquer ícone de icons.getbootstrap.com.</small>
        <div class="cs-icones" role="group" aria-label="Ícones sugeridos">
            <?php foreach (cartoesIconesSugeridos() as $ic): ?><button type="button" data-icone="<?= $ic ?>" aria-label="<?= $ic ?>" aria-pressed="<?= $ic === $c['icone'] ? 'true' : 'false' ?>"><i class="bi <?= $ic ?>"></i></button><?php endforeach; ?>
        </div>
    <?php endif; ?>
    <?php if ($usa('imagem')): ?>
        <label for="csImagem"><?= htmlspecialchars($def['imagem_rotulo'] ?? 'Fotografia') ?></label>
        <?php if (!empty($c['imagem'])): ?>
            <div class="cs-img-atual"><img src="/assets/img/<?= htmlspecialchars($c['imagem']) ?>" alt="">
                <label class="check"><input type="checkbox" name="tirar_imagem"> <?= htmlspecialchars($def['imagem_tirar'] ?? 'Tirar a fotografia (fica uma textura com o ícone)') ?></label></div>
        <?php endif; ?>
        <input type="file" id="csImagem" name="imagem" accept="image/*">
        <small><?= htmlspecialchars($def['imagem_nota'] ?? 'Fotografia horizontal, de preferência com 1200 px ou mais de largura.') ?></small>
    <?php endif; ?>
    <?php if ($usa('etiqueta') || $usa('acao')): ?>
    <div class="grelha">
        <?php if ($usa('etiqueta')): ?><div><label for="csEtq">Etiqueta (opcional)</label><input type="text" id="csEtq" name="etiqueta" maxlength="60" value="<?= htmlspecialchars((string)$c['etiqueta']) ?>" placeholder="Ex.: Cultura"></div><?php endif; ?>
        <?php if ($usa('acao')): ?><div><label for="csAcao">Texto do botão (opcional)</label><input type="text" id="csAcao" name="acao_texto" maxlength="80" value="<?= htmlspecialchars((string)$c['acao']) ?>" placeholder="Saber mais"></div><?php endif; ?>
    </div>
    <?php endif; ?>
    <label class="check"><input type="checkbox" name="ativo" <?= !empty($c['ativo']) ? 'checked' : '' ?>> Mostrar no site</label>
    <div class="cs-botoes">
        <button type="submit" class="btn"><i class="bi bi-check2" aria-hidden="true"></i> Guardar cartão</button>
        <a class="btn secondary" href="?grupo=<?= $grupo ?>">Cancelar</a>
    </div>
</form>
<script>
(function () {
    var campo = document.getElementById('csIcone'), ver = document.getElementById('csIconeVer');
    if (!campo) return;
    function marcar() {
        ver.className = 'bi ' + (campo.value.trim() || 'bi-square');
        document.querySelectorAll('[data-icone]').forEach(function (b) { b.setAttribute('aria-pressed', b.dataset.icone === campo.value.trim() ? 'true' : 'false'); });
    }
    document.querySelectorAll('[data-icone]').forEach(function (b) {
        b.addEventListener('click', function () { campo.value = b.dataset.icone; campo.dispatchEvent(new Event('input', { bubbles: true })); marcar(); });
    });
    campo.addEventListener('input', marcar);
})();
</script>
<?php endif; ?>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
