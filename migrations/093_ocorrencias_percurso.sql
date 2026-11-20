-- Ocorrências, 20/11/2026 (pedido da direção): o percurso de cada ocorrência — a quem foi encaminhada, por quem e
-- porquê, e o que cada pessoa fez (com fotografias da intervenção). O «Histórico de Resolução» (pedido_timeline)
-- passa a guardar quem fez cada passo (autor) e, nos encaminhamentos, para quem foi (para). As linhas antigas
-- ficam sem autor (aparecem como antes). Fotografias de cada passo em pedido_timeline_fotos.

CREATE TABLE IF NOT EXISTS pedido_timeline (
    id INT AUTO_INCREMENT PRIMARY KEY,
    pedido_id INT NOT NULL,
    tipo VARCHAR(50) NOT NULL,
    titulo VARCHAR(255) NOT NULL,
    descricao TEXT,
    criado_em DATETIME DEFAULT CURRENT_TIMESTAMP
);

SET @existe := (SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = 'pedido_timeline' AND column_name = 'autor_id');
SET @sql := IF(@existe = 0, 'ALTER TABLE pedido_timeline ADD COLUMN autor_id INT NULL AFTER descricao, ADD COLUMN autor_nome VARCHAR(150) NULL AFTER autor_id', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @existe := (SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = 'pedido_timeline' AND column_name = 'para_id');
SET @sql := IF(@existe = 0, 'ALTER TABLE pedido_timeline ADD COLUMN para_id INT NULL AFTER autor_nome, ADD COLUMN para_nome VARCHAR(150) NULL AFTER para_id', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @existe := (SELECT COUNT(*) FROM information_schema.statistics
                WHERE table_schema = DATABASE() AND table_name = 'pedido_timeline' AND index_name = 'idx_pedido');
SET @sql := IF(@existe = 0, 'ALTER TABLE pedido_timeline ADD KEY idx_pedido (pedido_id)', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

CREATE TABLE IF NOT EXISTS pedido_timeline_fotos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    timeline_id INT NOT NULL,
    pedido_id INT NOT NULL,
    ficheiro VARCHAR(255) NOT NULL,
    criado_em TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    KEY idx_timeline (timeline_id),
    KEY idx_pedido (pedido_id)
);
