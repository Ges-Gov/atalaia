-- =====================================================================
-- 071_noticias_destaque — Notícia em destaque na página inicial
-- =====================================================================
-- CORE: idêntico em todos os sites. Idempotente.
--
-- O cartão grande da grelha de notícias da página inicial mostrava sempre a mais recente.
-- Passa a haver um campo "destaque": o admin escolhe uma notícia (só uma de cada vez, em
-- Notícias → Editar → «Destaque na página inicial»). Sem nenhuma marcada, volta a ser a mais recente.
--
-- Mesmo nome de ficheiro que a 071 do modelo novo, mas reescrita (20/10/2026): a original fazia
-- um ALTER TABLE simples (falha se correr duas vezes) e marcava uma notícia do Sousel — conteúdo,
-- que não pode ir numa migração CORE. Qual é a notícia em destaque escolhe-se no backoffice.
-- =====================================================================

SET @existe := (SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = 'noticias' AND column_name = 'destaque');
SET @sql := IF(@existe = 0, 'ALTER TABLE noticias ADD COLUMN destaque TINYINT(1) NOT NULL DEFAULT 0', 'SELECT 1');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;
