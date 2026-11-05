-- =====================================================================
-- 087_respostas_backoffice — Responder às sugestões e mensagens de contacto
-- a partir do backoffice (05/11/2026)
-- =====================================================================
-- O botão «Responder» só abria o programa de email do computador (mailto:),
-- que muitas vezes não está configurado. Passa a responder-se no próprio
-- backoffice: o site envia o email e guarda aqui a resposta, quando e quem.
--
-- Idempotente: cada coluna só se cria se faltar.
-- =====================================================================

SET @existe := (SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = 'sugestoes' AND column_name = 'resposta');
SET @sql := IF(@existe = 0,
    'ALTER TABLE sugestoes ADD COLUMN resposta TEXT NULL DEFAULT NULL, ADD COLUMN respondida_em DATETIME NULL DEFAULT NULL, ADD COLUMN respondida_por VARCHAR(100) NULL DEFAULT NULL',
    'SELECT 1');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @existe := (SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = 'contactos_mensagens' AND column_name = 'resposta');
SET @sql := IF(@existe = 0,
    'ALTER TABLE contactos_mensagens ADD COLUMN resposta TEXT NULL DEFAULT NULL, ADD COLUMN respondida_em DATETIME NULL DEFAULT NULL, ADD COLUMN respondida_por VARCHAR(100) NULL DEFAULT NULL',
    'SELECT 1');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;
