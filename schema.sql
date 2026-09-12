-- ====================================================================
-- Maxi Fine Foods — Online Grocery Management System Database Schema
-- Database: `maxi_fine_foods`
-- Designed for CPRO306 Capstone Project / XAMPP phpMyAdmin & MySQL
-- ====================================================================

-- Drop in foreign-key dependency order
DROP TABLE IF EXISTS feedback;
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

-- 6. FEEDBACK TABLE (SRS FR37, FR47)
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
(5,  'iceberg-lettuce','Crisp Iceberg Lettuce',                 'Produce', 'Farm Fresh',     2.50, NULL, 0, 30, 10, 'https://images.unsplash.com/photo-1556881286-fc6915169721?w=600&q=80', 'Firm, crunchy iceberg lettuce perfect for fresh garden salads.'),
(6,  'baby-spinach',   'Organic Baby Spinach 200g',             'Produce', 'Organic',        3.90, NULL, 0, 24, 10, 'https://images.unsplash.com/photo-1576045057995-568f588f82fb?w=600&q=80', 'Tender washed organic baby spinach leaves, rich in iron.'),
(7,  'roma-tomatoes',  'Gourmet Roma Tomatoes 500g',            'Produce', 'Vine Ripened',   3.20, NULL, 0, 35, 10, 'https://images.unsplash.com/photo-1592924357228-91a4daadcfea?w=600&q=80', 'Sweet, flavourful Roma tomatoes ideal for sauces and slicing.'),
(8,  'dutch-carrots',  'Dutch Baby Carrots Bunch',              'Produce', 'Farm Fresh',     3.50, NULL, 0, 22, 10, 'https://images.unsplash.com/photo-1598170845058-32b9d6a5c317?w=600&q=80', 'Sweet, vibrant bunch of Dutch carrots with fresh green tops.'),
(9,  'potatoes',       'Washed White Potatoes 2kg',             'Produce', 'Local Grown',    4.90, NULL, 0, 35, 10, 'https://images.unsplash.com/photo-1518977676601-b53f82aba655?w=600&q=80', 'All-rounder potatoes suitable for mashing, baking, or roasting.'),
(10, 'watermelon',     'Seedless Red Watermelon Quarter',       'Produce', 'Summer Special', 3.80, 5.20, 1, 20, 10, 'https://images.unsplash.com/photo-1587049352846-4a222e784d38?w=600&q=80', 'Juicy and refreshing seedless red watermelon.'),
-- BAKERY (11–20)
(11, 'sourdough',      'Country Sourdough Loaf',                'Bakery', 'Special Deal',    5.90, 7.50, 1, 18, 10, 'https://images.unsplash.com/photo-1585478259715-4d3a5d7f3b8b?w=600&q=80', 'Slow-fermented 48-hour sourdough baked fresh each morning.'),
(12, 'croissant',      'French Butter Croissants 4pk',          'Bakery', 'Flaky & Buttery', 6.50, NULL, 0, 22, 10, 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=600&q=80', 'Traditional layered French butter croissants with golden flaky crust.'),
(13, 'ciabatta',       'Artisan Olive Oil Ciabatta',            'Bakery', 'Stone Baked',     5.20, NULL, 0, 16, 10, 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=600&q=80', 'Crusty Italian ciabatta loaf with an airy, chewy crumb.'),
(14, 'wholemeal-bread','Wholemeal Sandwich Bread 700g',         'Bakery', 'High Fibre',      4.20, NULL, 0, 30, 10, 'https://images.unsplash.com/photo-1549931319-a545dcf3bc73?w=600&q=80', 'Soft, nourishing wholemeal sliced bread for daily sandwiches.'),
(15, 'brioche-buns',   'Gourmet Brioche Burger Buns 4pk',       'Bakery', 'Golden Glaze',    4.80, NULL, 0, 25, 10, 'https://images.unsplash.com/photo-1586190848861-99aa4a171e90?w=600&q=80', 'Enriched, slightly sweet golden brioche buns, perfect for burgers.'),
(16, 'pain-chocolat',  'Pain au Chocolat 4pk',                  'Bakery', '20% OFF',         6.20, 7.80, 1, 20, 10, 'https://images.unsplash.com/photo-1608198093002-ad4e005484ec?w=600&q=80', 'Flaky French pastry filled with rich Belgian dark chocolate batons.'),
(17, 'blueberry-muffins','Blueberry Crumble Muffins 4pk',      'Bakery', 'Freshly Baked',   5.90, NULL, 0, 20, 10, 'https://images.unsplash.com/photo-1586985289688-ca3cf47d3e6e?w=600&q=80', 'Moist vanilla muffins loaded with bursting blueberries.'),
(18, 'baguette',       'Traditional French Baguette',           'Bakery', 'Artisan',         3.50, NULL, 0, 25, 10, 'https://images.unsplash.com/photo-1589367920969-ab8e050bbb04?w=600&q=80', 'Crisp crust and tender inside, baked fresh twice daily.'),
(19, 'plain-bagels',   'New York Plain Bagels 4pk',             'Bakery', 'Boiled & Baked',  4.50, NULL, 0, 24, 10, 'https://images.unsplash.com/photo-1585478259715-4d3a5d7f3b8b?w=600&q=80', 'Chewy, authentic New York style boiled bagels.'),
(20, 'focaccia',       'Rosemary & Sea Salt Focaccia',          'Bakery', 'Extra Virgin Olive Oil', 6.20, NULL, 0, 15, 10, 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=600&q=80', 'Thick Italian focaccia dimpled with fresh rosemary and olive oil.'),
-- MEAT & SEAFOOD (21–30)
(21, 'salmon',         'Atlantic Salmon Fillet 500g',           'Meat & Seafood', 'Save $3.40',        15.50, 18.90, 1, 12, 10, 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=600&q=80', 'Sustainably wild-caught salmon, filleted daily.'),
(22, 'chicken-breast', 'Free-Range Chicken Breast 1kg',         'Meat & Seafood', '100% Australian',   13.90, NULL,  0, 30, 10, 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?w=600&q=80', 'Tender, skinless free-range chicken breast fillets.'),
(23, 'ribeye-steak',   'Grass-Fed Angus Ribeye Steak 350g',     'Meat & Seafood', 'Prime Cut',         16.50, NULL,  0, 14, 10, 'https://images.unsplash.com/photo-1603048588665-791ca8aea617?w=600&q=80', 'Well-marbled Angus beef ribeye, tender and rich in flavour.'),
(24, 'beef-mince',     'Premium Lean Beef Mince 500g',          'Meat & Seafood', '90% Lean',          8.50,  NULL,  0, 35, 10, 'https://images.unsplash.com/photo-1588168333986-5078d3ae3976?w=600&q=80', 'Quality Australian ground beef for bolognese and tacos.'),
(25, 'tiger-prawns',   'Queensland Tiger Prawns 500g',          'Meat & Seafood', 'Special Catch',     18.90, 23.50, 1, 16, 10, 'https://images.unsplash.com/photo-1565680018434-b513d5e5fd47?w=600&q=80', 'Sweet, firm wild-caught Queensland tiger prawns.'),
(26, 'pork-chops',     'Free-Range Pork Loin Chops 500g',       'Meat & Seafood', 'Tender Cut',        9.80,  NULL,  0, 20, 10, 'https://images.unsplash.com/photo-1607623814075-e51df1bdc82f?w=600&q=80', 'Succulent pork chops with rind for crispy crackling.'),
(27, 'lamb-cutlets',   'Australian Grass-Fed Lamb Cutlets 6pk', 'Meat & Seafood', 'Gourmet Lamb',      22.00, NULL,  0, 15, 10, 'https://images.unsplash.com/photo-1544025162-d76694265947?w=600&q=80', 'French-trimmed tender lamb cutlets, tender and flavorful.'),
(28, 'wagyu-burgers',  'Wagyu Beef Burger Patties 4pk',         'Meat & Seafood', '20% OFF',           10.50, 13.20, 1, 22, 10, 'https://images.unsplash.com/photo-1586190848861-99aa4a171e90?w=600&q=80', 'High-marbled Wagyu beef patties for juicy gourmet burgers.'),
(29, 'whole-chicken',  'Whole Free-Range Roaster Chicken 1.6kg','Meat & Seafood', 'Roast Ready',       12.50, NULL,  0, 18, 10, 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?w=600&q=80', 'Fresh whole free-range chicken ready for Sunday family roasts.'),
(30, 'streaky-bacon',  'Naturally Smoked Streaky Bacon 250g',   'Meat & Seafood', 'Naturally Smoked',  5.90,  NULL,  0, 30, 10, 'https://images.unsplash.com/photo-1607623814075-e51df1bdc82f?w=600&q=80', 'Crisp-frying rindless streaky bacon cured with natural woodsmoke.'),
-- DAIRY (31–40)
(31, 'eggs',           'Free-Range Large Eggs 12pk',            'Dairy', 'Farm Fresh',       8.40, NULL, 0, 35, 10, 'https://images.unsplash.com/photo-1518569656558-1f25e69d93d7?w=600&q=80', 'Free-range eggs, collected daily from pasture-raised hens.'),
(32, 'feta',           'Artisan Greek Feta Cheese 200g',        'Dairy', 'Special Sale',     6.20, 8.50, 1, 24, 10, 'https://images.unsplash.com/photo-1559561853-08451507cbe7?w=600&q=80', 'Authentic barrel-aged sheep and goat milk feta cheese.'),
(33, 'full-cream-milk','Organic Full Cream Farm Milk 2L',       'Dairy', '100% Organic',     4.60, NULL, 0, 45, 10, 'https://images.unsplash.com/photo-1550583724-b2692b85b150?w=600&q=80', 'Creamy, unhomogenised pasture-raised dairy milk.'),
(34, 'salted-butter',  'Cultured Salted Butter 250g',           'Dairy', 'European Style',   5.20, NULL, 0, 32, 10, 'https://images.unsplash.com/photo-1589985270826-4b7bb135bc9d?w=600&q=80', 'Slow-churned cultured cream butter with sea salt crystals.'),
(35, 'greek-yoghurt',  'Authentic Greek Strained Yoghurt 1kg',  'Dairy', '22% OFF',          6.90, 8.90, 1, 30, 10, 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=600&q=80', 'Thick, high-protein traditional pot-set Greek yoghurt.'),
(36, 'heavy-cream',    'Pure Thick Whipping Cream 300ml',       'Dairy', '35% Milk Fat',     3.80, NULL, 0, 26, 10, 'https://images.unsplash.com/photo-1550583724-b2692b85b150?w=600&q=80', 'Rich dollop cream that whips into billowy peaks easily.'),
(37, 'vintage-cheddar','Aged Vintage Cheddar Block 250g',       'Dairy', '18-Month Aged',    6.80, NULL, 0, 25, 10, 'https://images.unsplash.com/photo-1618164436241-4473940d1f5c?w=600&q=80', 'Sharp, crumbly aged cheddar with delightful calcium crystals.'),
(38, 'mozzarella-ball','Fresh Fior di Latte Mozzarella 200g',   'Dairy', 'Artisan Italian',  5.50, NULL, 0, 20, 10, 'https://images.unsplash.com/photo-1559561853-08451507cbe7?w=600&q=80', 'Soft, milky fresh mozzarella ball soaked in brine for pizza & caprese.'),
(39, 'parmesan',       'Parmigiano Reggiano Wedge 200g',        'Dairy', 'DOP Certified',    9.50, NULL, 0, 20, 10, 'https://images.unsplash.com/photo-1618164436241-4473940d1f5c?w=600&q=80', '24-month matured authentic Italian parmesan cheese.'),
(40, 'oat-milk',       'Oat Milk Barista Edition 1L',           'Dairy', 'Froths Perfectly', 3.80, NULL, 0, 40, 10, 'https://images.unsplash.com/photo-1550583724-b2692b85b150?w=600&q=80', 'Velvety plant milk designed for micro-foaming specialty coffee.'),
-- BEVERAGES (41–50)
(41, 'oil',            'Extra Virgin Olive Oil 750ml',               'Beverages', 'Cold Pressed',       19.50, NULL,  0, 33, 10, 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=600&q=80', 'First cold-pressed olive oil from a single estate grove.'),
(42, 'oj',             'Fresh Cold-Pressed Orange Juice 1L',         'Beverages', '100% Squeezed',      4.90,  NULL,  0, 30, 10, 'https://images.unsplash.com/photo-1613478223719-2ab802602423?w=600&q=80', 'Pure unpasteurised orange juice with juicy pulp, no added sugar.'),
(43, 'green-juice',    'Cold-Pressed Green Detox Juice 750ml',       'Beverages', '25% OFF',            5.20,  6.90,  1, 20, 10, 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=600&q=80', 'Cold-pressed apple, cucumber, celery, kale, lemon, and mint.'),
(44, 'sparkling-water','Natural Mineral Water Sparkling 1.25L',      'Beverages', 'Spring Sourced',     2.50,  NULL,  0, 45, 10, 'https://images.unsplash.com/photo-1548839140-29a749e1bc4e?w=600&q=80', 'Crisp effervescent mineral water from natural underground springs.'),
(45, 'ginger-beer',    'Craft Brewed Spiced Ginger Beer 4pk',        'Beverages', 'Naturally Brewed',   7.80,  NULL,  0, 22, 10, 'https://images.unsplash.com/photo-1527661591475-527312dd65f5?w=600&q=80', 'Traditional fermented ginger beer with spicy fiery kick.'),
(46, 'kombucha-ginger','Organic Ginger Lemon Kombucha 330ml',        'Beverages', 'Live Probiotics',    3.90,  NULL,  0, 28, 10, 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?w=600&q=80', 'Sparkling fermented green tea with fresh pressed ginger juice.'),
(47, 'coconut-water',  '100% Pure Organic Coconut Water 1L',         'Beverages', 'Naturally Hydrating',4.80,  NULL,  0, 32, 10, 'https://images.unsplash.com/photo-1525385133512-2f3bdd039054?w=600&q=80', 'Hydrating young green coconut water rich in electrolytes.'),
(48, 'coffee-beans',   'Single Origin Ethiopian Coffee Beans 500g',  'Beverages', 'Specialty Roast',   17.50, NULL,  0, 20, 10, 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?w=600&q=80', 'Light-medium roast with floral jasmine and bergamot tasting notes.'),
(49, 'english-tea',    'Organic English Breakfast Tea 50pk',          'Beverages', 'Organic Leaf',       6.20,  NULL,  0, 30, 10, 'https://images.unsplash.com/photo-1576092768241-dec231879fc3?w=600&q=80', 'Full-bodied blend of Assam and Ceylon black tea leaves.'),
(50, 'maple-syrup',    '100% Pure Canadian Maple Syrup Grade A 250ml','Beverages', 'Save $3.00',        8.90,  11.90, 1, 20, 10, 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?w=600&q=80', 'Amber rich Canadian maple syrup tapped from sugar maples.');

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