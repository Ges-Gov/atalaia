<?php
/**
 * Abre a imagem de uma candidatura à Economia Local — só para quem tem sessão no backoffice. As imagens ficam em
 * uploads/comercio-candidaturas/ e não se abrem diretamente pelo site (includes/comercio_candidaturas.php).
 */
require_once "includes/auth.php";
require_once "../includes/db.php";
require_once "../includes/comercio_candidaturas.php";

$st = $pdo->prepare("SELECT imagem, nome_estabelecimento FROM comercio_candidaturas WHERE id = ?");
$st->execute([(int)($_GET['id'] ?? 0)]);
$c = $st->fetch(PDO::FETCH_ASSOC);
$nome = $c ? comercioCandImagemNome($c['imagem']) : '';
$caminho = COMERCIO_CAND_DIR . $nome;
if ($nome === '' || !is_file($caminho)) {
    http_response_code(404);
    die("Imagem não encontrada.");
}

$ext = pathinfo($nome, PATHINFO_EXTENSION);
$base = trim(preg_replace('/[^A-Za-z0-9._-]+/', '-', iconv('UTF-8', 'ASCII//TRANSLIT', (string)$c['nome_estabelecimento']) ?: 'imagem'), '-.') ?: 'imagem';

while (ob_get_level()) ob_end_clean();
header('Content-Type: ' . (COMERCIO_CAND_TIPOS[$ext] ?? 'application/octet-stream'));
header('Content-Length: ' . filesize($caminho));
header('Content-Disposition: ' . (!empty($_GET['descarregar']) ? 'attachment' : 'inline') . '; filename="' . $base . '.' . $ext . '"');
header('X-Content-Type-Options: nosniff');
header('Cache-Control: private, no-store');
readfile($caminho);
exit;
