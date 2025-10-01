<?php
/**
 * مكتبة سجل النشاط
 * تحتوي على دوال تسجيل وعرض نشاط المستخدمين
 */

require_once __DIR__ . '/../config/config.php';

class ActivityLog {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * تسجيل محاولة تسجيل الدخول في السجل
     */
    public function recordLoginHistory($userId, $username, $status) {
        $ip = $_SERVER['REMOTE_ADDR'];
        $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO login_history (user_id, username, ip_address, user_agent, status, login_time) 
                VALUES (?, ?, ?, ?, ?, NOW())
            ");
            $stmt->execute([$userId, $username, $ip, $userAgent, $status]);
        } catch (PDOException $e) {
            error_log("خطأ في تسجيل محاولة تسجيل الدخول: " . $e->getMessage());
        }
    }
    
    /**
     * الحصول على سجل تسجيل الدخول للمستخدم
     */
    public function getUserLoginHistory($userId, $limit = 20) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, ip_address, user_agent, status, login_time 
                FROM login_history 
                WHERE user_id = ? 
                ORDER BY login_time DESC 
                LIMIT ?
            ");
            $stmt->execute([$userId, $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("خطأ في الحصول على سجل تسجيل الدخول: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * مسح سجل تسجيل الدخول للمستخدم
     */
    public function clearUserLoginHistory($userId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM login_history WHERE user_id = ?");
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("خطأ في مسح سجل تسجيل الدخول: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * مسح جميع سجلات تسجيل الدخول (مدير فقط)
     */
    public function clearAllLoginHistory() {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM login_history");
            return $stmt->execute();
        } catch (PDOException $e) {
            error_log("خطأ في مسح جميع سجلات تسجيل الدخول: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * تحليل معلومات المتصفح من User Agent
     */
    public function parseUserAgent($userAgent) {
        if (empty($userAgent)) {
            return 'غير معروف';
        }
        
        // اكتشاف المتصفح
        $browser = 'متصفح غير معروف';
        if (preg_match('/MSIE|Trident/i', $userAgent)) {
            $browser = 'Internet Explorer';
        } elseif (preg_match('/Edge/i', $userAgent)) {
            $browser = 'Microsoft Edge';
        } elseif (preg_match('/Chrome/i', $userAgent)) {
            $browser = 'Google Chrome';
        } elseif (preg_match('/Safari/i', $userAgent)) {
            $browser = 'Safari';
        } elseif (preg_match('/Firefox/i', $userAgent)) {
            $browser = 'Mozilla Firefox';
        } elseif (preg_match('/Opera|OPR/i', $userAgent)) {
            $browser = 'Opera';
        }
        
        // اكتشاف نظام التشغيل
        $os = 'نظام تشغيل غير معروف';
        if (preg_match('/Windows NT 10/i', $userAgent)) {
            $os = 'Windows 10/11';
        } elseif (preg_match('/Windows NT 6.3/i', $userAgent)) {
            $os = 'Windows 8.1';
        } elseif (preg_match('/Windows NT 6.2/i', $userAgent)) {
            $os = 'Windows 8';
        } elseif (preg_match('/Windows NT 6.1/i', $userAgent)) {
            $os = 'Windows 7';
        } elseif (preg_match('/Windows/i', $userAgent)) {
            $os = 'Windows';
        } elseif (preg_match('/Mac OS X/i', $userAgent)) {
            $os = 'Mac OS X';
        } elseif (preg_match('/Linux/i', $userAgent)) {
            $os = 'Linux';
        } elseif (preg_match('/Android/i', $userAgent)) {
            $os = 'Android';
        } elseif (preg_match('/iOS|iPhone|iPad/i', $userAgent)) {
            $os = 'iOS';
        }
        
        return $browser . ' على ' . $os;
    }
    
    /**
     * تنسيق التاريخ بالعربية
     */
    public function formatArabicDate($dateTime) {
        $timestamp = strtotime($dateTime);
        $arabicMonths = [
            1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
            5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
            9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
        ];
        
        $day = date('d', $timestamp);
        $month = $arabicMonths[(int)date('m', $timestamp)];
        $year = date('Y', $timestamp);
        $time = date('H:i', $timestamp);
        
        return "$day $month $year - $time";
    }
}
?>
