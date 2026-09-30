-- =====================================================================
-- 020_ocorrencias_avancado — Reforço da funcionalidade "Ocorrências"
-- =====================================================================
-- CORE: idêntico em todos os sites. Idempotente.
--
-- "Ocorrências" já existia (tabela pedidos_junta, admin/pedidos.php +
-- admin/ver_pedido.php) com mapa, chat cidadão↔admin, código de
-- acompanhamento e atribuição de operador. Esta migração ACRESCENTA o que
-- faltava, sem tocar no que já funciona:
--
--   * categoria (já existia, texto livre fixo no HTML) passa a ser gerida
--     em ocorrencias_categorias, e ganha uma subcategoria opcional
--     (ocorrencias_assuntos, em cascata a partir da categoria).
--   * prioridade já existia na coluna pedidos_junta.prioridade (ENUM), só
--     não estava exposta em nenhum ecrã — não precisa de ALTER.
--   * competencia (nova) — a quem cabe resolver (Junta, Câmara, etc.).
--   * tags (N:N via pedidos_tags).
--   * múltiplos funcionários atribuídos (N:N via pedidos_funcionarios),
--     em complemento ao operador_id existente (que continua a mandar no
--     isOperador() do auth.php — não se mexe nisso).
--   * tratamentos internos e notas internas (timelines só visíveis ao
--     backoffice, distintas do chat com o cidadão).
--   * emails para entidades externas (registo do que foi reenviado, ex.
--     para a Câmara Municipal).
--
-- pedido_timeline já existia (criada antes, nunca usada) — passa a ser
-- preenchida pelo admin/ver_pedido.php e por pedidos.php como o
-- "Histórico de Resolução".
-- =====================================================================

-- NOTA sobre collation: sem "DEFAULT CHARSET=utf8mb4" nas tabelas novas —
-- herdam a collation da BD (utf8mb4_general_ci), tal como as antigas.

CREATE TABLE IF NOT EXISTS ocorrencias_categorias (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    designacao VARCHAR(100) NOT NULL,
    ordem      INT NOT NULL DEFAULT 0,
    ativo      TINYINT(1) NOT NULL DEFAULT 1,
    criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_categoria (designacao)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ocorrencias_assuntos (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    categoria_id INT NOT NULL,
    designacao   VARCHAR(150) NOT NULL,
    ordem        INT NOT NULL DEFAULT 0,
    ativo        TINYINT(1) NOT NULL DEFAULT 1,
    criado_em    TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_categoria (categoria_id),
    CONSTRAINT fk_ocorrencias_assuntos_categoria
        FOREIGN KEY (categoria_id) REFERENCES ocorrencias_categorias(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ocorrencias_tags (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    designacao VARCHAR(100) NOT NULL,
    ativo      TINYINT(1) NOT NULL DEFAULT 1,
    criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_tag (designacao)
) ENGINE=InnoDB;

-- Relações N:N com pedidos_junta: sem FOREIGN KEY, mesmo padrão de
-- pedidos_imagens/pedido_mensagens (integridade fica do lado da aplicação).
CREATE TABLE IF NOT EXISTS pedidos_tags (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    tag_id    INT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pedido_tag (pedido_id, tag_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pedidos_funcionarios (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    admin_id  INT NOT NULL,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pedido_funcionario (pedido_id, admin_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pedidos_tratamentos (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id   INT NOT NULL,
    admin_id    INT DEFAULT NULL,
    autor_nome  VARCHAR(150) NOT NULL,
    nota        TEXT NOT NULL,
    criado_em   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pedido (pedido_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pedidos_notas_internas (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id   INT NOT NULL,
    admin_id    INT DEFAULT NULL,
    autor_nome  VARCHAR(150) NOT NULL,
    nota        TEXT NOT NULL,
    criado_em   TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pedido (pedido_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pedidos_emails_externos (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id        INT NOT NULL,
    destinatario     VARCHAR(255) NOT NULL,
    assunto          VARCHAR(255) NOT NULL,
    corpo            TEXT NOT NULL,
    enviado_por_nome VARCHAR(150) DEFAULT NULL,
    criado_em        TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_pedido (pedido_id)
) ENGINE=InnoDB;

-- ALTER idempotente a pedidos_junta: subcategoria (cascata da categoria) e
-- competência (a quem cabe resolver). "prioridade" já existe, não se mexe.
SET @existe := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'pedidos_junta' AND column_name = 'subcategoria'
);
SET @sql := IF(@existe = 0,
    'ALTER TABLE pedidos_junta ADD COLUMN subcategoria VARCHAR(150) NULL AFTER categoria',
    'SELECT 1');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @existe := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'pedidos_junta' AND column_name = 'competencia'
);
SET @sql := IF(@existe = 0,
    'ALTER TABLE pedidos_junta ADD COLUMN competencia VARCHAR(120) NULL DEFAULT ''Junta de Freguesia''',
    'SELECT 1');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;

-- Seed das categorias/subcategorias/tags de base (idempotente por designação
-- única). Mantém as 6 categorias que já estavam fixas no HTML de pedidos.php,
-- para os pedidos existentes continuarem a bater certo com a lista.
INSERT IGNORE INTO ocorrencias_categorias (designacao, ordem) VALUES
    ('Limpeza urbana', 10),
    ('Iluminação pública', 20),
    ('Espaços verdes', 30),
    ('Vias e passeios', 40),
    ('Sugestão', 50),
    ('Outro', 60);

INSERT INTO ocorrencias_assuntos (categoria_id, designacao, ordem)
SELECT c.id, s.designacao, s.ordem
FROM ocorrencias_categorias c
JOIN (
    SELECT 'Limpeza urbana' AS categoria, 'Lixo acumulado' AS designacao, 10 AS ordem
    UNION ALL SELECT 'Limpeza urbana', 'Contentor danificado', 20
    UNION ALL SELECT 'Limpeza urbana', 'Dejetos de animais', 30
    UNION ALL SELECT 'Iluminação pública', 'Candeeiro fundido', 10
    UNION ALL SELECT 'Iluminação pública', 'Poste danificado', 20
    UNION ALL SELECT 'Espaços verdes', 'Corte de relva/mato', 10
    UNION ALL SELECT 'Espaços verdes', 'Árvore/ramo em risco', 20
    UNION ALL SELECT 'Vias e passeios', 'Buraco na via', 10
    UNION ALL SELECT 'Vias e passeios', 'Sinalização em falta/danificada', 20
    UNION ALL SELECT 'Vias e passeios', 'Passeio danificado', 30
) s ON s.categoria = c.designacao
WHERE NOT EXISTS (
    SELECT 1 FROM ocorrencias_assuntos a
    WHERE a.categoria_id = c.id AND a.designacao = s.designacao
);

INSERT IGNORE INTO ocorrencias_tags (designacao) VALUES
    ('Urgente'), ('Reincidente'), ('Via pública'), ('Zona escolar'), ('Zona residencial');
