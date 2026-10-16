<?php
/*
 * Plataforma de sites de Juntas de Freguesia — © 2026 GesGov. Todos os direitos reservados.
 * Licença GG-ATALAIA-005E52FF — uso exclusivo de: União das Freguesias de Atalaia e Alto Estanqueiro-Jardia, concelho do Montijo.
 * Proibida a cópia, modificação, redistribuição ou venda sem autorização escrita da GesGov.
 */
if (!headers_sent()) {
    header('X-Powered-By: GesGov');
}
require_once __DIR__ . "/db.php";
require_once __DIR__ . "/separadores.php";

$stmt = $pdo->query("SELECT * FROM configuracoes_site LIMIT 1");
$config = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$config) {
    $pdo->query("
        INSERT INTO configuracoes_site 
        (nome_site, email, telefone, morada, cor_principal, cor_secundaria, footer) 
        VALUES 
        ('Junta de Freguesia', '', '', '', '#242A30', '#D4AA00', '© " . date('Y') . " Junta de Freguesia')
    ");

    $stmt = $pdo->query("SELECT * FROM configuracoes_site LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
}

function siteConfig($campo, $default = '') {
    global $config;
    return !empty($config[$campo]) ? $config[$campo] : $default;
}