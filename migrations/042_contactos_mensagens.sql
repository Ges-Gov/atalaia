-- =====================================================================
-- 042_contactos_mensagens — Mensagens do formulário da página Contactos
-- =====================================================================
-- CORE: idêntico em todos os sites. Idempotente.
-- O formulário de contacto (contactos.php) guarda aqui cada mensagem (e tenta também enviar
-- por email para o endereço da Junta). Lidas no admin: Junta Virtual → Mensagens de Contacto.
-- =====================================================================
CREATE TABLE IF NOT EXISTS contactos_mensagens (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    nome        VARCHAR(150) NOT NULL,
    email       VARCHAR(190) NOT NULL,
    telefone    VARCHAR(40) DEFAULT NULL,
    assunto     VARCHAR(60) NOT NULL,
    mensagem    TEXT NOT NULL,
    lida        TINYINT(1) NOT NULL DEFAULT 0,
    email_enviado TINYINT(1) NOT NULL DEFAULT 0,
    ip_hash     CHAR(64) DEFAULT NULL,
    criado_em   TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (lida), INDEX (criado_em)
) ENGINE=InnoDB;
-- Mesmo nome que a 042 do modelo novo; sem DEFAULT CHARSET/COLLATE (redesign 10/2026): herda a da BD.
