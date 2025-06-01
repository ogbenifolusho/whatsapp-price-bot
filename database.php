<?php
require_once 'config.php';

class Database {
    private $connection;
    
    public function __construct() {
        $this->connect();
        $this->createTables();
    }
    
    private function connect() {
        try {
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            die("Database connection failed");
        }
    }
    
    private function createTables() {
        // Create users table
        $sql = "CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            phone_number VARCHAR(20) UNIQUE NOT NULL,
            name VARCHAR(100),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            last_active TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        )";
        $this->connection->exec($sql);
        
        // Create search_history table
        $sql = "CREATE TABLE IF NOT EXISTS search_history (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_phone VARCHAR(20) NOT NULL,
            search_query VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            INDEX idx_user_phone (user_phone),
            INDEX idx_created_at (created_at)
        )";
        $this->connection->exec($sql);
        
        // Create price_cache table
        $sql = "CREATE TABLE IF NOT EXISTS price_cache (
            id INT AUTO_INCREMENT PRIMARY KEY,
            product_name VARCHAR(255) NOT NULL,
            vendor VARCHAR(50) NOT NULL,
            price DECIMAL(12,2) NOT NULL,
            availability VARCHAR(100),
            product_url TEXT,
            cached_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            expires_at TIMESTAMP,
            INDEX idx_product_vendor (product_name, vendor),
            INDEX idx_expires_at (expires_at)
        )";
        $this->connection->exec($sql);
        
        // Create tracking table
        $sql = "CREATE TABLE IF NOT EXISTS price_tracking (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_phone VARCHAR(20) NOT NULL,
            product_name VARCHAR(255) NOT NULL,
            target_price DECIMAL(12,2),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            is_active BOOLEAN DEFAULT TRUE,
            INDEX idx_user_phone (user_phone),
            INDEX idx_active (is_active)
        )";
        $this->connection->exec($sql);
        
        // Create vendors table
        $sql = "CREATE TABLE IF NOT EXISTS vendors (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            base_url VARCHAR(255) NOT NULL,
            search_url VARCHAR(255) NOT NULL,
            affiliate_param VARCHAR(100),
            is_active BOOLEAN DEFAULT TRUE,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";
        $this->connection->exec($sql);
        
        // Insert default vendors
        $this->insertDefaultVendors();
    }
    
    private function insertDefaultVendors() {
        global $VENDORS;
        
        $checkSql = "SELECT COUNT(*) FROM vendors";
        $stmt = $this->connection->query($checkSql);
        
        if ($stmt->fetchColumn() == 0) {
            $sql = "INSERT INTO vendors (name, base_url, search_url, affiliate_param) VALUES (?, ?, ?, ?)";
            $stmt = $this->connection->prepare($sql);
            
            foreach ($VENDORS as $vendor) {
                $stmt->execute([
                    $vendor['name'],
                    $vendor['base_url'],
                    $vendor['search_url'],
                    $vendor['affiliate_param']
                ]);
            }
        }
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    public function saveUser($phone, $name = '') {
        $sql = "INSERT INTO users (phone_number, name) VALUES (?, ?) 
                ON DUPLICATE KEY UPDATE name = VALUES(name), last_active = CURRENT_TIMESTAMP";
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute([$phone, $name]);
    }
    
    public function saveSearchHistory($phone, $query) {
        $sql = "INSERT INTO search_history (user_phone, search_query) VALUES (?, ?)";
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute([$phone, $query]);
    }
    
    public function getCachedPrices($productName) {
        $sql = "SELECT * FROM price_cache 
                WHERE product_name LIKE ? AND expires_at > NOW() 
                ORDER BY price ASC";
        $stmt = $this->connection->prepare($sql);
        $stmt->execute(['%' . $productName . '%']);
        return $stmt->fetchAll();
    }
    
    public function savePriceCache($productName, $vendor, $price, $availability, $url) {
        $expiresAt = date('Y-m-d H:i:s', time() + CACHE_DURATION);
        
        $sql = "INSERT INTO price_cache (product_name, vendor, price, availability, product_url, expires_at) 
                VALUES (?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE 
                price = VALUES(price), 
                availability = VALUES(availability), 
                product_url = VALUES(product_url),
                cached_at = CURRENT_TIMESTAMP,
                expires_at = VALUES(expires_at)";
        
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute([$productName, $vendor, $price, $availability, $url, $expiresAt]);
    }
    
    public function addTracking($phone, $productName, $targetPrice = null) {
        $sql = "INSERT INTO price_tracking (user_phone, product_name, target_price) VALUES (?, ?, ?)";
        $stmt = $this->connection->prepare($sql);
        return $stmt->execute([$phone, $productName, $targetPrice]);
    }
    
    public function getActiveTracking() {
        $sql = "SELECT * FROM price_tracking WHERE is_active = 1";
        $stmt = $this->connection->query($sql);
        return $stmt->fetchAll();
    }
    
    public function getVendors() {
        $sql = "SELECT * FROM vendors WHERE is_active = 1 ORDER BY name";
        $stmt = $this->connection->query($sql);
        return $stmt->fetchAll();
    }
    
    public function getSearchStats() {
        $sql = "SELECT 
                    COUNT(*) as total_searches,
                    COUNT(DISTINCT user_phone) as unique_users,
                    search_query,
                    COUNT(*) as search_count
                FROM search_history 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
                GROUP BY search_query
                ORDER BY search_count DESC
                LIMIT 10";
        $stmt = $this->connection->query($sql);
        return $stmt->fetchAll();
    }
}
?>
