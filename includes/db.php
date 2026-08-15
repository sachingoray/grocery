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
            'stock' => 42, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1523049673857-eb18f1d7b578?w=600&q=80',
            'description' => 'Creamy, hand-selected Hass avocados, ripened to order.',
        ],
        [
            'id' => 2, 'sku' => 'sourdough', 'name' => 'Country Sourdough',
            'category' => 'Bakery', 'badge' => 'Artisan Baked', 'price' => 7.50,
            'stock' => 18, 'low_stock_threshold' => 8,
            'image_url' => 'https://images.unsplash.com/photo-1585478259715-4d3a5d7f3b8b?w=600&q=80',
            'description' => 'Slow-fermented 48-hour sourdough baked fresh each morning.',
        ],
        [
            'id' => 3, 'sku' => 'salmon', 'name' => 'Atlantic Salmon Fillet',
            'category' => 'Meat & Seafood', 'badge' => 'Wild Caught', 'price' => 18.90,
            'stock' => 6, 'low_stock_threshold' => 8,
            'image_url' => 'https://images.unsplash.com/photo-1519708227418-c8fd9a32b7a2?w=600&q=80',
            'description' => 'Sustainably wild-caught salmon, filleted daily.',
        ],
        [
            'id' => 4, 'sku' => 'blueberry', 'name' => 'Organic Blueberries',
            'category' => 'Produce', 'badge' => 'Organic', 'price' => 6.90,
            'stock' => 27, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1498557850523-fd3d118b962e?w=600&q=80',
            'description' => 'Sweet, plump blueberries grown without synthetic pesticides.',
        ],
        [
            'id' => 5, 'sku' => 'oil', 'name' => 'Extra Virgin Olive Oil',
            'category' => 'Beverages', 'badge' => 'Cold Pressed', 'price' => 19.50,
            'stock' => 33, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1474979266404-7eaacbcd87c5?w=600&q=80',
            'description' => 'First cold-pressed olive oil from a single estate grove.',
        ],
        [
            'id' => 6, 'sku' => 'eggs', 'name' => 'Free-Range Eggs',
            'category' => 'Dairy', 'badge' => 'Farm Fresh', 'price' => 8.40,
            'stock' => 4, 'low_stock_threshold' => 10,
            'image_url' => 'https://images.unsplash.com/photo-1518569656558-1f25e69d93d7?w=600&q=80',
            'description' => 'Free-range eggs, collected daily from pasture-raised hens.',
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
