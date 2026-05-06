-- =============================================
-- Royal Nawab Database
-- Run this file in phpMyAdmin or MySQL CLI
-- =============================================

-- Note for shared/free hosting (InfinityFree etc.):
-- Do not create/select databases in SQL; import into the DB selected in phpMyAdmin.
SET NAMES utf8mb4;

-- Admin users table
CREATE TABLE IF NOT EXISTS admin_users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(60) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table bookings
CREATE TABLE IF NOT EXISTS bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    num_guests INT NOT NULL DEFAULT 2,
    booking_date DATE NOT NULL,
    booking_time VARCHAR(20) NOT NULL,
    special_requests TEXT,
    status VARCHAR(30) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Menu categories
CREATE TABLE IF NOT EXISTS menu_categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(60) NOT NULL,
    icon VARCHAR(20) DEFAULT 'item',
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Menu items
CREATE TABLE IF NOT EXISTS menu_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    category_id INT NOT NULL,
    name VARCHAR(120) NOT NULL,
    description TEXT,
    price DECIMAL(8,2) NOT NULL,
    is_available TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0,
    FOREIGN KEY (category_id) REFERENCES menu_categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Deals
CREATE TABLE IF NOT EXISTS deals (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(8,2) NOT NULL,
    icon VARCHAR(20) DEFAULT 'deal',
    is_active TINYINT(1) DEFAULT 1,
    sort_order INT DEFAULT 0
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS deal_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    deal_id INT NOT NULL,
    item_text VARCHAR(150) NOT NULL,
    FOREIGN KEY (deal_id) REFERENCES deals(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Delivery orders
CREATE TABLE IF NOT EXISTS delivery_drivers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    driver_name VARCHAR(150) NOT NULL,
    phone VARCHAR(20),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS delivery_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_ref VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    delivery_address TEXT NOT NULL,
    special_requests TEXT,
    delivery_time VARCHAR(50),
    subtotal DECIMAL(8,2) DEFAULT 0.00,
    delivery_fee DECIMAL(8,2) DEFAULT 2.99,
    total DECIMAL(8,2) DEFAULT 0.00,
    payment_method VARCHAR(20) DEFAULT 'card',
    payment_status VARCHAR(30) DEFAULT 'Unpaid',
    payment_ref VARCHAR(30),
    paid_at DATETIME NULL,
    driver_id INT NULL,
    status VARCHAR(30) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (driver_id) REFERENCES delivery_drivers(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Delivery order items
CREATE TABLE IF NOT EXISTS delivery_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_name VARCHAR(120) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(8,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES delivery_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Collection orders
CREATE TABLE IF NOT EXISTS collection_orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_ref VARCHAR(20) NOT NULL UNIQUE,
    full_name VARCHAR(150) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    special_requests TEXT,
    collection_time VARCHAR(50),
    subtotal DECIMAL(8,2) DEFAULT 0.00,
    total DECIMAL(8,2) DEFAULT 0.00,
    payment_method VARCHAR(20) DEFAULT 'card',
    payment_status VARCHAR(30) DEFAULT 'Unpaid',
    payment_ref VARCHAR(30),
    paid_at DATETIME NULL,
    status VARCHAR(30) DEFAULT 'Pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS collection_order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    item_name VARCHAR(120) NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    unit_price DECIMAL(8,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES collection_orders(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Payment records for delivery and collection orders
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
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Post-order feedback (linked to an order and optionally to a specific menu item)
CREATE TABLE IF NOT EXISTS order_feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_ref VARCHAR(20) NOT NULL,
    order_type VARCHAR(20) NOT NULL,
    customer_name VARCHAR(150),
    menu_item_id INT NULL,
    menu_item_name VARCHAR(120) NULL,
    rating TINYINT NOT NULL,
    feedback_text TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_feedback_order (order_ref, order_type),
    KEY idx_feedback_item (menu_item_id),
    FOREIGN KEY (menu_item_id) REFERENCES menu_items(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Newsletter subscribers
CREATE TABLE IF NOT EXISTS subscribers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(150) NOT NULL UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- =============================================
-- SEED DATA
-- =============================================

-- Admin login: username=admin, password=admin123
INSERT IGNORE INTO admin_users (username, password_hash) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uSsqaefuW');

-- Menu Categories
INSERT INTO menu_categories (name, icon, sort_order) VALUES
('Starters', 'starter', 1),
('Curries',  'curry', 2),
('Rice',     'rice', 3),
('BBQ',      'bbq', 4),
('Drinks',   'drink', 5),
('Desserts', 'dessert', 6);

-- Menu Items
INSERT INTO menu_items (category_id, name, description, price, sort_order) VALUES
(1, 'Onion Bhaji',           'Crispy golden onion fritters with mint chutney',        5.95, 1),
(1, 'Seekh Kebab',           'Spiced minced lamb on skewers, char-grilled',            7.95, 2),
(1, 'Chicken Tikka',         'Marinated chicken pieces from the tandoor',              7.50, 3),
(1, 'Samosa (2 pcs)',        'Crispy pastry filled with spiced potatoes and peas',     4.95, 4),
(1, 'Prawn Puri',            'King prawns in spiced sauce on fluffy puri bread',       8.95, 5),
(1, 'Mixed Starter Platter', 'Selection of our finest starters for two',             12.95, 6),
(2, 'Chicken Karahi',        'Classic Pakistani karahi with tomatoes and ginger',     13.95, 1),
(2, 'Lamb Rogan Josh',       'Aromatic slow-cooked lamb in Kashmiri sauce',           14.95, 2),
(2, 'Butter Chicken',        'Tender chicken in creamy tomato butter sauce',          13.50, 3),
(2, 'Nihari',                'Slow-braised beef shank in fragrant broth',             15.95, 4),
(2, 'Saag Gosht',            'Tender lamb with fresh spinach and spices',             14.95, 5),
(2, 'Dal Makhani',           'Creamy black lentils slow-cooked overnight',            10.95, 6),
(3, 'Pilau Rice',            'Fragrant basmati with whole spices',                     3.50, 1),
(3, 'Biryani - Chicken',     'Aromatic layered rice with spiced chicken',             14.95, 2),
(3, 'Biryani - Lamb',        'Slow-cooked lamb layered with fragrant basmati',        15.95, 3),
(3, 'Plain Boiled Rice',     'Steamed basmati rice',                                   2.95, 4),
(3, 'Vegetable Fried Rice',  'Wok-fried basmati with seasonal vegetables',             4.50, 5),
(4, 'Mixed BBQ Platter',     'Assortment of tandoor meats for two',                   22.95, 1),
(4, 'Lamb Chops',            'Marinated rack of lamb from the tandoor',               16.95, 2),
(4, 'Whole Tandoori Chicken','Full chicken marinated in yoghurt and spices',          18.95, 3),
(4, 'Shish Kebab',           'Cubed lamb marinated in herbs and spices',              13.95, 4),
(4, 'BBQ King Prawns',       'Jumbo prawns from the tandoor with garlic butter',      17.95, 5),
(5, 'Mango Lassi',           'Chilled yoghurt drink blended with fresh mango',         3.95, 1),
(5, 'Rose Lassi',            'Sweet yoghurt drink with rose water',                    3.95, 2),
(5, 'Masala Chai',           'Spiced tea brewed with cardamom and ginger',             2.95, 3),
(5, 'Soft Drinks',           'Coca-Cola, Sprite, Fanta or Water',                      2.50, 4),
(5, 'Fresh Juices',          'Orange, Apple, Mango or Guava',                          3.50, 5),
(6, 'Gulab Jamun',           'Soft milk dumplings in rose-scented syrup',              4.95, 1),
(6, 'Kheer',                 'Traditional rice pudding with cardamom and pistachios',  4.50, 2),
(6, 'Kulfi',                 'Traditional Indian ice cream – mango or pistachio',      4.95, 3),
(6, 'Gajar Ka Halwa',        'Warm carrot halwa with cream and almonds',               5.50, 4);

-- Deals
INSERT INTO deals (name, description, price, icon, sort_order) VALUES
('Family Feast',       'Perfect for the whole family. Feeds 4-5 people.',       49.95, 'family', 1),
('Couple''s Night',    'A romantic feast for two with starters and dessert.',    29.95, 'couple', 2),
('Lunch Special',      'Quick weekday lunch deal. Available Mon-Fri 12-4pm.',    12.95, 'lunch', 3),
('BBQ Platter',        'Our finest tandoor meats for sharing.',                  34.95, 'bbq', 4),
('Vegetarian Delight', 'A full vegetarian spread of our finest dishes.',         22.95, 'veg', 5),
('Party Pack',         'Feed 8-10 people with this generous group spread.',      79.95, 'party', 6);

INSERT INTO deal_items (deal_id, item_text) VALUES
(1,'2x Chicken Karahi'),(1,'1x Lamb Rogan Josh'),(1,'Pilau Rice x4'),(1,'Naan x4'),(1,'2x Starters'),(1,'2x Soft Drinks'),
(2,'1x Curry of your choice'),(2,'1x Biryani'),(2,'Pilau Rice x2'),(2,'Garlic Naan x2'),(2,'Starter x2'),(2,'Dessert x2'),
(3,'1x Main Curry'),(3,'1x Rice or Naan'),(3,'1x Soft Drink'),
(4,'Seekh Kebab x4'),(4,'Lamb Chops x4'),(4,'Chicken Tikka x6'),(4,'Naan x4'),(4,'Mint Chutney and Salad'),(4,'Pilau Rice x2'),
(5,'Dal Makhani'),(5,'Vegetable Karahi'),(5,'Saag Paneer'),(5,'Pilau Rice x2'),(5,'Garlic Naan x2'),(5,'Onion Bhaji x4'),
(6,'3x Curries of your choice'),(6,'1x Biryani'),(6,'Mixed Starter Platter x2'),(6,'Rice x6'),(6,'Naan x6'),(6,'5x Soft Drinks');