-- =====================================================================
-- 039_rh_horario_trabalho — Categoria «Mapa de Horário de Trabalho» nos Recursos Humanos
-- =====================================================================
-- CORE: idêntico em todos os sites. Idempotente.
--
-- O mapa de horário de trabalho é de publicação obrigatória no sítio da Junta (art. 216.º, n.º 1 do Código do
-- Trabalho, ex vi art. 101.º da LTFP — lista da ANAFRE, setembro de 2026) e não tinha categoria própria.
-- =====================================================================

SET @tem := (SELECT COUNT(*) FROM information_schema.columns
             WHERE table_schema = DATABASE() AND table_name = 'recursos_humanos_documentos'
               AND column_name = 'categoria' AND column_type LIKE '%horario_trabalho%');
SET @sql := IF(@tem = 0,
    "ALTER TABLE recursos_humanos_documentos MODIFY categoria ENUM('mapa_pessoal','horario_trabalho','recrutamento_concursos','avaliacao_siadap','plano_formacao','organigrama','transparencia_legislacao','outros') NOT NULL DEFAULT 'outros'",
    'SELECT 1');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;
