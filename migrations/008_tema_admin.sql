-- =====================================================================
-- 008_tema_admin — Cor escura do backoffice
-- =====================================================================
-- CORE (DDL): idêntico em todos os sites. O VALOR é semeado por site.
--
-- O admin/assets/admin.css tinha as cores fixas no ficheiro. Resultado: o
-- backoffice do riomoinhos era azul (as cores dele) e os outros 6 eram todos
-- cinzento-escuro + dourado (as cores do GRANHO) — mesmo os sites verdes,
-- vermelhos ou azuis. Era isto que fazia o backoffice "parecer diferente".
--
-- Agora o admin.css usa var(--cor-principal) / var(--cor-secundaria) (que já vêm
-- de configuracoes_site) e este token novo para o tom escuro (títulos, sidebar).
--
-- 'admin_escuro' — tom escuro do backoffice. Costuma ser uma versão mais escura
--                  da cor principal da freguesia.
-- =====================================================================

INSERT INTO tema_config (chave, valor, descricao) VALUES
  ('admin_escuro', '#11151B', 'Tom escuro do backoffice (títulos, sidebar)')
ON DUPLICATE KEY UPDATE descricao = VALUES(descricao);
