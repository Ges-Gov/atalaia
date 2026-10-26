<?php
/**
 * Arquivo automático de editais e consultas públicas (CORE).
 * Passa a inativos (saem do site) os documentos cujo prazo de afixação — campo
 * "Prazo de afixação / consulta pública" no admin (documentos.data_limite) — já terminou,
 * e envia um relatório por e-mail para o endereço de notificações da Junta.
 * Documentos sem prazo nunca são tocados. Pode voltar-se a ativar qualquer um no admin.
 *
 * Cron diário no servidor (ex.: às 02h00):
 *     0 2 * * * php /caminho/do/site/cron/arquivar_editais.php
 * Só corre na linha de comandos (a pasta cron/ também está bloqueada no .htaccess).
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/mail_helper.php';

$hoje = date('Y-m-d');

try {
    $st = $pdo->prepare("SELECT id, titulo, categoria, data_limite FROM documentos WHERE ativo = 1 AND data_limite IS NOT NULL AND data_limite < ? ORDER BY data_limite");
    $st->execute([$hoje]);
    $expirados = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (Throwable $e) {
    fwrite(STDERR, "Erro a ler os documentos: " . $e->getMessage() . PHP_EOL);
    exit(1);
}

if (!$expirados) {
    echo "Nenhum documento com prazo terminado. Nada a fazer." . PHP_EOL;
    exit(0);
}

$arquivar = $pdo->prepare("UPDATE documentos SET ativo = 0, arquivado_em = NOW() WHERE id = ? AND ativo = 1");
$registo = $pdo->prepare("INSERT INTO logs_operacionais (admin_id, tipo, mensagem) VALUES (NULL, 'arquivo_automatico', ?)");
$linhas = '';
foreach ($expirados as $d) {
    $arquivar->execute([$d['id']]);
    $msg = "Documento #{$d['id']} «{$d['titulo']}» arquivado (prazo terminou a " . date('d/m/Y', strtotime($d['data_limite'])) . ").";
    try { $registo->execute([$msg]); } catch (Throwable $e) { /* o registo não pode impedir o arquivo */ }
    echo $msg . PHP_EOL;
    $linhas .= '<tr><td style="padding:6px 10px;border-bottom:1px solid #e5e7eb;">' . htmlspecialchars($d['titulo'])
        . '</td><td style="padding:6px 10px;border-bottom:1px solid #e5e7eb;">' . htmlspecialchars($d['categoria'])
        . '</td><td style="padding:6px 10px;border-bottom:1px solid #e5e7eb;">' . date('d/m/Y', strtotime($d['data_limite'])) . '</td></tr>';
}

$para = siteConfig('email_notificacoes', siteConfig('email'));
if ($para) {
    $n = count($expirados);
    $html = '<p>Os seguintes documentos terminaram o prazo de afixação e foram retirados do site automaticamente:</p>'
        . '<table style="border-collapse:collapse;font-family:sans-serif;font-size:14px;"><tr style="background:#f1f5f9;"><th style="padding:6px 10px;text-align:left;">Documento</th><th style="padding:6px 10px;text-align:left;">Categoria</th><th style="padding:6px 10px;text-align:left;">Prazo</th></tr>'
        . $linhas . '</table>'
        . '<p>Se algum tiver de continuar visível, volte a ativá-lo em <em>Admin → Documentos</em> e altere ou apague o prazo.</p>'
        . '<p style="color:#64748b;font-size:12px;">' . htmlspecialchars(siteConfig('nome_site')) . ' — mensagem automática.</p>';
    $ok = enviarEmailSistema($para, "Arquivo automático: {$n} documento" . ($n === 1 ? '' : 's') . " retirado" . ($n === 1 ? '' : 's') . " do site", $html);
    echo $ok ? "Relatório enviado para {$para}." . PHP_EOL : "Relatório NÃO enviado (e-mail não configurado ou falhou)." . PHP_EOL;
}
exit(0);
