-- 088 — Candidaturas à divulgação na Economia Local (Atalaia, 06/11/2026).
-- Os negócios da freguesia pedem, pelo formulário de /comercio.php, para ser divulgados; a Junta trata os pedidos no
-- backoffice (admin/comercio-candidaturas.php). O modo de candidaturas liga-se à parte, com tema_config
-- «comercio_candidaturas» = 1 (não é ligado aqui). Idempotente: só cria.
CREATE TABLE IF NOT EXISTS comercio_candidaturas (
    id                   INT AUTO_INCREMENT PRIMARY KEY,
    nome_estabelecimento VARCHAR(200) NOT NULL,
    atividade            VARCHAR(150) NOT NULL,
    morada               VARCHAR(255) NOT NULL,
    responsavel          VARCHAR(150) NOT NULL,
    email                VARCHAR(190) NOT NULL,
    telefone             VARCHAR(40)  NOT NULL,
    website              VARCHAR(255) NULL,
    descricao            TEXT NULL,
    imagem               VARCHAR(60)  NULL,
    estado               VARCHAR(20)  NOT NULL DEFAULT 'nova',
    notas                TEXT NULL,
    ip                   VARCHAR(45)  NULL,
    criado_em            DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    atualizado_em        DATETIME     NULL
);
