<?php
require_once __DIR__ . '/../includes/session.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    mff_set_role($_POST['role'] ?? 'guest');
}

$redirect = $_POST['redirect'] ?? '/index.php';
// Only allow same-site relative redirects.
if (!str_starts_with($redirect, '/')) {
    $redirect = '/index.php';
}
header('Location: ' . $redirect);
exit;
