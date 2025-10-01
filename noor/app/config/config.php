<?php
/**
 * ملف التكوين الرئيسي للمشروع
 * يحتوي على إعدادات قاعدة البيانات والثوابت العامة
 */

// إعدادات قاعدة البيانات - SQLite
define('DB_PATH', __DIR__ . '/../../database.sqlite');

// إعدادات الأمان
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 3600); // ساعة واحدة

// إعدادات reCAPTCHA - Using test keys for localhost
// For production, replace with actual reCAPTCHA keys from https://www.google.com/recaptcha/
define('RECAPTCHA_SITE_KEY', '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI');
define('RECAPTCHA_SECRET_KEY', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe');

// إعدادات التطبيق
define('APP_NAME', 'نظام إدارة المستخدمين');
define('APP_VERSION', '1.0.0');

// بدء الجلسة
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// إنشاء اتصال قاعدة البيانات باستخدام PDO (SQLite)
try {
    $pdo = new PDO('sqlite:' . DB_PATH);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    die("خطأ في الاتصال بقاعدة البيانات: " . $e->getMessage());
}

/**
 * دالة لإنشاء رمز CSRF
 */
function generateCSRFToken() {
    if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
        $_SESSION[CSRF_TOKEN_NAME] = bin2hex(random_bytes(32));
    }
    return $_SESSION[CSRF_TOKEN_NAME];
}

/**
 * دالة للتحقق من رمز CSRF
 */
function verifyCSRFToken($token) {
    return isset($_SESSION[CSRF_TOKEN_NAME]) && hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
}

/**
 * دالة لتنظيف المدخلات
 */
function sanitizeInput($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * دالة للتحقق من تسجيل الدخول
 */
function requireLogin() {
    if (!isset($_SESSION['user_id'])) {
        header('Location: login.php');
        exit();
    }
}

/**
 * دالة للتحقق من صلاحيات المدير
 */
function requireAdmin() {
    requireLogin();
    if ($_SESSION['user_role'] !== 'admin') {
        header('Location: dashboard.php');
        exit();
    }
}
?>
