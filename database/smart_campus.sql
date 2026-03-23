-- MySQL database schema for Greenfield Local Hub (GLH)

-- Users table (customers, producers, admins)
CREATE TABLE IF NOT EXISTS users (
    user_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer','producer','admin') NOT NULL DEFAULT 'customer',
    phone VARCHAR(30),
    address TEXT,
    loyalty_points INT NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- Producers table
CREATE TABLE IF NOT EXISTS producers (
    producer_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    farm_name VARCHAR(255) NOT NULL,
    description TEXT,
    farming_methods TEXT,
    location VARCHAR(255),
    website VARCHAR(255),
    image_path VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Categories table
CREATE TABLE IF NOT EXISTS categories (
    category_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT
);

-- Products table
CREATE TABLE IF NOT EXISTS products (
    product_id INT AUTO_INCREMENT PRIMARY KEY,
    producer_id INT NOT NULL,
    category_id INT,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    unit VARCHAR(50) NOT NULL DEFAULT 'each',
    stock_quantity INT NOT NULL DEFAULT 0,
    image_path VARCHAR(255),
    is_available TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (producer_id) REFERENCES producers(producer_id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(category_id) ON DELETE SET NULL
);

-- Orders table
CREATE TABLE IF NOT EXISTS orders (
    order_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    status ENUM('pending','confirmed','ready','out_for_delivery','delivered','cancelled') NOT NULL DEFAULT 'pending',
    fulfillment_type ENUM('collection','delivery') NOT NULL DEFAULT 'collection',
    delivery_address TEXT,
    collection_slot DATETIME,
    total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    discount_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
    loyalty_points_used INT NOT NULL DEFAULT 0,
    loyalty_points_earned INT NOT NULL DEFAULT 0,
    notes TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

-- Order items table
CREATE TABLE IF NOT EXISTS order_items (
    item_id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL,
    unit_price DECIMAL(10,2) NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(order_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE RESTRICT
);

-- Loyalty rewards table
CREATE TABLE IF NOT EXISTS loyalty_rewards (
    reward_id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    points_required INT NOT NULL,
    discount_percent DECIMAL(5,2),
    discount_amount DECIMAL(10,2),
    is_active TINYINT(1) NOT NULL DEFAULT 1
);

-- Cart table (session-based alternative for logged-in users)
CREATE TABLE IF NOT EXISTS cart_items (
    cart_id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    added_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_product (user_id, product_id),
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(product_id) ON DELETE CASCADE
);

-- Seed categories
INSERT IGNORE INTO categories (category_id, name, description) VALUES
(1, 'Vegetables', 'Fresh locally grown vegetables'),
(2, 'Fruit', 'Seasonal locally grown fruit'),
(3, 'Dairy', 'Milk, cheese, yoghurt and other dairy products'),
(4, 'Meat & Poultry', 'Locally reared meat and poultry'),
(5, 'Bakery', 'Freshly baked bread, cakes and pastries'),
(6, 'Preserves & Jams', 'Homemade jams, chutneys and preserves'),
(7, 'Eggs', 'Free-range and organic eggs'),
(8, 'Drinks', 'Locally produced juices, ciders and other drinks');

-- Seed loyalty rewards
INSERT IGNORE INTO loyalty_rewards (name, description, points_required, discount_percent, discount_amount) VALUES
('5% Off Your Order', 'Redeem 100 points for 5% off your next order', 100, 5.00, NULL),
('10% Off Your Order', 'Redeem 200 points for 10% off your next order', 200, 10.00, NULL),
('Free Delivery', 'Redeem 150 points for free delivery on your next order', 150, NULL, 0.00),
('£2 Off Your Order', 'Redeem 50 points for £2 off your next order', 50, NULL, 2.00);

-- Seed admin user (password: Admin@GLH2026 — change in production)
INSERT IGNORE INTO users (user_id, name, email, password_hash, role) VALUES
(1, 'GLH Admin', 'admin@glh.local', '$2y$12$PLACEHOLDER_HASH_REPLACE_ME', 'admin');

-- Seed demo producer user (password: Producer@GLH2026 — change in production)
INSERT IGNORE INTO users (user_id, name, email, password_hash, role) VALUES
(2, 'Green Acres Farm', 'greenacres@glh.local', '$2y$12$PLACEHOLDER_HASH_REPLACE_ME2', 'producer');

INSERT IGNORE INTO producers (user_id, farm_name, description, farming_methods, location) VALUES
(2, 'Green Acres Farm',
 'A family-run farm nestled in the rolling hills of the Greenfield valley. We have been growing seasonal vegetables and raising free-range poultry for over 30 years.',
 'Organic and regenerative farming practices. No artificial pesticides or fertilisers. Crop rotation and companion planting used throughout.',
 'Greenfield Valley, 3 miles east of town');

-- Seed demo customer user (password: Customer@GLH2026 — change in production)
INSERT IGNORE INTO users (user_id, name, email, password_hash, role, loyalty_points) VALUES
(3, 'Jane Smith', 'jane@example.com', '$2y$12$PLACEHOLDER_HASH_REPLACE_ME3', 'customer', 120);