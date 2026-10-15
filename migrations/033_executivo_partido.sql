-- Executivo e Assembleia passam a usar o mesmo formulário (nome, cargo, partido...).
-- O executivo ainda não tinha coluna para o partido.
SET @existe := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'executivo_membros'
      AND COLUMN_NAME = 'partido'
);
SET @sql := IF(@existe = 0,
    'ALTER TABLE executivo_membros ADD COLUMN partido VARCHAR(100) NULL AFTER cargo',
    'SELECT 1'
);
PREPARE stmt FROM @sql;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;
