<?php
require_once __DIR__ . "/includes/auth.php";
require_once __DIR__ . "/../includes/db.php";
require_once __DIR__ . "/../dompdf/autoload.inc.php";

use Dompdf\Dompdf;
use Dompdf\Options;

function pdfCount($pdo, $sql) {
    try {
        return (int)$pdo->query($sql)->fetchColumn();
    } catch (Exception $e) {
        return 0;
    }
}

function pdfFetch($pdo, $sql) {
    try {
        return $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        return [];
    }
}

$totalPedidos = pdfCount($pdo, "SELECT COUNT(*) FROM pedidos_junta");
$pendentes = pdfCount($pdo, "SELECT COUNT(*) FROM pedidos_junta WHERE estado = 'pendente'");
$analise = pdfCount($pdo, "SELECT COUNT(*) FROM pedidos_junta WHERE estado = 'em_analise'");
$resolvidos = pdfCount($pdo, "SELECT COUNT(*) FROM pedidos_junta WHERE estado = 'resolvido'");

$totalRequerimentos = pdfCount($pdo, "SELECT COUNT(*) FROM requerimentos");
$totalMarcacoes = pdfCount($pdo, "SELECT COUNT(*) FROM marcacoes_atendimento");
$notificacoesAtivas = pdfCount($pdo, "SELECT COUNT(*) FROM notificacoes WHERE ativo = 1");

$taxaResolucao = $totalPedidos > 0 ? round(($resolvidos / $totalPedidos) * 100) : 0;

$ultimosPedidos = pdfFetch($pdo, "
    SELECT *
    FROM pedidos_junta
    ORDER BY criado_em DESC
    LIMIT 8
");

$ultimosRequerimentos = pdfFetch($pdo, "
    SELECT *
    FROM requerimentos
    ORDER BY criado_em DESC
    LIMIT 8
");

$ultimasMarcacoes = pdfFetch($pdo, "
    SELECT *
    FROM marcacoes_atendimento
    ORDER BY criado_em DESC
    LIMIT 8
");

$categorias = pdfFetch($pdo, "
    SELECT categoria, COUNT(*) total
    FROM pedidos_junta
    GROUP BY categoria
    ORDER BY total DESC
    LIMIT 5
");

$dataRelatorio = date('d/m/Y H:i');

$html = '
<!DOCTYPE html>
<html lang="pt">
<head>
<meta charset="UTF-8">
<style>
    body {
        font-family: DejaVu Sans, Arial, sans-serif;
        background: #f4f6f8;
        color: #11151B;
        margin: 0;
        padding: 0;
        font-size: 12px;
    }

    .page {
        padding: 28px;
    }

    .hero {
        background: #242A30;
        color: white;
        border-radius: 18px;
        padding: 26px;
        margin-bottom: 22px;
    }

    .hero small {
        color: #D4AA00;
        font-weight: bold;
        text-transform: uppercase;
        letter-spacing: .5px;
    }

    .hero h1 {
        margin: 8px 0 6px;
        font-size: 28px;
    }

    .hero p {
        margin: 0;
        color: #dbeafe;
        font-size: 13px;
    }

    .meta {
        margin-top: 12px;
        font-size: 11px;
        color: #dbeafe;
    }

    .grid {
        width: 100%;
        margin-bottom: 18px;
    }

    .kpi {
        background: white;
        border-radius: 14px;
        padding: 15px;
        width: 23%;
        display: inline-block;
        margin-right: 1%;
        vertical-align: top;
        border-left: 5px solid #242A30;
    }

    .kpi strong {
        display: block;
        font-size: 25px;
        color: #242A30;
        margin-bottom: 4px;
    }

    .kpi span {
        font-weight: bold;
        color: #475569;
    }

    .section {
        background: white;
        border-radius: 16px;
        padding: 18px;
        margin-bottom: 18px;
    }

    .section h2 {
        margin: 0 0 12px;
        font-size: 17px;
        color: #11151B;
        border-bottom: 2px solid #eef2f7;
        padding-bottom: 8px;
    }

    table {
        width: 100%;
        border-collapse: collapse;
    }

    th {
        text-align: left;
        background: #f1f5f9;
        color: #11151B;
        padding: 9px;
        font-size: 11px;
    }

    td {
        padding: 9px;
        border-bottom: 1px solid #e5e7eb;
        color: #334155;
    }

    .badge {
        display: inline-block;
        padding: 4px 8px;
        border-radius: 999px;
        background: #e5e7eb;
        font-size: 10px;
        font-weight: bold;
    }

    .footer {
        text-align: center;
        color: #64748b;
        font-size: 10px;
        margin-top: 18px;
    }
</style>
</head>
<body>
<div class="page">

    <div class="hero">
        <small>AAEJ Digital</small>
        <h1>Relatório Geral da Junta Virtual</h1>
        <p>Resumo institucional dos pedidos, requerimentos, marcações e atividade digital.</p>
        <div class="meta">Gerado em: ' . htmlspecialchars($dataRelatorio) . '</div>
    </div>

    <div class="grid">
        <div class="kpi">
            <strong>' . (int)$totalPedidos . '</strong>
            <span>Pedidos</span>
        </div>

        <div class="kpi">
            <strong>' . (int)$pendentes . '</strong>
            <span>Pendentes</span>
        </div>

        <div class="kpi">
            <strong>' . (int)$resolvidos . '</strong>
            <span>Resolvidos</span>
        </div>

        <div class="kpi">
            <strong>' . (int)$taxaResolucao . '%</strong>
            <span>Taxa resolução</span>
        </div>
    </div>

    <div class="grid">
        <div class="kpi">
            <strong>' . (int)$totalRequerimentos . '</strong>
            <span>Requerimentos</span>
        </div>

        <div class="kpi">
            <strong>' . (int)$totalMarcacoes . '</strong>
            <span>Marcações</span>
        </div>

        <div class="kpi">
            <strong>' . (int)$notificacoesAtivas . '</strong>
            <span>Notificações ativas</span>
        </div>

        <div class="kpi">
            <strong>' . (int)$analise . '</strong>
            <span>Em análise</span>
        </div>
    </div>

    <div class="section">
        <h2>Categorias mais reportadas</h2>
        <table>
            <tr>
                <th>Categoria</th>
                <th>Total</th>
            </tr>';

foreach ($categorias as $c) {
    $html .= '
            <tr>
                <td>' . htmlspecialchars($c['categoria'] ?: 'Sem categoria') . '</td>
                <td>' . (int)$c['total'] . '</td>
            </tr>';
}

if (empty($categorias)) {
    $html .= '
            <tr>
                <td colspan="2">Sem dados disponíveis.</td>
            </tr>';
}

$html .= '
        </table>
    </div>

    <div class="section">
        <h2>Últimos pedidos</h2>
        <table>
            <tr>
                <th>Código</th>
                <th>Assunto</th>
                <th>Estado</th>
                <th>Data</th>
            </tr>';

foreach ($ultimosPedidos as $p) {
    $html .= '
            <tr>
                <td>' . htmlspecialchars($p['codigo'] ?? '-') . '</td>
                <td>' . htmlspecialchars($p['assunto'] ?? '-') . '</td>
                <td><span class="badge">' . htmlspecialchars(str_replace("_", " ", $p['estado'] ?? '-')) . '</span></td>
                <td>' . (!empty($p['criado_em']) ? date('d/m/Y H:i', strtotime($p['criado_em'])) : '-') . '</td>
            </tr>';
}

if (empty($ultimosPedidos)) {
    $html .= '
            <tr>
                <td colspan="4">Sem pedidos registados.</td>
            </tr>';
}

$html .= '
        </table>
    </div>

    <div class="section">
        <h2>Últimos requerimentos</h2>
        <table>
            <tr>
                <th>Tipo</th>
                <th>Estado</th>
                <th>Data</th>
            </tr>';

foreach ($ultimosRequerimentos as $r) {
    $html .= '
            <tr>
                <td>' . htmlspecialchars($r['tipo_requerimento'] ?? '-') . '</td>
                <td><span class="badge">' . htmlspecialchars(str_replace("_", " ", $r['estado'] ?? '-')) . '</span></td>
                <td>' . (!empty($r['criado_em']) ? date('d/m/Y H:i', strtotime($r['criado_em'])) : '-') . '</td>
            </tr>';
}

if (empty($ultimosRequerimentos)) {
    $html .= '
            <tr>
                <td colspan="3">Sem requerimentos registados.</td>
            </tr>';
}

$html .= '
        </table>
    </div>

    <div class="section">
        <h2>Últimas marcações</h2>
        <table>
            <tr>
                <th>Assunto</th>
                <th>Nome</th>
                <th>Data</th>
                <th>Hora</th>
            </tr>';

foreach ($ultimasMarcacoes as $m) {
    $html .= '
            <tr>
                <td>' . htmlspecialchars($m['assunto'] ?? '-') . '</td>
                <td>' . htmlspecialchars($m['nome'] ?? '-') . '</td>
                <td>' . (!empty($m['data_marcacao']) ? date('d/m/Y', strtotime($m['data_marcacao'])) : '-') . '</td>
                <td>' . (!empty($m['hora_marcacao']) ? substr($m['hora_marcacao'], 0, 5) : '-') . '</td>
            </tr>';
}

if (empty($ultimasMarcacoes)) {
    $html .= '
            <tr>
                <td colspan="4">Sem marcações registadas.</td>
            </tr>';
}

$html .= '
        </table>
    </div>

    <div class="footer">
        Relatório gerado automaticamente pela plataforma AAEJ Digital.
    </div>

</div>
</body>
</html>';

// Pasta gravável para cache de fontes/temporários do dompdf.
// (Em produção a pasta interna do dompdf pode não ter permissão de escrita,
//  causando erro 500. uploads/ é gravável pelo servidor web.)
$pdfCacheDir = __DIR__ . "/../uploads/_pdf_cache";
if (!is_dir($pdfCacheDir)) {
    @mkdir($pdfCacheDir, 0775, true);
}

$options = new Options();
$options->set('isRemoteEnabled', true);
$options->set('defaultFont', 'DejaVu Sans');
if (is_dir($pdfCacheDir) && is_writable($pdfCacheDir)) {
    $options->set('tempDir', $pdfCacheDir);
    $options->set('fontCache', $pdfCacheDir);
}

$dompdf = new Dompdf($options);
$dompdf->loadHtml($html, 'UTF-8');
$dompdf->setPaper('A4', 'portrait');
$dompdf->render();

$dompdf->stream("relatorio-granho.pdf", ["Attachment" => false]);
exit;