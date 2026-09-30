<?php
/**
 * Endpoint do formulário de subscrição no rodapé (aparece em todas as
 * páginas) — fica num ficheiro à parte porque o footer.php já vai a meio
 * do output quando é incluído, e não pode fazer header() de redirect.
 * Volta sempre para a página onde o formulário foi submetido.
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . "/includes/config.php";

$destino = '/';
if (!empty($_SERVER['HTTP_REFERER'])) {
    // parse_url(..., PHP_URL_HOST) nunca inclui a porta — comparar direto
    // com HTTP_HOST (que inclui a porta quando não é a 80/443 por omissão)
    // falha sempre em ambientes com porta não-padrão, como o servidor
    // local de desenvolvimento (localhost:8391).
    $refererHost = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_HOST);
    $hostAtual = parse_url('http://' . ($_SERVER['HTTP_HOST'] ?? ''), PHP_URL_HOST);
    if ($refererHost !== null && $refererHost === $hostAtual) {
        $destino = parse_url($_SERVER['HTTP_REFERER'], PHP_URL_PATH) ?: '/';
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $honeypot = trim($_POST['website'] ?? '');

    if ($honeypot === '' && filter_var($email, FILTER_VALIDATE_EMAIL)) {
        try {
            $pdo->prepare("INSERT INTO newsletter_subscribers (email, origem) VALUES (?, 'site')")->execute([$email]);
        } catch (PDOException $e) {
            // Email já subscrito — do ponto de vista de quem preencheu o
            // formulário isto não é um erro, mostra-se sucesso na mesma.
        }
    }
}

header("Location: " . $destino . "?newsletter=ok");
exit;
