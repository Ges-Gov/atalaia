-- 062_separadores_foco — ponto de foco (x, y em %) da fotografia de fundo do topo de cada página.
-- CORE, idempotente. Mesmo nome que a 062 do modelo novo, reescrita (redesign 10/2026): só acrescenta o que falta.

SET @falta := (SELECT COUNT(*) = 0 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'separadores_fundo' AND COLUMN_NAME = 'foco_x');
SET @sql := IF(@falta, 'ALTER TABLE separadores_fundo ADD COLUMN foco_x TINYINT UNSIGNED NOT NULL DEFAULT 50, ADD COLUMN foco_y TINYINT UNSIGNED NOT NULL DEFAULT 50', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
