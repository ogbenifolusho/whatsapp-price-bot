<?php
session_start();
require_once '../config.php';
require_once '../database.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

try {
    $db = new Database();
    $connection = $db->getConnection();
    
    // Clear expired cache entries
    $sql = "DELETE FROM price_cache WHERE expires_at < NOW()";
    $stmt = $connection->prepare($sql);
    $result = $stmt->execute();
    
    // Get count of deleted entries
    $deletedCount = $stmt->rowCount();
    
    if ($result) {
        echo json_encode([
            'success' => true,
            'message' => "Cache cleared successfully! Removed $deletedCount expired entries.",
            'deleted_count' => $deletedCount
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'error' => 'Failed to clear cache'
        ]);
    }
    
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>