-- ====================================================================
-- Maxi Fine Foods — Online Grocery Management System Database Schema
-- Database: `maxi_fine_foods`
-- Designed for CPRO306 Capstone Project / XAMPP phpMyAdmin & MySQL
-- ====================================================================

-- Drop in foreign-key dependency order
DROP TABLE IF EXISTS feedback;
DROP TABLE IF EXISTS cart_items;
DROP TABLE IF EXISTS order_items;
DROP TABLE IF EXISTS orders;
DROP TABLE IF EXISTS products;
DROP TABLE IF EXISTS categories;
DROP TABLE IF EXISTS users;

-- 1. USERS TABLE
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(190) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    role ENUM('customer', 'admin', 'delivery', 'inventory_manager', 'logistics_manager', 'support_staff') NOT NULL DEFAULT 'customer',
    contact_number VARCHAR(40) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 2. CATEGORIES TABLE
CREATE TABLE categories (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(80) NOT NULL UNIQUE,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 3. PRODUCTS TABLE
CREATE TABLE products (
    id INT AUTO_INCREMENT PRIMARY KEY,
    sku VARCHAR(60) UNIQUE,
    name VARCHAR(160) NOT NULL,
    category VARCHAR(60) NOT NULL,
    badge VARCHAR(60) NULL,
    price DECIMAL(10,2) NOT NULL,
    original_price DECIMAL(10,2) NULL,
    is_special TINYINT(1) NOT NULL DEFAULT 0,
    stock INT NOT NULL DEFAULT 0,
    low_stock_threshold INT NOT NULL DEFAULT 10,
    image_url VARCHAR(500) NULL,
    description TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- 4. ORDERS TABLE
CREATE TABLE orders (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    customer_name VARCHAR(160) NOT NULL,
    contact_number VARCHAR(40) NOT NULL,
    delivery_address VARCHAR(255) NOT NULL,
    delivery_instructions TEXT NULL,
    payment_method ENUM('cash_on_delivery', 'credit_card', 'paypal') NOT NULL DEFAULT 'cash_on_delivery',
    subtotal DECIMAL(10,2) NOT NULL,
    tax DECIMAL(10,2) NOT NULL,
    total DECIMAL(10,2) NOT NULL,
    status ENUM('pending', 'processing', 'out_for_delivery', 'delivered', 'cancelled') NOT NULL DEFAULT 'pending',
    driver VARCHAR(120) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    stripe_session_id VARCHAR(100) NULL,
    UNIQUE KEY uniq_orders_stripe_session (stripe_session_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 5. ORDER_ITEMS TABLE
CREATE TABLE order_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NOT NULL,
    product_id INT NULL,
    name VARCHAR(160) NOT NULL,
    price DECIMAL(10,2) NOT NULL,
    quantity INT NOT NULL,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE CASCADE,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- 6. CART_ITEMS TABLE — per-user persistent shopping cart
--
-- A cart belongs to EITHER a logged-in user (user_id set) OR an anonymous
-- browser session (guest_token set, user_id NULL), never both. Every read and
-- write filters on this owner, which is what keeps one account's cart from
-- being visible to another. There is deliberately no shared/global cart row.
CREATE TABLE cart_items (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NULL,
    guest_token CHAR(64) NULL,
    product_id INT NOT NULL,
    quantity INT NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_cart_user_product (user_id, product_id),
    UNIQUE KEY uniq_cart_guest_product (guest_token, product_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- 7. FEEDBACK TABLE (SRS FR37, FR47)
CREATE TABLE feedback (
    id INT AUTO_INCREMENT PRIMARY KEY,
    order_id INT NULL,
    user_id INT NULL,
    customer_name VARCHAR(120) NOT NULL,
    rating INT NOT NULL CHECK (rating BETWEEN 1 AND 5),
    comment TEXT NULL,
    sentiment VARCHAR(40) NULL DEFAULT 'positive',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (order_id) REFERENCES orders(id) ON DELETE SET NULL,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ====================================================================
-- SEED DATA
-- ====================================================================

-- Seed Users (Passwords: admin123, logistics123, inventory123, support123, driver123, customer123)
INSERT INTO users (id, name, email, password_hash, role, contact_number) VALUES
(1, 'Maxi Admin', 'admin@maxifinefoods.com.au', '$2y$10$jthKjHJZbqhQfY2jx1pVi.gbxRt8QjiK12Gb1kTjejKdZPlLuGnVa', 'admin', '0400 000 001'),
(2, 'Marcus Vance', 'logistics@maxifinefoods.com.au', '$2y$10$jthKjHJZbqhQfY2jx1pVi.gbxRt8QjiK12Gb1kTjejKdZPlLuGnVa', 'logistics_manager', '0411 778 990'),
(3, 'Elena Rostova', 'inventory@maxifinefoods.com.au', '$2y$10$jthKjHJZbqhQfY2jx1pVi.gbxRt8QjiK12Gb1kTjejKdZPlLuGnVa', 'inventory_manager', '0422 334 556'),
(4, 'Liam O\'Connor', 'support@maxifinefoods.com.au', '$2y$10$jthKjHJZbqhQfY2jx1pVi.gbxRt8QjiK12Gb1kTjejKdZPlLuGnVa', 'support_staff', '0433 889 112'),
(5, 'Chris Allen', 'driver@maxifinefoods.com.au', '$2y$10$6v09e2yQrbb2DDm.gEHu9OhCPBz4Mt9t.mgig46GWDN7ZmcOoJ3x6', 'delivery', '0400 000 002'),
(6, 'Jordan Lee', 'jordan@maxifinefoods.com.au', '$2y$10$6v09e2yQrbb2DDm.gEHu9OhCPBz4Mt9t.mgig46GWDN7ZmcOoJ3x6', 'delivery', '0400 000 003'),
(7, 'Emma Wilson', 'customer@maxifinefoods.com.au', '$2y$10$.He9ABf2cvWwB01Q4YEQSeBh8gltkQ5PU7FhSUp34VdgaS0hcFm72', 'customer', '0412 345 678');

-- Seed Categories
INSERT INTO categories (id, name, description) VALUES
(1, 'Produce', 'Fresh farm fruits and vegetables'),
(2, 'Bakery', 'Freshly baked breads and pastries'),
(3, 'Meat & Seafood', 'Wild-caught fish and premium meats'),
(4, 'Dairy', 'Farm fresh milk, cheese, and eggs'),
(5, 'Beverages', 'Pantry beverages and cold-pressed oils');
-- Seed Products (50 Total — 10 per Department)
INSERT INTO products (id, sku, name, category, badge, price, original_price, is_special, stock, low_stock_threshold, image_url, description) VALUES
-- PRODUCE (1–10)
(1,  'avocado',        'Organic Hass Avocados',                 'Produce', 'Organic',        2.49, NULL, 0, 42, 10, 'https://images.unsplash.com/photo-1523049673857-eb18f1d7b578?w=600&q=80', 'Creamy, hand-selected Hass avocados, ripened to order.'),
(2,  'strawberries',   'Australian Fresh Strawberries 250g',    'Produce', '33% OFF',        2.99, 4.50, 1, 45, 10, 'https://images.unsplash.com/photo-1464965911861-746a04b4bca6?w=600&q=80', 'Sweet, ruby-red Australian strawberries freshly picked from regional farms.'),
(3,  'bananas',        'Cavendish Bananas 1kg',                 'Produce', 'Local Pick',     3.90, NULL, 0, 55, 10, 'https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e?w=600&q=80', 'Naturally sweet Queensland Cavendish bananas, full of potassium.'),
(4,  'gala-apples',    'Royal Gala Apples 1kg',                 'Produce', 'Crunchy',        4.50, NULL, 0, 38, 10, 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?w=600&q=80', 'Crisp, aromatic Gala apples picked fresh from local orchards.'),
(5,  'iceberg-lettuce','Crisp Iceberg Lettuce',                 'Produce', 'Farm Fresh',     2.50, NULL, 0, 30, 10, 'https://images.pexels.com/photos/18441983/pexels-photo-18441983.jpeg?auto=compress&cs=tinysrgb&w=600', 'Firm, crunchy iceberg lettuce perfect for fresh garden salads.'),
(6,  'baby-spinach',   'Organic Baby Spinach 200g',             'Produce', 'Organic',        3.90, NULL, 0, 24, 10, 'https://images.unsplash.com/photo-1576045057995-568f588f82fb?w=600&q=80', 'Tender washed organic baby spinach leaves, rich in iron.'),
(7,  'roma-tomatoes',  'Gourmet Roma Tomatoes 500g',            'Produce', 'Vine Ripened',   3.20, NULL, 0, 35, 10, 'https://images.unsplash.com/photo-1592924357228-91a4daadcfea?w=600&q=80', 'Sweet, flavourful Roma tomatoes ideal for sauces and slicing.'),
(8,  'dutch-carrots',  'Dutch Baby Carrots Bunch',              'Produce', 'Farm Fresh',     3.50, NULL, 0, 22, 10, 'https://images.pexels.com/photos/4992944/pexels-photo-4992944.jpeg?auto=compress&cs=tinysrgb&w=600', 'Sweet, vibrant bunch of Dutch carrots with fresh green tops.'),
(9,  'potatoes',       'Washed White Potatoes 2kg',             'Produce', 'Local Grown',    4.90, NULL, 0, 35, 10, 'https://images.unsplash.com/photo-1518977676601-b53f82aba655?w=600&q=80', 'All-rounder potatoes suitable for mashing, baking, or roasting.'),
(10, 'watermelon',     'Seedless Red Watermelon Quarter',       'Produce', 'Summer Special', 3.80, 5.20, 1, 20, 10, 'https://images.unsplash.com/photo-1587049352846-4a222e784d38?w=600&q=80', 'Juicy and refreshing seedless red watermelon.'),
-- BAKERY (11–20)
(11, 'sourdough',      'Country Sourdough Loaf',                'Bakery', 'Special Deal',    5.90, 7.50, 1, 18, 10, 'https://images.pexels.com/photos/33972459/pexels-photo-33972459.jpeg?auto=compress&cs=tinysrgb&w=600', 'Slow-fermented 48-hour sourdough baked fresh each morning.'),
(12, 'croissant',      'French Butter Croissants 4pk',          'Bakery', 'Flaky & Buttery', 6.50, NULL, 0, 22, 10, 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=600&q=80', 'Traditional layered French butter croissants with golden flaky crust.'),
(13, 'ciabatta',       'Artisan Olive Oil Ciabatta',            'Bakery', 'Stone Baked',     5.20, NULL, 0, 16, 10, 'https://images.pexels.com/photos/5425885/pexels-photo-5425885.jpeg?auto=compress&cs=tinysrgb&w=600', 'Crusty Italian ciabatta loaf with an airy, chewy crumb.'),
(14, 'wholemeal-bread','Wholemeal Sandwich Bread 700g',         'Bakery', 'High Fibre',      4.20, NULL, 0, 30, 10, 'https://images.unsplash.com/photo-1549931319-a545dcf3bc73?w=600&q=80', 'Soft, nourishing wholemeal sliced bread for daily sandwiches.'),
(15, 'brioche-buns',   'Gourmet Brioche Burger Buns 4pk',       'Bakery', 'Golden Glaze',    4.80, NULL, 0, 25, 10, 'https://images.pexels.com/photos/8859762/pexels-photo-8859762.jpeg?auto=compress&cs=tinysrgb&w=600', 'Enriched, slightly sweet golden brioche buns, perfect for burgers.'),
(16, 'pain-chocolat',  'Pain au Chocolat 4pk',                  'Bakery', '20% OFF',         6.20, 7.80, 1, 20, 10, 'https://images.pexels.com/photos/20009435/pexels-photo-20009435.jpeg?auto=compress&cs=tinysrgb&w=600', 'Flaky French pastry filled with rich Belgian dark chocolate batons.'),
(17, 'blueberry-muffins','Blueberry Crumble Muffins 4pk',      'Bakery', 'Freshly Baked',   5.90, NULL, 0, 20, 10, 'https://images.pexels.com/photos/7935281/pexels-photo-7935281.jpeg?auto=compress&cs=tinysrgb&w=600', 'Moist vanilla muffins loaded with bursting blueberries.'),
(18, 'baguette',       'Traditional French Baguette',           'Bakery', 'Artisan',         3.50, NULL, 0, 25, 10, 'https://images.pexels.com/photos/28636290/pexels-photo-28636290.jpeg?auto=compress&cs=tinysrgb&w=600', 'Crisp crust and tender inside, baked fresh twice daily.'),
(19, 'plain-bagels',   'New York Plain Bagels 4pk',             'Bakery', 'Boiled & Baked',  4.50, NULL, 0, 24, 10, 'https://images.pexels.com/photos/3957500/pexels-photo-3957500.jpeg?auto=compress&cs=tinysrgb&w=600', 'Chewy, authentic New York style boiled bagels.'),
(20, 'focaccia',       'Rosemary & Sea Salt Focaccia',          'Bakery', 'Extra Virgin Olive Oil', 6.20, NULL, 0, 15, 10, 'https://images.pexels.com/photos/18453904/pexels-photo-18453904.jpeg?auto=compress&cs=tinysrgb&w=600', 'Thick Italian focaccia dimpled with fresh rosemary and olive oil.'),
-- MEAT & SEAFOOD (21–30)
(21, 'salmon',         'Atlantic Salmon Fillet 500g',           'Meat & Seafood', 'Save $3.40',        15.50, 18.90, 1, 12, 10, 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=600&q=80', 'Sustainably wild-caught salmon, filleted daily.'),
(22, 'chicken-breast', 'Free-Range Chicken Breast 1kg',         'Meat & Seafood', '100% Australian',   13.90, NULL,  0, 30, 10, 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?w=600&q=80', 'Tender, skinless free-range chicken breast fillets.'),
(23, 'ribeye-steak',   'Grass-Fed Angus Ribeye Steak 350g',     'Meat & Seafood', 'Prime Cut',         16.50, NULL,  0, 14, 10, 'https://images.pexels.com/photos/29095929/pexels-photo-29095929.jpeg?auto=compress&cs=tinysrgb&w=600', 'Well-marbled Angus beef ribeye, tender and rich in flavour.'),
(24, 'beef-mince',     'Premium Lean Beef Mince 500g',          'Meat & Seafood', '90% Lean',          8.50,  NULL,  0, 35, 10, 'https://images.pexels.com/photos/128401/pexels-photo-128401.jpeg?auto=compress&cs=tinysrgb&w=600', 'Quality Australian ground beef for bolognese and tacos.'),
(25, 'tiger-prawns',   'Queensland Tiger Prawns 500g',          'Meat & Seafood', 'Special Catch',     18.90, 23.50, 1, 16, 10, 'https://images.unsplash.com/photo-1565680018434-b513d5e5fd47?w=600&q=80', 'Sweet, firm wild-caught Queensland tiger prawns.'),
(26, 'pork-chops',     'Free-Range Pork Loin Chops 500g',       'Meat & Seafood', 'Tender Cut',        9.80,  NULL,  0, 20, 10, 'https://images.pexels.com/photos/7333266/pexels-photo-7333266.jpeg?auto=compress&cs=tinysrgb&w=600', 'Succulent pork chops with rind for crispy crackling.'),
(27, 'lamb-cutlets',   'Australian Grass-Fed Lamb Cutlets 6pk', 'Meat & Seafood', 'Gourmet Lamb',      22.00, NULL,  0, 15, 10, 'https://images.pexels.com/photos/4411696/pexels-photo-4411696.jpeg?auto=compress&cs=tinysrgb&w=600', 'French-trimmed tender lamb cutlets, tender and flavorful.'),
(28, 'wagyu-burgers',  'Wagyu Beef Burger Patties 4pk',         'Meat & Seafood', '20% OFF',           10.50, 13.20, 1, 22, 10, 'https://images.unsplash.com/photo-1586190848861-99aa4a171e90?w=600&q=80', 'High-marbled Wagyu beef patties for juicy gourmet burgers.'),
(29, 'whole-chicken',  'Whole Free-Range Roaster Chicken 1.6kg','Meat & Seafood', 'Roast Ready',       12.50, NULL,  0, 18, 10, 'https://images.pexels.com/photos/7900353/pexels-photo-7900353.jpeg?auto=compress&cs=tinysrgb&w=600', 'Fresh whole free-range chicken ready for Sunday family roasts.'),
(30, 'streaky-bacon',  'Naturally Smoked Streaky Bacon 250g',   'Meat & Seafood', 'Naturally Smoked',  5.90,  NULL,  0, 30, 10, 'https://images.pexels.com/photos/4110373/pexels-photo-4110373.jpeg?auto=compress&cs=tinysrgb&w=600', 'Crisp-frying rindless streaky bacon cured with natural woodsmoke.'),
-- DAIRY (31–40)
(31, 'eggs',           'Free-Range Large Eggs 12pk',            'Dairy', 'Farm Fresh',       8.40, NULL, 0, 35, 10, 'https://images.unsplash.com/photo-1518569656558-1f25e69d93d7?w=600&q=80', 'Free-range eggs, collected daily from pasture-raised hens.'),
(32, 'feta',           'Artisan Greek Feta Cheese 200g',        'Dairy', 'Special Sale',     6.20, 8.50, 1, 24, 10, 'https://images.unsplash.com/photo-1559561853-08451507cbe7?w=600&q=80', 'Authentic barrel-aged sheep and goat milk feta cheese.'),
(33, 'full-cream-milk','Organic Full Cream Farm Milk 2L',       'Dairy', '100% Organic',     4.60, NULL, 0, 45, 10, 'https://images.unsplash.com/photo-1550583724-b2692b85b150?w=600&q=80', 'Creamy, unhomogenised pasture-raised dairy milk.'),
(34, 'salted-butter',  'Cultured Salted Butter 250g',           'Dairy', 'European Style',   5.20, NULL, 0, 32, 10, 'https://images.unsplash.com/photo-1589985270826-4b7bb135bc9d?w=600&q=80', 'Slow-churned cultured cream butter with sea salt crystals.'),
(35, 'greek-yoghurt',  'Authentic Greek Strained Yoghurt 1kg',  'Dairy', '22% OFF',          6.90, 8.90, 1, 30, 10, 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=600&q=80', 'Thick, high-protein traditional pot-set Greek yoghurt.'),
(36, 'heavy-cream',    'Pure Thick Whipping Cream 300ml',       'Dairy', '35% Milk Fat',     3.80, NULL, 0, 26, 10, 'https://images.pexels.com/photos/6641182/pexels-photo-6641182.jpeg?auto=compress&cs=tinysrgb&w=600', 'Rich dollop cream that whips into billowy peaks easily.'),
(37, 'vintage-cheddar','Aged Vintage Cheddar Block 250g',       'Dairy', '18-Month Aged',    6.80, NULL, 0, 25, 10, 'https://images.unsplash.com/photo-1618164436241-4473940d1f5c?w=600&q=80', 'Sharp, crumbly aged cheddar with delightful calcium crystals.'),
(38, 'mozzarella-ball','Fresh Fior di Latte Mozzarella 200g',   'Dairy', 'Artisan Italian',  5.50, NULL, 0, 20, 10, 'https://images.pexels.com/photos/5589028/pexels-photo-5589028.jpeg?auto=compress&cs=tinysrgb&w=600', 'Soft, milky fresh mozzarella ball soaked in brine for pizza & caprese.'),
(39, 'parmesan',       'Parmigiano Reggiano Wedge 200g',        'Dairy', 'DOP Certified',    9.50, NULL, 0, 20, 10, 'https://images.pexels.com/photos/34037769/pexels-photo-34037769.jpeg?auto=compress&cs=tinysrgb&w=600', '24-month matured authentic Italian parmesan cheese.'),
(40, 'oat-milk',       'Oat Milk Barista Edition 1L',           'Dairy', 'Froths Perfectly', 3.80, NULL, 0, 40, 10, 'https://images.pexels.com/photos/29906263/pexels-photo-29906263.jpeg?auto=compress&cs=tinysrgb&w=600', 'Velvety plant milk designed for micro-foaming specialty coffee.'),
-- BEVERAGES (41–50)
(41, 'oil',            'Extra Virgin Olive Oil 750ml',               'Beverages', 'Cold Pressed',       19.50, NULL,  0, 33, 10, 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=600&q=80', 'First cold-pressed olive oil from a single estate grove.'),
(42, 'oj',             'Fresh Cold-Pressed Orange Juice 1L',         'Beverages', '100% Squeezed',      4.90,  NULL,  0, 30, 10, 'https://images.unsplash.com/photo-1613478223719-2ab802602423?w=600&q=80', 'Pure unpasteurised orange juice with juicy pulp, no added sugar.'),
(43, 'green-juice',    'Cold-Pressed Green Detox Juice 750ml',       'Beverages', '25% OFF',            5.20,  6.90,  1, 20, 10, 'https://images.pexels.com/photos/12433988/pexels-photo-12433988.jpeg?auto=compress&cs=tinysrgb&w=600', 'Cold-pressed apple, cucumber, celery, kale, lemon, and mint.'),
(44, 'sparkling-water','Natural Mineral Water Sparkling 1.25L',      'Beverages', 'Spring Sourced',     2.50,  NULL,  0, 45, 10, 'https://images.pexels.com/photos/12987478/pexels-photo-12987478.jpeg?auto=compress&cs=tinysrgb&w=600', 'Crisp effervescent mineral water from natural underground springs.'),
(45, 'ginger-beer',    'Craft Brewed Spiced Ginger Beer 4pk',        'Beverages', 'Naturally Brewed',   7.80,  NULL,  0, 22, 10, 'https://images.pexels.com/photos/8234585/pexels-photo-8234585.jpeg?auto=compress&cs=tinysrgb&w=600', 'Traditional fermented ginger beer with spicy fiery kick.'),
(46, 'kombucha-ginger','Organic Ginger Lemon Kombucha 330ml',        'Beverages', 'Live Probiotics',    3.90,  NULL,  0, 28, 10, 'https://images.pexels.com/photos/38466020/pexels-photo-38466020.jpeg?auto=compress&cs=tinysrgb&w=600', 'Sparkling fermented green tea with fresh pressed ginger juice.'),
(47, 'coconut-water',  '100% Pure Organic Coconut Water 1L',         'Beverages', 'Naturally Hydrating',4.80,  NULL,  0, 32, 10, 'https://images.pexels.com/photos/15020644/pexels-photo-15020644.jpeg?auto=compress&cs=tinysrgb&w=600', 'Hydrating young green coconut water rich in electrolytes.'),
(48, 'coffee-beans',   'Single Origin Ethiopian Coffee Beans 500g',  'Beverages', 'Specialty Roast',   17.50, NULL,  0, 20, 10, 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?w=600&q=80', 'Light-medium roast with floral jasmine and bergamot tasting notes.'),
(49, 'english-tea',    'Organic English Breakfast Tea 50pk',          'Beverages', 'Organic Leaf',       6.20,  NULL,  0, 30, 10, 'https://images.unsplash.com/photo-1576092768241-dec231879fc3?w=600&q=80', 'Full-bodied blend of Assam and Ceylon black tea leaves.'),
(50, 'maple-syrup',    '100% Pure Canadian Maple Syrup Grade A 250ml','Beverages', 'Save $3.00',        8.90,  11.90, 1, 20, 10, 'https://images.pexels.com/photos/17052506/pexels-photo-17052506.jpeg?auto=compress&cs=tinysrgb&w=600', 'Amber rich Canadian maple syrup tapped from sugar maples.');

-- Seed Orders
INSERT INTO orders (id, user_id, customer_name, contact_number, delivery_address, delivery_instructions, payment_method, subtotal, tax, total, status, driver, created_at) VALUES
(2048, 4, 'Emma Wilson', '0412 345 678', '42 Riverside Drive, Parramatta NSW 2150', 'Leave with concierge if not home.', 'cash_on_delivery', 13.38, 1.34, 14.72, 'out_for_delivery', 'Chris Allen', '2026-08-20 09:15:00'),
(2049, 5, 'Noah Brown', '0433 221 998', '8 Harbord Street, Marrickville NSW 2204', 'Ring the doorbell twice.', 'credit_card', 26.40, 2.64, 29.04, 'processing', 'Jordan Lee', '2026-08-21 10:02:00'),
(2050, 4, 'Emma Wilson', '0412 345 678', '42 Riverside Drive, Parramatta NSW 2150', '', 'paypal', 25.80, 2.58, 28.38, 'delivered', 'Chris Allen', '2026-08-19 14:30:00'),
(2051, 5, 'Noah Brown', '0433 221 998', '8 Harbord Street, Marrickville NSW 2204', '', 'cash_on_delivery', 9.99, 1.00, 10.99, 'pending', NULL, '2026-08-22 08:45:00');

-- Seed Order Items
INSERT INTO order_items (id, order_id, product_id, name, price, quantity) VALUES
(1, 2048, 1, 'Organic Hass Avocados', 2.49, 2),
(2, 2048, 46, 'Free-Range Large Eggs 12pk', 8.40, 1),
(3, 2049, 31, 'Atlantic Salmon Fillet 500g', 15.50, 1),
(4, 2049, 16, 'Country Sourdough Loaf', 5.90, 1),
(5, 2050, 3, 'Organic Blueberries 125g', 4.99, 1),
(6, 2050, 61, 'Extra Virgin Olive Oil 750ml', 19.50, 1),
(7, 2051, 1, 'Organic Hass Avocados', 2.49, 4);

-- Seed Feedback
INSERT INTO feedback (id, order_id, user_id, customer_name, rating, comment, sentiment, created_at) VALUES
(1, 2050, 4, 'Emma Wilson', 5, 'Exceptional quality avocados and olive oil! Delivery arrived right on time.', 'positive', '2026-08-19 16:00:00');

-- 8. PRODUCT_NUTRITION TABLE (added 2026-09-29)
-- Per-product nutrition panel, ingredients, allergens, storage and origin.
-- Generated from includes/nutrition_data.php - keep the two in step.
CREATE TABLE product_nutrition (
    product_id INT PRIMARY KEY,
    serving VARCHAR(60) NULL,
    energy_kcal DECIMAL(8,2) NULL,
    energy_kj DECIMAL(8,2) NULL,
    protein_g DECIMAL(8,2) NULL,
    fat_g DECIMAL(8,2) NULL,
    saturated_fat_g DECIMAL(8,2) NULL,
    carbohydrates_g DECIMAL(8,2) NULL,
    sugars_g DECIMAL(8,2) NULL,
    fibre_g DECIMAL(8,2) NULL,
    sodium_mg DECIMAL(8,2) NULL,
    ingredients TEXT NULL,
    allergens VARCHAR(200) NULL,
    storage VARCHAR(255) NULL,
    origin VARCHAR(120) NULL,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- Seed Product Nutrition
INSERT INTO product_nutrition (product_id, serving, energy_kcal, energy_kj, protein_g, fat_g,
                             saturated_fat_g, carbohydrates_g, sugars_g, fibre_g, sodium_mg,
                             ingredients, allergens, storage, origin) VALUES
(1, 'per 100 g', 160.00, 669.4, 2.00, 14.70, 2.10, 8.50, 0.70, 6.70, 7.0, 'Hass avocados.', 'None', 'Refrigerate once ripe to slow browning.', 'Queensland, Australia'),
(2, 'per 100 g', 32.00, 133.9, 0.70, 0.30, 0.00, 7.70, 6.00, 2.00, 1.0, 'Australian strawberries.', 'None', 'Refrigerate and use within 3 days.', 'Australia'),
(3, 'per 100 g', 89.00, 372.4, 1.10, 0.30, 0.10, 22.80, 12.20, 2.60, 1.0, 'Cavendish bananas.', 'None', 'Store at room temperature away from sunlight.', 'Queensland, Australia'),
(5, 'per 100 g', 14.00, 58.6, 1.20, 0.20, 0.00, 2.90, 1.40, 1.20, 10.0, 'Iceberg lettuce.', 'None', 'Refrigerate in the crisper drawer; wash before use.', 'Australia'),
(6, 'per 100 g', 23.00, 96.2, 2.90, 0.40, 0.10, 3.60, 0.40, 2.20, 79.0, 'Organic baby spinach leaves.', 'None', 'Refrigerate and use within 3 days.', 'Australia'),
(7, 'per 100 g', 18.00, 75.3, 0.90, 0.20, 0.00, 3.90, 2.60, 1.20, 5.0, 'Roma tomatoes.', 'None', 'Refrigerate for freshness; do not chill below 10C.', 'Australia'),
(8, 'per 100 g', 41.00, 171.5, 0.90, 0.20, 0.00, 9.60, 4.70, 2.80, 70.0, 'Dutch baby carrots with tops.', 'None', 'Refrigerate in a bag; remove tops before cooking.', 'Australia'),
(9, 'per 100 g', 77.00, 322.2, 2.00, 0.10, 0.00, 17.50, 0.80, 2.20, 6.0, 'Washed white potatoes.', 'None', 'Store in a cool, dark, well-ventilated place.', 'Australia'),
(10, 'per 100 g', 30.00, 125.5, 0.60, 0.20, 0.00, 7.60, 6.20, 0.40, 1.0, 'Seedless red watermelon.', 'None', 'Refrigerate once cut; consume within 3 days.', 'Australia'),
(11, 'per 100 g', 246.00, 1029.3, 9.00, 1.60, 0.30, 48.00, 2.70, 2.70, 480.0, 'Stoneground wheat flour, water, sourdough culture, sea salt.', 'Gluten (wheat)', 'Keep at room temperature in the paper bag; slice only when needed.', 'Baked in Australia'),
(12, 'per 100 g', 406.00, 1698.7, 8.20, 21.00, 13.00, 45.80, 5.70, 1.80, 320.0, 'Wheat flour, butter, milk, sugar, egg, yeast, salt.', 'Gluten (wheat), Milk, Egg', 'Store in a sealed bag and consume same day; freeze up to 1 month.', 'Baked in Australia'),
(13, 'per 100 g', 262.00, 1096.2, 8.60, 3.40, 0.50, 48.90, 2.20, 2.40, 450.0, 'Wheat flour, extra virgin olive oil, water, salt, yeast.', 'Gluten (wheat)', 'Store in the paper bag; refresh in the oven for 5 minutes.', 'Baked in Australia'),
(14, 'per 100 g', 243.00, 1016.7, 9.40, 3.30, 0.60, 43.50, 4.10, 6.50, 450.0, 'Wholemeal wheat flour (98%), water, yeast, salt, wheat bran.', 'Gluten (wheat)', 'Store sealed at room temperature; freeze to extend life.', 'Baked in Australia'),
(15, 'per 100 g', 316.00, 1322.1, 9.40, 11.60, 5.60, 42.50, 9.20, 1.60, 340.0, 'Wheat flour, butter, milk, egg, sugar, yeast, salt.', 'Gluten (wheat), Milk, Egg', 'Freeze after 2 days; thaw at room temperature.', 'Baked in Australia'),
(16, 'per 100 g', 375.00, 1569.0, 7.10, 17.50, 10.20, 46.50, 22.50, 2.20, 300.0, 'Wheat flour, butter, dark chocolate (30%), sugar, egg, yeast.', 'Gluten (wheat), Milk, Egg, Soy', 'Consume same day; freeze up to 1 month.', 'Baked in Australia'),
(17, 'per 100 g', 385.00, 1610.8, 5.20, 17.80, 9.10, 48.60, 27.40, 1.60, 260.0, 'Wheat flour, butter, milk, sugar, blueberries, egg.', 'Gluten (wheat), Milk, Egg', 'Store in an airtight container for up to 3 days.', 'Baked in Australia'),
(18, 'per 100 g', 274.00, 1146.4, 8.90, 1.30, 0.30, 56.50, 1.80, 2.40, 470.0, 'Wheat flour, water, salt, yeast.', 'Gluten (wheat)', 'Best eaten the same day; crisp again in the oven.', 'Baked in Australia'),
(19, 'per 100 g', 257.00, 1075.3, 10.10, 1.50, 0.20, 50.90, 5.20, 2.10, 520.0, 'Wheat flour, water, salt, yeast.', 'Gluten (wheat)', 'Freeze for up to 3 months; refresh before serving.', 'Baked in Australia'),
(20, 'per 100 g', 280.00, 1171.5, 8.00, 8.50, 1.20, 40.50, 2.10, 2.20, 560.0, 'Wheat flour, extra virgin olive oil, rosemary, sea salt, yeast.', 'Gluten (wheat)', 'Store in a sealed container for up to 2 days.', 'Baked in Australia'),
(21, 'per 100 g', 208.00, 870.3, 20.40, 13.40, 3.10, 0.00, 0.00, 0.00, 59.0, 'Atlantic salmon (100%).', 'Fish', 'Keep refrigerated below 5C and use within 2 days, or freeze.', 'Norway / Australia'),
(22, 'per 100 g', 110.00, 460.2, 23.00, 1.90, 0.50, 0.00, 0.00, 0.00, 45.0, 'Free-range chicken breast (100%).', 'None', 'Keep refrigerated below 5C; use within 3 days or freeze.', 'Australia'),
(23, 'per 100 g', 250.00, 1046.0, 26.00, 16.50, 7.20, 0.00, 0.00, 0.00, 55.0, 'Grass-fed Angus beef (100%).', 'None', 'Keep refrigerated below 5C; mature further in the fridge if desired.', 'Australia'),
(24, 'per 100 g', 152.00, 636.0, 21.50, 6.40, 2.60, 0.00, 0.00, 0.00, 62.0, 'Lean Australian beef (100%).', 'None', 'Keep refrigerated below 5C; use within 2 days or freeze.', 'Australia'),
(25, 'per 100 g', 99.00, 414.2, 20.90, 1.10, 0.30, 0.20, 0.00, 0.00, 111.0, 'Tiger prawns (100%).', 'Crustacean (shellfish)', 'Keep refrigerated on ice and use within 1 day; can be frozen raw.', 'Queensland, Australia'),
(26, 'per 100 g', 143.00, 598.3, 21.80, 5.90, 2.00, 0.00, 0.00, 0.00, 48.0, 'Free-range pork loin (100%).', 'None', 'Keep refrigerated below 5C; use within 3 days or freeze.', 'Australia'),
(27, 'per 100 g', 294.00, 1230.1, 25.00, 20.50, 9.80, 0.00, 0.00, 0.00, 70.0, 'Grass-fed lamb (100%).', 'None', 'Keep refrigerated below 5C; rest 10 minutes before cooking.', 'Australia'),
(28, 'per 100 g', 195.00, 815.9, 19.50, 12.50, 5.10, 0.60, 0.20, 0.00, 68.0, 'Wagyu beef (85%), water, salt, black pepper.', 'None', 'Keep refrigerated below 5C; cook thoroughly; do not refreeze once cooked.', 'Australia'),
(29, 'per 100 g', 170.00, 711.3, 19.00, 10.90, 3.10, 0.00, 0.00, 0.00, 60.0, 'Free-range whole chicken (100%).', 'None', 'Keep refrigerated below 5C; use within 3 days or freeze.', 'Australia'),
(30, 'per 100 g', 118.00, 493.7, 17.00, 5.50, 1.80, 0.20, 0.10, 0.00, 1100.0, 'Pork, sea salt, sodium nitrite, hardwood smoke.', 'None', 'Keep refrigerated below 5C; use within 7 days of opening.', 'Australia'),
(31, 'per 100 g (about 2 large eggs)', 143.00, 598.3, 12.60, 9.50, 3.10, 0.70, 0.40, 0.00, 142.0, 'Free-range eggs (100%).', 'Egg', 'Refrigerate in the carton; do not wash before storing.', 'Australia'),
(32, 'per 100 g', 264.00, 1104.6, 14.20, 21.30, 15.00, 4.10, 4.10, 0.00, 1100.0, 'Sheep milk, goat milk, salt, live cultures.', 'Milk', 'Refrigerate once opened and use within 7 days.', 'Greece'),
(33, 'per 100 ml', 62.00, 259.4, 3.40, 3.50, 2.20, 4.80, 4.80, 0.00, 38.0, 'Organic whole milk (100%).', 'Milk', 'Keep refrigerated below 5C; shake well before use.', 'Australia'),
(34, 'per 100 g', 717.00, 2999.9, 0.90, 81.10, 51.40, 0.10, 0.10, 0.00, 90.0, 'Cream, milk cultures, sea salt.', 'Milk', 'Refrigerate; suitable for freezing for butter-making.', 'Australia'),
(35, 'per 100 g', 97.00, 405.8, 9.00, 5.00, 3.20, 3.60, 3.60, 0.00, 36.0, 'Milk, live yoghurt cultures.', 'Milk', 'Keep refrigerated below 5C; consume within the use-by date.', 'Australia'),
(36, 'per 100 ml', 340.00, 1422.6, 2.10, 36.10, 23.70, 2.80, 2.80, 0.00, 27.0, 'Cream (100%).', 'Milk', 'Refrigerate below 5C; use within 3 days of opening.', 'Australia'),
(37, 'per 100 g', 402.00, 1682.0, 25.70, 33.10, 21.40, 1.30, 0.50, 0.00, 620.0, 'Cow milk, salt, starter cultures, vegetable-based rennet.', 'Milk', 'Cut and wrap airtight; refrigerate and use within 4 weeks.', 'Australia'),
(38, 'per 100 g', 300.00, 1255.2, 22.00, 22.40, 16.00, 2.20, 2.00, 0.00, 620.0, 'Cow milk, salt, citric acid, cultures, rennet.', 'Milk', 'Keep submerged in brine in the fridge; use within 7 days.', 'Australia'),
(39, 'per 100 g', 392.00, 1640.1, 35.80, 25.80, 16.40, 3.20, 0.50, 0.00, 1800.0, 'Raw cow milk, salt, rennet.', 'Milk', 'Refrigerate after cutting; wrap airtight and use within 4 weeks.', 'Italy'),
(40, 'per 100 ml', 46.00, 192.5, 0.80, 1.50, 0.10, 6.70, 4.20, 0.80, 42.0, 'Water, oats (gluten-free), rapeseed oil, sea salt, calcium carbonate.', 'Gluten (oats)', 'Shake well before pouring; refrigerate once opened and use within 5 days.', 'Australia'),
(41, 'per 100 ml', 884.00, 3698.7, 0.00, 100.00, 13.80, 0.00, 0.00, 0.00, 2.0, 'Extra virgin olive oil (100%).', 'None', 'Store in a dark place away from heat; use within 12 months of opening.', 'Single estate grove'),
(42, 'per 100 ml', 45.00, 188.3, 0.70, 0.20, 0.00, 10.40, 8.40, 0.20, 1.0, 'Orange juice (100%), nothing else.', 'None', 'Refrigerate below 5C; shake well; use within 3 days of opening.', 'Australia'),
(43, 'per 100 ml', 38.00, 159.0, 0.90, 0.10, 0.00, 8.10, 6.20, 0.70, 6.0, 'Apple, cucumber, celery, kale, lemon, mint.', 'None', 'Refrigerate below 5C and consume within 3 days of pressing.', 'Australia'),
(44, 'per 100 ml', 0.00, 0.0, 0.00, 0.00, 0.00, 0.00, 0.00, 0.00, 25.0, 'Natural mineral water.', 'None', 'Store in a cool, dry place; chilled is best.', 'Natural underground springs'),
(45, 'per 100 ml', 42.00, 175.7, 0.00, 0.00, 0.00, 10.40, 9.80, 0.00, 8.0, 'Carbonated water, cane sugar, ginger extract, spices, citric acid.', 'None (may contain sulfites)', 'Store in a cool place; refrigerate after opening.', 'Australia'),
(46, 'per 100 ml', 18.00, 75.3, 0.00, 0.00, 0.00, 4.40, 3.90, 0.00, 5.0, 'Water, organic green tea, ginger juice, lemon, live cultures.', 'None', 'Refrigerate below 5C; consume within the use-by date.', 'Australia'),
(47, 'per 100 ml', 19.00, 79.5, 0.70, 0.20, 0.20, 3.70, 2.60, 0.50, 105.0, 'Young coconut water (100%).', 'None', 'Refrigerate below 5C; consume within 3 days of opening.', 'Sri Lanka'),
(48, 'per 100 g', 200.00, 836.8, 13.90, 0.20, 0.00, 41.00, 0.30, 10.50, 5.0, '100% Arabica coffee beans.', 'None (contains caffeine)', 'Store airtight, cool and dark; grind just before brewing.', 'Ethiopia'),
(49, 'per 100 g brewed infusion', 1.00, 4.2, 0.00, 0.00, 0.00, 0.20, 0.00, 0.00, 3.0, 'Organic Assam and Ceylon black tea leaves.', 'None (contains caffeine)', 'Store sealed, cool and dry, away from strong odours.', 'Sri Lanka / India'),
(50, 'per 100 ml', 260.00, 1087.8, 0.00, 0.10, 0.10, 67.00, 60.50, 0.00, 12.0, '100% pure Canadian maple syrup.', 'None', 'Refrigerate after opening; no refrigeration needed before.', 'Canada');
