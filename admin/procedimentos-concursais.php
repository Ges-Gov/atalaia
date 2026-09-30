<?php
require_once "includes/auth.php";
require_once "../includes/db.php";

$uploadDir = __DIR__ . "/../uploads/contratacao-publica/";
if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

$erro = '';

// Carreiras gerais da Administração Pública (LTFP) e respetivas categorias —
// mesmo mapa oficial (DGAEP) usado no painel de Procedimentos Concursais da
// intranet (App\Filament\Enums\CarreiraGeral). Terminologia legal exata,
// não inventada — não acrescentar outras carreiras/categorias sem confirmar.
$carreiras = [
    'tecnico_superior' => 'Técnico Superior',
    'assistente_tecnico' => 'Assistente Técnico',
    'assistente_operacional' => 'Assistente Operacional',
];
$categoriasPorCarreira = [
    'tecnico_superior' => ['tecnico_superior' => 'Técnico Superior'],
    'assistente_tecnico' => [
        'coordenador_tecnico' => 'Coordenador Técnico',
        'assistente_tecnico' => 'Assistente Técnico',
    ],
    'assistente_operacional' => [
        'encarregado_geral_operacional' => 'Encarregado Geral Operacional',
        'encarregado_operacional' => 'Encarregado Operacional',
        'assistente_operacional' => 'Assistente Operacional',
    ],
];

// Designações legais exatas (mesmo texto usado na intranet) — não abreviar/inventar.
$tiposVinculo = [
    'tempo_indeterminado' => 'Contrato de Trabalho em Funções Públicas por Tempo Indeterminado',
    'termo_certo' => 'Contrato de Trabalho em Funções Públicas a Termo Resolutivo Certo',
    'termo_incerto' => 'Contrato de Trabalho em Funções Públicas a Termo Resolutivo Incerto',
];

$estados = [
    'rascunho' => 'Rascunho',
    'publicado' => 'Publicado',
    'encerrado' => 'Encerrado',
    'arquivado' => 'Arquivado',
];

function uploadCPMultiplos($campo, $uploadDir, &$erro) {
    $guardados = [];

    if (empty($_FILES[$campo]['name']) || !is_array($_FILES[$campo]['name'])) {
        return $guardados;
    }

    $permitidos = ['pdf','doc','docx','xls','xlsx','jpg','jpeg','png','webp'];

    foreach ($_FILES[$campo]['name'] as $i => $nomeOriginal) {
        if (empty($nomeOriginal) || empty($_FILES[$campo]['tmp_name'][$i])) {
            continue;
        }

        $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

        if (!in_array($ext, $permitidos)) {
            $erro = "Formato inválido em um dos ficheiros. Use PDF, Word, Excel ou imagem.";
            return [];
        }

        $novo = "contratacao_" . date("YmdHis") . "_" . $i . "_" . bin2hex(random_bytes(4)) . "." . $ext;

        if (move_uploaded_file($_FILES[$campo]['tmp_name'][$i], $uploadDir . $novo)) {
            $guardados[] = [
                'ficheiro' => $novo,
                'ficheiro_original' => $nomeOriginal
            ];
        } else {
            $erro = "Erro ao enviar um dos ficheiros.";
            return [];
        }
    }

    return $guardados;
}


if (isset($_GET['apagar_anexo'])) {
    $anexoId = (int)$_GET['apagar_anexo'];

    $stmt = $pdo->prepare("SELECT * FROM contratacao_publica_anexos WHERE id=?");
    $stmt->execute([$anexoId]);
    $anexo = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($anexo) {
        $procedimentoId = (int)$anexo['procedimento_id'];

        if (!empty($anexo['ficheiro'])) {
            $ficheiroPath = $uploadDir . $anexo['ficheiro'];
            if (is_file($ficheiroPath)) {
                @unlink($ficheiroPath);
            }
        }

        $del = $pdo->prepare("DELETE FROM contratacao_publica_anexos WHERE id=?");
        $del->execute([$anexoId]);

        header("Location: procedimentos-concursais.php?editar=" . $procedimentoId . "&ok=1");
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int)($_POST['id'] ?? 0);
    $titulo = trim($_POST['titulo'] ?? '');
    $carreira = trim($_POST['carreira'] ?? '');
    $categoria = trim($_POST['categoria'] ?? '');
    $area_funcional = trim($_POST['area_funcional'] ?? '');
    $vagas = trim($_POST['vagas'] ?? '');
    $tipo_vinculo = trim($_POST['tipo_vinculo'] ?? '');
    $estado = trim($_POST['estado'] ?? 'rascunho');
    $descricao = trim($_POST['descricao'] ?? '');
    $requisitos = trim($_POST['requisitos'] ?? '');
    $data_publicacao = trim($_POST['data_publicacao'] ?? '');
    $inicio_candidaturas = trim($_POST['inicio_candidaturas'] ?? '');
    $fim_candidaturas = trim($_POST['fim_candidaturas'] ?? '');
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    if (!$titulo) $erro = "O título é obrigatório.";
    if (!$erro && $carreira !== '' && !isset($carreiras[$carreira])) $erro = "Carreira inválida.";
    if (!$erro && $categoria !== '' && $carreira !== '' && !isset(($categoriasPorCarreira[$carreira] ?? [])[$categoria])) $erro = "Categoria inválida para a carreira escolhida.";

    if (!$erro) {
        $vagasSql = $vagas !== '' ? (int)$vagas : null;
        $dataSql = $data_publicacao !== '' ? $data_publicacao : null;
        $inicioSql = $inicio_candidaturas !== '' ? $inicio_candidaturas : null;
        $fimSql = $fim_candidaturas !== '' ? $fim_candidaturas : null;

        if ($id > 0) {
            $stmt = $pdo->prepare("UPDATE contratacao_publica SET titulo=?, carreira=?, categoria=?, area_funcional=?, vagas=?, tipo_vinculo=?, estado=?, descricao=?, requisitos=?, data_publicacao=?, inicio_candidaturas=?, fim_candidaturas=?, ativo=? WHERE id=?");
            $stmt->execute([$titulo,$carreira,$categoria,$area_funcional,$vagasSql,$tipo_vinculo,$estado,$descricao,$requisitos,$dataSql,$inicioSql,$fimSql,$ativo,$id]);
            $procedimentoId = $id;
        } else {
            $stmt = $pdo->prepare("INSERT INTO contratacao_publica (titulo,carreira,categoria,area_funcional,vagas,tipo_vinculo,estado,descricao,requisitos,data_publicacao,inicio_candidaturas,fim_candidaturas,ativo) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)");
            $stmt->execute([$titulo,$carreira,$categoria,$area_funcional,$vagasSql,$tipo_vinculo,$estado,$descricao,$requisitos,$dataSql,$inicioSql,$fimSql,$ativo]);
            $procedimentoId = (int)$pdo->lastInsertId();
        }

        $ficheiros = uploadCPMultiplos('ficheiros', $uploadDir, $erro);

        if (!$erro && !empty($ficheiros)) {
            $stmtAnexo = $pdo->prepare("INSERT INTO contratacao_publica_anexos (procedimento_id, ficheiro, ficheiro_original) VALUES (?, ?, ?)");

            foreach ($ficheiros as $f) {
                $stmtAnexo->execute([$procedimentoId, $f['ficheiro'], $f['ficheiro_original']]);
            }

            // Compatibilidade com a coluna antiga ficheiro:
            // guarda o primeiro anexo na tabela principal caso ainda esteja vazio.
            $stmtAtual = $pdo->prepare("SELECT ficheiro FROM contratacao_publica WHERE id=?");
            $stmtAtual->execute([$procedimentoId]);
            $ficheiroAntigo = $stmtAtual->fetchColumn();

            if (empty($ficheiroAntigo)) {
                $stmtFirst = $pdo->prepare("UPDATE contratacao_publica SET ficheiro=? WHERE id=?");
                $stmtFirst->execute([$ficheiros[0]['ficheiro'], $procedimentoId]);
            }
        }

        if (!$erro) {
            header("Location: procedimentos-concursais.php?ok=1");
            exit;
        }
    }
}

if (isset($_GET['apagar'])) {
    $idApagar = (int)$_GET['apagar'];

    $stmt = $pdo->prepare("SELECT ficheiro FROM contratacao_publica_anexos WHERE procedimento_id=?");
    $stmt->execute([$idApagar]);
    $anexosApagar = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($anexosApagar as $a) {
        if (!empty($a['ficheiro'])) {
            $ficheiroPath = $uploadDir . $a['ficheiro'];
            if (is_file($ficheiroPath)) {
                @unlink($ficheiroPath);
            }
        }
    }

    $stmt = $pdo->prepare("DELETE FROM contratacao_publica_anexos WHERE procedimento_id=?");
    $stmt->execute([$idApagar]);

    $stmt = $pdo->prepare("DELETE FROM contratacao_publica WHERE id=?");
    $stmt->execute([$idApagar]);

    header("Location: procedimentos-concursais.php");
    exit;
}

if (isset($_GET['toggle'])) {
    $stmt = $pdo->prepare("UPDATE contratacao_publica SET ativo=IF(ativo=1,0,1) WHERE id=?");
    $stmt->execute([(int)$_GET['toggle']]);
    header("Location: procedimentos-concursais.php");
    exit;
}

$editar = null;
$anexosEditar = [];

if (isset($_GET['editar'])) {
    $stmt = $pdo->prepare("SELECT * FROM contratacao_publica WHERE id=?");
    $stmt->execute([(int)$_GET['editar']]);
    $editar = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($editar) {
        $stmt = $pdo->prepare("SELECT * FROM contratacao_publica_anexos WHERE procedimento_id=? ORDER BY id DESC");
        $stmt->execute([(int)$editar['id']]);
        $anexosEditar = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}

$lista = $pdo->query("SELECT * FROM contratacao_publica ORDER BY data_publicacao DESC, id DESC")->fetchAll(PDO::FETCH_ASSOC);

$anexosPorProcedimento = [];
try {
    $rowsAnexos = $pdo->query("SELECT * FROM contratacao_publica_anexos ORDER BY id DESC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rowsAnexos as $a) {
        $anexosPorProcedimento[(int)$a['procedimento_id']][] = $a;
    }
} catch(Exception $e) {}

$adminPageTitle = "Procedimentos Concursais";
$adminActive = "procedimentos_concursais";
require_once "includes/header.php";
?>

<style>
.cp-admin-hero{background:linear-gradient(135deg,var(--cor-principal),#11151B);color:white;border-radius:30px;padding:30px;margin-bottom:24px;box-shadow:0 22px 60px rgba(15,23,42,.18);display:grid;grid-template-columns:1fr auto;gap:20px;align-items:center}
.cp-admin-hero h2{margin:8px 0;font-size:34px}.cp-admin-hero p{margin:0;color:#dbeafe}
.cp-kicker{display:inline-flex;background:rgba(212,170,0,.16);border:1px solid rgba(212,170,0,.35);color:#F0D060;padding:8px 13px;border-radius:999px;font-size:12px;font-weight:900;text-transform:uppercase}
.cp-grid-admin{display:grid;grid-template-columns:.85fr 1.15fr;gap:24px;align-items:start}
.cp-card-admin{background:white;border:1px solid #e5e7eb;border-radius:24px;padding:22px;box-shadow:0 14px 34px rgba(15,23,42,.06)}
.cp-card-admin h3{margin:0 0 16px;color:#11151B;font-size:24px}
.cp-form{display:grid;gap:14px}.cp-form label{font-weight:900;color:#11151B;font-size:13px}
.cp-form input,.cp-form textarea,.cp-form select{width:100%;box-sizing:border-box;border:1px solid #dbe4ee;background:#f8fafc;color:#11151B;border-radius:15px;min-height:48px;padding:0 14px;font-weight:800}
.cp-form textarea{min-height:120px;padding:14px;line-height:1.6}
.cp-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:12px}
.cp-checks,.cp-actions{display:flex;gap:8px;flex-wrap:wrap}.cp-checks label{display:flex;align-items:center;gap:8px;background:#f8fafc;border:1px solid #e5e7eb;padding:10px 12px;border-radius:14px}.cp-checks input{width:auto;min-height:auto}
.cp-alert{padding:14px 16px;border-radius:16px;margin-bottom:16px;font-weight:900}.cp-alert.ok{background:#dcfce7;color:#166534}.cp-alert.err{background:#fee2e2;color:#991b1b}
.cp-row{background:#f8fafc;border:1px solid #e5e7eb;border-radius:18px;padding:14px;display:grid;grid-template-columns:54px 1fr auto;gap:14px;align-items:center;margin-bottom:12px}
.cp-icon{width:54px;height:54px;border-radius:16px;background:var(--cor-principal);color:white;display:grid;place-items:center;font-size:12px;font-weight:900}
.cp-row h4{margin:0 0 5px;color:#11151B}.cp-row p{margin:0;color:#64748b;font-weight:800;font-size:13px}
.badge{display:inline-flex;padding:5px 8px;border-radius:999px;background:#eef2ff;color:#242A30;font-size:11px;font-weight:900;margin-right:5px}.badge.off{background:#fee2e2;color:#991b1b}

.cp-anexos-box{background:#f8fafc;border:1px solid #e5e7eb;border-radius:18px;padding:14px;margin-top:6px}
.cp-anexos-box strong{display:block;color:#11151B;margin-bottom:10px}
.cp-anexo-item{display:flex;justify-content:space-between;align-items:center;gap:10px;background:white;border:1px solid #e5e7eb;border-radius:14px;padding:10px;margin-bottom:8px}
.cp-anexo-item span{font-weight:800;color:#475569;font-size:13px;word-break:break-word}
.cp-anexo-item a{font-size:12px}
.cp-file-help{display:block;color:#64748b;font-weight:800;font-size:12px;margin-top:-8px}

@media(max-width:1100px){.cp-grid-admin,.cp-admin-hero{grid-template-columns:1fr}.cp-row{grid-template-columns:1fr}}
@media(max-width:700px){.cp-form-grid{grid-template-columns:1fr}}
</style>

<div class="cp-admin-hero">
    <div>
        <span class="cp-kicker">Transparência</span>
        <h2>Procedimentos Concursais</h2>
        <p>Gerir procedimentos, contratos, documentos, estados e anexos públicos.</p>
    </div>
    <a class="btn" href="../procedimentos-concursais.php" target="_blank">Ver página pública</a>
</div>

<?php if(isset($_GET['ok'])): ?><div class="cp-alert ok">Procedimento guardado com sucesso.</div><?php endif; ?>
<?php if($erro): ?><div class="cp-alert err"><?= htmlspecialchars($erro) ?></div><?php endif; ?>

<div class="cp-grid-admin">
    <div class="cp-card-admin">
        <h3><?= $editar ? 'Editar procedimento' : 'Novo procedimento' ?></h3>
        <form method="POST" enctype="multipart/form-data" class="cp-form">
            <input type="hidden" name="id" value="<?= htmlspecialchars($editar['id'] ?? 0) ?>">

            <div class="cp-form-grid">
                <div><label>Título *</label><input type="text" name="titulo" value="<?= htmlspecialchars($editar['titulo'] ?? '') ?>" required></div>
                <div><label>Estado</label><select name="estado"><?php foreach($estados as $val => $label): ?><option value="<?= htmlspecialchars($val) ?>" <?= (($editar['estado'] ?? 'rascunho') === $val) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select></div>
            </div>
            <div class="cp-form-grid">
                <div>
                    <label>Carreira</label>
                    <select name="carreira" id="cp-carreira" onchange="cpAtualizarCategorias()">
                        <option value="">— Escolher —</option>
                        <?php foreach($carreiras as $val => $label): ?><option value="<?= htmlspecialchars($val) ?>" <?= (($editar['carreira'] ?? '') === $val) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label>Categoria</label>
                    <select name="categoria" id="cp-categoria"></select>
                </div>
            </div>
            <div class="cp-form-grid">
                <div><label>Área funcional</label><input type="text" name="area_funcional" value="<?= htmlspecialchars($editar['area_funcional'] ?? '') ?>"></div>
                <div><label>Vagas (número de postos)</label><input type="number" min="1" name="vagas" value="<?= htmlspecialchars($editar['vagas'] ?? '') ?>"></div>
            </div>
            <div><label>Tipo de vínculo</label><select name="tipo_vinculo"><option value="">— Escolher —</option><?php foreach($tiposVinculo as $val => $label): ?><option value="<?= htmlspecialchars($val) ?>" <?= (($editar['tipo_vinculo'] ?? '') === $val) ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option><?php endforeach; ?></select></div>
            <div><label>Descrição</label><textarea name="descricao"><?= htmlspecialchars($editar['descricao'] ?? '') ?></textarea></div>
            <div><label>Requisitos</label><textarea name="requisitos"><?= htmlspecialchars($editar['requisitos'] ?? '') ?></textarea></div>
            <div class="cp-form-grid">
                <div><label>Data de publicação</label><input type="date" name="data_publicacao" value="<?= htmlspecialchars($editar['data_publicacao'] ?? date('Y-m-d')) ?>"></div>
                <div><label>Início das candidaturas</label><input type="date" name="inicio_candidaturas" value="<?= htmlspecialchars($editar['inicio_candidaturas'] ?? '') ?>"></div>
                <div><label>Fim das candidaturas</label><input type="date" name="fim_candidaturas" value="<?= htmlspecialchars($editar['fim_candidaturas'] ?? '') ?>"></div>
            </div>
            <div>
                <label>Ficheiros / Anexos</label>
                <input type="file" name="ficheiros[]" multiple accept=".pdf,.doc,.docx,.xls,.xlsx,.jpg,.jpeg,.png,.webp">
                <small class="cp-file-help">Pode selecionar vários ficheiros ao mesmo tempo (ex.: Aviso de Abertura, Ata).</small>
            </div>
            <?php if($editar && !empty($anexosEditar)): ?>
                <div class="cp-anexos-box">
                    <strong>Anexos atuais</strong>

                    <?php foreach($anexosEditar as $a): ?>
                        <div class="cp-anexo-item">
                            <span><i class="bi bi-paperclip"></i> <?= htmlspecialchars($a['ficheiro_original'] ?: $a['ficheiro']) ?></span>

                            <div class="cp-actions">
                                <a class="btn secondary" href="../uploads/contratacao-publica/<?= htmlspecialchars($a['ficheiro']) ?>" target="_blank">Ver</a>
                                <a class="btn danger" href="procedimentos-concursais.php?apagar_anexo=<?= (int)$a['id'] ?>" onclick="return confirm('Apagar este anexo?')">Apagar</a>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
            <div class="cp-checks"><label><input type="checkbox" name="ativo" <?= !isset($editar['ativo']) || !empty($editar['ativo']) ? 'checked' : '' ?>> Ativo</label></div>
            <div class="cp-actions">
                <button class="btn" type="submit"><?= $editar ? 'Guardar alterações' : 'Criar procedimento' ?></button>
                <?php if($editar): ?><a class="btn secondary" href="procedimentos-concursais.php">Cancelar</a><?php endif; ?>
            </div>
        </form>
    </div>

    <div class="cp-card-admin">
        <h3>Procedimentos existentes</h3>
        <?php foreach($lista as $p): ?>
            <div class="cp-row">
                <div class="cp-icon">PDF</div>
                <div>
                    <h4><?= htmlspecialchars($p['titulo']) ?></h4>
                    <p>
                        <span class="badge"><?= htmlspecialchars($estados[$p['estado']] ?? $p['estado'] ?? '—') ?></span>
                        <?php if(!empty($p['carreira'])): ?><span class="badge"><?= htmlspecialchars($carreiras[$p['carreira']] ?? $p['carreira']) ?></span><?php endif; ?>
                        <?php if(!empty($p['categoria'])): ?><span class="badge"><?= htmlspecialchars(($categoriasPorCarreira[$p['carreira']] ?? [])[$p['categoria']] ?? $p['categoria']) ?></span><?php endif; ?>
                        <?php if(!empty($p['vagas'])): ?><span class="badge"><?= (int)$p['vagas'] ?> vaga(s)</span><?php endif; ?>
                        <?php if(!empty($p['data_publicacao'])): ?><span class="badge"><?= date('d/m/Y', strtotime($p['data_publicacao'])) ?></span><?php endif; ?>
                        <?php if(!$p['ativo']): ?><span class="badge off">Inativo</span><?php endif; ?>
                        <span class="badge"><?= count($anexosPorProcedimento[(int)$p['id']] ?? []) ?> anexo(s)</span>
                    </p>
                </div>
                <div class="cp-actions">
                    <a class="btn secondary" href="procedimentos-concursais.php?editar=<?= (int)$p['id'] ?>">Editar</a>
                    <a class="btn secondary" href="procedimentos-concursais.php?toggle=<?= (int)$p['id'] ?>"><?= $p['ativo'] ? 'Ocultar' : 'Mostrar' ?></a>
                    <a class="btn danger" href="procedimentos-concursais.php?apagar=<?= (int)$p['id'] ?>" onclick="return confirm('Apagar este procedimento e todos os anexos?')">Apagar</a>
                </div>
            </div>
        <?php endforeach; ?>
        <?php if(empty($lista)): ?><p>Ainda não existem procedimentos.</p><?php endif; ?>
    </div>
</div>

<script>
const cpCategoriasPorCarreira = <?= json_encode($categoriasPorCarreira, JSON_UNESCAPED_UNICODE) ?>;
const cpCategoriaAtual = <?= json_encode($editar['categoria'] ?? '', JSON_UNESCAPED_UNICODE) ?>;

function cpAtualizarCategorias() {
    const carreira = document.getElementById('cp-carreira').value;
    const select = document.getElementById('cp-categoria');
    const categorias = cpCategoriasPorCarreira[carreira] || {};

    while (select.firstChild) select.removeChild(select.firstChild);

    const optVazia = document.createElement('option');
    optVazia.value = '';
    optVazia.textContent = '— Escolher —';
    select.appendChild(optVazia);

    for (const valor in categorias) {
        const opt = document.createElement('option');
        opt.value = valor;
        opt.textContent = categorias[valor];
        if (valor === cpCategoriaAtual) opt.selected = true;
        select.appendChild(opt);
    }
    select.disabled = carreira === '';
}

cpAtualizarCategorias();
</script>

<?php require_once "includes/footer.php"; ?>
