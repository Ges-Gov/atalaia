<?php
/*
 * Gestão de membros partilhada pelo Executivo e pela Assembleia: mesmo formulário
 * (nome, cargo, partido, fotografia) e mesma lista. Cada página só passa a config.
 *
 * $cfg = [
 *   'tabela'   => 'executivo_membros',
 *   'pagina'   => 'executivo.php',
 *   'titulo'   => 'Executivo',
 *   'descricao'=> '...',
 *   'publica'  => '../executivo.php',
 *   'cargos'   => ['Presidente', ...],               // sugestões do campo cargo
 *   'extras'   => [['coluna', 'Rótulo', 'textarea|email|text'], ...],
 *   'grupos'   => ['Mesa da Assembleia', 'Vogais'],  // escolha obrigatória da coluna `grupo` (opcional)
 *   'grupo_padrao' => 'Vogais',
 *   'grupo_sugerido' => 'presidente|secret',         // cargos que pré-selecionam o 1.º grupo
 *   'ordenar'  => 'ORDER BY ...',
 *   'secoes'   => callable|null,                     // agrupa a lista: fn($m) => 'Nome da secção'
 * ]
 */

require_once __DIR__ . "/../../includes/membros.php";

function membrosAdminColunas(PDO $pdo, string $tabela): array
{
    return $pdo->query("SHOW COLUMNS FROM `$tabela`")->fetchAll(PDO::FETCH_COLUMN);
}

function membrosAdminProcessar(PDO $pdo, array $cfg): string
{
    $tabela = $cfg['tabela'];
    $pagina = $cfg['pagina'];
    $colunas = membrosAdminColunas($pdo, $tabela);
    $temAtualizado = in_array('atualizado_em', $colunas, true);

    if (isset($_GET['eliminar'])) {
        $id = (int)$_GET['eliminar'];
        $st = $pdo->prepare("SELECT foto FROM `$tabela` WHERE id = ?");
        $st->execute([$id]);
        $foto = $st->fetchColumn();
        if ($foto) {
            $caminho = __DIR__ . '/../..' . fotoMembroUrl($foto);
            if (is_file($caminho)) @unlink($caminho);
        }
        $pdo->prepare("DELETE FROM `$tabela` WHERE id = ?")->execute([$id]);
        header("Location: $pagina?ok=eliminado");
        exit;
    }

    if (isset($_GET['toggle'])) {
        $pdo->prepare("UPDATE `$tabela` SET ativo = IF(ativo = 1, 0, 1)" . ($temAtualizado ? ", atualizado_em = NOW()" : "") . " WHERE id = ?")
            ->execute([(int)$_GET['toggle']]);
        header("Location: $pagina?ok=estado");
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        return '';
    }

    $id = (int)($_POST['id'] ?? 0);
    $dados = [
        'nome'    => trim($_POST['nome'] ?? ''),
        'cargo'   => trim($_POST['cargo'] ?? ''),
        'partido' => trim($_POST['partido'] ?? ''),
        'ativo'   => isset($_POST['ativo']) ? 1 : 0,
    ];

    if ($dados['nome'] === '' || $dados['cargo'] === '') {
        return "O nome e o cargo são obrigatórios.";
    }

    [$foto, $erroFoto] = guardarFotoMembro($_FILES['foto'] ?? [], (string)($_POST['foto_atual'] ?? ''));
    if ($erroFoto !== '') {
        return $erroFoto;
    }
    $dados['foto'] = $foto;

    foreach ($cfg['extras'] as [$coluna]) {
        if (in_array($coluna, $colunas, true)) {
            $dados[$coluna] = trim($_POST[$coluna] ?? '');
        }
    }

    if (!empty($cfg['grupos'])) {
        $permitidos = $cfg['grupos'];
        if ($id > 0) {
            // um membro antigo com um grupo fora da lista (ex.: 'Outros') pode manter o seu
            $st = $pdo->prepare("SELECT grupo FROM `$tabela` WHERE id = ?");
            $st->execute([$id]);
            $permitidos[] = (string)$st->fetchColumn();
        }
        $grupo = trim($_POST['grupo'] ?? '');
        if (!in_array($grupo, $permitidos, true)) {
            return "Escolha onde o membro aparece: " . implode(' ou ', $cfg['grupos']) . ".";
        }
        $dados['grupo'] = $grupo;
    }

    $ordemEnviada = trim((string)($_POST['ordem'] ?? ''));
    if ($ordemEnviada !== '') {
        $dados['ordem'] = (int)$ordemEnviada;
    } elseif ($id === 0) {
        $dados['ordem'] = (int)$pdo->query("SELECT COALESCE(MAX(ordem), 0) + 1 FROM `$tabela`")->fetchColumn();
    }

    $dados = array_intersect_key($dados, array_flip($colunas));

    if ($id > 0) {
        $sets = implode(', ', array_map(fn($c) => "`$c` = ?", array_keys($dados)));
        if ($temAtualizado) $sets .= ", atualizado_em = NOW()";
        $st = $pdo->prepare("UPDATE `$tabela` SET $sets WHERE id = ?");
        $st->execute([...array_values($dados), $id]);
        header("Location: $pagina?ok=editado");
    } else {
        $cols = implode(', ', array_map(fn($c) => "`$c`", array_keys($dados)));
        $marcas = implode(', ', array_fill(0, count($dados), '?'));
        $st = $pdo->prepare("INSERT INTO `$tabela` ($cols) VALUES ($marcas)");
        $st->execute(array_values($dados));
        header("Location: $pagina?ok=criado");
    }
    exit;
}

function membrosAdminRender(PDO $pdo, array $cfg, string $erro): void
{
    $tabela = $cfg['tabela'];
    $pagina = $cfg['pagina'];
    $colunas = membrosAdminColunas($pdo, $tabela);

    $editar = null;
    if (isset($_GET['editar'])) {
        $st = $pdo->prepare("SELECT * FROM `$tabela` WHERE id = ?");
        $st->execute([(int)$_GET['editar']]);
        $editar = $st->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    // Depois de um erro, o formulário volta preenchido com o que foi enviado.
    $valores = $erro !== '' ? $_POST : ($editar ?? []);
    $formAberto = $editar !== null || $erro !== '' || isset($_GET['novo']);

    $membros = $pdo->query("SELECT * FROM `$tabela` " . $cfg['ordenar'])->fetchAll(PDO::FETCH_ASSOC);
    $secoes = [];
    foreach ($membros as $m) {
        $secao = !empty($cfg['secoes']) ? ($cfg['secoes'])($m) : '';
        $secoes[$secao][] = $m;
    }

    $mensagens = ['criado' => 'Membro adicionado.', 'editado' => 'Alterações guardadas.', 'estado' => 'Visibilidade alterada.', 'eliminado' => 'Membro eliminado.'];
    $ok = $mensagens[$_GET['ok'] ?? ''] ?? '';
    $v = fn($k) => htmlspecialchars((string)($valores[$k] ?? ''));
    $extrasPreenchidos = false;
    foreach ($cfg['extras'] as [$coluna]) {
        if (!empty($valores[$coluna])) $extrasPreenchidos = true;
    }
    ?>
    <style>
    .mbr-head{display:flex;justify-content:space-between;align-items:center;gap:16px;flex-wrap:wrap;margin-bottom:20px}
    .mbr-head p{margin:0;color:#64748b;font-weight:600}
    .mbr-head .btn,.mbr-form-acoes .btn{display:inline-flex;align-items:center;gap:7px;font-size:14px;font-weight:800;padding:11px 18px;border:0;cursor:pointer;text-decoration:none;line-height:1.2}
    .mbr-form-box form.mbr-form{background:none;border:0;box-shadow:none;padding:0;margin:0;max-width:none}
    .mbr-head-actions{display:flex;gap:10px;flex-wrap:wrap}
    .mbr-alert{padding:13px 16px;border-radius:14px;margin-bottom:16px;font-weight:800}
    .mbr-alert.ok{background:#dcfce7;color:#166534}
    .mbr-alert.err{background:#fee2e2;color:#991b1b}
    .mbr-secao{margin-bottom:22px}
    .mbr-secao h3{margin:0 0 10px;font-size:15px;text-transform:uppercase;letter-spacing:.6px;color:#64748b}
    /* width:0 + min-width:100% impede a fila de alargar a coluna principal (e esmagar o menu lateral) */
    .mbr-row{display:flex;gap:14px;overflow-x:auto;padding:4px 2px 12px;scroll-snap-type:x proximity;width:0;min-width:100%}
    .mbr-card{flex:0 0 200px;scroll-snap-align:start;background:#fff;border:1px solid #e5e7eb;border-radius:20px;padding:18px 14px 14px;text-align:center;box-shadow:0 10px 26px rgba(15,23,42,.06);display:flex;flex-direction:column;align-items:center;gap:6px}
    .mbr-card.inativo{opacity:.55}
    .mbr-card.a-editar{border-color:var(--cor-principal);box-shadow:0 0 0 2px var(--cor-principal)}
    .mbr-foto{width:76px;height:76px;border-radius:50%;overflow:hidden;background:var(--cor-principal);color:#fff;display:grid;place-items:center;font-weight:900;font-size:24px;margin-bottom:4px}
    .mbr-foto img{width:100%;height:100%;object-fit:cover}
    .mbr-card strong{color:#11151B;font-size:15px;line-height:1.25}
    .mbr-cargo{color:#475569;font-size:13px;font-weight:700}
    .mbr-tags{display:flex;gap:5px;flex-wrap:wrap;justify-content:center;min-height:22px}
    .mbr-tag{background:#f1f5f9;color:#334155;border-radius:999px;padding:3px 9px;font-size:11px;font-weight:800}
    .mbr-tag.off{background:#fee2e2;color:#991b1b}
    .mbr-acoes{display:flex;gap:6px;margin-top:auto;padding-top:8px}
    .mbr-acoes a{width:36px;height:36px;border-radius:11px;display:grid;place-items:center;background:#f1f5f9;color:#334155;text-decoration:none;font-size:16px}
    .mbr-acoes a:hover{background:var(--cor-principal);color:#fff}
    .mbr-acoes a.apagar:hover{background:#dc2626}
    .mbr-vazio{background:#f8fafc;border:1px dashed #cbd5e1;border-radius:18px;padding:28px;text-align:center;color:#64748b;font-weight:700}
    .mbr-form-box{background:#fff;border:1px solid #e5e7eb;border-radius:22px;padding:24px;margin-bottom:24px;box-shadow:0 14px 34px rgba(15,23,42,.07)}
    .mbr-form-box[hidden]{display:none}
    .mbr-form-box h2{margin:0 0 18px;font-size:22px}
    .mbr-form{display:grid;grid-template-columns:1fr 1fr;gap:14px 18px}
    .mbr-form .full{grid-column:1/-1}
    .mbr-form label{display:block;font-weight:800;font-size:13px;color:#11151B;margin-bottom:6px}
    .mbr-form input[type=text],.mbr-form input[type=email],.mbr-form input[type=number],.mbr-form textarea{width:100%;box-sizing:border-box;border:1px solid #dbe4ee;background:#f8fafc;border-radius:12px;padding:11px 13px;font:inherit}
    .mbr-form textarea{min-height:90px}
    .mbr-foto-campo{display:flex;align-items:center;gap:12px}
    .mbr-foto-campo .mbr-foto{width:56px;height:56px;font-size:18px;margin:0;flex:0 0 auto}
    .mbr-check{display:flex;align-items:center;gap:8px;font-weight:800}
    .mbr-check input{width:auto}
    .mbr-form details{grid-column:1/-1;border-top:1px solid #e5e7eb;padding-top:12px}
    .mbr-form summary{cursor:pointer;font-weight:800;color:#475569}
    .mbr-extras{display:grid;grid-template-columns:1fr 1fr;gap:14px 18px;margin-top:14px}
    .mbr-form-acoes{grid-column:1/-1;display:flex;gap:10px;flex-wrap:wrap}
    .mbr-grupos{display:flex;gap:10px;flex-wrap:wrap}
    .mbr-grupo{display:block;position:relative;margin:0!important;cursor:pointer}
    .mbr-grupo input{position:absolute;opacity:0;pointer-events:none}
    .mbr-grupo span{display:inline-flex;align-items:center;gap:8px;padding:10px 16px;border:1px solid #dbe4ee;background:#f8fafc;border-radius:12px;font-weight:800;color:#334155}
    .mbr-grupo span:before{content:"";width:14px;height:14px;border-radius:50%;border:2px solid #94a3b8;box-sizing:border-box}
    .mbr-grupo input:checked + span{border-color:var(--cor-principal);background:color-mix(in srgb,var(--cor-principal) 8%,#fff);color:var(--cor-principal)}
    .mbr-grupo input:checked + span:before{border:4px solid var(--cor-principal)}
    .mbr-grupo input:focus-visible + span{outline:2px solid var(--cor-principal);outline-offset:2px}
    @media(max-width:760px){.mbr-form,.mbr-extras{grid-template-columns:1fr}.mbr-card{flex-basis:170px}}
    </style>

    <div class="mbr-head">
        <p><?= htmlspecialchars($cfg['descricao']) ?></p>
        <div class="mbr-head-actions">
            <?php if (!empty($cfg['publica'])): ?>
                <a class="btn secondary" href="<?= htmlspecialchars($cfg['publica']) ?>" target="_blank"><i class="bi bi-box-arrow-up-right"></i> Ver página pública</a>
            <?php endif; ?>
            <button type="button" class="btn" id="mbrNovo"><i class="bi bi-person-plus-fill"></i> Adicionar membro</button>
        </div>
    </div>

    <?php if ($ok): ?><div class="mbr-alert ok"><?= htmlspecialchars($ok) ?></div><?php endif; ?>
    <?php if ($erro): ?><div class="mbr-alert err"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

    <div class="mbr-form-box" id="mbrFormBox" <?= $formAberto ? '' : 'hidden' ?>>
        <h2><?= $editar ? 'Editar membro' : 'Novo membro' ?></h2>
        <form method="POST" enctype="multipart/form-data" class="mbr-form" action="<?= htmlspecialchars($pagina) ?><?= $editar ? '?editar=' . (int)$editar['id'] : '' ?>">
            <input type="hidden" name="id" value="<?= (int)($editar['id'] ?? ($valores['id'] ?? 0)) ?>">
            <input type="hidden" name="foto_atual" value="<?= $v('foto') ?: htmlspecialchars($valores['foto_atual'] ?? '') ?>">

            <div>
                <label for="mbrNome">Nome *</label>
                <input type="text" id="mbrNome" name="nome" value="<?= $v('nome') ?>" required>
            </div>
            <div>
                <label for="mbrCargo">Cargo *</label>
                <input type="text" id="mbrCargo" name="cargo" value="<?= $v('cargo') ?>" list="mbrCargos" required>
                <datalist id="mbrCargos">
                    <?php foreach ($cfg['cargos'] as $c): ?><option value="<?= htmlspecialchars($c) ?>"><?php endforeach; ?>
                </datalist>
            </div>
            <?php if (!empty($cfg['grupos'])):
                $grupoAtual = (string)($valores['grupo'] ?? '') ?: $cfg['grupo_padrao'];
                $opcoesGrupo = $cfg['grupos'];
                if (!in_array($grupoAtual, $opcoesGrupo, true)) $opcoesGrupo[] = $grupoAtual;
            ?>
            <div class="full">
                <label>Aparece em *</label>
                <div class="mbr-grupos" id="mbrGrupos">
                    <?php foreach ($opcoesGrupo as $g): ?>
                        <label class="mbr-grupo">
                            <input type="radio" name="grupo" value="<?= htmlspecialchars($g) ?>" <?= $g === $grupoAtual ? 'checked' : '' ?> required>
                            <span><?= htmlspecialchars($g) ?></span>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
            <div>
                <label for="mbrPartido">Partido / Movimento</label>
                <input type="text" id="mbrPartido" name="partido" value="<?= $v('partido') ?>">
            </div>
            <div>
                <label for="mbrFoto">Fotografia</label>
                <div class="mbr-foto-campo">
                    <?php if (!empty($editar['foto'])): ?>
                        <div class="mbr-foto"><img src="<?= htmlspecialchars(fotoMembroUrl($editar['foto'])) ?>" alt=""></div>
                    <?php endif; ?>
                    <input type="file" id="mbrFoto" name="foto" accept="image/*">
                </div>
            </div>

            <label class="mbr-check full">
                <input type="checkbox" name="ativo" <?= ($erro !== '' ? !empty($valores['ativo']) : (!isset($editar['ativo']) || !empty($editar['ativo']))) ? 'checked' : '' ?>>
                Visível no site
            </label>

            <details <?= $extrasPreenchidos ? 'open' : '' ?>>
                <summary>Mais detalhes (opcional)</summary>
                <div class="mbr-extras">
                    <?php foreach ($cfg['extras'] as [$coluna, $rotulo, $tipo]): ?>
                        <?php if (!in_array($coluna, $colunas, true)) continue; ?>
                        <div class="<?= $tipo === 'textarea' ? 'full' : '' ?>">
                            <label for="mbr_<?= $coluna ?>"><?= htmlspecialchars($rotulo) ?></label>
                            <?php if ($tipo === 'textarea'): ?>
                                <textarea id="mbr_<?= $coluna ?>" name="<?= $coluna ?>"><?= $v($coluna) ?></textarea>
                            <?php else: ?>
                                <input type="<?= $tipo ?>" id="mbr_<?= $coluna ?>" name="<?= $coluna ?>" value="<?= $v($coluna) ?>">
                            <?php endif; ?>
                        </div>
                    <?php endforeach; ?>
                    <div>
                        <label for="mbrOrdem">Ordem de apresentação</label>
                        <input type="number" id="mbrOrdem" name="ordem" value="<?= $v('ordem') ?>" placeholder="Automática">
                    </div>
                </div>
            </details>

            <div class="mbr-form-acoes">
                <button type="submit" class="btn"><?= $editar ? 'Guardar alterações' : 'Adicionar membro' ?></button>
                <a href="<?= htmlspecialchars($pagina) ?>" class="btn secondary" id="mbrCancelar">Cancelar</a>
            </div>
        </form>
    </div>

    <?php if (empty($membros)): ?>
        <div class="mbr-vazio">Ainda não existem membros. Use o botão «Adicionar membro».</div>
    <?php endif; ?>

    <?php foreach ($secoes as $nomeSecao => $lista): ?>
        <div class="mbr-secao">
            <?php if ($nomeSecao !== ''): ?><h3><?= htmlspecialchars($nomeSecao) ?> · <?= count($lista) ?></h3><?php endif; ?>
            <div class="mbr-row">
                <?php foreach ($lista as $m): ?>
                    <div class="mbr-card<?= empty($m['ativo']) ? ' inativo' : '' ?><?= ($editar && (int)$editar['id'] === (int)$m['id']) ? ' a-editar' : '' ?>">
                        <div class="mbr-foto">
                            <?php if (!empty($m['foto'])): ?>
                                <img src="<?= htmlspecialchars(fotoMembroUrl($m['foto'])) ?>" alt="">
                            <?php else: ?>
                                <?= htmlspecialchars(mb_strtoupper(mb_substr($m['nome'], 0, 1))) ?>
                            <?php endif; ?>
                        </div>
                        <strong><?= htmlspecialchars($m['nome']) ?></strong>
                        <span class="mbr-cargo"><?= htmlspecialchars($m['cargo']) ?></span>
                        <div class="mbr-tags">
                            <?php if (!empty($m['partido'])): ?><span class="mbr-tag"><?= htmlspecialchars($m['partido']) ?></span><?php endif; ?>
                            <?php if (empty($m['ativo'])): ?><span class="mbr-tag off">Oculto</span><?php endif; ?>
                        </div>
                        <div class="mbr-acoes">
                            <a href="<?= htmlspecialchars($pagina) ?>?editar=<?= (int)$m['id'] ?>" title="Editar"><i class="bi bi-pencil-fill"></i></a>
                            <a href="<?= htmlspecialchars($pagina) ?>?toggle=<?= (int)$m['id'] ?>" title="<?= !empty($m['ativo']) ? 'Ocultar do site' : 'Mostrar no site' ?>"><i class="bi <?= !empty($m['ativo']) ? 'bi-eye-slash-fill' : 'bi-eye-fill' ?>"></i></a>
                            <a href="<?= htmlspecialchars($pagina) ?>?eliminar=<?= (int)$m['id'] ?>" class="apagar" title="Eliminar" onclick="return confirm('Eliminar <?= htmlspecialchars(addslashes($m['nome'])) ?>?')"><i class="bi bi-trash-fill"></i></a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <script>
    (function () {
        var box = document.getElementById('mbrFormBox');
        document.getElementById('mbrNovo').addEventListener('click', function () {
            <?php if ($editar): ?>
            window.location = '<?= htmlspecialchars($pagina) ?>?novo=1';
            return;
            <?php endif; ?>
            box.hidden = false;
            box.scrollIntoView({behavior: 'smooth', block: 'start'});
            document.getElementById('mbrNome').focus({preventScroll: true});
        });
        <?php if (!$editar): ?>
        document.getElementById('mbrCancelar').addEventListener('click', function (e) {
            e.preventDefault();
            box.hidden = true;
        });
        <?php endif; ?>
        <?php if ($formAberto): ?>
        box.scrollIntoView({block: 'start'});
        <?php endif; ?>
        <?php if (!empty($cfg['grupos']) && !empty($cfg['grupo_sugerido'])): ?>
        // Ao escrever o cargo, sugere o grupo — até a pessoa escolher à mão.
        var grupos = document.querySelectorAll('#mbrGrupos input');
        var escolhidoAMao = <?= $editar ? 'true' : 'false' ?>;
        grupos.forEach(function (r) { r.addEventListener('change', function () { escolhidoAMao = true; }); });
        document.getElementById('mbrCargo').addEventListener('input', function () {
            if (escolhidoAMao || !this.value.trim()) return;
            var alvo = new RegExp(<?= json_encode($cfg['grupo_sugerido']) ?>, 'i').test(this.value) ? <?= json_encode($cfg['grupos'][0]) ?> : <?= json_encode($cfg['grupo_padrao']) ?>;
            grupos.forEach(function (r) { r.checked = (r.value === alvo); });
        });
        <?php endif; ?>
    })();
    </script>
    <?php
}
