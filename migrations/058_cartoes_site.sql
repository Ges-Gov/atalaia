-- =====================================================================
-- 058_cartoes_site — CORE: cartões da página inicial editáveis no backoffice. Idempotente.
-- =====================================================================
-- Grupos: atalhos (acesso rápido), servicos (Serviços e informação útil), apoios (Perto de quem
-- precisa), descobrir (Descobrir), boasvindas (destaques do Bem-vindo). Editados em
-- Página Inicial › Cartões e atalhos (admin/cartoes-site.php).
--
-- Mesmo nome que a 058 do modelo novo, reescrita (20/10/2026): sem DEFAULT CHARSET/COLLATE (herda a
-- da BD) e só com os cartões GENÉRICOS que estavam escritos no código — servem a qualquer freguesia.
-- Os cartões próprios de cada Junta («Perto de quem precisa», «Descobrir») NÃO vão em migração:
-- carregam-se com o carregador de conteúdo (deploy/acrescentar_conteudo.php) ou no backoffice.
-- Os INSERT só correm com a tabela vazia (correr duas vezes não duplica).
-- =====================================================================

CREATE TABLE IF NOT EXISTS cartoes_site (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    grupo     VARCHAR(40)  NOT NULL,
    titulo    VARCHAR(160) NOT NULL,
    texto     VARCHAR(400) NULL,
    icone     VARCHAR(60)  NULL,
    imagem    VARCHAR(255) NULL,
    link      VARCHAR(255) NULL,
    etiqueta  VARCHAR(60)  NULL,
    acao      VARCHAR(80)  NULL,
    ordem     INT NOT NULL DEFAULT 0,
    ativo     TINYINT(1) NOT NULL DEFAULT 1,
    INDEX (grupo, ativo, ordem)
) ENGINE=InnoDB;

SET @vazia := (SELECT COUNT(*) = 0 FROM cartoes_site);

INSERT INTO cartoes_site (grupo, titulo, texto, icone, link, ordem)
SELECT * FROM (
    SELECT 'atalhos' AS g, 'Comunicar Ocorrência' AS t, 'Buracos, iluminação, limpeza' AS x, 'bi-exclamation-triangle-fill' AS i, '/pedidos.php' AS l, 1 AS o
    UNION ALL SELECT 'atalhos', 'Editais e Documentos', 'Atas, editais e informação oficial', 'bi-file-earmark-text-fill', '/assembleia-documentos.php', 2
    UNION ALL SELECT 'atalhos', 'Executivo e Assembleia', 'Quem representa a freguesia', 'bi-people-fill', '/executivo.php', 3
    UNION ALL SELECT 'atalhos', 'Pontos de Interesse', 'Património e locais a visitar', 'bi-geo-alt-fill', '/pontos.php', 4
    UNION ALL SELECT 'atalhos', 'Contactos', 'Telefone, email e horário', 'bi-telephone-fill', '/contactos.php', 5
    UNION ALL SELECT 'servicos', 'Contactos Úteis', 'Encontre contactos importantes da freguesia e do concelho.', 'bi-telephone-fill', '/contactos.php', 1
    UNION ALL SELECT 'servicos', 'Documentos', 'Consulte documentos oficiais da Junta de Freguesia.', 'bi-file-earmark-text-fill', '/freguesia-documentos.php', 2
    UNION ALL SELECT 'servicos', 'Mapa Interativo', 'Explore património, locais e pontos de interesse.', 'bi-map-fill', '/mapa.php', 3
    UNION ALL SELECT 'servicos', 'Economia Local', 'Conheça os estabelecimentos da freguesia.', 'bi-shop', '/comercio.php', 4
    UNION ALL SELECT 'boasvindas', 'História e heráldica da freguesia', NULL, 'bi-clock-history', '/historia.php', 1
    UNION ALL SELECT 'boasvindas', 'Comunidade e associativismo', NULL, 'bi-people-fill', '/associacoes.php', 2
    UNION ALL SELECT 'boasvindas', 'Serviços ao alcance de um clique', NULL, 'bi-laptop', '/pedidos.php', 3
) AS genericos
WHERE @vazia = 1;
