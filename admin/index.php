<?php
session_start();
require_once '../config.php';
require_once '../database.php';

// Check if user is logged in
if (!isset($_SESSION['admin_logged_in'])) {
    header('Location: login.php');
    exit;
}

$db = new Database();
$stats = $db->getSearchStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PriceBotNG Admin Dashboard</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .card-hover {
            transition: transform 0.2s ease-in-out;
        }
        .card-hover:hover {
            transform: translateY(-2px);
        }
    </style>
</head>
<body class="bg-gray-100">
    <!-- Navigation -->
    <nav class="gradient-bg shadow-lg">
        <div class="max-w-7xl mx-auto px-4">
            <div class="flex justify-between items-center py-4">
                <div class="flex items-center">
                    <i class="fas fa-robot text-white text-2xl mr-3"></i>
                    <h1 class="text-white text-xl font-bold">PriceBotNG Admin</h1>
                </div>
                <div class="flex items-center space-x-4">
                    <span class="text-white">
                        <i class="fas fa-user mr-2"></i>
                        <?php echo ADMIN_EMAIL; ?>
                    </span>
                    <a href="logout.php" class="bg-red-500 hover:bg-red-600 text-white px-4 py-2 rounded-lg transition">
                        <i class="fas fa-sign-out-alt mr-2"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="max-w-7xl mx-auto px-4 py-8">
        <!-- Overview Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <?php
            $totalSearches = 0;
            $uniqueUsers = 0;
            if (!empty($stats)) {
                $totalSearches = $stats[0]['total_searches'] ?? 0;
                $uniqueUsers = $stats[0]['unique_users'] ?? 0;
            }
            
            // Get recent activity
            $recentLogs = $this->getRecentLogs();
            $activeVendors = count($db->getVendors());
            ?>
            
            <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-blue-100">
                        <i class="fas fa-search text-blue-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-600 text-sm">Total Searches</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo number_format($totalSearches); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-green-100">
                        <i class="fas fa-users text-green-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-600 text-sm">Active Users</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo number_format($uniqueUsers); ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-purple-100">
                        <i class="fas fa-store text-purple-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-600 text-sm">Active Vendors</p>
                        <p class="text-2xl font-bold text-gray-800"><?php echo $activeVendors; ?></p>
                    </div>
                </div>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 card-hover">
                <div class="flex items-center">
                    <div class="p-3 rounded-full bg-yellow-100">
                        <i class="fas fa-chart-line text-yellow-600 text-xl"></i>
                    </div>
                    <div class="ml-4">
                        <p class="text-gray-600 text-sm">Bot Status</p>
                        <p class="text-2xl font-bold text-green-600">Active</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Main Content Grid -->
        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
            <!-- Popular Searches -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-fire text-orange-500 mr-2"></i>
                        Popular Searches (Last 7 Days)
                    </h2>
                </div>
                <div class="p-6">
                    <?php if (!empty($stats)): ?>
                        <div class="space-y-4">
                            <?php foreach (array_slice($stats, 0, 8) as $index => $search): ?>
                                <div class="flex items-center justify-between">
                                    <div class="flex items-center">
                                        <span class="w-6 h-6 bg-blue-100 text-blue-600 rounded-full flex items-center justify-center text-sm font-medium mr-3">
                                            <?php echo $index + 1; ?>
                                        </span>
                                        <span class="text-gray-800 font-medium">
                                            <?php echo htmlspecialchars($search['search_query']); ?>
                                        </span>
                                    </div>
                                    <span class="bg-gray-100 text-gray-600 px-2 py-1 rounded-full text-sm">
                                        <?php echo $search['search_count']; ?> searches
                                    </span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php else: ?>
                        <div class="text-center py-8 text-gray-500">
                            <i class="fas fa-search text-4xl mb-4"></i>
                            <p>No search data available yet</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- System Status -->
            <div class="bg-white rounded-lg shadow-md">
                <div class="px-6 py-4 border-b border-gray-200">
                    <h2 class="text-lg font-semibold text-gray-800">
                        <i class="fas fa-cogs text-blue-500 mr-2"></i>
                        System Status
                    </h2>
                </div>
                <div class="p-6">
                    <div class="space-y-4">
                        <!-- WhatsApp API Status -->
                        <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fab fa-whatsapp text-green-600 text-xl mr-3"></i>
                                <span class="font-medium">WhatsApp API</span>
                            </div>
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-sm">
                                <i class="fas fa-check-circle mr-1"></i>Connected
                            </span>
                        </div>

                        <!-- Database Status -->
                        <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-database text-green-600 text-xl mr-3"></i>
                                <span class="font-medium">Database</span>
                            </div>
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-sm">
                                <i class="fas fa-check-circle mr-1"></i>Online
                            </span>
                        </div>

                        <!-- Scraper Status -->
                        <div class="flex items-center justify-between p-3 bg-green-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-spider text-green-600 text-xl mr-3"></i>
                                <span class="font-medium">Price Scraper</span>
                            </div>
                            <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-sm">
                                <i class="fas fa-check-circle mr-1"></i>Active
                            </span>
                        </div>

                        <!-- Cache Status -->
                        <div class="flex items-center justify-between p-3 bg-blue-50 rounded-lg">
                            <div class="flex items-center">
                                <i class="fas fa-memory text-blue-600 text-xl mr-3"></i>
                                <span class="font-medium">Price Cache</span>
                            </div>
                            <span class="bg-blue-100 text-blue-800 px-2 py-1 rounded-full text-sm">
                                <i class="fas fa-info-circle mr-1"></i>1 Hour TTL
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Vendor Management -->
        <div class="mt-8 bg-white rounded-lg shadow-md">
            <div class="px-6 py-4 border-b border-gray-200 flex justify-between items-center">
                <h2 class="text-lg font-semibold text-gray-800">
                    <i class="fas fa-store text-purple-500 mr-2"></i>
                    Vendor Management
                </h2>
                <button onclick="addVendor()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition">
                    <i class="fas fa-plus mr-2"></i>Add Vendor
                </button>
            </div>
            <div class="p-6">
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead>
                            <tr class="border-b border-gray-200">
                                <th class="text-left py-3 px-4 font-medium text-gray-600">Vendor</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-600">Status</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-600">Base URL</th>
                                <th class="text-left py-3 px-4 font-medium text-gray-600">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $vendors = $db->getVendors();
                            foreach ($vendors as $vendor):
                            ?>
                            <tr class="border-b border-gray-100 hover:bg-gray-50">
                                <td class="py-3 px-4">
                                    <div class="flex items-center">
                                        <div class="w-8 h-8 bg-blue-100 rounded-full flex items-center justify-center mr-3">
                                            <i class="fas fa-store text-blue-600 text-sm"></i>
                                        </div>
                                        <span class="font-medium"><?php echo htmlspecialchars($vendor['name']); ?></span>
                                    </div>
                                </td>
                                <td class="py-3 px-4">
                                    <?php if ($vendor['is_active']): ?>
                                        <span class="bg-green-100 text-green-800 px-2 py-1 rounded-full text-sm">Active</span>
                                    <?php else: ?>
                                        <span class="bg-red-100 text-red-800 px-2 py-1 rounded-full text-sm">Inactive</span>
                                    <?php endif; ?>
                                </td>
                                <td class="py-3 px-4 text-sm text-gray-600">
                                    <?php echo htmlspecialchars($vendor['base_url']); ?>
                                </td>
                                <td class="py-3 px-4">
                                    <div class="flex space-x-2">
                                        <button onclick="editVendor(<?php echo $vendor['id']; ?>)" 
                                                class="text-blue-600 hover:text-blue-800">
                                            <i class="fas fa-edit"></i>
                                        </button>
                                        <button onclick="toggleVendor(<?php echo $vendor['id']; ?>)" 
                                                class="text-yellow-600 hover:text-yellow-800">
                                            <i class="fas fa-power-off"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <div class="mt-8 grid grid-cols-1 md:grid-cols-3 gap-6">
            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <i class="fas fa-broadcast-tower text-blue-500 text-3xl mb-4"></i>
                <h3 class="text-lg font-semibold mb-2">Test Webhook</h3>
                <p class="text-gray-600 mb-4">Send a test message to verify WhatsApp connection</p>
                <button onclick="testWebhook()" class="bg-blue-500 hover:bg-blue-600 text-white px-4 py-2 rounded-lg transition">
                    Test Connection
                </button>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <i class="fas fa-broom text-green-500 text-3xl mb-4"></i>
                <h3 class="text-lg font-semibold mb-2">Clear Cache</h3>
                <p class="text-gray-600 mb-4">Clear expired price cache and refresh data</p>
                <button onclick="clearCache()" class="bg-green-500 hover:bg-green-600 text-white px-4 py-2 rounded-lg transition">
                    Clear Cache
                </button>
            </div>

            <div class="bg-white rounded-lg shadow-md p-6 text-center">
                <i class="fas fa-download text-purple-500 text-3xl mb-4"></i>
                <h3 class="text-lg font-semibold mb-2">Export Data</h3>
                <p class="text-gray-600 mb-4">Download search history and analytics</p>
                <button onclick="exportData()" class="bg-purple-500 hover:bg-purple-600 text-white px-4 py-2 rounded-lg transition">
                    Export CSV
                </button>
            </div>
        </div>
    </div>

    <script>
        function addVendor() {
            alert('Vendor management coming soon!');
        }

        function editVendor(id) {
            alert('Edit vendor ' + id + ' - Coming soon!');
        }

        function toggleVendor(id) {
            if (confirm('Toggle vendor status?')) {
                alert('Vendor status toggled - Feature coming soon!');
            }
        }

        function testWebhook() {
            fetch('test_webhook.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('✅ Webhook test successful!');
                    } else {
                        alert('❌ Webhook test failed: ' + data.error);
                    }
                })
                .catch(error => {
                    alert('❌ Error testing webhook: ' + error);
                });
        }

        function clearCache() {
            if (confirm('Clear all cached price data?')) {
                fetch('clear_cache.php', { method: 'POST' })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            alert('✅ Cache cleared successfully!');
                            window.location.reload();
                        } else {
                            alert('❌ Error clearing cache: ' + data.error);
                        }
                    });
            }
        }

        function exportData() {
            window.open('export.php', '_blank');
        }

        // Auto-refresh every 30 seconds
        setInterval(() => {
            window.location.reload();
        }, 30000);
    </script>
</body>
</html>

<?php
// Helper function for recent logs
function getRecentLogs() {
    $logFile = __DIR__ . '/../logs/bot.log';
    if (!file_exists($logFile)) return [];
    
    $lines = file($logFile);
    return array_slice($lines, -10);
}
?>
