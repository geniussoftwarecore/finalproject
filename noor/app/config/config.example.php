<?php
/**
 * ملف التكوين الرئيسي للمشروع - نسخة النموذج
 * انسخ هذا الملف إلى config.php وقم بتعديل الإعدادات حسب بيئتك
 */

// إعدادات قاعدة البيانات - MySQL (XAMPP)
// للتثبيت المحلي على XAMPP، استخدم القيم التالية:
define('DB_HOST', 'localhost');
define('DB_NAME', 'project_db');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_PORT', '3306');

// إعدادات الأمان
define('CSRF_TOKEN_NAME', 'csrf_token');
define('SESSION_TIMEOUT', 3600); // ساعة واحدة

// إعدادات reCAPTCHA
// احصل على مفاتيح reCAPTCHA من: https://www.google.com/recaptcha/
// المفاتيح التالية مخصصة للاختبار المحلي فقط
define('RECAPTCHA_SITE_KEY', '6LeIxAcTAAAAAJcZVRqyHh71UMIEGNQ_MXjiZKhI');
define('RECAPTCHA_SECRET_KEY', '6LeIxAcTAAAAAGG-vFI1TnRWxMZNFuojJ4WifJWe');

// إعدادات التطبيق
define('APP_NAME', 'نظام إدارة المستخدمين');
define('APP_VERSION', '1.0.0');

// بدء الجلسة
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

// إنشاء اتصال قاعدة البيانات باستخدام PDO (MySQL)
try {
    $dsn = "mysql:host=" . DB_HOST . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ];
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
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
