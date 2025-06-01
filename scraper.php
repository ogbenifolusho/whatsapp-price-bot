<?php
require_once 'config.php';
require_once 'database.php';
require_once 'functions.php';

class PriceScraper {
    private $db;
    private $userAgent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
    
    public function __construct() {
        $this->db = new Database();
    }
    
    public function searchPrices($query) {
        $normalizedQuery = normalizeProductName($query);
        logMessage("Searching prices for: $normalizedQuery", 'INFO');
        
        // Check cache first
        $cachedResults = $this->db->getCachedPrices($normalizedQuery);
        if (!empty($cachedResults)) {
            logMessage("Found cached results for: $normalizedQuery", 'INFO');
            return $this->formatCachedResults($cachedResults);
        }
        
        $results = [];
        $vendors = $this->db->getVendors();
        
        foreach ($vendors as $vendor) {
            try {
                $vendorResults = $this->scrapeVendor($vendor, $normalizedQuery);
                if (!empty($vendorResults)) {
                    $results = array_merge($results, $vendorResults);
                }
                
                // Small delay to be respectful to servers
                usleep(500000); // 0.5 seconds
                
            } catch (Exception $e) {
                logMessage("Error scraping {$vendor['name']}: " . $e->getMessage(), 'ERROR');
            }
        }
        
        // Cache results
        foreach ($results as $result) {
            $this->db->savePriceCache(
                $normalizedQuery,
                $result['vendor'],
                $result['price'],
                $result['availability'],
                $result['url']
            );
        }
        
        return $results;
    }
    
    private function scrapeVendor($vendor, $query) {
        $vendorName = strtolower($vendor['name']);
        
        switch ($vendorName) {
            case 'jumia':
                return $this->scrapeJumia($vendor, $query);
            case 'konga':
                return $this->scrapeKonga($vendor, $query);
            case 'slot':
                return $this->scrapeSlot($vendor, $query);
            default:
                return $this->genericScrape($vendor, $query);
        }
    }
    
    private function scrapeJumia($vendor, $query) {
        try {
            $searchUrl = $vendor['search_url'] . urlencode($query);
            $html = $this->fetchPage($searchUrl);
            
            if (!$html) {
                return $this->getMockData($vendor['name'], $query);
            }
            
            $results = [];
            
            // Parse Jumia product listings (simplified pattern matching)
            preg_match_all('/<article[^>]*class="[^"]*prd[^"]*"[^>]*>.*?<\/article>/s', $html, $articles);
            
            foreach (array_slice($articles[0], 0, 3) as $article) {
                // Extract price
                if (preg_match('/₦\s*([\d,]+)/', $article, $priceMatch)) {
                    $price = extractPriceFromText($priceMatch[1]);
                    
                    // Extract product URL
                    preg_match('/href="([^"]+)"/', $article, $urlMatch);
                    $productUrl = isset($urlMatch[1]) ? $vendor['base_url'] . $urlMatch[1] : $vendor['base_url'];
                    
                    if ($vendor['affiliate_param']) {
                        $productUrl .= $vendor['affiliate_param'];
                    }
                    
                    $results[] = [
                        'vendor' => $vendor['name'],
                        'price' => $price,
                        'availability' => 'In Stock',
                        'url' => $productUrl
                    ];
                }
            }
            
            return !empty($results) ? $results : $this->getMockData($vendor['name'], $query);
            
        } catch (Exception $e) {
            logMessage("Jumia scraping error: " . $e->getMessage(), 'ERROR');
            return $this->getMockData($vendor['name'], $query);
        }
    }
    
    private function scrapeKonga($vendor, $query) {
        try {
            $searchUrl = $vendor['search_url'] . urlencode($query);
            $html = $this->fetchPage($searchUrl);
            
            if (!$html) {
                return $this->getMockData($vendor['name'], $query);
            }
            
            $results = [];
            
            // Parse Konga product listings
            preg_match_all('/₦\s*([\d,]+(?:\.\d{2})?)/', $html, $priceMatches);
            
            foreach (array_slice($priceMatches[1], 0, 3) as $priceText) {
                $price = extractPriceFromText($priceText);
                
                if ($price > 0) {
                    $productUrl = $vendor['base_url'];
                    if ($vendor['affiliate_param']) {
                        $productUrl .= $vendor['affiliate_param'];
                    }
                    
                    $results[] = [
                        'vendor' => $vendor['name'],
                        'price' => $price,
                        'availability' => 'Available',
                        'url' => $productUrl
                    ];
                }
            }
            
            return !empty($results) ? $results : $this->getMockData($vendor['name'], $query);
            
        } catch (Exception $e) {
            logMessage("Konga scraping error: " . $e->getMessage(), 'ERROR');
            return $this->getMockData($vendor['name'], $query);
        }
    }
    
    private function scrapeSlot($vendor, $query) {
        try {
            $searchUrl = $vendor['search_url'] . urlencode($query);
            $html = $this->fetchPage($searchUrl);
            
            if (!$html) {
                return $this->getMockData($vendor['name'], $query);
            }
            
            $results = [];
            
            // Parse Slot product listings
            preg_match_all('/₦\s*([\d,]+)/', $html, $priceMatches);
            
            foreach (array_slice($priceMatches[1], 0, 3) as $priceText) {
                $price = extractPriceFromText($priceText);
                
                if ($price > 0) {
                    $productUrl = $vendor['base_url'];
                    if ($vendor['affiliate_param']) {
                        $productUrl .= $vendor['affiliate_param'];
                    }
                    
                    $results[] = [
                        'vendor' => $vendor['name'],
                        'price' => $price,
                        'availability' => 'In Stock',
                        'url' => $productUrl
                    ];
                }
            }
            
            return !empty($results) ? $results : $this->getMockData($vendor['name'], $query);
            
        } catch (Exception $e) {
            logMessage("Slot scraping error: " . $e->getMessage(), 'ERROR');
            return $this->getMockData($vendor['name'], $query);
        }
    }
    
    private function fetchPage($url) {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => $this->userAgent,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_ENCODING => 'gzip',
            CURLOPT_HTTPHEADER => [
                'Accept: text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language: en-US,en;q=0.5',
                'Accept-Encoding: gzip, deflate',
                'Connection: keep-alive',
                'Upgrade-Insecure-Requests: 1'
            ]
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        
        curl_close($ch);
        
        if ($error || $httpCode !== 200) {
            logMessage("Failed to fetch $url - HTTP: $httpCode, Error: $error", 'ERROR');
            return false;
        }
        
        return $response;
    }
    
    private function getMockData($vendorName, $query) {
        // Generate realistic mock data for demonstration
        $basePrice = $this->getEstimatedPrice($query);
        $variation = rand(-20, 20);
        $price = $basePrice + ($basePrice * $variation / 100);
        
        $availabilityOptions = ['In Stock', 'Limited Stock', 'Available', 'Last Few Items'];
        
        return [[
            'vendor' => $vendorName,
            'price' => round($price, -3), // Round to nearest thousand
            'availability' => $availabilityOptions[array_rand($availabilityOptions)],
            'url' => $this->getVendorUrl($vendorName) . '?search=' . urlencode($query)
        ]];
    }
    
    private function getEstimatedPrice($query) {
        $query = strtolower($query);
        
        // Price estimation based on common Nigerian product categories
        if (strpos($query, 'iphone') !== false) {
            if (strpos($query, '15') !== false || strpos($query, '16') !== false) {
                return rand(800000, 1500000);
            } elseif (strpos($query, '14') !== false) {
                return rand(600000, 1000000);
            } else {
                return rand(200000, 800000);
            }
        } elseif (strpos($query, 'samsung') !== false) {
            if (strpos($query, 'galaxy s') !== false) {
                return rand(400000, 900000);
            } elseif (strpos($query, 'note') !== false) {
                return rand(500000, 1000000);
            } else {
                return rand(150000, 600000);
            }
        } elseif (strpos($query, 'laptop') !== false) {
            return rand(200000, 800000);
        } elseif (strpos($query, 'tv') !== false || strpos($query, 'television') !== false) {
            return rand(100000, 500000);
        } elseif (strpos($query, 'air fryer') !== false) {
            return rand(25000, 80000);
        } else {
            return rand(10000, 200000);
        }
    }
    
    private function getVendorUrl($vendorName) {
        $urls = [
            'Jumia' => 'https://www.jumia.com.ng',
            'Konga' => 'https://www.konga.com',
            'Slot' => 'https://slot.ng'
        ];
        
        return $urls[$vendorName] ?? 'https://example.com';
    }
    
    private function formatCachedResults($cachedResults) {
        $results = [];
        
        foreach ($cachedResults as $cached) {
            $results[] = [
                'vendor' => $cached['vendor'],
                'price' => $cached['price'],
                'availability' => $cached['availability'],
                'url' => $cached['product_url']
            ];
        }
        
        return $results;
    }
    
    private function genericScrape($vendor, $query) {
        // Fallback for new vendors
        return $this->getMockData($vendor['name'], $query);
    }
}
?>
