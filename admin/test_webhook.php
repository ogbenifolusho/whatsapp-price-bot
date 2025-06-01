<?php
require_once '../config.php';
require_once '../functions.php';

header('Content-Type: application/json');

try {
    // Test WhatsApp API connection
    $testMessage = "🤖 PriceBotNG Test Message\n\nThis is a test to verify the WhatsApp API connection is working correctly.\n\nTimestamp: " . date('Y-m-d H:i:s');
    
    // You can change this to your own WhatsApp number for testing
    $testNumber = '2348123456789'; // Replace with actual test number
    
    $result = sendTextMessage($testNumber, $testMessage);
    
    if ($result['success']) {
        echo json_encode([
            'success' => true,
            'message' => 'Webhook test successful!',
            'response' => $result['response']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to send test message',
            'details' => $result
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>