-- =====================================================================
-- 039_pontos_destaque — Ponto de interesse em destaque na página inicial
-- =====================================================================
-- CORE: idêntico em todos os sites. Idempotente.
--
-- A página inicial mostrava os pontos pelo mais recente primeiro, e o 1.º ficava no quadro
-- grande. Passa a haver um campo "destaque": os pontos com destaque = 1 vêm primeiro (o
-- primeiro deles ocupa o quadro grande); os restantes seguem pelo mais recente.
-- Qual é o ponto em destaque é MARCA de cada site: ver 039_pontos_destaque_seed.sql.
-- =====================================================================

SET @existe := (SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = 'pontos_interesse' AND column_name = 'destaque');
SET @sql := IF(@existe = 0, 'ALTER TABLE pontos_interesse ADD COLUMN destaque TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;
