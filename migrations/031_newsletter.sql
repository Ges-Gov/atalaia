-- =====================================================================
-- 031_newsletter — newsletter mensal (subscritores, histórico, definições)
-- =====================================================================
-- CORE: idêntico em todos os sites. Idempotente.
--
-- O texto da newsletter compõe-se sozinho todos os meses a partir das
-- notícias e eventos já publicados (ver includes/newsletter.php) — não é
-- um CMS com corpo editável campo a campo. Só há 2 exceções manuais:
-- escolher o destaque do topo, ou substituir tudo por um texto escrito
-- à mão (newsletter_config.conteudo_manual), que se limpa sozinho depois
-- de cada envio.
--
-- O envio usa a MESMA conta de email partilhada entre todos os sites
-- (ver includes/mail_helper.php) — nunca a caixa de correio própria da
-- freguesia. Só o nome a aparecer no "De:" muda (siteConfig('nome_site')).
-- =====================================================================

CREATE TABLE IF NOT EXISTS newsletter_subscribers (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    email      VARCHAR(255) NOT NULL,
    nome       VARCHAR(255) NULL,
    origem     ENUM('site','manual') NOT NULL DEFAULT 'manual',
    ativo      TINYINT(1) NOT NULL DEFAULT 1,
    criado_em  DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_email (email)
);

CREATE TABLE IF NOT EXISTS newsletter_envios (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    mes_referencia      VARCHAR(7) NOT NULL,
    conteudo_html       LONGTEXT NOT NULL,
    total_destinatarios INT NOT NULL DEFAULT 0,
    enviado_em          DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_mes (mes_referencia)
);

-- Linha única de definições (sempre id=1) — mesmo padrão de "configuração
-- de uma peça" já usado noutras tabelas deste projeto.
CREATE TABLE IF NOT EXISTS newsletter_config (
    id              INT PRIMARY KEY DEFAULT 1,
    auto_ativo      TINYINT(1) NOT NULL DEFAULT 0,
    dia_envio       TINYINT UNSIGNED NOT NULL DEFAULT 1,
    ultimo_envio    VARCHAR(7) NULL,
    conteudo_manual LONGTEXT NULL,
    destaque_tipo   ENUM('noticia','evento') NULL,
    destaque_id     INT NULL
);

INSERT IGNORE INTO newsletter_config (id) VALUES (1);

-- Domínio público do site (ex: https://jf-granho.pt) — necessário para
-- montar URLs absolutas de imagens no email (um cliente de email não tem
-- "página atual", ao contrário do browser, por isso caminhos relativos
-- não funcionam). Só é preciso preencher para o envio real (por cron,
-- sem pedido HTTP); a pré-visualização no backoffice já resolve sozinha
-- a partir do próprio pedido web.
SET @coluna_existe := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'configuracoes_site' AND column_name = 'dominio'
);
SET @sql := IF(@coluna_existe = 0,
    'ALTER TABLE configuracoes_site ADD COLUMN dominio VARCHAR(255) NULL AFTER email',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
