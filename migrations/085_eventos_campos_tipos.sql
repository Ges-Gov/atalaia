-- =====================================================================
-- 085_eventos_campos_tipos — Mais tipos nos campos extra das inscrições
-- =====================================================================
-- CORE: idêntico em todos os sites. 02/11/2026.
--
-- Os campos extra dos eventos só podiam ser texto ou checkbox. Passam a ter
-- os mesmos tipos dos eventos do site da GesGov na intranet: texto curto,
-- texto longo, número, data, escolha numa lista de opções e foto/ficheiro
-- (além do sim/não). A lista de tipos vive em includes/eventos_campos.php;
-- a coluna passa a VARCHAR para um tipo novo não obrigar a mexer no ENUM.
-- As opções de uma «escolha» ficam em JSON na coluna nova `opcoes`.
--
-- Idempotente: o MODIFY pode repetir-se; a coluna só se cria se faltar.
-- =====================================================================

ALTER TABLE eventos_campos_extra MODIFY tipo VARCHAR(20) NOT NULL DEFAULT 'texto';

SET @existe := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'eventos_campos_extra' AND column_name = 'opcoes'
);
SET @sql := IF(@existe = 0,
    'ALTER TABLE eventos_campos_extra ADD COLUMN opcoes TEXT NULL DEFAULT NULL AFTER tipo',
    'SELECT 1');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;
