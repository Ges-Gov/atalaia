-- =====================================================================
-- 003_marca_logo — Iniciais e favicon para fora do código
-- =====================================================================
-- CORE (DDL): idêntico em todos os sites. Os VALORES são semeados no
-- 003_marca_logo_seed.sql de cada site (é aí que vive a marca).
--
-- Porquê: o includes/footer.php tinha as iniciais hardcoded (<span>GR</span>,
-- <span>TG</span>, ...) e o includes/header.php tinha o ficheiro do favicon
-- hardcoded (brasao-granho.png, favicon-terrugem.png, ...). Eram as duas
-- últimas coisas que impediam header.php e footer.php de serem ficheiros core
-- iguais em todos os sites.
--
-- 'logo_iniciais' — usado no rodapé quando não há imagem de logótipo.
-- 'favicon'       — ficheiro em assets/img/ para o ícone do separador do browser.
--                   Fica vazio => usa o logo de configuracoes_site.
-- =====================================================================

INSERT INTO tema_config (chave, valor, descricao) VALUES
  ('logo_iniciais', '', 'Iniciais da freguesia (fallback do logótipo no rodapé)'),
  ('favicon',       '', 'Ficheiro do favicon em assets/img/ (vazio = usa o logo)')
ON DUPLICATE KEY UPDATE descricao = VALUES(descricao);
