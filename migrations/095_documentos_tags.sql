-- Documentos da Freguesia, 20/11/2026: palavras-chave, como já havia nos documentos da Assembleia (pedido: «podia haver
-- palavras-chave nos dois»). Ajudam a encontrar o documento na pesquisa do backoffice e do site.
SET @existe := (SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = 'documentos' AND column_name = 'tags');
SET @sql := IF(@existe = 0, 'ALTER TABLE documentos ADD COLUMN tags VARCHAR(255) NULL AFTER descricao', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
