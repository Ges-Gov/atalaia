-- 001_tema_config_seed — MARCA (específico deste site: JFGRANHO)
-- Valores extraídos do bloco de tema que estava hardcoded no footer.php,
-- para que a aparência do site fique EXATAMENTE igual à de antes.
INSERT INTO tema_config (chave, valor, descricao) VALUES
  ('fundo',            '#FAF6EC', 'Cor de fundo do site (body)'),
  ('topbar_bg',        'var(--cor-secundaria)',   'Fundo da barra de topo'),
  ('topbar_texto',     '#242A30',  'Texto da barra de topo'),
  ('topbar_borda',     'transparent',  'Bordo inferior da barra de topo'),
  ('hero_1',           '#FCF8EF',    'Gradiente do herói — cor inicial'),
  ('hero_2',           '#EFE2C2',    'Gradiente do herói — cor final'),
  ('hero_texto',       '#242A30',  'Texto dos heróis (sem imagem de fundo)'),
  ('acento',           '#C8A02E',    'Cor de acento (bordos, kicker, badges)'),
  ('acento_escuro',    '#856611',   'Acento escuro (texto do kicker, títulos do rodapé)'),
  ('footer_bg',        '#F2EAD6',   'Fundo do rodapé'),
  ('footer_texto',     '#3a3f47',  'Texto do rodapé'),
  ('kicker_img_texto', '#FCE9B8',  'Texto do kicker quando o herói tem imagem de fundo')
ON DUPLICATE KEY UPDATE valor = VALUES(valor), descricao = VALUES(descricao);
