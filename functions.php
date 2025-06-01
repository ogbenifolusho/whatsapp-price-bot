<?php
require_once 'config.php';
require_once 'database.php';

function logMessage($message, $type = 'INFO') {
    $logFile = __DIR__ . '/logs/bot.log';
    $timestamp = date('Y-m-d H:i:s');
    $logEntry = "[$timestamp] [$type] $message" . PHP_EOL;
    
    if (!is_dir(__DIR__ . '/logs')) {
        mkdir(__DIR__ . '/logs', 0755, true);
    }
    
    file_put_contents($logFile, $logEntry, FILE_APPEND | LOCK_EX);
}

function sendTextMessage($to, $message) {
    $url = WA_API_URL . WA_PHONE_NUMBER_ID . '/messages';
    
    // Optimize message for low bandwidth
    if (strlen($message) > MAX_MESSAGE_LENGTH) {
        $message = substr($message, 0, MAX_MESSAGE_LENGTH - 50) . "...\n\n[Message truncated for faster delivery]";
    }
    
    $data = [
        'messaging_product' => 'whatsapp',
        'to' => $to,
        'type' => 'text',
        'text' => [
            'body' => $message
        ]
    ];
    
    return makeApiCall($url, $data);
}

function makeApiCall($url, $data, $retries = 0) {
    $headers = [
        'Authorization: Bearer ' . WA_ACCESS_TOKEN,
        'Content-Type: application/json'
    ];
    
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, NETWORK_TIMEOUT);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 15);
    curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    
    curl_close($ch);
    
    if ($error && $retries < RETRY_ATTEMPTS) {
        logMessage("cURL Error (retry $retries): $error", 'ERROR');
        sleep(1);
        return makeApiCall($url, $data, $retries + 1);
    }
    
    logMessage("API Call: HTTP $httpCode - " . substr($response, 0, 200), 'DEBUG');
    
    return [
        'success' => $httpCode == 200,
        'response' => $response,
        'http_code' => $httpCode
    ];
}

function validatePhoneNumber($phone) {
    $phone = preg_replace('/[^0-9]/', '', $phone);
    
    if (strlen($phone) === 11 && substr($phone, 0, 1) === '0') {
        $phone = '234' . substr($phone, 1);
    } elseif (strlen($phone) === 10) {
        $phone = '234' . $phone;
    }
    
    return $phone;
}

function checkRateLimit($from) {
    $rateLimitFile = __DIR__ . '/logs/rate_limit.json';
    $currentTime = time();
    $limits = [];
    
    if (file_exists($rateLimitFile)) {
        $limits = json_decode(file_get_contents($rateLimitFile), true) ?: [];
    }
    
    $limits = array_filter($limits, function($timestamp) use ($currentTime) {
        return ($currentTime - $timestamp) < 60;
    });
    
    $userLimits = array_filter($limits, function($timestamp, $phone) use ($from) {
        return strpos($phone, $from) === 0;
    }, ARRAY_FILTER_USE_BOTH);
    
    if (count($userLimits) >= 5) {
        return false;
    }
    
    $limits[$from . '_' . $currentTime] = $currentTime;
    file_put_contents($rateLimitFile, json_encode($limits));
    
    return true;
}

function formatPrice($price) {
    if (!is_numeric($price)) return $price;
    return CURRENCY . number_format($price, 0);
}

function extractPriceFromText($text) {
    preg_match('/[\d,]+(?:\.\d{2})?/', $text, $matches);
    if (!empty($matches)) {
        return (float) str_replace(',', '', $matches[0]);
    }
    return 0;
}

function normalizeProductName($query) {
    $query = strtolower(trim($query));
    $query = preg_replace('/[^\w\s]/', ' ', $query);
    $query = preg_replace('/\s+/', ' ', $query);
    
    // Common Nigerian product name normalizations
    $replacements = [
        'iphone' => 'apple iphone',
        'samsung galaxy' => 'samsung',
        'hp laptop' => 'hp',
        'dell laptop' => 'dell',
        'tecno' => 'tecno',
        'infinix' => 'infinix',
        'itel' => 'itel'
    ];
    
    foreach ($replacements as $search => $replace) {
        if (strpos($query, $search) !== false) {
            $query = str_replace($search, $replace, $query);
            break;
        }
    }
    
    return trim($query);
}

function sendQuickReplies($to, $message, $buttons) {
    if (count($buttons) > 3) {
        $buttons = array_slice($buttons, 0, 3);
    }
    
    $url = WA_API_URL . WA_PHONE_NUMBER_ID . '/messages';
    
    $interactiveButtons = [];
    foreach ($buttons as $index => $button) {
        $interactiveButtons[] = [
            'type' => 'reply',
            'reply' => [
                'id' => 'btn_' . $index,
                'title' => substr($button, 0, 20)
            ]
        ];
    }
    
    $data = [
        'messaging_product' => 'whatsapp',
        'to' => $to,
        'type' => 'interactive',
        'interactive' => [
            'type' => 'button',
            'body' => [
                'text' => $message
            ],
            'action' => [
                'buttons' => $interactiveButtons
            ]
        ]
    ];
    
    return makeApiCall($url, $data);
}

function generateWelcomeMessage($name = '') {
    $greeting = $name ? "Hi $name! 👋" : "Hi there! 👋";
    
    $message = "$greeting Welcome to *PriceBotNG* - Nigeria's smartest price comparison bot! 🇳🇬\n\n";
    $message .= "🔍 *How it works:*\n";
    $message .= "Just send me any product name and I'll find the best prices across top Nigerian stores!\n\n";
    $message .= "📱 *Examples:*\n";
    $message .= "• iPhone 15 Pro Max\n";
    $message .= "• Samsung 55 inch TV\n";
    $message .= "• HP Laptop core i7\n";
    $message .= "• Air fryer\n\n";
    $message .= "💰 I search *Jumia*, *Konga*, *Slot* and more to save you money!\n\n";
    $message .= "Try searching for something now! 🛍️";
    
    return $message;
}

function sendPriceComparison($to, $query, $results) {
    if (empty($results)) {
        $message = "😔 Sorry, I couldn't find any results for '$query'.\n\n";
        $message .= "💡 *Try:*\n";
        $message .= "• Different product name\n";
        $message .= "• More specific search (e.g., 'iPhone 15 128GB')\n";
        $message .= "• Brand name included\n\n";
        $message .= "Need help? Just ask! 😊";
        
        sendTextMessage($to, $message);
        return;
    }
    
    $message = "🔍 *Price Results for:* $query\n\n";
    
    // Sort by price
    usort($results, function($a, $b) {
        return $a['price'] - $b['price'];
    });
    
    foreach ($results as $index => $result) {
        $emoji = $index == 0 ? "🥇" : ($index == 1 ? "🥈" : "🥉");
        $message .= "$emoji *" . $result['vendor'] . "*\n";
        $message .= "💰 " . formatPrice($result['price']) . "\n";
        $message .= "📦 " . $result['availability'] . "\n";
        
        if (!empty($result['url'])) {
            $message .= "🌐 " . $result['url'] . "\n";
        }
        
        $message .= "\n";
        
        if (strlen($message) > MAX_MESSAGE_LENGTH - 200) {
            break;
        }
    }
    
    $message .= "💡 *Tip:* Prices change frequently. Check stores for latest offers!\n";
    $message .= "🚚 Consider shipping costs when comparing.\n\n";
    $message .= "Want to track this product? Reply 'Track' 📈";
    
    sendTextMessage($to, $message);
}

function isProductQuery($text) {
    $text = strtolower($text);
    
    // Nigerian product keywords
    $productKeywords = [
        'iphone', 'samsung', 'tecno', 'infinix', 'itel', 'nokia',
        'laptop', 'phone', 'tv', 'television', 'computer', 'tablet',
        'air fryer', 'blender', 'microwave', 'generator', 'ac', 'fan',
        'price', 'cost', 'how much', 'buy', 'purchase', 'compare',
        'apple', 'hp', 'dell', 'lenovo', 'lg', 'sony', 'panasonic'
    ];
    
    foreach ($productKeywords as $keyword) {
        if (strpos($text, $keyword) !== false) {
            return true;
        }
    }
    
    return false;
}

function getErrorMessage($type = 'general') {
    $messages = [
        'general' => "😅 Oops! Something went wrong. Please try again in a moment.",
        'network' => "🌐 Network issue detected. We're working on it! Please try again.",
        'rate_limit' => "⏰ Slow down! You're sending messages too quickly. Please wait a moment.",
        'no_results' => "🔍 No results found. Try a different product name or be more specific.",
        'scraping_failed' => "🛠️ Our price checkers are busy. Please try again in a few minutes."
    ];
    
    return $messages[$type] ?? $messages['general'];
}
?>
