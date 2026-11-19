-- Selo de Usabilidade e Acessibilidade, 19/11/2026: a declaração passa a listar cada avaliação manual (checklists
-- «10 aspetos críticos», «Conteúdo» e «Transação» da AMA) com a ligação à folha de evidências, como no modelo do
-- gerador da AMA (secção II.B). Uma linha por checklist, ligada à avaliação (acessibilidade_avaliacoes) a que pertence.
-- auto_relatorio_url: ligação ao relatório da avaliação automática (secção II.A); selo: bronze | prata | ouro, só
-- depois de atribuído pela AMA (secção IV).
CREATE TABLE IF NOT EXISTS acessibilidade_checklists (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    avaliacao_id INT UNSIGNED NOT NULL,
    checklist VARCHAR(20) NOT NULL,
    data_avaliacao DATE NOT NULL,
    paginas INT UNSIGNED NULL,
    satisfeitas INT UNSIGNED NOT NULL,
    aplicaveis INT UNSIGNED NOT NULL,
    evidencia_url VARCHAR(255) NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_avaliacao (avaliacao_id)
);

SET @existe := (SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = 'acessibilidade_avaliacoes' AND column_name = 'auto_relatorio_url');
SET @sql := IF(@existe = 0, 'ALTER TABLE acessibilidade_avaliacoes ADD COLUMN auto_relatorio_url VARCHAR(255) NULL AFTER auto_resumo', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @existe := (SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = 'acessibilidade_avaliacoes' AND column_name = 'selo');
SET @sql := IF(@existe = 0, 'ALTER TABLE acessibilidade_avaliacoes ADD COLUMN selo VARCHAR(10) NULL AFTER usabilidade', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
