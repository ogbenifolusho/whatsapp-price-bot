<?php
require_once 'config.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PriceBotNG - Nigeria's Smart Price Comparison Bot</title>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.19/dist/tailwind.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        .gradient-bg {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        }
        .hero-pattern {
            background-image: url("data:image/svg+xml,%3Csvg width='60' height='60' viewBox='0 0 60 60' xmlns='http://www.w3.org/2000/svg'%3E%3Cg fill='none' fill-rule='evenodd'%3E%3Cg fill='%23ffffff' fill-opacity='0.1'%3E%3Ccircle cx='30' cy='30' r='4'/%3E%3C/g%3E%3C/g%3E%3C/svg%3E");
        }
    </style>
</head>
<body class="bg-gray-50">
    <!-- Navigation -->
    <nav class="bg-white shadow-sm">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex justify-between items-center h-16">
                <div class="flex items-center">
                    <i class="fas fa-robot text-purple-600 text-2xl mr-3"></i>
                    <span class="text-xl font-bold text-gray-900">PriceBotNG</span>
                </div>
                <div class="flex items-center space-x-4">
                    <a href="#features" class="text-gray-600 hover:text-purple-600 transition">Features</a>
                    <a href="#how-it-works" class="text-gray-600 hover:text-purple-600 transition">How It Works</a>
                    <a href="admin/login.php" class="bg-purple-600 hover:bg-purple-700 text-white px-4 py-2 rounded-lg transition">
                        Admin Login
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="gradient-bg hero-pattern">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-20">
            <div class="text-center">
                <div class="inline-flex items-center justify-center w-20 h-20 bg-white rounded-full shadow-lg mb-8">
                    <i class="fab fa-whatsapp text-green-500 text-3xl"></i>
                </div>
                <h1 class="text-4xl md:text-6xl font-bold text-white mb-6">
                    Find Best Prices on <span class="text-yellow-300">WhatsApp</span>
                </h1>
                <p class="text-xl text-white text-opacity-90 mb-8 max-w-3xl mx-auto">
                    Nigeria's smartest price comparison bot. Compare prices across Jumia, Konga, Slot and more - 
                    all through WhatsApp! 🇳🇬
                </p>
                <div class="flex flex-col sm:flex-row gap-4 justify-center">
                    <a href="https://wa.me/<?php echo str_replace('+', '', DEFAULT_COUNTRY_CODE) . WA_PHONE_NUMBER_ID; ?>?text=Hi%20PriceBotNG" 
                       target="_blank"
                       class="bg-green-500 hover:bg-green-600 text-white font-bold py-4 px-8 rounded-lg text-lg transition inline-flex items-center justify-center">
                        <i class="fab fa-whatsapp mr-3 text-xl"></i>Start Chatting Now
                    </a>
                    <button onclick="scrollToDemo()" 
                            class="bg-white bg-opacity-20 backdrop-blur-sm hover:bg-opacity-30 text-white font-bold py-4 px-8 rounded-lg text-lg transition border border-white border-opacity-30">
                        <i class="fas fa-play mr-2"></i>See Demo
                    </button>
                </div>
            </div>
        </div>
    </section>

    <!-- Features Section -->
    <section id="features" class="py-20 bg-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">Why Choose PriceBotNG?</h2>
                <p class="text-xl text-gray-600 max-w-3xl mx-auto">
                    Built specifically for Nigerian shoppers who want the best deals without the hassle
                </p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="text-center p-6 rounded-lg hover:shadow-lg transition">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-100 rounded-full mb-6">
                        <i class="fas fa-search text-blue-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Instant Price Comparison</h3>
                    <p class="text-gray-600">
                        Search any product and get prices from top Nigerian stores in seconds. No app downloads needed!
                    </p>
                </div>

                <!-- Feature 2 -->
                <div class="text-center p-6 rounded-lg hover:shadow-lg transition">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-green-100 rounded-full mb-6">
                        <i class="fab fa-whatsapp text-green-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">WhatsApp Native</h3>
                    <p class="text-gray-600">
                        Works directly in WhatsApp - the app Nigerians already use. No new apps, no learning curve.
                    </p>
                </div>

                <!-- Feature 3 -->
                <div class="text-center p-6 rounded-lg hover:shadow-lg transition">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-100 rounded-full mb-6">
                        <i class="fas fa-bolt text-purple-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Lightning Fast</h3>
                    <p class="text-gray-600">
                        Optimized for Nigeria's network conditions. Get results even on slow connections.
                    </p>
                </div>

                <!-- Feature 4 -->
                <div class="text-center p-6 rounded-lg hover:shadow-lg transition">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-yellow-100 rounded-full mb-6">
                        <i class="fas fa-store text-yellow-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Top Nigerian Stores</h3>
                    <p class="text-gray-600">
                        Searches Jumia, Konga, Slot and more trusted Nigerian retailers for the best deals.
                    </p>
                </div>

                <!-- Feature 5 -->
                <div class="text-center p-6 rounded-lg hover:shadow-lg transition">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-red-100 rounded-full mb-6">
                        <i class="fas fa-bell text-red-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Price Alerts</h3>
                    <p class="text-gray-600">
                        Track products and get notified when prices drop. Never miss a good deal again!
                    </p>
                </div>

                <!-- Feature 6 -->
                <div class="text-center p-6 rounded-lg hover:shadow-lg transition">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-indigo-100 rounded-full mb-6">
                        <i class="fas fa-shield-alt text-indigo-600 text-2xl"></i>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">100% Free</h3>
                    <p class="text-gray-600">
                        Completely free to use. No hidden fees, no subscriptions. Just smart shopping made easy.
                    </p>
                </div>
            </div>
        </div>
    </section>

    <!-- How It Works -->
    <section id="how-it-works" class="py-20 bg-gray-50">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">How It Works</h2>
                <p class="text-xl text-gray-600">Getting the best prices is as easy as 1-2-3</p>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Step 1 -->
                <div class="text-center">
                    <div class="relative">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 text-white rounded-full text-2xl font-bold mb-6">
                            1
                        </div>
                        <div class="absolute top-8 left-1/2 transform -translate-x-1/2 w-full h-0.5 bg-blue-200 hidden md:block"></div>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Send Product Name</h3>
                    <p class="text-gray-600 mb-4">
                        Message our WhatsApp bot with any product you want to buy
                    </p>
                    <div class="bg-white p-4 rounded-lg shadow-sm border">
                        <p class="text-sm text-gray-500 mb-2">Example message:</p>
                        <p class="font-medium">"iPhone 15 Pro Max"</p>
                    </div>
                </div>

                <!-- Step 2 -->
                <div class="text-center">
                    <div class="relative">
                        <div class="inline-flex items-center justify-center w-16 h-16 bg-green-600 text-white rounded-full text-2xl font-bold mb-6">
                            2
                        </div>
                        <div class="absolute top-8 left-1/2 transform -translate-x-1/2 w-full h-0.5 bg-green-200 hidden md:block"></div>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">We Search & Compare</h3>
                    <p class="text-gray-600 mb-4">
                        Our bot instantly searches across multiple Nigerian stores
                    </p>
                    <div class="flex justify-center space-x-2">
                        <div class="w-8 h-8 bg-orange-500 rounded-full flex items-center justify-center">
                            <span class="text-white text-xs font-bold">J</span>
                        </div>
                        <div class="w-8 h-8 bg-green-500 rounded-full flex items-center justify-center">
                            <span class="text-white text-xs font-bold">K</span>
                        </div>
                        <div class="w-8 h-8 bg-blue-500 rounded-full flex items-center justify-center">
                            <span class="text-white text-xs font-bold">S</span>
                        </div>
                    </div>
                </div>

                <!-- Step 3 -->
                <div class="text-center">
                    <div class="inline-flex items-center justify-center w-16 h-16 bg-purple-600 text-white rounded-full text-2xl font-bold mb-6">
                        3
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 mb-4">Get Best Prices</h3>
                    <p class="text-gray-600 mb-4">
                        Receive a formatted list of prices with direct links to buy
                    </p>
                    <div class="bg-white p-4 rounded-lg shadow-sm border text-left">
                        <p class="text-sm text-gray-500 mb-2">Sample response:</p>
                        <div class="text-xs">
                            <p><strong>1️⃣ Jumia</strong></p>
                            <p>₦1,050,000 | In Stock</p>
                            <p class="text-blue-600">🌐 View Product</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Demo Section -->
    <section id="demo" class="py-20 bg-white">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-12">
                <h2 class="text-3xl md:text-4xl font-bold text-gray-900 mb-4">See It In Action</h2>
                <p class="text-xl text-gray-600">Watch how easy it is to find the best prices</p>
            </div>

            <div class="bg-gray-900 rounded-lg p-6 text-white">
                <div class="flex items-center mb-4">
                    <div class="flex space-x-2">
                        <div class="w-3 h-3 bg-red-500 rounded-full"></div>
                        <div class="w-3 h-3 bg-yellow-500 rounded-full"></div>
                        <div class="w-3 h-3 bg-green-500 rounded-full"></div>
                    </div>
                    <span class="ml-4 text-sm text-gray-400">WhatsApp Chat with PriceBotNG</span>
                </div>
                
                <div class="space-y-4">
                    <div class="flex justify-end">
                        <div class="bg-green-600 rounded-lg px-4 py-2 max-w-xs">
                            <p>iPhone 15 price</p>
                        </div>
                    </div>
                    
                    <div class="flex justify-start">
                        <div class="bg-gray-700 rounded-lg px-4 py-2 max-w-md">
                            <p class="text-sm">🔍 Searching for 'iPhone 15' across Nigerian stores...</p>
                            <p class="text-sm mt-2">⏱️ This may take a moment for the best results!</p>
                        </div>
                    </div>
                    
                    <div class="flex justify-start">
                        <div class="bg-gray-700 rounded-lg px-4 py-2 max-w-md">
                            <p class="font-bold mb-2">🔍 Price Results for: iPhone 15</p>
                            <div class="text-sm space-y-2">
                                <div>
                                    <p><strong>🥇 Jumia</strong></p>
                                    <p>💰 ₦850,000</p>
                                    <p>📦 In Stock</p>
                                    <p class="text-blue-400">🌐 https://jumia.com.ng/iphone-15</p>
                                </div>
                                <div class="mt-3">
                                    <p><strong>🥈 Konga</strong></p>
                                    <p>💰 ₦899,000</p>
                                    <p>📦 Available</p>
                                    <p class="text-blue-400">🌐 https://konga.com/iphone-15</p>
                                </div>
                            </div>
                            <p class="text-xs mt-3 text-gray-400">💡 Tip: Prices change frequently. Check stores for latest offers!</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- CTA Section -->
    <section class="gradient-bg hero-pattern py-20">
        <div class="max-w-4xl mx-auto px-4 sm:px-6 lg:px-8 text-center">
            <h2 class="text-3xl md:text-4xl font-bold text-white mb-6">Ready to Save Money?</h2>
            <p class="text-xl text-white text-opacity-90 mb-8">
                Join thousands of smart Nigerian shoppers using PriceBotNG
            </p>
            <a href="https://wa.me/<?php echo str_replace('+', '', DEFAULT_COUNTRY_CODE) . WA_PHONE_NUMBER_ID; ?>?text=Hi%20PriceBotNG" 
               target="_blank"
               class="bg-green-500 hover:bg-green-600 text-white font-bold py-4 px-8 rounded-lg text-xl transition inline-flex items-center">
                <i class="fab fa-whatsapp mr-3 text-2xl"></i>Start Shopping Smarter
            </a>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-gray-900 text-white py-12">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <div>
                    <div class="flex items-center mb-4">
                        <i class="fas fa-robot text-purple-400 text-2xl mr-3"></i>
                        <span class="text-xl font-bold">PriceBotNG</span>
                    </div>
                    <p class="text-gray-400">
                        Nigeria's smartest price comparison bot, helping you find the best deals on WhatsApp.
                    </p>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-4">Features</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li>Price Comparison</li>
                        <li>WhatsApp Integration</li>
                        <li>Price Tracking</li>
                        <li>Nigerian Stores</li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-4">Supported Stores</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li>Jumia Nigeria</li>
                        <li>Konga</li>
                        <li>Slot Nigeria</li>
                        <li>More coming soon...</li>
                    </ul>
                </div>
                
                <div>
                    <h3 class="text-lg font-semibold mb-4">Contact</h3>
                    <ul class="space-y-2 text-gray-400">
                        <li>
                            <i class="fab fa-whatsapp mr-2"></i>
                            <a href="https://wa.me/<?php echo str_replace('+', '', DEFAULT_COUNTRY_CODE) . WA_PHONE_NUMBER_ID; ?>" class="hover:text-white transition">
                                WhatsApp Bot
                            </a>
                        </li>
                        <li>
                            <i class="fas fa-envelope mr-2"></i>
                            <?php echo ADMIN_EMAIL; ?>
                        </li>
                        <li>
                            <i class="fas fa-globe mr-2"></i>
                            Nigeria 🇳🇬
                        </li>
                    </ul>
                </div>
            </div>
            
            <div class="border-t border-gray-800 mt-8 pt-8 text-center text-gray-400">
                <p>&copy; 2025 PriceBotNG. Built for Nigerian shoppers with ❤️</p>
            </div>
        </div>
    </footer>

    <script>
        function scrollToDemo() {
            document.getElementById('demo').scrollIntoView({ 
                behavior: 'smooth' 
            });
        }
    </script>
</body>
</html>
