<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/config.php';

// Conta de envio partilhada por todos os 17 sites das Juntas de Freguesia
// (caixa de correio do próprio FastPanel, não Gmail) — só o nome no "De:"
// muda por site, via siteConfig('nome_site'). Nunca a caixa da freguesia.
const SMTP_HOST = 'mail.gesgov.pt';
const SMTP_USER = 'newsletter@gesgov.pt';

// A password vive à parte, em includes/mail_config.php — nunca neste
// ficheiro. Assim mail_helper.php pode voltar a viajar em zips sem risco
// de apagar uma password já configurada no servidor (já aconteceu uma vez).
(function () {
    $mailConfig = require __DIR__ . '/mail_config.php';
    define('SMTP_PASS', $mailConfig['pass'] ?? '');
})();

// Valida formato de email antes de o usar como destinatário em formulários públicos.
function emailValido($email) {
    return $email !== '' && filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

// Trava submissões repetidas do mesmo browser/sessão em menos de $segundos.
// Defesa simples contra scripts a rebentar formulários públicos com envios em massa.
function submissaoRecente($chave, $segundos = 20) {
    $agora = time();
    $ultima = $_SESSION['ultimo_envio_' . $chave] ?? 0;

    if ($agora - $ultima < $segundos) {
        return true;
    }

    $_SESSION['ultimo_envio_' . $chave] = $agora;
    return false;
}

function enviarEmailSistema($para, $assunto, $html, $replyTo = null) {
    // Sem password configurada: não tenta enviar (evita timeouts nos formulários).
    if (SMTP_PASS === '') {
        return false;
    }

    $mail = new PHPMailer(true);

    try {
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;

        $mail->Username = SMTP_USER;
        $mail->Password = SMTP_PASS;

        $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
        $mail->Port = 465;

        $mail->CharSet = 'UTF-8';

        $mail->setFrom(SMTP_USER, siteConfig('nome_site', 'Junta de Freguesia'));
        $mail->addAddress($para);

        // Para envios em nome da freguesia a partir de uma conta partilhada
        // (ex: newsletter) — respostas vão para o contacto real da Junta,
        // não para a conta de envio partilhada por todos os sites.
        if ($replyTo && emailValido($replyTo)) {
            $mail->addReplyTo($replyTo, siteConfig('nome_site', 'Junta de Freguesia'));
        }

        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body = $html;

        return $mail->send();

    } catch (Exception $e) {
        return false;
    }
}