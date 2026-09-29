<?php
/**
 * download_receipt.php — authorized PDF receipt endpoint.
 * Date: 29/09/2026
 * Purpose: Streams a PDF receipt for an order, but ONLY after proving that
 *   (a) the current session is allowed to see that order, and
 *   (b) Stripe still reports the backing Checkout Session as paid.
 *
 * Security: a sequential id in the URL is never enough on its own — see
 * mff_receipt_authorized() in includes/receipt.php. No receipt file is ever
 * written to a public path; the PDF is generated in memory per request.
 */

require_once file_exists(__DIR__ . '/../includes/session.php')
    ? __DIR__ . '/../includes/session.php'
    : __DIR__ . '/includes/session.php';
require_once file_exists(__DIR__ . '/../includes/stripe_config.php')
    ? __DIR__ . '/../includes/stripe_config.php'
    : __DIR__ . '/includes/stripe_config.php';
require_once file_exists(__DIR__ . '/../includes/receipt.php')
    ? __DIR__ . '/../includes/receipt.php'
    : __DIR__ . '/includes/receipt.php';

if (!headers_sent()) {
    header('X-Content-Type-Options: nosniff');
}

/**
 * Minimal, CSP-safe error page (no inline styles/scripts — the site's CSP
 * forbids them). Never touches the order, the payment, or the cart.
 */
function mff_receipt_fail(int $status, string $title, string $message, ?string $retryUrl = null): void
{
    http_response_code($status);
    if (!headers_sent()) {
        header('Content-Type: text/html; charset=utf-8');
    }
    $store = mff_receipt_store();
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $safeMessage = htmlspecialchars($message, ENT_QUOTES, 'UTF-8');
    $safeStore = htmlspecialchars($store['name'], ENT_QUOTES, 'UTF-8');
    $retryHtml = '';
    if ($retryUrl !== null && $retryUrl !== '') {
        $safeRetry = htmlspecialchars($retryUrl, ENT_QUOTES, 'UTF-8');
        $retryHtml = '<p><a href="' . $safeRetry . '">Try again</a></p>';
    }
    $homeUrl = htmlspecialchars(BASE_URL . '/index.php', ENT_QUOTES, 'UTF-8');
    echo '<!DOCTYPE html><html lang="en"><head><meta charset="utf-8">'
        . '<meta name="viewport" content="width=device-width, initial-scale=1">'
        . '<title>' . $safeTitle . ' | ' . $safeStore . '</title></head><body>'
        . '<main><h1>' . $safeTitle . '</h1><p>' . $safeMessage . '</p>'
        . $retryHtml
        . '<p><a href="' . $homeUrl . '">Back to home</a></p>'
        . '</main></body></html>';
    exit;
}

$orderId = (int) ($_GET['id'] ?? 0);
$retryUrl = (string) ($_SERVER['REQUEST_URI'] ?? '');

if ($orderId <= 0) {
    mff_receipt_fail(404, 'Receipt not found', 'The receipt you requested does not exist.');
}

$order = mff_receipt_order($orderId);

// Unknown id and forbidden id return the SAME response, so sequential ids
// cannot be probed to discover which orders exist.
if ($order === null || !mff_receipt_authorized($order)) {
    mff_receipt_fail(403, 'Access denied', 'You are not allowed to download this receipt.');
}

// Receipts exist only for orders actually paid through Stripe Checkout.
if ($order['payment_method'] !== 'credit_card') {
    mff_receipt_fail(
        403,
        'Receipt unavailable',
        'A digital receipt is issued for orders paid by card through Stripe.'
    );
}
if ($order['stripe_session_id'] === '') {
    mff_receipt_fail(
        403,
        'Receipt unavailable',
        'This order has no Stripe payment attached, so a receipt cannot be issued.'
    );
}
// (b) Ask Stripe — server-side — whether this payment really succeeded.
// A URL parameter or a client-side "success" claim is never trusted.
try {
    $session = \Stripe\Checkout\Session::retrieve($order['stripe_session_id']);
} catch (\Exception $e) {
    error_log('[mff] receipt Stripe verification failed: ' . $e->getMessage());
    mff_receipt_fail(
        503,
        'Receipt unavailable',
        'Your payment was successful, but your receipt could not be generated right now. Please try again.',
        $retryUrl
    );
}

if ($session->payment_status !== 'paid') {
    mff_receipt_fail(
        403,
        'Payment not confirmed',
        'Stripe has not confirmed this payment as successful, so no receipt can be issued.'
    );
}

// Soft cross-check: the stored total must equal what Stripe actually
// charged. A mismatch is logged loudly but never blocks the customer and
// never alters the order or the payment.
$paidCents = (int) ($session->amount_total ?? -1);
$storedCents = (int) round($order['total'] * 100);
if ($paidCents !== $storedCents) {
    error_log(sprintf(
        '[mff] receipt amount mismatch: order=%d stripe=%d stored=%d',
        $orderId,
        $paidCents,
        $storedCents
    ));
}

// Generate the PDF. Any failure here leaves the order/payment untouched and
// simply lets the customer retry (requirement: never mark payment failed,
// never re-charge, never duplicate).
try {
    $pdfBytes = mff_receipt_pdf($order, [
        'payment_intent' => is_string($session->payment_intent ?? null) ? $session->payment_intent : '',
        'amount_total' => $paidCents,
    ]);
} catch (\Throwable $e) {
    error_log('[mff] receipt generation failed: ' . $e->getMessage());
    mff_receipt_fail(
        503,
        'Receipt unavailable',
        'Your payment was successful, but your receipt could not be generated right now. Please try again.',
        $retryUrl
    );
}

$filename = 'receipt-MFF-' . $order['id'] . '.pdf';
header('Content-Type: application/pdf');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . strlen($pdfBytes));
header('Cache-Control: private, no-store');
header('Pragma: no-cache');
echo $pdfBytes;
exit;

