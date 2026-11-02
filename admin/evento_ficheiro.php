<?php
/**
 * Abre um ficheiro (foto/PDF) enviado numa inscrição de evento — só para quem tem
 * sessão no backoffice. Os ficheiros ficam em uploads/eventos-inscricoes/ e não se
 * abrem diretamente pelo site (includes/eventos_campos.php).
 */
require_once "includes/auth.php";
require_once "../includes/db.php";
require_once "../includes/eventos_campos.php";

$stmt = $pdo->prepare("SELECT campos_extra FROM eventos_inscricoes WHERE id = ?");
$stmt->execute([(int)($_GET['inscricao'] ?? 0)]);
$extra = json_decode((string)$stmt->fetchColumn(), true);
$valor = is_array($extra) ? ($extra[(int)($_GET['campo'] ?? 0)] ?? null) : null;

$nome = eventoFicheiroNome($valor);
$caminho = EVENTO_FICHEIROS_DIR . $nome;
if ($nome === '' || !is_file($caminho)) {
    http_response_code(404);
    die("Ficheiro não encontrado.");
}

$ext = pathinfo($nome, PATHINFO_EXTENSION);
$original = (string)($valor['nome'] ?? $nome);

while (ob_get_level()) ob_end_clean();
header('Content-Type: ' . (EVENTO_FICHEIRO_TIPOS[$ext] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($caminho));
header('Content-Disposition: inline; filename="' . preg_replace('/[^A-Za-z0-9._-]+/', '_', $original) . '"; filename*=UTF-8\'\'' . rawurlencode($original));
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($caminho);
exit;
