-- 046_documentos_data_limite — prazo de afixação/consulta pública dos documentos (editais) e data em que foram arquivados.
-- CORE, idempotente. Mesmo nome que a 046 do modelo novo, reescrita (redesign 10/2026): só acrescenta o que falta.

SET @falta := (SELECT COUNT(*) = 0 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'documentos' AND COLUMN_NAME = 'data_limite');
SET @sql := IF(@falta, 'ALTER TABLE documentos ADD COLUMN data_limite DATE NULL DEFAULT NULL AFTER data_documento, ADD COLUMN arquivado_em DATETIME NULL DEFAULT NULL AFTER data_limite, ADD INDEX idx_documentos_limite (ativo, data_limite)', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
