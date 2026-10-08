-- =====================================================================
-- 038_slides_ordem — Ordem de apresentação dos slides da página inicial
-- =====================================================================
-- CORE: idêntico em todos os sites. Idempotente.
--
-- Os slides apareciam pela ordem em que foram criados. Passam a ter um número de ordem
-- (1 = primeiro), escolhido no backoffice ao adicionar/editar o slide.
-- Os slides que ainda não têm ordem ficam numerados pela ordem atual (por id), a seguir aos que já têm.
-- =====================================================================

SET @existe := (SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = 'slides_homepage' AND column_name = 'ordem');
SET @sql := IF(@existe = 0, 'ALTER TABLE slides_homepage ADD COLUMN ordem INT DEFAULT NULL', 'SELECT 1');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @n := (SELECT COALESCE(MAX(ordem), 0) FROM slides_homepage);
UPDATE slides_homepage SET ordem = (@n := @n + 1) WHERE ordem IS NULL ORDER BY id;
