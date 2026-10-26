-- 053_contactos_uteis_imagem — CORE: fotografia de capa dos cartões de contactos úteis.
-- Caminho relativo a assets/img (ex.: 'cartoes/contacto-3-1791.jpg' ou 'ponto_123.jpg').
-- Sem imagem, o cartão mostra uma capa com o tom da categoria (nunca uma imagem inventada).
-- Mesmo nome que a 053 do modelo novo, reescrita idempotente (redesign 10/2026): só acrescenta a coluna se faltar.
SET @existe := (SELECT COUNT(*) FROM information_schema.COLUMNS
                WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'contactos_uteis' AND COLUMN_NAME = 'imagem');
SET @sql := IF(@existe = 0, 'ALTER TABLE contactos_uteis ADD COLUMN imagem VARCHAR(255) NULL DEFAULT NULL AFTER icone', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
