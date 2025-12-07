-- Migration: Add priceUnit column to products table
-- This column stores the unit of measurement for the price (e.g., Per KG, Per Each, Per Dozen)

-- Check if column exists, if not add it
SET @dbname = DATABASE();
SET @tablename = "products";
SET @columnname = "priceUnit";
SET @preparedStatement = (SELECT IF(
  (
    SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
    WHERE
      (table_name = @tablename)
      AND (table_schema = @dbname)
      AND (column_name = @columnname)
  ) > 0,
  "SELECT 'Column already exists.'",
  CONCAT("ALTER TABLE ", @tablename, " ADD COLUMN ", @columnname, " VARCHAR(50) DEFAULT 'Per Each' AFTER itemSize")
));
PREPARE alterIfNotExists FROM @preparedStatement;
EXECUTE alterIfNotExists;
DEALLOCATE PREPARE alterIfNotExists;

-- Update existing products to have a default price unit if they don't have one
UPDATE products SET priceUnit = 'Per Each' WHERE priceUnit IS NULL OR priceUnit = '';

