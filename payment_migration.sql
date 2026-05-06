USE royal_nawab;

DROP PROCEDURE IF EXISTS add_column_if_missing;
DELIMITER //
CREATE PROCEDURE add_column_if_missing(
  IN table_name_value VARCHAR(64),
  IN column_name_value VARCHAR(64),
  IN column_definition_value TEXT
)
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = table_name_value
      AND COLUMN_NAME = column_name_value
  ) THEN
    SET @add_column_sql = CONCAT('ALTER TABLE `', table_name_value, '` ADD COLUMN ', column_definition_value);
    PREPARE add_column_stmt FROM @add_column_sql;
    EXECUTE add_column_stmt;
    DEALLOCATE PREPARE add_column_stmt;
  END IF;
END//
DELIMITER ;

CALL add_column_if_missing('delivery_orders', 'payment_status', 'payment_status VARCHAR(30) DEFAULT ''Unpaid'' AFTER payment_method');
CALL add_column_if_missing('delivery_orders', 'payment_ref', 'payment_ref VARCHAR(30) NULL AFTER payment_status');
CALL add_column_if_missing('delivery_orders', 'paid_at', 'paid_at DATETIME NULL AFTER payment_ref');

CALL add_column_if_missing('collection_orders', 'payment_status', 'payment_status VARCHAR(30) DEFAULT ''Unpaid'' AFTER payment_method');
CALL add_column_if_missing('collection_orders', 'payment_ref', 'payment_ref VARCHAR(30) NULL AFTER payment_status');
CALL add_column_if_missing('collection_orders', 'paid_at', 'paid_at DATETIME NULL AFTER payment_ref');

DROP PROCEDURE IF EXISTS add_column_if_missing;

CREATE TABLE IF NOT EXISTS payments (
  id INT AUTO_INCREMENT PRIMARY KEY,
  order_type VARCHAR(20) NOT NULL,
  order_id INT NOT NULL,
  order_ref VARCHAR(20) NOT NULL,
  amount DECIMAL(8,2) NOT NULL,
  payment_method VARCHAR(20) DEFAULT 'card',
  payment_status VARCHAR(30) DEFAULT 'Paid',
  payment_ref VARCHAR(30) NOT NULL UNIQUE,
  cardholder_name VARCHAR(150),
  card_last4 VARCHAR(4),
  paid_at DATETIME DEFAULT CURRENT_TIMESTAMP
);
