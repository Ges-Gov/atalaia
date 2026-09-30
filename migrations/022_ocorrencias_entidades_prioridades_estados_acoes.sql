-- =====================================================================
-- 022_ocorrencias_entidades_prioridades_estados_acoes
-- =====================================================================
-- CORE: idêntico em todos os sites. Idempotente.
--
--   * ocorrencias_entidades_externas — lista gerida de a quem pode
--     caber resolver uma ocorrência (substitui o texto livre que a
--     "competência" era até aqui). Tem email próprio, para sugerir
--     automaticamente o destinatário ao enviar para entidade externa.
--   * ocorrencias_prioridades — as prioridades deixam de ser fixas no
--     código; passam a ser geridas (designação + cor), tal como as
--     categorias/tags já eram. A coluna pedidos_junta.prioridade era um
--     ENUM fixo — passa a VARCHAR para aceitar prioridades novas.
--   * ocorrencias_estados — o mesmo para os estados (designação + cor +
--     % da barra de progresso).
--   * ocorrencias_acoes — novo campo "Ação" (o que foi feito/vai ser
--     feito com a ocorrência, ex. "Enviar por E-mail").
--   * origem — o conjunto de valores era pobre (site/telefone/
--     presencial/email/outro); passa a incluir balcão, ofício e redes
--     sociais/app móvel, e o valor 'site' passa a 'website' (mais claro).
-- =====================================================================

CREATE TABLE IF NOT EXISTS ocorrencias_entidades_externas (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    designacao VARCHAR(150) NOT NULL,
    email      VARCHAR(255) NULL,
    ordem      INT NOT NULL DEFAULT 0,
    ativo      TINYINT(1) NOT NULL DEFAULT 1,
    criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_entidade (designacao)
) ENGINE=InnoDB;

-- "slug" é o código gravado em pedidos_junta (ex. 'critico'), igual ao que já
-- existia fixo no código — só a designação (o texto bonito) e a cor passam a
-- ser geridas. Isto evita ter de reescrever os valores das linhas existentes.
CREATE TABLE IF NOT EXISTS ocorrencias_prioridades (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    slug       VARCHAR(30) NOT NULL,
    designacao VARCHAR(60) NOT NULL,
    cor        VARCHAR(20) NOT NULL DEFAULT '#495057',
    ordem      INT NOT NULL DEFAULT 0,
    ativo      TINYINT(1) NOT NULL DEFAULT 1,
    criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_prioridade_slug (slug)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ocorrencias_estados (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    slug            VARCHAR(30) NOT NULL,
    designacao      VARCHAR(60) NOT NULL,
    cor             VARCHAR(20) NOT NULL DEFAULT '#495057',
    valor_percentual INT NOT NULL DEFAULT 0,
    ordem           INT NOT NULL DEFAULT 0,
    ativo           TINYINT(1) NOT NULL DEFAULT 1,
    criado_em       TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_estado_slug (slug)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ocorrencias_acoes (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    designacao VARCHAR(100) NOT NULL,
    ordem      INT NOT NULL DEFAULT 0,
    ativo      TINYINT(1) NOT NULL DEFAULT 1,
    criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_acao (designacao)
) ENGINE=InnoDB;

-- pedidos_junta.prioridade era ENUM fixo — passa a VARCHAR para aceitar
-- prioridades novas geridas em ocorrencias_prioridades.
SET @tipo := (
    SELECT COLUMN_TYPE FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'pedidos_junta' AND column_name = 'prioridade'
);
SET @sql := IF(@tipo LIKE 'enum%',
    "ALTER TABLE pedidos_junta MODIFY prioridade VARCHAR(30) NOT NULL DEFAULT 'normal'",
    'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- Novo campo "Ação".
SET @existe := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pedidos_junta' AND column_name = 'acao');
SET @sql := IF(@existe = 0, 'ALTER TABLE pedidos_junta ADD COLUMN acao VARCHAR(100) NULL', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- Origem: valores mais ricos. 'site' (o único usado até aqui) passa a 'website'.
ALTER TABLE pedidos_junta ALTER COLUMN origem SET DEFAULT 'website';
UPDATE pedidos_junta SET origem = 'website' WHERE origem = 'site';

-- Seed das listas geridas (idempotente, por designação única).
-- "Junta de Freguesia" mantém o nome exato do valor por omissão que
-- pedidos_junta.competencia já tinha, para bater certo com as linhas existentes.
INSERT IGNORE INTO ocorrencias_entidades_externas (designacao, email, ordem) VALUES
    ('Junta de Freguesia', NULL, 10),
    ('Câmara Municipal', NULL, 20),
    ('GNR - Guarda Nacional Republicana', NULL, 30),
    ('EDP/E-REDES', NULL, 40),
    ('Serviços Municipalizados de Água e Saneamento', NULL, 50);

INSERT IGNORE INTO ocorrencias_prioridades (slug, designacao, cor, ordem) VALUES
    ('critico', 'Crítico', '#c92a2a', 10),
    ('urgente', 'Urgente', '#e8590c', 20),
    ('normal', 'Normal', '#495057', 30),
    ('baixo', 'Baixo', '#adb5bd', 40);

INSERT IGNORE INTO ocorrencias_estados (slug, designacao, cor, valor_percentual, ordem) VALUES
    ('pendente', 'Pendente', '#f59f00', 15, 10),
    ('em_analise', 'Em análise', '#2563eb', 55, 20),
    ('resolvido', 'Resolvido', '#2b8a3e', 100, 30),
    ('arquivado', 'Arquivado', '#6b7280', 100, 40);

INSERT IGNORE INTO ocorrencias_acoes (designacao, ordem) VALUES
    ('Enviar por E-mail', 10),
    ('Enviar por Ofício', 20),
    ('Agendar Visita', 30),
    ('Resolver Internamente', 40),
    ('Arquivar sem Ação', 50);
