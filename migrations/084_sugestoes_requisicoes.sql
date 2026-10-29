-- =====================================================================
-- 084_sugestoes_requisicoes — Sugestões com telemóvel, requisições de espaços nas Marcações
-- =====================================================================
-- CORE: idêntico em todos os sites. Idempotente (só cria/acrescenta; a categoria «Sugestão»
-- das ocorrências fica inativa, não se apaga — os pedidos antigos com ela continuam iguais).
--
-- sugestoes.telefone: o formulário /sugestoes.php passou a pedir o telemóvel (30/10/2026).
-- marcacoes_atendimento: a página Marcações passou a aceitar também requisições/alugueres de
--   espaços ou equipamento — tipo_pedido ('atendimento' | 'requisicao'), o que se requisita
--   (recurso), até que horas (hora_fim) e quantas pessoas (num_pessoas).
-- ocorrencias_categorias: as sugestões têm agora formulário próprio, por isso a categoria
--   «Sugestão» deixa de aparecer nas Ocorrências.
-- =====================================================================

SET @existe := (SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = 'sugestoes' AND column_name = 'telefone');
SET @sql := IF(@existe = 0, 'ALTER TABLE sugestoes ADD COLUMN telefone VARCHAR(30) DEFAULT NULL AFTER email', 'SELECT 1');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @existe := (SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = 'marcacoes_atendimento' AND column_name = 'tipo_pedido');
SET @sql := IF(@existe = 0, 'ALTER TABLE marcacoes_atendimento ADD COLUMN tipo_pedido VARCHAR(20) NOT NULL DEFAULT ''atendimento'' AFTER id', 'SELECT 1');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @existe := (SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = 'marcacoes_atendimento' AND column_name = 'recurso');
SET @sql := IF(@existe = 0, 'ALTER TABLE marcacoes_atendimento ADD COLUMN recurso VARCHAR(200) DEFAULT NULL AFTER assunto', 'SELECT 1');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @existe := (SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = 'marcacoes_atendimento' AND column_name = 'hora_fim');
SET @sql := IF(@existe = 0, 'ALTER TABLE marcacoes_atendimento ADD COLUMN hora_fim TIME DEFAULT NULL AFTER hora_marcacao', 'SELECT 1');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;

SET @existe := (SELECT COUNT(*) FROM information_schema.columns
                WHERE table_schema = DATABASE() AND table_name = 'marcacoes_atendimento' AND column_name = 'num_pessoas');
SET @sql := IF(@existe = 0, 'ALTER TABLE marcacoes_atendimento ADD COLUMN num_pessoas INT DEFAULT NULL AFTER hora_fim', 'SELECT 1');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;

UPDATE ocorrencias_categorias SET ativo = 0
 WHERE CONVERT(designacao USING utf8mb4) COLLATE utf8mb4_general_ci IN ('Sugestão', 'Sugestões', 'Sugestao', 'Sugestoes');

-- As Sugestões passam a estar ligadas em todos os sites (menu do site, rodapé e backoffice).
INSERT INTO tema_config (chave, valor, descricao)
VALUES ('sugestoes_ativo', '1', 'Mostra as Sugestões no menu do backoffice')
ON DUPLICATE KEY UPDATE valor = '1';
