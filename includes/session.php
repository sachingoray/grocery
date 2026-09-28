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
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    if (($pos = strpos($scriptName, '/g4')) !== false) {
        define('BASE_URL', substr($scriptName, 0, $pos + strlen('/g4')));
    } elseif (($pos = strpos($scriptName, '/public')) !== false) {
        define('BASE_URL', substr($scriptName, 0, $pos + strlen('/public')));
    } elseif (($pos = strpos($scriptName, '/admin')) !== false) {
        define('BASE_URL', substr($scriptName, 0, $pos));
    } elseif (($pos = strpos($scriptName, '/delivery')) !== false) {
        define('BASE_URL', substr($scriptName, 0, $pos));
    } else {
        $dir = dirname($scriptName);
        define('BASE_URL', ($dir === '/' || $dir === '\\' || $dir === '.') ? '' : rtrim($dir, '/\\'));
    }
}
/**
 * MFF_BUILD — identifies which build a server is running.
 *
 * Rendered into a <meta name="mff-build"> tag by header.php, so you can confirm
 * at a glance whether a given host (local, staging or the live cloud server) is
 * actually serving the current code. Bump the suffix whenever the cart or auth
 * behaviour changes. A host that does NOT show this tag is running pre-build
 * code, whatever the local files look like.
 */
if (!defined('MFF_BUILD')) {
    define('MFF_BUILD', '2026-09-28-auth-checkout-parse-fix');
}
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// Serve everything explicitly as UTF-8 so emoji render correctly instead of
// showing as mojibake. The <meta charset="utf-8"> in header.php is only a
// fallback — the HTTP header wins, and without it proxies/hosts may default
// to Latin-1. Must run before any output; session.php is included first.
if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}
if (!headers_sent()) {
    header('Content-Type: text/html; charset=utf-8');
}
/**
 * Content-Security-Policy — sent from PHP rather than .htaccess because the
 * site is served by nginx, and from here (not header.php) so it also covers
 * pages that render without the site chrome.
 *
 * The policy is deny-by-default: every resource the application actually uses
 * is listed explicitly, and there is no 'unsafe-inline', 'unsafe-eval',
 * wildcard or data: source anywhere in it. Supporting the strict policy means
 * the front end carries no inline <script> blocks, no on* event handlers and no
 * class="u-001" attributes — those live in assets/main.js, assets/style.css and the
 * page-specific assets/*.js files instead.
 *
 * Resource inventory this policy was built from:
 *   scripts : local assets only + cdn.jsdelivr.net (Lucide icon renderer).
 *             The Tailwind Play CDN was removed: no Tailwind utility class is
 *             used anywhere in the templates, and it injected its own <style>
 *             block, which would have forced 'unsafe-inline' into style-src.
 *   styles  : local assets + fonts.googleapis.com (stylesheet only).
 *   fonts   : fonts.gstatic.com (served by Google Fonts).
 *   images  : local assets + images.unsplash.com (catalog photography seeds).
 *   xhr     : same origin only (cart.php ?ajax=1), so connect-src 'self'.
 *   forms   : same origin, plus https://checkout.stripe.com. checkout.php
 *             answers a card payment with a 303 redirect to the Stripe-hosted
 *             checkout page, and form-action governs that navigation: with
 *             'self' alone the browser blocks the redirect and the Pay with
 *             Stripe button silently does nothing, even though the Checkout
 *             Session was created successfully server-side. Listing the one
 *             specific host is the narrowest form that lets card payments work.
 */
function mff_send_security_headers(): void
{
    if (headers_sent()) {
        return;
    }
    $policy = [
        "default-src 'none'",
        "base-uri 'self'",
        "script-src 'self' https://cdn.jsdelivr.net",
        "script-src-attr 'none'",
        "style-src 'self' https://fonts.googleapis.com",
        "style-src-attr 'none'",
        "img-src 'self' https://images.unsplash.com",
        "font-src 'self' https://fonts.gstatic.com",
        "connect-src 'self'",
        // checkout.stripe.com is required: checkout.php redirects card payments
        // there with a 303, and form-action blocks that navigation otherwise.
        "form-action 'self' https://checkout.stripe.com",
        "frame-ancestors 'none'",
        "frame-src 'none'",
        "object-src 'none'",
        "media-src 'none'",
        "manifest-src 'none'",
        "worker-src 'none'",
    ];
    header('Content-Security-Policy: ' . implode('; ', $policy));

    // HTTP Strict Transport Security (HSTS)
    // Send only over HTTPS to instruct browsers to strictly use HTTPS for future requests.
    // Notice: includeSubDomains and preload are intentionally omitted until domain-wide compliance is verified.
    $isHttps = (!empty($_SERVER['HTTPS']) && strtolower((string)$_SERVER['HTTPS']) !== 'off')
        || (!empty($_SERVER['SERVER_PORT']) && (int)$_SERVER['SERVER_PORT'] === 443)
        || (!empty($_SERVER['HTTP_X_FORWARDED_PROTO']) && strtolower((string)$_SERVER['HTTP_X_FORWARDED_PROTO']) === 'https');

    if ($isHttps) {
        header('Strict-Transport-Security: max-age=31536000');
    }
}
mff_send_security_headers();
/**
 * Cart ownership.
 *
 * The cart used to live only in $_SESSION['cart'], which meant it was scoped
 * to the BROWSER (the PHPSESSID cookie) rather than to the ACCOUNT. Logging
 * out only cleared user_id, so the next account to log in on that same browser
 * inherited the previous user's cart. It is now persisted in the cart_items
 * table and scoped to the authenticated user id (see mff_cart_owner()).
 *
 * $GLOBALS['mff_cart_cache'] is a per-request memoisation of that database read
 * so a page rendering many product cards (index.php calls cart_qty() once per
 * product) issues a single SELECT instead of one per card. It is rebuilt from
 * the database on first access in every request, is keyed implicitly to the
 * current owner, and is never treated as the source of truth.
 */
$GLOBALS['mff_cart_cache'] = null;
/** Guest carts are keyed by a random per-browser token rather than the raw
 *  PHPSESSID, so a session-fixation attempt cannot be pointed at another
 *  browser's guest cart. */
if (empty($_SESSION['cart_guest_token'])) {
    $_SESSION['cart_guest_token'] = bin2hex(random_bytes(32));
}
if (!isset($_SESSION['role'])) {
    $_SESSION['role'] = 'guest'; // guest | customer | admin | delivery
}
/* ---------------------------------------------------------------- roles */
const MFF_ROLES = [
    'guest',
    'customer',
    'delivery',
    'inventory_manager',
    'logistics_manager',
    'support_staff',
    'admin'
];
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
/**
 * Guards a page behind a set of permitted roles, bouncing anyone else to the
 * login screen with an explanatory flash message.
 */
function mff_require_role(array $allowed): void
{
    if (!in_array(mff_role(), $allowed, true)) {
        mff_set_flash('error', "Access restricted — required role: " . implode(', ', $allowed));
        header('Location: ' . BASE_URL . '/login.php');
        exit;
    }
}
function mff_is_staff(): bool
{
    return in_array(mff_role(), ['admin', 'inventory_manager', 'logistics_manager', 'support_staff'], true);
}
function mff_role_label(string $role): string
{
    return match ($role) {
        'admin' => 'Administrator',
        'logistics_manager' => 'Logistics & Fleet Manager',
        'inventory_manager' => 'Product & Inventory Manager',
        'support_staff' => 'Customer Service Staff',
        'delivery' => 'Delivery Driver',
        'customer' => 'Customer',
        default => 'Guest',
    };
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
/**
 * Resolves who owns the cart for the current request.
 *
 * This is the single source of truth for cart ownership and the only place a
 * user id enters the cart code path. It reads $_SESSION['user_id'], which is
 * set exclusively by login.php from the database row matching the submitted
 * credentials. Nothing from $_GET, $_POST or the request body is ever
 * consulted, so nobody can reach another account's cart by editing a URL, a
 * form field or a JavaScript payload.
 *
 * Logged in -> ['user_id' => <int>, 'guest_token' => null]
 * Guest     -> ['user_id' => null, 'guest_token' => <random per-browser token>]
 */
function mff_cart_owner(): array
{
    $userId = mff_user_id();
    if ($userId !== null) {
        return ['user_id' => $userId, 'guest_token' => null];
    }
    return ['user_id' => null, 'guest_token' => $_SESSION['cart_guest_token']];
}
/**
 * The authenticated user's id, or null for a guest. Exposed for templates that
 * need to attribute an order to the signed-in account.
 */
function mff_user_id(): ?int
{
    $userId = $_SESSION['user_id'] ?? null;
    return ($userId !== null && is_numeric($userId) && (int) $userId > 0) ? (int) $userId : null;
}
/**
 * Loads the current owner's cart from the database, memoised for the request.
 * Returns [product_id => quantity].
 *
 * If the database is unreachable the cart degrades to empty rather than
 * falling back to a shared/global cart, so a DB outage can never merge two
 * users' baskets.
 */
function mff_cart_rows(): array
{
    if (is_array($GLOBALS['mff_cart_cache'])) {
        return $GLOBALS['mff_cart_cache'];
    }
    $pdo = mff_db();
    if ($pdo === null) {
        return $GLOBALS['mff_cart_cache'] = [];
    }
    $owner = mff_cart_owner();
    try {
        $stmt = $owner['user_id'] !== null
            ? $pdo->prepare('SELECT product_id, quantity FROM cart_items WHERE user_id = :uid')
            : $pdo->prepare('SELECT product_id, quantity FROM cart_items WHERE user_id IS NULL AND guest_token = :token');
        $stmt->execute($owner['user_id'] !== null
            ? ['uid' => $owner['user_id']]
            : ['token' => $owner['guest_token']]);
        $rows = [];
        foreach ($stmt->fetchAll() as $row) {
            $rows[(int) $row['product_id']] = (int) $row['quantity'];
        }
        return $GLOBALS['mff_cart_cache'] = $rows;
    } catch (PDOException $e) {
        error_log('[mff] cart load failed: ' . $e->getMessage());
        return $GLOBALS['mff_cart_cache'] = [];
    }
}
/** Writes the current owner's cart back to the database, replacing it wholesale. */
function mff_cart_persist(array $rows): void
{
    $GLOBALS['mff_cart_cache'] = $rows; // keep this request's reads consistent
    $pdo = mff_db();
    if ($pdo === null) {
        return;
    }
    $owner = mff_cart_owner();
    $isUser = $owner['user_id'] !== null;
    try {
        // Owner-scoped, so this can never delete another account's rows.
        $stmt = $isUser
            ? $pdo->prepare('DELETE FROM cart_items WHERE user_id = :uid')
            : $pdo->prepare('DELETE FROM cart_items WHERE user_id IS NULL AND guest_token = :token');
        $stmt->execute($isUser ? ['uid' => $owner['user_id']] : ['token' => $owner['guest_token']]);
        if (!$rows) {
            return;
        }
        $insert = $pdo->prepare(
            'INSERT INTO cart_items (user_id, guest_token, product_id, quantity)
             VALUES (:uid, :token, :pid, :qty)'
        );
        foreach ($rows as $productId => $quantity) {
            $insert->execute([
                'uid' => $owner['user_id'],
                'token' => $owner['guest_token'],
                'pid' => (int) $productId,
                'qty' => (int) $quantity,
            ]);
        }
    } catch (PDOException $e) {
        error_log('[mff] cart persist failed: ' . $e->getMessage());
    }
}
/** Quantity of a single product in the current owner's cart. */
function cart_qty(int $productId): int
{
    return mff_cart_rows()[$productId] ?? 0;
}
function cart_add(int $productId, int $quantity = 1): void
{
    if ($productId < 1) {
        return;
    }
    $quantity = max(1, $quantity);
    $rows = mff_cart_rows();
    $rows[$productId] = ($rows[$productId] ?? 0) + $quantity;
    mff_cart_persist($rows);
}
function cart_set_qty(int $productId, int $quantity): void
{
    if ($quantity < 1) {
        cart_remove($productId);
        return;
    }
    if ($productId < 1) {
        return;
    }
    $rows = mff_cart_rows();
    $rows[$productId] = $quantity;
    mff_cart_persist($rows);
}
function cart_remove(int $productId): void
{
    $rows = mff_cart_rows();
    if (!array_key_exists($productId, $rows)) {
        return;
    }
    unset($rows[$productId]);
    mff_cart_persist($rows);
}
/**
 * Empties the current owner's cart only. Used after a completed order and by
 * the clear-cart action; the WHERE clause is owner-scoped, so one user can
 * never clear another user's cart.
 */
function cart_clear(): void
{
    mff_cart_persist([]);
}
function cart_count(): int
{
    return array_sum(mff_cart_rows());
}
/**
 * Folds the guest cart into the newly authenticated user's cart at login, then
 * discards the guest rows.
 *
 * Only this browser's own guest cart is merged, and only at the moment the
 * server transitions from guest to authenticated. The user's already-saved cart
 * is topped up rather than replaced, so logging in on a second device restores
 * the cart they left behind instead of resetting it.
 */
function cart_merge_guest_into_user(): void
{
    $userId = mff_user_id();
    $guestToken = $_SESSION['cart_guest_token'] ?? null;
    $GLOBALS['mff_cart_cache'] = null;
    if ($userId === null || empty($guestToken)) {
        return;
    }
    $pdo = mff_db();
    if ($pdo === null) {
        return;
    }
    try {
        $userRows = [];
        $stmt = $pdo->prepare('SELECT product_id, quantity FROM cart_items WHERE user_id = :uid');
        $stmt->execute(['uid' => $userId]);
        foreach ($stmt->fetchAll() as $row) {
            $userRows[(int) $row['product_id']] = (int) $row['quantity'];
        }
        $guestRows = [];
        $stmt = $pdo->prepare(
            'SELECT product_id, quantity FROM cart_items WHERE user_id IS NULL AND guest_token = :token'
        );
        $stmt->execute(['token' => $guestToken]);
        foreach ($stmt->fetchAll() as $row) {
            $guestRows[(int) $row['product_id']] = (int) $row['quantity'];
        }
        foreach ($guestRows as $productId => $quantity) {
            $userRows[$productId] = ($userRows[$productId] ?? 0) + $quantity;
        }
        $pdo->prepare('DELETE FROM cart_items WHERE user_id = :uid')->execute(['uid' => $userId]);
        // Guest rows are dropped so the same items cannot be merged twice.
        $pdo->prepare('DELETE FROM cart_items WHERE user_id IS NULL AND guest_token = :token')
            ->execute(['token' => $guestToken]);
        $insert = $pdo->prepare(
            'INSERT INTO cart_items (user_id, guest_token, product_id, quantity)
             VALUES (:uid, NULL, :pid, :qty)'
        );
        foreach ($userRows as $productId => $quantity) {
            $insert->execute(['uid' => $userId, 'pid' => (int) $productId, 'qty' => (int) $quantity]);
        }
        // A fresh token, so if this browser later logs out it starts from an
        // empty guest cart instead of resurrecting the merged items.
        $_SESSION['cart_guest_token'] = bin2hex(random_bytes(32));
    } catch (PDOException $e) {
        error_log('[mff] cart merge failed: ' . $e->getMessage());
    }
    $GLOBALS['mff_cart_cache'] = null;
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
    foreach (mff_cart_rows() as $productId => $quantity) {
        $productId = (int) $productId;
        $quantity = (int) $quantity;
        if ($quantity < 1) {
            continue;
        }
        $product = mff_get_product($productId);
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
/**
 * True if the request is an XHR / fetch() call expecting JSON back.
 * The front end appends ?ajax=1 to every cart fetch (see main.js) so JSON is
 * returned even when a proxy strips custom headers, and the browser-set
 * Sec-Fetch-* hints are used as a final safety net: fetch()/XHR always send
 * mode=cors|same-origin or dest=empty, whereas a plain form POST sends
 * mode=navigate / dest=document.
 */
function mff_wants_json(): bool
{
    $secFetchMode = strtolower($_SERVER['HTTP_SEC_FETCH_MODE'] ?? '');
    $secFetchDest = strtolower($_SERVER['HTTP_SEC_FETCH_DEST'] ?? '');
    return (
        (isset($_GET['ajax']) && $_GET['ajax'] === '1')
        || (isset($_POST['ajax']) && $_POST['ajax'] === '1')
        || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json'))
        || in_array($secFetchMode, ['cors', 'same-origin'], true)
        || $secFetchDest === 'empty'
    );
}
function mff_money(float $n): string
{
    return '$' . number_format($n, 2);
}
