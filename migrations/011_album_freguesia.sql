-- =====================================================================
-- 011_album_freguesia — As fotos da galeria antiga passam a um álbum
--                       com o nome da freguesia
-- =====================================================================
-- CORE: idêntico em todos os sites. Idempotente.
--
-- Passou a existir UMA só galeria no backoffice (Página Inicial → Galeria de
-- Fotos), organizada por álbuns. A secção "Galeria Homepage" antiga — uma lista
-- plana de imagens na tabela homepage_galeria — deixou de fazer sentido.
--
-- Esta migração passa essas fotografias para um álbum com o NOME DA FREGUESIA
-- ("Terrugem", "Rio de Moinhos", "Granho", ...), ao lado dos álbuns dos pontos de
-- interesse e dos eventos. O nome é derivado do nome_site (configuracoes_site),
-- por isso cada site apanha o seu sem ser preciso editar nada.
--
-- A migração 005 já tinha feito uma cópia semelhante, com o nome provisório
-- "Galeria da Freguesia"; aqui esse álbum é renomeado.
--
-- ONDE ESTÃO OS FICHEIROS: as imagens vindas da galeria antiga continuam gravadas
-- em assets/img/, e não em assets/img/galeria/ (a pasta das imagens novas). NÃO são
-- movidas de propósito — mover ficheiros em produção é arriscado e desnecessário:
-- o imagemGaleriaUrl(), em includes/galeria.php, procura nas duas pastas.
--
-- A tabela homepage_galeria NÃO é apagada. Fica intacta, como salvaguarda.
-- =====================================================================

-- Nome da freguesia, a partir do nome do site.
SET @freguesia := (
    SELECT TRIM(
        REPLACE(REPLACE(REPLACE(REPLACE(nome_site,
            'Junta de Freguesia de ', ''),
            'Junta de Freguesia do ', ''),
            'Junta de Freguesia da ', ''),
            'Junta de Freguesia ',    '')
    )
    FROM configuracoes_site
    LIMIT 1
);

-- Se o nome vier vazio (site sem nome configurado), usa um nome seguro.
SET @freguesia := IF(@freguesia IS NULL OR @freguesia = '', 'Freguesia', @freguesia);

-- NOTA sobre collations: a @freguesia herda a collation da configuracoes_site, que
-- em vários sites é utf8mb4_unicode_ci, enquanto a galeria_albuns usa a collation
-- por omissão da base de dados (utf8mb4_general_ci). Comparar as duas diretamente
-- rebenta com "Illegal mix of collations". Por isso as comparações abaixo forçam
-- utf8mb4_bin nos DOIS lados do '=' — assim funcionam seja qual for a collation
-- de cada site. (É a mesma armadilha que fez a migração 005 falhar à primeira.)

-- 1) O álbum criado pela migração 005 com o nome provisório passa a ter o nome da
--    freguesia. Aqui compara-se com um literal, que se adapta — não há problema.
UPDATE galeria_albuns
SET nome = @freguesia
WHERE origem = 'manual' AND nome = 'Galeria da Freguesia';

-- 2) Se ainda não existe (a 005 pode ter corrido com a tabela antiga vazia), cria-o —
--    mas só se houver mesmo fotografias para lá pôr.
INSERT INTO galeria_albuns (nome, descricao, origem, origem_id, ordem, ativo)
SELECT @freguesia,
       'Fotografias da freguesia.',
       'manual', NULL, 0, 1
FROM DUAL
WHERE EXISTS (SELECT 1 FROM homepage_galeria)
  AND NOT EXISTS (
        SELECT 1 FROM galeria_albuns
        WHERE origem = 'manual'
          AND CONVERT(nome USING utf8mb4) COLLATE utf8mb4_bin
            = CONVERT(@freguesia USING utf8mb4) COLLATE utf8mb4_bin
  );

-- 3) Copia as fotografias da galeria antiga.
--    Só corre se o álbum estiver vazio — assim é idempotente sem ter de comparar
--    nomes de ficheiro entre as duas tabelas.
INSERT INTO galeria_imagens (album_id, ficheiro, titulo, ordem, ativo)
SELECT a.id, g.imagem, g.titulo, g.ordem, g.ativo
FROM homepage_galeria g
CROSS JOIN galeria_albuns a
WHERE a.origem = 'manual'
  AND CONVERT(a.nome USING utf8mb4) COLLATE utf8mb4_bin
    = CONVERT(@freguesia USING utf8mb4) COLLATE utf8mb4_bin
  AND NOT EXISTS (SELECT 1 FROM galeria_imagens gi WHERE gi.album_id = a.id);

-- 4) Capa do álbum: a primeira imagem, se ainda não tiver nenhuma.
UPDATE galeria_albuns a
SET a.capa = (
    SELECT gi.ficheiro FROM galeria_imagens gi
    WHERE gi.album_id = a.id
    ORDER BY gi.ordem ASC, gi.id ASC
    LIMIT 1
)
WHERE a.origem = 'manual'
  AND CONVERT(a.nome USING utf8mb4) COLLATE utf8mb4_bin
    = CONVERT(@freguesia USING utf8mb4) COLLATE utf8mb4_bin
  AND (a.capa IS NULL OR a.capa = '');
