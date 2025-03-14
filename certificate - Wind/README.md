# VeriSureX

![VeriSureX Logo](logo.png)

A premium certificate verification system that provides enterprise-grade security and verification capabilities for organizations to issue, manage, and authenticate digital certificates.

## Features

- Secure certificate verification using unique IDs
- QR code scanning for quick verification
- Rate limiting to prevent abuse
- Digital signature verification
- Detailed verification logging with analytics
- Mobile-responsive design
- Browser and device detection
- Enhanced security features
- Real-time verification status
- Comprehensive audit trails
- Customizable certificate templates

## Requirements

- PHP 7.4 or higher
- MySQL 5.7 or higher
- Apache/Nginx web server
- XAMPP (for local development)
- Composer (for dependency management)

## Installation

1. Clone the repository:
```bash
git clone https://github.com/yourusername/verisurex.git
```

2. Import the database schema:
- Create a new MySQL database
- Import the SQL file from `database/schema.sql`

3. Configure the application:
- Copy `config/config.example.php` to `config/config.php`
- Update the database credentials and other settings

4. Set up the web server:
- Point your web server to the project directory
- Ensure the `uploads` and `assets/img/qrcodes` directories are writable

5. Install dependencies:
```bash
composer install
```

## Usage

### Verifying Certificates

1. Visit the verification page
2. Enter the certificate ID or scan the QR code
3. View the certificate details and verification status

### Issuing Certificates (Admin)

1. Log in to the admin panel
2. Navigate to "Issue Certificate"
3. Fill in the certificate details
4. Generate and issue the certificate

## Security Features

- CSRF protection
- XSS prevention
- SQL injection protection
- Rate limiting
- Digital signatures
- Secure session handling

## Directory Structure

```
certificate/
├── admin/           # Admin panel files
├── api/             # API endpoints
├── assets/          # Static assets (CSS, JS, images)
├── config/          # Configuration files
├── database/        # Database schema and migrations
├── includes/        # PHP includes and functions
├── uploads/         # Uploaded files
└── verify/          # Certificate verification interface
```

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## Security

If you discover any security-related issues, please email [your-email] instead of using the issue tracker.
#   V e r i s u r e X  
 