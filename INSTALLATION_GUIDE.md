# PriceBotNG Installation Guide

## 📋 Pre-Requirements

### WhatsApp Business API Setup
1. **Meta Developer Account**: Create account at [developers.facebook.com](https://developers.facebook.com)
2. **WhatsApp Business App**: Create a new WhatsApp Business app
3. **Get Credentials**: Note down your Phone Number ID and Access Token
4. **Webhook Configuration**: You'll need to set this up after uploading files

### cPanel Hosting Requirements
- **PHP Version**: 7.4 or higher
- **MySQL Database**: Available with your hosting plan
- **File Manager**: Access to cPanel file manager or FTP
- **SSL Certificate**: HTTPS required for WhatsApp webhooks

## 🚀 Step-by-Step Installation

### Step 1: Database Setup
1. **Login to cPanel** → Go to MySQL Databases
2. **Create Database**: Name it `hslcomn2_whatsapp_bot` (or as per your preference)
3. **Create User**: Username `hslcomn2_whatsapp_bot` with password `cgaWQL5hZ)fhHY5&NW`
4. **Add User to Database**: Grant ALL PRIVILEGES
5. **Note**: Database tables will be created automatically when the app first runs

### Step 2: File Upload
1. **Extract Files**: Extract the PriceBotNG package
2. **Upload to cPanel**: 
   - Go to File Manager in cPanel
   - Navigate to `public_html/whatsprice/` (create directory if needed)
   - Upload all files maintaining the folder structure
3. **Set Permissions**: 
   - Set `logs/` directory to 755 or 777 permissions
   - Ensure PHP files have 644 permissions

### Step 3: Configuration
1. **Edit config.php**: Update the following if needed:
   ```php
   // Database configuration (update if different)
   define('DB_HOST', 'localhost');
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_database_user');
   define('DB_PASS', 'your_database_password');
   
   // WhatsApp API (update with your credentials)
   define('WA_ACCESS_TOKEN', 'your_access_token_here');
   define('WA_PHONE_NUMBER_ID', 'your_phone_number_id');
   ```

2. **Update Admin Credentials** (Optional):
   ```php
   define('ADMIN_EMAIL', 'your_admin@email.com');
   define('ADMIN_PASSWORD', 'your_secure_password');
   ```

### Step 4: WhatsApp Webhook Setup
1. **Get Webhook URL**: `https://yourdomain.com/whatsprice/webhook.php`
2. **Go to Meta Developer Console** → Your WhatsApp App → Configuration
3. **Set Webhook URL**: Enter your webhook URL
4. **Set Verify Token**: `pricebotng_verify_token_2025`
5. **Subscribe to Events**: Select `messages` field
6. **Verify**: Meta will send a verification request to your webhook

### Step 5: Testing
1. **Test Webhook**: Visit `https://yourdomain.com/whatsprice/webhook.php?health=check`
2. **Test Admin Panel**: Visit `https://yourdomain.com/whatsprice/admin/`
3. **Test WhatsApp**: Send a test message to your WhatsApp Business number

## 🔧 Configuration Details

### Database Configuration
The app uses MySQL with these tables (auto-created):
- `users` - User information and activity tracking
- `search_history` - All search queries for analytics  
- `price_cache` - Cached price data (1-hour TTL)
- `price_tracking` - User price tracking requests
- `vendors` - Vendor configuration and URLs
- `logs` - Application logging (if enabled)

### WhatsApp API Settings
```php
// Current configuration in config.php
define('WA_ACCESS_TOKEN', 'EAAJLd25hOtYBOZBZAYUsATuQRdVg1PVD02AYeZAuBTrjSZBKwg0TI52WrF6oEN8022L2Wl5nnw97aZChfgAKc0CZBs10YjQu56gQdQV9ewmAae4NY3ZBeJaGVYOKpSnTGUOX42aCeW9Gq37ti392F3aiCGFOZCZB36pjZCIh1iMrHSUJduWyBj2YxvMymKgi2ZAjuxeaiPXePVjAIRHJZCZAdljZCo12H9BxhjHKy38swuNfRtc2AEH7wK');
define('WA_PHONE_NUMBER_ID', '597838043423458');
define('WA_VERIFY_TOKEN', 'pricebotng_verify_token_2025');
```

### Admin Panel Access
- **URL**: `https://yourdomain.com/whatsprice/admin/`
- **Email**: `pricebotng@hsl.com.ng`
- **Password**: `Admin123`

## 🛠 Troubleshooting

### Common Issues

#### 1. Webhook Verification Fails
- **Check**: SSL certificate is valid and HTTPS is working
- **Verify**: Webhook URL is accessible publicly
- **Confirm**: Verify token matches exactly: `pricebotng_verify_token_2025`

#### 2. Database Connection Errors
- **Check**: Database credentials in config.php
- **Verify**: Database user has proper permissions
- **Test**: Use cPanel phpMyAdmin to test connection

#### 3. PHP Errors
- **Check**: PHP error logs in cPanel
- **Verify**: PHP version is 7.4 or higher
- **Ensure**: All file permissions are correct

#### 4. Admin Panel Won't Load
- **Check**: File permissions (644 for PHP files)
- **Verify**: Path to admin folder is correct
- **Test**: Direct access to `admin/login.php`

#### 5. Price Scraping Not Working
- **Note**: App uses mock data when live scraping fails
- **Check**: Internet connectivity from your server
- **Verify**: Target websites (Jumia, Konga, Slot) are accessible

### Testing Endpoints

1. **Health Check**: 
   ```
   GET https://yourdomain.com/whatsprice/webhook.php?health=check
   ```
   Expected: JSON response with status "healthy"

2. **Webhook Verification**: 
   ```
   GET https://yourdomain.com/whatsprice/webhook.php?hub_mode=subscribe&hub_verify_token=pricebotng_verify_token_2025&hub_challenge=test123
   ```
   Expected: Returns "test123"

3. **Admin Login**: 
   ```
   https://yourdomain.com/whatsprice/admin/login.php
   ```
   Expected: Login form loads

## 📱 Using the Bot

### For End Users
1. **Add WhatsApp Number**: Users add your WhatsApp Business number
2. **Send Product Query**: Users type product names like "iPhone 15 Pro Max"
3. **Get Price Comparison**: Bot responds with formatted price comparison
4. **Track Prices**: Users can reply "Track" to monitor price changes

### Sample User Interaction
```
User: "iPhone 15 price"

Bot: "🔍 Searching for 'iPhone 15' across Nigerian stores...
⏱️ This may take a moment for the best results!"

Bot: "🔍 Price Results for: iPhone 15

🥇 Jumia
💰 ₦850,000
📦 In Stock
🌐 https://jumia.com.ng/iphone-15

🥈 Konga  
💰 ₦899,000
📦 Available
🌐 https://konga.com/iphone-15

💡 Tip: Prices change frequently. Check stores for latest offers!"
```

## 🔒 Security Notes

1. **Change Default Passwords**: Update admin credentials in config.php
2. **Protect Admin Panel**: Consider IP whitelisting for admin access
3. **Secure API Keys**: Never expose WhatsApp tokens publicly
4. **Regular Backups**: Backup database and files regularly
5. **Monitor Logs**: Check logs/ directory for errors and suspicious activity

## 📈 Next Steps

1. **Test Thoroughly**: Send test messages and verify responses
2. **Monitor Performance**: Use admin dashboard to track usage
3. **Add More Vendors**: Extend scraper.php to support additional stores  
4. **Customize Messages**: Modify responses in functions.php
5. **Scale Infrastructure**: Consider dedicated hosting as usage grows

## 📞 Support

If you encounter issues:
1. Check the troubleshooting section above
2. Review logs in `logs/` directory
3. Test individual components using the admin panel
4. Verify WhatsApp webhook configuration in Meta Developer Console

## 🎯 Success Criteria

Your installation is successful when:
- ✅ Webhook verification passes in Meta Developer Console
- ✅ Health check endpoint returns "healthy" status
- ✅ Admin panel loads and accepts login credentials
- ✅ Test WhatsApp message receives proper bot response
- ✅ Price search returns formatted comparison results

---

**Congratulations!** 🎉 Your PriceBotNG WhatsApp price comparison bot is now ready to help Nigerian users find the best deals across multiple ecommerce platforms!