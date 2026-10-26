<?php
/*
 * Plataforma de sites de Juntas de Freguesia — © 2026 GesGov. Todos os direitos reservados.
 * Licença GG-ATALAIA-005E52FF — uso exclusivo de: Freguesias de Atalaia e Alto Estanqueiro-Jardia, concelho do Montijo.
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
        ('Junta de Freguesia', '', '', '', '#242A30', '#C8A02E', '© " . date('Y') . " Junta de Freguesia')
    ");

    $stmt = $pdo->query("SELECT * FROM configuracoes_site LIMIT 1");
    $config = $stmt->fetch(PDO::FETCH_ASSOC);
}

function siteConfig($campo, $default = '') {
    global $config;
    return !empty($config[$campo]) ? $config[$campo] : $default;
}

/** Prefixo dos códigos de pedidos/denúncias (ex.: SOU-2026-…, CIB-2026-…) — do modelo novo (redesign 10/2026).
 *  tema_config «prefixo_pedidos» se existir; senão as 3 primeiras letras da freguesia, sem acentos. */
function prefixoPedidos(): string {
    static $p = null;
    if ($p !== null) return $p;
    global $pdo;
    try {
        $s = $pdo->prepare("SELECT valor FROM tema_config WHERE chave = 'prefixo_pedidos'");
        $s->execute();
        $v = strtoupper(trim((string)$s->fetchColumn()));
        if (preg_match('/^[A-Z]{2,6}$/', $v)) return $p = $v;
    } catch (Throwable $e) {}
    $base = iconv('UTF-8', 'ASCII//TRANSLIT', nomeFreguesia()) ?: nomeFreguesia();
    $letras = preg_replace('/[^A-Za-z]/', '', $base);
    return $p = strtoupper(substr($letras !== '' ? $letras : 'JF', 0, 3));
}
/** Nome curto da freguesia, sem «Junta de Freguesia de»/«União das Freguesias de» (redesign, do modelo novo) */
function nomeFreguesia(): string {
    $n = trim((string)siteConfig('nome_site', ''));
    $curto = preg_replace('/^(junta\s+de\s+)?(uni[aã]o\s+das\s+)?freguesias?\s+(de|do|da|dos|das)\s+/iu', '', $n);
    return $curto !== '' ? $curto : 'Freguesia';
}
function nomeConcelho(): string {
    return trim((string)siteConfig('municipio', ''));
}
/** A freguesia tem o nome do concelho (é a sede: Sousel, Sines, Avis…)? Então o nome não se repete. */
function freguesiaSedeConcelho(): bool {
    $n = fn($t) => mb_strtolower(trim((string)(iconv('UTF-8', 'ASCII//TRANSLIT', $t) ?: $t)));
    return nomeConcelho() !== '' && $n(nomeFreguesia()) === $n(nomeConcelho());
}
/** «Cano · Sousel», mas só «Sousel» (sede do concelho) — nunca «Sousel · Sousel». */
function localFreguesia(string $sep = ' · '): string {
    return (nomeConcelho() === '' || freguesiaSedeConcelho()) ? nomeFreguesia() : nomeFreguesia() . $sep . nomeConcelho();
}
/** Preposição da freguesia («de Pombalinho», «do Torrão», «da Comporta»): a mesma que o nome oficial da Junta usa
 *  («Junta de Freguesia do Torrão»); tema_config «freguesia_preposicao» sobrepõe-se. */
function preposicaoFreguesia(): string {
    $v = strtolower(trim((string)temaConfigSeguro('freguesia_preposicao')));
    if (in_array($v, ['de', 'do', 'da', 'dos', 'das'], true)) return $v;
    if (preg_match('/freguesias?\s+(de|do|da|dos|das)\s+/iu', (string)siteConfig('nome_site', ''), $m)) return mb_strtolower($m[1]);
    return 'de';
}
/** Preposição do concelho («de Sousel», «da Golegã», «do Crato»): tema_config «concelho_preposicao»; por omissão «de». */
function preposicaoConcelho(): string {
    $v = strtolower(trim((string)temaConfigSeguro('concelho_preposicao')));
    return in_array($v, ['de', 'do', 'da', 'dos', 'das'], true) ? $v : 'de';
}
function deFreguesia(): string { return preposicaoFreguesia() . ' ' . nomeFreguesia(); }
function deConcelho(): string { $c = nomeConcelho() !== '' ? nomeConcelho() : nomeFreguesia(); return preposicaoConcelho() . ' ' . $c; }
/** tema_config sem depender do tema.php (o config.php é carregado antes dele em algumas páginas) */
function temaConfigSeguro(string $chave): string {
    static $cache = [];
    if (array_key_exists($chave, $cache)) return $cache[$chave];
    global $pdo;
    try { $s = $pdo->prepare("SELECT valor FROM tema_config WHERE chave = ?"); $s->execute([$chave]); return $cache[$chave] = (string)$s->fetchColumn(); }
    catch (Throwable $e) { return $cache[$chave] = ''; }
}
// Textos do site (te()/tx()): os originais estão em includes/textos-registo.php; os alterados na tabela textos_site
require_once __DIR__ . "/textos.php";