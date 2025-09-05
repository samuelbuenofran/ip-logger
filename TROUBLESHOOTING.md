# IP Logger Troubleshooting Guide

## Issue: 502/403 Errors When Accessing Short URLs

### Problem Description

When trying to access generated short URLs (e.g., `https://keizai-tech.com/NH1usvZg.html`), users get HTTP 502 (Bad Gateway) or 403 (Forbidden) errors. This prevents click tracking and geolocation data collection.

### Root Causes

#### 1. Database Connection Issues

- **502 Bad Gateway**: Server can't connect to the database
- **403 Forbidden**: Server rejects requests due to database failures

#### 2. Server Configuration Issues

- Missing or misconfigured `.htaccess` file
- `mod_rewrite` not enabled
- Incorrect file permissions
- PHP errors in `redirect.php`

#### 3. Path/URL Issues

- Incorrect `BASE_URL` configuration
- Mismatched domain/path settings
- Missing server document root configuration

### Solutions

#### For Local Testing

1. **Run Local Setup**:

   ```bash
   php setup_local.php
   ```

2. **Manual Local Setup**:

   ```bash
   # Create local database
   mysql -u root -p -e "CREATE DATABASE ip_logger_test;"

   # Update config for local testing
   cp config/config.local.php config/config.php

   # Run install script
   php install.php
   ```

3. **Test Locally**:
   - Access: `http://localhost/ip-logger/`
   - Create a test link
   - Try accessing the short URL

#### For Production Server

1. **Check Database Connection**:

   ```php
   // Test database connection
   <?php
   require_once 'config/config.php';
   require_once 'config/database.php';

   try {
       $db = new Database();
       $conn = $db->getConnection();
       echo "Database connection successful!";
   } catch (Exception $e) {
       echo "Database error: " . $e->getMessage();
   }
   ?>
   ```

2. **Verify Server Configuration**:

   ```bash
   # Check if mod_rewrite is enabled
   apache2ctl -M | grep rewrite

   # Check .htaccess file
   ls -la .htaccess

   # Check file permissions
   chmod 644 .htaccess
   chmod 755 redirect.php
   ```

3. **Test URL Rewriting**:

   ```bash
   # Test if .htaccess is working
   curl -I http://yourdomain.com/test123
   # Should redirect to redirect.php?short_code=test123
   ```

4. **Check PHP Error Logs**:
   ```bash
   # Check PHP error log
   tail -f /var/log/apache2/error.log
   # or
   tail -f /var/log/nginx/error.log
   ```

#### Common Fixes

1. **Enable mod_rewrite**:

   ```bash
   # Apache
   sudo a2enmod rewrite
   sudo systemctl restart apache2

   # Nginx (different configuration needed)
   # Add rewrite rules to server block
   ```

2. **Fix File Permissions**:

   ```bash
   chmod 644 .htaccess
   chmod 644 *.php
   chmod 755 config/
   chmod 755 includes/
   ```

3. **Update BASE_URL**:

   ```php
   // In config/config.php
   define('BASE_URL', 'https://yourdomain.com/');
   ```

4. **Check Database Credentials**:
   ```php
   // Verify in config/config.php
   define('DB_HOST', 'localhost'); // or your DB server
   define('DB_NAME', 'your_database_name');
   define('DB_USER', 'your_username');
   define('DB_PASS', 'your_password');
   ```

### Testing Steps

1. **Create Test Link**:

   - Go to Dashboard
   - Create a new link with URL: `https://www.google.com`
   - Note the generated short code

2. **Test Direct Access**:

   ```bash
   # Test redirect.php directly
   curl "http://yourdomain.com/redirect.php?short_code=YOUR_CODE"
   ```

3. **Test URL Rewriting**:

   ```bash
   # Test short URL
   curl -I "http://yourdomain.com/YOUR_CODE"
   curl -I "http://yourdomain.com/YOUR_CODE.html"
   ```

4. **Check Database**:

   ```sql
   -- Check if link exists
   SELECT * FROM links WHERE short_code = 'YOUR_CODE';

   -- Check if clicks are being recorded
   SELECT * FROM targets WHERE link_id = (SELECT id FROM links WHERE short_code = 'YOUR_CODE');
   ```

### Debug Mode

Enable debug mode in `config/config.php`:

```php
define('DEBUG_MODE', true);
define('LOG_ERRORS', true);
```

This will show detailed error messages and log issues to `logs/error.log`.

### Server Requirements

- **PHP**: 7.4+ (8.4 recommended)
- **MySQL**: 5.7+ or MariaDB 10.3+
- **Apache**: mod_rewrite enabled
- **Extensions**: PDO, PDO_MySQL, JSON, cURL

### Contact Support

If issues persist:

1. Check server error logs
2. Verify all requirements are met
3. Test with a simple PHP file first
4. Contact your hosting provider for server-specific issues
