-- 083 — Resultados eleitorais da Assembleia de Freguesia (distribuição dos lugares, votos, participação),
-- mostrados em /assembleia-composicao.php. CORE, idempotente.
--
-- Mesmo nome que a 083 do modelo novo (20/10/2026), reescrita: só as tabelas, sem DEFAULT CHARSET/COLLATE
-- (herdam a da BD). Os resultados de cada freguesia são conteúdo da Junta: carregador de conteúdo
-- (deploy/<site>_redesign.json) ou backoffice — não vão por migração.
CREATE TABLE IF NOT EXISTS assembleia_eleicao (
    id INT AUTO_INCREMENT PRIMARY KEY,
    mandato VARCHAR(20) NOT NULL,
    data_eleicao DATE NULL,
    membros_total INT NOT NULL DEFAULT 0,
    inscritos INT NULL,
    votantes INT NULL,
    fonte VARCHAR(255) NULL,
    fonte_url VARCHAR(255) NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    UNIQUE KEY uk_assembleia_eleicao_mandato (mandato)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS assembleia_eleicao_listas (
    id INT AUTO_INCREMENT PRIMARY KEY,
    eleicao_id INT NOT NULL,
    sigla VARCHAR(30) NOT NULL,
    nome VARCHAR(150) NULL,
    votos_pct DECIMAL(5,2) NULL,
    mandatos INT NOT NULL DEFAULT 0,
    cor VARCHAR(9) NULL,            -- cor da lista no gráfico (validada para daltonismo e contraste)
    ordem INT NOT NULL DEFAULT 0,
    KEY idx_listas_eleicao (eleicao_id, ordem)
) ENGINE=InnoDB;
