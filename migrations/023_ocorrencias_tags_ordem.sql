-- =====================================================================
-- 023_ocorrencias_tags_ordem — coluna "ordem" em falta em ocorrencias_tags
-- =====================================================================
-- CORE: idêntico em todos os sites. Idempotente.
--
-- ocorrencias_tags (migração 015) ficou sem a coluna "ordem" que todas as
-- outras listas geridas de Ocorrências (categorias, assuntos, prioridades,
-- estados, ações, entidades externas) já têm — o ecrã de configuração
-- (admin/ocorrencias_config.php) ordena sempre por "ordem, designacao" e
-- rebentava ao abrir a tab de Tags.
-- =====================================================================

SET @existe := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'ocorrencias_tags' AND column_name = 'ordem'
);
SET @sql := IF(@existe = 0,
    'ALTER TABLE ocorrencias_tags ADD COLUMN ordem INT NOT NULL DEFAULT 0 AFTER designacao',
    'SELECT 1');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;
