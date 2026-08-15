-- Maxi Fine Foods — schema.sql
-- Run against a fresh `maxi_fine_foods` database (matches includes/db.php).
-- Field names here are provisional — confirm against Anurag's ERD before
-- treating this as final (see "Open Questions" in the design doc).

CREATE DATABASE IF NOT EXISTS maxi_fine_foods CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE maxi_fine_foods;

CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin', 'delivery') NOT NULL DEFAULT 'customer',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(60) UNIQUE,
    name VARCHAR(160) NOT NULL,
    category VARCHAR(60) NOT NULL,
    badge VARCHAR(60),
    price DECIMAL(10,2) NOT NULL,
    stock INT NOT NULL DEFAULT 0,
    low_stock_threshold INT NOT NULL DEFAULT 10,
    image_url VARCHAR(500),
    description TEXT,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    customer_name VARCHAR(160) NOT NULL,
    contact_number VARCHAR(40) NOT NULL,
    delivery_address VARCHAR(255) NOT NULL,
    delivery_instructions TEXT,
    payment_method ENUM('cash_on_delivery', 'credit_card', 'paypal') NOT NULL DEFAULT 'cash_on_delivery',
    subtotal DECIMAL(10,2) NOT NULL,
    tax DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'out_for_delivery', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    driver VARCHAR(120) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NULL,
    name VARCHAR(160) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- Seed data mirroring includes/db.php's mff_products_fallback() so the
-- catalogue looks the same whether or not MySQL is connected.
INSERT INTO products (sku, name, category, badge, price, stock, low_stock_threshold, image_url, description) VALUES
('avocado', 'Organic Hass Avocados', 'Produce', 'Organic', 2.49, 42, 10, 'https://images.unsplash.com/photo-1523049673857-eb18f1d7b578?w=600&q=80', 'Creamy, hand-selected Hass avocados, ripened to order.'),
('sourdough', 'Country Sourdough', 'Bakery', 'Artisan Baked', 7.50, 18, 8, 'https://images.unsplash.com/photo-1585478259715-4d3a5d7f3b8b?w=600&q=80', 'Slow-fermented 48-hour sourdough baked fresh each morning.'),
('salmon', 'Atlantic Salmon Fillet', 'Meat & Seafood', 'Wild Caught', 18.90, 6, 8, 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=600&q=80', 'Sustainably wild-caught salmon, filleted daily.'),
('blueberry', 'Organic Blueberries', 'Produce', 'Organic', 6.90, 27, 10, 'https://images.unsplash.com/photo-1498557850523-fd3d118b962e?w=600&q=80', 'Sweet, plump blueberries grown without synthetic pesticides.'),
('oil', 'Extra Virgin Olive Oil', 'Beverages', 'Cold Pressed', 19.50, 33, 10, 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=600&q=80', 'First cold-pressed olive oil from a single estate grove.'),
('eggs', 'Free-Range Eggs', 'Dairy', 'Farm Fresh', 8.40, 4, 10, 'https://images.unsplash.com/photo-1518569656558-1f25e69d93d7?w=600&q=80', 'Free-range eggs, collected daily from pasture-raised hens.');
