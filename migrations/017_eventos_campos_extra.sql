-- =====================================================================
-- 022_eventos_campos_extra — Campos extra nas inscrições de eventos
-- =====================================================================
-- CORE: idêntico em todos os sites.
--
-- Alguns eventos (ex.: excursões) precisam de perguntar mais do que o
-- nome/email/telefone/nº de pessoas fixos — ex.: "precisa de almoço?",
-- "toma o autocarro em que paragem?". Isto permite à Junta definir, por
-- evento, campos de texto ou checkboxes extra no formulário de inscrição.
-- As respostas ficam guardadas em JSON na própria inscrição (chave =
-- id do campo), para não precisar de uma tabela EAV separada.
--
-- Idempotente: só cria/acrescenta o que faltar.
-- =====================================================================

CREATE TABLE IF NOT EXISTS eventos_campos_extra (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    evento_id INT UNSIGNED NOT NULL,
    label VARCHAR(190) NOT NULL,
    tipo ENUM('texto','checkbox') NOT NULL DEFAULT 'texto',
    obrigatorio TINYINT(1) NOT NULL DEFAULT 0,
    ordem INT NOT NULL DEFAULT 0,
    INDEX idx_eventos_campos_extra_evento (evento_id)
);

SET @existe := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'eventos_inscricoes' AND column_name = 'campos_extra'
);
SET @sql := IF(@existe = 0,
    'ALTER TABLE eventos_inscricoes ADD COLUMN campos_extra TEXT NULL DEFAULT NULL',
    'SELECT 1');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;
