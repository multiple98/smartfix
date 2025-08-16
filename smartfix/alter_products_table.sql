USE smartfix;

-- Check if stock column exists
SET @column_exists = 0;
SELECT COUNT(*) INTO @column_exists 
FROM information_schema.columns 
WHERE table_schema = 'smartfix' 
AND table_name = 'products' 
AND column_name = 'stock';

-- Add stock column if it doesn't exist
SET @query = IF(@column_exists = 0, 
    'ALTER TABLE products ADD COLUMN stock INT DEFAULT 1',
    'SELECT "Stock column already exists"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;

-- Check if updated_at column exists
SET @column_exists = 0;
SELECT COUNT(*) INTO @column_exists 
FROM information_schema.columns 
WHERE table_schema = 'smartfix' 
AND table_name = 'products' 
AND column_name = 'updated_at';

-- Add updated_at column if it doesn't exist
SET @query = IF(@column_exists = 0, 
    'ALTER TABLE products ADD COLUMN updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP',
    'SELECT "updated_at column already exists"');
PREPARE stmt FROM @query;
EXECUTE stmt;
DEALLOCATE PREPARE stmt;