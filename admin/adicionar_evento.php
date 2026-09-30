<?php
require_once "includes/auth.php";
require_once "../includes/db.php";
require_once "../includes/galeria.php";
require_once "../includes/categorias.php";

$errosFotos = [];

function uploadImagemEvento($campo) {
    if (empty($_FILES[$campo]['name'])) {
        return null;
    }

    $permitidas = ['jpg', 'jpeg', 'png', 'webp'];
    $ext = strtolower(pathinfo($_FILES[$campo]['name'], PATHINFO_EXTENSION));

    if (!in_array($ext, $permitidas)) {
        return null;
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

    return $imagem;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $titulo = trim($_POST['titulo'] ?? '');
    $descricao = trim($_POST['descricao'] ?? '');
    $data_evento = str_replace('T', ' ', trim($_POST['data_evento'] ?? ''));
    $data_fim = !empty($_POST['data_fim']) ? str_replace('T', ' ', trim($_POST['data_fim'])) : null;
    $imagem = uploadImagemEvento('imagem');
    $focoX = max(0, min(100, (int)($_POST['foco_x'] ?? 50)));
    $focoY = max(0, min(100, (int)($_POST['foco_y'] ?? 50)));
    $categoria = in_array($_POST['categoria'] ?? '', $GLOBALS['CATEGORIAS_CONTEUDO'], true) ? $_POST['categoria'] : null;
    $local = trim($_POST['local'] ?? '') !== '' ? trim($_POST['local']) : null;
    $latitude = trim($_POST['latitude'] ?? '') !== '' ? trim($_POST['latitude']) : null;
    $longitude = trim($_POST['longitude'] ?? '') !== '' ? trim($_POST['longitude']) : null;

    $repetir = $_POST['repetir'] ?? 'nao';
    $repeticoes = max(1, min(60, (int)($_POST['repeticoes'] ?? 1)));

    $inscricoesAtivas = isset($_POST['inscricoes_ativas']) ? 1 : 0;
    $inscricoesVagas = trim($_POST['inscricoes_vagas'] ?? '') !== '' ? max(1, (int)$_POST['inscricoes_vagas']) : null;
    $inscricoesAte = !empty($_POST['inscricoes_ate']) ? str_replace('T', ' ', trim($_POST['inscricoes_ate'])) : null;

    $stmt = $pdo->prepare("INSERT INTO eventos (titulo, descricao, data_evento, data_fim, imagem, imagem_foco_x, imagem_foco_y, categoria, local, latitude, longitude, inscricoes_ativas, inscricoes_vagas, inscricoes_ate) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$titulo, $descricao, $data_evento, $data_fim, $imagem, $focoX, $focoY, $categoria, $local, $latitude, $longitude, $inscricoesAtivas, $inscricoesVagas, $inscricoesAte]);
    $eventoId = (int)$pdo->lastInsertId();

    // Campos extra da inscrição, criados já nesta página (sem precisar de guardar
    // o evento primeiro): arrays paralelos vindos das linhas dinâmicas do formulário.
    $camposLabel = $_POST['campo_extra_label'] ?? [];
    $camposTipo = $_POST['campo_extra_tipo'] ?? [];
    $camposObrigatorio = $_POST['campo_extra_obrigatorio'] ?? [];
    $insCampo = $pdo->prepare("INSERT INTO eventos_campos_extra (evento_id, label, tipo, obrigatorio, ordem) VALUES (?, ?, ?, ?, ?)");
    $ordemCampo = 1;
    foreach ($camposLabel as $i => $label) {
        $label = trim($label);
        if ($label === '') continue;
        $tipo = ($camposTipo[$i] ?? 'texto') === 'checkbox' ? 'checkbox' : 'texto';
        $obrigatorio = isset($camposObrigatorio[$i]) ? 1 : 0;
        $insCampo->execute([$eventoId, $label, $tipo, $obrigatorio, $ordemCampo++]);
    }

    // Fotografias -> Galeria, num álbum com o título do evento. O álbum fica ligado
    // ao evento BASE (as repetições abaixo são cópias e partilham o mesmo álbum).
    if (!empty($_FILES['fotos']['name'][0])) {
        $albumId = albumDaOrigem($pdo, 'evento', $eventoId, $titulo);
        [$nFotos, $errosFotos] = guardarImagensNoAlbum($pdo, $albumId, $_FILES['fotos']);
    }

    // Repetições: gera cópias reais do evento, deslocando a data (anual/mensal/semanal)
    $intervalos = ['semanal' => 'P7D', 'mensal' => 'P1M', 'anual' => 'P1Y'];
    if (isset($intervalos[$repetir]) && $repeticoes > 1 && $data_evento !== '') {
        try {
            $dt = new DateTime($data_evento);
            $dtFim = $data_fim ? new DateTime($data_fim) : null;
            $iv = new DateInterval($intervalos[$repetir]);
            for ($i = 1; $i < $repeticoes; $i++) {
                $dt->add($iv);
                $fimCopia = null;
                if ($dtFim) { $dtFim->add($iv); $fimCopia = $dtFim->format('Y-m-d H:i:s'); }
                $stmt->execute([$titulo, $descricao, $dt->format('Y-m-d H:i:s'), $fimCopia, $imagem, $focoX, $focoY, $categoria, $local, $latitude, $longitude, $inscricoesAtivas, $inscricoesVagas, null]);
                $idCopia = (int)$pdo->lastInsertId();

                foreach ($camposLabel as $ci => $labelCopia) {
                    $labelCopia = trim($labelCopia);
                    if ($labelCopia === '') continue;
                    $tipoCopia = ($camposTipo[$ci] ?? 'texto') === 'checkbox' ? 'checkbox' : 'texto';
                    $obrigatorioCopia = isset($camposObrigatorio[$ci]) ? 1 : 0;
                    $insCampo->execute([$idCopia, $labelCopia, $tipoCopia, $obrigatorioCopia, $ci + 1]);
                }
            }
        } catch (Exception $e) {
            // data inválida: fica só o evento base
        }
    }

    header("Location: eventos.php?fb_novo=" . $eventoId);
    exit;
}

$adminPageTitle = "Adicionar Evento";
$adminActive = "eventos";
require_once "includes/header.php";
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
.evento-campos-extra-builder{
    margin-top:14px;
    padding-top:14px;
    border-top:1px dashed #cbd5e1;
}
.campo-extra-linha{
    display:grid;
    grid-template-columns:1.6fr 1fr auto auto;
    gap:10px;
    align-items:center;
    background:white;
    border:1px solid #e5e7eb;
    border-radius:12px;
    padding:10px;
    margin-bottom:10px;
}
.campo-extra-linha input[type=text]{
    border:1px solid #dbe4ee;
    border-radius:10px;
    padding:9px 11px;
    font-weight:700;
    width:100%;
    box-sizing:border-box;
}
.campo-extra-linha select{
    border:1px solid #dbe4ee;
    border-radius:10px;
    padding:9px 11px;
    font-weight:700;
    width:100%;
    box-sizing:border-box;
}
.campo-extra-linha label{
    display:flex;
    align-items:center;
    gap:6px;
    font-weight:800;
    color:#11151B;
    font-size:13px;
    white-space:nowrap;
}
@media(max-width:700px){.campo-extra-linha{grid-template-columns:1fr 1fr;}}
</style>

<form method="POST" enctype="multipart/form-data" class="evento-admin-form">
    <input type="text" name="titulo" placeholder="Título do evento" required>
    <textarea name="descricao" placeholder="Descrição do evento" required></textarea>
    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin:4px 0;">
        <div>
            <label style="display:block;font-weight:800;color:#11151B;margin-bottom:6px;">Data de início *</label>
            <input type="datetime-local" name="data_evento" required style="width:100%;">
        </div>
        <div>
            <label style="display:block;font-weight:800;color:#11151B;margin-bottom:6px;">Data de fim (opcional)</label>
            <input type="datetime-local" name="data_fim" style="width:100%;">
        </div>
    </div>
    <p style="color:#6b7280;font-weight:700;font-size:13px;margin:2px 0 8px;">Para um evento de vários dias, preenche a data de fim (ex.: festas de 12 a 15 de agosto).</p>

    <label style="display:block;font-weight:800;color:#11151B;margin:12px 0 6px;">Categoria</label>
    <select name="categoria" style="width:100%;">
        <option value="">Sem categoria</option>
        <?php foreach ($GLOBALS['CATEGORIAS_CONTEUDO'] as $cat): ?>
            <option value="<?= htmlspecialchars($cat) ?>"><?= htmlspecialchars($cat) ?></option>
        <?php endforeach; ?>
    </select>

    <label style="display:block;font-weight:800;color:#11151B;margin:12px 0 6px;">Local (opcional)</label>
    <input type="text" name="local" placeholder="Ex.: Largo da Igreja">
    <p style="color:#6b7280;font-weight:700;font-size:13px;margin:-8px 0 12px;">Se preencheres as coordenadas abaixo, o evento aparece também no <a href="/mapa.php" target="_blank">mapa da freguesia</a>.</p>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin:0 0 12px;">
        <div>
            <label style="display:block;font-weight:800;color:#11151B;margin-bottom:6px;">Latitude</label>
            <input type="text" name="latitude" placeholder="38.805">
        </div>
        <div>
            <label style="display:block;font-weight:800;color:#11151B;margin-bottom:6px;">Longitude</label>
            <input type="text" name="longitude" placeholder="-7.46">
        </div>
    </div>

    <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;margin:12px 0 4px;">
        <div>
            <label style="display:block;font-weight:800;color:#11151B;margin-bottom:6px;">Repetir</label>
            <select name="repetir" style="width:100%;">
                <option value="nao">Não repetir</option>
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
    <p style="color:#6b7280;font-weight:700;font-size:13px;margin:2px 0 8px;">Ex.: "Todos os anos" + 5 cria o evento em 5 anos seguidos (ideal para Natal, aniversário da freguesia, etc.).</p>

    <div class="evento-upload-box">
        <strong>Inscrições</strong>
        <label style="display:flex;align-items:center;gap:8px;font-weight:800;color:#11151B;margin:6px 0 12px;">
            <input type="checkbox" name="inscricoes_ativas" style="width:auto;">
            Permitir inscrições neste evento (ex.: excursões, workshops)
        </label>
        <div style="display:grid;grid-template-columns:1fr 1fr;gap:14px;">
            <div>
                <label style="display:block;font-weight:800;color:#11151B;margin-bottom:6px;">Limite de vagas</label>
                <input type="number" name="inscricoes_vagas" min="1" placeholder="Sem limite" style="width:100%;">
            </div>
            <div>
                <label style="display:block;font-weight:800;color:#11151B;margin-bottom:6px;">Inscrições até</label>
                <input type="datetime-local" name="inscricoes_ate" style="width:100%;">
            </div>
        </div>
        <p style="color:#6b7280;font-weight:700;font-size:13px;margin:8px 0 0;">Deixe os campos vazios para não limitar vagas nem prazo (fecha automaticamente na data do evento).</p>

        <div class="evento-campos-base">
            <strong>Campos que aparecem sempre no formulário público</strong>
            <span>Nome completo (obrigatório) · Email ou Telefone (pelo menos um) · Nº de pessoas · Observações (opcional)</span>
        </div>

        <div class="evento-campos-extra-builder">
            <strong>Campos extra (opcional)</strong>
            <p style="color:#6b7280;font-weight:700;font-size:13px;margin:4px 0 10px;">Ex.: "Precisa de almoço?", "Restrição alimentar". Ficam gravados por cada inscrição e visíveis na lista de inscritos.</p>
            <div id="camposExtraLista"></div>
            <button type="button" class="btn secondary" id="btnAdicionarCampoExtra"><i class="bi bi-plus-lg"></i> Adicionar campo</button>
        </div>
    </div>

    <div class="evento-upload-box">
        <strong>Imagem do evento</strong>
        <input type="file" name="imagem" id="imagemCapaInput" accept="image/*">

        <div class="foco-picker-wrap" id="focoPickerWrap" hidden>
            <p style="color:#6b7280;font-weight:700;font-size:13px;margin:8px 0 0;">Clique na imagem para marcar o ponto mais importante (ex.: uma cara). Esse ponto mantém-se sempre visível, mesmo quando a imagem é cortada em proporções diferentes (cartão de listagem vs. topo do evento).</p>
            <div class="foco-picker">
                <img id="focoPreviewImg" alt="Pré-visualização da imagem do evento">
                <div class="foco-marker" id="focoMarker"></div>
            </div>
            <input type="hidden" name="foco_x" id="focoX" value="50">
            <input type="hidden" name="foco_y" id="focoY" value="50">
        </div>
    </div>

    <div class="evento-upload-box">
        <strong>Fotografias (pode escolher várias)</strong>
        <input type="file" name="fotos[]" accept="image/*" multiple>
        <p style="color:#6b7280;font-weight:700;font-size:13px;margin:8px 0 0;">
            Vão automaticamente para a <strong>Galeria</strong>, agrupadas num álbum com o título do evento.
        </p>
    </div>

    <button class="btn">Guardar evento</button>
</form>

<script src="assets/foco-imagem.js"></script>
<script>
// Ligado em separado do seletor de foco da imagem (abaixo): se aquele falhar
// por algum motivo, isto continua a funcionar de qualquer forma.
document.addEventListener("DOMContentLoaded", function () {
    var lista = document.getElementById("camposExtraLista");
    document.getElementById("btnAdicionarCampoExtra").addEventListener("click", function () {
        var linha = document.createElement("div");
        linha.className = "campo-extra-linha";

        var inputLabel = document.createElement("input");
        inputLabel.type = "text";
        inputLabel.name = "campo_extra_label[]";
        inputLabel.placeholder = 'Ex.: Precisa de almoço?';

        var selectTipo = document.createElement("select");
        selectTipo.name = "campo_extra_tipo[]";
        var optTexto = document.createElement("option");
        optTexto.value = "texto";
        optTexto.textContent = "Texto";
        var optCheckbox = document.createElement("option");
        optCheckbox.value = "checkbox";
        optCheckbox.textContent = "Checkbox (sim/não)";
        selectTipo.appendChild(optTexto);
        selectTipo.appendChild(optCheckbox);

        var labelObrigatorio = document.createElement("label");
        var inputObrigatorio = document.createElement("input");
        inputObrigatorio.type = "checkbox";
        inputObrigatorio.name = "campo_extra_obrigatorio[]";
        inputObrigatorio.style.width = "auto";
        labelObrigatorio.appendChild(inputObrigatorio);
        labelObrigatorio.appendChild(document.createTextNode(" Obrigatório"));

        var btnRemover = document.createElement("button");
        btnRemover.type = "button";
        btnRemover.className = "btn danger";
        btnRemover.textContent = "Remover";
        btnRemover.addEventListener("click", function () { linha.remove(); });

        linha.appendChild(inputLabel);
        linha.appendChild(selectTipo);
        linha.appendChild(labelObrigatorio);
        linha.appendChild(btnRemover);
        lista.appendChild(linha);
        inputLabel.focus();
    });
});

document.addEventListener("DOMContentLoaded", function () {
    initFocoPicker({
        fileInputId: "imagemCapaInput",
        previewWrapId: "focoPickerWrap",
        previewImgId: "focoPreviewImg",
        markerId: "focoMarker",
        hiddenXId: "focoX",
        hiddenYId: "focoY"
    });
});
</script>

<?php require_once "includes/footer.php"; ?>
