-- =====================================================================
-- 010_alinhar_colunas — Alinhar as COLUNAS que faltavam em alguns sites
-- =====================================================================
-- CORE: idêntico em todos os sites. Idempotente (só adiciona onde falta).
--
-- A comparação coluna-a-coluna entre os 7 sites revelou drift que não se via ao
-- olhar só para as tabelas (essas eram todas iguais):
--
--   * noticias.imagem_foco_x / _y  — só o jfgranho as tinha. O ponto de foco da
--       imagem existia nos eventos (migração 006) mas não nas notícias dos outros 6.
--
--   * eventos.data_fim  — faltava no riomoinhos-test. GRAVE: o código de eventos
--       (uniformizado a partir do jfgranho) insere esta coluna, por isso GRAVAR UM
--       EVENTO nesse site rebentava com erro de SQL.
--
--   * associacoes.* e comercio_local.*  — faltavam 6 colunas em cada, no
--       riomoinhos-test: website, redes sociais e coordenadas para o mapa.
--
-- Sem isto, os sites ficariam com o mesmo CÓDIGO mas bases de dados diferentes —
-- a maneira mais rápida de partir tudo no deploy seguinte.
--
-- NOTA: não se usa CREATE PROCEDURE aqui de propósito. O corpo de um procedimento
-- tem ';' lá dentro, e o runner (migrations/run.php) executa o ficheiro inteiro de
-- uma vez — o ';' interno cortaria a instrução a meio. O padrão abaixo
-- (SET @sql + PREPARE/EXECUTE) é seguro e é o mesmo da migração 006.
-- =====================================================================

-- ---------- noticias.imagem_foco_x ----------
SET @e := (SELECT COUNT(*) FROM information_schema.columns
           WHERE table_schema = DATABASE() AND table_name = 'noticias' AND column_name = 'imagem_foco_x');
SET @s := IF(@e = 0, 'ALTER TABLE noticias ADD COLUMN imagem_foco_x TINYINT UNSIGNED NOT NULL DEFAULT 50', 'SELECT 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- ---------- noticias.imagem_foco_y ----------
SET @e := (SELECT COUNT(*) FROM information_schema.columns
           WHERE table_schema = DATABASE() AND table_name = 'noticias' AND column_name = 'imagem_foco_y');
SET @s := IF(@e = 0, 'ALTER TABLE noticias ADD COLUMN imagem_foco_y TINYINT UNSIGNED NOT NULL DEFAULT 50', 'SELECT 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- ---------- eventos.data_fim ----------
SET @e := (SELECT COUNT(*) FROM information_schema.columns
           WHERE table_schema = DATABASE() AND table_name = 'eventos' AND column_name = 'data_fim');
SET @s := IF(@e = 0, 'ALTER TABLE eventos ADD COLUMN data_fim DATETIME NULL DEFAULT NULL', 'SELECT 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- ---------- associacoes.website ----------
SET @e := (SELECT COUNT(*) FROM information_schema.columns
           WHERE table_schema = DATABASE() AND table_name = 'associacoes' AND column_name = 'website');
SET @s := IF(@e = 0, 'ALTER TABLE associacoes ADD COLUMN website VARCHAR(255) NULL DEFAULT NULL', 'SELECT 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- ---------- associacoes.facebook ----------
SET @e := (SELECT COUNT(*) FROM information_schema.columns
           WHERE table_schema = DATABASE() AND table_name = 'associacoes' AND column_name = 'facebook');
SET @s := IF(@e = 0, 'ALTER TABLE associacoes ADD COLUMN facebook VARCHAR(255) NULL DEFAULT NULL', 'SELECT 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- ---------- associacoes.instagram ----------
SET @e := (SELECT COUNT(*) FROM information_schema.columns
           WHERE table_schema = DATABASE() AND table_name = 'associacoes' AND column_name = 'instagram');
SET @s := IF(@e = 0, 'ALTER TABLE associacoes ADD COLUMN instagram VARCHAR(255) NULL DEFAULT NULL', 'SELECT 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- ---------- associacoes.latitude ----------
SET @e := (SELECT COUNT(*) FROM information_schema.columns
           WHERE table_schema = DATABASE() AND table_name = 'associacoes' AND column_name = 'latitude');
SET @s := IF(@e = 0, 'ALTER TABLE associacoes ADD COLUMN latitude VARCHAR(50) NULL DEFAULT NULL', 'SELECT 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- ---------- associacoes.longitude ----------
SET @e := (SELECT COUNT(*) FROM information_schema.columns
           WHERE table_schema = DATABASE() AND table_name = 'associacoes' AND column_name = 'longitude');
SET @s := IF(@e = 0, 'ALTER TABLE associacoes ADD COLUMN longitude VARCHAR(50) NULL DEFAULT NULL', 'SELECT 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- ---------- associacoes.outros_contactos ----------
SET @e := (SELECT COUNT(*) FROM information_schema.columns
           WHERE table_schema = DATABASE() AND table_name = 'associacoes' AND column_name = 'outros_contactos');
SET @s := IF(@e = 0, 'ALTER TABLE associacoes ADD COLUMN outros_contactos TEXT NULL DEFAULT NULL', 'SELECT 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- ---------- comercio_local.website ----------
SET @e := (SELECT COUNT(*) FROM information_schema.columns
           WHERE table_schema = DATABASE() AND table_name = 'comercio_local' AND column_name = 'website');
SET @s := IF(@e = 0, 'ALTER TABLE comercio_local ADD COLUMN website VARCHAR(255) NULL DEFAULT NULL', 'SELECT 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- ---------- comercio_local.facebook ----------
SET @e := (SELECT COUNT(*) FROM information_schema.columns
           WHERE table_schema = DATABASE() AND table_name = 'comercio_local' AND column_name = 'facebook');
SET @s := IF(@e = 0, 'ALTER TABLE comercio_local ADD COLUMN facebook VARCHAR(255) NULL DEFAULT NULL', 'SELECT 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- ---------- comercio_local.instagram ----------
SET @e := (SELECT COUNT(*) FROM information_schema.columns
           WHERE table_schema = DATABASE() AND table_name = 'comercio_local' AND column_name = 'instagram');
SET @s := IF(@e = 0, 'ALTER TABLE comercio_local ADD COLUMN instagram VARCHAR(255) NULL DEFAULT NULL', 'SELECT 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- ---------- comercio_local.latitude ----------
SET @e := (SELECT COUNT(*) FROM information_schema.columns
           WHERE table_schema = DATABASE() AND table_name = 'comercio_local' AND column_name = 'latitude');
SET @s := IF(@e = 0, 'ALTER TABLE comercio_local ADD COLUMN latitude VARCHAR(50) NULL DEFAULT NULL', 'SELECT 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- ---------- comercio_local.longitude ----------
SET @e := (SELECT COUNT(*) FROM information_schema.columns
           WHERE table_schema = DATABASE() AND table_name = 'comercio_local' AND column_name = 'longitude');
SET @s := IF(@e = 0, 'ALTER TABLE comercio_local ADD COLUMN longitude VARCHAR(50) NULL DEFAULT NULL', 'SELECT 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;

-- ---------- comercio_local.outros_contactos ----------
SET @e := (SELECT COUNT(*) FROM information_schema.columns
           WHERE table_schema = DATABASE() AND table_name = 'comercio_local' AND column_name = 'outros_contactos');
SET @s := IF(@e = 0, 'ALTER TABLE comercio_local ADD COLUMN outros_contactos TEXT NULL DEFAULT NULL', 'SELECT 1');
PREPARE st FROM @s;
EXECUTE st;
DEALLOCATE PREPARE st;
