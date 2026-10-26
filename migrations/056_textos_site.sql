-- 056_textos_site — CORE: textos do site editáveis no backoffice (12/10/2026). Idempotente.
-- Só se guardam os textos ALTERADOS; os originais estão no registo includes/textos-registo.php.
-- Apagar uma linha = voltar ao texto original.
-- Mesmo nome que a 056 do modelo novo, sem DEFAULT CHARSET/COLLATE (20/10/2026): a tabela herda a
-- colação da BD, para não misturar utf8mb4_unicode_ci com utf8mb4_general_ci (armadilha do CLAUDE.md).
CREATE TABLE IF NOT EXISTS textos_site (
    chave          VARCHAR(150) NOT NULL PRIMARY KEY,
    valor          MEDIUMTEXT NOT NULL,
    atualizado_em  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    atualizado_por INT NULL
) ENGINE=InnoDB;
