USE royal_nawab;

CREATE TABLE IF NOT EXISTS delivery_drivers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  driver_name VARCHAR(150) NOT NULL,
  phone VARCHAR(20),
  is_active TINYINT(1) DEFAULT 1,
  created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

DROP PROCEDURE IF EXISTS add_driver_column_if_missing;
DELIMITER //
CREATE PROCEDURE add_driver_column_if_missing()
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'delivery_orders'
      AND COLUMN_NAME = 'driver_id'
  ) THEN
    ALTER TABLE delivery_orders ADD COLUMN driver_id INT NULL AFTER paid_at;
  END IF;
END//
DELIMITER ;

CALL add_driver_column_if_missing();
DROP PROCEDURE IF EXISTS add_driver_column_if_missing;

DROP PROCEDURE IF EXISTS add_driver_fk_if_missing;
DELIMITER //
CREATE PROCEDURE add_driver_fk_if_missing()
BEGIN
  IF NOT EXISTS (
    SELECT 1
    FROM information_schema.TABLE_CONSTRAINTS
    WHERE CONSTRAINT_SCHEMA = DATABASE()
      AND TABLE_NAME = 'delivery_orders'
      AND CONSTRAINT_NAME = 'fk_delivery_orders_driver'
  ) THEN
    ALTER TABLE delivery_orders
    ADD CONSTRAINT fk_delivery_orders_driver
    FOREIGN KEY (driver_id) REFERENCES delivery_drivers(id)
    ON DELETE SET NULL;
  END IF;
END//
DELIMITER ;

CALL add_driver_fk_if_missing();
DROP PROCEDURE IF EXISTS add_driver_fk_if_missing;

INSERT INTO delivery_drivers (driver_name, phone)
SELECT 'Driver 1', ''
WHERE NOT EXISTS (SELECT 1 FROM delivery_drivers WHERE driver_name = 'Driver 1');

INSERT INTO delivery_drivers (driver_name, phone)
SELECT 'Driver 2', ''
WHERE NOT EXISTS (SELECT 1 FROM delivery_drivers WHERE driver_name = 'Driver 2');

INSERT INTO delivery_drivers (driver_name, phone)
SELECT 'Driver 3', ''
WHERE NOT EXISTS (SELECT 1 FROM delivery_drivers WHERE driver_name = 'Driver 3');
