// package.json
{
  "name": "whatsapp-price-bot",
  "version": "1.0.0",
  "description": "Nigerian WhatsApp Price Comparison Bot",
  "main": "server.js",
  "scripts": {
    "start": "node server.js",
    "dev": "nodemon server.js",
    "scrape": "node scrapers/run_scrapers.js"
  },
  "dependencies": {
    "express": "^4.18.2",
    "axios": "^1.6.0",
    "puppeteer": "^21.5.0",
    "firebase-admin": "^11.11.0",
    "body-parser": "^1.20.2",
    "cors": "^2.8.5",
    "dotenv": "^16.3.1",
    "node-cron": "^3.0.2",
    "fuse.js": "^7.0.0",
    "express-rate-limit": "^7.1.5",
    "bcryptjs": "^2.4.3",
    "jsonwebtoken": "^9.0.2"
  },
  "devDependencies": {
    "nodemon": "^3.0.1"
  }
}

// .env
WHATSAPP_TOKEN=your_whatsapp_cloud_api_token
WHATSAPP_WEBHOOK_VERIFY_TOKEN=your_webhook_verify_token
PHONE_NUMBER_ID=your_phone_number_id
FIREBASE_PROJECT_ID=your_firebase_project_id
FIREBASE_PRIVATE_KEY=your_firebase_private_key
FIREBASE_CLIENT_EMAIL=your_firebase_client_email
ADMIN_EMAIL=admin@yourbot.com
ADMIN_PASSWORD=your_secure_password
JWT_SECRET=your_jwt_secret
AFFILIATE_ID=whatsprice_bot
PORT=3000

// server.js
const express = require('express');
const bodyParser = require('body-parser');
const cors = require('cors');
const cron = require('node-cron');
require('dotenv').config();

const whatsappHandler = require('./bot/webhook_handler');
const adminRoutes = require('./admin/routes');
const { runAllScrapers } = require('./scrapers/run_scrapers');

const app = express();
const PORT = process.env.PORT || 3000;

// Middleware
app.use(cors());
app.use(bodyParser.json());
app.use(express.static('admin/public'));

// Routes
app.use('/webhook', whatsappHandler);
app.use('/admin', adminRoutes);

// Health check
app.get('/', (req, res) => {
  res.json({ status: 'WhatsApp Price Bot is running!' });
});

// Schedule scrapers every 12 hours
cron.schedule('0 */12 * * *', async () => {
  console.log('Running scheduled scraping...');
  await runAllScrapers();
});

app.listen(PORT, () => {
  console.log(`Server running on port ${PORT}`);
});

// bot/webhook_handler.js
const express = require('express');
const { handleMessage } = require('./message_handler');
const router = express.Router();

// Webhook verification
router.get('/', (req, res) => {
  const mode = req.query['hub.mode'];
  const token = req.query['hub.verify_token'];
  const challenge = req.query['hub.challenge'];

  if (mode === 'subscribe' && token === process.env.WHATSAPP_WEBHOOK_VERIFY_TOKEN) {
    console.log('Webhook verified');
    res.status(200).send(challenge);
  } else {
    res.sendStatus(403);
  }
});

// Handle incoming messages
router.post('/', async (req, res) => {
  try {
    const body = req.body;
    
    if (body.object && body.entry && body.entry[0].changes) {
      const change = body.entry[0].changes[0];
      
      if (change.field === 'messages' && change.value.messages) {
        const message = change.value.messages[0];
        const from = message.from;
        const messageBody = message.text?.body;
        
        if (messageBody) {
          await handleMessage(from, messageBody);
        }
      }
    }
    
    res.sendStatus(200);
  } catch (error) {
    console.error('Webhook error:', error);
    res.sendStatus(500);
  }
});

module.exports = router;

// bot/message_handler.js
const { parseMessage } = require('./message_parser');
const { sendResponse } = require('./response_composer');
const { searchProducts } = require('../services/product_service');
const { trackProduct, getTrackedProducts } = require('../services/tracking_service');

async function handleMessage(from, messageBody) {
  try {
    const parsedMessage = parseMessage(messageBody);
    
    if (parsedMessage.type === 'search') {
      const products = await searchProducts(parsedMessage.query);
      await sendResponse(from, 'search_results', { query: parsedMessage.query, products });
      
    } else if (parsedMessage.type === 'track') {
      const result = await trackProduct(from, parsedMessage.query);
      await sendResponse(from, 'track_confirmation', { query: parsedMessage.query, result });
      
    } else if (parsedMessage.type === 'help') {
      await sendResponse(from, 'help');
      
    } else {
      await sendResponse(from, 'invalid_command');
    }
    
  } catch (error) {
    console.error('Message handling error:', error);
    await sendResponse(from, 'error');
  }
}

module.exports = { handleMessage };

// bot/message_parser.js
const Fuse = require('fuse.js');

const commands = [
  { pattern: /^track\s+(.+)/i, type: 'track' },
  { pattern: /^help$/i, type: 'help' },
  { pattern: /^start$/i, type: 'help' }
];

function parseMessage(message) {
  const cleanMessage = message.trim();
  
  // Check for commands
  for (const command of commands) {
    const match = cleanMessage.match(command.pattern);
    if (match) {
      return {
        type: command.type,
        query: match[1] ? normalizeProductName(match[1]) : null
      };
    }
  }
  
  // Default to search
  return {
    type: 'search',
    query: normalizeProductName(cleanMessage)
  };
}

function normalizeProductName(query) {
  // Basic normalization for common typos and variations
  return query
    .toLowerCase()
    .replace(/iphone?/gi, 'iPhone')
    .replace(/promax/gi, 'Pro Max')
    .replace(/\s+/g, ' ')
    .trim();
}

module.exports = { parseMessage, normalizeProductName };

// bot/response_composer.js
const axios = require('axios');

async function sendResponse(to, type, data = {}) {
  let message = '';
  
  switch (type) {
    case 'search_results':
      message = formatSearchResults(data.query, data.products);
      break;
    case 'track_confirmation':
      message = `✅ Now tracking "${data.query}"\n\nYou'll get alerts when prices drop by 5% or more!`;
      break;
    case 'help':
      message = `🤖 *PriceBot Nigeria*\n\n📱 Send me any product name to compare prices\n\n*Examples:*\n• iPhone 16 Pro Max\n• Samsung Galaxy S24\n• MacBook Air M2\n\n💡 Reply "Track [product]" to get price alerts\n\n🔍 I search Jumia, Slot, and Pointek for you!`;
      break;
    case 'invalid_command':
      message = `❓ I didn't understand that. Send me a product name like "iPhone 16" or type "help" for instructions.`;
      break;
    case 'error':
      message = `⚠️ Something went wrong. Please try again in a moment.`;
      break;
    default:
      message = 'Hello! Send me a product name to search for prices.';
  }
  
  await sendWhatsAppMessage(to, message);
}

function formatSearchResults(query, products) {
  if (!products || products.length === 0) {
    return `❌ No results found for "${query}"\n\nTry:\n• Different spelling\n• Shorter search terms\n• Popular brands like iPhone, Samsung, etc.`;
  }
  
  let message = `🔍 *Price Compare for: ${query}*\n\n`;
  
  products.slice(0, 3).forEach((product, index) => {
    const emoji = ['1️⃣', '2️⃣', '3️⃣'][index];
    const vendor = product.vendor.toUpperCase();
    const price = formatPrice(product.price);
    const stock = product.stock || 'Check availability';
    const link = addAffiliateId(product.link);
    
    message += `${emoji} *${vendor}*\n`;
    message += `₦${price} | ${stock}\n`;
    message += `🌐 ${link}\n\n`;
  });
  
  message += `💡 Reply "Track ${query}" to get price alerts!`;
  
  return message;
}

function formatPrice(price) {
  if (typeof price === 'number') {
    return price.toLocaleString('en-NG');
  }
  return price.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ',');
}

function addAffiliateId(url) {
  const affiliateId = process.env.AFFILIATE_ID;
  if (!affiliateId) return url;
  
  const separator = url.includes('?') ? '&' : '?';
  return `${url}${separator}aff_id=${affiliateId}`;
}

async function sendWhatsAppMessage(to, message) {
  try {
    const response = await axios.post(
      `https://graph.facebook.com/v18.0/${process.env.PHONE_NUMBER_ID}/messages`,
      {
        messaging_product: 'whatsapp',
        to: to,
        text: { body: message }
      },
      {
        headers: {
          'Authorization': `Bearer ${process.env.WHATSAPP_TOKEN}`,
          'Content-Type': 'application/json'
        }
      }
    );
    
    console.log('Message sent successfully');
  } catch (error) {
    console.error('Error sending WhatsApp message:', error.response?.data || error.message);
  }
}

module.exports = { sendResponse };

// scrapers/vendor_scraper_jumia.js
const puppeteer = require('puppeteer');

class JumiaScraper {
  constructor() {
    this.baseUrl = 'https://www.jumia.com.ng';
    this.searchUrl = 'https://www.jumia.com.ng/catalog/?q=';
  }
  
  async scrape(query) {
    let browser;
    try {
      browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
      });
      
      const page = await browser.newPage();
      await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
      
      const searchUrl = `${this.searchUrl}${encodeURIComponent(query)}`;
      await page.goto(searchUrl, { waitUntil: 'networkidle2', timeout: 30000 });
      
      const products = await page.evaluate(() => {
        const items = document.querySelectorAll('article[data-catalog-id]');
        const results = [];
        
        for (let i = 0; i < Math.min(items.length, 5); i++) {
          const item = items[i];
          
          const nameElement = item.querySelector('h3 a');
          const priceElement = item.querySelector('.prc');
          const linkElement = item.querySelector('a');
          const stockElement = item.querySelector('.bdg._glb');
          
          if (nameElement && priceElement && linkElement) {
            const name = nameElement.textContent.trim();
            const priceText = priceElement.textContent.trim();
            const price = parseInt(priceText.replace(/[₦,\s]/g, ''));
            const link = linkElement.href;
            const stock = stockElement ? stockElement.textContent.trim() : 'In Stock';
            
            results.push({
              name,
              price,
              link,
              stock,
              vendor: 'jumia'
            });
          }
        }
        
        return results;
      });
      
      return products;
      
    } catch (error) {
      console.error('Jumia scraping error:', error);
      return [];
    } finally {
      if (browser) {
        await browser.close();
      }
    }
  }
}

module.exports = JumiaScraper;

// scrapers/vendor_scraper_slot.js
const puppeteer = require('puppeteer');

class SlotScraper {
  constructor() {
    this.baseUrl = 'https://slot.ng';
    this.searchUrl = 'https://slot.ng/search?query=';
  }
  
  async scrape(query) {
    let browser;
    try {
      browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
      });
      
      const page = await browser.newPage();
      await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
      
      const searchUrl = `${this.searchUrl}${encodeURIComponent(query)}`;
      await page.goto(searchUrl, { waitUntil: 'networkidle2', timeout: 30000 });
      
      const products = await page.evaluate(() => {
        const items = document.querySelectorAll('.product-item, .product-card');
        const results = [];
        
        for (let i = 0; i < Math.min(items.length, 5); i++) {
          const item = items[i];
          
          const nameElement = item.querySelector('.product-name, h3, .title');
          const priceElement = item.querySelector('.price, .product-price');
          const linkElement = item.querySelector('a');
          const stockElement = item.querySelector('.stock-status, .availability');
          
          if (nameElement && priceElement && linkElement) {
            const name = nameElement.textContent.trim();
            const priceText = priceElement.textContent.trim();
            const price = parseInt(priceText.replace(/[₦,\s]/g, ''));
            const link = linkElement.href.startsWith('http') ? linkElement.href : 'https://slot.ng' + linkElement.href;
            const stock = stockElement ? stockElement.textContent.trim() : 'Check Stock';
            
            results.push({
              name,
              price,
              link,
              stock,
              vendor: 'slot'
            });
          }
        }
        
        return results;
      });
      
      return products;
      
    } catch (error) {
      console.error('Slot scraping error:', error);
      return [];
    } finally {
      if (browser) {
        await browser.close();
      }
    }
  }
}

module.exports = SlotScraper;

// scrapers/vendor_scraper_pointek.js
const puppeteer = require('puppeteer');

class PointekScraper {
  constructor() {
    this.baseUrl = 'https://pointekonline.com';
    this.searchUrl = 'https://pointekonline.com/search?q=';
  }
  
  async scrape(query) {
    let browser;
    try {
      browser = await puppeteer.launch({
        headless: true,
        args: ['--no-sandbox', '--disable-setuid-sandbox']
      });
      
      const page = await browser.newPage();
      await page.setUserAgent('Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36');
      
      const searchUrl = `${this.searchUrl}${encodeURIComponent(query)}`;
      await page.goto(searchUrl, { waitUntil: 'networkidle2', timeout: 30000 });
      
      const products = await page.evaluate(() => {
        const items = document.querySelectorAll('.product-item, .grid-item, .product');
        const results = [];
        
        for (let i = 0; i < Math.min(items.length, 5); i++) {
          const item = items[i];
          
          const nameElement = item.querySelector('h3, .product-title, .title');
          const priceElement = item.querySelector('.price, .amount');
          const linkElement = item.querySelector('a');
          const stockElement = item.querySelector('.stock, .availability');
          
          if (nameElement && priceElement && linkElement) {
            const name = nameElement.textContent.trim();
            const priceText = priceElement.textContent.trim();
            const price = parseInt(priceText.replace(/[₦,\s]/g, ''));
            const link = linkElement.href.startsWith('http') ? linkElement.href : 'https://pointekonline.com' + linkElement.href;
            const stock = stockElement ? stockElement.textContent.trim() : 'Available';
            
            results.push({
              name,
              price,
              link,
              stock,
              vendor: 'pointek'
            });
          }
        }
        
        return results;
      });
      
      return products;
      
    } catch (error) {
      console.error('Pointek scraping error:', error);
      return [];
    } finally {
      if (browser) {
        await browser.close();
      }
    }
  }
}

module.exports = PointekScraper;

// scrapers/run_scrapers.js
const JumiaScraper = require('./vendor_scraper_jumia');
const SlotScraper = require('./vendor_scraper_slot');
const PointekScraper = require('./vendor_scraper_pointek');
const { cacheProducts } = require('../services/cache_service');

const scrapers = {
  jumia: new JumiaScraper(),
  slot: new SlotScraper(),
  pointek: new PointekScraper()
};

async function scrapeVendor(vendor, query) {
  try {
    console.log(`Scraping ${vendor} for: ${query}`);
    const scraper = scrapers[vendor];
    const products = await scraper.scrape(query);
    
    // Cache results
    await cacheProducts(vendor, query, products);
    
    return products;
  } catch (error) {
    console.error(`Error scraping ${vendor}:`, error);
    return [];
  }
}

async function scrapeAllVendors(query) {
  const promises = Object.keys(scrapers).map(vendor => 
    scrapeVendor(vendor, query)
  );
  
  const results = await Promise.allSettled(promises);
  const allProducts = [];
  
  results.forEach((result, index) => {
    if (result.status === 'fulfilled') {
      allProducts.push(...result.value);
    } else {
      console.error(`Scraper ${Object.keys(scrapers)[index]} failed:`, result.reason);
    }
  });
  
  return allProducts;
}

async function runAllScrapers() {
  const popularQueries = [
    'iPhone 16 Pro Max',
    'Samsung Galaxy S24',
    'MacBook Air',
    'iPad Pro',
    'AirPods Pro'
  ];
  
  for (const query of popularQueries) {
    await scrapeAllVendors(query);
    // Wait between queries to be respectful
    await new Promise(resolve => setTimeout(resolve, 2000));
  }
}

module.exports = { scrapeVendor, scrapeAllVendors, runAllScrapers };

// services/firebase_service.js
const admin = require('firebase-admin');

const serviceAccount = {
  type: "service_account",
  project_id: process.env.FIREBASE_PROJECT_ID,
  private_key: process.env.FIREBASE_PRIVATE_KEY?.replace(/\\n/g, '\n'),
  client_email: process.env.FIREBASE_CLIENT_EMAIL
};

admin.initializeApp({
  credential: admin.credential.cert(serviceAccount),
  databaseURL: `https://${process.env.FIREBASE_PROJECT_ID}.firebaseio.com`
});

const db = admin.firestore();

module.exports = { db, admin };

// services/product_service.js
const { scrapeAllVendors } = require('../scrapers/run_scrapers');
const { getCachedProducts } = require('./cache_service');
const Fuse = require('fuse.js');

async function searchProducts(query) {
  try {
    // First check cache
    const cachedProducts = await getCachedProducts(query);
    
    if (cachedProducts && cachedProducts.length > 0) {
      return rankProducts(cachedProducts, query);
    }
    
    // If no cache, scrape fresh
    const products = await scrapeAllVendors(query);
    
    return rankProducts(products, query);
    
  } catch (error) {
    console.error('Product search error:', error);
    return [];
  }
}

function rankProducts(products, query) {
  if (!products || products.length === 0) return [];
  
  // Use fuzzy search to rank by relevance
  const fuse = new Fuse(products, {
    keys: ['name'],
    threshold: 0.6,
    includeScore: true
  });
  
  const results = fuse.search(query);
  
  // Sort by relevance, then by price
  const rankedProducts = results
    .map(result => result.item)
    .sort((a, b) => {
      // Prefer lower prices for same relevance
      return a.price - b.price;
    });
  
  return rankedProducts.slice(0, 3);
}

module.exports = { searchProducts };

// services/cache_service.js
const { db } = require('./firebase_service');

async function cacheProducts(vendor, query, products) {
  try {
    const cacheRef = db.collection('product_cache');
    const cacheKey = `${vendor}_${query.toLowerCase().replace(/\s+/g, '_')}`;
    
    await cacheRef.doc(cacheKey).set({
      vendor,
      query,
      products,
      timestamp: admin.firestore.FieldValue.serverTimestamp(),
      expiresAt: new Date(Date.now() + 12 * 60 * 60 * 1000) // 12 hours
    });
    
  } catch (error) {
    console.error('Cache error:', error);
  }
}

async function getCachedProducts(query) {
  try {
    const cacheRef = db.collection('product_cache');
    const normalizedQuery = query.toLowerCase().replace(/\s+/g, '_');
    
    const snapshot = await cacheRef
      .where('query', '==', query)
      .where('expiresAt', '>', new Date())
      .get();
    
    const allProducts = [];
    snapshot.forEach(doc => {
      const data = doc.data();
      if (data.products) {
        allProducts.push(...data.products);
      }
    });
    
    return allProducts;
    
  } catch (error) {
    console.error('Cache retrieval error:', error);
    return [];
  }
}

module.exports = { cacheProducts, getCachedProducts };

// services/tracking_service.js
const { db } = require('./firebase_service');

async function trackProduct(userId, query) {
  try {
    const trackingRef = db.collection('tracked_products');
    
    await trackingRef.add({
      userId,
      query,
      createdAt: admin.firestore.FieldValue.serverTimestamp(),
      active: true
    });
    
    return { success: true };
    
  } catch (error) {
    console.error('Tracking error:', error);
    return { success: false, error: error.message };
  }
}

async function getTrackedProducts(userId) {
  try {
    const trackingRef = db.collection('tracked_products');
    const snapshot = await trackingRef
      .where('userId', '==', userId)
      .where('active', '==', true)
      .get();
    
    const trackedProducts = [];
    snapshot.forEach(doc => {
      trackedProducts.push({ id: doc.id, ...doc.data() });
    });
    
    return trackedProducts;
    
  } catch (error) {
    console.error('Get tracked products error:', error);
    return [];
  }
}

module.exports = { trackProduct, getTrackedProducts };

// admin/routes.js
const express = require('express');
const bcrypt = require('bcryptjs');
const jwt = require('jsonwebtoken');
const { db } = require('../services/firebase_service');
const router = express.Router();

// Middleware to verify admin token
function verifyToken(req, res, next) {
  const token = req.headers['authorization']?.split(' ')[1];
  
  if (!token) {
    return res.status(401).json({ error: 'No token provided' });
  }
  
  try {
    const decoded = jwt.verify(token, process.env.JWT_SECRET);
    req.user = decoded;
    next();
  } catch (error) {
    return res.status(401).json({ error: 'Invalid token' });
  }
}

// Admin login
router.post('/login', async (req, res) => {
  try {
    const { email, password } = req.body;
    
    if (email === process.env.ADMIN_EMAIL && password === process.env.ADMIN_PASSWORD) {
      const token = jwt.sign({ email }, process.env.JWT_SECRET, { expiresIn: '24h' });
      res.json({ token, success: true });
    } else {
      res.status(401).json({ error: 'Invalid credentials' });
    }
  } catch (error) {
    res.status(500).json({ error: 'Login failed' });
  }
});

// Get dashboard data
router.get('/dashboard', verifyToken, async (req, res) => {
  try {
    // Get recent searches
    const cacheSnapshot = await db.collection('product_cache')
      .orderBy('timestamp', 'desc')
      .limit(10)
      .get();
    
    const recentSearches = [];
    cacheSnapshot.forEach(doc => {
      const data = doc.data();
      recentSearches.push({
        query: data.query,
        vendor: data.vendor,
        products: data.products?.length || 0,
        timestamp: data.timestamp?.toDate()
      });
    });
    
    // Get tracking stats
    const trackingSnapshot = await db.collection('tracked_products')
      .where('active', '==', true)
      .get();
    
    const trackingStats = {
      totalTracked: trackingSnapshot.size,
      uniqueUsers: new Set(trackingSnapshot.docs.map(doc => doc.data().userId)).size
    };
    
    res.json({
      recentSearches,
      trackingStats,
      vendors: ['jumia', 'slot', 'pointek']
    });
    
  } catch (error) {
    console.error('Dashboard error:', error);
    res.status(500).json({ error: 'Failed to load dashboard' });
  }
});

// Serve admin panel
router.get('/', (req, res) => {
  res.send(`
    <!DOCTYPE html>
    <html>
    <head>
        <title>PriceBot Admin</title>
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <style>
            body { font-family: Arial, sans-serif; margin: 0; padding: 20px; background: #f5f5f5; }
            .container { max-width: 1200px; margin: 0 auto; background: white; padding: 20px; border-radius: 8px; }
            .header { border-bottom: 1px solid #eee; padding-bottom: 20px; margin-bottom: 20px; }
            .stats { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 30px; }
            .stat-card { background: #007bff; color: white; padding: 20px; border-radius: 8px; text-align: center; }
            .stat-number { font-size: 2em; font-weight: bold; }
            .recent-searches { background: #f8f9fa; padding: 20px; border-radius: 8px; }
            .search-item { padding: 10px; border-bottom: 1px solid #dee2e6; }
            .login-form { max-width: 400px; margin: 50px auto; padding: 30px; background: white; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
            .form-group { margin-bottom: 20px; }
            .form-group label { display: block; margin-bottom: 5px; font-weight: bold; }
            .form-group input { width: 100%; padding: 10px; border: 1px solid #ddd; border-radius: 4px; }
            .btn { background: #007bff; color: white; padding: 10px 20px; border: none; border-radius: 4px; cursor: pointer; }
            .btn:hover { background: #0056b3; }
            .hidden { display: none; }
        </style>
    </head>
    <body>
        <div id="loginForm" class="login-form">
            <h2>Admin Login</h2>
            <form onsubmit="login(event)">
                <div class="form-group">
                    <label>Email:</label>
                    <input type="email" id="email" required>
                </div>
                <div class="form-group">
                    <label>Password:</label>
                    <input type="password" id="password" required>
                </div>
                <button type="submit" class="btn">Login</button>
            </form>
        </div>

        <div id="dashboard" class="container hidden">
            <div class="header">
                <h1>PriceBot Admin Dashboard</h1>
                <button onclick="logout()" class="btn">Logout</button>
            </div>
            
            <div class="stats">
                <div class="stat-card">
                    <div class="stat-number" id="totalTracked">0</div>
                    <div>Products Tracked</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number" id="uniqueUsers">0</div>
                    <div>Active Users</div>
                </div>
                <div class="stat-card">
                    <div class="stat-number">3</div>
                    <div>Active Vendors</div>
                </div>
            </div>
            
            <div class="recent-searches">
                <h3>Recent Searches</h3>
                <div id="searchList">Loading...</div>
            </div>
        </div>

        <script>
            let token = localStorage.getItem('adminToken');
            
            if (token) {
                showDashboard();
            } else {
                showLogin();
            }
            
            async function login(event) {
                event.preventDefault();
                
                const email = document.getElementById('email').value;
                const password = document.getElementById('password').value;
                
                try {
                    const response = await fetch('/admin/login', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ email, password })
                    });
                    
                    const data = await response.json();
                    
                    if (data.success) {
                        localStorage.setItem('adminToken', data.token);
                        showDashboard();
                    } else {
                        alert('Invalid credentials');
                    }
                } catch (error) {
                    alert('Login failed');
                    console.error(error);
                }
            }
            
            function logout() {
                localStorage.removeItem('adminToken');
                showLogin();
            }
            
            function showLogin() {
                document.getElementById('loginForm').classList.remove('hidden');
                document.getElementById('dashboard').classList.add('hidden');
            }
            
            function showDashboard() {
                document.getElementById('loginForm').classList.add('hidden');
                document.getElementById('dashboard').classList.remove('hidden');
                loadDashboardData();
            }
            
            async function loadDashboardData() {
                try {
                    const response = await fetch('/admin/dashboard', {
                        headers: { 'Authorization': `Bearer ${token}` }
                    });
                    
                    const data = await response.json();
                    
                    document.getElementById('totalTracked').textContent = data.trackingStats.totalTracked;
                    document.getElementById('uniqueUsers').textContent = data.trackingStats.uniqueUsers;
                    
                    const searchList = document.getElementById('searchList');
                    if (data.recentSearches.length > 0) {
                        searchList.innerHTML = data.recentSearches.map(search => `
                            <div class="search-item">
                                <strong>${search.query}</strong> on ${search.vendor} 
                                - ${search.products} products found
                                <small>(${new Date(search.timestamp).toLocaleString()})</small>
                            </div>
                        `).join('');
                    } else {
                        searchList.innerHTML = '<p>No recent searches</p>';
                    }
                    
                } catch (error) {
                    console.error('Failed to load dashboard:', error);
                    if (error.status === 401) {
                        logout();
                    }
                }
            }
        </script>
    </body>
    </html>
  `);
});

module.exports = router;

// cron_jobs.js - Scheduled tasks for price monitoring
const cron = require('node-cron');
const { db } = require('./services/firebase_service');
const { searchProducts } = require('./services/product_service');
const { sendResponse } = require('./bot/response_composer');

// Check for price drops every 6 hours
cron.schedule('0 */6 * * *', async () => {
  console.log('Checking for price drops...');
  await checkPriceDrops();
});

async function checkPriceDrops() {
  try {
    const trackingSnapshot = await db.collection('tracked_products')
      .where('active', '==', true)
      .get();
    
    const priceHistoryRef = db.collection('price_history');
    
    for (const doc of trackingSnapshot.docs) {
      const tracking = doc.data();
      const { userId, query } = tracking;
      
      // Get current prices
      const currentProducts = await searchProducts(query);
      
      if (currentProducts.length === 0) continue;
      
      // Get previous prices
      const historySnapshot = await priceHistoryRef
        .where('query', '==', query)
        .orderBy('timestamp', 'desc')
        .limit(1)
        .get();
      
      let previousPrices = {};
      if (!historySnapshot.empty) {
        previousPrices = historySnapshot.docs[0].data().prices;
      }
      
      // Check for significant price drops (5% or more)
      const priceDrops = [];
      const currentPrices = {};
      
      currentProducts.forEach(product => {
        const vendor = product.vendor;
        const currentPrice = product.price;
        const previousPrice = previousPrices[vendor];
        
        currentPrices[vendor] = currentPrice;
        
        if (previousPrice && currentPrice < previousPrice) {
          const dropPercentage = ((previousPrice - currentPrice) / previousPrice) * 100;
          
          if (dropPercentage >= 5) {
            priceDrops.push({
              vendor,
              previousPrice,
              currentPrice,
              dropPercentage: Math.round(dropPercentage),
              product
            });
          }
        }
      });
      
      // Save current prices to history
      await priceHistoryRef.add({
        query,
        prices: currentPrices,
        timestamp: admin.firestore.FieldValue.serverTimestamp()
      });
      
      // Send alerts for price drops
      if (priceDrops.length > 0) {
        await sendPriceDropAlert(userId, query, priceDrops);
      }
    }
    
  } catch (error) {
    console.error('Price drop check error:', error);
  }
}

async function sendPriceDropAlert(userId, query, priceDrops) {
  let message = `🚨 *Price Drop Alert!*\n\n`;
  message += `💰 Great deals found for: *${query}*\n\n`;
  
  priceDrops.forEach(drop => {
    const { vendor, previousPrice, currentPrice, dropPercentage, product } = drop;
    message += `📉 *${vendor.toUpperCase()}*\n`;
    message += `Was: ₦${previousPrice.toLocaleString()}\n`;
    message += `Now: ₦${currentPrice.toLocaleString()}\n`;
    message += `💸 Save ${dropPercentage}% (₦${(previousPrice - currentPrice).toLocaleString()})\n`;
    message += `🌐 ${product.link}\n\n`;
  });
  
  message += `🔥 Prices updated ${new Date().toLocaleString()}`;
  
  await sendResponse(userId, 'custom', { message });
}

// deployment.md - Deployment instructions
const deploymentGuide = `
# WhatsApp Price Comparison Bot - Deployment Guide

## Prerequisites
1. Meta Business Account
2. WhatsApp Business API access
3. Firebase project
4. Node.js hosting service (Render, Railway, or Vercel)

## Step 1: WhatsApp Business API Setup
1. Go to Meta for Developers (developers.facebook.com)
2. Create a new app > Business > WhatsApp
3. Add WhatsApp product to your app
4. Get your:
   - Access Token
   - Phone Number ID
   - Webhook Verify Token (create your own)

## Step 2: Firebase Setup
1. Create Firebase project at console.firebase.google.com
2. Enable Firestore Database
3. Generate service account key:
   - Project Settings > Service Accounts
   - Generate new private key
   - Copy project_id, private_key, client_email

## Step 3: Environment Variables
Create .env file with:
\`\`\`
WHATSAPP_TOKEN=your_access_token_from_meta
WHATSAPP_WEBHOOK_VERIFY_TOKEN=your_custom_verify_token
PHONE_NUMBER_ID=your_phone_number_id
FIREBASE_PROJECT_ID=your_firebase_project_id
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\\nYOUR_KEY\\n-----END PRIVATE KEY-----\\n"
FIREBASE_CLIENT_EMAIL=your-service-account@project.iam.gserviceaccount.com
ADMIN_EMAIL=admin@yourdomain.com
ADMIN_PASSWORD=secure_admin_password
JWT_SECRET=your_jwt_secret_key
AFFILIATE_ID=your_affiliate_tracking_id
PORT=3000
\`\`\`

## Step 4: Deploy to Hosting Service

### Option A: Deploy to Render
1. Connect GitHub repo to Render
2. Create new Web Service
3. Build Command: \`npm install\`
4. Start Command: \`npm start\`
5. Add environment variables in Render dashboard
6. Deploy

### Option B: Deploy to Railway
1. Install Railway CLI: \`npm install -g @railway/cli\`
2. Login: \`railway login\`
3. Deploy: \`railway up\`
4. Add environment variables: \`railway variables set KEY=value\`

### Option C: Deploy to Vercel (API routes)
1. Install Vercel CLI: \`npm install -g vercel\`
2. Deploy: \`vercel --prod\`
3. Add environment variables in Vercel dashboard

## Step 5: Configure WhatsApp Webhook
1. In Meta Developer Console > WhatsApp > Configuration
2. Set Webhook URL: \`https://your-domain.com/webhook\`
3. Set Verify Token: same as WHATSAPP_WEBHOOK_VERIFY_TOKEN
4. Subscribe to 'messages' field
5. Test webhook connection

## Step 6: Test the Bot
1. Send test message to your WhatsApp Business number
2. Try: "iPhone 16 Pro Max"
3. Check admin panel at: \`https://your-domain.com/admin\`

## Step 7: Production Optimizations
1. Enable HTTPS (required for WhatsApp)
2. Set up monitoring (logs, uptime)
3. Configure auto-scaling if needed
4. Set up backup for Firebase
5. Test rate limiting

## Troubleshooting
- Check Render/Railway logs for errors
- Verify environment variables are set
- Test Firebase connection separately
- Use WhatsApp webhook debugger
- Check Meta Business verification status

## Scaling Considerations
- Add Redis for caching
- Use queue system for scraping jobs
- Implement load balancing
- Add more vendor scrapers
- Set up CDN for faster responses

## Maintenance
- Monitor scraper success rates
- Update selectors when sites change
- Clean old cache data monthly
- Backup tracked products data
- Update affiliate links as needed
`;

// README.md
const readme = `
# 🤖 WhatsApp Price Comparison Bot for Nigeria

A smart WhatsApp bot that helps Nigerian users compare product prices across major e-commerce platforms (Jumia, Slot, Pointek) with real-time scraping, price tracking, and mobile-optimized responses.

## ✨ Features

### 🔍 **Smart Product Search**
- Natural language processing for product queries
- Fuzzy matching for typos and variations
- Returns top 3 price matches with vendor info

### 📱 **WhatsApp Integration**
- Native WhatsApp Cloud API integration
- Mobile-first, low-bandwidth optimized
- Clean, scannable response format

### 📊 **Real-time Price Scraping**
- Automated scraping from 3+ Nigerian vendors
- 12-hour scheduled updates
- Intelligent caching system

### 🔔 **Price Drop Alerts**
- Track favorite products
- Get notified for 5%+ price drops
- Automatic price monitoring

### 🛠 **Admin Dashboard**
- Monitor bot usage and performance
- View recent searches and tracking stats
- Manage vendor configurations

## 🚀 Quick Setup

1. **Clone & Install**
   \`\`\`bash
   git clone <repo-url>
   cd whatsapp-price-bot
   npm install
   \`\`\`

2. **Configure Environment**
   \`\`\`bash
   cp .env.example .env
   # Edit .env with your credentials
   \`\`\`

3. **Start Development**
   \`\`\`bash
   npm run dev
   \`\`\`

4. **Deploy to Production**
   - See [Deployment Guide](deployment.md)

## 💬 Bot Commands

- **Search Products**: Just send product name
  - Example: "iPhone 16 Pro Max"
  
- **Track Products**: "Track [product name]"
  - Example: "Track Samsung Galaxy S24"
  
- **Get Help**: Send "help" or "start"

## 🏗 Architecture

\`\`\`
├── bot/ - WhatsApp message handling
├── scrapers/ - Vendor-specific scrapers
├── services/ - Business logic & Firebase
├── admin/ - Dashboard and management
└── deployment/ - Production configs
\`\`\`

## 🛍 Supported Vendors

- **Jumia Nigeria** - jumia.com.ng
- **Slot Nigeria** - slot.ng  
- **Pointek** - pointekonline.com

*Easily extensible to add more vendors*

## 📊 Response Format

\`\`\`
🔍 Price Compare for: iPhone 16 Pro Max

1️⃣ SLOT NIGERIA
₦1,050,000 | In Stock
🌐 https://slot.ng/iphone-16

2️⃣ JUMIA
₦1,099,500 | Verified Seller  
🌐 https://jumia.com.ng/iphone-16

3️⃣ POINTEK
₦999,000 | Limited Stock
🌐 https://pointekonline.com/product/iphone-16

💡 Reply "Track iPhone 16 Pro Max" to get price alerts!
\`\`\`

## 🔧 Technical Stack

- **Backend**: Node.js + Express
- **Database**: Firebase Firestore
- **Scraping**: Puppeteer (headless Chrome)
- **WhatsApp**: Meta Cloud API
- **Hosting**: Render/Railway/Vercel
- **Caching**: In-memory + Firebase
- **Monitoring**: Built-in admin dashboard

## 📈 Scaling Features

- ✅ Modular scraper architecture
- ✅ Intelligent caching system  
- ✅ Rate limiting protection
- ✅ Error handling & logging
- ✅ Affiliate link support
- ✅ Mobile-optimized responses

## 🇳🇬 Nigeria-Specific Optimizations

- **Low bandwidth friendly** - Text only responses
- **Naira currency formatting** - ₦1,050,000
- **Local vendor integration** - Nigerian e-commerce sites
- **Mobile-first design** - WhatsApp native experience
- **Data-efficient caching** - Reduces API calls

## 🛡 Security & Compliance

- JWT-based admin authentication
- Rate limiting on all endpoints
- Input sanitization for scrapers
- Secure environment variable handling
- HTTPS-only webhook endpoints

## 📝 License

MIT License - Build amazing price comparison tools!

---

**Ready to help Nigerian shoppers save money! 💰🇳🇬**
`;

console.log('WhatsApp Price Comparison Bot generated successfully!');
console.log('\\nNext steps:');
console.log('1. Set up WhatsApp Business API credentials');
console.log('2. Create Firebase project and get service account key');
console.log('3. Configure environment variables');
console.log('4. Deploy to your hosting service');
console.log('5. Configure webhook URL in Meta Developer Console');
console.log('\\nSee deployment.md for detailed instructions.');