-- =====================================================================
-- 037_associacoes_categoria — Categoria das associações (Cultura / Desporto / Comunidade)
-- =====================================================================
-- CORE: idêntico em todos os sites. Idempotente.
--
-- A página pública mostrava as etiquetas Cultura / Desporto / Comunidade, mas eram só decorativas:
-- não havia onde guardar a categoria nem onde a escolher no backoffice. Esta coluna passa a existir,
-- as etiquetas filtram a lista e o formulário da associação tem a escolha.
--
-- Sugestão inicial pelo nome, SÓ para as que ainda não têm categoria (a Junta corrige no backoffice):
-- primeiro Cultura, depois Desporto; o resto fica Comunidade.
-- =====================================================================

SET @existe := (SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = 'associacoes' AND column_name = 'categoria');
SET @sql := IF(@existe = 0, 'ALTER TABLE associacoes ADD COLUMN categoria VARCHAR(60) DEFAULT NULL', 'SELECT 1');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;

UPDATE associacoes SET categoria = 'Cultura'
WHERE (categoria IS NULL OR categoria = '')
  AND LOWER(nome) REGEXP 'cultur|recreativ|banda|filarm|músic|music|coral|coro|teatro|rancho|folclor|danç|danc|arte|tuna|cante|etnogr|patrimón|biblioteca|carnaval|fado|gaiteir|gigant|artíst|artist|sociedade musical|orquestr';

UPDATE associacoes SET categoria = 'Desporto'
WHERE (categoria IS NULL OR categoria = '')
  AND LOWER(nome) REGEXP 'desport|futebol|futsal|atlét|atlet|ciclis|btt|nataç|karat|judo|ténis|tenis|xadrez|caçador|caça|pesca|columb|motoclube|motard|radiomodel|ginást|ginas|surf|canoag|vela|trail|petanca|hóquei|hoquei|basquet|andebol|voleib|equestre|hipic|tiro|aventura|sport|clube de pesca|ginásio|náutic|nautic|skate|benfica|sporting|futebol clube|desportiv';

UPDATE associacoes SET categoria = 'Comunidade'
WHERE categoria IS NULL OR categoria = '';
