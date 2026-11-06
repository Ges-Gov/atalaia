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

// emailValido() e telefoneValido() vivem em validacao.php (30/10/2026: o email passou a ver também o domínio).
require_once __DIR__ . '/validacao.php';

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

// IPs públicos dos servidores onde os sites correm: o nosso VPS (o SERVER_ADDR lá é o do proxy interno, 127.0.0.1)
// e o alojamento da Junta da Atalaia (juf.aaej.pt)
const SERVIDORES_ENVIO_IPS = ['185.205.244.198', '185.12.116.247'];

/** Domínios do site, sem www: o do pedido (HTTP_HOST) e o de configuracoes_site.dominio — este ainda é o endereço
 *  temporário (nip.io) em vários sites que já passaram para o domínio próprio. */
function dominiosSite(): array {
    $r = [];
    foreach ([(string)($_SERVER['HTTP_HOST'] ?? ''), (string)siteConfig('dominio', '')] as $d) {
        $d = strtolower(preg_replace('#^(https?://)?(www\.)?#i', '', trim($d)));
        $d = (string)strtok($d, '/:');
        if ($d !== '' && strpos($d, '.') !== false && !filter_var($d, FILTER_VALIDATE_IP)) $r[] = $d;
    }
    return array_values(array_unique($r));
}

/** Domínio principal do site (o do pedido; sem pedido, o da configuração). */
function dominioSite(): string {
    return dominiosSite()[0] ?? '';
}

/** O SPF do domínio autoriza o servidor onde o site corre? (ip4 exato, a e mx do próprio domínio; não segue include) */
function spfAutorizaServidor(string $dominio): bool {
    static $cache = [];
    $dominio = strtolower(trim($dominio));
    if ($dominio === '') return false;
    if (isset($cache[$dominio])) return $cache[$dominio];
    $ips = array_values(array_filter(array_unique(array_merge([(string)($_SERVER['SERVER_ADDR'] ?? '')], SERVIDORES_ENVIO_IPS)),
        fn($ip) => $ip !== '' && strpos($ip, '127.') !== 0));
    $spf = '';
    foreach (@dns_get_record($dominio, DNS_TXT) ?: [] as $r) {
        $t = (string)($r['txt'] ?? implode('', $r['entries'] ?? []));
        if (stripos($t, 'v=spf1') === 0) { $spf = $t; break; }
    }
    $ok = false;
    foreach ($spf !== '' ? preg_split('/\s+/', $spf) : [] as $tok) {
        $tok = ltrim($tok, '+');
        if (stripos($tok, 'ip4:') === 0 && in_array(substr($tok, 4), $ips, true)) { $ok = true; break; }
        if (strcasecmp($tok, 'a') === 0 && array_intersect(gethostbynamel($dominio) ?: [], $ips)) { $ok = true; break; }
        if (strcasecmp($tok, 'mx') === 0) {
            foreach (@dns_get_record($dominio, DNS_MX) ?: [] as $mx) {
                if (array_intersect(gethostbynamel((string)$mx['target']) ?: [], $ips)) { $ok = true; break 2; }
            }
        }
    }
    return $cache[$dominio] = $ok;
}

/** O remetente técnico do mail() do PHP (o «-f» do sendmail_path, que o FastPanel fixou na criação do site, quase sempre
 *  admin@<site>.nip.io) é do mesmo domínio que $dominio? Sem isso o DMARC falha. Sem «-f» fixo (ex.: cPanel da Atalaia)
 *  vale o nosso. Só conta quando o sendmail direto (enviarEmailLocal) não está disponível. */
function envelopeServidorAlinhado(string $dominio): bool {
    $dominio = strtolower(trim($dominio));
    if ($dominio === '') return false;
    if (!preg_match('/-f\s*[\'"]?[^\s\'"@]+@([^\s\'"]+)/', (string)ini_get('sendmail_path'), $m)) return true;
    $env = strtolower($m[1]);
    return $env === $dominio || str_ends_with($env, '.' . $dominio) || str_ends_with($dominio, '.' . $env);
}

/** Pode sair do servidor local em nome deste domínio? (o SPF do domínio autoriza o servidor) */
function podeEnviarComo(string $dominio): bool {
    return spfAutorizaServidor($dominio);
}

/** Remetente para emails em nome da Junta (ex.: «Responder», 06/11/2026): o email da Junta, se o domínio dele autorizar
 *  este servidor; senão geral@<domínio do site>, se este o autorizar; senão '' (fica a conta de envio partilhada). */
function remetenteJunta(): string {
    $email = (string)siteConfig('email', '');
    if ($email !== '' && emailValido($email) && podeEnviarComo(substr((string)strrchr($email, '@'), 1))) return $email;
    foreach (dominiosSite() as $dominio) {
        if (podeEnviarComo($dominio)) return 'geral@' . $dominio;
    }
    return '';
}

// $de / $bcc (05/11/2026): remetente próprio (ex.: o email da Junta, no «Responder») e cópia oculta. Se o SPF do
// domínio do remetente autorizar este servidor e o remetente técnico do servidor for desse domínio (podeEnviarComo),
// o email sai pelo servidor de email local, em nome dele; senão sai
// pelo SMTP partilhado, com a conta de envio no «De:».
function enviarEmailSistema($para, $assunto, $html, $replyTo = null, $de = null, $bcc = null) {
    if ($de && emailValido($de) && podeEnviarComo(substr((string)strrchr($de, '@'), 1))
        && enviarEmailLocal($para, $assunto, $html, $replyTo, $de, $bcc)) {
        return true;
    }

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
        if ($bcc && emailValido($bcc)) $mail->addBCC($bcc);

        $mail->isHTML(true);
        $mail->Subject = $assunto;
        $mail->Body = $html;

        return $mail->send();

    } catch (Exception $e) {
        // 05/11/2026: alguns alojamentos (o cPanel da Atalaia) bloqueiam ligações SMTP para fora. Se nem chegou a ligar
        // ao nosso servidor, envia pelo servidor de email local do alojamento, em nome do domínio do próprio site.
        if (stripos((string)$mail->ErrorInfo, 'connect') === false) return false;
        return enviarEmailLocal($para, $assunto, $html, $replyTo, $de, $bcc);
    }
}

// Envio pelo servidor de email local, em nome de $de (se o SPF do domínio dele autorizar este servidor, ou se for o
// domínio do site) ou de noreply@<domínio do site>. 06/11/2026: chama o /usr/sbin/sendmail diretamente, com o remetente
// técnico («-f») igual ao «De:» — o mail() do PHP no VPS tem o «-f» fixado pelo FastPanel (admin@<site>.nip.io), o que
// faz falhar o DMARC. Se o sendmail direto falhar, usa o mail() só quando esse «-f» é do mesmo domínio.
function enviarEmailLocal($para, $assunto, $html, $replyTo = null, $de = null, $bcc = null) {
    $dominio = dominioSite();
    if ($dominio === '') return false;
    $dDe = $de ? strtolower(substr((string)strrchr($de, '@'), 1)) : '';
    $deOk = $de && emailValido($de) && ($dDe === $dominio || podeEnviarComo($dDe));
    $remetente = $deOk ? $de : 'noreply@' . $dominio;
    $dRem = strtolower(substr((string)strrchr($remetente, '@'), 1));

    $vias = ['sendmail'];
    if (function_exists('mail') && envelopeServidorAlinhado($dRem)) $vias[] = 'mail';
    foreach ($vias as $via) {
        $mail = new PHPMailer(true);
        try {
            if ($via === 'sendmail') { $mail->isSendmail(); $mail->Sendmail = '/usr/sbin/sendmail'; } else { $mail->isMail(); }
            $mail->CharSet = 'UTF-8';
            $mail->setFrom($remetente, siteConfig('nome_site', 'Junta de Freguesia'));
            $mail->addAddress($para);
            if ($replyTo && emailValido($replyTo)) {
                $mail->addReplyTo($replyTo, siteConfig('nome_site', 'Junta de Freguesia'));
            }
            if ($bcc && emailValido($bcc)) $mail->addBCC($bcc);
            $mail->isHTML(true);
            $mail->Subject = $assunto;
            $mail->Body = $html;
            if ($mail->send()) return true;
        } catch (\Throwable $e) {
            // tenta a via seguinte
        }
    }
    return false;
}
