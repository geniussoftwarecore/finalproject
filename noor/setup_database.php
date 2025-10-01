<?php
/**
 * Database Setup Script - SQLite Version
 * Converts MySQL schema to SQLite
 */

$db_path = __DIR__ . '/database.sqlite';

try {
    $pdo = new PDO('sqlite:' . $db_path);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Create users table
    $pdo->exec("CREATE TABLE IF NOT EXISTS users (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        username TEXT NOT NULL UNIQUE,
        email TEXT NOT NULL UNIQUE,
        password_hash TEXT NOT NULL,
        role TEXT NOT NULL DEFAULT 'user' CHECK(role IN ('user', 'admin')),
        last_login DATETIME DEFAULT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");
    
    // Create remember_tokens table
    $pdo->exec("CREATE TABLE IF NOT EXISTS remember_tokens (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        user_id INTEGER NOT NULL,
        selector TEXT NOT NULL UNIQUE,
        validator_hash TEXT NOT NULL,
        expires_at DATETIME NOT NULL,
        created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
    )");
    
    // Create login_attempts table
    $pdo->exec("CREATE TABLE IF NOT EXISTS login_attempts (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        ip_address TEXT NOT NULL UNIQUE,
        attempts INTEGER NOT NULL DEFAULT 1,
        last_attempt DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
        blocked_until DATETIME DEFAULT NULL
    )");
    
    // Insert default admin user (password: 'password')
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    $stmt->execute();
    $count = $stmt->fetchColumn();
    
    if ($count == 0) {
        $pdo->exec("INSERT INTO users (username, email, password_hash, role) VALUES 
            ('admin', 'admin@gmail.com', '\$2y\$10\$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin')");
        echo "Database created successfully!\n";
        echo "Default admin user created:\n";
        echo "Username: admin\n";
        echo "Password: password\n";
        echo "Please change the password after first login!\n";
    } else {
        echo "Database already exists and admin user is present.\n";
    }
    
} catch (PDOException $e) {
    die("Error setting up database: " . $e->getMessage());
}
?>
