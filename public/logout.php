<?php
require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';

unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email'], $_SESSION['user_contact']);
mff_set_role('guest');
// Clear the server-grant flag as well. Without this, signing out would leave
// the session marked as "holding a trusted role" even though no one is signed
// in, and set_role.php's self-assign branch keys off exactly that flag.

// The signed-in account's cart is preserved in the database under its user id
// and is reloaded the moment that account signs in again. A brand new guest
// token is issued so this browser drops back to an EMPTY guest cart and can
// never display the previous user's items, nor hand them to the next account
// that signs in on this machine.
$_SESSION['cart_guest_token'] = bin2hex(random_bytes(32));
$GLOBALS['mff_cart_cache'] = null;
// Rotate the session id on logout as well, so a stale cookie cannot be reused.
session_regenerate_id(true);
mff_set_flash('info', 'You have been logged out successfully.');
header('Location: ' . BASE_URL . '/index.php');
exit;
