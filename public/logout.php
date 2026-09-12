<?php
require_once file_exists(__DIR__ . '/../includes/session.php') ? __DIR__ . '/../includes/session.php' : __DIR__ . '/includes/session.php';

unset($_SESSION['user_id'], $_SESSION['user_name'], $_SESSION['user_email']);
mff_set_role('guest');
mff_set_flash('info', 'You have been logged out successfully.');
header('Location: ' . BASE_URL . '/index.php');
exit;