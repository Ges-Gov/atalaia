-- =====================================================================
-- 086_creditos_imagens — Autor/titular das imagens de terceiros
-- =====================================================================
-- CORE: idêntico em todos os sites. 02/11/2026.
--
-- Para usar fotografias de terceiros é preciso identificar o autor e
-- respeitar a licença. A página /creditos.php (ligação no rodapé) lista
-- as imagens de terceiros com o titular, a licença e a fonte. Junta as
-- linhas desta tabela com os créditos que já existiam noutros sítios
-- (Memória da Freguesia, galeria, fotos dos cartões) — includes/creditos.php.
--
-- ficheiro = caminho a partir da raiz do site (ex.: assets/img/igreja.jpg).
--
-- Idempotente: só cria a tabela se faltar.
-- =====================================================================

CREATE TABLE IF NOT EXISTS creditos_imagens (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    ficheiro VARCHAR(255) NOT NULL,
    onde VARCHAR(190) NULL DEFAULT NULL,
    titular VARCHAR(255) NOT NULL,
    licenca VARCHAR(120) NULL DEFAULT NULL,
    fonte_url VARCHAR(500) NULL DEFAULT NULL,
    nota VARCHAR(255) NULL DEFAULT NULL,
    ativo TINYINT(1) NOT NULL DEFAULT 1,
    criado_em TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_creditos_ficheiro (ficheiro)
);
