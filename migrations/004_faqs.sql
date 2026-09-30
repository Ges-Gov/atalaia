-- =====================================================================
-- 004_faqs — Perguntas Frequentes
-- =====================================================================
-- CORE: idêntico em todos os sites. Idempotente (IF NOT EXISTS): nos sites
-- que já têm a tabela não faz nada; cria-a onde falta (era o caso do
-- riomoinhos-test, que nem tinha a página faq.php).
--
-- A página pública é faq.php e o CRUD do backoffice é admin/faqs.php.
-- =====================================================================

CREATE TABLE IF NOT EXISTS faqs (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    pergunta  VARCHAR(255) NOT NULL,
    resposta  TEXT NOT NULL,
    ordem     INT DEFAULT 0,
    ativo     TINYINT(1) DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
