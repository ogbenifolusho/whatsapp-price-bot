<?php
require_once 'config.php';
require_once 'functions.php';
require_once 'database.php';
require_once 'scraper.php';

// Set headers for webhook response
header('Content-Type: application/json');

// Health check endpoint
if (isset($_GET['health']) && $_GET['health'] === 'check') {
    echo json_encode([
        'status' => 'healthy',
        'timestamp' => date('c'),
        'version' => '1.0',
        'webhook_url' => 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
    ]);
    exit;
}

// Handle GET request for webhook verification
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    verifyWebhook();
}

// Handle POST request for incoming messages
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    handleIncomingMessage();
}

function verifyWebhook() {
    $hub_verify_token = $_GET['hub_verify_token'] ?? '';
    $hub_challenge = $_GET['hub_challenge'] ?? '';
    $hub_mode = $_GET['hub_mode'] ?? '';
    
    logMessage("Webhook verification attempt - Mode: $hub_mode, Token: " . substr($hub_verify_token, 0, 10) . "...", 'INFO');
    
    if ($hub_mode === 'subscribe' && $hub_verify_token === WA_VERIFY_TOKEN) {
        logMessage("Webhook verification successful", 'INFO');
        echo $hub_challenge;
        http_response_code(200);
    } else {
        logMessage("Webhook verification failed", 'ERROR');
        http_response_code(403);
        echo json_encode(['error' => 'Verification failed']);
    }
    exit;
}

function handleIncomingMessage() {
    $input = file_get_contents('php://input');
    logMessage("Received webhook data: " . substr($input, 0, 500), 'DEBUG');
    
    $data = json_decode($input, true);
    
    if (!$data) {
        logMessage("Invalid JSON received", 'ERROR');
        http_response_code(400);
        exit;
    }
    
    // Check if this is a valid WhatsApp webhook
    if (!isset($data['entry'][0]['changes'][0]['value'])) {
        logMessage("Invalid webhook structure", 'WARNING');
        http_response_code(200);
        exit;
    }
    
    $value = $data['entry'][0]['changes'][0]['value'];
    
    // Handle status updates (delivery confirmations, etc.)
    if (isset($value['statuses'])) {
        logMessage("Received status update", 'DEBUG');
        http_response_code(200);
        exit;
    }
    
    // Handle messages
    if (!isset($value['messages'])) {
        logMessage("No messages in webhook", 'DEBUG');
        http_response_code(200);
        exit;
    }
    
    $messages = $value['messages'];
    $contacts = $value['contacts'] ?? [];
    
    $db = new Database();
    
    foreach ($messages as $message) {
        try {
            processMessage($message, $contacts, $db);
        } catch (Exception $e) {
            logMessage("Error processing message: " . $e->getMessage(), 'ERROR');
        }
    }
    
    http_response_code(200);
}

function processMessage($message, $contacts, $db) {
    $from = $message['from'];
    $messageId = $message['id'];
    $timestamp = $message['timestamp'];
    
    // Validate phone number
    $from = validatePhoneNumber($from);
    
    // Check rate limiting
    if (!checkRateLimit($from)) {
        logMessage("Rate limit exceeded for $from", 'WARNING');
        sendTextMessage($from, getErrorMessage('rate_limit'));
        return;
    }
    
    // Get contact name
    $contactName = '';
    foreach ($contacts as $contact) {
        if ($contact['wa_id'] === $from) {
            $contactName = $contact['profile']['name'] ?? '';
            break;
        }
    }
    
    // Save user info
    $db->saveUser($from, $contactName);
    
    logMessage("Processing message from $from ($contactName)", 'INFO');
    
    // Handle different message types
    if (isset($message['text'])) {
        handleTextMessage($from, $message['text']['body'], $contactName, $db);
    } elseif (isset($message['interactive'])) {
        handleInteractiveMessage($from, $message['interactive'], $db);
    } else {
        // Unsupported message type
        $response = "🤖 I can only understand text messages right now.\n\n";
        $response .= "Send me a product name to search for prices! 📱";
        sendTextMessage($from, $response);
    }
}

function handleTextMessage($from, $text, $contactName, $db) {
    $text = trim($text);
    $textLower = strtolower($text);
    
    logMessage("Text message from $from: $text", 'DEBUG');
    
    // Handle special commands
    if (in_array($textLower, ['hi', 'hello', 'hey', 'start', 'help'])) {
        $response = generateWelcomeMessage($contactName);
        sendTextMessage($from, $response);
        return;
    }
    
    if (in_array($textLower, ['track', 'track this', 'track product'])) {
        $response = "📈 *Price Tracking*\n\n";
        $response .= "To track a product, search for it first, then reply 'Track' to that result.\n\n";
        $response .= "I'll notify you when:\n";
        $response .= "• Price drops by 5% or more\n";
        $response .= "• Product goes on sale\n";
        $response .= "• Stock becomes available\n\n";
        $response .= "Try searching for a product now! 🔍";
        sendTextMessage($from, $response);
        return;
    }
    
    if (strpos($textLower, 'stop') !== false || strpos($textLower, 'unsubscribe') !== false) {
        $response = "✋ No problem! You can always come back and search for prices anytime.\n\n";
        $response .= "Just send me a product name when you need help! 😊";
        sendTextMessage($from, $response);
        return;
    }
    
    // Check if this looks like a product query
    if (!isProductQuery($text)) {
        $response = "🤔 I'm not sure what you're looking for.\n\n";
        $response .= "💡 *Try sending:*\n";
        $response .= "• Product name (e.g., 'iPhone 15')\n";
        $response .= "• Brand + model (e.g., 'Samsung Galaxy S24')\n";
        $response .= "• Category (e.g., 'laptop', 'air fryer')\n\n";
        $response .= "I'll find the best prices for you! 💰";
        sendTextMessage($from, $response);
        return;
    }
    
    // Save search history
    $db->saveSearchHistory($from, $text);
    
    // Send searching message
    $searchingMsg = "🔍 Searching for '*$text*' across Nigerian stores...\n\n";
    $searchingMsg .= "⏱️ This may take a moment for the best results!";
    sendTextMessage($from, $searchingMsg);
    
    // Perform price search
    try {
        $scraper = new PriceScraper();
        $results = $scraper->searchPrices($text);
        
        if (empty($results)) {
            $noResultsMsg = "😔 No results found for '*$text*'.\n\n";
            $noResultsMsg .= "💡 *Try these tips:*\n";
            $noResultsMsg .= "• Check spelling\n";
            $noResultsMsg .= "• Use brand names\n";
            $noResultsMsg .= "• Be more specific\n";
            $noResultsMsg .= "• Try different keywords\n\n";
            $noResultsMsg .= "*Popular searches:*\n";
            $noResultsMsg .= "iPhone, Samsung, Tecno, Laptop, TV, Air fryer";
            sendTextMessage($from, $noResultsMsg);
        } else {
            sendPriceComparison($from, $text, $results);
        }
        
    } catch (Exception $e) {
        logMessage("Price search error for '$text': " . $e->getMessage(), 'ERROR');
        sendTextMessage($from, getErrorMessage('scraping_failed'));
    }
}

function handleInteractiveMessage($from, $interactive, $db) {
    if ($interactive['type'] === 'button_reply') {
        $buttonId = $interactive['button_reply']['id'];
        $buttonTitle = $interactive['button_reply']['title'];
        
        logMessage("Button pressed by $from: $buttonId ($buttonTitle)", 'DEBUG');
        
        switch ($buttonId) {
            case 'btn_0':
                // Track button
                $response = "📈 Great! I've noted your interest in tracking this product.\n\n";
                $response .= "🔔 You'll get notified when:\n";
                $response .= "• Price drops significantly\n";
                $response .= "• Better deals are found\n";
                $response .= "• Stock becomes available\n\n";
                $response .= "Search for more products anytime! 🛍️";
                sendTextMessage($from, $response);
                break;
                
            case 'btn_1':
                // Help button
                $response = generateWelcomeMessage();
                sendTextMessage($from, $response);
                break;
                
            case 'btn_2':
                // More options
                $response = "🎯 *More Features Coming Soon:*\n\n";
                $response .= "• Price history tracking\n";
                $response .= "• Deal alerts\n";
                $response .= "• Wishlist management\n";
                $response .= "• More Nigerian stores\n\n";
                $response .= "For now, send me any product name to search! 🔍";
                sendTextMessage($from, $response);
                break;
                
            default:
                handleTextMessage($from, $buttonTitle, '', $db);
        }
    }
}

// Health check endpoint
if (isset($_GET['health']) && $_GET['health'] === 'check') {
    echo json_encode([
        'status' => 'healthy',
        'timestamp' => date('c'),
        'version' => '1.0',
        'webhook_url' => 'https://' . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI']
    ]);
    exit;
}

// If we get here, return 200 for any other requests
http_response_code(200);
echo json_encode(['status' => 'ok']);
?>
