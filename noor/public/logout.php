<?php
/**
 * صفحة تسجيل الخروج
 * تقوم بتسجيل خروج المستخدم وتنظيف الجلسة
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/lib/auth.php';

// إنشاء كائن المصادقة
$auth = new Auth($pdo);

// تسجيل الخروج
$auth->logout();

// توجيه المستخدم لصفحة تسجيل الدخول
header('Location: login.php?message=logged_out');
exit();
?>
