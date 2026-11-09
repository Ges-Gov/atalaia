-- Contador de visitas (11/2026): total por dia e por página, sem cookies nem IP (includes/visitas.php).
-- Para o relatório mensal do site e o «Acompanhamento das Juntas» da Intranet GesGov.
CREATE TABLE IF NOT EXISTS visitas_diarias (
    data DATE NOT NULL,
    pagina VARCHAR(120) NOT NULL,
    total INT UNSIGNED NOT NULL DEFAULT 0,
    PRIMARY KEY (data, pagina)
);
