<?php
require_once __DIR__ . '/../includes/session.php';

unset($_SESSION['user_id'], $_SESSION['user_name']);
mff_set_role('guest');
mff_set_flash('info', 'You have been logged out.');
header('Location: ' . BASE_URL . '/index.php');
exit;