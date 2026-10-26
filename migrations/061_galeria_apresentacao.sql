-- 061_galeria_apresentacao — escolher que álbuns entram na apresentação da galeria da página inicial.
-- CORE, idempotente. Mesmo nome que a 061 do modelo novo, reescrita (redesign 10/2026): só acrescenta o que falta.

SET @falta := (SELECT COUNT(*) = 0 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'galeria_albuns' AND COLUMN_NAME = 'na_apresentacao');
SET @sql := IF(@falta, 'ALTER TABLE galeria_albuns ADD COLUMN na_apresentacao TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
-- na primeira vez: os álbuns criados à mão entram na apresentação (o que o site já fazia)
SET @sql := IF(@falta, 'UPDATE galeria_albuns SET na_apresentacao = 1 WHERE origem = ''manual''', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
