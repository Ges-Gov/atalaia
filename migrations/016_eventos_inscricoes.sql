-- =====================================================================
-- 021_eventos_inscricoes — Inscrições em eventos (ex.: excursões)
-- =====================================================================
-- CORE: idêntico em todos os sites.
--
-- Permite ativar, por evento, um formulário público de inscrição (nome,
-- email, telefone, nº de pessoas, observações), com limite de vagas e
-- prazo opcionais. Pensado para excursões, mas serve qualquer evento com
-- inscrição prévia.
--
-- Idempotente: só cria/acrescenta o que faltar.
-- =====================================================================

SET @existe := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'eventos' AND column_name = 'inscricoes_ativas'
);
SET @sql := IF(@existe = 0,
    'ALTER TABLE eventos ADD COLUMN inscricoes_ativas TINYINT(1) NOT NULL DEFAULT 0, ADD COLUMN inscricoes_vagas INT UNSIGNED NULL DEFAULT NULL, ADD COLUMN inscricoes_ate DATETIME NULL DEFAULT NULL',
    'SELECT 1');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;

CREATE TABLE IF NOT EXISTS eventos_inscricoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    nome VARCHAR(190) NOT NULL,
    email VARCHAR(190) NOT NULL,
    telefone VARCHAR(40) NULL,
    num_pessoas SMALLINT UNSIGNED NOT NULL DEFAULT 1,
    observacoes TEXT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_eventos_inscricoes_evento (evento_id)
);
