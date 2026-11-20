-- Assembleia de Freguesia, 20/11/2026 (pedido da direção): a ordem de trabalhos deixa de ser texto livre e passa a
-- pontos, como nas Atas da Intranet (ata_ponto): período (antes da ordem do dia / ordem do dia / intervenção do público),
-- ordem, subpontos, proposta, documentos de cada ponto e o resultado da votação, registado depois da sessão.
-- O texto antigo (assembleia_sessoes.ordem_trabalhos) fica: aparece enquanto a sessão não tiver pontos.

CREATE TABLE IF NOT EXISTS assembleia_pontos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sessao_id INT NOT NULL,
    pai_id INT NULL,
    periodo VARCHAR(10) NOT NULL DEFAULT 'OD',
    ordem INT NOT NULL DEFAULT 0,
    titulo VARCHAR(500) NOT NULL,
    descricao TEXT NULL,
    proposta_ref VARCHAR(150) NULL,
    tem_votacao TINYINT(1) NOT NULL DEFAULT 1,
    resultado VARCHAR(20) NULL,
    unanimidade TINYINT(1) NOT NULL DEFAULT 0,
    votos_favor SMALLINT NULL,
    votos_contra SMALLINT NULL,
    votos_abstencao SMALLINT NULL,
    deliberacao TEXT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em TIMESTAMP NULL DEFAULT NULL,
    KEY idx_sessao (sessao_id, periodo, ordem),
    KEY idx_pai (pai_id)
);

CREATE TABLE IF NOT EXISTS assembleia_ponto_documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    ponto_id INT NOT NULL,
    documento_id INT NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_ponto_documento (ponto_id, documento_id),
    KEY idx_documento (documento_id)
);

CREATE TABLE IF NOT EXISTS assembleia_sessao_documentos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sessao_id INT NOT NULL,
    documento_id INT NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_sessao_documento (sessao_id, documento_id),
    KEY idx_sessao_id (sessao_id),
    KEY idx_documento_id (documento_id)
);
