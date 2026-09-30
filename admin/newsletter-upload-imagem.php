<?php
/**
 * Upload de imagem a partir do editor de texto da newsletter (botão de
 * imagem do Quill). Devolve a URL real da imagem — nunca base64: muitos
 * clientes de email (Outlook em particular) não mostram imagens embutidas
 * em base64, só URLs normais.
 */
// Só a autenticação (não o header.php inteiro — isto é um endpoint JSON,
// não uma página, não precisa da barra lateral nem do resto do layout).
require_once "includes/auth.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/newsletter.php";

header('Content-Type: application/json');

if (empty($_FILES['imagem']['name'])) {
    http_response_code(400);
    echo json_encode(['erro' => 'Nenhum ficheiro enviado.']);
    exit;
}

$permitidas = ['jpg', 'jpeg', 'png', 'webp', 'gif'];
$limiteBytes = 8 * 1024 * 1024;
$ext = strtolower(pathinfo($_FILES['imagem']['name'], PATHINFO_EXTENSION));

if (!in_array($ext, $permitidas)) {
    http_response_code(400);
    echo json_encode(['erro' => 'Formato não permitido. Use JPG, PNG, WEBP ou GIF.']);
    exit;
}

if ($_FILES['imagem']['size'] > $limiteBytes) {
    http_response_code(400);
    echo json_encode(['erro' => 'A imagem deve ter no máximo 8 MB.']);
    exit;
}

$pasta = __DIR__ . "/../assets/img/newsletter/";
if (!is_dir($pasta)) {
    mkdir($pasta, 0777, true);
}

$novoNome = "newsletter_" . date('YmdHis') . "_" . bin2hex(random_bytes(4)) . "." . $ext;

if (!move_uploaded_file($_FILES['imagem']['tmp_name'], $pasta . $novoNome)) {
    http_response_code(500);
    echo json_encode(['erro' => 'Erro ao guardar a imagem.']);
    exit;
}

// URL absoluta, não relativa — isto vai parar dentro do HTML de um email
// real, que não tem "página atual" nenhuma para resolver um caminho
// relativo (ao contrário do browser aqui no backoffice).
echo json_encode(['url' => newsletterBaseUrl() . '/assets/img/newsletter/' . $novoNome]);
