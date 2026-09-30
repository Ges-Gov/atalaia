-- =====================================================================
-- 015_heraldica_elementos_imagem — Coluna de imagem nos elementos heráldicos
-- =====================================================================
-- CORE: idêntico em todos os sites. Idempotente (só adiciona onde falta).
--
-- heraldica_elementos servia só para cartões ícone+texto (ex.: "a cruz",
-- "a coroa mural"). Sites que sejam uniões de freguesias (várias antigas
-- freguesias, cada uma com o seu brasão próprio) precisam de mostrar uma
-- fotografia de cada brasão, não só um ícone genérico — daí esta coluna,
-- opcional (NULL = continua a usar o ícone, como até agora).
-- =====================================================================

SET @e := (SELECT COUNT(*) FROM information_schema.columns
           WHERE table_schema = DATABASE() AND table_name = 'heraldica_elementos' AND column_name = 'imagem');
SET @s := IF(@e = 0, 'ALTER TABLE heraldica_elementos ADD COLUMN imagem VARCHAR(255) NULL DEFAULT NULL AFTER icone', 'SELECT 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;
