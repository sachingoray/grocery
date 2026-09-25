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

        if ($pdo !== null) {
            try {
                $pdo->beginTransaction();

                $stmt = $pdo->prepare(
                    'INSERT INTO orders (user_id, customer_name, contact_number, delivery_address, delivery_instructions,
                                          payment_method, subtotal, tax, total, status, created_at)
                     VALUES (:user_id, :name, :contact, :address, :instructions, :payment, :subtotal, :tax, :total, "pending", CURRENT_TIMESTAMP)'
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
                error_log('[mff] order insert failed on stripe success: ' . $e->getMessage());
                $errors[] = 'Payment succeeded, but we could not place your order. Please contact support.';
            }
        }

        if (empty($errors)) {
            $_SESSION['last_order'] = [
                'id' => $orderId ?? random_int(3000, 3999),
                'customer_name' => $pending['customer_name'],
                'contact_number' => $pending['contact_number'],
                'delivery_address' => $pending['delivery_address'],
                'delivery_instructions' => $pending['delivery_instructions'],
                'payment_method' => $pending['payment_method'],
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
