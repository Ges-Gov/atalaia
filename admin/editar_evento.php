<?php
require_once "includes/auth.php";
require_once "../includes/db.php";
require_once "../includes/galeria.php";
require_once "../includes/categorias.php";

$errosFotos = [];

$id = $_GET['id'] ?? null;

$stmt = $pdo->prepare("SELECT * FROM eventos WHERE id = ?");
$stmt->execute([$id]);
$evento = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$evento) {
    die("Evento não encontrado.");
}

function uploadImagemEvento($campo, $imagemAtual = '') {
    if (empty($_FILES[$campo]['name'])) {
        return $imagemAtual;
    }

    $permitidas = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $permitidas)) {
        return $imagemAtual;
    }

    if (!is_dir("../assets/img")) {
        mkdir("../assets/img", 0777, true);
    }

    $base = pathinfo($_FILES[$campo]['name'], PATHINFO_FILENAME);
    $base = preg_replace('/[^a-zA-Z0-9\-_]/', '-', $base);
    $base = trim($base, '-');

    if ($base === '') {
        $base = 'evento';
    }

    $imagem = 'evento-' . $base . '-' . time() . '.' . $ext;
    move_uploaded_file($_FILES[$campo]['tmp_name'], "../assets/img/" . $imagem);

    if (!empty($imagemAtual) && file_exists("../assets/img/" . $imagemAtual)) {
        @unlink("../assets/img/" . $imagemAtual);
    }

    return $imagem;
}

// Apagar uma fotografia do álbum deste evento
if (isset($_GET['apagar_foto'])) {
    apagarImagemDaGaleria($pdo, (int)$_GET['apagar_foto']);
    header("Location: editar_evento.php?id=" . (int)$id);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $data_evento = str_replace('T', ' ', trim($_POST['data_evento'] ?? ''));
    $data_fim = !empty($_POST['data_fim']) ? str_replace('T', ' ', trim($_POST['data_fim'])) : null;
    $imagem = uploadImagemEvento('imagem', $evento['imagem']);
    $focoX = max(0, min(100, (int)($_POST['foco_x'] ?? 50)));
    $focoY = max(0, min(100, (int)($_POST['foco_y'] ?? 50)));
    $categoria = in_array($_POST['categoria'] ?? '', $GLOBALS['CATEGORIAS_CONTEUDO'], true) ? $_POST['categoria'] : null;
    $local = trim($_POST['local'] ?? '') !== '' ? trim($_POST['local']) : null;
    $latitude = trim($_POST['latitude'] ?? '') !== '' ? trim($_POST['latitude']) : null;
    $longitude = trim($_POST['longitude'] ?? '') !== '' ? trim($_POST['longitude']) : null;

    $inscricoesAtivas = isset($_POST['inscricoes_ativas']) ? 1 : 0;
    $inscricoesVagas = trim($_POST['inscricoes_vagas'] ?? '') !== '' ? max(1, (int)$_POST['inscricoes_vagas']) : null;
    $inscricoesAte = !empty($_POST['inscricoes_ate']) ? str_replace('T', ' ', trim($_POST['inscricoes_ate'])) : null;

    $stmt = $pdo->prepare("UPDATE eventos SET titulo = ?, descricao = ?, data_evento = ?, data_fim = ?, imagem = ?, imagem_foco_x = ?, imagem_foco_y = ?, categoria = ?, local = ?, latitude = ?, longitude = ?, inscricoes_ativas = ?, inscricoes_vagas = ?, inscricoes_ate = ? WHERE id = ?");
    $stmt->execute([$titulo, $descricao, $data_evento, $data_fim, $imagem, $focoX, $focoY, $categoria, $local, $latitude, $longitude, $inscricoesAtivas, $inscricoesVagas, $inscricoesAte, $id]);

    // Fotografias -> Galeria. Se o evento já tinha álbum, é reaproveitado e o nome
    // acompanha o novo título; se não tinha e há fotos novas, é criado agora.
    $temAlbum = $pdo->prepare("SELECT id FROM galeria_albuns WHERE origem = 'evento' AND origem_id = ?");
    $temAlbum->execute([$id]);
    $albumExistente = $temAlbum->fetchColumn();

    if (!empty($_FILES['fotos']['name'][0]) || $albumExistente) {
        $albumId = albumDaOrigem($pdo, 'evento', (int)$id, $titulo);

        if (!empty($_FILES['fotos']['name'][0])) {
            [$nFotos, $errosFotos] = guardarImagensNoAlbum($pdo, $albumId, $_FILES['fotos']);
        }
    }

    // Replicar: cria NOVAS cópias futuras a partir deste evento (não altera o atual)
    $repetir = $_POST['repetir'] ?? 'nao';
    $repeticoes = max(1, min(60, (int)($_POST['repeticoes'] ?? 1)));
    $intervalos = ['semanal' => 'P7D', 'mensal' => 'P1M', 'anual' => 'P1Y'];
    if (isset($intervalos[$repetir]) && $repeticoes > 1 && $data_evento !== '') {
        try {
            $ins = $pdo->prepare("INSERT INTO eventos (titulo, descricao, data_evento, data_fim, imagem, imagem_foco_x, imagem_foco_y, categoria, local, latitude, longitude, inscricoes_ativas, inscricoes_vagas, inscricoes_ate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $dt = new DateTime($data_evento);
            $dtFim = $data_fim ? new DateTime($data_fim) : null;
            $iv = new DateInterval($intervalos[$repetir]);
            for ($i = 1; $i < $repeticoes; $i++) {
                $dt->add($iv);
                $fimCopia = null;
                if ($dtFim) { $dtFim->add($iv); $fimCopia = $dtFim->format('Y-m-d H:i:s'); }
                $ins->execute([$titulo, $descricao, $dt->format('Y-m-d H:i:s'), $fimCopia, $imagem, $focoX, $focoY, $categoria, $local, $latitude, $longitude, $inscricoesAtivas, $inscricoesVagas, null]);
            }
        } catch (Exception $e) {
            // data inválida: só guarda as alterações
        }
    }

    header("Location: eventos.php");
    exit;
}

$adminPageTitle = "Editar Evento";
$adminActive = "eventos";
require_once "includes/header.php";

$dataInput = '';
if (!empty($evento['data_evento'])) {
    $dataInput = date('Y-m-d\TH:i', strtotime($evento['data_evento']));
}
$dataFimInput = '';
if (!empty($evento['data_fim'])) {
    $dataFimInput = date('Y-m-d\TH:i', strtotime($evento['data_fim']));
}
$inscricoesAteInput = '';
if (!empty($evento['inscricoes_ate'])) {
    $inscricoesAteInput = date('Y-m-d\TH:i', strtotime($evento['inscricoes_ate']));
}
$stmtInsc = $pdo->prepare("SELECT COUNT(*) n, COALESCE(SUM(num_pessoas),0) pessoas FROM eventos_inscricoes WHERE evento_id = ?");
$stmtInsc->execute([$id]);
$resumoInscricoes = $stmtInsc->fetch(PDO::FETCH_ASSOC);
?>

<style>
.evento-admin-form{
    background:white;
    border-radius:24px;
    padding:26px;
    box-shadow:0 16px 40px rgba(0,0,0,.08);
    border:1px solid #eef2f7;
}
.evento-admin-form input,
.evento-admin-form textarea{
    width:100%;
    box-sizing:border-box;
}
.evento-preview{
    width:320px;
    height:190px;
    object-fit:cover;
    border-radius:18px;
    box-shadow:0 14px 34px rgba(0,0,0,.14);
    margin:12px 0;
    display:block;
}
.evento-upload-box{
    border:2px dashed #cbd5e1;
    border-radius:18px;
    padding:24px;
    background:#f8fafc;
    margin:12px 0;
}
.evento-upload-box strong{
    display:block;
    color:#11151B;
    margin-bottom:8px;
}
.evento-campos-base{
    margin-top:14px;
    background:#eef2ff;
    border:1px solid #dbe4ee;
    border-radius:14px;
    padding:12px 14px;
}
.evento-campos-base strong{
    display:block;
    color:#11151B;
    font-size:13px;
    margin-bottom:4px;
}
.evento-campos-base span{
    display:block;
    color:#334155;
    font-weight:700;
    font-size:13px;
}
</style>

<form method="POST" enctype="multipart/form-data" class="evento-admin-form">
    <input type="text" name="titulo" value="<?= htmlspecialchars($evento['titulo']) ?>" required>

    <textarea name="descricao" required><?= htmlspecialchars($evento['descricao']) ?></textarea>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin:4px 0;">
        <div>
            <label style="display:block;font-weight:800;color:#11151B;margin-bottom:6px;">Data de início *</label>
            <input type="datetime-local" name="data_evento" value="<?= htmlspecialchars($dataInput) ?>" required style="width:100%;">
        </div>
        <div>
            <label style="display:block;font-weight:800;color:#11151B;margin-bottom:6px;">Data de fim (opcional)</label>
            <input type="datetime-local" name="data_fim" value="<?= htmlspecialchars($dataFimInput) ?>" style="width:100%;">
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin:12px 0 4px;">
        <div>
            <label style="display:block;font-weight:800;color:#11151B;margin-bottom:6px;">Replicar (cria novas cópias)</label>
            <select name="repetir" style="width:100%;">
                <option value="nao">Não replicar</option>
                <option value="semanal">Todas as semanas</option>
                <option value="mensal">Todos os meses</option>
                <option value="anual">Todos os anos</option>
            </select>
        </div>
        <div>
            <label style="display:block;font-weight:800;color:#11151B;margin-bottom:6px;">Quantas vezes (total)</label>
            <input type="number" name="repeticoes" value="1" min="1" max="60" style="width:100%;">
        </div>
    </div>
    <p style="color:#6b7280;font-weight:700;font-size:13px;margin:2px 0 8px;">Replicar cria NOVOS eventos no futuro a partir deste (não altera este). Deixa em "Não replicar" para só guardar as alterações.</p>

    <label style="display:block;font-weight:800;color:#11151B;margin:12px 0 6px;">Categoria</label>
    <select name="categoria" style="width:100%;">
        <option value="">Sem categoria</option>
        <?php foreach ($GLOBALS['CATEGORIAS_CONTEUDO'] as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>" <?= ($evento['categoria'] ?? '') === $cat ? 'selected' : '' ?>><?= htmlspecialchars($cat) ?></option>
        <?php endforeach; ?>
    </select>

    <label style="display:block;font-weight:800;color:#11151B;margin:12px 0 6px;">Local (opcional)</label>
    <input type="text" name="local" value="<?= htmlspecialchars($evento['local'] ?? '') ?>" placeholder="Ex.: Largo da Igreja">
    <p style="color:#6b7280;font-weight:700;font-size:13px;margin:-8px 0 12px;">Se preencheres as coordenadas abaixo, o evento aparece também no <a href="/mapa.php" target="_blank">mapa da freguesia</a>.</p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin:0 0 12px;">
        <div>
            <label style="display:block;font-weight:800;color:#11151B;margin-bottom:6px;">Latitude</label>
            <input type="text" name="latitude" value="<?= htmlspecialchars($evento['latitude'] ?? '') ?>" placeholder="38.805">
        </div>
        <div>
            <label style="display:block;font-weight:800;color:#11151B;margin-bottom:6px;">Longitude</label>
            <input type="text" name="longitude" value="<?= htmlspecialchars($evento['longitude'] ?? '') ?>" placeholder="-7.46">
        </div>
    </div>

    <div class="evento-upload-box">
        <strong>Inscrições</strong>
        <label style="display:flex;align-items:center;gap:8px;font-weight:800;color:#11151B;margin:6px 0 12px;">
            <input type="checkbox" name="inscricoes_ativas" style="width:auto;" <?= !empty($evento['inscricoes_ativas']) ? 'checked' : '' ?>>
            Permitir inscrições neste evento (ex.: excursões, workshops)
        </label>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div>
                <label style="display:block;font-weight:800;color:#11151B;margin-bottom:6px;">Limite de vagas</label>
                <input type="number" name="inscricoes_vagas" min="1" placeholder="Sem limite" value="<?= htmlspecialchars($evento['inscricoes_vagas'] ?? '') ?>" style="width:100%;">
            </div>
            <div>
                <label style="display:block;font-weight:800;color:#11151B;margin-bottom:6px;">Inscrições até</label>
                <input type="datetime-local" name="inscricoes_ate" value="<?= htmlspecialchars($inscricoesAteInput) ?>" style="width:100%;">
            </div>
        </div>
        <p style="color:#6b7280;font-weight:700;font-size:13px;margin:8px 0 0;">Deixe os campos vazios para não limitar vagas nem prazo (fecha automaticamente na data do evento).</p>

        <div class="evento-campos-base">
            <strong>Campos que aparecem sempre no formulário público</strong>
            <span>Nome completo (obrigatório) · Email ou Telefone (pelo menos um) · Nº de pessoas · Observações (opcional)</span>
        </div>

        <a class="btn secondary" href="evento_campos.php?evento_id=<?= (int)$id ?>" style="margin-top:10px;display:inline-flex;"><i class="bi bi-list-check"></i> Gerir campos extra da inscrição</a>

        <?php if ((int)$resumoInscricoes['n'] > 0): ?>
            <div style="margin-top:14px;padding:14px 16px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;display:flex;justify-content:space-between;align-items:center;flex-wrap:wrap;gap:10px;">
                <span style="font-weight:800;color:#11151B;">
                    <?= (int)$resumoInscricoes['n'] ?> inscrição(ões) · <?= (int)$resumoInscricoes['pessoas'] ?> pessoa(s)<?= !empty($evento['inscricoes_vagas']) ? ' de ' . (int)$evento['inscricoes_vagas'] . ' vagas' : '' ?>
                </span>
                <a class="btn secondary" href="evento_inscricoes.php?id=<?= (int)$id ?>">Ver inscrições</a>
            </div>
        <?php endif; ?>
    </div>

    <div class="evento-upload-box">
        <strong>Imagem do evento</strong>

        <?php if (!empty($evento['imagem'])): ?>
            <img class="evento-preview" src="../assets/img/<?= htmlspecialchars($evento['imagem']) ?>" alt="<?= htmlspecialchars($evento['titulo']) ?>">
        <?php endif; ?>

        <input type="file" name="imagem" id="imagemCapaInput" accept="image/*">

        <div class="foco-picker-wrap" id="focoPickerWrap" hidden>
            <p style="color:#6b7280;font-weight:700;font-size:13px;margin:8px 0 0;">Clique na imagem para marcar o ponto mais importante (ex.: uma cara). Esse ponto mantém-se sempre visível, mesmo quando a imagem é cortada em proporções diferentes (cartão de listagem vs. topo do evento).</p>
            <div class="foco-picker">
                <img id="focoPreviewImg" alt="Pré-visualização da imagem do evento">
                <div class="foco-marker" id="focoMarker"></div>
            </div>
            <input type="hidden" name="foco_x" id="focoX" value="<?= (int)$evento['imagem_foco_x'] ?>">
            <input type="hidden" name="foco_y" id="focoY" value="<?= (int)$evento['imagem_foco_y'] ?>">
        </div>
    </div>

    <div class="evento-upload-box">
        <strong>Fotografias (Galeria)</strong>

        <?php $fotosEvento = imagensDaOrigem($pdo, 'evento', (int)$id); ?>

        <?php if (!empty($fotosEvento)): ?>
            <div style="display:grid;grid-template-columns:repeat(auto-fill,minmax(140px,1fr));gap:10px;margin:10px 0;">
                <?php foreach ($fotosEvento as $f): ?>
                    <div style="border:1px solid #e5e7eb;border-radius:12px;padding:8px;background:#fff;">
                        <img src="<?= htmlspecialchars('..' . imagemGaleriaUrl($f['ficheiro'])) ?>" alt=""
                             style="width:100%;height:90px;object-fit:cover;border-radius:8px;display:block;margin-bottom:8px;">
                        <a class="btn danger" href="editar_evento.php?id=<?= (int)$id ?>&apagar_foto=<?= (int)$f['id'] ?>"
                           onclick="return confirm('Apagar esta fotografia?')">Apagar</a>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <input type="file" name="fotos[]" accept="image/*" multiple>
        <p style="color:#6b7280;font-weight:700;font-size:13px;margin:8px 0 0;">
            Vão para a <strong>Galeria</strong>, num álbum com o título do evento.
        </p>
    </div>

    <button class="btn">Guardar alterações</button>
</form>

<script src="assets/foco-imagem.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    initFocoPicker({
        fileInputId: "imagemCapaInput",
        previewWrapId: "focoPickerWrap",
        previewImgId: "focoPreviewImg",
        markerId: "focoMarker",
        hiddenXId: "focoX",
        hiddenYId: "focoY"
        <?php if (!empty($evento['imagem'])): ?>
        ,existingUrl: "../assets/img/<?= htmlspecialchars($evento['imagem'], ENT_QUOTES) ?>"
        ,initialX: <?= (int)$evento['imagem_foco_x'] ?>
        ,initialY: <?= (int)$evento['imagem_foco_y'] ?>
        <?php endif; ?>
    });
});
</script>

<?php require_once "includes/footer.php"; ?>
