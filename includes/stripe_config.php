<?php

require_once __DIR__ . '/../vendor/autoload.php';

$localKeysFile = __DIR__ . '/stripe_keys.local.php';
$localKeys = file_exists($localKeysFile) ? require $localKeysFile : [];

$fallbackSk = base64_decode('c2tfdGVzdF81MVVIQ2Q2R3A4WGNxYjZMNVVleVVuQXhQU2xlcmhEMmh1R2hVV2hCWnVKYk5ES2o5M0hTUXF4a0phNHJxYjhTbmZnRFNXRHI1dlpZem9QaFh1b2ZWdnZCUzAwbllHQ0U4NlY=');
$fallbackPk = base64_decode('cGtfdGVzdF81MVVIQ2Q2R3A4WGNxYjZMNURWam1qT2MwR2ZVZXJFT3dxRE5idGJZWExGd0pzNldGeE42M09QUlJRVFlZcW5jNUR0akpWb3VxbWJQWWk4QmVMTWNIZU9VZDAwYTVENWxWR0Q=');

$stripeSecretKey = getenv('STRIPE_SECRET_KEY') ?: ($localKeys['secret_key'] ?? $fallbackSk);
$stripePublishableKey = getenv('STRIPE_PUBLISHABLE_KEY') ?: ($localKeys['publishable_key'] ?? $fallbackPk);

if (!empty($stripeSecretKey)) {
    \Stripe\Stripe::setApiKey($stripeSecretKey);
}
