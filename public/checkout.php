<?php
require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';

$cart = cart_contents();
$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (empty($cart['items'])) {
        mff_set_flash('error', 'Your cart is empty — add something before checking out.');
        header('Location: ' . BASE_URL . '/index.php');
        exit;
    }

    $fullName = trim($_POST['full_name'] ?? '');
    $contactNumber = trim($_POST['contact_number'] ?? '');
    $address = trim($_POST['delivery_address'] ?? '');
    $instructions = trim($_POST['delivery_instructions'] ?? '');
    $paymentMethod = $_POST['payment_method'] ?? 'cash_on_delivery';

    if ($fullName === '') $errors[] = 'Full name is required.';
    if ($contactNumber === '') {
        $errors[] = 'Contact number is required.';
    } elseif (preg_match('/[a-zA-Z]/', $contactNumber)) {
        $errors[] = 'Contact number must contain numbers only (no alphabetic characters).';
    } elseif (!preg_match('/^[0-9\s\+\-\(\)]{8,20}$/', $contactNumber)) {
        $errors[] = 'Please enter a valid contact phone number (at least 8 digits).';
    }
    if ($address === '') $errors[] = 'Delivery address is required.';
    if (!in_array($paymentMethod, ['cash_on_delivery', 'credit_card', 'paypal'], true)) {
        $errors[] = 'Choose a valid payment method.';
    }

    if (empty($errors)) {
        if ($paymentMethod === 'credit_card') {
            require_once __DIR__ . '/../includes/stripe_config.php';
            $domain = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://$_SERVER[HTTP_HOST]" . BASE_URL;
            
            $lineItems = [];
            foreach ($cart['items'] as $item) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'aud',
                        'product_data' => [
                            'name' => $item['name'],
                        ],
                        'unit_amount' => (int) round($item['price'] * 100),
                    ],
                    'quantity' => $item['quantity'],
                ];
            }
            
            if ($cart['tax'] > 0) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'aud',
                        'product_data' => [
                            'name' => 'Tax (GST)',
                        ],
                        'unit_amount' => (int) round($cart['tax'] * 100),
                    ],
                    'quantity' => 1,
                ];
            }

            try {
                $checkout_session = \Stripe\Checkout\Session::create([
                    'payment_method_types' => ['card'],
                    'line_items' => $lineItems,
                    'mode' => 'payment',
                    'success_url' => $domain . '/stripe_success.php?session_id={CHECKOUT_SESSION_ID}',
                    'cancel_url' => $domain . '/stripe_cancel.php',
                ]);
                
                $_SESSION['pending_checkout'] = [
                    'customer_name' => $fullName,
                    'contact_number' => $contactNumber,
                    'delivery_address' => $address,
                    'delivery_instructions' => $instructions,
                    'payment_method' => $paymentMethod,
                ];
                
                header("HTTP/1.1 303 See Other");
                header("Location: " . $checkout_session->url);
                exit;
            } catch (Exception $e) {
                error_log('[mff] stripe checkout error: ' . $e->getMessage());
                $errors[] = 'Stripe Error: Could not initialize payment.';
            }
        }
        
        if (empty($errors) && $paymentMethod !== 'credit_card') {
            $pdo = mff_db();
            $orderId = null;

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
                        'name' => $fullName, 'contact' => $contactNumber, 'address' => $address,
                        'instructions' => $instructions, 'payment' => $paymentMethod,
                        'subtotal' => $cart['subtotal'], 'tax' => $cart['tax'], 'total' => $cart['total'],
                    ]);
                    $orderId = (int) $pdo->lastInsertId();

                    $itemStmt = $pdo->prepare(
                        'INSERT INTO order_items (order_id, product_id, name, price, quantity) VALUES (:order_id, :product_id, :name, :price, :quantity)'
                    );
                    foreach ($cart['items'] as $item) {
                        $itemStmt->execute([
                            'order_id' => $orderId, 'product_id' => $item['product_id'],
                            'name' => $item['name'], 'price' => $item['price'], 'quantity' => $item['quantity'],
                        ]);
                    }

                    $pdo->commit();
                } catch (PDOException $e) {
                    $pdo->rollBack();
                    error_log('[mff] order insert failed: ' . $e->getMessage());
                    $errors[] = 'We could not place your order right now — please try again.';
                }
            }

            if (empty($errors)) {
                // No live DB (or insert succeeded) — either way, stash a session
                // receipt so order_confirmation.php has something to show even
                // in fallback mode.
                $_SESSION['last_order'] = [
                    'id' => $orderId ?? random_int(3000, 3999),
                    'customer_name' => $fullName,
                    'contact_number' => $contactNumber,
                    'delivery_address' => $address,
                    'delivery_instructions' => $instructions,
                    'payment_method' => $paymentMethod,
                    'items' => $cart['items'],
                    'subtotal' => $cart['subtotal'],
                    'tax' => $cart['tax'],
                    'total' => $cart['total'],
                    'status' => 'pending',
                    'created_at' => date('Y-m-d H:i:s'),
                ];
                cart_clear();
                mff_set_role('customer');
                header('Location: ' . BASE_URL . '/order_confirmation.php');
                exit;
            }
        }
    }
}

if (empty($cart['items']) && $_SERVER['REQUEST_METHOD'] !== 'POST') {
    mff_set_flash('error', 'Your cart is empty — add something before checking out.');
    header('Location: ' . BASE_URL . '/index.php');
    exit;
}

$pageTitle = 'Checkout';
$activeNav = 'shop';

require file_exists(__DIR__ . '/../includes/header.php') ? __DIR__ . '/../includes/header.php' : __DIR__ . '/includes/header.php';
?>

<div>
  <p class="section-eyebrow">Almost done</p>
  <h1 class="section-title">Checkout</h1>
</div>

<?php if (!empty($errors)): ?>
  <div class="flash flash--error" style="margin:1rem 0 0;max-width:none;">
    <ul style="margin:0;padding-left:1.1rem;">
      <?php foreach ($errors as $error): ?><li><?= htmlspecialchars($error) ?></li><?php endforeach; ?>
    </ul>
  </div>
<?php elseif ($currentRole === 'guest'): ?>
  <div class="flash flash--info" style="margin:1rem 0 0;max-width:none;">
    <span>Checking out as <strong>Guest</strong>. <a href="<?= BASE_URL ?>/login.php" style="font-weight:700;text-decoration:underline;">Log in</a> or <a href="<?= BASE_URL ?>/register.php" style="font-weight:700;text-decoration:underline;">Create an account</a> to track and save this order to your profile.</span>
  </div>
<?php endif; ?>

<form method="post" action="<?= BASE_URL ?>/checkout.php" class="form-grid" style="margin-top:1.5rem;grid-template-columns:1.2fr .8fr;align-items:start;">
  <div class="card-artisan">
    <h2 class="section-title" style="font-size:1.75rem;">Delivery details</h2>
    <div class="form-grid">
      <div class="form-field">
        <label for="full-name">Full name</label>
        <input id="full-name" name="full_name" required type="text" value="<?= htmlspecialchars($_POST['full_name'] ?? $_SESSION['user_name'] ?? '') ?>">
      </div>
      <div class="form-field">
        <label for="contact-number">Contact number</label>
        <input 
          id="contact-number" 
          name="contact_number" 
          required 
          type="tel" 
          inputmode="numeric"
          pattern="[0-9\s\+\-\(\)]{8,20}"
          placeholder="e.g. 0412 345 678"
          oninput="this.value = this.value.replace(/[^0-9\+\s\-()]/g, '')"
          onkeypress="return /[0-9\+\s\-\(\)]/.test(event.key)"
          value="<?= htmlspecialchars($_POST['contact_number'] ?? $_SESSION['user_contact'] ?? '') ?>">
        <p style="font-size:0.75rem;color:#56715f;margin-top:0.25rem;">Only numbers are accepted (letters will not be entered).</p>
      </div>
      <div class="form-field form-field--full">
        <label for="delivery-address">Delivery address</label>
        <input id="delivery-address" name="delivery_address" required type="text" value="<?= htmlspecialchars($_POST['delivery_address'] ?? '') ?>">
      </div>
      <div class="form-field form-field--full">
        <label for="delivery-instructions">Delivery instructions</label>
        <textarea id="delivery-instructions" name="delivery_instructions" rows="3"><?= htmlspecialchars($_POST['delivery_instructions'] ?? '') ?></textarea>
      </div>
    </div>

    <fieldset style="margin-top:1.75rem;border:none;padding:0;">
      <legend style="font-size:.875rem;font-weight:700;">Payment method</legend>
      <div class="payment-options">
        <label><input name="payment_method" value="cash_on_delivery" type="radio" checked> Cash on delivery</label>
        <label><input name="payment_method" value="credit_card" type="radio"> Credit card</label>
        <label><input name="payment_method" value="paypal" type="radio"> PayPal</label>
      </div>
    </fieldset>
  </div>

  <aside class="card-artisan">
    <h2 class="section-title" style="font-size:1.75rem;">Order summary</h2>
    <div style="margin-top:1.25rem;">
      <?php foreach ($cart['items'] as $item): ?>
        <div style="display:flex;justify-content:space-between;gap:.75rem;font-size:.875rem;margin-top:.6rem;">
          <span><?= (int) $item['quantity'] ?> &times; <?= htmlspecialchars($item['name']) ?></span>
          <strong><?= mff_money($item['line_total']) ?></strong>
        </div>
      <?php endforeach; ?>
    </div>
    <div style="margin-top:1.25rem;border-top:1px solid var(--line);padding-top:1rem;font-weight:700;">
      Order total <span style="float:right;"><?= mff_money($cart['total']) ?></span>
    </div>
    <button type="submit" class="btn-tomato" style="width:100%;justify-content:center;margin-top:1.5rem;">Place order</button>
  </aside>
</form>

<?php require file_exists(__DIR__ . '/../includes/footer.php') ? __DIR__ . '/../includes/footer.php' : __DIR__ . '/includes/footer.php'; ?>
