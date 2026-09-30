-- =====================================================================
-- 013_mostrar_galeria — Ligar a secção da Galeria de Fotos na página inicial
-- =====================================================================
-- CORE: idêntico em todos os sites. Idempotente.
--
-- A secção da Galeria de Fotos na página inicial só aparece se DUAS condições se
-- verificarem (ver index.php):
--
--     if (!empty($home['mostrar_galeria']) && !empty($galeriaHome))
--
--   1. o interruptor mostrar_galeria estar ligado;
--   2. existirem álbuns com fotografias.
--
-- A segunda foi resolvida pela migração 012 (que cria álbuns para os pontos e
-- eventos que já existiam). Mas a primeira ficou por resolver: o backoffice
-- (admin/homepage.php) nunca expôs este interruptor — controlava os outros oito,
-- mas não este. Resultado: nos sites onde estava a 0, a galeria não aparecia e não
-- havia maneira nenhuma de a ligar pelo backoffice.
--
-- Esta migração liga-o. A partir daqui o interruptor passa também a estar visível
-- em Página Inicial → Textos Homepage → Secções visíveis, e quem quiser esconder a
-- galeria pode desligá-lo por lá.
-- =====================================================================

UPDATE homepage_config
SET mostrar_galeria = 1
WHERE mostrar_galeria IS NULL
   OR mostrar_galeria = 0;
