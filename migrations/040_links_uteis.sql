-- =====================================================================
-- 040_links_uteis — Secção "Links úteis" (logótipos de entidades e parceiros). Idempotente.
-- =====================================================================
-- CORE: idêntico em todos os sites.
--
-- Logótipos com ligação para o site de cada entidade, geridos no admin (Página Inicial →
-- Links Úteis). "patrocinado" marca os espaços de publicidade paga: aparecem com a etiqueta
-- "Patrocinado" (transparência obrigatória numa entidade pública) e com rel="sponsored".
-- As entidades de cada site são conteúdo da Junta: carregador de conteúdo ou backoffice.
--
-- Mesmo nome que a 040 do modelo novo, sem DEFAULT CHARSET/COLLATE (20/10/2026): herda a da BD.
-- =====================================================================

CREATE TABLE IF NOT EXISTS links_uteis (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    nome         VARCHAR(150) NOT NULL,
    url          VARCHAR(500) NOT NULL,
    logo         VARCHAR(255) DEFAULT NULL,
    patrocinado  TINYINT(1) NOT NULL DEFAULT 0,
    ordem        INT NOT NULL DEFAULT 0,
    ativo        TINYINT(1) NOT NULL DEFAULT 1,
    criado_em    TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;
