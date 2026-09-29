<?php
/**
 * receipt.php — digital PDF receipt builder for paid orders.
 * Date: 29/09/2026
 * Purpose: Loads an order the current session is allowed to see, and renders
 * a branded A4 PDF receipt with FPDF (includes/lib/fpdf.php).
 *
 * Included by public/download_receipt.php only — not by HTML pages — so the
 * PDF library is loaded only when a receipt is actually generated.
 *
 * Design rules:
 *   - Totals come straight from the stored order (the same values the cart
 *     charged through Stripe); they are never recalculated here.
 *   - Receipt number reuses the site's existing #MFF-<order id> system.
 *   - Store details are copied verbatim from includes/footer.php; nothing is
 *     invented.
 */

require_once __DIR__ . '/lib/fpdf.php';

/** Store identity — values mirror includes/footer.php exactly. */
function mff_receipt_store(): array
{
    return [
        'name' => 'Maxi Fine Foods',
        'address' => '42 Market Street, Sydney NSW 2000',
        'phone' => '1800 629 436 (Toll-Free)',
        'email' => 'support@maxifinefoods.com.au',
        'company' => 'Maxi Fine Foods Pty Ltd',
        'abn' => 'ABN 84 192 847 291',
    ];
}

/** Human label for the stored payment_method value. */
function mff_receipt_payment_label(string $method): string
{
    return match ($method) {
        'credit_card' => 'Credit / Debit card (Stripe)',
        'paypal' => 'PayPal',
        'cash_on_delivery' => 'Cash on delivery',
        default => ucwords(str_replace('_', ' ', $method)),
    };
}

/** Normalise an orders row / $_SESSION['last_order'] array into one shape. */
function mff_receipt_normalise(array $row, array $items): array
{
    return [
        'id' => (int) ($row['id'] ?? 0),
        'user_id' => (isset($row['user_id']) && $row['user_id'] !== null && $row['user_id'] !== '')
            ? (int) $row['user_id']
            : null,
        'customer_name' => (string) ($row['customer_name'] ?? ''),
        'contact_number' => (string) ($row['contact_number'] ?? ''),
        'delivery_address' => (string) ($row['delivery_address'] ?? ''),
        'delivery_instructions' => (string) ($row['delivery_instructions'] ?? ''),
        'payment_method' => (string) ($row['payment_method'] ?? ''),
        'subtotal' => (float) ($row['subtotal'] ?? 0),
        'tax' => (float) ($row['tax'] ?? 0),
        'total' => (float) ($row['total'] ?? 0),
        'status' => (string) ($row['status'] ?? ''),
        'created_at' => (string) ($row['created_at'] ?? ''),
        'stripe_session_id' => (string) ($row['stripe_session_id'] ?? ''),
        'customer_email' => (string) ($row['customer_email'] ?? ''),
        'items' => $items,
    ];
}
/**
 * Load an order + its items. Prefers the database; falls back to this
 * session's own last_order (covers DB-outage / fallback mode, where the
 * confirmation page already shows session data).
 */
function mff_receipt_order(int $orderId): ?array
{
    if ($orderId <= 0) {
        return null;
    }

    $pdo = mff_db();
    if ($pdo !== null) {
        try {
            $stmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id');
            $stmt->execute(['id' => $orderId]);
            $row = $stmt->fetch();
            if ($row !== false) {
                $itemStmt = $pdo->prepare('SELECT * FROM order_items WHERE order_id = :id ORDER BY id');
                $itemStmt->execute(['id' => $orderId]);
                $items = [];
                foreach ($itemStmt->fetchAll() as $i) {
                    $items[] = [
                        'name' => (string) $i['name'],
                        'price' => (float) $i['price'],
                        'quantity' => (int) $i['quantity'],
                        'line_total' => round((float) $i['price'] * (int) $i['quantity'], 2),
                    ];
                }
                return mff_receipt_normalise($row, $items);
            }
        } catch (PDOException $e) {
            error_log('[mff] receipt order load failed: ' . $e->getMessage());
        }
    }

    $last = $_SESSION['last_order'] ?? null;
    if (is_array($last) && (int) ($last['id'] ?? 0) === $orderId) {
        $items = [];
        foreach (($last['items'] ?? []) as $i) {
            $price = (float) ($i['price'] ?? 0);
            $qty = (int) ($i['quantity'] ?? 0);
            $items[] = [
                'name' => (string) ($i['name'] ?? ''),
                'price' => $price,
                'quantity' => $qty,
                'line_total' => (float) ($i['line_total'] ?? round($price * $qty, 2)),
            ];
        }
        return mff_receipt_normalise($last, $items);
    }

    return null;
}

/**
 * Authorization: may the CURRENT session download this order's receipt?
 *
 * A sequential id in the URL must never be enough. Allowed:
 *   1. admins,
 *   2. the account that owns the order (orders.user_id = session user),
 *   3. the exact browser session that just paid (session last_order matches
 *      the order id AND the Stripe session reference / customer name),
 *   4. a logged-in customer viewing a guest order under the same name —
 *      the same visibility my_orders.php already applies, restricted to
 *      guest orders so other customers' card orders can't be scanned.
 */
function mff_receipt_authorized(array $order): bool
{
    if ($order['id'] <= 0) {
        return false;
    }
    if (mff_role() === 'admin') {
        return true;
    }

    $userId = mff_user_id();
    if ($order['user_id'] !== null && $userId !== null && $order['user_id'] === $userId) {
        return true;
    }

    $last = $_SESSION['last_order'] ?? null;
    if (is_array($last) && (int) ($last['id'] ?? 0) === $order['id']) {
        $lastSid = trim((string) ($last['stripe_session_id'] ?? ''));
        $orderSid = trim($order['stripe_session_id']);
        if ($lastSid !== '' && $orderSid !== '') {
            return hash_equals($orderSid, $lastSid);
        }
        $lastName = trim((string) ($last['customer_name'] ?? ''));
        return $lastName !== '' && $lastName === trim($order['customer_name']);
    }

    if (
        $order['user_id'] === null
        && $userId !== null
        && $order['customer_name'] !== ''
        && trim((string) ($_SESSION['user_name'] ?? '')) === trim($order['customer_name'])
    ) {
        return true;
    }

    return false;
}

/**
 * Customer e-mail for the receipt. Orders have no e-mail column, so the
 * session address is only used when it belongs to the account that owns the
 * order (an admin must never leak their own address onto someone's receipt).
 */
function mff_receipt_email(array $order): string
{
    if ($order['customer_email'] !== '') {
        return $order['customer_email'];
    }
    if ($order['user_id'] !== null && mff_user_id() === $order['user_id']) {
        return (string) ($_SESSION['user_email'] ?? '');
    }
    return '';
}
/** UTF-8 → Latin-1 so FPDF's core fonts can render any stored string. */
function mff_receipt_text(string $value): string
{
    if (!mb_check_encoding($value, 'UTF-8')) {
        $value = mb_convert_encoding($value, 'UTF-8', 'Windows-1252');
    }
    $value = preg_replace('/[^\x{0000}-\x{00FF}]/u', '?', $value) ?? '?';
    return mb_convert_encoding($value, 'ISO-8859-1', 'UTF-8');
}

/** Greedy word wrap measured with the current FPDF font. */
function mff_receipt_wrap(FPDF $pdf, string $text, float $maxWidth): array
{
    $words = preg_split('/\s+/', trim($text)) ?: [];
    $lines = [];
    $current = '';
    foreach ($words as $word) {
        if ($word === '') {
            continue;
        }
        $candidate = ($current === '') ? $word : $current . ' ' . $word;
        if ($pdf->GetStringWidth($candidate) <= $maxWidth) {
            $current = $candidate;
        } else {
            if ($current !== '') {
                $lines[] = $current;
            }
            $current = $word;
        }
    }
    if ($current !== '') {
        $lines[] = $current;
    }
    return $lines === [] ? [''] : $lines;
}

/** "$12.34" — identical formatting to mff_money() used by the checkout. */
function mff_receipt_amount(float $n): string
{
    return '$' . number_format($n, 2);
}
/**
 * Render the receipt PDF. $meta carries the Stripe verification results
 * collected by the download endpoint (payment intent id, etc.).
 * Returns the raw PDF bytes.
 */
function mff_receipt_pdf(array $order, array $meta = []): string
{
    $store = mff_receipt_store();
    $receiptNo = 'MFF-' . $order['id'];

    // Brand palette — public/assets/style.css :root
    $ink = [25, 55, 39];      // --ink
    $tomato = [200, 70, 52];  // --tomato
    $oat = [233, 224, 204];   // --oat
    $lineC = [216, 209, 191]; // --line

    $pdf = new FPDF('P', 'mm', 'A4');
    // Layout and pagination are fully managed below (rows break at a fixed
    // y), so FPDF's automatic page break must stay off — otherwise a footer
    // drawn near the bottom edge silently appends a blank second page.
    $pdf->SetAutoPageBreak(false);
    $pdf->SetTitle('Receipt ' . $receiptNo);
    $pdf->SetAuthor($store['name']);
    $pdf->SetCreator($store['name']);
    $pdf->AddPage();

    $L = 15.0;
    $pageW = 210.0;
    $cw = $pageW - 2 * $L; // 180mm content width
    $R = $L + $cw;         // 195mm right edge

    // ---- Header band --------------------------------------------------
    $pdf->SetFillColor($ink[0], $ink[1], $ink[2]);
    $pdf->Rect(0, 0, $pageW, 34, 'F');
    $pdf->SetTextColor(255, 255, 255);
    $pdf->SetFont('Helvetica', 'B', 17);
    $pdf->SetXY($L, 9);
    $pdf->Cell($cw * 0.6, 8, mff_receipt_text($store['name']), 0, 0, 'L');
    $pdf->SetFont('Helvetica', '', 8.5);
    $pdf->SetXY($L, 18.5);
    $pdf->Cell($cw * 0.6, 5, 'Digital receipt', 0, 0, 'L');
    $pdf->SetFont('Helvetica', 'B', 15);
    $pdf->SetXY($L, 8);
    $pdf->Cell($cw, 8, 'RECEIPT', 0, 0, 'R');
    $pdf->SetFont('Helvetica', '', 11);
    $pdf->SetXY($L, 17);
    $pdf->Cell($cw, 6, $receiptNo, 0, 0, 'R');

    // ---- Store contact line (from footer.php) -------------------------
    $pdf->SetTextColor(80, 80, 80);
    $pdf->SetFont('Helvetica', '', 8);
    $pdf->SetXY($L, 39);
    $pdf->Cell($cw, 4, mff_receipt_text($store['address'] . '  ·  ' . $store['phone'] . '  ·  ' . $store['email']), 0, 0, 'L');
    $pdf->SetXY($L, 43.5);
    $pdf->Cell($cw, 4, mff_receipt_text($store['company'] . '  ·  ' . $store['abn']), 0, 0, 'L');

    $pdf->SetDrawColor($lineC[0], $lineC[1], $lineC[2]);
    $pdf->Line($L, 50, $R, 50);

    // ---- Two info boxes: receipt-to / payment -------------------------
    $boxY = 54.0;
    $boxH = 38.0;
    $boxW = ($cw - 6) / 2; // 87mm
    $innerPad = 5.0;
    $innerW = $boxW - 2 * $innerPad;

    foreach ([[$L, 'RECEIPT TO'], [$L + $boxW + 6, 'PAYMENT']] as [$boxX, $eyebrow]) {
        $pdf->SetDrawColor($lineC[0], $lineC[1], $lineC[2]);
        $pdf->Rect($boxX, $boxY, $boxW, $boxH);
        $pdf->SetTextColor($tomato[0], $tomato[1], $tomato[2]);
        $pdf->SetFont('Helvetica', 'B', 7.5);
        $pdf->SetXY($boxX + $innerPad, $boxY + 3);
        $pdf->Cell($innerW, 4, $eyebrow, 0, 0, 'L');
    }

    // Left box — customer
    $leftX = $L + $innerPad;
    $ty = $boxY + 9;
    $pdf->SetTextColor($ink[0], $ink[1], $ink[2]);
    $pdf->SetFont('Helvetica', 'B', 10.5);
    $pdf->SetXY($leftX, $ty);
    $pdf->Cell($innerW, 5.5, mff_receipt_text($order['customer_name']), 0, 0, 'L');
    $ty += 6;
    $pdf->SetFont('Helvetica', '', 9);
    if ($order['contact_number'] !== '') {
        $pdf->SetXY($leftX, $ty);
        $pdf->Cell($innerW, 5, mff_receipt_text($order['contact_number']), 0, 0, 'L');
        $ty += 5;
    }
    $email = mff_receipt_email($order);
    if ($email !== '') {
        $pdf->SetXY($leftX, $ty);
        $pdf->Cell($innerW, 5, mff_receipt_text($email), 0, 0, 'L');
        $ty += 5;
    }
    foreach (mff_receipt_wrap($pdf, $order['delivery_address'], $innerW) as $addrLine) {
        if ($ty > $boxY + $boxH - 4.5) {
            break;
        }
        $pdf->SetXY($leftX, $ty);
        $pdf->Cell($innerW, 5, mff_receipt_text($addrLine), 0, 0, 'L');
        $ty += 5;
    }

    // Right box — payment facts
    $rightX = $L + $boxW + 6 + $innerPad;
    $ty = $boxY + 9;
    $pdf->SetTextColor($ink[0], $ink[1], $ink[2]);
    $pdf->SetFont('Helvetica', 'B', 10.5);
    $pdf->SetXY($rightX, $ty);
    $pdf->Cell($innerW, 5.5, 'Paid - confirmed by Stripe', 0, 0, 'L');
    $ty += 6;
    $pdf->SetFont('Helvetica', '', 9);
    $pdf->SetXY($rightX, $ty);
    $pdf->Cell($innerW, 5, mff_receipt_text(mff_receipt_payment_label($order['payment_method'])), 0, 0, 'L');
    $ty += 5;
    $paidAt = $order['created_at'] !== ''
        ? date('j M Y, g:i A', strtotime($order['created_at']))
        : date('j M Y, g:i A');
    $pdf->SetXY($rightX, $ty);
    $pdf->Cell($innerW, 5, mff_receipt_text($paidAt), 0, 0, 'L');
    $ty += 5;
    $pdf->SetFont('Helvetica', '', 7.5);
    if (!empty($meta['payment_intent'])) {
        $pdf->SetXY($rightX, $ty);
        $pdf->Cell($innerW, 4.5, mff_receipt_text('Payment intent: ' . $meta['payment_intent']), 0, 0, 'L');
        $ty += 4.5;
    }
    if ($order['stripe_session_id'] !== '') {
        $pdf->SetXY($rightX, $ty);
        $pdf->Cell($innerW, 4.5, mff_receipt_text('Session: ' . $order['stripe_session_id']), 0, 0, 'L');
    }

    // ---- Items table ---------------------------------------------------
    $colName = [$L, 88.0];
    $colQty = [$L + 88.0, 18.0];
    $colUnit = [$L + 106.0, 32.0];
    $colAmount = [$L + 138.0, 42.0]; // ends at 195

    $tableTop = $boxY + $boxH + 8; // 100

    $drawTableHeader = function (float $y) use ($pdf, $colName, $colQty, $colUnit, $colAmount, $oat, $ink, $R, $L): float {
        $pdf->SetFillColor($oat[0], $oat[1], $oat[2]);
        $pdf->SetTextColor($ink[0], $ink[1], $ink[2]);
        $pdf->SetFont('Helvetica', 'B', 8.5);
        $h = 8.0;
        $pdf->Rect($L, $y, $R - $L, $h, 'F');
        $pdf->SetXY($colName[0] + 2, $y);
        $pdf->Cell($colName[1] - 4, $h, 'Product', 0, 0, 'L');
        $pdf->SetXY($colQty[0], $y);
        $pdf->Cell($colQty[1], $h, 'Qty', 0, 0, 'C');
        $pdf->SetXY($colUnit[0], $y);
        $pdf->Cell($colUnit[1] - 2, $h, 'Unit price', 0, 0, 'R');
        $pdf->SetXY($colAmount[0], $y);
        $pdf->Cell($colAmount[1] - 2, $h, 'Amount', 0, 0, 'R');
        return $y + $h;
    };

    $y = $drawTableHeader($tableTop);
    if ($order['items'] === []) {
        // Session-only orders in rare race conditions can lack items; the
        // stored totals are still authoritative and shown below.
        $pdf->SetTextColor(95, 95, 95);
        $pdf->SetFont('Helvetica', 'I', 9);
        $pdf->SetXY($L + 2, $y + 2);
        $pdf->Cell(100, 8, 'No line items recorded for this order.', 0, 0, 'L');
        $y += 12;
    }

    foreach ($order['items'] as $item) {
        $nameLines = mff_receipt_wrap($pdf, $item['name'], $colName[1] - 6);
        $rowH = max(8.0, 4.8 * count($nameLines) + 3.2);
        if ($y + $rowH > 250) {
            $pdf->AddPage();
            $pdf->SetFillColor($ink[0], $ink[1], $ink[2]);
            $pdf->SetTextColor(255, 255, 255);
            $pdf->SetFont('Helvetica', 'B', 11);
            $pdf->Rect(0, 0, $pageW, 18, 'F');
            $pdf->SetXY($L, 6);
            $pdf->Cell($cw, 7, mff_receipt_text($store['name'] . ' - receipt ' . $receiptNo . ' (continued)'), 0, 0, 'L');
            $y = $drawTableHeader(24);
        }

        $pdf->SetDrawColor($lineC[0], $lineC[1], $lineC[2]);
        $pdf->Line($L, $y + $rowH, $R, $y + $rowH);

        $pdf->SetTextColor($ink[0], $ink[1], $ink[2]);
        $pdf->SetFont('Helvetica', '', 9.5);
        $lineY = $y + 1.7;
        foreach ($nameLines as $nameLine) {
            $pdf->SetXY($colName[0] + 2, $lineY);
            $pdf->Cell($colName[1] - 4, 4.8, mff_receipt_text($nameLine), 0, 0, 'L');
            $lineY += 4.8;
        }
        $pdf->SetXY($colQty[0], $y);
        $pdf->Cell($colQty[1], $rowH, (string) $item['quantity'], 0, 0, 'C');
        $pdf->SetXY($colUnit[0], $y);
        $pdf->Cell($colUnit[1] - 2, $rowH, mff_receipt_amount((float) $item['price']), 0, 0, 'R');
        $pdf->SetXY($colAmount[0], $y);
        $pdf->Cell($colAmount[1] - 2, $rowH, mff_receipt_amount((float) $item['line_total']), 0, 0, 'R');
        $y += $rowH;
    }

    // ---- Totals (stored values — identical to what Stripe charged) ----
    $y += 8;
    if ($y + 55 > 250) {
        $pdf->AddPage();
        $y = 30;
    }

    $totalsW = 80.0;
    $totalsX = $R - $totalsW;
    $labelW = 46.0;
    $valueW = $totalsW - $labelW - 2;

    $totalsRow = function (float $yy, string $label, string $value, bool $bold) use ($pdf, $totalsX, $labelW, $valueW, $ink): float {
        $pdf->SetTextColor(...($bold ? $ink : [95, 95, 95]));
        $pdf->SetFont('Helvetica', $bold ? 'B' : '', 10);
        $pdf->SetXY($totalsX, $yy);
        $pdf->Cell($labelW, 6, $label, 0, 0, 'L');
        $pdf->SetXY($totalsX + $labelW, $yy);
        $pdf->Cell($valueW, 6, $value, 0, 0, 'R');
        return $yy + 6;
    };

    $y = $totalsRow($y, 'Subtotal', mff_receipt_amount($order['subtotal']), false);
    if ($order['tax'] > 0) {
        $y = $totalsRow($y, 'GST', mff_receipt_amount($order['tax']), false);
    }

    // Total — tomato accent, matching the site's primary action colour.
    $pdf->SetDrawColor($ink[0], $ink[1], $ink[2]);
    $pdf->Line($totalsX, $y + 1, $R, $y + 1);
    $y += 4;
    $pdf->SetTextColor($ink[0], $ink[1], $ink[2]);
    $pdf->SetFont('Helvetica', 'B', 13);
    $pdf->SetXY($totalsX, $y);
    $pdf->Cell($labelW, 8, 'Total', 0, 0, 'L');
    $pdf->SetTextColor($tomato[0], $tomato[1], $tomato[2]);
    $pdf->SetXY($totalsX + $labelW, $y);
    $pdf->Cell($valueW, 8, mff_receipt_amount($order['total']), 0, 0, 'R');
    $y += 8;

    $pdf->SetTextColor(95, 95, 95);
    $pdf->SetFont('Helvetica', '', 7.5);
    $pdf->SetXY($totalsX - 25, $y + 2);
    $pdf->Cell($totalsW + 25, 4, 'All amounts in Australian Dollars (AUD). GST is 10% of subtotal.', 0, 0, 'R');

    // ---- Footer --------------------------------------------------------
    $footY = 266.0;
    if ($y + 20 > $footY) {
        $footY = $y + 14;
    }
    $pdf->SetDrawColor($lineC[0], $lineC[1], $lineC[2]);
    $pdf->Line($L, $footY, $R, $footY);
    $pdf->SetTextColor($ink[0], $ink[1], $ink[2]);
    $pdf->SetFont('Helvetica', 'I', 9.5);
    $pdf->SetXY($L, $footY + 3);
    $pdf->Cell($cw, 5, mff_receipt_text('Thank you for shopping with ' . $store['name'] . '.'), 0, 0, 'C');
    $pdf->SetTextColor(120, 120, 120);
    $pdf->SetFont('Helvetica', '', 7.5);
    $pdf->SetXY($L, $footY + 9);
    $pdf->Cell(
        $cw,
        4,
        mff_receipt_text(
            'Receipt ' . $receiptNo . ' · Generated ' . date('j M Y, g:i A')
            . ' · Issued after Stripe confirmed the payment.'
        ),
        0,
        0,
        'C'
    );

    return $pdf->Output('S');
}




