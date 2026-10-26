-- 057_slides_video — slides do topo com vídeo do YouTube (tipo, endereço, ajuste e formato).
-- CORE, idempotente. Mesmo nome que a 057 do modelo novo, reescrita (redesign 10/2026): só acrescenta o que falta.

-- O slide de vídeo de cada Junta é conteúdo: carregador ou backoffice (o INSERT do Sousel do modelo novo não entrou).
SET @falta := (SELECT COUNT(*) = 0 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'slides_homepage' AND COLUMN_NAME = 'tipo');
SET @sql := IF(@falta, 'ALTER TABLE slides_homepage ADD COLUMN tipo VARCHAR(10) NOT NULL DEFAULT ''imagem'', ADD COLUMN video_url VARCHAR(255) NULL, ADD COLUMN video_ajuste VARCHAR(10) NOT NULL DEFAULT ''cobrir'', ADD COLUMN video_formato DECIMAL(5,4) NOT NULL DEFAULT 1.7778', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
