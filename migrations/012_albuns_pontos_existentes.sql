-- =====================================================================
-- 012_albuns_pontos_existentes — Álbum para os pontos que já existiam
-- =====================================================================
-- CORE: idêntico em todos os sites. Idempotente.
--
-- O álbum de um ponto de interesse é criado quando se enviam fotografias pelo
-- campo "Fotografias" do backoffice. Os pontos que já existiam ANTES desta
-- funcionalidade só têm a imagem principal (pontos_interesse.imagem) e, por isso,
-- não têm álbum nenhum — não aparecem na Galeria de Fotos nem na página inicial.
--
-- Esta migração cria, para cada um desses pontos, um álbum com o NOME DO PONTO e
-- coloca lá a imagem principal como primeira fotografia. A partir daí, acrescentar
-- mais fotos no backoffice junta-as a este mesmo álbum (o albumDaOrigem reaproveita-o).
--
-- ONDE ESTÃO OS FICHEIROS: a imagem principal está gravada em assets/img/, e não em
-- assets/img/galeria/ (a pasta das fotos novas). NÃO é copiada nem movida de
-- propósito — o imagemGaleriaUrl(), em includes/galeria.php, procura nas duas pastas.
-- Mexer em ficheiros de produção é arriscado e aqui é desnecessário.
--
-- A coluna pontos_interesse.imagem fica intacta: continua a ser a imagem de capa do
-- ponto no cartão e no mapa.
--
-- O mesmo se aplica aos EVENTOS, mais abaixo.
-- =====================================================================

-- ---------- PONTOS DE INTERESSE ----------

-- 1) Cria o álbum (nome = nome do ponto) para os pontos que têm imagem e ainda não
--    têm álbum. A chave única (origem, origem_id) impede duplicados.
INSERT INTO galeria_albuns (nome, descricao, origem, origem_id, ordem, ativo)
SELECT p.nome, NULL, 'ponto', p.id, 0, 1
FROM pontos_interesse p
WHERE p.imagem IS NOT NULL
  AND TRIM(p.imagem) <> ''
  AND NOT EXISTS (
        SELECT 1 FROM galeria_albuns a
        WHERE a.origem = 'ponto' AND a.origem_id = p.id
  );

-- 2) Põe a imagem principal como primeira fotografia do álbum — mas só nos álbuns
--    que ainda estão vazios (idempotente, e não mexe nos que já têm fotos).
INSERT INTO galeria_imagens (album_id, ficheiro, titulo, ordem, ativo)
SELECT a.id, p.imagem, p.nome, 1, 1
FROM galeria_albuns a
JOIN pontos_interesse p ON p.id = a.origem_id
WHERE a.origem = 'ponto'
  AND p.imagem IS NOT NULL
  AND TRIM(p.imagem) <> ''
  AND NOT EXISTS (
        SELECT 1 FROM galeria_imagens gi WHERE gi.album_id = a.id
  );

-- ---------- EVENTOS ----------

INSERT INTO galeria_albuns (nome, descricao, origem, origem_id, ordem, ativo)
SELECT e.titulo, NULL, 'evento', e.id, 0, 1
FROM eventos e
WHERE e.imagem IS NOT NULL
  AND TRIM(e.imagem) <> ''
  AND NOT EXISTS (
        SELECT 1 FROM galeria_albuns a
        WHERE a.origem = 'evento' AND a.origem_id = e.id
  );

INSERT INTO galeria_imagens (album_id, ficheiro, titulo, ordem, ativo)
SELECT a.id, e.imagem, e.titulo, 1, 1
FROM galeria_albuns a
JOIN eventos e ON e.id = a.origem_id
WHERE a.origem = 'evento'
  AND e.imagem IS NOT NULL
  AND TRIM(e.imagem) <> ''
  AND NOT EXISTS (
        SELECT 1 FROM galeria_imagens gi WHERE gi.album_id = a.id
  );

-- ---------- CAPAS ----------

-- Capa de cada álbum: a primeira imagem, para os que ainda não tenham.
UPDATE galeria_albuns a
SET a.capa = (
    SELECT gi.ficheiro FROM galeria_imagens gi
    WHERE gi.album_id = a.id
    ORDER BY gi.ordem ASC, gi.id ASC
    LIMIT 1
)
WHERE (a.capa IS NULL OR a.capa = '')
  AND EXISTS (SELECT 1 FROM galeria_imagens gi WHERE gi.album_id = a.id);
