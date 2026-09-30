<?php
$adminPageTitle = "Ver Marcação";
$adminActive = "marcacoes";
require_once "includes/header.php";
require_once "../includes/mail_helper.php";

$id = $_GET['id'] ?? null;

$stmt = $pdo->prepare("SELECT * FROM marcacoes_atendimento WHERE id = ?");
$stmt->execute([$id]);
$marcacao = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$marcacao) {
    die("Marcação não encontrada.");
}

function estadoMarcacaoLabel($estado) {
    switch ($estado) {
        case 'confirmada': return 'Confirmada';
        case 'cancelada': return 'Cancelada';
        case 'concluida': return 'Concluída';
        default: return 'Pendente';
    }
}

function estadoMarcacaoCor($estado) {
    switch ($estado) {
        case 'confirmada': return '#16a34a';
        case 'cancelada': return '#dc2626';
        case 'concluida': return '#2563eb';
        default: return '#f59f00';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $estadoAntigo = $marcacao['estado'];

    $estado = $_POST['estado'] ?? $marcacao['estado'];
    $resposta = trim($_POST['resposta'] ?? '');
    $responsavel = trim($_POST['responsavel'] ?? '');
    $observacoesAdmin = trim($_POST['observacoes_admin'] ?? '');

    $dataMarcacao = trim($_POST['data_marcacao'] ?? $marcacao['data_marcacao']);
    $horaMarcacao = trim($_POST['hora_marcacao'] ?? $marcacao['hora_marcacao']);
    $linkReuniao = trim($_POST['link_reuniao'] ?? '');
    $tipoAtendimento = $_POST['tipo_atendimento'] ?? ($marcacao['tipo_atendimento'] ?? 'presencial');
    $tipoAtendimento = $tipoAtendimento === 'virtual' ? 'virtual' : 'presencial';

    if (!$dataMarcacao || !$horaMarcacao) {
        die("Data e hora são obrigatórias.");
    }

    $stmt = $pdo->prepare("
        UPDATE marcacoes_atendimento
        SET 
            estado = ?,
            resposta = ?,
            responsavel = ?,
            observacoes_admin = ?,
            data_marcacao = ?,
            hora_marcacao = ?,
            link_reuniao = ?,
            tipo_atendimento = ?,
            atualizado_em = NOW()
        WHERE id = ?
    ");

    $stmt->execute([
        $estado,
        $resposta,
        $responsavel,
        $observacoesAdmin,
        $dataMarcacao,
        $horaMarcacao,
        $linkReuniao,
        $tipoAtendimento,
        $id
    ]);

    if (!empty($marcacao['email'])) {

        $tituloEmail = "Atualização da sua marcação";

        if ($estadoAntigo !== 'confirmada' && $estado === 'confirmada') {
            $tituloEmail = "A sua marcação foi confirmada";
        } elseif ($estado === 'cancelada') {
            $tituloEmail = "A sua marcação foi cancelada";
        } elseif ($estado === 'concluida') {
            $tituloEmail = "A sua marcação foi concluída";
        }

        $dataFormatada = date('d/m/Y', strtotime($dataMarcacao));
        $horaFormatada = substr($horaMarcacao, 0, 5);
        $estadoLabel = estadoMarcacaoLabel($estado);
        $tipoLabel = $tipoAtendimento === 'virtual' ? 'Virtual' : 'Presencial';

        $blocoLinkReuniao = '';
        if ($tipoAtendimento === 'virtual' && !empty($linkReuniao)) {
            $linkSeguro = htmlspecialchars($linkReuniao);
            $blocoLinkReuniao = "
                <div style='background:#eff6ff;border:1px solid #bfdbfe;border-radius:16px;padding:18px;margin:20px 0;'>
                    <p style='margin-top:0;'><strong>Link da reunião virtual:</strong></p>
                    <p>
                        <a href='{$linkSeguro}' target='_blank' style='display:inline-block;background:#2563eb;color:white;text-decoration:none;padding:12px 18px;border-radius:12px;font-weight:bold;'>
                            Entrar na reunião virtual
                        </a>
                    </p>
                    <p style='font-size:13px;color:#475569;margin-bottom:0;'>{$linkSeguro}</p>
                </div>
            ";
        }

        $fraseFinal = $tipoAtendimento === 'virtual'
            ? 'Entre na reunião virtual através do link indicado acima, na data e hora confirmadas.'
            : 'Por favor, compareça na Junta de Freguesia na data e hora indicadas.';

        $html = "
            <div style='font-family:Arial,sans-serif;background:#f4f7fb;padding:24px;'>
                <div style='max-width:680px;margin:auto;background:white;border-radius:18px;overflow:hidden;box-shadow:0 18px 45px rgba(15,23,42,.12);'>
                    <div style='background:#242A30;color:white;padding:26px;'>
                        <h2 style='margin:0;font-size:26px;'>{$tituloEmail}</h2>
                        <p style='margin:8px 0 0;color:#dbeafe;'>Balcão Virtual da Junta de Freguesia</p>
                    </div>

                    <div style='padding:26px;color:#11151B;line-height:1.65;'>
                        <p>Olá <strong>{$marcacao['nome']}</strong>,</p>
                        <p>A sua marcação de atendimento foi atualizada.</p>

                        <div style='background:#f8fafc;border:1px solid #e5e7eb;border-radius:16px;padding:18px;margin:20px 0;'>
                            <p><strong>Assunto:</strong> {$marcacao['assunto']}</p>
                            <p><strong>Estado:</strong> {$estadoLabel}</p>
                            <p><strong>Data:</strong> {$dataFormatada}</p>
                            <p><strong>Hora:</strong> {$horaFormatada}</p>
                            <p><strong>Responsável:</strong> " . htmlspecialchars($responsavel ?: 'A definir') . "</p>
                            <p><strong>Tipo de atendimento:</strong> {$tipoLabel}</p>
                        </div>

                        {$blocoLinkReuniao}

                        " . ($resposta ? "<p><strong>Resposta:</strong><br>" . nl2br(htmlspecialchars($resposta)) . "</p>" : "") . "
                        " . ($observacoesAdmin ? "<p><strong>Observações:</strong><br>" . nl2br(htmlspecialchars($observacoesAdmin)) . "</p>" : "") . "

                        <p>{$fraseFinal}</p>

                        <br>
                        <p><strong>" . siteConfig('nome_site') . "</strong></p>
                    </div>
                </div>
            </div>
        ";

        enviarEmailSistema($marcacao['email'], $tituloEmail, $html);
    }

    header("Location: ver_marcacao.php?id=" . $id);
    exit;
}

$estadoAtualCor = estadoMarcacaoCor($marcacao['estado']);
$estadoAtualLabel = estadoMarcacaoLabel($marcacao['estado']);
?>

<style>
.exec-marcacao-hero{background:linear-gradient(135deg,#242A30,#11151B);color:white;border-radius:30px;padding:30px;margin-bottom:24px;box-shadow:0 22px 60px rgba(15,23,42,.18);display:grid;grid-template-columns:1.1fr .9fr;gap:24px;align-items:center;position:relative;overflow:hidden}
.exec-marcacao-hero::after{content:"";position:absolute;width:360px;height:360px;border-radius:50%;right:-150px;top:-170px;background:rgba(255,255,255,.07)}
.exec-marcacao-hero>*{position:relative;z-index:2}
.exec-kicker{display:inline-flex;background:rgba(212,170,0,.16);border:1px solid rgba(212,170,0,.35);color:#F0D060;padding:8px 13px;border-radius:999px;text-transform:uppercase;letter-spacing:.7px;font-weight:900;font-size:12px}
.exec-marcacao-hero h2{margin:14px 0 8px;font-size:36px;line-height:1.08}
.exec-marcacao-hero p{margin:0;color:#dbeafe;line-height:1.7}
.exec-status-card{background:rgba(255,255,255,.12);border:1px solid rgba(255,255,255,.17);border-radius:24px;padding:20px;backdrop-filter:blur(12px)}
.exec-status-card strong{display:block;font-size:34px;margin-bottom:6px}
.exec-status-pill{display:inline-flex;align-items:center;gap:8px;background:<?= $estadoAtualCor ?>;color:white;border-radius:999px;padding:9px 13px;font-weight:900;text-transform:uppercase;font-size:12px}
.exec-grid{display:grid;grid-template-columns:minmax(0,.95fr) minmax(420px,1.05fr);gap:24px;align-items:start}
.exec-card{background:white;border:1px solid #e5e7eb;border-radius:26px;padding:24px;box-shadow:0 16px 45px rgba(15,23,42,.08)}
.exec-card h3{margin:0 0 16px;color:#11151B;font-size:24px}
.exec-info-list{display:grid;gap:12px}
.exec-info-row{background:#f8fafc;border:1px solid #eef2f7;border-radius:16px;padding:13px 15px}
.exec-info-row strong{display:block;color:#64748b;font-size:12px;text-transform:uppercase;letter-spacing:.5px;margin-bottom:5px}
.exec-info-row span{color:#11151B;font-weight:900}
.exec-form-grid{display:grid;grid-template-columns:1fr 1fr;gap:16px}
.exec-form-grid .full{grid-column:span 2}
.exec-card label{display:block;color:#11151B;font-weight:900;margin-bottom:8px;font-size:13px}
.exec-card input,.exec-card select,.exec-card textarea{width:100%;box-sizing:border-box;border:1px solid #dbe4ee;background:#f8fafc;color:#11151B;border-radius:15px;min-height:48px;padding:0 14px;font-weight:800;outline:none}
.exec-card textarea{min-height:120px;padding:14px;line-height:1.6}
.exec-actions{display:flex;gap:12px;flex-wrap:wrap;align-items:center;margin-top:18px;padding-top:18px;border-top:1px solid #eef2f7}
.exec-note{background:linear-gradient(135deg,rgba(212,170,0,.16),rgba(36,42,50,.07));border:1px solid rgba(212,170,0,.28);border-radius:18px;padding:16px;color:#334155;line-height:1.6;font-weight:700;margin-top:18px}
.exec-timeline{display:grid;gap:12px}
.exec-step{display:grid;grid-template-columns:42px 1fr;gap:13px;align-items:start}
.exec-step i{width:42px;height:42px;border-radius:15px;background:#242A30;color:white;display:grid;place-items:center;font-style:normal;font-weight:900}
.exec-step div{background:#f8fafc;border:1px solid #eef2f7;border-radius:16px;padding:13px}
.exec-step strong{display:block;color:#11151B;margin-bottom:4px}
.exec-step span{color:#64748b;font-size:13px;line-height:1.5}
@media(max-width:1050px){.exec-marcacao-hero,.exec-grid{grid-template-columns:1fr}}
@media(max-width:720px){.exec-form-grid{grid-template-columns:1fr}.exec-form-grid .full{grid-column:span 1}}
</style>

<div class="exec-marcacao-hero">
    <div>
        <span class="exec-kicker">Agenda Executiva Digital</span>
        <h2><?= htmlspecialchars($marcacao['assunto']) ?></h2>
        <p>
            Pedido de atendimento de <?= htmlspecialchars($marcacao['nome']) ?>.
            Defina responsável, data oficial, hora e estado da marcação.
        </p>
    </div>

    <div class="exec-status-card">
        <span class="exec-status-pill"><?= htmlspecialchars($estadoAtualLabel) ?></span>
        <strong><?= date('d/m/Y', strtotime($marcacao['data_marcacao'])) ?></strong>
        <p><?= substr($marcacao['hora_marcacao'], 0, 5) ?> · <?= htmlspecialchars($marcacao['responsavel'] ?: 'Responsável por definir') ?></p>
    </div>
</div>

<div class="exec-grid">

    <div class="exec-card">
        <h3>Dados do pedido</h3>

        <div class="exec-info-list">
            <div class="exec-info-row"><strong>Munícipe</strong><span><?= htmlspecialchars($marcacao['nome']) ?></span></div>
            <div class="exec-info-row"><strong>Email</strong><span><?= htmlspecialchars($marcacao['email']) ?></span></div>
            <div class="exec-info-row"><strong>Telefone</strong><span><?= htmlspecialchars($marcacao['telefone'] ?? '-') ?></span></div>
            <div class="exec-info-row"><strong>Assunto</strong><span><?= htmlspecialchars($marcacao['assunto']) ?></span></div>
            <div class="exec-info-row"><strong>Data pretendida/atual</strong><span><?= date('d/m/Y', strtotime($marcacao['data_marcacao'])) ?></span></div>
            <div class="exec-info-row"><strong>Hora pretendida/atual</strong><span><?= substr($marcacao['hora_marcacao'], 0, 5) ?></span></div>
            <div class="exec-info-row"><strong>Responsável atual</strong><span><?= htmlspecialchars($marcacao['responsavel'] ?: 'Ainda não definido') ?></span></div>
            <div class="exec-info-row"><strong>Preferência / tipo atual</strong><span><?= (($marcacao['tipo_atendimento'] ?? 'presencial') === 'virtual') ? '<i class="bi bi-laptop"></i> Virtual' : '<i class="bi bi-building"></i> Presencial' ?></span></div>
        </div>

        <?php if (!empty($marcacao['mensagem'])): ?>
            <div class="exec-note">
                <strong>Mensagem do munícipe:</strong><br>
                <?= nl2br(htmlspecialchars($marcacao['mensagem'])) ?>
            </div>
        <?php endif; ?>

        <div class="exec-note">
            <strong>Fluxo recomendado:</strong><br>
            1. Escolher responsável executivo/técnico.<br>
            2. Confirmar data e hora disponíveis.<br>
            3. Mudar estado para confirmada.<br>
            4. Guardar e notificar automaticamente o munícipe.
        </div>
    </div>

    <div class="exec-card">
        <h3>Gestão executiva da marcação</h3>

        <form method="POST">

            <div class="exec-form-grid">

                <div>
                    <label>Responsável</label>
                    <select name="responsavel">
                        <option value="">A definir</option>
                        <option value="Presidente" <?= ($marcacao['responsavel'] ?? '') == 'Presidente' ? 'selected' : '' ?>>Presidente</option>
                        <option value="Vice-Presidente" <?= ($marcacao['responsavel'] ?? '') == 'Vice-Presidente' ? 'selected' : '' ?>>Vice-Presidente</option>
                        <option value="Vogal" <?= ($marcacao['responsavel'] ?? '') == 'Vogal' ? 'selected' : '' ?>>Vogal</option>
                        <option value="Técnico Administrativo" <?= ($marcacao['responsavel'] ?? '') == 'Técnico Administrativo' ? 'selected' : '' ?>>Técnico Administrativo</option>
                        <option value="Gabinete Técnico" <?= ($marcacao['responsavel'] ?? '') == 'Gabinete Técnico' ? 'selected' : '' ?>>Gabinete Técnico</option>
                        <option value="Outro Responsável" <?= ($marcacao['responsavel'] ?? '') == 'Outro Responsável' ? 'selected' : '' ?>>Outro Responsável</option>
                    </select>
                </div>

                <div>
                    <label>Tipo de atendimento decidido pelo executivo</label>
                    <select name="tipo_atendimento" id="tipoAtendimentoExec">
                        <option value="presencial" <?= ($marcacao['tipo_atendimento'] ?? 'presencial') === 'presencial' ? 'selected' : '' ?>>Presencial</option>
                        <option value="virtual" <?= ($marcacao['tipo_atendimento'] ?? '') === 'virtual' ? 'selected' : '' ?>>Virtual</option>
                    </select>
                </div>

                <div>
                    <label>Estado</label>
                    <select name="estado">
                        <option value="pendente" <?= $marcacao['estado'] == 'pendente' ? 'selected' : '' ?>>Pendente</option>
                        <option value="confirmada" <?= $marcacao['estado'] == 'confirmada' ? 'selected' : '' ?>>Confirmada</option>
                        <option value="cancelada" <?= $marcacao['estado'] == 'cancelada' ? 'selected' : '' ?>>Cancelada</option>
                        <option value="concluida" <?= $marcacao['estado'] == 'concluida' ? 'selected' : '' ?>>Concluída</option>
                    </select>
                </div>

                <div>
                    <label>Data oficial</label>
                    <input type="date" name="data_marcacao" value="<?= htmlspecialchars($marcacao['data_marcacao']) ?>" required>
                </div>

                <div>
                    <label>Hora oficial</label>
                    <input type="time" name="hora_marcacao" value="<?= htmlspecialchars(substr($marcacao['hora_marcacao'], 0, 5)) ?>" required>
                </div>

                <div class="full" id="campoLinkReuniaoVirtual">
                    <label>Link da reunião virtual</label>
                    <input type="url" name="link_reuniao" placeholder="https://meet.google.com/..." value="<?= htmlspecialchars($marcacao['link_reuniao'] ?? '') ?>">
                </div>

                <div class="full">
                    <label>Resposta ao munícipe</label>
                    <textarea name="resposta" placeholder="Mensagem enviada ao munícipe por email..."><?= htmlspecialchars($marcacao['resposta'] ?? '') ?></textarea>
                </div>

                <div class="full">
                    <label>Observações internas / administrativas</label>
                    <textarea name="observacoes_admin" placeholder="Notas internas para a Junta..."><?= htmlspecialchars($marcacao['observacoes_admin'] ?? '') ?></textarea>
                </div>

            </div>

            <div class="exec-actions">
                <button class="btn">Guardar e notificar cidadão</button>
                <a class="btn secondary" href="marcacoes.php">Voltar</a>
            </div>

        </form>
    </div>

</div>

<div class="exec-card" style="margin-top:24px;">
    <h3>Timeline da marcação</h3>

    <div class="exec-timeline">
        <div class="exec-step"><i>1</i><div><strong>Pedido submetido</strong><span>O munícipe submeteu o pedido de atendimento no Balcão Virtual.</span></div></div>
        <div class="exec-step"><i>2</i><div><strong>Análise executiva</strong><span>A Junta analisa o assunto, define responsável, data e hora oficial.</span></div></div>
        <div class="exec-step"><i>3</i><div><strong>Confirmação automática</strong><span>Quando guardar como confirmada, o munícipe recebe email com todos os detalhes.</span></div></div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const tipo = document.getElementById('tipoAtendimentoExec');
    const campo = document.getElementById('campoLinkReuniaoVirtual');

    function toggleLinkVirtual() {
        if (!tipo || !campo) return;
        campo.style.display = tipo.value === 'virtual' ? 'block' : 'none';
    }

    if (tipo) {
        tipo.addEventListener('change', toggleLinkVirtual);
        toggleLinkVirtual();
    }
});
</script>

<?php require_once "includes/footer.php"; ?>
