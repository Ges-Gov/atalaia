-- =====================================================================
-- 021_ocorrencias_origem_estado_interno — mais 4 reforços às Ocorrências
-- =====================================================================
-- CORE: idêntico em todos os sites. Idempotente.
--
--   * origem — como a ocorrência chegou (site, telefone, presencial,
--     email, outro). Até agora só existia o formulário público, por isso
--     todas as ocorrências já existentes ficam com origem='site'.
--   * estado_interno — estado de trabalho da equipa (por_tratar /
--     em_tratamento / tratado), DISTINTO do "estado" já existente, que é
--     o que o cidadão vê. Permite à equipa acompanhar o andamento sem
--     mexer no que é mostrado publicamente.
--   * campos *_original — snapshot do que foi submetido/registado da
--     primeira vez (nome, email, telefone, categoria, subcategoria,
--     assunto, mensagem, localizacao). Nunca são alterados depois de
--     escritos; servem para comparar com o que a equipa possa vir a
--     corrigir mais tarde (reclassificação). As linhas já existentes são
--     preenchidas uma única vez a partir dos valores atuais.
-- =====================================================================

SET @existe := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pedidos_junta' AND column_name = 'origem');
SET @sql := IF(@existe = 0, "ALTER TABLE pedidos_junta ADD COLUMN origem VARCHAR(30) NOT NULL DEFAULT 'site'", 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @existe := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pedidos_junta' AND column_name = 'estado_interno');
SET @sql := IF(@existe = 0, "ALTER TABLE pedidos_junta ADD COLUMN estado_interno VARCHAR(30) NOT NULL DEFAULT 'por_tratar'", 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @existe := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pedidos_junta' AND column_name = 'nome_original');
SET @sql := IF(@existe = 0, 'ALTER TABLE pedidos_junta ADD COLUMN nome_original VARCHAR(255) NULL', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @existe := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pedidos_junta' AND column_name = 'email_original');
SET @sql := IF(@existe = 0, 'ALTER TABLE pedidos_junta ADD COLUMN email_original VARCHAR(255) NULL', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @existe := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pedidos_junta' AND column_name = 'telefone_original');
SET @sql := IF(@existe = 0, 'ALTER TABLE pedidos_junta ADD COLUMN telefone_original VARCHAR(50) NULL', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @existe := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pedidos_junta' AND column_name = 'categoria_original');
SET @sql := IF(@existe = 0, 'ALTER TABLE pedidos_junta ADD COLUMN categoria_original VARCHAR(100) NULL', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @existe := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pedidos_junta' AND column_name = 'subcategoria_original');
SET @sql := IF(@existe = 0, 'ALTER TABLE pedidos_junta ADD COLUMN subcategoria_original VARCHAR(150) NULL', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @existe := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pedidos_junta' AND column_name = 'assunto_original');
SET @sql := IF(@existe = 0, 'ALTER TABLE pedidos_junta ADD COLUMN assunto_original VARCHAR(255) NULL', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @existe := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pedidos_junta' AND column_name = 'mensagem_original');
SET @sql := IF(@existe = 0, 'ALTER TABLE pedidos_junta ADD COLUMN mensagem_original TEXT NULL', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

SET @existe := (SELECT COUNT(*) FROM information_schema.columns WHERE table_schema = DATABASE() AND table_name = 'pedidos_junta' AND column_name = 'localizacao_original');
SET @sql := IF(@existe = 0, 'ALTER TABLE pedidos_junta ADD COLUMN localizacao_original VARCHAR(255) NULL', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;

-- Backfill das linhas já existentes: só preenche quem ainda não tem snapshot.
UPDATE pedidos_junta
SET nome_original = nome,
    email_original = email,
    telefone_original = telefone,
    categoria_original = categoria,
    subcategoria_original = subcategoria,
    assunto_original = assunto,
    mensagem_original = mensagem,
    localizacao_original = localizacao
WHERE nome_original IS NULL;
