-- =====================================================================
-- 041_redes_sociais — Mais redes sociais nas Configurações do site
-- =====================================================================
-- CORE: idêntico em todos os sites. Idempotente.
-- Além de facebook e instagram (já existentes), o admin passa a ter campos para YouTube,
-- X (Twitter), TikTok, LinkedIn e WhatsApp. Os ícones aparecem no menu e no rodapé só para
-- as redes preenchidas.
-- =====================================================================
SET @t := 'configuracoes_site';
SET @c := 'youtube';   SET @sql := IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = @t AND column_name = @c) = 0, 'ALTER TABLE configuracoes_site ADD COLUMN youtube VARCHAR(255) DEFAULT NULL', 'SELECT 1');   PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
SET @c := 'x_twitter'; SET @sql := IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = @t AND column_name = @c) = 0, 'ALTER TABLE configuracoes_site ADD COLUMN x_twitter VARCHAR(255) DEFAULT NULL', 'SELECT 1'); PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
SET @c := 'tiktok';    SET @sql := IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = @t AND column_name = @c) = 0, 'ALTER TABLE configuracoes_site ADD COLUMN tiktok VARCHAR(255) DEFAULT NULL', 'SELECT 1');    PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
SET @c := 'linkedin';  SET @sql := IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = @t AND column_name = @c) = 0, 'ALTER TABLE configuracoes_site ADD COLUMN linkedin VARCHAR(255) DEFAULT NULL', 'SELECT 1');  PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
SET @c := 'whatsapp';  SET @sql := IF((SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = @t AND column_name = @c) = 0, 'ALTER TABLE configuracoes_site ADD COLUMN whatsapp VARCHAR(255) DEFAULT NULL', 'SELECT 1');  PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
