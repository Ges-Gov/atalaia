-- =====================================================================
-- 032_eventos_coordenadas — latitude/longitude opcionais em eventos
-- =====================================================================
-- CORE: idêntico em todos os sites. Idempotente.
--
-- Alinha "eventos" com "pontos_interesse"/"associacoes"/"comercio_local",
-- que já têm estas duas colunas — para o evento poder aparecer no mapa da
-- freguesia (mapa.php) quando tiver um local físico associado. Continua
-- opcional: um evento sem coordenadas simplesmente não aparece no mapa,
-- mas continua normal em /eventos.php.
-- =====================================================================

SET @col_lat := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'eventos' AND column_name = 'latitude'
);
SET @sql := IF(@col_lat = 0,
    'ALTER TABLE eventos ADD COLUMN latitude VARCHAR(50) NULL AFTER imagem',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_lng := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'eventos' AND column_name = 'longitude'
);
SET @sql := IF(@col_lng = 0,
    'ALTER TABLE eventos ADD COLUMN longitude VARCHAR(50) NULL AFTER latitude',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

SET @col_local := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'eventos' AND column_name = 'local'
);
SET @sql := IF(@col_local = 0,
    'ALTER TABLE eventos ADD COLUMN local VARCHAR(255) NULL AFTER longitude',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
