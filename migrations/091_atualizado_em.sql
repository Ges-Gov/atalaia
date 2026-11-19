-- «Página atualizada em …» (checklist «Conteúdo» do Selo, requisito 1.3), 19/11/2026: as tabelas de conteúdo que não
-- guardavam a data da última alteração passam a guardá-la. O MySQL atualiza a coluna sozinho em cada alteração e põe a
-- data de criação nas linhas novas. As linhas que já existiam ficam SEM data (NULL): não se sabe quando foram mudadas
-- e não se inventa — a página só mostra uma data quando a conhece.

SET @existe := (SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = 'executivo_membros' AND column_name = 'atualizado_em');
SET @sql := IF(@existe = 0, 'ALTER TABLE executivo_membros ADD COLUMN atualizado_em TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
SET @sql := IF(@existe = 0, 'UPDATE executivo_membros SET atualizado_em = NULL', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @existe := (SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = 'faqs' AND column_name = 'atualizado_em');
SET @sql := IF(@existe = 0, 'ALTER TABLE faqs ADD COLUMN atualizado_em TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
SET @sql := IF(@existe = 0, 'UPDATE faqs SET atualizado_em = NULL', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @existe := (SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = 'pontos_interesse' AND column_name = 'atualizado_em');
SET @sql := IF(@existe = 0, 'ALTER TABLE pontos_interesse ADD COLUMN atualizado_em TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
SET @sql := IF(@existe = 0, 'UPDATE pontos_interesse SET atualizado_em = NULL', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @existe := (SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = 'associacoes' AND column_name = 'atualizado_em');
SET @sql := IF(@existe = 0, 'ALTER TABLE associacoes ADD COLUMN atualizado_em TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
SET @sql := IF(@existe = 0, 'UPDATE associacoes SET atualizado_em = NULL', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @existe := (SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = 'comercio_local' AND column_name = 'atualizado_em');
SET @sql := IF(@existe = 0, 'ALTER TABLE comercio_local ADD COLUMN atualizado_em TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
SET @sql := IF(@existe = 0, 'UPDATE comercio_local SET atualizado_em = NULL', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
