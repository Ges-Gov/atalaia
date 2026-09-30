-- =====================================================================
-- 007_perfis — Perfis de permissões do backoffice
-- =====================================================================
-- CORE: idêntico em todos os sites.
--
-- Um PERFIL é um conjunto de permissões com nome (ex.: "Secretaria", "Assembleia")
-- que se associa a um utilizador do backoffice. Substitui a necessidade de criar
-- um `tipo` novo no ENUM sempre que se quer uma combinação diferente de acessos.
--
-- REGRA IMPORTANTE: só o utilizador OCULTO da GesGov (o que está em
-- $utilizadoresOcultos, em admin/utilizadores.php) pode criar perfis e associá-los
-- a utilizadores. Os administradores da junta não veem sequer o separador.
-- Ver isGesGov() em admin/includes/auth.php e admin/perfis.php.
--
-- As permissões são as secções da sidebar do backoffice:
--   freguesia  — Executivo, Notícias, Eventos, Pontos, Documentos, Galeria...
--   home       — Slides e Homepage
--   virtual    — Pedidos, Marcações, Requerimentos, RH, Contratação...
--   assembleia — Composição, Sessões, Documentos, Competências...
--   denuncias  — Canal de Denúncias
--   sistema    — Utilizadores, Configurações, Separadores de Fundo
--
-- Guardadas como lista separada por vírgulas (ex.: 'freguesia,home').
-- Perfil sem permissões = não vê nada além do painel.
--
-- Idempotente.
-- =====================================================================

CREATE TABLE IF NOT EXISTS perfis (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    nome       VARCHAR(100) NOT NULL UNIQUE,
    descricao  VARCHAR(255) DEFAULT NULL,
    permissoes TEXT DEFAULT NULL,
    ativo      TINYINT(1) NOT NULL DEFAULT 1,
    criado_em  TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- Ligação utilizador -> perfil (opcional: sem perfil, mantém-se o comportamento
-- antigo, baseado apenas no `tipo`).
SET @existe := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE()
      AND table_name = 'admin_utilizadores'
      AND column_name = 'perfil_id'
);
SET @sql := IF(@existe = 0,
    'ALTER TABLE admin_utilizadores ADD COLUMN perfil_id INT DEFAULT NULL',
    'SELECT 1');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;

-- Perfis de arranque, para não começar com a tabela vazia.
INSERT INTO perfis (nome, descricao, permissoes, ativo) VALUES
  ('Secretaria',  'Conteúdos do site e atendimento ao munícipe.', 'freguesia,home,virtual', 1),
  ('Assembleia',  'Apenas a área da Assembleia de Freguesia.',    'assembleia',            1),
  ('Conteúdos',   'Só notícias, eventos e restantes conteúdos.',  'freguesia,home',        1)
ON DUPLICATE KEY UPDATE descricao = VALUES(descricao);
