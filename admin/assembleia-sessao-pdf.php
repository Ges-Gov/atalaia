<?php
/*
 * Convocatória, edital e edital das deliberações de uma sessão da Assembleia de Freguesia, em PDF, gerados a partir
 * dos pontos da ordem de trabalhos (20/11/2026, pedido da direção; modelo: documentos finais das Atas da Intranet).
 * Uso: assembleia-sessao-pdf.php?sessao=ID&tipo=convocatoria|edital|deliberacoes
 */
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/config.php";
require_once __DIR__ . "/../includes/assembleia_pontos.php";
require_once __DIR__ . "/../dompdf/autoload.inc.php";
requireAssembleiaManager();

use Dompdf\Dompdf;
use Dompdf\Options;

$tipo = in_array($_GET['tipo'] ?? '', ['convocatoria', 'edital', 'deliberacoes'], true) ? $_GET['tipo'] : 'convocatoria';
$st = $pdo->prepare("SELECT * FROM assembleia_sessoes WHERE id = ?");
$st->execute([(int)($_GET['sessao'] ?? 0)]);
$sessao = $st->fetch(PDO::FETCH_ASSOC);
if (!$sessao) { http_response_code(404); exit('Sessão não encontrada.'); }

$pontos = assembleiaPontos($pdo, (int)$sessao['id'], true);
$periodos = assembleiaPeriodos();
$meses = ['', 'janeiro', 'fevereiro', 'março', 'abril', 'maio', 'junho', 'julho', 'agosto', 'setembro', 'outubro', 'novembro', 'dezembro'];
$dataExtenso = fn(string $d) => (int)date('j', strtotime($d)) . ' de ' . $meses[(int)date('n', strtotime($d))] . ' de ' . date('Y', strtotime($d));
$hora = str_replace(':', 'h', substr((string)$sessao['hora_sessao'], 0, 5));
$tipoSessao = mb_strtolower(trim((string)$sessao['tipo'])) ?: 'sessão';
if (!preg_match('/^(sessão|reunião)/u', $tipoSessao)) $tipoSessao = 'sessão';
$assembleia = 'Assembleia de Freguesia ' . deFreguesia();
$presidente = assembleiaPresidenteMesa($pdo);
$local = trim((string)$sessao['local_sessao']);
$quando = 'no dia ' . $dataExtenso($sessao['data_sessao']) . ', pelas ' . $hora . ($local !== '' ? ' (local: ' . $local . ')' : '');
$h = fn($t) => htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8');
// Sem artigo («o»/«a»): não se adivinha o género de quem preside
$quemTorna = fn() => $presidente !== '' ? $h($presidente) . ', Presidente da ' . $h($assembleia) . ',' : 'A Presidência da ' . $h($assembleia);

/* Logótipo da Junta, embutido (o Dompdf não precisa de ir buscar nada à rede) */
$logoHtml = '';
$logo = basename((string)siteConfig('logo', ''));
$logoFich = __DIR__ . '/../assets/img/' . $logo;
if ($logo !== '' && is_file($logoFich) && ($info = @getimagesize($logoFich))) {
    $logoHtml = '<img class="logo" src="data:' . $info['mime'] . ';base64,' . base64_encode((string)file_get_contents($logoFich)) . '" alt="">';
}

/* Ordem de trabalhos (convocatória e edital) ou deliberações */
$lista = function (bool $soResultados) use ($pontos, $periodos, $h): string {
    $html = '';
    foreach ($pontos as $per => $ps) {
        $itens = '';
        foreach ($ps as $p) {
            $todos = array_merge([$p], $p['filhos']);
            foreach ($todos as $q) {
                $res = assembleiaResultadoTexto($q);
                if ($soResultados && $res === '') continue;
                $sub = $q !== $p;
                $itens .= '<tr><td class="num' . ($sub ? ' sub' : '') . '">' . $h($q['num']) . '.</td><td class="txt' . ($sub ? ' sub' : '') . '">' . $h($q['titulo'])
                    . (!empty($q['proposta_ref']) ? ' <span class="ref">(' . $h($q['proposta_ref']) . ')</span>' : '')
                    . ($soResultados ? '<div class="res">' . $h($res) . '</div>' . (!empty($q['deliberacao']) ? '<div class="delib">' . nl2br($h($q['deliberacao'])) . '</div>' : '') : '')
                    . '</td></tr>';
            }
        }
        if ($itens === '') continue;
        if (count($pontos) > 1 || $per !== 'OD') $html .= '<p class="periodo">' . $h($periodos[$per] ?? $per) . '</p>';
        $html .= '<table class="pontos">' . $itens . '</table>';
    }
    return $html;
};

$temDocs = false;
foreach ($pontos as $ps) foreach ($ps as $p) if ($p['docs'] || array_filter($p['filhos'], fn($f) => $f['docs'])) $temDocs = true;
$temPublico = isset($pontos['PUBLICO']);

if ($tipo === 'convocatoria') {
    $titulo = 'Convocatória';
    $corpo = '<p>Nos termos da Lei n.º 75/2013, de 12 de setembro, convoco os membros da ' . $h($assembleia) . ' para a ' . $h($tipoSessao)
        . ' que se realiza ' . $h($quando) . ', com a seguinte ordem de trabalhos:</p>' . $lista(false)
        . ($temDocs ? '<p>Os documentos relativos aos pontos da ordem de trabalhos estão disponíveis para consulta no site da Junta de Freguesia.</p>' : '');
} elseif ($tipo === 'edital') {
    $titulo = 'Edital';
    $corpo = '<p>' . $quemTorna() . ' torna público que a Assembleia de Freguesia reúne em '
        . $h($tipoSessao) . ' ' . $h($quando) . ', com a seguinte ordem de trabalhos:</p>' . $lista(false)
        . '<p>A sessão é pública' . ($temPublico ? ' e tem um período de intervenção do público' : '') . '.</p>'
        . '<p>Para constar, se publica este edital e outros de igual teor, que vão ser afixados nos lugares de estilo.</p>';
} else {
    $titulo = 'Edital — Deliberações';
    $delib = $lista(true);
    $corpo = '<p>' . $quemTorna() . ' torna público que, na '
        . $h($tipoSessao) . ' realizada no dia ' . $h($dataExtenso($sessao['data_sessao'])) . ', a Assembleia de Freguesia deliberou o seguinte:</p>'
        . ($delib !== '' ? $delib : '<p><em>Ainda não há resultados registados nos pontos desta sessão.</em></p>')
        . '<p>Para constar, se publica este edital e outros de igual teor, que vão ser afixados nos lugares de estilo.</p>';
}

$html = '<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8"><title>' . $h($titulo . ' — ' . $sessao['titulo']) . '</title><style>
@page { margin: 2.2cm 2.2cm 2cm; }
body { font-family: "DejaVu Serif", serif; font-size: 11pt; line-height: 1.55; color: #111; }
.cab { text-align: center; margin-bottom: 26px; }
.cab .logo { max-height: 70px; max-width: 220px; margin-bottom: 8px; }
.cab .org { font-family: "DejaVu Sans", sans-serif; font-size: 10pt; letter-spacing: .06em; text-transform: uppercase; color: #333; }
h1 { font-size: 16pt; text-align: center; text-transform: uppercase; letter-spacing: .08em; margin: 6px 0 4px; }
.sub-t { text-align: center; font-size: 10.5pt; color: #333; margin-bottom: 22px; }
p { text-align: justify; margin: 0 0 12px; }
.periodo { font-family: "DejaVu Sans", sans-serif; font-weight: bold; font-size: 10pt; text-transform: uppercase; letter-spacing: .04em; margin: 14px 0 6px; text-align: left; }
table.pontos { width: 100%; border-collapse: collapse; margin: 0 0 12px; }
table.pontos td { vertical-align: top; padding: 3px 0; }
td.num { width: 1cm; white-space: nowrap; font-weight: bold; padding-right: 8px; }
td.txt { width: 100%; }
td.num.sub { padding-left: 0.4cm; font-weight: normal; }
td.sub { font-size: 10.5pt; }
.ref { color: #444; font-size: 10pt; }
.res { font-weight: bold; margin-top: 2px; }
.delib { font-size: 10pt; color: #333; margin-top: 2px; }
.assin { margin-top: 34px; text-align: center; }
.assin .linha { margin: 46px auto 4px; width: 60%; border-top: 1px solid #333; }
</style></head><body>
<div class="cab">' . $logoHtml . '<div class="org">' . $h($assembleia) . '</div></div>
<h1>' . $h($titulo) . '</h1>
<div class="sub-t">' . $h($sessao['titulo']) . '</div>
' . $corpo . '
<div class="assin"><p style="text-align:center">' . $h(nomeFreguesia()) . ', ' . $h($dataExtenso(date('Y-m-d'))) . '</p>
<p style="text-align:center;margin:0">Presidente da ' . $h($assembleia) . '</p><div class="linha"></div>' . ($presidente !== '' ? '<p style="text-align:center">(' . $h($presidente) . ')</p>' : '') . '</div>
</body></html>';

$pdfCacheDir = __DIR__ . "/../uploads/_pdf_cache";
if (!is_dir($pdfCacheDir)) @mkdir($pdfCacheDir, 0775, true);
$options = new Options();
$options->set('isRemoteEnabled', false);
$options->set('defaultFont', 'DejaVu Serif');
if (is_dir($pdfCacheDir) && is_writable($pdfCacheDir)) {
    $options->set('tempDir', $pdfCacheDir);
    $options->set('fontCache', $pdfCacheDir);
}
$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();
$nome = $tipo . '-assembleia-' . date('Y-m-d', strtotime($sessao['data_sessao'])) . '.pdf';
$dompdf->stream($nome, ['Attachment' => false]);
exit;
