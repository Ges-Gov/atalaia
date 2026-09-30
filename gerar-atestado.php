<?php
require_once __DIR__ . "/dompdf/autoload.inc.php";

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acesso inválido.");
}

$nome = trim($_POST['nome'] ?? '');
$morada = trim($_POST['morada'] ?? '');
$nif = trim($_POST['nif'] ?? '');
$cc = trim($_POST['cc'] ?? '');
$dataNascimento = trim($_POST['data_nascimento'] ?? '');

if (!$nome || !$morada) {
    die("Dados obrigatórios em falta.");
}

$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);

$dataAtual = date('d/m/Y');

$html = '
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">

<style>
body{
    font-family: DejaVu Sans, sans-serif;
    color:#111;
    line-height:1.6;
    padding:40px;
}

.topo{
    text-align:center;
    margin-bottom:40px;
}

.topo h1{
    margin:0;
    font-size:28px;
}

.topo p{
    margin:5px 0;
}

.titulo{
    text-align:center;
    font-size:22px;
    margin:40px 0;
    text-transform:uppercase;
}

.texto{
    font-size:16px;
    text-align:justify;
}

.assinatura{
    margin-top:80px;
    text-align:right;
}

.rodape{
    margin-top:80px;
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
    Atestado de Residência
</div>

<div class="texto">

<p>
Certifica-se, para os devidos efeitos, que
<strong>' . htmlspecialchars($nome) . '</strong>,
';

if ($dataNascimento) {
    $html .= '
    nascido(a) em <strong>' . htmlspecialchars($dataNascimento) . '</strong>,
    ';
}

if ($cc) {
    $html .= '
    titular do Cartão de Cidadão n.º <strong>' . htmlspecialchars($cc) . '</strong>,
    ';
}

if ($nif) {
    $html .= '
    contribuinte fiscal n.º <strong>' . htmlspecialchars($nif) . '</strong>,
    ';
}

$html .= '
reside na morada
<strong>' . htmlspecialchars($morada) . '</strong>,
na freguesia de Atalaia e Alto Estanqueiro-Jardia,
concelho do Montijo.
</p>

<p>
Por ser verdade e me ter sido solicitado,
passa-se o presente atestado.
</p>

</div>

<div class="assinatura">
    <p>Atalaia, ' . $dataAtual . '</p>

    <br><br><br>

    _______________________________
    <br>
    O Presidente da Junta
</div>

<div class="rodape">
Documento gerado automaticamente através do Balcão Virtual.
</div>

</body>
</html>
';

$dompdf->loadHtml($html);

$dompdf->setPaper('A4', 'portrait');

$dompdf->render();

$nomeFicheiro = "atestado_residencia_" . time() . ".pdf";

$dompdf->stream($nomeFicheiro, ["Attachment" => true]);

exit;
?>