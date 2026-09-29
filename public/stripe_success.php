<?php
require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';
require_once file_exists(__DIR__ . '/../includes/stripe_config.php') ? __DIR__ . '/../includes/stripe_config.php' : __DIR__ . '/includes/stripe_config.php';

$sessionId = $_GET['session_id'] ?? '';
$errors = [];

if (empty($sessionId) || empty($_SESSION['pending_checkout'])) {
    mff_set_flash('error', 'Invalid or missing session information.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

try {
    $session = \Stripe\Checkout\Session::retrieve($sessionId);

    if ($session->payment_status === 'paid') {
        $pdo = mff_db();
        $orderId = null;
        $cart = cart_contents();
        $pending = $_SESSION['pending_checkout'];
        $stripeSessionId = (string) ($session->id ?? $sessionId);

        // Idempotency — Stripe or the browser can deliver this success URL
        // more than once for the SAME payment. The unique
        // orders.stripe_session_id column is the reference check: an order
        // that already exists for this Checkout Session is reused, never
        // duplicated (covers refresh, back-button and webhook-style retries).
        $existingOrderId = null;
        if ($pdo !== null) {
            try {
                $dupStmt = $pdo->prepare('SELECT id FROM orders WHERE stripe_session_id = :sid');
                $dupStmt->execute(['sid' => $stripeSessionId]);
                $dupRow = $dupStmt->fetch();
                if ($dupRow !== false) {
                    $existingOrderId = (int) $dupRow['id'];
                }
            } catch (PDOException $e) {
                error_log('[mff] stripe success duplicate check failed: ' . $e->getMessage());
            }
        }

        if ($pdo !== null && $existingOrderId !== null) {
            $orderId = $existingOrderId; // this payment is already recorded
        } elseif ($pdo !== null) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    'INSERT INTO orders (user_id, customer_name, contact_number, delivery_address, delivery_instructions,
                                          payment_method, subtotal, tax, total, status, stripe_session_id, created_at)
                     VALUES (:user_id, :name, :contact, :address, :instructions, :payment, :subtotal, :tax, :total, "pending", :stripe_session_id, CURRENT_TIMESTAMP)'
                );
                $stmt->execute([
                    'user_id' => $_SESSION['user_id'] ?? null,
                    'name' => $pending['customer_name'], 
                    'contact' => $pending['contact_number'], 
                    'address' => $pending['delivery_address'],
                    'instructions' => $pending['delivery_instructions'], 
                    'payment' => $pending['payment_method'],
                    'subtotal' => $cart['subtotal'], 
                    'tax' => $cart['tax'], 
                    'total' => $cart['total'],
                    'stripe_session_id' => $stripeSessionId,
                ]);
                $orderId = (int) $pdo->lastInsertId();

                $itemStmt = $pdo->prepare(
                    'INSERT INTO order_items (order_id, product_id, name, price, quantity) VALUES (:order_id, :product_id, :name, :price, :quantity)'
                );
                foreach ($cart['items'] as $item) {
                    $itemStmt->execute([
                        'order_id' => $orderId, 
                        'product_id' => $item['product_id'],
                        'name' => $item['name'], 
                        'price' => $item['price'], 
                        'quantity' => $item['quantity'],
                    ]);
                }

                $pdo->commit();
            } catch (PDOException $e) {
                $pdo->rollBack();
                // Losing the idempotency race to a concurrent request for the
                // same payment is NOT an error — reuse the winner's order.
                $isDuplicate = strpos($e->getMessage(), 'UNIQUE') !== false
                    || (int) ($e->errorInfo[1] ?? 0) === 1062;
                if ($isDuplicate) {
                    try {
                        $again = $pdo->prepare('SELECT id FROM orders WHERE stripe_session_id = :sid');
                        $again->execute(['sid' => $stripeSessionId]);
                        $againRow = $again->fetch();
                        if ($againRow !== false) {
                            $existingOrderId = (int) $againRow['id'];
                            $orderId = $existingOrderId;
                        }
                    } catch (PDOException $againE) {
                        error_log('[mff] stripe success duplicate lookup failed: ' . $againE->getMessage());
                    }
                }
                if ($orderId === null) {
                    error_log('[mff] order insert failed on stripe success: ' . $e->getMessage());
                    $errors[] = 'Payment succeeded, but we could not place your order. Please contact support.';
                }
            }
        }

        if (empty($errors)) {
            // Replay / concurrency safety: if this payment was already
            // recorded (or a parallel tab cleared the cart first), rebuild
            // the confirmation data from the STORED order so the customer
            // still sees their real order — never a duplicate one.
            $rebuilt = null;
            if ($pdo !== null && ($existingOrderId !== null || $cart['items'] === [])) {
                try {
                    $rowStmt = $pdo->prepare('SELECT * FROM orders WHERE id = :id');
                    $rowStmt->execute(['id' => $orderId]);
                    $reRow = $rowStmt->fetch();
                    if ($reRow !== false) {
                        $reItems = [];
                        $reItemStmt = $pdo->prepare('SELECT name, price, quantity FROM order_items WHERE order_id = :id ORDER BY id');
                        $reItemStmt->execute(['id' => $orderId]);
                        foreach ($reItemStmt->fetchAll() as $ri) {
                            $reItems[] = [
                                'product_id' => null,
                                'name' => $ri['name'],
                                'price' => (float) $ri['price'],
                                'quantity' => (int) $ri['quantity'],
                                'line_total' => round((float) $ri['price'] * (int) $ri['quantity'], 2),
                            ];
                        }
                        $rebuilt = [
                            'id' => (int) $reRow['id'],
                            'customer_name' => $reRow['customer_name'],
                            'contact_number' => $reRow['contact_number'],
                            'delivery_address' => $reRow['delivery_address'],
                            'delivery_instructions' => $reRow['delivery_instructions'],
                            'payment_method' => $reRow['payment_method'],
                            'customer_email' => (string) ($_SESSION['user_email'] ?? ''),
                            'stripe_session_id' => (string) ($reRow['stripe_session_id'] ?? $stripeSessionId),
                            'items' => $reItems,
                            'subtotal' => (float) $reRow['subtotal'],
                            'tax' => (float) $reRow['tax'],
                            'total' => (float) $reRow['total'],
                            'status' => (string) $reRow['status'],
                            'created_at' => (string) $reRow['created_at'],
                        ];
                    }
                } catch (PDOException $e) {
                    error_log('[mff] stripe success order reload failed: ' . $e->getMessage());
                }
            }
            $_SESSION['last_order'] = $rebuilt ?? [
                'id' => $orderId ?? random_int(3000, 3999),
                'customer_name' => $pending['customer_name'],
                'contact_number' => $pending['contact_number'],
                'delivery_address' => $pending['delivery_address'],
                'delivery_instructions' => $pending['delivery_instructions'],
                'payment_method' => $pending['payment_method'],
                'customer_email' => (string) ($_SESSION['user_email'] ?? ''),
                'stripe_session_id' => $stripeSessionId,
                'items' => $cart['items'],
                'subtotal' => $cart['subtotal'],
                'tax' => $cart['tax'],
                'total' => $cart['total'],
                'status' => 'pending',
                'created_at' => date('Y-m-d H:i:s'),
            ];
            
            cart_clear();
            unset($_SESSION['pending_checkout']);
            mff_set_role('customer');
            header('Location: ' . BASE_URL . '/order_confirmation.php');
            exit;
        }
    } else {
         $errors[] = 'Payment not completed.';
    }
} catch (Exception $e) {
    error_log('[mff] stripe error on success: ' . $e->getMessage());
    $errors[] = 'Error validating payment.';
}

$pageTitle = 'Payment Error';
require file_exists(__DIR__ . '/../includes/header.php') ? __DIR__ . '/../includes/header.php' : __DIR__ . '/includes/header.php';
?>
<div>
  <h1 class="section-title">Payment Error</h1>
  <div class="flash flash--error">
    <?php foreach ($errors as $error): ?><p><?= htmlspecialchars($error) ?></p><?php endforeach; ?>
  </div>
  <p><a href="<?= BASE_URL ?>/checkout.php">Return to Checkout</a></p>
</div>
<?php require file_exists(__DIR__ . '/../includes/footer.php') ? __DIR__ . '/../includes/footer.php' : __DIR__ . '/includes/footer.php'; ?>
