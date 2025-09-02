# IP Logger - URL Shortener & IP Tracker

A powerful PHP-based URL shortener that tracks IP addresses, geolocation data, and user behavior when people click on your shortened links.

## Features

- **URL Shortening**: Create short, memorable links from long URLs
- **IP Tracking**: Capture visitor IP addresses and geolocation data
- **Google Maps Integration**: Visualize visitor locations on an interactive map
- **Password Protection**: Secure your tracking data with passwords
- **Link Expiration**: Set automatic expiration dates for links
- **Device Detection**: Identify desktop vs mobile users
- **Real-time Analytics**: View click statistics and visitor information
- **Privacy Compliant**: Built with privacy regulations in mind
- **No Registration Required**: Publicly accessible tool

## Requirements

- **PHP**: 8.0 or higher
- **MySQL**: 5.7.44 or higher
- **Web Server**: Apache with mod_rewrite enabled
- **Google Maps API Key**: For location mapping (optional)

## Installation

### 1. Download and Extract

```bash
# Clone or download the project
git clone https://github.com/yourusername/ip-logger.git
cd ip-logger
```

### 2. Configure Database

Edit `config/config.php` and update the database settings:

```php
// Database Configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'ip_logger');
define('DB_USER', 'your_username');
define('DB_PASS', 'your_password');
```

### 3. Set Permissions

```bash
# Set write permissions for config directory
chmod 755 config/
chmod 644 config/*.php
```

### 4. Run Installation

Visit `http://yourdomain.com/install.php` in your browser to:

- Create the database and tables
- Insert default settings
- Create sample data for testing
- Generate `.htaccess` file

### 5. Configure Google Maps (Optional)

1. Get a Google Maps API key from [Google Cloud Console](https://console.cloud.google.com/)
2. Edit `config/config.php` and add your API key:
   ```php
   define('GOOGLE_MAPS_API_KEY', 'your_api_key_here');
   ```

### 6. Remove Installation File

```bash
# For security, remove the installation file
rm install.php
```

## Usage

### Creating Links

1. Visit your IP Logger installation
2. Enter the URL you want to shorten
3. Set a password to protect your tracking data
4. Choose whether to set an expiration date
5. Click "Create Link"

### Viewing Tracking Data

1. Click "View Targets" on any link
2. Enter the password you set when creating the link
3. View detailed analytics including:
   - IP addresses and locations
   - Device types (desktop/mobile)
   - Click timestamps
   - Google Maps visualization

### Sample Data

After installation, you can test with these sample links:

- **Link 1**: `yourdomain.com/sample1` (Password: `test123`)
- **Link 2**: `yourdomain.com/sample2` (Password: `test123`)

## File Structure

```
ip-logger/
├── config/
│   ├── config.php          # Main configuration
│   ├── database.php        # Database connection class
│   └── installed.txt       # Installation marker
├── assets/
│   ├── css/
│   │   └── style.css       # Custom styles
│   └── js/
│       └── script.js       # JavaScript functions
├── index.php               # Main dashboard
├── redirect.php           # URL redirect handler
├── view_targets.php       # Target data viewer
├── install.php            # Installation script
├── .htaccess              # URL rewriting rules
└── README.md              # This file
```

## API Endpoints

### Create Link

```
POST /index.php
Parameters:
- action: 'create_link'
- original_url: The URL to shorten
- password: Password for protection
- no_expiry: 1 for no expiration (optional)
```

### View Targets

```
GET /view_targets.php?link_id={id}
POST /view_targets.php
Parameters:
- password: Link password
```

### Redirect

```
GET /{short_code}
Redirects to original URL and logs visit
```

## Security Features

- **Password Protection**: All tracking data is password-protected
- **SQL Injection Prevention**: Prepared statements used throughout
- **XSS Protection**: Input sanitization and output escaping
- **CSRF Protection**: Token-based form protection
- **Rate Limiting**: Built-in request throttling
- **Security Headers**: X-Frame-Options, X-XSS-Protection, etc.

## Privacy Compliance

This tool is designed to respect user privacy:

- **GDPR Compliant**: Data retention controls and user rights
- **CCPA Ready**: California Consumer Privacy Act compliance
- **Data Minimization**: Only collects necessary information
- **Transparency**: Clear privacy policy and data usage
- **User Control**: Easy data deletion and export

## Customization

### Styling

Edit `assets/css/style.css` to customize the appearance.

### Functionality

Modify `assets/js/script.js` to add custom JavaScript features.

### Settings

Update the `settings` table in the database to change:

- Site name and description
- Default expiration days
- Data retention period
- Privacy policy text

## Troubleshooting

### Common Issues

**Database Connection Error**

- Verify database credentials in `config/config.php`
- Ensure MySQL service is running
- Check database user permissions

**URL Rewriting Not Working**

- Ensure Apache mod_rewrite is enabled
- Verify `.htaccess` file exists and is readable
- Check server configuration allows .htaccess overrides

**Google Maps Not Loading**

- Verify API key is correct in `config/config.php`
- Check API key has Maps JavaScript API enabled
- Ensure billing is set up for Google Cloud project

**Permission Errors**

- Set correct file permissions (755 for directories, 644 for files)
- Ensure web server user has write access to config directory

### Debug Mode

To enable debug mode, add this to `config/config.php`:

```php
define('DEBUG_MODE', true);
```

## Support

For support and questions:

- Create an issue on GitHub
- Check the troubleshooting section above
- Review the code comments for implementation details

## License

This project is licensed under the MIT License - see the LICENSE file for details.

## Contributing

1. Fork the repository
2. Create a feature branch
3. Make your changes
4. Test thoroughly
5. Submit a pull request

## Changelog

### Version 1.0.0

- Initial release
- URL shortening functionality
- IP tracking and geolocation
- Google Maps integration
- Password protection
- Link expiration
- Responsive design

## Disclaimer

This tool is for legitimate tracking purposes only. Users are responsible for complying with applicable privacy laws and regulations in their jurisdiction. The developers are not responsible for misuse of this software.
