# PriceBotNG - WhatsApp Price Comparison Bot

A WhatsApp chatbot that helps Nigerian users compare product prices across multiple ecommerce vendors including Jumia, Konga, and Slot.

## Features

- **WhatsApp Integration**: Native WhatsApp Cloud API integration
- **Multi-Vendor Price Comparison**: Scrapes prices from top Nigerian ecommerce sites
- **Real-time Search**: Instant price comparison with caching for performance
- **Admin Dashboard**: Comprehensive admin panel for monitoring and management
- **Mobile Optimized**: Built for Nigeria's mobile-first, low-bandwidth environment
- **Price Tracking**: Users can track products for price drop notifications
- **Affiliate Ready**: Support for affiliate links and revenue generation

## Technology Stack

- **Backend**: PHP (cPanel compatible)
- **Database**: MySQL
- **Frontend**: HTML/CSS/JavaScript with Tailwind CSS
- **API**: WhatsApp Cloud API
- **Scraping**: cURL-based web scraping

## Installation

1. Upload all files to your cPanel hosting directory
2. Set up the MySQL database using provided credentials
3. Configure WhatsApp webhook URL: `https://yourdomain.com/webhook.php`
4. Access admin panel at: `https://yourdomain.com/admin/`

## Configuration

All configuration is handled in `config.php`:

- WhatsApp API credentials
- Database connection settings
- Vendor configurations
- Admin credentials

## Usage

### For Users
1. Send a WhatsApp message to the bot number
2. Type any product name (e.g., "iPhone 15 Pro Max")
3. Receive formatted price comparison from multiple vendors
4. Click links to purchase or reply "Track" to monitor prices

### For Admins
1. Login to admin panel with provided credentials
2. Monitor search activity and user engagement
3. Manage vendor settings and configurations
4. Export data and analytics
5. Test webhook connections

## File Structure

```
/app/
├── config.php              # Main configuration file
├── database.php             # Database connection and models
├── functions.php            # Helper functions
├── scraper.php              # Price scraping logic
├── webhook.php              # WhatsApp webhook handler
├── index.php               # Public landing page
├── admin/                   # Admin panel
│   ├── index.php           # Dashboard
│   ├── login.php           # Admin login
│   ├── logout.php          # Logout handler
│   ├── test_webhook.php    # Webhook testing
│   ├── clear_cache.php     # Cache management
│   └── export.php          # Data export
└── logs/                   # Application logs
```

## Getting Started

1. Set up WhatsApp webhook URL in Meta Developer Console
2. Upload files to cPanel hosting
3. Configure database and API credentials in config.php
4. Test webhook connection through admin panel