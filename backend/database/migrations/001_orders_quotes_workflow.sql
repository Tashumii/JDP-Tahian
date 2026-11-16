-- Upgrade either the original contact-only order table or an installation that
-- already has some checkout columns. Safe to run more than once on MySQL/MariaDB.
USE joey_tahian;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'email');
SET @migration_sql = IF(@column_exists = 0,
  'ALTER TABLE orders ADD COLUMN email VARCHAR(120) NOT NULL DEFAULT '''' AFTER customer_name',
  'SELECT 1');
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'mobile');
SET @migration_sql = IF(@column_exists = 0,
  'ALTER TABLE orders ADD COLUMN mobile VARCHAR(30) NOT NULL DEFAULT '''' AFTER email',
  'SELECT 1');
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'payment_method');
SET @migration_sql = IF(@column_exists = 0,
  'ALTER TABLE orders ADD COLUMN payment_method VARCHAR(20) NOT NULL DEFAULT ''COD'' AFTER total',
  'SELECT 1');
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'contact');
SET @migration_sql = IF(@column_exists > 0,
  'UPDATE orders SET email = CASE WHEN contact LIKE ''%@%'' THEN contact ELSE email END, mobile = CASE WHEN contact NOT LIKE ''%@%'' THEN contact ELSE mobile END',
  'SELECT 1');
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'confirmation_token_hash');
SET @migration_sql = IF(@column_exists = 0,
  'ALTER TABLE orders ADD COLUMN confirmation_token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL, ADD UNIQUE KEY uq_orders_confirmation_token (confirmation_token_hash)',
  'SELECT 1');
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'confirmation_expires_at');
SET @migration_sql = IF(@column_exists = 0,
  'ALTER TABLE orders ADD COLUMN confirmation_expires_at DATETIME DEFAULT NULL',
  'SELECT 1');
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'email_confirmed_at');
SET @migration_sql = IF(@column_exists = 0,
  'ALTER TABLE orders ADD COLUMN email_confirmed_at DATETIME DEFAULT NULL',
  'SELECT 1');
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'confirmation_email_sent_at');
SET @migration_sql = IF(@column_exists = 0,
  'ALTER TABLE orders ADD COLUMN confirmation_email_sent_at DATETIME DEFAULT NULL',
  'SELECT 1');
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;

SET @column_exists = (SELECT COUNT(*) FROM information_schema.COLUMNS
  WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'quotes' AND COLUMN_NAME = 'status');
SET @migration_sql = IF(@column_exists = 0,
  'ALTER TABLE quotes ADD COLUMN status ENUM(''pending'',''reviewed'',''contacted'',''closed'') NOT NULL DEFAULT ''pending'' AFTER requested_items',
  'SELECT 1');
PREPARE migration_statement FROM @migration_sql;
EXECUTE migration_statement;
DEALLOCATE PREPARE migration_statement;
