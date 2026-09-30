-- =====================================================================
-- 006_evento_foco — Ponto de foco da imagem do evento
-- =====================================================================
-- CORE: idêntico em todos os sites.
--
-- O jfgranho e o riomoinhos já tinham esta feature (colunas + seletor visual no
-- backoffice); os restantes 5 sites não. Isto é drift ao nível das COLUNAS — as
-- tabelas eram as mesmas, mas não as colunas.
--
-- O ponto de foco guarda, em percentagem, qual a zona da imagem que deve
-- manter-se sempre visível quando a foto é cortada em proporções diferentes
-- (cartão da listagem vs. topo do evento). 50/50 = centro, o comportamento antigo.
--
-- Idempotente: só adiciona as colunas onde faltam.
-- =====================================================================

SET @existe := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'eventos' AND column_name = 'imagem_foco_x'
);
SET @sql := IF(@existe = 0,
    'ALTER TABLE eventos ADD COLUMN imagem_foco_x TINYINT UNSIGNED NOT NULL DEFAULT 50, ADD COLUMN imagem_foco_y TINYINT UNSIGNED NOT NULL DEFAULT 50',
    'SELECT 1');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;
