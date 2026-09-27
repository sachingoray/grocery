<?php
/**
 * Cart isolation test — drives the REAL session.php / db.php cart code.
 * Run: C:\xampp\php\php.exe tests\cart_isolation_test.php
 */
$root = dirname(__DIR__);
require_once $root . '/includes/session.php';

$pass = 0; $fail = 0;
function ok(string $label, bool $cond) {
    global $pass, $fail;
    if ($cond) { $pass++; echo "  PASS  $label\n"; }
    else { $fail++; echo "  FAIL  $label\n"; }
}
/** Simulate a distinct browser: fresh session id + fresh guest token. */
function newBrowser(int $id) {
    $_SESSION = [];
    // In CLI the headers are already flushed, so PHP refuses to rotate the id.
    // The session contents reset above is what this test actually relies on.
    if (!headers_sent()) {
        session_regenerate_id(true);
    }
    $_SESSION['cart_guest_token'] = 'tok_browser_' . $id . '_' . bin2hex(random_bytes(8));
    $GLOBALS['mff_cart_cache'] = null;
}
/** Simulate logging in as a user id (bypasses the password form on purpose). */
function loginAs(int $userId) {
    $_SESSION['user_id'] = $userId;
    mff_set_role('customer');
    cart_merge_guest_into_user();
    $GLOBALS['mff_cart_cache'] = null;
}
function logout() {
    unset($_SESSION['user_id']);
    mff_set_role('guest');
    $_SESSION['cart_guest_token'] = 'tok_after_logout_' . bin2hex(random_bytes(8));
    $GLOBALS['mff_cart_cache'] = null;
}

$pdo = mff_db();
if ($pdo === null) { echo "FATAL: no database connection\n"; exit(1); }
$pdo->exec('DELETE FROM cart_items');

$A = 7; $B = 8;            // two distinct customer ids
$productX = 1;             // Organic Hass Avocados
$productY = 3;             // Cavendish Bananas
$xName = mff_get_product($productX)['name'];
$yName = mff_get_product($productY)['name'];

echo "\n== Test 1: User A adds Product X qty 2 ==\n";
newBrowser(1); loginAs($A);
cart_add($productX, 2);
ok("A sees X at qty 2", cart_qty($productX) === 2);
ok("A cart count is 2", cart_count() === 2);

echo "\n== Test 2: User B in a separate browser sees nothing ==\n";
newBrowser(2); loginAs($B);
ok("B does NOT see A's $xName", cart_qty($productX) === 0);
ok("B's cart is empty", cart_count() === 0);
cart_add($productY, 3);
ok("B sees Y at qty 3", cart_qty($productY) === 3);
ok("B does NOT have X", cart_qty($productX) === 0);

echo "\n== Test 3: Back to User A - A's cart is intact and separate ==\n";
newBrowser(3); loginAs($A);
ok("A still has X qty 2", cart_qty($productX) === 2);
ok("A does NOT have B's $yName", cart_qty($productY) === 0);
ok("A count still 2", cart_count() === 2);

echo "\n== Test 4: Log out A, log in as B - only B's cart ==\n";
// This is the reported bug: the SAME browser, two different accounts.
logout();
ok("Guest after logout sees an empty cart", cart_count() === 0);
loginAs($B);
ok("B still has only Y qty 3", cart_qty($productY) === 3 && cart_qty($productX) === 0);
ok("B count is 3", cart_count() === 3);
ok("A's cart is preserved in the DB for A's next login", (function () use ($pdo, $A) {
    $q = $pdo->prepare('SELECT COUNT(*) FROM cart_items WHERE user_id = :u');
    $q->execute(['u' => $A]);
    return (int)$q->fetchColumn() === 1;
})());

echo "\n== Test 5: Ownership is enforced in the database ==\n";
$q = $pdo->prepare('SELECT product_id, quantity FROM cart_items WHERE user_id = :u');
$q->execute(['u' => $A]); $aRows = $q->fetchAll();
$q->execute(['u' => $B]); $bRows = $q->fetchAll();
ok("A has exactly 1 row (X x2)", count($aRows) === 1 && (int)$aRows[0]['product_id'] === $productX && (int)$aRows[0]['quantity'] === 2);
ok("B has exactly 1 row (Y x3)", count($bRows) === 1 && (int)$bRows[0]['product_id'] === $productY && (int)$bRows[0]['quantity'] === 3);
$orphan = $pdo->query('SELECT COUNT(*) FROM cart_items WHERE user_id IS NULL')->fetchColumn();
ok("No leftover guest cart rows", (int)$orphan === 0);

echo "\n== Test 6: Cross-user tampering via the request is ignored ==\n";
newBrowser(4); loginAs($B);
// B legitimately still holds Y x3 from test 2 — that is B's own saved cart.
ok("B reloads its own saved cart (Y x3)", cart_qty($productY) === 3 && cart_qty($productX) === 0);
$_GET['user_id'] = (string) $A; $_POST['user_id'] = (string) $A;   // attempt to spoof the owner
cart_set_qty($productX, 99);
$_GET['user_id'] = null; $_POST['user_id'] = null;
// The write must land in B's OWN cart, not in A's. A spoofed user_id is ignored.
ok("the write landed in B's own cart", cart_qty($productX) === 99);
newBrowser(5); loginAs($A);
ok("A's X is untouched by B's request", cart_qty($productX) === 2);
ok("A's cart still holds only X", cart_count() === 2);
newBrowser(6); loginAs($B);
cart_remove($productX);
ok("B removed only its own line, keeping Y x3", cart_qty($productX) === 0 && cart_count() === 3);

echo "\n== Test 7: B's cart_clear cannot wipe A's cart ==\n";
newBrowser(7); loginAs($B);
ok("B has Y x3 before clearing", cart_count() === 3);
cart_clear();
ok("B's cart is now empty", cart_count() === 0);
newBrowser(8); loginAs($A);
ok("A still has X qty 2 after B cleared", cart_qty($productX) === 2 && cart_count() === 2);

echo "\n== Test 8: Guest cart merges into the account on login ==\n";
newBrowser(20);
ok("guest starts empty", cart_count() === 0);
cart_add($productY, 1);
ok("guest sees Y", cart_qty($productY) === 1);
loginAs($A);
ok("Y merged into A's cart after login", cart_qty($productY) === 1);
ok("A's pre-existing X survived the merge", cart_qty($productX) === 2);
loginAs($A); // merge must be idempotent
ok("re-login does not duplicate items", cart_qty($productY) === 1 && cart_qty($productX) === 2 && cart_count() === 3);
$guestLeft = $pdo->query('SELECT COUNT(*) FROM cart_items WHERE user_id IS NULL')->fetchColumn();
ok("guest rows consumed by the merge", (int)$guestLeft === 0);

echo "\n== Test 9: Two browsers on the SAME account share one cart ==\n";
newBrowser(9);  loginAs($B);
cart_add($productX, 4);
newBrowser(10); loginAs($B);   // same account, different browser
ok("same user sees their cart on another device", cart_qty($productX) === 4);
ok("and it is not duplicated", cart_count() === 4);

echo "\n== Test 10: cart_contents totals are per-user ==\n";
newBrowser(11); loginAs($A);
// A's cart is X x2 (from test 1) plus Y x1 (merged from the guest cart in test 8).
$cA = cart_contents();
$expectSub = round((float)mff_get_product($productX)['price'] * 2 + (float)mff_get_product($productY)['price'] * 1, 2);
ok("A subtotal matches A's two own lines", abs($cA['subtotal'] - $expectSub) < 0.001);
ok("A has exactly 2 line items", count($cA['items']) === 2);
ok("A item count is 3 units", array_sum(array_column($cA['items'], 'quantity')) === 3);
ok("A total = subtotal + 10% GST", abs($cA['total'] - round($expectSub * 1.1, 2)) < 0.01);

newBrowser(12); loginAs($B);   // B holds X x4 from test 9
$cB = cart_contents();
$expectSubB = round((float)mff_get_product($productX)['price'] * 4, 2);
ok("B subtotal matches only B's X x4", abs($cB['subtotal'] - $expectSubB) < 0.001);
ok("B subtotal differs from A's", abs($cB['subtotal'] - $cA['subtotal']) > 0.001);

echo "\n== Test 11: quantity stepper and remove still work ==\n";
newBrowser(13); loginAs($A);
cart_set_qty($productX, 3);
ok("stepper up to 3", cart_qty($productX) === 3);
cart_set_qty($productX, 0);
ok("stepper down to 0 removes the line", cart_qty($productX) === 0);
ok("the other line is unaffected", cart_qty($productY) === 1);
cart_add($productX, 2);
cart_remove($productX);
ok("remove item works", cart_qty($productX) === 0);
cart_clear();
ok("clear cart works", cart_count() === 0);
$stillThere = $pdo->prepare('SELECT COUNT(*) FROM cart_items WHERE user_id = :u');
$stillThere->execute(['u' => $A]);
ok("A's rows are gone from the DB", (int)$stillThere->fetchColumn() === 0);
$stillThere->execute(['u' => $B]);
ok("B's cart is untouched by A clearing", (int)$stillThere->fetchColumn() > 0);

$pdo->exec('DELETE FROM cart_items'); // clean up
echo "\n----------------------------------------\n";
echo "PASS: $pass   FAIL: $fail\n";
echo ($fail === 0 ? "ALL CART ISOLATION TESTS PASSED\n" : "THERE ARE FAILURES\n");
exit($fail === 0 ? 0 : 1);

logout();
ok("Guest after logout sees an empty cart", cart_count() === 0);
loginAs($B);
ok("B still has only Y qty 3", cart_qty($productY) === 3 && cart_qty($productX) === 0);
ok("B count is 3", cart_count() === 3);

echo "\n== Test 5: Ownership is enforced in the database ==\n";
$q = $pdo->prepare('SELECT product_id, quantity FROM cart_items WHERE user_id = :u');
$q->execute(['u' => $A]); $aRows = $q->fetchAll();
$q->execute(['u' => $B]); $bRows = $q->fetchAll();
ok("A has exactly 1 row (X x2)", count($aRows) === 1 && (int)$aRows[0]['product_id'] === $productX && (int)$aRows[0]['quantity'] === 2);
ok("B has exactly 1 row (Y x3)", count($bRows) === 1 && (int)$bRows[0]['product_id'] === $productY && (int)$bRows[0]['quantity'] === 3);
$orphan = $pdo->query('SELECT COUNT(*) FROM cart_items WHERE user_id IS NULL')->fetchColumn();
ok("No leftover guest cart rows", (int)$orphan === 0);
