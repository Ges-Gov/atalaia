-- =====================================================================
-- 019_procedimentos_concursais_campos — campos legais de recrutamento
-- =====================================================================
-- CORE: idêntico em todos os sites.
--
-- A tabela contratacao_publica (agora "Procedimentos Concursais" no
-- site público/admin — ver migração 023) tinha campos pensados para
-- contratação de bens/serviços (tipo, empresa, valor), não para
-- recrutamento de pessoal. Acrescenta os campos reais usados no
-- procedimento concursal comum (LTFP), alinhados com o painel de
-- Procedimentos Concursais da intranet (App\Filament\Enums\CarreiraGeral/
-- TipoVinculo/ProcedimentoEstado) — mesma terminologia legal, não inventada.
--
-- Os campos antigos (tipo, empresa, valor) ficam na tabela, por
-- compatibilidade com registos existentes, mas deixam de ser mostrados
-- no formulário.
--
-- Idempotente: só acrescenta as colunas que ainda não existirem.
-- =====================================================================

SET @existe := (
    SELECT COUNT(*) FROM information_schema.columns
    WHERE table_schema = DATABASE() AND table_name = 'contratacao_publica' AND column_name = 'referencia'
);
SET @sql := IF(@existe = 0,
    'ALTER TABLE contratacao_publica
        ADD COLUMN referencia VARCHAR(100) NULL AFTER titulo,
        ADD COLUMN numero_procedimento VARCHAR(100) NULL AFTER referencia,
        ADD COLUMN carreira VARCHAR(100) NULL AFTER numero_procedimento,
        ADD COLUMN categoria VARCHAR(100) NULL AFTER carreira,
        ADD COLUMN area_funcional VARCHAR(150) NULL AFTER categoria,
        ADD COLUMN vagas SMALLINT UNSIGNED NULL AFTER area_funcional,
        ADD COLUMN tipo_vinculo VARCHAR(100) NULL AFTER vagas,
        ADD COLUMN inicio_candidaturas DATE NULL AFTER data_publicacao,
        ADD COLUMN fim_candidaturas DATE NULL AFTER inicio_candidaturas,
        ADD COLUMN requisitos TEXT NULL AFTER descricao',
    'SELECT 1');
PREPARE st FROM @sql;
EXECUTE st;
DEALLOCATE PREPARE st;
