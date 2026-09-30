-- ============================================================
-- Atalaia e Alto Estanqueiro-Jardia — marca e conteúdo inicial
-- ============================================================
-- Corre sobre uma BD criada a partir do dump do Caia (esquema completo,
-- migrações 001–032). Substitui todo o conteúdo do Caia.
--
-- Fontes: juf.aaej.pt (site oficial da Junta, mandato 2025–2029),
-- mun-montijo.pt (Câmara Municipal do Montijo: resenha histórica,
-- heráldica, Museu Agrícola), OpenStreetMap (coordenadas), Wikimedia
-- Commons (fotos CC BY-SA). Ver CLAUDE.md para o detalhe.
-- ============================================================

SET NAMES utf8mb4;

-- ------------------------------------------------------------
-- 1. Limpar o conteúdo herdado do Caia
-- ------------------------------------------------------------
DELETE FROM galeria_imagens;
DELETE FROM galeria_albuns;
DELETE FROM noticias_imagens;
DELETE FROM noticias;
DELETE FROM eventos;
DELETE FROM pontos_interesse;
DELETE FROM associacoes;
DELETE FROM comercio_local;
DELETE FROM contactos_uteis;
DELETE FROM documentos;
DELETE FROM executivo_membros;
DELETE FROM assembleia_composicao;
DELETE FROM heraldica_elementos;
DELETE FROM slides_homepage;
DELETE FROM requerimentos_ficheiros;
DELETE FROM requerimentos;
DELETE FROM separadores_fundo;

-- ------------------------------------------------------------
-- 2. Marca
-- ------------------------------------------------------------
-- Cores do Manual de Normas Gráficas da Junta (dezembro de 2025):
-- azul-violeta #312783 (principal; texto branco ≈ 12:1) e amarelo
-- #F9B233 (secundária; pede texto escuro). Logótipo oficial recortado
-- do PDF vetorial do manual (símbolo = Cruzeiro Mor estilizado).
UPDATE configuracoes_site SET
    nome_site          = 'Junta de Freguesia de Atalaia e Alto Estanqueiro-Jardia',
    municipio          = 'Montijo',
    slogan             = NULL,
    email              = 'geral@juf.aaej.pt',
    dominio            = NULL,
    telefone           = '212 320 480',
    morada             = 'Av. 28 de Setembro, n.º 56, 2870-701 Atalaia',
    logo               = 'logo-aaej.png',
    cor_principal      = '#312783',
    cor_secundaria     = '#F9B233',
    footer             = NULL,
    facebook           = 'https://www.facebook.com/jfaaej/',
    instagram          = NULL,
    horario            = 'Segunda a sexta-feira, das 9h00 às 12h30 e das 14h00 às 17h30',
    email_notificacoes = 'geral@juf.aaej.pt';

UPDATE tema_config SET valor = '#F7F8F5' WHERE chave = 'fundo';
UPDATE tema_config SET valor = '#242A30' WHERE chave = 'topbar_texto';
UPDATE tema_config SET valor = 'var(--cor-principal)' WHERE chave = 'topbar_borda';
UPDATE tema_config SET valor = '#F7F8F5' WHERE chave = 'hero_1';
UPDATE tema_config SET valor = '#E7E5F2' WHERE chave = 'hero_2';
UPDATE tema_config SET valor = '#242A30' WHERE chave = 'hero_texto';
UPDATE tema_config SET valor = '#F9B233' WHERE chave = 'acento';
UPDATE tema_config SET valor = '#8A5A00' WHERE chave = 'acento_escuro';
UPDATE tema_config SET valor = '#F2F4F1' WHERE chave = 'footer_bg';
UPDATE tema_config SET valor = 'AAEJ'    WHERE chave = 'logo_iniciais';
UPDATE tema_config SET valor = 'logo-aaej.png' WHERE chave = 'favicon';

-- ------------------------------------------------------------
-- 3. Páginas de configuração
-- ------------------------------------------------------------
DELETE FROM pagina_freguesia;
INSERT INTO pagina_freguesia
(id, hero_kicker, hero_titulo, hero_subtitulo, hero_imagem, intro_titulo, intro_texto,
 historia_titulo, historia_texto, identidade_titulo, identidade_texto,
 patrimonio_titulo, patrimonio_texto, localidades_titulo, localidades_texto,
 galeria_titulo, botao1_texto, botao1_link, botao2_texto, botao2_link)
VALUES
(1, 'Concelho do Montijo', 'Atalaia e Alto Estanqueiro-Jardia',
 'Num monte sobranceiro ao estuário do Tejo, entre o Santuário da Atalaia e os campos do Alto Estanqueiro e da Jardia.',
 'santuario_atalaia.jpg',
 'Uma freguesia do Montijo',
 'A União das Freguesias de Atalaia e Alto Estanqueiro-Jardia pertence ao concelho do Montijo, distrito de Setúbal. Tem 13,65 km² e 5379 habitantes (Censos 2021). Foi constituída pela Lei n.º 11-A/2013, de 28 de janeiro, que agregou as freguesias da Atalaia e do Alto Estanqueiro-Jardia.',
 'História e memória',
 'A cerca de quatro quilómetros da sede do município, a Atalaia beneficiou desde sempre da proximidade da Estrada Real que ligava Lisboa a Badajoz, por Aldeia Galega, e viu passar monarcas e outras personagens ilustres a caminho da fronteira e do sul do país. Já no início do século XVI, as populações locais e dos arredores vinham aqui em peregrinação.',
 'Identidade',
 'O culto de Nossa Senhora da Atalaia, vivido por romeiros e festeiros, é o grande traço de identidade da freguesia. Alguns monarcas foram particularmente devotos da Senhora, como D. João V; a última visita régia foi a da rainha D. Maria II, a 5 de outubro de 1843.',
 'Património',
 'A Igreja de Nossa Senhora da Atalaia e os seus três cruzeiros — o Cruzeiro Mor (1551), o Cruzeiro de Alcochete (1669) e o Cruzeiro das Esmolas — foram classificados em 2009 como Imóveis de Interesse Público. Junto à escadaria do Santuário fica o Museu Agrícola da Atalaia, na Quinta Nova da Atalaia.',
 'Localidades da freguesia',
 'Atalaia|Sede da freguesia, junto ao Santuário de Nossa Senhora da Atalaia\nAlto Estanqueiro|Onde fica a dependência da Junta, na Quinta das Tílias\nJardia|Lugar já referido em 1866, de tradição hortícola',
 'A freguesia em imagens',
 'Ver pontos de interesse', '/pontos.php', 'Explorar no mapa', '/mapa.php');

DELETE FROM pagina_historia;
INSERT INTO pagina_historia
(id, hero_kicker, hero_titulo, hero_subtitulo, hero_imagem, intro_titulo, intro_texto,
 bloco1_titulo, bloco1_texto, bloco2_titulo, bloco2_texto, timeline_titulo, timeline_texto,
 galeria_titulo)
VALUES
(1, 'História', 'História de Atalaia e Alto Estanqueiro-Jardia',
 'Do Santuário da Atalaia aos campos hortícolas do Alto Estanqueiro e da Jardia.',
 'cruzeiro_mor.jpg',
 'A Atalaia',
 'Assente num monte sobranceiro ao estuário do Tejo, a povoação da Atalaia cresceu à volta do seu Santuário. A proximidade da Estrada Real, que ligava Lisboa a Badajoz via Aldeia Galega, trouxe-lhe passagem constante de viajantes — e a fé trouxe-lhe peregrinos desde o início do século XVI. Por volta de 1507, os funcionários da Alfândega de Lisboa vieram aqui em promessa a Nossa Senhora da Atalaia por ocasião de uma peste.',
 'Tempos difíceis',
 'Em 1808, as invasões francesas levaram ao saque da Igreja da Atalaia pelos exércitos de Napoleão. Mais tarde, com a implantação da República e o anticlericalismo que a acompanhou, o Cruzeiro Mor ficou com as imagens decapitadas e a coroa das armas reais do retábulo partida; em 1912, depois de um comício em Aldeia Galega, populares assaltaram a igreja. Ainda assim, o culto da Senhora da Atalaia manteve-se vivo até aos nossos dias.',
 'Alto Estanqueiro e Jardia',
 'Nascida da junção de dois lugares, a antiga freguesia do Alto Estanqueiro-Jardia pertenceu à jurisdição da Ordem de Santiago, sediada em Palmela. O topónimo «Estanqueiro» liga-se provavelmente ao comércio em regime de monopólio (tabaco, pólvora, palha); «Jardia» à járdia, a charneca de rosmaninho e alecrim. Até meados do século XX o território era de fazendas e terrenos agrícolas que abasteciam o concelho de produtos hortícolas; o crescimento urbano veio na segunda metade do século.',
 'Principais datas',
 'c. 1507|Os funcionários da Alfândega de Lisboa vêm em promessa a Nossa Senhora da Atalaia, por ocasião de uma peste.\n1551|A Confraria de Lisboa manda construir o Cruzeiro Mor.\n1669|Uma família de Alcochete manda construir o Cruzeiro de Alcochete.\n1808|Saque da Igreja da Atalaia durante as invasões francesas.\n1843|A rainha D. Maria II visita a Igreja da Atalaia, a 5 de outubro.\n1866|A Jardia é referida como lugar da freguesia do Divino Espírito Santo do Montijo.\n1985|A Lei n.º 82/85, de 4 de outubro, cria a freguesia de Alto Estanqueiro-Jardia.\n1997|Abre ao público o Museu Agrícola da Atalaia, na Quinta Nova da Atalaia.\n2009|A Igreja de Nossa Senhora da Atalaia e os três cruzeiros são classificados como Imóveis de Interesse Público.\n2013|Lei n.º 11-A/2013: constituição da União das Freguesias de Atalaia e Alto Estanqueiro-Jardia.',
 'A freguesia em imagens');

-- Heráldica: a união ainda não tem ordenação heráldica publicada em
-- Diário da República. Mostra-se o logótipo institucional (2025) e, como
-- elementos, os brasões e bandeiras das antigas freguesias (texto da CM
-- Montijo). O heraldicacivica.pt NÃO é usado como fonte: em 30/09/2026
-- o domínio servia uma página de casinos online.
DELETE FROM heraldica_pagina;
INSERT INTO heraldica_pagina
(id, hero_kicker, hero_titulo, hero_subtitulo, titulo, texto_intro, imagem, fonte_texto, fonte_url, ativo)
VALUES
(1, 'Heráldica', 'Símbolos de Atalaia e Alto Estanqueiro-Jardia',
 'Logótipo institucional da União das Freguesias e brasões das antigas freguesias.',
 'Símbolos da União das Freguesias',
 'A União das Freguesias de Atalaia e Alto Estanqueiro-Jardia ainda não tem ordenação heráldica própria publicada em Diário da República. Até lá, apresentam-se os brasões e bandeiras das antigas freguesias da Atalaia e do Alto Estanqueiro-Jardia. O logótipo institucional, adotado em dezembro de 2025, estiliza o Cruzeiro Mor da Atalaia.',
 'logo-aaej-oficial.png',
 'Câmara Municipal do Montijo — Descrição Heráldica',
 'https://www.mun-montijo.pt/municipio/freguesias/uniao-das-freguesias-de-atalaia-e-alto-estanqueiro-jardia',
 1);

INSERT INTO heraldica_elementos (titulo, descricao, icone, ordem, ativo) VALUES
('Brasão da Atalaia', 'Escudo de prata, cruzeiro de púrpura assente num monte de negro, movente da ponta e entre uma flor-de-lis de azul, à dextra, e uma espiga de milho de ouro, folhada de verde, à sinistra. Coroa mural de prata de três torres. Listel branco, com a legenda a negro: «Atalaia – Montijo».', 'bi-shield', 1, 1),
('Bandeira da Atalaia', 'De azul. Cordão e borlas de prata e azul. Haste e lança de ouro.', 'bi-flag', 2, 1),
('Brasão do Alto Estanqueiro-Jardia', 'Escudo de prata, uma cruz da Ordem de Santiago, de vermelho, uma roda dentada de azul, uma espiga de milho de ouro, folhada de verde, e um pinheiro arrancado de verde, frutado de ouro, as quatro figuras dispostas em cruz. Coroa mural de três torres de prata. Listel branco, com a legenda a negro: «Alto Estanqueiro–Jardia».', 'bi-shield', 3, 1),
('Bandeira do Alto Estanqueiro-Jardia', 'De vermelho. Cordão e borlas de prata e vermelho. Haste e lança de ouro.', 'bi-flag', 4, 1);

DELETE FROM contactos_pagina_config;
INSERT INTO contactos_pagina_config
(id, hero_kicker, hero_titulo, hero_subtitulo, bloco_titulo, bloco_texto, horario_titulo, horario_texto, ativo)
VALUES
(1, 'Contactos', 'Contactos', 'Estamos ao seu dispor na sede, na Atalaia, e na dependência do Alto Estanqueiro.',
 'Junta de Freguesia de Atalaia e Alto Estanqueiro-Jardia',
 'Sede: Av. 28 de Setembro, n.º 56, 2870-701 Atalaia · Tel. 212 320 480 · Tlm. 910 697 305 / 961 826 278 · geral@juf.aaej.pt\nDependência: Rua dos Russos – Quinta das Tílias, 2870-624 Alto Estanqueiro-Jardia · Tel. 212 301 076',
 'Horário de atendimento',
 'Segunda a sexta-feira, das 9h00 às 12h30 e das 14h00 às 17h30. Posto CTT: segunda a sexta-feira, das 9h00 às 12h30.', 1);

UPDATE homepage_config SET
    hero_titulo       = 'Atalaia e Alto Estanqueiro-Jardia',
    hero_subtitulo    = 'Informação, serviços, documentos e património da freguesia, no concelho do Montijo.',
    boasvindas_titulo = 'Bem-vindo a Atalaia e Alto Estanqueiro-Jardia',
    boasvindas_texto  = 'A União das Freguesias de Atalaia e Alto Estanqueiro-Jardia reúne o Santuário de Nossa Senhora da Atalaia, lugar de peregrinação desde o século XVI, e as localidades do Alto Estanqueiro e da Jardia, no concelho do Montijo.',
    galeria_kicker    = 'A freguesia em imagens',
    galeria_titulo    = 'Atalaia e Alto Estanqueiro-Jardia em imagens',
    presidente_titulo = 'Mensagem do Presidente',
    presidente_nome   = 'Pedro Miguel Guerreiro da Franca Araújo',
    presidente_cargo  = 'Presidente da Junta de Freguesia',
    presidente_foto   = 'pedro_araujo.jpg',
    presidente_mensagem = 'É com um profundo e enorme sentido de responsabilidade que cumprimento e saúdo todos os fregueses e a comunidade em geral.\n\nComo Presidente da União de Freguesias da Atalaia, Alto do Estanqueiro e Jardia, é para mim uma elevada honra assumir este compromisso com a causa pública, dedicando-me diariamente ao serviço de todos.\n\nIniciamos um novo ciclo, de novembro de 2025 a 2029, um ciclo marcado por proximidade, transparência e resultados concretos.\n\nA nossa Junta existe para servir, e esse é o eixo central de todo o trabalho que assumo com o restante executivo.',
    mostrar_mensagem_presidente = 1;

UPDATE recursos_humanos_config SET
    intro_texto = REPLACE(intro_texto, 'Junta de Freguesia de Caia, São Pedro e Alcáçova', 'Junta de Freguesia de Atalaia e Alto Estanqueiro-Jardia');

UPDATE faqs SET
    pergunta = REPLACE(pergunta, 'Caia, São Pedro e Alcáçova', 'Atalaia e Alto Estanqueiro-Jardia'),
    resposta = REPLACE(resposta, 'Caia, São Pedro e Alcáçova', 'Atalaia e Alto Estanqueiro-Jardia');

-- ------------------------------------------------------------
-- 4. Executivo e Assembleia (mandato 2025–2029)
-- ------------------------------------------------------------
-- Fonte: juf.aaej.pt › Freguesia › Órgãos (nomes curtos, partidos,
-- pelouros e fotos) + mun-montijo.pt (nomes completos do executivo).
INSERT INTO executivo_membros (nome, cargo, pelouros, foto, ordem, ativo) VALUES
('Pedro Miguel Guerreiro da Franca Araújo', 'Presidente', 'Gestão Financeira\nRecursos Humanos\nPatrimónio\nExpediente\nDesporto e Associativismo', 'pedro_araujo.jpg', 1, 1),
('Vanessa Sofia Leite de Castro', 'Secretária', 'Ação Social\nCultura\nEducação e Ensino\nCertificação de Atas\nSubscrição de Atestados', 'vanessa_castro.jpg', 2, 1),
('Augusto Marques Cardoso', 'Tesoureiro', 'Higiene e Limpeza Urbana\nMercados e Feiras\nObras\nArrecadação de Receitas\nSubscrição de Despesas autorizadas', 'augusto_cardoso.jpg', 3, 1);

INSERT INTO assembleia_composicao (nome, cargo, grupo, partido, foto, ordem, destaque, ativo) VALUES
('David Costa', 'Presidente da Assembleia', 'Mesa da Assembleia', 'CHEGA', 'david_costa.jpg', 1, 1, 1),
('Luís Pinho', '1.º Secretário', 'Mesa da Assembleia', 'CHEGA', 'luis_pinho.jpg', 2, 0, 1),
('Carla Mestre', '2.º Secretário', 'Mesa da Assembleia', 'CHEGA', 'carla_mestre.jpg', 3, 0, 1),
('Bruno Silva', 'Vogal', 'Vogais', 'PS', 'bruno_silva.jpg', 4, 0, 1),
('Adelino Silva', 'Vogal', 'Vogais', 'PS', 'adelino_silva.jpg', 5, 0, 1),
('Inga Oliveira', 'Vogal', 'Vogais', 'MVC', NULL, 6, 0, 1),
('Dora Horta', 'Vogal', 'Vogais', 'MVC', 'dora_horta.jpg', 7, 0, 1),
('Patrícia Machado', 'Vogal', 'Vogais', 'PSD', 'patricia_machado.jpg', 8, 0, 1),
('Rui Joaquim', 'Vogal', 'Vogais', 'IL', 'rui_joaquim.jpg', 9, 0, 1);

-- ------------------------------------------------------------
-- 5. Contactos úteis
-- ------------------------------------------------------------
-- Escolas: nós do OpenStreetMap dentro do limite da freguesia.
INSERT INTO contactos_uteis (nome, categoria, telefone, email, morada, horario, ordem, destaque, ativo) VALUES
('Junta de Freguesia — Sede', 'Junta de Freguesia', '212 320 480 / 910 697 305 / 961 826 278', 'geral@juf.aaej.pt', 'Av. 28 de Setembro, n.º 56, 2870-701 Atalaia', 'Segunda a sexta, 9h00–12h30 e 14h00–17h30', 1, 1, 1),
('Junta de Freguesia — Dependência', 'Junta de Freguesia', '212 301 076', NULL, 'Rua dos Russos – Quinta das Tílias, 2870-624 Alto Estanqueiro-Jardia', NULL, 2, 1, 1),
('Posto CTT (na sede da Junta)', 'Serviços', '212 320 480', NULL, 'Av. 28 de Setembro, n.º 56, 2870-701 Atalaia', 'Segunda a sexta, 9h00–12h30', 3, 0, 1),
('Escola Básica de Novos Trilhos', 'Educação', '212 312 623', NULL, 'Rua 28 de Setembro, Atalaia', NULL, 4, 0, 1),
('Escola Básica do Alto Estanqueiro', 'Educação', '212 318 521', NULL, 'Rua Gomes Martins de Lemos, Alto Estanqueiro', NULL, 5, 0, 1),
('Escola Básica de Jardia', 'Educação', '212 361 576', NULL, 'Jardia', NULL, 6, 0, 1),
('Jardim de Infância de Alto Estanqueiro-Jardia', 'Educação', NULL, NULL, 'Alto Estanqueiro', NULL, 7, 0, 1);

-- ------------------------------------------------------------
-- 6. Pontos de interesse
-- ------------------------------------------------------------
-- Coordenadas: nós do OpenStreetMap, todos DENTRO do freguesia.geojson
-- e confirmados pelo Nominatim (reverse). Textos: juf.aaej.pt (História
-- da Freguesia) e mun-montijo.pt (Museu Agrícola).
-- Fotos: Wikimedia Commons (CC BY-SA, autores nos metadados de cada
-- ficheiro; ver CLAUDE.md) e Câmara Municipal do Montijo (Museu Agrícola).
INSERT INTO pontos_interesse (nome, descricao, imagem, localizacao, latitude, longitude) VALUES
('Igreja de Nossa Senhora da Atalaia', 'Igreja-santuário edificada no século XVI e reedificada no século XVIII, classificada como Imóvel de Interesse Público em 2009, com os três cruzeiros. Antecedida por um alpendre de três arcos, tem uma só nave, púlpito de mármore da Arrábida e altar-mor com retábulo setecentista de madeira do Brasil. As paredes estão forradas de azulejos azuis e brancos do século XVIII com cenas da vida da Virgem. Numa dependência anexa guardam-se os ex-votos populares; nas traseiras, a Fonte da Senhora, onde, segundo a lenda, terá aparecido a imagem de Nossa Senhora da Atalaia.', 'igreja_atalaia.jpg', 'Atalaia', '38.7075836', '-8.9220027'),
('Cruzeiro Mor', 'Com as imagens esculpidas de Jesus Cristo e de Nossa Senhora da Piedade cobertas por uma cúpula sustida por quatro colunas, foi mandado construir pela Confraria de Lisboa em 1551 e reconstruído em 2001. As imagens continuam decapitadas, marca do anticlericalismo da República. Imóvel de Interesse Público (2009).', 'cruzeiro_mor.jpg', 'Atalaia', '38.7070004', '-8.9245699'),
('Cruzeiro de Alcochete', 'Cruzeiro de pedra lioz, à direita da igreja, junto à linha limite do concelho, mandado construir por uma família de Alcochete em 1669. Imóvel de Interesse Público (2009).', NULL, 'Atalaia', '38.7082449', '-8.9224782'),
('Cruzeiro das Esmolas', 'Também chamado Cruzeiro da Estrada, junto à Estrada Nacional n.º 4, a cerca de 150 metros da igreja. É o mais simples dos três; desconhece-se o ano da sua construção e foi reconstruído no início deste século. Imóvel de Interesse Público (2009).', NULL, 'Atalaia', '38.7063537', '-8.9221678'),
('Museu Agrícola da Atalaia', 'Desde 1997, a Quinta Nova da Atalaia, junto à escadaria do Santuário, é o núcleo museológico do concelho dedicado à temática agrícola. Requalificado em 2009, preserva o lagar de azeite (com moinho de duas galgas e prensas), a adega e as práticas agrícolas tradicionais ligadas ao azeite, ao vinho e à fruta. Entrada gratuita.', 'museu_agricola_atalaia.jpg', 'Rua da Atalaia, Atalaia', '38.7082512', '-8.9231138'),
('Monumento de Homenagem à Floricultura', 'Monumento de homenagem à floricultura, no Alto Estanqueiro.', NULL, 'EN 5, Alto Estanqueiro', '38.6864969', '-8.944169'),
('Monumento a Álvaro Tavares Mora', 'Busto em bronze e calcário moleano de Laureano Ribatua, inaugurado a 24 de agosto de 2001. É uma homenagem da população da Atalaia a Álvaro Tavares Mora, autarca da Câmara Municipal do Montijo e benemérito que, em 1947, mandou construir dois chafarizes, resolvendo o problema do abastecimento de água à população.', 'monumento_alvaro_tavares_mora.jpg', 'Praça dos Operários, Atalaia', '38.7069401', '-8.9220795'),
('Cruzeiro de Granito', 'Cruzeiro em granito mandado colocar pela Junta de Freguesia em 2005, na rotunda da Atalaia, em homenagem aos círios que ainda hoje fazem romagem à Atalaia.', 'cruzeiro_granito.jpg', 'Estrada Nacional 4, Atalaia', NULL, NULL);

-- Álbuns da galeria: um por ponto com foto, com capa preenchida.
INSERT INTO galeria_albuns (nome, descricao, capa, origem, origem_id, ordem, ativo)
SELECT nome, NULL, imagem, 'ponto', id, id, 1 FROM pontos_interesse WHERE imagem IS NOT NULL;
INSERT INTO galeria_imagens (album_id, ficheiro, titulo, ordem, ativo)
SELECT a.id, a.capa, a.nome, 1, 1 FROM galeria_albuns a;
-- Fotos adicionais (Câmara Municipal do Montijo — Rota da Atalaia e
-- Arte Pública) nos álbuns dos pontos.
INSERT INTO galeria_imagens (album_id, ficheiro, titulo, ordem, ativo)
SELECT a.id, x.f, x.t, x.o, 1 FROM galeria_albuns a
JOIN (SELECT 'Igreja de Nossa Senhora da Atalaia' n, 'igreja_atalaia_interior.jpg' f, 'Interior e retábulo do altar-mor' t, 2 o
      UNION ALL SELECT 'Igreja de Nossa Senhora da Atalaia', 'igreja_atalaia_escadaria.jpg', 'A escadaria do Santuário', 3
      UNION ALL SELECT 'Cruzeiro Mor', 'cruzeiro_mor_2.jpg', 'Cruzeiro Mor', 2
      UNION ALL SELECT 'Cruzeiro Mor', 'cruzeiro_mor_noite.jpg', 'Cruzeiro Mor à noite', 3
      UNION ALL SELECT 'Museu Agrícola da Atalaia', 'museu_agricola_lagar.jpg', 'Mós do lagar de azeite', 2
      UNION ALL SELECT 'Museu Agrícola da Atalaia', 'museu_agricola_quinta_nova.jpg', 'Quinta Nova da Atalaia', 3
      UNION ALL SELECT 'Monumento a Álvaro Tavares Mora', 'monumento_alvaro_tavares_mora_2.jpg', 'Busto de Álvaro Tavares Mora', 2) x
  ON CONVERT(x.n USING utf8mb4) COLLATE utf8mb4_bin = CONVERT(a.nome USING utf8mb4) COLLATE utf8mb4_bin;

INSERT INTO slides_homepage (titulo, subtitulo, imagem, link_destino, ativo) VALUES
('Atalaia e Alto Estanqueiro-Jardia', 'Um santuário de peregrinação desde o século XVI, no concelho do Montijo.', 'santuario_atalaia.jpg', 'freguesia.php', 1),
('Cruzeiro Mor', 'Imóvel de Interesse Público, mandado construir em 1551.', 'cruzeiro_mor.jpg', 'pontos.php', 1),
('Museu Agrícola da Atalaia', 'O lagar, a adega e as tradições agrícolas do concelho.', 'museu_agricola_atalaia.jpg', 'mapa.php', 1);


-- ------------------------------------------------------------
-- 7. Associações
-- ------------------------------------------------------------
-- Lista oficial da Junta (Raízes e Identidade › Associativismo, 6
-- entidades). Moradas verificadas: todas em Atalaia ou Alto
-- Estanqueiro-Jardia (códigos postais 2870-6xx/7xx da freguesia).
-- Coordenadas: Sociedade Recreativa Atalaiense = nó OSM (bar da sede);
-- as outras 4 = ponto da RUA no Nominatim (aproximadas); todas DENTRO
-- do freguesia.geojson. Mansos e Vadios sem morada publicada.
INSERT INTO associacoes (nome, descricao, email, telefone, imagem, morada, facebook, website, latitude, longitude) VALUES
('Sociedade Recreativa Atalaiense', 'Associação desportiva, cultural e recreativa fundada a 11 de outubro de 1946.', NULL, NULL, 'sociedade_recreativa_atalaiense.jpg', 'Avenida 28 de Setembro, 2870-701 Atalaia', 'https://www.facebook.com/sociedade.atalaiense', 'https://atalaiense.pt/', '38.7065383', '-8.9213201'),
('Rancho Folclórico Juventude Atalaiense', 'Associação etnográfica, presença habitual nas Festas em honra de Nossa Senhora da Atalaia.', NULL, NULL, NULL, 'Rua do Bairro Novo, Atalaia', NULL, NULL, '38.7038851', '-8.9255232'),
('Águias Negras Futebol Clube', 'Clube fundado a 1 de março de 1964, no Alto Estanqueiro, com um papel importante na dinamização desportiva, social e cultural da freguesia.', NULL, '212 301 826', 'noticia_aguias_negras_62.jpg', 'Estrada da Charnequinha, 2870-604 Alto Estanqueiro-Jardia', NULL, NULL, '38.6779956', '-8.9237494'),
('União Futebol Clube Jardiense', 'Clube de futebol da Jardia, fundado a 1 de maio de 1963.', 'uniaofcjardiense@gmail.com', '917 752 975', 'ufc_jardiense.jpg', 'Rua União Clube Jardiense, 2870-684 Alto Estanqueiro-Jardia', 'https://www.facebook.com/formacaojardia/', NULL, '38.6655363', '-8.9256051'),
('Academia Desportiva Infantil e Juvenil Bairro Miranda', 'Associação desportiva, recreativa e cultural fundada a 31 de março de 2003, dedicada sobretudo ao futsal jovem. Recebeu a Bandeira da Ética do IPDJ em 2020.', NULL, NULL, 'academia_bairro_miranda.jpg', 'Rua das Águias, 85 – Bairro Miranda, 2870-682 Alto Estanqueiro-Jardia', 'https://www.facebook.com/academia.bairro.miranda/', NULL, '38.6721345', '-8.9258328'),
('Associação Mansos e Vadios', 'Tertúlia e charanga da Atalaia, organizadora da Caminhada Solidária da Atalaia, integrada nas comemorações do 25 de Abril.', NULL, NULL, NULL, 'Atalaia', NULL, NULL, NULL, NULL),
('Centro Social e Paroquial de Nossa Senhora da Atalaia', 'Instituição Particular de Solidariedade Social que gere creche, centro de dia e serviço de apoio domiciliário. Atendimento das 9h30 às 12h30 e das 14h30 às 18h45.', 'geral.csatalaia@gmail.com', '212 317 534 / 915 943 757', NULL, 'Escadaria do Adro da Igreja, 2870-711 Atalaia', NULL, 'https://www.cspatalaia.com/', NULL, NULL),
('Cáritas Paroquial de Nossa Senhora da Atalaia', 'Apoio social às famílias da freguesia, ligada à paróquia de Nossa Senhora da Atalaia.', 'caritas.atalaia@gmail.com', NULL, NULL, 'Atalaia', NULL, NULL, NULL, NULL);


-- ------------------------------------------------------------
-- 7b. Comércio local
-- ------------------------------------------------------------
-- O site oficial não tem lista de comércio. Fonte: nós do OpenStreetMap
-- DENTRO do freguesia.geojson, cada um confirmado pelo Nominatim
-- (reverse → "Atalaia" ou "Atalaia e Alto Estanqueiro-Jardia").
-- O Ninho é citado numa notícia oficial (apoio à 3.ª Caminhada Solidária).
-- Restantes restaurantes: pesquisa web (moradas com código postal da
-- freguesia) + rua geocodificada no Nominatim e testada no polígono.
-- Coordenadas por rua (aproximadas): O Carlos, O Tacho d'Mãe, Sinfonia
-- dos Sabores, O Pardal. Sem coordenadas: Sabores do Mar, Apeadeiro Café.
-- Imagens: logótipos/fotos das páginas de Facebook (O Tacho d'Mãe,
-- Adega do Mocho, O Carlos) e foto de um prato do site d'O Ninho.
INSERT INTO comercio_local (nome, tipo, telefone, email, morada, website, facebook, outros_contactos, latitude, longitude, imagem) VALUES
('O Ninho', 'Restaurante', '212 318 988', NULL, 'Avenida Dom Manuel I, 2870-736 Atalaia', 'https://restauranteoninho.net/', NULL, 'Grelhados no carvão, peixe e cozinha tradicional portuguesa.', '38.7062978', '-8.9230556', 'o_ninho.jpg'),
('Adega do Mocho', 'Restaurante', '212 316 312 / 912 217 453', NULL, 'EN 4, n.º 41, Atalaia', NULL, 'https://www.facebook.com/pages/Adega-Mocho/175906119213164', 'Cozinha simples e rústica, com destaque para a carne de porco preto.', '38.7066864', '-8.9287066', 'adega_do_mocho.jpg'),
('Restaurante Churrasqueira O Carlos', 'Restaurante', '212 316 760', 'restaurantecarlos52@gmail.com', 'Rua Círio de Aldegalega, 210, 2870-724 Atalaia', NULL, 'https://www.facebook.com/Restaurante-Churrasqueira-O-Carlos-639343232765601/', 'Grelhados e cozinha tradicional.', '38.7095185', '-8.9263794', 'o_carlos.jpg'),
('O Tacho d''Mãe', 'Restaurante', NULL, NULL, 'Rua da Figueira, 68, 2870-738 Atalaia', NULL, 'https://www.facebook.com/tachodamae', 'Cozinha tradicional alentejana.', '38.7003828', '-8.9297564', 'o_tacho_d_mae.jpg'),
('A Rotunda', 'Restaurante', '910 532 529', NULL, 'Rua das Forças Armadas, 2870-712 Atalaia', NULL, NULL, NULL, '38.7064795', '-8.9276435', NULL),
('Sinfonia dos Sabores', 'Restaurante / Marisqueira', NULL, NULL, 'Rua das Forças Armadas, 2870-712 Atalaia', NULL, 'https://www.facebook.com/p/Sinfonia-dos-Sabores-Restaurante-Marisqueira-61581520914922/', 'Marisqueira e grelhados.', '38.7041687', '-8.9276785', NULL),
('O Típico', 'Restaurante', '211 586 265 / 914 602 806', NULL, 'EN 5, 2870-621 Alto Estanqueiro', NULL, NULL, NULL, '38.6812278', '-8.928787', NULL),
('Marisqueira Sabores do Mar', 'Restaurante / Marisqueira', NULL, NULL, 'Rua 1.º de Maio, 2870-626 Jardia', NULL, 'https://www.facebook.com/p/Restaurante-Marisqueira-Sabores-do-Mar-100067801087481/', 'Antigo «Mercado do Peixe».', NULL, NULL, NULL),
('O Pardal', 'Restaurante', NULL, NULL, 'Rua dos Tractores, 506 – Parque Industrial da Jardia', NULL, 'https://www.facebook.com/opardal.montijo/', 'Almoços de segunda a sexta-feira.', '38.6720755', '-8.9367860', NULL),
('Apeadeiro Café', 'Café', NULL, NULL, 'Rua do Operário, 10, 2870-609 Alto Estanqueiro-Jardia', NULL, NULL, NULL, NULL, NULL, NULL),
('Padaria da Atalaia', 'Padaria', '212 474 228', NULL, 'Rua do Mercado, 31, 2870-751 Atalaia', NULL, NULL, NULL, '38.7052472', '-8.9226277', NULL),
('Farmácia Cravidão', 'Farmácia', NULL, NULL, 'Avenida Dom Manuel I, 2870-736 Atalaia', NULL, NULL, NULL, '38.7064108', '-8.9228921', NULL),
('Provari', 'Comércio agrícola e ferragens', '212 318 904', NULL, 'Rua 25 de Abril, 25, 2870-709 Atalaia', NULL, NULL, 'Comércio agrícola, agropecuária e ferragens.', '38.7061291', '-8.9222211', NULL),
('Rolizoo', 'Loja de animais', '212 384 731', NULL, 'EN 252, gaveto com a Rua Gil Fernandes, 2, Alto Estanqueiro', 'https://www.rolizoo.com/', NULL, NULL, '38.6804598', '-8.9387858', NULL),
('Stand Ricarauto', 'Comércio automóvel', '964 604 547', NULL, 'EN 252, 2870-660 Alto Estanqueiro', 'https://www.standricarauto.pt/', NULL, NULL, '38.6810634', '-8.9393324', NULL),
('RP Auto', 'Oficina automóvel', NULL, NULL, 'EN 4, 2870-700 Atalaia', NULL, NULL, NULL, '38.7066243', '-8.9285662', NULL);


-- ------------------------------------------------------------
-- 8. Notícias (site oficial, 2026; texto integral, fotos originais)
-- ------------------------------------------------------------
INSERT INTO noticias (titulo, descricao, imagem, data, categoria) VALUES
('Montijo, 41 anos de cidade', 'No dia 14 de agosto de 1985, o Montijo foi elevado à categoria de cidade.\n\nHoje, 41 anos depois, celebramos não apenas uma data, mas uma história construída por gerações de Montijenses e por todas as freguesias do concelho, que ao longo dos anos contribuíram para o seu desenvolvimento e afirmação.\n\nO crescimento do Montijo fez-se também a partir das suas freguesias, através do trabalho das suas populações, da atividade económica, da agricultura, do comércio, das associações, da cultura, das tradições e da vida comunitária.\n\nA União das Freguesias de Atalaia e Alto Estanqueiro-Jardia associa-se a esta celebração, deixando uma palavra de reconhecimento a todos aqueles que, ao longo dos anos, contribuíram e continuam a contribuir para o crescimento e desenvolvimento do nosso concelho.\n\nParabéns, Montijo.', 'noticia_montijo_41_anos.jpg', '2026-08-14 10:00:00', 'Freguesia'),
('Entrega do donativo angariado à Cáritas Diocesana', 'No dia 22 de maio foi entregue à Cáritas Diocesana o donativo angariado na 3.ª Caminhada Solidária da Atalaia, iniciativa promovida pela Associação Mansos e Vadios e integrada nas comemorações do 25 de Abril, com o apoio da Junta da União das Freguesias de Atalaia e Alto Estanqueiro-Jardia.\n\nGraças à participação de todos, foi possível angariar 306 €, valor que reverteu integralmente para esta instituição, contribuindo para apoiar quem mais precisa na nossa comunidade.\n\nA todos os que participaram e contribuíram, o nosso sincero obrigado. Juntos, continuamos a construir uma união de freguesias mais solidária, unida e próxima da comunidade.', 'noticia_caritas.jpg', '2026-05-22 10:00:00', 'Social'),
('3.ª Caminhada Solidária da Atalaia', 'A Junta da União das Freguesias de Atalaia e Alto Estanqueiro-Jardia marcou presença na 3.ª Caminhada Solidária da Atalaia.\n\nEsta iniciativa, promovida pela Associação Mansos e Vadios e integrada nas comemorações do 25 de Abril, voltou a reunir fregueses, munícipes, famílias e visitantes num momento de convívio, partilha e solidariedade. Com um percurso acessível, a caminhada teve como principal objetivo apoiar uma instituição de solidariedade do concelho.\n\nO valor total angariado através das inscrições foi de 306 €, doado à Cáritas Diocesana da Atalaia. A Junta felicita a Associação Mansos e Vadios pela excelente organização desta 3.ª edição e todos os participantes, e agradece ao Restaurante «O Ninho» o apoio prestado a esta causa.', 'noticia_caminhada_2026.jpg', '2026-04-25 10:00:00', 'Eventos'),
('Mensagem de Páscoa', 'Estimados fregueses da Atalaia, Alto Estanqueiro e Jardia.\n\nNesta época de celebração e partilha, dirijo-me a cada um de vós para desejar uma Santa Páscoa, repleta de harmonia e paz. A Páscoa é, acima de tudo, um tempo de renovação e de esperança — valores que guiam o nosso trabalho diário na União de Freguesias.\n\nQue este período seja vivido com serenidade junto das vossas famílias e que o espírito de união que carateriza a nossa terra se fortaleça ainda mais.\n\nUm abraço fraterno a todos.\nPedro Araújo — O Presidente', 'noticia_pascoa_2026.jpg', '2026-04-03 15:00:00', 'Freguesia'),
('Apresentação do livro «Um Pouco de Tudo», de José Martinho', 'No passado dia 28 de março, a dependência da Junta no Alto Estanqueiro encheu-se de poesia, emoção e partilha com a apresentação do livro «Um Pouco de Tudo», de José Martinho, um autor da nossa terra.\n\nCom um percurso marcante no futebol — como jogador, treinador e árbitro ao mais alto nível —, José Martinho traz agora para a escrita o mesmo rigor, sensibilidade e olhar atento sobre a vida, dando continuidade a obras como «Histórias Rimadas» e «Amor e Humor em Poesia».\n\nA sessão, conduzida por Inga Oliveira, locutora e também membro da assembleia de freguesia, contou com momentos verdadeiramente especiais: enquanto a poesia era declamada, o Sr. Sérgio Pastor acompanhava ao acordeão. No final, houve ainda uma sessão de autógrafos e um momento de convívio com o autor.', 'noticia_livro_jose_martinho.jpg', '2026-03-28 18:00:00', 'Cultura'),
('62.º aniversário do Águias Negras Futebol Clube', 'No dia 1 de março de 2026, o Senhor Presidente e o Senhor Tesoureiro da Junta estiveram presentes no almoço comemorativo do 62.º aniversário do Águias Negras Futebol Clube, que teve lugar na sua sede, no Alto Estanqueiro.\n\nA iniciativa reuniu sócios, familiares e amigos desta coletividade, num momento de convívio e celebração, assinalado com um almoço tradicional de feijoada caramela.\n\nA Junta associa-se a esta data, felicitando o Águias Negras Futebol Clube pelos seus 62 anos de existência e destacando o seu importante papel na dinamização desportiva, social e cultural da freguesia.', 'noticia_aguias_negras_62.jpg', '2026-03-01 13:00:00', 'Associativismo'),
('Ajuda solidária a Alcácer do Sal', 'As recentes cheias em Alcácer do Sal afetaram várias famílias, que neste momento precisam do apoio de todos. A União das Freguesias de Atalaia e Alto Estanqueiro-Jardia está a promover uma angariação de bens alimentares e produtos de higiene, que serão entregues diretamente no local com a carrinha da Junta.\n\nO que pode doar: alimentos não perecíveis (arroz, massa, enlatados, leite, óleo, bolachas) e produtos de higiene pessoal (gel de banho, champô, pasta e escova de dentes, fraldas, pensos higiénicos, papel higiénico).\n\nPontos de recolha: Sede (Av. 28 de Setembro, n.º 56, Atalaia) e Dependência (Rua dos Russos – Quinta das Tílias, Alto Estanqueiro-Jardia), das 9h00 às 12h30 e das 14h00 às 17h30, até terça-feira, dia 10 de fevereiro.', 'noticia_alcacer_do_sal.jpg', '2026-02-05 10:00:00', 'Social');


-- ------------------------------------------------------------
-- 10. Imagens de fundo dos separadores (uploads/separadores/)
-- ------------------------------------------------------------
INSERT INTO separadores_fundo (chave, imagem) VALUES
('freguesia', 'sep-igreja.jpg'),
('historia', 'sep-cruzeiro.jpg'),
('heraldica', 'sep-cruzeiro.jpg'),
('pontos', 'sep-museu.jpg'),
('galeria', 'sep-museu.jpg'),
('noticias', 'sep-igreja.jpg'),
('eventos', 'sep-igreja.jpg'),
('contactos', 'sep-igreja.jpg'),
('executivo', 'sep-cruzeiro.jpg'),
('associacoes', 'sep-museu.jpg');
