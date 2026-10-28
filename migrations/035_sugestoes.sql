-- =====================================================================
-- 035_sugestoes — Formulário de sugestões e fundo de separador nas páginas
-- =====================================================================
-- CORE: idêntico em todos os sites. Idempotente (só cria/acrescenta).
--
-- sugestoes: mensagens do formulário público /sugestoes.php, lidas no backoffice
--   (Junta Virtual › Sugestões). lida = 0 conta no número ao lado do menu.
-- paginas.fundo: imagem de fundo do separador de cada página de conteúdo
--   (ficheiro em uploads/separadores/, como os outros separadores).
-- =====================================================================

CREATE TABLE IF NOT EXISTS sugestoes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(150) NOT NULL,
    email      VARCHAR(190) NOT NULL,
    assunto    VARCHAR(200) NOT NULL,
    mensagem   TEXT NOT NULL,
    lida       TINYINT(1) NOT NULL DEFAULT 0,
    ip         VARCHAR(45) DEFAULT NULL,
    criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_lida (lida)
) ENGINE=InnoDB;

SET @existe := (SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = 'paginas' AND column_name = 'fundo');
SET @sql := IF(@existe = 0, 'ALTER TABLE paginas ADD COLUMN fundo VARCHAR(255) DEFAULT NULL AFTER imagem', 'SELECT 1');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;
