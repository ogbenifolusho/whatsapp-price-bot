<?php
// Configuration file for PriceBotNG
define('WA_ACCESS_TOKEN', 'EAAJLd25hOtYBOZBZAYUsATuQRdVg1PVD02AYeZAuBTrjSZBKwg0TI52WrF6oEN8022L2Wl5nnw97aZChfgAKc0CZBs10YjQu56gQdQV9ewmAae4NY3ZBeJaGVYOKpSnTGUOX42aCeW9Gq37ti392F3aiCGFOZCZB36pjZCIh1iMrHSUJduWyBj2YxvMymKgi2ZAjuxeaiPXePVjAIRHJZCZAdljZCo12H9BxhjHKy38swuNfRtc2AEH7wK');
define('WA_PHONE_NUMBER_ID', '597838043423458');
define('WA_VERIFY_TOKEN', 'pricebotng_verify_token_2025');
define('WA_API_URL', 'https://graph.facebook.com/v18.0/');

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'hslcomn2_whatsapp-bot');
define('DB_USER', 'hslcomn2_whatsapp-bot');
define('DB_PASS', 'cgaWQL5hZ)fhHY5&NW');

// Admin credentials
define('ADMIN_EMAIL', 'pricebotng@hsl.com.ng');
define('ADMIN_PASSWORD', 'Admin123');

// Nigerian-specific settings
define('DEFAULT_COUNTRY_CODE', '+234');
define('TIMEZONE', 'Africa/Lagos');
define('CURRENCY', '₦');

// Optimization settings
define('MAX_MESSAGE_LENGTH', 1600);
define('NETWORK_TIMEOUT', 45);
define('RETRY_ATTEMPTS', 3);
define('CACHE_DURATION', 3600); // 1 hour cache for prices

// Supported vendors
$VENDORS = [
    'jumia' => [
        'name' => 'Jumia',
        'base_url' => 'https://www.jumia.com.ng',
        'search_url' => 'https://www.jumia.com.ng/catalog/?q=',
        'affiliate_param' => '?aff_id=whatsprice_bot'
    ],
    'konga' => [
        'name' => 'Konga',
        'base_url' => 'https://www.konga.com',
        'search_url' => 'https://www.konga.com/search?search=',
        'affiliate_param' => '?ref=whatsprice_bot'
    ],
    'slot' => [
        'name' => 'Slot',
        'base_url' => 'https://slot.ng',
        'search_url' => 'https://slot.ng/search?q=',
        'affiliate_param' => '?partner=whatsprice_bot'
    ]
];

// Error reporting for development
error_reporting(E_ALL);
ini_set('display_errors', 0);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/logs/error.log');

// Set timezone
date_default_timezone_set(TIMEZONE);
?>
