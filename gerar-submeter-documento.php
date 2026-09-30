<?php
session_start();

require_once "includes/mail_helper.php";
require_once __DIR__ . "/includes/db.php";

require_once __DIR__ . "/dompdf/autoload.inc.php";

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acesso inválido.");
}

function limpar($v) {
    return htmlspecialchars(trim($v), ENT_QUOTES, 'UTF-8');
}

$tipo = limpar($_POST['tipo_documento'] ?? '');
$nome = limpar($_POST['nome'] ?? '');
$morada = limpar($_POST['morada'] ?? '');
$cc = limpar($_POST['cc'] ?? '');
$nif = limpar($_POST['nif'] ?? '');
$dataNascimento = limpar($_POST['data_nascimento'] ?? '');
$assunto = limpar($_POST['assunto'] ?? '');
$observacoes = limpar($_POST['observacoes'] ?? '');

if (!$tipo || !$nome || !$morada) {
    die("Dados obrigatórios em falta.");
}

$codigoReq = "REQ-" . date('Y') . "-" . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));

/* =========================
   TÍTULOS
========================= */

$titulo = '';

switch ($tipo) {

    case 'atestado_residencia':
        $titulo = "Atestado de Residência";
        break;

    case 'declaracao':
        $titulo = "Declaração / Comprovativo";
        break;

    case 'certidao':
        $titulo = "Pedido de Certidão";
        break;

    case 'licenca':
        $titulo = "Licença / Autorização";
        break;

    default:
        die("Documento inválido.");
}

/* =========================
   HTML PDF
========================= */

$dataAtual = date('d/m/Y');

$html = '
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">

<style>

body{
    font-family: DejaVu Sans, sans-serif;
    padding:40px;
    color:#111;
    line-height:1.6;
}

.topo{
    text-align:center;
    margin-bottom:50px;
}

.titulo{
    text-align:center;
    font-size:24px;
    margin-bottom:40px;
    font-weight:bold;
}

.bloco{
    margin-bottom:18px;
}

.assinatura{
    margin-top:80px;
    text-align:right;
}

.nota{
    margin-top:70px;
    font-size:12px;
    color:#666;
    text-align:center;
}

</style>

</head>

<body>

<div class="topo">
    <h1>Junta de Freguesia de Atalaia e Alto Estanqueiro-Jardia</h1>
    <p>Concelho do Montijo</p>
</div>

<div class="titulo">
    ' . $titulo . '
</div>

<div class="bloco">
    <strong>Nome:</strong> ' . $nome . '
</div>

<div class="bloco">
    <strong>Morada:</strong> ' . $morada . '
</div>

<div class="bloco">
    <strong>Cartão de Cidadão:</strong> ' . $cc . '
</div>

<div class="bloco">
    <strong>NIF:</strong> ' . $nif . '
</div>

<div class="bloco">
    <strong>Data nascimento:</strong> ' . $dataNascimento . '
</div>

<div class="bloco">
    <strong>Assunto:</strong> ' . $assunto . '
</div>

<div class="bloco">
    <strong>Observações:</strong><br>
    ' . nl2br($observacoes) . '
</div>

<div class="assinatura">

    Atalaia, ' . $dataAtual . '

    <br><br><br>

    _______________________________

    <br>

    O Requerente

</div>

<div class="nota">

Documento gerado automaticamente pelo Balcão Virtual.<br>
Sujeito a validação pela Junta de Freguesia.

</div>

</body>
</html>
';

/* =========================
   GERAR PDF
========================= */

$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'portrait');

$dompdf->render();

$pastaDocs = "assets/docs/requerimentos/";

if (!is_dir($pastaDocs)) {
    mkdir($pastaDocs, 0777, true);
}

$nomePdf = "documento_" . time() . ".pdf";

file_put_contents(
    $pastaDocs . $nomePdf,
    $dompdf->output()
);

/* =========================
   GUARDAR REQUERIMENTO
========================= */

$stmt = $pdo->prepare("
INSERT INTO requerimentos
(codigo, nome, email, telefone, tipo, assunto, mensagem, documento_pdf)
VALUES (?, ?, ?, ?, ?, ?, ?, ?)
");

$stmt->execute([
    $codigoReq,
    $nome,
    '',
    '',
    $titulo,
    $assunto,
    $observacoes,
    $nomePdf
]);

$reqId = $pdo->lastInsertId();

/* =========================
   UPLOADS CC
========================= */

$permitidas = ['jpg','jpeg','png','pdf','webp'];

foreach (['cc_frente', 'cc_verso'] as $campo) {

    if (
        !empty($_FILES[$campo]['name']) &&
        $_FILES[$campo]['error'] == 0
    ) {

        $nomeOriginal = $_FILES[$campo]['name'];

        $ext = strtolower(pathinfo($nomeOriginal, PATHINFO_EXTENSION));

        if (in_array($ext, $permitidas)) {

            $novoNome = $campo . "_" . time() . "." . $ext;

            move_uploaded_file(
                $_FILES[$campo]['tmp_name'],
                $pastaDocs . $novoNome
            );

            $stmtFile = $pdo->prepare("
            INSERT INTO requerimentos_ficheiros
            (requerimento_id, ficheiro, nome_original)
            VALUES (?, ?, ?)
            ");

            $stmtFile->execute([
                $reqId,
                $novoNome,
                $nomeOriginal
            ]);
        }
    }
}

/* =========================
   GUARDAR PDF COMO FICHEIRO
========================= */

$stmtFilePdf = $pdo->prepare("
INSERT INTO requerimentos_ficheiros
(requerimento_id, ficheiro, nome_original)
VALUES (?, ?, ?)
");

$stmtFilePdf->execute([
    $reqId,
    $nomePdf,
    $titulo . ".pdf"
]);

/* =========================
   EMAILS
========================= */

$emailJunta = siteConfig('email_notificacoes', siteConfig('email'));

if ($emailJunta) {

    $htmlEmail = "
    <h2>Novo documento automático submetido</h2>

    <p><strong>Código:</strong> {$codigoReq}</p>

    <p><strong>Nome:</strong> {$nome}</p>

    <p><strong>Documento:</strong> {$titulo}</p>

    <p>Consulte no backoffice.</p>
    ";

    enviarEmailSistema(
        $emailJunta,
        "Novo documento submetido",
        $htmlEmail
    );
}

/* =========================
   DOWNLOAD AUTOMÁTICO
========================= */

header("Content-type: application/pdf");

header("Content-Disposition: attachment; filename=documento.pdf");

echo $dompdf->output();

exit;
?>