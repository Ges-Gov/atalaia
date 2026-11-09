<?php
/**
 * «Esqueci-me da palavra-passe» no login do backoffice (09/11/2026). CORE: igual em todos os sites.
 *
 * As contas do backoffice não têm email, de propósito (simplicidade). Por isso o pedido não vai para o utilizador:
 * vai para a GesGov, que muda a palavra-passe no backoffice (Utilizadores) e avisa a Junta por email.
 * A pessoa indica só o nome de utilizador; a resposta é sempre a mesma, exista ou não a conta (para não se
 * descobrirem nomes de utilizador). Com token, isco para robôs e limite de pedidos (por sessão, por IP e por dia).
 */

const RECUPERAR_ACESSO_PARA = ['filipe.dordio@gesgov.pt', 'joao.pereira@gesgov.pt'];
const RECUPERAR_ACESSO_POR_IP_HORA = 3;
const RECUPERAR_ACESSO_POR_DIA = 20;

/** O login está no modo «Esqueci-me da palavra-passe»? */
function recuperarAcessoModo(): bool
{
    return isset($_GET['recuperar']) || isset($_POST['recuperar_acesso']);
}

/** Limite de pedidos guardado num ficheiro temporário por site (IP em hash, nunca em claro). */
function recuperarAcessoLimite(): bool
{
    $f = rtrim(sys_get_temp_dir(), '/\\') . '/gg_recuperar_' . md5(__DIR__) . '.json';
    $agora = time();
    $reg = @json_decode((string)@file_get_contents($f), true);
    $reg = is_array($reg) ? array_values(array_filter($reg, fn($r) => is_array($r) && ($r['t'] ?? 0) > $agora - 86400)) : [];
    $ip = hash('sha256', (string)($_SERVER['REMOTE_ADDR'] ?? '') . __DIR__);
    $doIp = array_filter($reg, fn($r) => ($r['ip'] ?? '') === $ip && $r['t'] > $agora - 3600);
    if (count($doIp) >= RECUPERAR_ACESSO_POR_IP_HORA || count($reg) >= RECUPERAR_ACESSO_POR_DIA) return false;
    $reg[] = ['t' => $agora, 'ip' => $ip];
    @file_put_contents($f, json_encode($reg), LOCK_EX);
    return true;
}

/**
 * Trata o envio do formulário. Devolve ['erro' => '', 'enviado' => bool].
 * $pdo: ligação à BD (para dizer à GesGov se a conta existe).
 */
function recuperarAcessoTratar(PDO $pdo): array
{
    if (empty($_SESSION['recuperar_acesso_token'])) {
        $_SESSION['recuperar_acesso_token'] = bin2hex(random_bytes(16));
    }
    if (!empty($_SESSION['recuperar_acesso_enviado'])) {
        unset($_SESSION['recuperar_acesso_enviado']);
        return ['erro' => '', 'enviado' => true];
    }
    if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['recuperar_acesso'])) {
        $_SESSION['recuperar_acesso_aberto'] = time();
        return ['erro' => '', 'enviado' => false];
    }

    $utilizador = trim((string)($_POST['utilizador'] ?? ''));
    $abertoEm = (int)($_SESSION['recuperar_acesso_aberto'] ?? 0);
    $envios = array_filter($_SESSION['recuperar_acesso_envios'] ?? [], fn($t) => $t > time() - 3600);

    if (!hash_equals($_SESSION['recuperar_acesso_token'], (string)($_POST['token'] ?? ''))) {
        return ['erro' => 'A sessão expirou. Volte a carregar a página e tente de novo.', 'enviado' => false];
    }
    $robo = trim((string)($_POST['site_web'] ?? '')) !== '' || ($abertoEm && time() - $abertoEm < 2);
    if (!$robo) {
        // mail_helper.php traz também o emailValido() (nos dois modelos)
        require_once __DIR__ . '/../../includes/mail_helper.php';
        $emailEnvio = trim((string)($_POST['email_envio'] ?? ''));
        if ($utilizador === '' || mb_strlen($utilizador) > 120 || preg_match('/[\x00-\x1F\x7F]/u', $utilizador)) {
            return ['erro' => 'Indique o seu nome de utilizador.', 'enviado' => false];
        }
        $emailOk = $emailEnvio !== '' && mb_strlen($emailEnvio) <= 190
            && (function_exists('emailValido') ? emailValido($emailEnvio) : filter_var($emailEnvio, FILTER_VALIDATE_EMAIL));
        if (!$emailOk) {
            return ['erro' => 'Indique um email válido para onde enviar a nova palavra-passe.', 'enviado' => false];
        }
        if (count($envios) >= 2 || !recuperarAcessoLimite()) {
            return ['erro' => 'Já foi enviado um pedido há pouco. A GesGov vai tratar dele; se for urgente, contacte-nos.', 'enviado' => false];
        }

        // Só para a GesGov saber o que fazer: a conta existe? (a pessoa vê sempre a mesma resposta)
        $estado = 'não existe nenhuma conta com este nome de utilizador';
        try {
            $st = $pdo->prepare("SELECT nome, tipo, ativo FROM admin_utilizadores WHERE username = ? LIMIT 1");
            $st->execute([$utilizador]);
            if ($c = $st->fetch(PDO::FETCH_ASSOC)) {
                $estado = 'existe — ' . trim(($c['nome'] ?: 'sem nome') . ', tipo ' . ($c['tipo'] ?: 'admin')) . ($c['ativo'] ? ', ativa' : ', DESATIVADA');
            }
        } catch (Throwable $e) {
            $estado = 'não foi possível verificar';
        }

        $junta = (string)siteConfig('nome_site', 'Junta de Freguesia');
        $host = preg_replace('/[^a-z0-9.\-:]/i', '', (string)($_SERVER['HTTP_HOST'] ?? ''));
        $esquema = ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https') ? 'https' : 'http';
        $linkUtilizadores = $esquema . '://' . $host . '/admin/utilizadores.php';
        $emailJunta = (string)siteConfig('email', '');
        $h = fn($t) => htmlspecialchars((string)$t, ENT_QUOTES, 'UTF-8');
        $assunto = 'Pedido de nova palavra-passe — ' . $junta . ' — utilizador «' . $utilizador . '»';
        $html = "<h2>Pedido de nova palavra-passe do backoffice</h2>"
            . "<p><strong>Junta de Freguesia:</strong> {$h($junta)}<br><strong>Site:</strong> {$h($host)}<br>"
            . "<strong>Utilizador indicado:</strong> «{$h($utilizador)}»<br><strong>Conta:</strong> {$h($estado)}</p>"
            . "<p style=\"font-size:16px\"><strong>Enviar a nova palavra-passe para:</strong> {$h($emailEnvio)}</p>"
            . ($emailJunta !== '' && strcasecmp($emailEnvio, $emailJunta) !== 0
                ? "<p style=\"background:#fef3c7;color:#92400e;padding:10px 12px;border-radius:8px\"><strong>Atenção:</strong> este não é o email da Junta "
                  . "registado no site ({$h($emailJunta)}). Se parecer estranho, confirmar com a Junta antes de enviar.</p>"
                : '')
            . "<p><strong>Pedido feito a:</strong> " . date('d/m/Y \à\s H:i') . "</p>"
            . "<p><strong>O que fazer:</strong> entrar no backoffice com a conta da GesGov, mudar a palavra-passe em "
            . "<a href=\"{$h($linkUtilizadores)}\">Utilizadores</a> e enviar a nova para o email indicado acima.</p>"
            . "<p style=\"color:#64748b;font-size:13px\">Se a Junta não pediu isto, pode ser alguém a experimentar o botão: não é preciso fazer nada.</p>";

        $ok = false;
        foreach (RECUPERAR_ACESSO_PARA as $para) {
            try {
                $ok = enviarEmailSistema($para, $assunto, $html) || $ok;
            } catch (Throwable $e) {
                error_log('recuperar acesso: ' . $e->getMessage());
            }
        }
        if (!$ok) {
            return ['erro' => 'Não foi possível enviar o pedido agora. Tente mais tarde ou contacte a GesGov.', 'enviado' => false];
        }
        $envios[] = time();
        $_SESSION['recuperar_acesso_envios'] = $envios;
    }

    // Depois de enviar, volta ao formulário por GET (assim recarregar a página não repete o pedido)
    $_SESSION['recuperar_acesso_enviado'] = 1;
    header('Location: login.php?recuperar=1');
    exit;
}

/** O formulário (no lugar do de entrada) e, depois de enviado, a confirmação. */
function recuperarAcessoFormulario(array $estado): void
{
    if ($estado['enviado']): ?>
        <div class="recuperar-ok" role="status">
            <strong><i class="bi bi-check-circle"></i> Pedido enviado à GesGov.</strong>
            Vamos tratar do pedido e enviar a nova palavra-passe para o email indicado.
        </div>
        <a class="login-submit recuperar-voltar" href="login.php">Voltar à entrada</a>
    <?php return; endif; ?>
    <?php if ($estado['erro'] !== ''): ?>
        <div class="erro" role="alert"><?= htmlspecialchars($estado['erro']) ?></div>
    <?php endif; ?>
    <form method="POST" action="login.php?recuperar=1" autocomplete="off">
        <input type="hidden" name="recuperar_acesso" value="1">
        <input type="hidden" name="token" value="<?= htmlspecialchars($_SESSION['recuperar_acesso_token'] ?? '') ?>">
        <div class="recuperar-isco" aria-hidden="true">
            <label for="rcSiteWeb">Não preencher</label>
            <input type="text" id="rcSiteWeb" name="site_web" tabindex="-1" autocomplete="off">
        </div>
        <p class="recuperar-texto">Indique o seu nome de utilizador e o email para onde enviar a nova palavra-passe.
            Ao carregar no botão, a <strong>GesGov</strong> recebe o pedido.</p>
        <label for="rcUser">Utilizador</label>
        <input type="text" name="utilizador" id="rcUser" placeholder="O seu nome de utilizador" required maxlength="120" autocomplete="username">
        <label for="rcEmail">Email para onde enviar a nova palavra-passe</label>
        <input type="email" name="email_envio" id="rcEmail" placeholder="nome@exemplo.pt" required maxlength="190" autocomplete="email">
        <button type="submit" class="login-submit">Pedir nova palavra-passe</button>
    </form>
    <a class="recuperar-link" href="login.php"><i class="bi bi-arrow-left"></i> Voltar à entrada</a>
<?php
}

/** Estilos do modo de recuperação (usam as classes do login). */
function recuperarAcessoEstilos(): void
{ ?>
<style>
/* cores do cartão escuro do login (as mesmas da «Voltar ao site» e do texto do cartão) */
.recuperar-link{display:inline-flex;align-items:center;gap:6px;margin-top:14px;font-size:14px;font-weight:600;color:#dbeafe;text-decoration:none;}
.recuperar-link:hover{color:var(--ouro, #C8A02E);text-decoration:underline;}
.recuperar-texto{font-size:14px;line-height:1.55;color:#dbe3ec;margin:0 0 14px;}
.recuperar-texto strong{color:#fff;}
.recuperar-ok{padding:14px 16px;border-radius:14px;background:#dcfce7;color:#166534;line-height:1.55;font-size:14px;margin-bottom:14px;}
.recuperar-ok strong{display:block;margin-bottom:4px;}
/* o «Voltar à entrada» é um <a> com o aspeto do botão: texto centrado na vertical (antes ficava colado em cima) */
a.login-submit.recuperar-voltar{display:flex;align-items:center;justify-content:center;text-decoration:none;box-sizing:border-box;}
.recuperar-isco{position:absolute;left:-9999px;width:1px;height:1px;overflow:hidden;}
</style>
<?php
}
