<?php
/**
 * Responder a uma sugestão ou mensagem de contacto a partir do backoffice (05/11/2026).
 * Antes o «Responder» era um mailto:, que só funciona se o computador tiver um programa de email
 * configurado. Agora o site envia a resposta (includes/mail_helper.php), com «responder para» o email
 * da Junta e a mensagem original citada, e guarda o que se respondeu, quando e quem (migração 087).
 */
require_once __DIR__ . '/../../includes/mail_helper.php';

const RESPONDER_TABELAS = [
    'sugestoes' => [
        'ler'     => "SELECT * FROM sugestoes WHERE id = ?",
        'gravar'  => "UPDATE sugestoes SET resposta = ?, respondida_em = NOW(), respondida_por = ?, lida = 1 WHERE id = ?",
    ],
    'contactos_mensagens' => [
        'ler'     => "SELECT * FROM contactos_mensagens WHERE id = ?",
        'gravar'  => "UPDATE contactos_mensagens SET resposta = ?, respondida_em = NOW(), respondida_por = ?, lida = 1 WHERE id = ?",
    ],
];

/** Envia a resposta e regista-a. Devolve '' se correu bem, ou a mensagem de erro. */
function responderMensagem(PDO $pdo, string $tabela, int $id, string $texto): string
{
    $sql = RESPONDER_TABELAS[$tabela] ?? null;
    if (!$sql) return 'Tabela inválida.';
    $st = $pdo->prepare($sql['ler']);
    $st->execute([$id]);
    $m = $st->fetch(PDO::FETCH_ASSOC);
    if (!$m) return 'Mensagem não encontrada.';
    $texto = trim(str_replace("\r\n", "\n", $texto));
    if ($texto === '') return 'Escreva a resposta antes de enviar.';
    if (!filter_var((string)$m['email'], FILTER_VALIDATE_EMAIL)) return 'Esta pessoa não deixou um email válido; não é possível responder por email.';

    $nomeJunta = siteConfig('nome_site', 'Junta de Freguesia');
    $html = '<p>' . nl2br(htmlspecialchars($texto)) . '</p>'
        . '<p>' . htmlspecialchars($nomeJunta) . '</p>'
        . '<hr style="border:0;border-top:1px solid #e5e7eb;margin:24px 0">'
        . '<p style="color:#64748b;font-size:13px">Em resposta à sua mensagem de ' . date('d/m/Y', strtotime($m['criado_em'])) . ' («' . htmlspecialchars($m['assunto']) . '»):</p>'
        . '<blockquote style="color:#64748b;font-size:13px;border-left:3px solid #e5e7eb;margin:0;padding-left:12px">' . nl2br(htmlspecialchars($m['mensagem'])) . '</blockquote>';
    // enviado em nome do email da Junta (remetente), com cópia oculta para a Junta ficar com registo
    $emailJunta = (string)siteConfig('email');
    if (!enviarEmailSistema($m['email'], 'Re: ' . $m['assunto'], $html, $emailJunta, $emailJunta, $emailJunta)) {
        return 'Não foi possível enviar o email (o envio de email do site não está a funcionar). A resposta não foi enviada nem gravada.';
    }
    $pdo->prepare($sql['gravar'])->execute([$texto, (string)($_SESSION['admin'] ?? ''), $id]);
    return '';
}

/** Caixa de resposta (ou a resposta já enviada). $extra = campos escondidos do formulário da página. */
function responderBloco(array $m, array $extra = []): void
{
    if (!empty($m['resposta'])) { ?>
        <div class="resp-feita">
            <strong><i class="bi bi-reply-fill" aria-hidden="true"></i> Respondida a <?= date('d/m/Y H:i', strtotime($m['respondida_em'])) ?><?= !empty($m['respondida_por']) ? ' por ' . htmlspecialchars($m['respondida_por']) : '' ?></strong>
            <div><?= nl2br(htmlspecialchars($m['resposta'])) ?></div>
        </div>
        <?php return;
    }
    if (!filter_var((string)$m['email'], FILTER_VALIDATE_EMAIL)) { ?>
        <p class="resp-sem">Sem email válido — não é possível responder por email.</p>
        <?php return;
    } ?>
    <details class="resp-caixa">
        <summary class="btn"><i class="bi bi-reply" aria-hidden="true"></i> Responder</summary>
        <form method="post" class="resp-form">
            <?php foreach ($extra as $k => $v): ?><input type="hidden" name="<?= htmlspecialchars($k) ?>" value="<?= htmlspecialchars((string)$v) ?>"><?php endforeach; ?>
            <input type="hidden" name="acao" value="responder">
            <input type="hidden" name="id" value="<?= (int)$m['id'] ?>">
            <label>Resposta para <strong><?= htmlspecialchars($m['email']) ?></strong> (assunto «Re: <?= htmlspecialchars($m['assunto']) ?>»)</label>
            <textarea name="resposta" rows="6" required placeholder="Escreva aqui a resposta. A mensagem original vai citada no fim do email."></textarea>
            <div class="resp-botoes">
                <button class="btn" type="submit"><i class="bi bi-send" aria-hidden="true"></i> Enviar resposta</button>
                <a class="resp-mailto" href="mailto:<?= htmlspecialchars($m['email']) ?>?subject=<?= rawurlencode('Re: ' . $m['assunto']) ?>">ou abrir no programa de email</a>
            </div>
        </form>
    </details>
    <?php
}

function responderEstilos(): void
{ ?>
    <style>
    .resp-caixa{margin-top:12px;}
    .resp-caixa > summary{list-style:none;display:inline-flex;align-items:center;gap:6px;cursor:pointer;}
    .resp-caixa > summary::-webkit-details-marker{display:none;}
    .resp-form{margin:12px 0 0;padding:16px;background:#f8fafc;border:1px solid #e5e7eb;border-radius:14px;box-shadow:none;display:flex;flex-direction:column;gap:10px;}
    .resp-form label{font-size:14px;color:#475569;}
    .resp-form textarea{width:100%;box-sizing:border-box;border:1px solid #dbe4ee;border-radius:12px;padding:12px;font:inherit;}
    .resp-botoes{display:flex;gap:14px;align-items:center;flex-wrap:wrap;}
    .resp-mailto{font-size:13px;color:#64748b;}
    .resp-feita{margin-top:12px;padding:12px 16px;background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;color:#14532d;}
    .resp-feita div{margin-top:6px;white-space:normal;line-height:1.6;color:#1f2937;}
    .resp-sem{margin-top:12px;color:#64748b;font-size:14px;}
    .resp-aviso{padding:12px 16px;border-radius:12px;margin:12px 0;font-weight:700;}
    .resp-aviso.ok{background:#dcfce7;color:#166534;}
    .resp-aviso.erro{background:#fee2e2;color:#991b1b;}
    </style>
    <?php
}

/** Aviso do último envio (guardado na sessão antes do redirecionamento). */
function responderAviso(): void
{
    if (empty($_SESSION['resp_aviso'])) return;
    [$tipo, $texto] = $_SESSION['resp_aviso'];
    unset($_SESSION['resp_aviso']);
    echo '<div class="resp-aviso ' . ($tipo === 'ok' ? 'ok' : 'erro') . '">' . htmlspecialchars($texto) . '</div>';
}
