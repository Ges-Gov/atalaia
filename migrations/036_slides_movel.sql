-- =====================================================================
-- 036_slides_movel — Foto própria do slide para o telemóvel
-- =====================================================================
-- CORE: idêntico em todos os sites. Idempotente (só acrescenta colunas).
--
-- Num telemóvel ao alto, uma foto larga (ex.: faixa panorâmica 1920×550) mostra só uma
-- fatia do centro. Estas colunas, opcionais, deixam cada slide ter:
--   imagem_movel  — outra foto para ecrãs até 850px (ficheiro em assets/img/)
--   posicao_movel — ponto de foco no telemóvel (CSS background-position, ex.: "30% 50%")
-- Sem valores, o slide fica exatamente como antes.
-- =====================================================================

SET @existe := (SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = 'slides_homepage' AND column_name = 'imagem_movel');
SET @sql := IF(@existe = 0, 'ALTER TABLE slides_homepage ADD COLUMN imagem_movel VARCHAR(255) DEFAULT NULL', 'SELECT 1');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @existe := (SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = 'slides_homepage' AND column_name = 'posicao_movel');
SET @sql := IF(@existe = 0, 'ALTER TABLE slides_homepage ADD COLUMN posicao_movel VARCHAR(20) DEFAULT NULL', 'SELECT 1');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;
