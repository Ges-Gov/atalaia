<?php
// homepage_destaque.php
// Painel premium para gerir destaque homepage (slider/video)

require_once 'includes/header.php';
require_once '../includes/db.php';

$msg = '';
$erro = '';

$atual = $pdo->query("SELECT * FROM homepage_destaque ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $tipo = $_POST['tipo'] ?? 'slider';
    $titulo = $_POST['titulo'] ?? '';
    $subtitulo = $_POST['subtitulo'] ?? '';
    $video_url = $_POST['video_url'] ?? '';
    $botao_texto = $_POST['botao_texto'] ?? '';
    $botao_link = $_POST['botao_link'] ?? '';
    $ativo = isset($_POST['ativo']) ? 1 : 0;

    $video_ficheiro = $atual['video_ficheiro'] ?? '';

    if (!empty($_FILES['video_ficheiro']['name'])) {
        $erroUpload = $_FILES['video_ficheiro']['error'];

        if ($erroUpload === UPLOAD_ERR_INI_SIZE || $erroUpload === UPLOAD_ERR_FORM_SIZE) {
            $erro = 'O ficheiro de vídeo é demasiado grande para o limite de upload configurado no servidor (fala com o suporte de hosting para aumentar o "upload_max_filesize"/"post_max_size" no PHP). A configuração não foi guardada.';
        } elseif ($erroUpload !== UPLOAD_ERR_OK) {
            $erro = 'Não foi possível carregar o ficheiro de vídeo (código de erro ' . $erroUpload . '). A configuração não foi guardada.';
        } else {
            $pasta = '../uploads/homepage/';
            if (!is_dir($pasta)) {
                mkdir($pasta, 0777, true);
            }

            // A extensão TEM de ser validada. Sem isto, nada impedia enviar um "x.php"
            // para uploads/homepage/ — onde o servidor o executaria. E o nome do ficheiro
            // é gerado por nós, nunca aproveitado do que vem do browser.
            $ext = strtolower(pathinfo($_FILES['video_ficheiro']['name'], PATHINFO_EXTENSION));

            if (!in_array($ext, ['mp4', 'webm', 'ogg', 'mov'], true)) {
                $erro = 'Formato de vídeo inválido. Use MP4, WEBM, OGG ou MOV.';
            } else {
                $nome = 'destaque_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                $destino = $pasta . $nome;

                if (move_uploaded_file($_FILES['video_ficheiro']['tmp_name'], $destino)) {
                    $video_ficheiro = 'uploads/homepage/' . $nome;
                } else {
                    $erro = 'Não foi possível guardar o ficheiro de vídeo no servidor. A configuração não foi guardada.';
                }
            }
        }
    }

    if ($erro === '') {
        $stmt = $pdo->prepare("
            INSERT INTO homepage_destaque
            (tipo, titulo, subtitulo, video_url, video_ficheiro, botao_texto, botao_link, ativo)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        $stmt->execute([
            $tipo,
            $titulo,
            $subtitulo,
            $video_url,
            $video_ficheiro,
            $botao_texto,
            $botao_link,
            $ativo
        ]);

        $msg = 'Configuração guardada com sucesso!';
        $atual = $pdo->query("SELECT * FROM homepage_destaque ORDER BY id DESC LIMIT 1")->fetch(PDO::FETCH_ASSOC) ?: [];
    } else {
        // repõe os valores submetidos no formulário para não se perderem
        $atual = [
            'tipo' => $tipo, 'titulo' => $titulo, 'subtitulo' => $subtitulo,
            'video_url' => $video_url, 'video_ficheiro' => $video_ficheiro,
            'botao_texto' => $botao_texto, 'botao_link' => $botao_link, 'ativo' => $ativo,
        ];
    }
}
?>

<style>
.hp-box{
background:white;
padding:30px;
border-radius:24px;
box-shadow:0 18px 45px rgba(0,0,0,.08);
max-width:1100px;
margin:auto;
}
.hp-grid{
display:grid;
grid-template-columns:1fr 1fr;
gap:18px;
}
.hp-box input,
.hp-box select,
.hp-box textarea{
width:100%;
min-height:50px;
border:1px solid #dbe3ea;
border-radius:14px;
padding:12px 14px;
box-sizing:border-box;
}
.hp-box textarea{
min-height:140px;
}
.hp-box button{
background:#242A30;
color:white;
border:0;
padding:14px 22px;
border-radius:14px;
font-weight:800;
cursor:pointer;
}
.full{
grid-column:span 2;
}
.alerta{
background:#dcfce7;
color:#166534;
padding:14px;
border-radius:12px;
margin-bottom:20px;
font-weight:700;
}
@media(max-width:900px){
.hp-grid{
grid-template-columns:1fr;
}
.full{
grid-column:span 1;
}
}
</style>

<div class="hp-box">

<h1>Homepage Destaque</h1>

<?php if($msg): ?>
<div class="alerta"><?= $msg ?></div>
<?php endif; ?>

<?php if($erro): ?>
<div class="alerta" style="background:#fee2e2;color:#991b1b;"><?= htmlspecialchars($erro) ?></div>
<?php endif; ?>

<form method="POST" enctype="multipart/form-data">

<div class="hp-grid">

<div>
<label>Tipo de destaque</label>
<select name="tipo">
<option value="slider" <?= ($atual['tipo'] ?? 'slider') === 'slider' ? 'selected' : '' ?>>Slider</option>
<option value="video" <?= ($atual['tipo'] ?? '') === 'video' ? 'selected' : '' ?>>Vídeo</option>
</select>
</div>

<div>
<label>Ativo</label><br>
<input type="checkbox" name="ativo" <?= !empty($atual['ativo']) || $atual === [] ? 'checked' : '' ?>>
</div>

<div class="full">
<label>Título</label>
<input type="text" name="titulo" value="<?= htmlspecialchars($atual['titulo'] ?? '') ?>">
</div>

<div class="full">
<label>Subtítulo</label>
<textarea name="subtitulo"><?= htmlspecialchars($atual['subtitulo'] ?? '') ?></textarea>
</div>

<div>
<label>Texto botão</label>
<input type="text" name="botao_texto" value="<?= htmlspecialchars($atual['botao_texto'] ?? '') ?>">
</div>

<div>
<label>Link botão</label>
<input type="text" name="botao_link" value="<?= htmlspecialchars($atual['botao_link'] ?? '') ?>">
</div>

<div class="full">
<label>URL vídeo (YouTube)</label>
<input type="text" name="video_url" value="<?= htmlspecialchars($atual['video_url'] ?? '') ?>">
<p style="color:#6b7280;font-weight:700;font-size:13px;margin:6px 0 0;">Alternativa ao upload de ficheiro: cola aqui um link do YouTube em vez de carregar um MP4.</p>
</div>

<div class="full">
<label>Upload vídeo MP4</label>
<?php if (!empty($atual['video_ficheiro'])): ?>
<p style="color:#166534;font-weight:800;font-size:13px;margin:0 0 8px;"><i class="bi bi-check-circle-fill"></i> Vídeo atual: <?= htmlspecialchars($atual['video_ficheiro']) ?></p>
<?php endif; ?>
<input type="file" name="video_ficheiro" accept="video/mp4">
<p style="color:#6b7280;font-weight:700;font-size:13px;margin:6px 0 0;">Escolher um ficheiro novo substitui o vídeo atual. Se o vídeo for grande e o upload falhar, usa antes um link do YouTube no campo acima.</p>
</div>

<div class="full">
<button type="submit">Guardar configuração</button>
</div>

</div>

</form>
</div>

<?php require_once 'includes/footer.php'; ?>
