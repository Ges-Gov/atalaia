-- =====================================================================
-- 001_tema_config — Tokens de tema (separação "core" / "marca")
-- =====================================================================
-- CORE: este ficheiro é IDÊNTICO em todos os sites.
--
-- Porquê: as cores de cada freguesia estavam hardcoded dentro do bloco de
-- tema do includes/footer.php. Isso obrigava a que o footer.php fosse
-- diferente em cada site — e por isso as correções feitas num site (ex.: a
-- guarda "tem-fundo-separador", que faz as imagens de fundo dos separadores
-- aparecerem) não podiam ser copiadas para os outros sem lhes trocar a marca.
--
-- A partir daqui: o footer.php passa a ser core (igual em todos), lê estes
-- tokens como variáveis CSS, e a marca vive só aqui na BD.
--
-- Os valores são semeados por site (ver 001_tema_config_seed_<site>.sql) com
-- as cores ATUAIS de cada um, para que não haja qualquer mudança visual.
-- =====================================================================

CREATE TABLE IF NOT EXISTS tema_config (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    chave         VARCHAR(60)  NOT NULL UNIQUE,
    valor         VARCHAR(120) NOT NULL,
    descricao     VARCHAR(255) DEFAULT NULL,
    atualizado_em TIMESTAMP    DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
