<?php
/**
 * ai_config.php — server-side OpenRouter configuration for the AI chatbot.
 *
 * The API key NEVER leaves the server. Resolution order:
 *   1. OPENROUTER_API_KEY environment variable (hosting dashboards, .env, Apache SetEnv)
 *   2. includes/ai_keys.local.php  (git-ignored, same pattern as stripe_keys.local.php)
 *
 * Frontend JS must call public/api/chat.php — never OpenRouter directly.
 */

function mff_openrouter_key(): string
{
    $fromEnv = getenv('OPENROUTER_API_KEY');
    if (is_string($fromEnv) && trim($fromEnv) !== '') {
        return trim($fromEnv);
    }
    // $_ENV/$_SERVER fallbacks for hosts where getenv() is restricted.
    foreach (['_ENV', '_SERVER'] as $super) {
        $bag = $GLOBALS[$super] ?? ($super === '_ENV' ? ($_ENV ?? []) : ($_SERVER ?? []));
        if (isset($bag['OPENROUTER_API_KEY']) && is_string($bag['OPENROUTER_API_KEY']) && trim($bag['OPENROUTER_API_KEY']) !== '') {
            return trim($bag['OPENROUTER_API_KEY']);
        }
    }
    $localFile = __DIR__ . '/ai_keys.local.php';
    if (is_file($localFile)) {
        $local = require $localFile;
        if (is_array($local) && isset($local['openrouter_api_key']) && is_string($local['openrouter_api_key'])) {
            $k = trim($local['openrouter_api_key']);
            if ($k !== '' && stripos($k, 'YOUR_KEY_HERE') === false) {
                return $k;
            }
        }
    }
    return '';
}

if (!defined('MFF_AI_MODEL')) {
    define('MFF_AI_MODEL', 'nvidia/nemotron-3-ultra-550b-a55b:free');
}
if (!defined('MFF_AI_ENDPOINT')) {
    define('MFF_AI_ENDPOINT', 'https://openrouter.ai/api/v1/chat/completions');
}
