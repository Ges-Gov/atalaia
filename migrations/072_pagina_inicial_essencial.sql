-- 072_pagina_inicial_essencial — interruptores «Junta perto de si», «Perto de quem precisa» e «Descobrir»; página inicial essencial.
-- CORE, idempotente. Mesmo nome que a 072 do modelo novo, reescrita (redesign 10/2026): só acrescenta o que falta.

-- Na primeira vez desliga as secções que repetem o que já está no topo (como no modelo novo): boas-vindas,
-- proximidade, descobrir, faixa do mapa, serviços, galeria em carrossel e chamada final. Voltam a ligar-se em
-- Página Inicial › Textos da página inicial.
SET @falta := (SELECT COUNT(*) = 0 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'homepage_config' AND COLUMN_NAME = 'mostrar_proximidade');
SET @sql := IF(@falta, 'ALTER TABLE homepage_config ADD COLUMN mostrar_proximidade TINYINT(1) NOT NULL DEFAULT 1, ADD COLUMN mostrar_apoios TINYINT(1) NOT NULL DEFAULT 1, ADD COLUMN mostrar_descobrir TINYINT(1) NOT NULL DEFAULT 1', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
SET @sql := IF(@falta, 'UPDATE homepage_config SET mostrar_proximidade = 0, mostrar_boasvindas = 0, mostrar_descobrir = 0, mostrar_mapa = 0, mostrar_servicos = 0, mostrar_galeria = 0, mostrar_cta_final = 0', 'SELECT 1');
PREPARE st FROM @sql; EXECUTE st; DEALLOCATE PREPARE st;
