-- =====================================================================
-- 018_contratacao_publica_pagina — página simples "Contratação Pública"
-- =====================================================================
-- CORE: idêntico em todos os sites.
--
-- A funcionalidade rica que existia em "Contratação Pública" (listagem de
-- procedimentos, anexos, filtros) passou a chamar-se "Procedimentos
-- Concursais" (tabelas contratacao_publica / contratacao_publica_anexos,
-- inalteradas — só o nome mostrado e os ficheiros PHP mudaram de nome).
--
-- "Contratação Pública" passa a ser uma página simples e só de
-- configuração: texto institucional + botão para o Portal BASE (onde a
-- contratação pública é legalmente publicada). Uma linha só, como
-- pagina_historia/contactos_pagina_config.
--
-- Idempotente: só cria a tabela e semeia a linha inicial se não existir.
-- =====================================================================

CREATE TABLE IF NOT EXISTS contratacao_publica_pagina (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    hero_kicker VARCHAR(150) NOT NULL DEFAULT 'Transparência e Gestão Pública',
    hero_titulo VARCHAR(255) NOT NULL DEFAULT 'Contratação Pública',
    texto TEXT NULL,
    botao_texto VARCHAR(150) NOT NULL DEFAULT 'Ver no Portal BASE',
    botao_link VARCHAR(500) NULL,
    atualizado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

SET @existe := (SELECT COUNT(*) FROM contratacao_publica_pagina);
SET @sql := IF(@existe = 0,
    'INSERT INTO contratacao_publica_pagina (hero_kicker, hero_titulo, texto, botao_texto, botao_link) VALUES (
        \'Transparência e Gestão Pública\',
        \'Contratação Pública\',
        \'Os procedimentos de contratação pública desta freguesia são publicados no Portal BASE, a plataforma oficial e obrigatória para a divulgação de contratos públicos em Portugal.\',
        \'Ver no Portal BASE\',
        NULL
    )',
    'SELECT 1');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;
