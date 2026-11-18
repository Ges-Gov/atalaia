-- Declaração de Acessibilidade e Usabilidade (DL 83/2018), 18/11/2026: resultados das avaliações do sítio, para a
-- declaração mostrar dados reais (secção II do modelo da AMA). A declaração usa a linha mais recente.
-- estado: plenamente | parcialmente | nao. Campos «auto_*» = avaliação automática (AccessMonitor);
-- «manual_*» = checklist «10 aspetos críticos»; «usabilidade» = testes com pessoas com deficiência (se houver).
CREATE TABLE IF NOT EXISTS acessibilidade_avaliacoes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    data_declaracao DATE NOT NULL,
    estado VARCHAR(20) NOT NULL DEFAULT 'parcialmente',
    auto_data DATE NULL,
    auto_ferramenta VARCHAR(80) NULL,
    auto_paginas INT UNSIGNED NULL,
    auto_nota DECIMAL(4,2) NULL,
    auto_resumo VARCHAR(255) NULL,
    manual_data DATE NULL,
    manual_paginas INT UNSIGNED NULL,
    manual_satisfeitas INT UNSIGNED NULL,
    manual_total INT UNSIGNED NULL,
    usabilidade TEXT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);
