INSERT INTO tema_config (chave, valor, descricao) VALUES ('admin_escuro','#11151B','Tom escuro do backoffice (títulos, sidebar)') ON DUPLICATE KEY UPDATE valor=VALUES(valor);
