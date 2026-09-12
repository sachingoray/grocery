<?php
/**
 * db.php — PDO connection for Maxi Fine Foods.
 *
 * Falls back to an in-memory mock catalog if MySQL isn't reachable so the
 * front end still renders during dev/demo without XAMPP's MySQL running.
 * Set MFF_DB_FALLBACK=0 in the environment to disable the fallback and
 * force a hard failure instead (useful once the real schema is finalised).
 */

const DB_HOST = '127.0.0.1';
const DB_NAME = 'maxi_fine_foods';
const DB_USER = 'root';
const DB_PASS = '';
const DB_CHARSET = 'utf8mb4';

/** @var bool True once we've confirmed a live PDO connection. */
$GLOBALS['mff_db_live'] = false;

function mff_products_fallback(): array
{
    return [
        [
            'id' => 1, 'sku' => 'avocado', 'name' => 'Organic Hass Avocados',
            'category' => 'Produce', 'badge' => 'Organic', 'price' => 2.49,
            'original_price' => null, 'is_special' => 0,
            'stock' => 42, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1523049673857-eb18f1d7b578?w=600&q=80',
            'description' => 'Creamy, hand-selected Hass avocados, ripened to order.',
        ],
        [
            'id' => 2, 'sku' => 'strawberries', 'name' => 'Australian Fresh Strawberries 250g',
            'category' => 'Produce', 'badge' => '33% OFF', 'price' => 2.99,
            'original_price' => 4.5, 'is_special' => 1,
            'stock' => 45, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1464965911861-746a04b4bca6?w=600&q=80',
            'description' => 'Sweet, ruby-red Australian strawberries freshly picked from regional farms.',
        ],
        [
            'id' => 3, 'sku' => 'bananas', 'name' => 'Cavendish Bananas 1kg',
            'category' => 'Produce', 'badge' => 'Local Pick', 'price' => 3.9,
            'original_price' => null, 'is_special' => 0,
            'stock' => 55, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1571771894821-ce9b6c11b08e?w=600&q=80',
            'description' => 'Naturally sweet Queensland Cavendish bananas, full of potassium.',
        ],
        [
            'id' => 4, 'sku' => 'gala-apples', 'name' => 'Royal Gala Apples 1kg',
            'category' => 'Produce', 'badge' => 'Crunchy', 'price' => 4.5,
            'original_price' => null, 'is_special' => 0,
            'stock' => 38, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1560806887-1e4cd0b6cbd6?w=600&q=80',
            'description' => 'Crisp, aromatic Gala apples picked fresh from local orchards.',
        ],
        [
            'id' => 5, 'sku' => 'iceberg-lettuce', 'name' => 'Crisp Iceberg Lettuce',
            'category' => 'Produce', 'badge' => 'Farm Fresh', 'price' => 2.5,
            'original_price' => null, 'is_special' => 0,
            'stock' => 30, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1556881286-fc6915169721?w=600&q=80',
            'description' => 'Firm, crunchy iceberg lettuce perfect for fresh garden salads.',
        ],
        [
            'id' => 6, 'sku' => 'baby-spinach', 'name' => 'Organic Baby Spinach 200g',
            'category' => 'Produce', 'badge' => 'Organic', 'price' => 3.9,
            'original_price' => null, 'is_special' => 0,
            'stock' => 24, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1576045057995-568f588f82fb?w=600&q=80',
            'description' => 'Tender washed organic baby spinach leaves, rich in iron.',
        ],
        [
            'id' => 7, 'sku' => 'roma-tomatoes', 'name' => 'Gourmet Roma Tomatoes 500g',
            'category' => 'Produce', 'badge' => 'Vine Ripened', 'price' => 3.2,
            'original_price' => null, 'is_special' => 0,
            'stock' => 35, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1592924357228-91a4daadcfea?w=600&q=80',
            'description' => 'Sweet, flavourful Roma tomatoes ideal for sauces and slicing.',
        ],
        [
            'id' => 8, 'sku' => 'dutch-carrots', 'name' => 'Dutch Baby Carrots Bunch',
            'category' => 'Produce', 'badge' => 'Farm Fresh', 'price' => 3.5,
            'original_price' => null, 'is_special' => 0,
            'stock' => 22, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1598170845058-32b9d6a5c317?w=600&q=80',
            'description' => 'Sweet, vibrant bunch of Dutch carrots with fresh green tops.',
        ],
        [
            'id' => 9, 'sku' => 'potatoes', 'name' => 'Washed White Potatoes 2kg',
            'category' => 'Produce', 'badge' => 'Local Grown', 'price' => 4.9,
            'original_price' => null, 'is_special' => 0,
            'stock' => 35, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1518977676601-b53f82aba655?w=600&q=80',
            'description' => 'All-rounder potatoes suitable for mashing, baking, or roasting.',
        ],
        [
            'id' => 10, 'sku' => 'watermelon', 'name' => 'Seedless Red Watermelon Quarter',
            'category' => 'Produce', 'badge' => 'Summer Special', 'price' => 3.8,
            'original_price' => 5.2, 'is_special' => 1,
            'stock' => 20, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1587049352846-4a222e784d38?w=600&q=80',
            'description' => 'Juicy and refreshing seedless red watermelon.',
        ],
        [
            'id' => 11, 'sku' => 'sourdough', 'name' => 'Country Sourdough Loaf',
            'category' => 'Bakery', 'badge' => 'Special Deal', 'price' => 5.9,
            'original_price' => 7.5, 'is_special' => 1,
            'stock' => 18, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1585478259715-4d3a5d7f3b8b?w=600&q=80',
            'description' => 'Slow-fermented 48-hour sourdough baked fresh each morning.',
        ],
        [
            'id' => 12, 'sku' => 'croissant', 'name' => 'French Butter Croissants 4pk',
            'category' => 'Bakery', 'badge' => 'Flaky & Buttery', 'price' => 6.5,
            'original_price' => null, 'is_special' => 0,
            'stock' => 22, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1555507036-ab1f4038808a?w=600&q=80',
            'description' => 'Traditional layered French butter croissants with golden flaky crust.',
        ],
        [
            'id' => 13, 'sku' => 'ciabatta', 'name' => 'Artisan Olive Oil Ciabatta',
            'category' => 'Bakery', 'badge' => 'Stone Baked', 'price' => 5.2,
            'original_price' => null, 'is_special' => 0,
            'stock' => 16, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=600&q=80',
            'description' => 'Crusty Italian ciabatta loaf with an airy, chewy crumb.',
        ],
        [
            'id' => 14, 'sku' => 'wholemeal-bread', 'name' => 'Wholemeal Sandwich Bread 700g',
            'category' => 'Bakery', 'badge' => 'High Fibre', 'price' => 4.2,
            'original_price' => null, 'is_special' => 0,
            'stock' => 30, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1549931319-a545dcf3bc73?w=600&q=80',
            'description' => 'Soft, nourishing wholemeal sliced bread for daily sandwiches.',
        ],
        [
            'id' => 15, 'sku' => 'brioche-buns', 'name' => 'Gourmet Brioche Burger Buns 4pk',
            'category' => 'Bakery', 'badge' => 'Golden Glaze', 'price' => 4.8,
            'original_price' => null, 'is_special' => 0,
            'stock' => 25, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1586190848861-99aa4a171e90?w=600&q=80',
            'description' => 'Enriched, slightly sweet golden brioche buns, perfect for burgers.',
        ],
        [
            'id' => 16, 'sku' => 'pain-chocolat', 'name' => 'Pain au Chocolat 4pk',
            'category' => 'Bakery', 'badge' => '20% OFF', 'price' => 6.2,
            'original_price' => 7.8, 'is_special' => 1,
            'stock' => 20, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1608198093002-ad4e005484ec?w=600&q=80',
            'description' => 'Flaky French pastry filled with rich Belgian dark chocolate batons.',
        ],
        [
            'id' => 17, 'sku' => 'blueberry-muffins', 'name' => 'Blueberry Crumble Muffins 4pk',
            'category' => 'Bakery', 'badge' => 'Freshly Baked', 'price' => 5.9,
            'original_price' => null, 'is_special' => 0,
            'stock' => 20, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1586985289688-ca3cf47d3e6e?w=600&q=80',
            'description' => 'Moist vanilla muffins loaded with bursting blueberries.',
        ],
        [
            'id' => 18, 'sku' => 'baguette', 'name' => 'Traditional French Baguette',
            'category' => 'Bakery', 'badge' => 'Artisan', 'price' => 3.5,
            'original_price' => null, 'is_special' => 0,
            'stock' => 25, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1589367920969-ab8e050bbb04?w=600&q=80',
            'description' => 'Crisp crust and tender inside, baked fresh twice daily.',
        ],
        [
            'id' => 19, 'sku' => 'plain-bagels', 'name' => 'New York Plain Bagels 4pk',
            'category' => 'Bakery', 'badge' => 'Boiled & Baked', 'price' => 4.5,
            'original_price' => null, 'is_special' => 0,
            'stock' => 24, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1585478259715-4d3a5d7f3b8b?w=600&q=80',
            'description' => 'Chewy, authentic New York style boiled bagels.',
        ],
        [
            'id' => 20, 'sku' => 'focaccia', 'name' => 'Rosemary & Sea Salt Focaccia',
            'category' => 'Bakery', 'badge' => 'Extra Virgin Olive Oil', 'price' => 6.2,
            'original_price' => null, 'is_special' => 0,
            'stock' => 15, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1509440159596-0249088772ff?w=600&q=80',
            'description' => 'Thick Italian focaccia dimpled with fresh rosemary and olive oil.',
        ],
        [
            'id' => 21, 'sku' => 'salmon', 'name' => 'Atlantic Salmon Fillet 500g',
            'category' => 'Meat & Seafood', 'badge' => 'Save $3.40', 'price' => 15.5,
            'original_price' => 18.9, 'is_special' => 1,
            'stock' => 12, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=600&q=80',
            'description' => 'Sustainably wild-caught salmon, filleted daily.',
        ],
        [
            'id' => 22, 'sku' => 'chicken-breast', 'name' => 'Free-Range Chicken Breast 1kg',
            'category' => 'Meat & Seafood', 'badge' => '100% Australian', 'price' => 13.9,
            'original_price' => null, 'is_special' => 0,
            'stock' => 30, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?w=600&q=80',
            'description' => 'Tender, skinless free-range chicken breast fillets.',
        ],
        [
            'id' => 23, 'sku' => 'ribeye-steak', 'name' => 'Grass-Fed Angus Ribeye Steak 350g',
            'category' => 'Meat & Seafood', 'badge' => 'Prime Cut', 'price' => 16.5,
            'original_price' => null, 'is_special' => 0,
            'stock' => 14, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1603048588665-791ca8aea617?w=600&q=80',
            'description' => 'Well-marbled Angus beef ribeye, tender and rich in flavour.',
        ],
        [
            'id' => 24, 'sku' => 'beef-mince', 'name' => 'Premium Lean Beef Mince 500g',
            'category' => 'Meat & Seafood', 'badge' => '90% Lean', 'price' => 8.5,
            'original_price' => null, 'is_special' => 0,
            'stock' => 35, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1588168333986-5078d3ae3976?w=600&q=80',
            'description' => 'Quality Australian ground beef for bolognese and tacos.',
        ],
        [
            'id' => 25, 'sku' => 'tiger-prawns', 'name' => 'Queensland Tiger Prawns 500g',
            'category' => 'Meat & Seafood', 'badge' => 'Special Catch', 'price' => 18.9,
            'original_price' => 23.5, 'is_special' => 1,
            'stock' => 16, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1565680018434-b513d5e5fd47?w=600&q=80',
            'description' => 'Sweet, firm wild-caught Queensland tiger prawns.',
        ],
        [
            'id' => 26, 'sku' => 'pork-chops', 'name' => 'Free-Range Pork Loin Chops 500g',
            'category' => 'Meat & Seafood', 'badge' => 'Tender Cut', 'price' => 9.8,
            'original_price' => null, 'is_special' => 0,
            'stock' => 20, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1607623814075-e51df1bdc82f?w=600&q=80',
            'description' => 'Succulent pork chops with rind for crispy crackling.',
        ],
        [
            'id' => 27, 'sku' => 'lamb-cutlets', 'name' => 'Australian Grass-Fed Lamb Cutlets 6pk',
            'category' => 'Meat & Seafood', 'badge' => 'Gourmet Lamb', 'price' => 22.0,
            'original_price' => null, 'is_special' => 0,
            'stock' => 15, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1544025162-d76694265947?w=600&q=80',
            'description' => 'French-trimmed tender lamb cutlets, tender and flavorful.',
        ],
        [
            'id' => 28, 'sku' => 'wagyu-burgers', 'name' => 'Wagyu Beef Burger Patties 4pk',
            'category' => 'Meat & Seafood', 'badge' => '20% OFF', 'price' => 10.5,
            'original_price' => 13.2, 'is_special' => 1,
            'stock' => 22, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1586190848861-99aa4a171e90?w=600&q=80',
            'description' => 'High-marbled Wagyu beef patties for juicy gourmet burgers.',
        ],
        [
            'id' => 29, 'sku' => 'whole-chicken', 'name' => 'Whole Free-Range Roaster Chicken 1.6kg',
            'category' => 'Meat & Seafood', 'badge' => 'Roast Ready', 'price' => 12.5,
            'original_price' => null, 'is_special' => 0,
            'stock' => 18, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1604503468506-a8da13d82791?w=600&q=80',
            'description' => 'Fresh whole free-range chicken ready for Sunday family roasts.',
        ],
        [
            'id' => 30, 'sku' => 'streaky-bacon', 'name' => 'Naturally Smoked Streaky Bacon 250g',
            'category' => 'Meat & Seafood', 'badge' => 'Naturally Smoked', 'price' => 5.9,
            'original_price' => null, 'is_special' => 0,
            'stock' => 30, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1607623814075-e51df1bdc82f?w=600&q=80',
            'description' => 'Crisp-frying rindless streaky bacon cured with natural woodsmoke.',
        ],
        [
            'id' => 31, 'sku' => 'eggs', 'name' => 'Free-Range Large Eggs 12pk',
            'category' => 'Dairy', 'badge' => 'Farm Fresh', 'price' => 8.4,
            'original_price' => null, 'is_special' => 0,
            'stock' => 35, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1518569656558-1f25e69d93d7?w=600&q=80',
            'description' => 'Free-range eggs, collected daily from pasture-raised hens.',
        ],
        [
            'id' => 32, 'sku' => 'feta', 'name' => 'Artisan Greek Feta Cheese 200g',
            'category' => 'Dairy', 'badge' => 'Special Sale', 'price' => 6.2,
            'original_price' => 8.5, 'is_special' => 1,
            'stock' => 24, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1559561853-08451507cbe7?w=600&q=80',
            'description' => 'Authentic barrel-aged sheep and goat milk feta cheese.',
        ],
        [
            'id' => 33, 'sku' => 'full-cream-milk', 'name' => 'Organic Full Cream Farm Milk 2L',
            'category' => 'Dairy', 'badge' => '100% Organic', 'price' => 4.6,
            'original_price' => null, 'is_special' => 0,
            'stock' => 45, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1550583724-b2692b85b150?w=600&q=80',
            'description' => 'Creamy, unhomogenised pasture-raised dairy milk.',
        ],
        [
            'id' => 34, 'sku' => 'salted-butter', 'name' => 'Cultured Salted Butter 250g',
            'category' => 'Dairy', 'badge' => 'European Style', 'price' => 5.2,
            'original_price' => null, 'is_special' => 0,
            'stock' => 32, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1589985270826-4b7bb135bc9d?w=600&q=80',
            'description' => 'Slow-churned cultured cream butter with sea salt crystals.',
        ],
        [
            'id' => 35, 'sku' => 'greek-yoghurt', 'name' => 'Authentic Greek Strained Yoghurt 1kg',
            'category' => 'Dairy', 'badge' => '22% OFF', 'price' => 6.9,
            'original_price' => 8.9, 'is_special' => 1,
            'stock' => 30, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1488477181946-6428a0291777?w=600&q=80',
            'description' => 'Thick, high-protein traditional pot-set Greek yoghurt.',
        ],
        [
            'id' => 36, 'sku' => 'heavy-cream', 'name' => 'Pure Thick Whipping Cream 300ml',
            'category' => 'Dairy', 'badge' => '35% Milk Fat', 'price' => 3.8,
            'original_price' => null, 'is_special' => 0,
            'stock' => 26, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1550583724-b2692b85b150?w=600&q=80',
            'description' => 'Rich dollop cream that whips into billowy peaks easily.',
        ],
        [
            'id' => 37, 'sku' => 'vintage-cheddar', 'name' => 'Aged Vintage Cheddar Block 250g',
            'category' => 'Dairy', 'badge' => '18-Month Aged', 'price' => 6.8,
            'original_price' => null, 'is_special' => 0,
            'stock' => 25, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1618164436241-4473940d1f5c?w=600&q=80',
            'description' => 'Sharp, crumbly aged cheddar with delightful calcium crystals.',
        ],
        [
            'id' => 38, 'sku' => 'mozzarella-ball', 'name' => 'Fresh Fior di Latte Mozzarella 200g',
            'category' => 'Dairy', 'badge' => 'Artisan Italian', 'price' => 5.5,
            'original_price' => null, 'is_special' => 0,
            'stock' => 20, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1559561853-08451507cbe7?w=600&q=80',
            'description' => 'Soft, milky fresh mozzarella ball soaked in brine for pizza & caprese.',
        ],
        [
            'id' => 39, 'sku' => 'parmesan', 'name' => 'Parmigiano Reggiano Wedge 200g',
            'category' => 'Dairy', 'badge' => 'DOP Certified', 'price' => 9.5,
            'original_price' => null, 'is_special' => 0,
            'stock' => 20, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1618164436241-4473940d1f5c?w=600&q=80',
            'description' => '24-month matured authentic Italian parmesan cheese.',
        ],
        [
            'id' => 40, 'sku' => 'oat-milk', 'name' => 'Oat Milk Barista Edition 1L',
            'category' => 'Dairy', 'badge' => 'Froths Perfectly', 'price' => 3.8,
            'original_price' => null, 'is_special' => 0,
            'stock' => 40, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1550583724-b2692b85b150?w=600&q=80',
            'description' => 'Velvety plant milk designed for micro-foaming specialty coffee.',
        ],
        [
            'id' => 41, 'sku' => 'oil', 'name' => 'Extra Virgin Olive Oil 750ml',
            'category' => 'Beverages', 'badge' => 'Cold Pressed', 'price' => 19.5,
            'original_price' => null, 'is_special' => 0,
            'stock' => 33, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=600&q=80',
            'description' => 'First cold-pressed olive oil from a single estate grove.',
        ],
        [
            'id' => 42, 'sku' => 'oj', 'name' => 'Fresh Cold-Pressed Orange Juice 1L',
            'category' => 'Beverages', 'badge' => '100% Squeezed', 'price' => 4.9,
            'original_price' => null, 'is_special' => 0,
            'stock' => 30, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1613478223719-2ab802602423?w=600&q=80',
            'description' => 'Pure unpasteurised orange juice with juicy pulp, no added sugar.',
        ],
        [
            'id' => 43, 'sku' => 'green-juice', 'name' => 'Cold-Pressed Green Detox Juice 750ml',
            'category' => 'Beverages', 'badge' => '25% OFF', 'price' => 5.2,
            'original_price' => 6.9, 'is_special' => 1,
            'stock' => 20, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1540420773420-3366772f4999?w=600&q=80',
            'description' => 'Cold-pressed apple, cucumber, celery, kale, lemon, and mint.',
        ],
        [
            'id' => 44, 'sku' => 'sparkling-water', 'name' => 'Natural Mineral Water Sparkling 1.25L',
            'category' => 'Beverages', 'badge' => 'Spring Sourced', 'price' => 2.5,
            'original_price' => null, 'is_special' => 0,
            'stock' => 45, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1548839140-29a749e1bc4e?w=600&q=80',
            'description' => 'Crisp effervescent mineral water from natural underground springs.',
        ],
        [
            'id' => 45, 'sku' => 'ginger-beer', 'name' => 'Craft Brewed Spiced Ginger Beer 4pk',
            'category' => 'Beverages', 'badge' => 'Naturally Brewed', 'price' => 7.8,
            'original_price' => null, 'is_special' => 0,
            'stock' => 22, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1527661591475-527312dd65f5?w=600&q=80',
            'description' => 'Traditional fermented ginger beer with spicy fiery kick.',
        ],
        [
            'id' => 46, 'sku' => 'kombucha-ginger', 'name' => 'Organic Ginger Lemon Kombucha 330ml',
            'category' => 'Beverages', 'badge' => 'Live Probiotics', 'price' => 3.9,
            'original_price' => null, 'is_special' => 0,
            'stock' => 28, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1513558161293-cdaf765ed2fd?w=600&q=80',
            'description' => 'Sparkling fermented green tea with fresh pressed ginger juice.',
        ],
        [
            'id' => 47, 'sku' => 'coconut-water', 'name' => '100% Pure Organic Coconut Water 1L',
            'category' => 'Beverages', 'badge' => 'Naturally Hydrating', 'price' => 4.8,
            'original_price' => null, 'is_special' => 0,
            'stock' => 32, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1525385133512-2f3bdd039054?w=600&q=80',
            'description' => 'Hydrating young green coconut water rich in electrolytes.',
        ],
        [
            'id' => 48, 'sku' => 'coffee-beans', 'name' => 'Single Origin Ethiopian Coffee Beans 500g',
            'category' => 'Beverages', 'badge' => 'Specialty Roast', 'price' => 17.5,
            'original_price' => null, 'is_special' => 0,
            'stock' => 20, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1559056199-641a0ac8b55e?w=600&q=80',
            'description' => 'Light-medium roast with floral jasmine and bergamot tasting notes.',
        ],
        [
            'id' => 49, 'sku' => 'english-tea', 'name' => 'Organic English Breakfast Tea 50pk',
            'category' => 'Beverages', 'badge' => 'Organic Leaf', 'price' => 6.2,
            'original_price' => null, 'is_special' => 0,
            'stock' => 30, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1576092768241-dec231879fc3?w=600&q=80',
            'description' => 'Full-bodied blend of Assam and Ceylon black tea leaves.',
        ],
        [
            'id' => 50, 'sku' => 'maple-syrup', 'name' => '100% Pure Canadian Maple Syrup Grade A 250ml',
            'category' => 'Beverages', 'badge' => 'Save $3.00', 'price' => 8.9,
            'original_price' => 11.9, 'is_special' => 1,
            'stock' => 20, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1589301760014-d929f3979dbc?w=600&q=80',
            'description' => 'Amber rich Canadian maple syrup tapped from sugar maples.',
        ],
    ];
}

function mff_orders_fallback(): array
{
    return [
        [
            'id' => 2048, 'customer_name' => 'Emma Wilson', 'contact_number' => '0412 345 678',
            'delivery_address' => '42 Riverside Drive, Parramatta NSW 2150',
            'delivery_instructions' => 'Leave with concierge if not home.',
            'payment_method' => 'cash_on_delivery', 'subtotal' => 12.98, 'tax' => 1.30,
            'total' => 14.28, 'status' => 'out_for_delivery', 'driver' => 'Chris Allen',
            'created_at' => '2026-08-10 09:15:00',
            'items' => [
                ['name' => 'Organic Hass Avocados', 'price' => 2.49, 'quantity' => 2],
                ['name' => 'Free-Range Eggs', 'price' => 8.40, 'quantity' => 1],
            ],
        ],
        [
            'id' => 2049, 'customer_name' => 'Noah Brown', 'contact_number' => '0433 221 998',
            'delivery_address' => '8 Harbord Street, Marrickville NSW 2204',
            'delivery_instructions' => '',
            'payment_method' => 'credit_card', 'subtotal' => 26.40, 'tax' => 2.64,
            'total' => 29.04, 'status' => 'processing', 'driver' => null,
            'created_at' => '2026-08-10 10:02:00',
            'items' => [
                ['name' => 'Atlantic Salmon Fillet', 'price' => 18.90, 'quantity' => 1],
                ['name' => 'Country Sourdough', 'price' => 7.50, 'quantity' => 1],
            ],
        ],
    ];
}

/**
 * Returns a live PDO connection, or null if unavailable (caller should use
 * the mff_*_fallback() functions in that case).
 */
function mff_db(): ?PDO
{
    static $pdo = null;
    static $attempted = false;

    if ($pdo !== null) {
        return $pdo;
    }
    if ($attempted) {
        return null;
    }
    $attempted = true;

    $dsn = sprintf('mysql:host=%s;dbname=%s;charset=%s', DB_HOST, DB_NAME, DB_CHARSET);

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
        $GLOBALS['mff_db_live'] = true;
        return $pdo;
    } catch (PDOException $e) {
        error_log('[mff] DB connection failed, using fallback data: ' . $e->getMessage());
        if (getenv('MFF_DB_FALLBACK') === '0') {
            throw $e;
        }
        return null;
    }
}

/** Fetch all products, from DB if live, otherwise the mock fallback. */
function mff_get_products(): array
{
    $pdo = mff_db();
    if ($pdo === null) {
        return mff_products_fallback();
    }
    $stmt = $pdo->query('SELECT * FROM products ORDER BY category, name');
    return $stmt->fetchAll();
}

/** Fetch a single product by id. */
function mff_get_product(int $id): ?array
{
    $pdo = mff_db();
    if ($pdo === null) {
        foreach (mff_products_fallback() as $p) {
            if ($p['id'] === $id) {
                return $p;
            }
        }
        return null;
    }
    $stmt = $pdo->prepare('SELECT * FROM products WHERE id = :id');
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}
