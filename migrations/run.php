<?php
/**
 * Runner de migrações (CORE: ficheiro idêntico em todos os sites).
 *
 * Aplica, por ordem, os ficheiros migrations/NNN_*.sql que ainda não correram
 * neste site, e regista-os na tabela `migracoes`. É idempotente: correr duas
 * vezes não repete nada.
 *
 * Isto resolve o pedido "criar forma de atualizar a estrutura de todos os sites
 * transversalmente": basta acrescentar um ficheiro .sql novo à pasta migrations/
 * de cada site e correr este runner em cada um.
 *
 * Uso:
 *   Linha de comandos (recomendado):   php migrations/run.php
 *   Ver o que falta, sem aplicar:      php migrations/run.php --dry-run
 *
 * Nota: os ficheiros *_seed.sql contêm a MARCA de cada site (cores, etc.) e são
 * diferentes de site para site — mas correm pelo mesmo mecanismo.
 */

$emCli = (php_sapi_name() === 'cli');
if (!$emCli) {
    // Pela web só corre para quem tem sessão de admin, para não expor a BD.
    session_start();
    if (empty($_SESSION['admin'])) {
        http_response_code(403);
        exit("Acesso negado. Corre este runner pela linha de comandos: php migrations/run.php\n");
    }
    header('Content-Type: text/plain; charset=utf-8');
}

require_once __DIR__ . '/../includes/db.php';

$dryRun = in_array('--dry-run', $argv ?? [], true);
$nl     = "\n";

// Tabela de controlo: que migrações já correram neste site.
$pdo->exec("
    CREATE TABLE IF NOT EXISTS migracoes (
        id          INT AUTO_INCREMENT PRIMARY KEY,
        ficheiro    VARCHAR(190) NOT NULL UNIQUE,
        aplicada_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
");

$jaAplicadas = $pdo->query("SELECT ficheiro FROM migracoes")->fetchAll(PDO::FETCH_COLUMN);

$ficheiros = glob(__DIR__ . '/*.sql');
sort($ficheiros, SORT_NATURAL);

$pendentes = [];
foreach ($ficheiros as $caminho) {
    $nome = basename($caminho);
    if (!in_array($nome, $jaAplicadas, true)) {
        $pendentes[] = $caminho;
    }
}

if (empty($pendentes)) {
    echo "Nada a fazer: todas as migrações já foram aplicadas." . $nl;
    exit(0);
}

echo "Migrações pendentes (" . count($pendentes) . "):" . $nl;
foreach ($pendentes as $c) {
    echo "  - " . basename($c) . $nl;
}

if ($dryRun) {
    echo $nl . "--dry-run: nada foi aplicado." . $nl;
    exit(0);
}

echo $nl;
$registar = $pdo->prepare("INSERT INTO migracoes (ficheiro) VALUES (?)");

foreach ($pendentes as $caminho) {
    $nome = basename($caminho);
    $sql  = file_get_contents($caminho);

    if (trim($sql) === '') {
        echo "IGNORADA (vazia): $nome" . $nl;
        continue;
    }

    try {
        // Cada ficheiro corre como um todo; o PDO/MySQL aceita múltiplas
        // instruções separadas por ";" via exec().
        $pdo->exec($sql);
        $registar->execute([$nome]);
        echo "OK      $nome" . $nl;
    } catch (Exception $e) {
        echo "FALHOU  $nome" . $nl;
        echo "        " . $e->getMessage() . $nl;
        echo $nl . "Interrompido. Corrige o erro e volta a correr." . $nl;
        exit(1);
    }
}

echo $nl . "Concluído." . $nl;
