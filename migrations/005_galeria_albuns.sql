-- =====================================================================
-- 005_galeria_albuns — Galeria organizada por ÁLBUNS
-- =====================================================================
-- CORE: idêntico em todos os sites.
--
-- Antes: a galeria era uma lista plana (homepage_galeria) e os pontos de
-- interesse só tinham UMA imagem.
--
-- Agora:
--   * galeria_albuns  — um álbum agrupa imagens. Pode ser criado à mão
--     (origem='manual') ou ser criado/associado AUTOMATICAMENTE a partir de um
--     evento (origem='evento') ou de um ponto de interesse (origem='ponto').
--   * galeria_imagens — as imagens, sempre dentro de um álbum.
--
-- Decisão importante: as fotos de um ponto/evento NÃO são duplicadas. Elas
-- vivem aqui, no álbum da sua origem, e a página do ponto/evento lê-as daqui.
-- Há uma única fonte de verdade — não há cópias para manter sincronizadas.
--
-- A chave única (origem, origem_id) garante que cada evento/ponto tem no
-- máximo UM álbum: ao gravar, ou cria o álbum, ou associa ao já existente.
-- =====================================================================

-- NOTA sobre collation: NÃO declarar "DEFAULT CHARSET=utf8mb4" aqui. Isso faria a
-- tabela nascer com utf8mb4_0900_ai_ci (a collation por omissão do charset no MySQL 8),
-- enquanto as tabelas antigas destes sites usam utf8mb4_general_ci (a default da BD).
-- Misturar as duas rebenta qualquer comparação entre colunas das duas tabelas
-- ("Illegal mix of collations"). Sem a cláusula, a tabela herda a collation da BD e
-- fica consistente com as antigas.

CREATE TABLE IF NOT EXISTS galeria_albuns (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(255) NOT NULL,
    descricao  TEXT DEFAULT NULL,
    capa       VARCHAR(255) DEFAULT NULL,
    origem     ENUM('manual','evento','ponto') NOT NULL DEFAULT 'manual',
    origem_id  INT DEFAULT NULL,
    ordem      INT NOT NULL DEFAULT 0,
    ativo      TINYINT(1) NOT NULL DEFAULT 1,
    criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_origem (origem, origem_id)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS galeria_imagens (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    album_id  INT NOT NULL,
    ficheiro  VARCHAR(255) NOT NULL,
    titulo    VARCHAR(255) DEFAULT NULL,
    ordem     INT NOT NULL DEFAULT 0,
    ativo     TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    KEY idx_album (album_id),
    CONSTRAINT fk_galeria_imagens_album
        FOREIGN KEY (album_id) REFERENCES galeria_albuns(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Migrar a galeria antiga (lista plana) para um álbum manual, sem perder nada.
-- A homepage_galeria fica intacta: as páginas que ainda a usam continuam a
-- funcionar até serem passadas para os álbuns.
INSERT INTO galeria_albuns (nome, descricao, origem, origem_id, ordem, ativo)
SELECT 'Galeria da Freguesia', 'Imagens que já existiam na galeria antes dos álbuns.', 'manual', NULL, 0, 1
FROM DUAL
WHERE EXISTS (SELECT 1 FROM homepage_galeria)
  AND NOT EXISTS (SELECT 1 FROM galeria_albuns WHERE nome = 'Galeria da Freguesia' AND origem = 'manual');

-- Só copia se o álbum ainda estiver vazio (idempotente, sem comparar strings entre
-- tabelas — evita de vez o problema de collations).
INSERT INTO galeria_imagens (album_id, ficheiro, titulo, ordem, ativo)
SELECT a.id, g.imagem, g.titulo, g.ordem, g.ativo
FROM homepage_galeria g
CROSS JOIN galeria_albuns a
WHERE a.nome = 'Galeria da Freguesia' AND a.origem = 'manual'
  AND NOT EXISTS (SELECT 1 FROM galeria_imagens gi WHERE gi.album_id = a.id);
