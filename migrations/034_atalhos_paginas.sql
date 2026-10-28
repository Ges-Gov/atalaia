-- =====================================================================
-- 034_atalhos_paginas — Barra de atalhos com ícones e páginas de conteúdo
-- =====================================================================
-- CORE: idêntico em todos os sites. Idempotente (só cria).
--
-- atalhos: barra de ícones na página inicial, logo a seguir aos cartões de acesso
--   rápido (ex.: no Sines, os ícones do designer da Junta — Visitar, Associativismo,
--   Rede Escolar, ATL, Serviços, Sugestões). Só aparece se houver atalhos ativos:
--   nos sites sem linhas nesta tabela a página inicial fica igual.
--   icone = ficheiro em assets/img/; url = página do site (ex.: /pontos.php) ou link externo.
--
-- paginas: páginas de conteúdo livre, servidas por /pagina.php?p=<slug>
--   (ex.: Rede Escolar, ATL, Serviços). conteudo é HTML gerado/revisto pela GesGov.
-- =====================================================================

CREATE TABLE IF NOT EXISTS atalhos (
    id      INT AUTO_INCREMENT PRIMARY KEY,
    titulo  VARCHAR(120) NOT NULL,
    icone   VARCHAR(255) DEFAULT NULL,
    url     VARCHAR(255) NOT NULL,
    ordem   INT NOT NULL DEFAULT 0,
    ativo   TINYINT(1) NOT NULL DEFAULT 1
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS paginas (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    slug       VARCHAR(120) NOT NULL,
    titulo     VARCHAR(255) NOT NULL,
    subtitulo  VARCHAR(255) DEFAULT NULL,
    kicker     VARCHAR(80) DEFAULT NULL,
    conteudo   MEDIUMTEXT,
    imagem     VARCHAR(255) DEFAULT NULL,
    ativo      TINYINT(1) NOT NULL DEFAULT 1,
    criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_slug (slug)
) ENGINE=InnoDB;
