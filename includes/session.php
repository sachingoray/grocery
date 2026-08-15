<?php
/**
 * session.php — role/auth helpers, cart state, and flash messages.
 * Include this before any output on every page (it starts the session).
 */

require_once __DIR__ . '/db.php';

/**
 * BASE_URL — the URL path prefix under which /public is being served.
 * Computed automatically from the request, so the app works whether it's
 * accessed at the domain root or nested under folders like
 * /capstone/maxi-fine-foods-php/public. All links/forms/assets should be
 * built as BASE_URL . '/something.php' instead of a hardcoded '/something.php'.
 */
if (!defined('BASE_URL')) {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $publicPos = strpos($scriptName, '/public');
    define('BASE_URL', $publicPos !== false ? substr($scriptName, 0, $publicPos + strlen('/public')) : '');
}

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}




if (!isset($_SESSION['cart']) || !is_array($_SESSION['cart'])) {
    $_SESSION['cart'] = []; // [product_id => quantity]
}
foreach ($_SESSION['cart'] as $mffCartKey => $mffCartVal) {
    if (!is_numeric($mffCartVal)) {
        $_SESSION['cart'] = [];
        break;
    }
}


unset($mffCartKey, $mffCartVal);
if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'guest'; // guest | customer | admin | delivery
}

/* ---------------------------------------------------------------- roles */

const MFF_ROLES = ['guest', 'customer', 'admin', 'delivery'];

function mff_role(): string
{
    return $_SESSION['role'] ?? 'guest';
}

function mff_set_role(string $role): void
{
    if (in_array($role, MFF_ROLES, true)) {
        $_SESSION['role'] = $role;
    }
}

function mff_require_role(array $allowed): void
{
    if (!in_array(mff_role(), $allowed, true)) {
        mff_set_flash('error', "You need to be signed in as one of: " . implode(', ', $allowed));
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}

/* ------------------------------------------------------------ flash msg */

function mff_set_flash(string $type, string $message): void
{
    $_SESSION['flash'] = ['type' => $type, 'message' => $message];
}

function mff_get_flash(): ?array
{
    if (empty($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

/* ------------------------------------------------------------------ cart */

function cart_add(int $productId, int $quantity = 1): void
{
    $quantity = max(1, $quantity);
    $existing = $_SESSION['cart'][$productId] ?? 0;
    $existing = is_numeric($existing) ? (int) $existing : 0;
    $_SESSION['cart'][$productId] = $existing + $quantity;
}

function cart_set_qty(int $productId, int $quantity): void
{
    if ($quantity < 1) {
        cart_remove($productId);
        return;
    }
    $_SESSION['cart'][$productId] = $quantity;
}

function cart_remove(int $productId): void
{
    unset($_SESSION['cart'][$productId]);
}

function cart_clear(): void
{
    $_SESSION['cart'] = [];
}

function cart_count(): int
{
    return array_sum(array_map(fn($q) => is_numeric($q) ? (int) $q : 0, $_SESSION['cart']));
}

/**
 * Resolves the cart against the live product catalog (or fallback) and
 * returns line items plus totals. Silently drops any product id that no
 * longer exists (e.g. deleted by an admin) rather than erroring.
 */
function cart_contents(): array
{
    $items = [];
    $subtotal = 0.0;

    foreach ($_SESSION['cart'] as $productId => $quantity) {
        $quantity = is_numeric($quantity) ? (int) $quantity : 0;
        if ($quantity < 1) {
            continue;
        }
        $product = mff_get_product((int) $productId);
        if ($product === null) {
            continue;
        }
        $lineTotal = (float) $product['price'] * $quantity;
        $subtotal += $lineTotal;
        $items[] = [
            'product_id' => $product['id'],
            'name' => $product['name'],
            'price' => $product['price'],
            'quantity' => $quantity,
            'line_total' => $lineTotal,
            'image_url' => $product['image_url'] ?? null,
        ];
    }

    $tax = round($subtotal * 0.10, 2); // 10% GST
    $total = round($subtotal + $tax, 2);

    return [
        'items' => $items,
        'subtotal' => round($subtotal, 2),
        'tax' => $tax,
        'total' => $total,
    ];
}

/** True if the request is an XHR / fetch() call expecting JSON back. */
function mff_wants_json(): bool
{
    return (
        (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
    );
}

function mff_money(float $n): string
{
    return '$' . number_format($n, 2);
}
