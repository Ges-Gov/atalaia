-- =====================================================================
-- 002_separadores_fundo — Imagens de fundo dos separadores (menus)
-- =====================================================================
-- CORE: idêntico em todos os sites.
--
-- Guarda a imagem de fundo escolhida no backoffice para o cabeçalho ("herói")
-- de cada página: 'historia', 'heraldica', 'executivo', 'noticias', etc.
-- A chave corresponde ao $paginaAtual; ver includes/separadores.php
-- (fundoSeparadorImg) e o bloco que o includes/header.php injeta.
--
-- Idempotente: pode correr em sites que já têm a tabela (a maioria já tem;
-- o riomoinhos-test não tinha).
-- =====================================================================

CREATE TABLE IF NOT EXISTS separadores_fundo (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    chave         VARCHAR(60) NOT NULL UNIQUE,
    imagem        VARCHAR(255) DEFAULT NULL,
    atualizado_em TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
