-- 003_marca_logo_seed — MARCA (específico deste site: JFGRANHO)
INSERT INTO tema_config (chave, valor, descricao) VALUES
  ('logo_iniciais', 'GR', 'Iniciais da freguesia (fallback do logótipo no rodapé)'),
  ('favicon',       'brasao-granho.png', 'Ficheiro do favicon em assets/img/ (vazio = usa o logo)')
ON DUPLICATE KEY UPDATE valor = VALUES(valor);
