CREATE DATABASE IF NOT EXISTS dionisio_fitness CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE dionisio_fitness;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    full_name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS memberships (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    plan ENUM('Basic','Premium','VIP') NOT NULL,
    price DECIMAL(10,2) NOT NULL DEFAULT 0,
    status ENUM('Pending','Active','Expired') NOT NULL DEFAULT 'Active',
    start_date DATE NOT NULL,
    expiration_date DATE NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_membership_user_status (user_id, status),
    CONSTRAINT fk_membership_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    product_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    product_name VARCHAR(150) NOT NULL,
    description TEXT DEFAULT NULL,
    price DECIMAL(10,2) NOT NULL,
    image VARCHAR(255) DEFAULT NULL,
    stock INT UNSIGNED NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_product_name (product_name),
    INDEX idx_products_name (product_name)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS cart_items (
    cart_item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_cart_user_product (user_id, product_id),
    CONSTRAINT fk_cart_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_cart_product FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    order_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    total_amount DECIMAL(10,2) NOT NULL,
    status ENUM('Pending','Processing','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
    order_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_orders_user_date (user_id, order_date),
    CONSTRAINT fk_order_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    order_item_id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    order_id INT UNSIGNED NOT NULL,
    product_id INT UNSIGNED DEFAULT NULL,
    product_name VARCHAR(150) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT UNSIGNED NOT NULL,
    subtotal DECIMAL(10,2) NOT NULL,
    CONSTRAINT fk_order_item_order FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    CONSTRAINT fk_order_item_product FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- If you already have the older memberships table, these columns should be added before using the new pages.
ALTER TABLE memberships ADD COLUMN IF NOT EXISTS price DECIMAL(10,2) NOT NULL DEFAULT 0 AFTER plan;
ALTER TABLE memberships ADD COLUMN IF NOT EXISTS expiration_date DATE NULL AFTER start_date;

-- Existing older rows get a sensible expiration date instead of breaking the account page.
UPDATE memberships SET expiration_date = DATE_ADD(start_date, INTERVAL 1 MONTH) WHERE expiration_date IS NULL;

INSERT INTO products (product_name, description, price, image, stock) VALUES
('Dionisio Performance Tee', 'Dionisio training shirt for workouts and everyday wear.', 599.00, '124321312412-removebg-preview.png', 20),
('Dionisio Shaker Bottle', 'Reusable shaker bottle for training days.', 399.00, '5B590DAD-32B1-481D-A8C5-B76ADF1CCFFE_copy-removebg-preview.png', 20),
('Dionisio Hoodie', 'Comfortable Dionisio hoodie for warm-ups and rest days.', 1099.00, 'EWRWR4WE-removebg-preview.png', 15),
('Dionisio Cap', 'Dionisio cap with a clean training-day look.', 1099.00, 'cap.png', 15)
ON DUPLICATE KEY UPDATE product_name = VALUES(product_name);
