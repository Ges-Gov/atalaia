<?php
/**
 * Contador de visitas (CORE: ficheiro idêntico em todos os sites) — 11/2026.
 *
 * Conta visitas às páginas públicas por dia e por página (ex.: 2026-11-09 · noticia.php · 37), para o relatório
 * mensal do site que a GesGov envia a cada junta e para o «Acompanhamento das Juntas» da Intranet
 * (api/noticias.php devolve os totais por mês).
 *
 * Sem cookies, sem IP, sem identificar ninguém: só um total por dia e por página — não precisa de aviso de
 * cookies. Ficam de fora robôs, pré-carregamentos do browser e pedidos que não sejam GET.
 *
 * Chamado no includes/seo.php (carregado só pelo cabeçalho público). Nunca deixa uma página falhar: qualquer
 * erro é ignorado. Se a tabela ainda não existir (migração 089 por correr), cria-a.
 */

function contarVisita(): void
{
    static $contada = false;
    if ($contada) return;
    $contada = true;

    if (PHP_SAPI === 'cli' || ($_SERVER['REQUEST_METHOD'] ?? 'GET') !== 'GET') return;

    $ua = (string)($_SERVER['HTTP_USER_AGENT'] ?? '');
    if ($ua === '' || preg_match('~bot|crawl|spider|slurp|preview|facebookexternalhit|whatsapp|telegram|curl|wget|python|java/|monitor|uptime|lighthouse|headless|gesgov-intranet~i', $ua)) return;
    if (!empty($_SERVER['HTTP_SEC_PURPOSE']) || !empty($_SERVER['HTTP_PURPOSE']) || !empty($_SERVER['HTTP_X_MOZ'])) return;

    global $pdo;
    if (!($pdo instanceof PDO)) return;

    $pagina = substr(basename((string)parse_url((string)($_SERVER['SCRIPT_NAME'] ?? ''), PHP_URL_PATH)) ?: 'index.php', 0, 120);
    $sql = 'INSERT INTO visitas_diarias (data, pagina, total) VALUES (CURDATE(), ?, 1) ON DUPLICATE KEY UPDATE total = total + 1';

    try {
        $pdo->prepare($sql)->execute([$pagina]);
    } catch (Throwable $e) {
        try {
            $pdo->query('CREATE TABLE IF NOT EXISTS visitas_diarias (
                data DATE NOT NULL,
                pagina VARCHAR(120) NOT NULL,
                total INT UNSIGNED NOT NULL DEFAULT 0,
                PRIMARY KEY (data, pagina)
            )');
            $pdo->prepare($sql)->execute([$pagina]);
        } catch (Throwable $e2) {
            // Sem contagem desta vez — a página continua.
        }
    }
}
