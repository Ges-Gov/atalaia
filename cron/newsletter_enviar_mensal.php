<?php
/**
 * Envio automático mensal da newsletter — CORE, idêntico em todos os
 * sites. Pensado para correr uma vez por dia via cron do servidor
 * (ex: `0 8 * * * php /caminho/do/site/cron/newsletter_enviar_mensal.php`),
 * tal como o sistema de referência (Laravel Scheduler diário que decide
 * sozinho se hoje é o dia certo) — só que aqui é o próprio cron do SO,
 * sem precisar de nenhuma infraestrutura de fila/scheduler extra.
 *
 * Só envia se: o envio automático estiver ligado, hoje for o dia
 * configurado (ajustado para meses mais curtos que o dia escolhido), e
 * ainda não tiver sido enviada este mês (evita duplicar se o cron correr
 * mais que uma vez no mesmo dia, ou o servidor reiniciar a meio).
 */

require_once __DIR__ . '/../includes/config.php';
require_once __DIR__ . '/../includes/newsletter.php';

// NUNCA chamar isto "$config" — colide com a variável global do mesmo
// nome que includes/config.php usa para a configuração do SITE (lida por
// siteConfig()). Já aconteceu em admin/newsletter.php: o logo e o nome do
// site ficavam sempre com o valor por omissão porque este array substituía
// silenciosamente o outro.
$nlConfig = newsletterConfig($pdo);

if (empty($nlConfig['auto_ativo'])) {
    echo "Envio automático desligado. Nada a fazer." . PHP_EOL;
    exit(0);
}

if ($nlConfig['ultimo_envio'] === date('Y-m')) {
    echo "Já foi enviada este mês (" . date('Y-m') . "). Nada a fazer." . PHP_EOL;
    exit(0);
}

$ultimoDiaDoMes = (int) date('t');
$diaAlvo = min((int) $nlConfig['dia_envio'], $ultimoDiaDoMes);

if ((int) date('j') !== $diaAlvo) {
    echo "Hoje não é o dia configurado (dia {$diaAlvo} deste mês). Nada a fazer." . PHP_EOL;
    exit(0);
}

$enviados = newsletterEnviar($pdo);
if ($enviados === 0) {
    echo "Falha total no envio (0 entregues) — verifique includes/mail_helper.php. Não ficou registado como enviado este mês, corre outra vez amanhã." . PHP_EOL;
} else {
    echo "Newsletter enviada a {$enviados} subscritor(es)." . PHP_EOL;
}
