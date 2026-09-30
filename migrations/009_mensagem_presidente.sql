-- =====================================================================
-- 009_mensagem_presidente — Ligar a Mensagem do Presidente ao backoffice
-- =====================================================================
-- CORE: idêntico em todos os sites.
--
-- Os campos presidente_titulo / presidente_mensagem / mostrar_mensagem_presidente
-- JÁ EXISTIAM na tabela homepage_config, mas estavam ÓRFÃOS: nem o backoffice os
-- mostrava, nem o index.php os usava. A "Mensagem do Presidente" da página inicial
-- vinha, na verdade, do campo BIOGRAFIA do presidente (secção Executivo) — não
-- havia sítio nenhum onde a alterar com esse nome, e mexer na biografia mexia
-- também na homepage.
--
-- Agora o index.php usa, por esta ordem:
--   1. homepage_config.presidente_mensagem   (o campo próprio, editável em Página Inicial)
--   2. executivo_membros.biografia           (o que era usado até aqui)
--   3. um texto genérico
--
-- ESTA MIGRAÇÃO garante que nada muda de aspeto:
--
-- O 'mostrar_mensagem_presidente' estava a ser IGNORADO pelo index.php (a secção
-- aparecia sempre que houvesse presidente). Os valores que lá estão são, por isso,
-- lixo — há sites com 0. Se passássemos a respeitar o campo sem mais nada, esses
-- sites perdiam a secção da homepage de um dia para o outro.
--
-- Põe-se tudo a 1 (= continuar a mostrar, como até aqui). A partir daqui o botão
-- passa a funcionar a sério e quem quiser esconder a secção desliga-o no backoffice.
-- =====================================================================

UPDATE homepage_config
SET mostrar_mensagem_presidente = 1
WHERE mostrar_mensagem_presidente IS NULL
   OR mostrar_mensagem_presidente = 0;
