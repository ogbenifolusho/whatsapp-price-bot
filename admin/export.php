<?php
session_start();
require_once '../config.php';
require_once '../database.php';

// Check admin authentication
if (!isset($_SESSION['admin_logged_in'])) {
    http_response_code(403);
    echo "Unauthorized access";
    exit;
}

try {
    $db = new Database();
    $connection = $db->getConnection();
    
    // Get search history data
    $sql = "SELECT 
                sh.search_query,
                sh.user_phone,
                sh.created_at,
                u.name as user_name
            FROM search_history sh
            LEFT JOIN users u ON sh.user_phone = u.phone_number
            ORDER BY sh.created_at DESC
            LIMIT 1000";
    
    $stmt = $connection->query($sql);
    $results = $stmt->fetchAll();
    
    // Set headers for CSV download
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="pricebotng_search_data_' . date('Y-m-d') . '.csv"');
    
    // Create CSV output
    $output = fopen('php://output', 'w');
    
    // Add CSV headers
    fputcsv($output, [
        'Search Query',
        'User Phone',
        'User Name', 
        'Search Date',
        'Search Time'
    ]);
    
    // Add data rows
    foreach ($results as $row) {
        fputcsv($output, [
            $row['search_query'],
            $row['user_phone'],
            $row['user_name'] ?: 'Unknown',
            date('Y-m-d', strtotime($row['created_at'])),
            date('H:i:s', strtotime($row['created_at']))
        ]);
    }
    
    fclose($output);
    
} catch (Exception $e) {
    echo "Error exporting data: " . $e->getMessage();
}
?>