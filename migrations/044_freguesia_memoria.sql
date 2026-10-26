-- =====================================================================
-- 044_freguesia_memoria — "Memória da freguesia" (história viva na página A Freguesia)
-- =====================================================================
-- CORE: idêntico em todos os sites. Idempotente.
--
-- Blocos de conteúdo histórico/cultural, cada um COM FONTE, mostrados em freguesia.php e
-- geridos no admin (A Freguesia → Memória da Freguesia). O conteúdo é MARCA de cada site:
-- carregador de conteúdo (deploy/<site>_redesign.json) ou backoffice.
--
-- Mesmo nome que a 044 do modelo novo; sem DEFAULT CHARSET/COLLATE (20/10/2026): herda a da BD.
--
-- secao:
--   arquivo  — imagens antigas / documentos (com legenda e crédito)
--   lenda    — lendas e memórias populares (indicar quando não têm base histórica)
--   fe       — fé, devoção, igrejas e romarias
--   tradicao — calendário de tradições, feiras e festas (subtitulo = época do ano)
--   tempo    — passado / presente / futuro (subtitulo = "Passado", "Presente", "Futuro")
-- =====================================================================
CREATE TABLE IF NOT EXISTS freguesia_memoria (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    secao           VARCHAR(20)  NOT NULL,
    titulo          VARCHAR(200) NOT NULL,
    subtitulo       VARCHAR(120) DEFAULT NULL,
    texto           TEXT NOT NULL,
    icone           VARCHAR(60)  DEFAULT NULL,
    imagem          VARCHAR(255) DEFAULT NULL,
    imagem_legenda  VARCHAR(255) DEFAULT NULL,
    imagem_credito  VARCHAR(255) DEFAULT NULL,
    fonte_nome      VARCHAR(255) DEFAULT NULL,
    fonte_url       VARCHAR(500) DEFAULT NULL,
    ordem           INT NOT NULL DEFAULT 0,
    ativo           TINYINT(1) NOT NULL DEFAULT 1,
    criado_em       TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX (secao, ordem)
) ENGINE=InnoDB;
