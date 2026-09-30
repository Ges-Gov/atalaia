-- =====================================================================
-- 014_categorias — Categorias fixas para notícias e eventos
-- =====================================================================
-- CORE: idêntico em todos os sites. Idempotente (só adiciona onde falta).
--
-- Categoria fixa (não é campo de texto livre) para classificar notícias e
-- eventos. Serve, para já, só para etiquetar o conteúdo; mais tarde vai
-- permitir separar por tema para gerar mailing/newsletter e o relatório de
-- atividades do executivo. A lista de categorias vive no código
-- (includes/categorias.php), nunca na BD — é sempre validada contra essa
-- lista antes de gravar (nunca aceita o valor em bruto do POST).
-- =====================================================================

SET @existe := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'noticias' AND column_name = 'categoria'
);
SET @sql := IF(@existe = 0,
    'ALTER TABLE noticias ADD COLUMN categoria VARCHAR(60) NULL',
    'SELECT 1');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @existe := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'eventos' AND column_name = 'categoria'
);
SET @sql := IF(@existe = 0,
    'ALTER TABLE eventos ADD COLUMN categoria VARCHAR(60) NULL',
    'SELECT 1');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;
