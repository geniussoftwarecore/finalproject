# نظام إدارة المستخدمين (User Management System)

## Project Overview
This is a complete PHP-based user management system with an Arabic interface. The system provides user registration, login, dashboard, and admin panel functionality.

## Technology Stack
- **Backend**: PHP 8.2
- **Database**: PostgreSQL (converted from MySQL for Replit compatibility)
- **Frontend**: HTML, CSS, JavaScript
- **Security**: CSRF protection, password hashing, reCAPTCHA

## Project Structure
```
noor/
├── app/
│   ├── config/config.php      # Main configuration file (using PostgreSQL)
│   ├── lib/                   # Libraries (auth, validation, captcha)
│   └── models/                # Data models
├── public/                    # Web root directory
│   ├── assets/               # CSS and JavaScript files
│   ├── index.php             # Registration page
│   ├── login.php             # Login page
│   ├── dashboard.php         # User dashboard
│   └── profile.php           # User profile
└── admin/                     # Admin panel
```

## Recent Changes (October 1, 2025)
- ✅ Converted MySQL database to PostgreSQL for Replit environment
- ✅ Configured PHP 8.2 runtime with PDO PostgreSQL support
- ✅ Set up workflow to run PHP server on port 5000 (0.0.0.0)
- ✅ Added test reCAPTCHA keys for development
- ✅ Created database schema with users, remember_tokens, and login_attempts tables
- ✅ Seeded admin user account

## Configuration
- **Database**: PostgreSQL (Replit managed)
  - Connection configured via environment variables (PGHOST, PGDATABASE, PGUSER, PGPASSWORD, PGPORT)
  - Tables: users, remember_tokens, login_attempts
- **Server**: PHP built-in server on 0.0.0.0:5000
- **reCAPTCHA**: Using Google test keys (should be replaced in production)
  - Site Key: 6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI
  - Secret Key: 6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe

## Default Credentials
- **Username**: admin
- **Password**: password
- **Email**: admin@gmail.com
- **Role**: admin

⚠️ **Important**: Change the default admin password after first login!

## Features
- ✅ User registration with validation
- ✅ Login with "Remember Me" functionality
- ✅ User dashboard
- ✅ Admin panel
- ✅ CSRF protection
- ✅ Password hashing (bcrypt)
- ✅ reCAPTCHA integration
- ✅ Arabic language support
- ✅ Responsive design

## Running the Application
The application is configured to run automatically via Replit workflows:
- Server runs on port 5000
- Access at: http://0.0.0.0:5000

## User Preferences
None specified yet.

## Important Notes for Production
1. Replace reCAPTCHA test keys with actual production keys from https://www.google.com/recaptcha/
2. Change default admin password immediately
3. Review and update security settings
4. Enable HTTPS for secure communication

## Database Schema
The PostgreSQL database includes:
- **users**: User accounts with username, email, password_hash, role, timestamps
- **remember_tokens**: Persistent login tokens for "Remember Me" functionality
- **login_attempts**: Login attempt tracking for security
