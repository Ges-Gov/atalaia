<?php
require_once __DIR__ . "/dompdf/autoload.inc.php";

use Dompdf\Dompdf;
use Dompdf\Options;

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    die("Acesso inválido.");
}

$tipo = trim($_POST['tipo_documento'] ?? '');
$nome = trim($_POST['nome'] ?? '');
$morada = trim($_POST['morada'] ?? '');
$cc = trim($_POST['cc'] ?? '');
$nif = trim($_POST['nif'] ?? '');
$dataNascimento = trim($_POST['data_nascimento'] ?? '');
$assunto = trim($_POST['assunto'] ?? '');
$observacoes = trim($_POST['observacoes'] ?? '');

if (!$tipo || !$nome || !$morada) {
    die("Dados obrigatórios em falta.");
}

$dataAtual = date('d/m/Y');

function limpar($valor) {
    return htmlspecialchars($valor, ENT_QUOTES, 'UTF-8');
}

$titulo = "";
$texto = "";

switch ($tipo) {
    case 'atestado_residencia':
        $titulo = "Atestado de Residência";
        $texto = "
            <p>
                Certifica-se, para os devidos efeitos, que <strong>" . limpar($nome) . "</strong>,
                residente em <strong>" . limpar($morada) . "</strong>,
                pertence à freguesia de Atalaia e Alto Estanqueiro-Jardia, concelho do Montijo.
            </p>
        ";
        break;

    case 'declaracao':
        $titulo = "Declaração / Comprovativo";
        $texto = "
            <p>
                Declara-se, para os devidos efeitos, que <strong>" . limpar($nome) . "</strong>,
                residente em <strong>" . limpar($morada) . "</strong>,
                solicitou a presente declaração/comprovativo.
            </p>
        ";
        break;

    case 'certidao':
        $titulo = "Pedido de Certidão";
        $texto = "
            <p>
                O cidadão <strong>" . limpar($nome) . "</strong>, residente em
                <strong>" . limpar($morada) . "</strong>, vem por este meio solicitar a emissão de certidão.
            </p>
        ";
        break;

    case 'licenca':
        $titulo = "Pedido de Licença / Autorização";
        $texto = "
            <p>
                O cidadão <strong>" . limpar($nome) . "</strong>, residente em
                <strong>" . limpar($morada) . "</strong>, vem solicitar licença/autorização junto da Junta de Freguesia.
            </p>
        ";
        break;

    default:
        die("Tipo de documento inválido.");
}

$dadosExtra = "";

if ($dataNascimento) {
    $dadosExtra .= "<p><strong>Data de nascimento:</strong> " . limpar($dataNascimento) . "</p>";
}

if ($cc) {
    $dadosExtra .= "<p><strong>Cartão de Cidadão:</strong> " . limpar($cc) . "</p>";
}

if ($nif) {
    $dadosExtra .= "<p><strong>NIF:</strong> " . limpar($nif) . "</p>";
}

if ($assunto) {
    $dadosExtra .= "<p><strong>Assunto / finalidade:</strong> " . limpar($assunto) . "</p>";
}

if ($observacoes) {
    $dadosExtra .= "<p><strong>Observações:</strong><br>" . nl2br(limpar($observacoes)) . "</p>";
}

$html = '
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<style>
body {
    font-family: DejaVu Sans, sans-serif;
    color: #111;
    line-height: 1.6;
    padding: 42px;
}

.topo {
    text-align: center;
    margin-bottom: 45px;
}

.topo h1 {
    margin: 0;
    font-size: 27px;
}

.topo p {
    margin: 5px 0;
}

.titulo {
    text-align: center;
    font-size: 22px;
    margin: 40px 0;
    text-transform: uppercase;
    font-weight: bold;
}

.texto {
    font-size: 16px;
    text-align: justify;
}

.dados {
    margin-top: 25px;
    font-size: 14px;
    background: #f4f4f4;
    padding: 14px;
    border-radius: 8px;
}

.assinatura {
    margin-top: 80px;
    text-align: right;
}

.rodape {
    margin-top: 70px;
    font-size: 12px;
    color: #666;
    text-align: center;
}
</style>
</head>

<body>

<div class="topo">
    <h1>Junta de Freguesia de Atalaia e Alto Estanqueiro-Jardia</h1>
    <p>Concelho do Montijo</p>
</div>

<div class="titulo">' . limpar($titulo) . '</div>

<div class="texto">
    ' . $texto . '

    <p>
        Por ser verdade e/ou por me ter sido solicitado,
        passa-se o presente documento.
    </p>
</div>

<div class="dados">
    ' . $dadosExtra . '
</div>

<div class="assinatura">
    <p>Atalaia, ' . $dataAtual . '</p>
    <br><br><br>
    _______________________________<br>
    O Presidente da Junta
</div>

<div class="rodape">
    Documento gerado automaticamente através do Balcão Virtual.
</div>

</body>
</html>
';

$options = new Options();
$options->set('isRemoteEnabled', true);

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html);
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$nomeFicheiro = strtolower(str_replace(' ', '_', $titulo)) . "_" . time() . ".pdf";

$dompdf->stream($nomeFicheiro, ["Attachment" => true]);
exit;