-- 068_mostrar_transparencia — interruptor da secção «Contas à vista» na página inicial (desligada por omissão).
-- CORE, idempotente. Mesmo nome que a 068 do modelo novo, reescrita (redesign 10/2026): só acrescenta o que falta.

SET @falta := (SELECT COUNT(*) = 0 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'homepage_config' AND COLUMN_NAME = 'mostrar_transparencia');
SET @sql := IF(@falta, 'ALTER TABLE homepage_config ADD COLUMN mostrar_transparencia TINYINT(1) NOT NULL DEFAULT 0 AFTER mostrar_galeria', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
