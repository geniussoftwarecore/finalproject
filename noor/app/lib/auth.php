<?php
/**
 * مكتبة المصادقة والحماية
 * تحتوي على دوال تسجيل الدخول والخروج وإدارة الجلسات
 */

require_once __DIR__ . '/../config/config.php';

class Auth {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * تسجيل مستخدم جديد
     */
    public function register($username, $email, $password) {
        // التحقق من صحة البيانات
        if (!$this->validateRegistration($username, $email, $password)) {
            return false;
        }
        
        // التحقق من عدم تكرار اسم المستخدم أو الإيميل
        if ($this->userExists($username, $email)) {
            return false;
        }
        
        // تشفير كلمة المرور
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $this->pdo->prepare("INSERT INTO users (username, email, password_hash) VALUES (?, ?, ?)");
            return $stmt->execute([$username, $email, $passwordHash]);
        } catch (PDOException $e) {
            error_log("خطأ في تسجيل المستخدم: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * تسجيل الدخول
     */
    public function login($username, $password, $remember = false) {
        // التحقق من محاولات تسجيل الدخول
        if ($this->isLoginBlocked()) {
            return false;
        }
        
        try {
            $stmt = $this->pdo->prepare("SELECT id, username, email, password_hash, role FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            
            if ($user && password_verify($password, $user['password_hash'])) {
                // تسجيل الدخول ناجح
                $this->setUserSession($user);
                $this->updateLastLogin($user['id']);
                $this->clearLoginAttempts();
                
                // إنشاء رمز التذكر إذا طُلب
                if ($remember) {
                    $this->createRememberToken($user['id']);
                }
                
                return true;
            } else {
                // تسجيل الدخول فاشل
                $this->recordFailedLogin();
                return false;
            }
        } catch (PDOException $e) {
            error_log("خطأ في تسجيل الدخول: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * تسجيل الخروج
     */
    public function logout() {
        // حذف رمز التذكر إذا كان موجوداً
        if (isset($_COOKIE['remember_token'])) {
            $this->deleteRememberToken($_COOKIE['remember_token']);
            setcookie('remember_token', '', time() - 3600, '/');
        }
        
        // تدمير الجلسة
        session_destroy();
        return true;
    }
    
    /**
     * التحقق من تسجيل الدخول عبر رمز التذكر
     */
    public function checkRememberToken() {
        if (!isset($_COOKIE['remember_token'])) {
            return false;
        }
        
        $token = $_COOKIE['remember_token'];
        $selector = substr($token, 0, 32);
        $validator = substr($token, 32);
        
        try {
            $stmt = $this->pdo->prepare("
                SELECT u.id, u.username, u.email, u.role, rt.validator_hash 
                FROM users u 
                JOIN remember_tokens rt ON u.id = rt.user_id 
                WHERE rt.selector = ? AND rt.expires_at > NOW()
            ");
            $stmt->execute([$selector]);
            $result = $stmt->fetch();
            
            if ($result && hash_equals($result['validator_hash'], hash('sha256', $validator))) {
                $this->setUserSession($result);
                $this->updateLastLogin($result['id']);
                return true;
            } else {
                // حذف الرمز غير الصالح
                $this->deleteRememberToken($token);
                return false;
            }
        } catch (PDOException $e) {
            error_log("خطأ في التحقق من رمز التذكر: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * التحقق من صحة بيانات التسجيل
     */
    private function validateRegistration($username, $email, $password) {
        // التحقق من اسم المستخدم
        if (strlen($username) < 3 || strlen($username) > 50) {
            return false;
        }
        
        // التحقق من الإيميل
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return false;
        }
        
        // التحقق من نطاق الإيميل (gmail.com أو hotmail.com فقط)
        $domain = substr(strrchr($email, "@"), 1);
        if (!in_array($domain, ['gmail.com', 'hotmail.com'])) {
            return false;
        }
        
        // التحقق من قوة كلمة المرور
        if (strlen($password) < 8) {
            return false;
        }
        
        // التحقق من وجود حروف وأرقام ورموز
        if (!preg_match('/^(?=.*[a-zA-Z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]/', $password)) {
            return false;
        }
        
        return true;
    }
    
    /**
     * التحقق من وجود المستخدم
     */
    private function userExists($username, $email) {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ?");
            $stmt->execute([$username, $email]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("خطأ في التحقق من وجود المستخدم: " . $e->getMessage());
            return true; // في حالة الخطأ، نفترض أن المستخدم موجود
        }
    }
    
    /**
     * إعداد جلسة المستخدم
     */
    private function setUserSession($user) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['email'] = $user['email'];
        $_SESSION['user_role'] = $user['role'];
        $_SESSION['login_time'] = time();
    }
    
    /**
     * تحديث وقت آخر تسجيل دخول
     */
    private function updateLastLogin($userId) {
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?");
            $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("خطأ في تحديث وقت آخر تسجيل دخول: " . $e->getMessage());
        }
    }
    
    /**
     * إنشاء رمز التذكر
     */
    private function createRememberToken($userId) {
        $selector = bin2hex(random_bytes(16));
        $validator = bin2hex(random_bytes(32));
        $validatorHash = hash('sha256', $validator);
        $expires = date('Y-m-d H:i:s', time() + (30 * 24 * 60 * 60)); // 30 يوم
        
        try {
            $stmt = $this->pdo->prepare("INSERT INTO remember_tokens (user_id, selector, validator_hash, expires_at) VALUES (?, ?, ?, ?)");
            $stmt->execute([$userId, $selector, $validatorHash, $expires]);
            
            // حفظ الرمز في الكوكي
            setcookie('remember_token', $selector . $validator, time() + (30 * 24 * 60 * 60), '/', '', false, true);
        } catch (PDOException $e) {
            error_log("خطأ في إنشاء رمز التذكر: " . $e->getMessage());
        }
    }
    
    /**
     * حذف رمز التذكر
     */
    private function deleteRememberToken($token) {
        $selector = substr($token, 0, 32);
        try {
            $stmt = $this->pdo->prepare("DELETE FROM remember_tokens WHERE selector = ?");
            $stmt->execute([$selector]);
        } catch (PDOException $e) {
            error_log("خطأ في حذف رمز التذكر: " . $e->getMessage());
        }
    }
    
    /**
     * تسجيل محاولة تسجيل دخول فاشلة
     */
    private function recordFailedLogin() {
        $ip = $_SERVER['REMOTE_ADDR'];
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO login_attempts (ip_address, attempts, last_attempt) 
                VALUES (?, 1, NOW()) 
                ON DUPLICATE KEY UPDATE 
                attempts = attempts + 1, 
                last_attempt = NOW(),
                blocked_until = CASE 
                    WHEN attempts >= 4 THEN DATE_ADD(NOW(), INTERVAL 15 MINUTE)
                    ELSE blocked_until
                END
            ");
            $stmt->execute([$ip]);
        } catch (PDOException $e) {
            error_log("خطأ في تسجيل محاولة تسجيل دخول فاشلة: " . $e->getMessage());
        }
    }
    
    /**
     * التحقق من حظر تسجيل الدخول
     */
    private function isLoginBlocked() {
        $ip = $_SERVER['REMOTE_ADDR'];
        try {
            $stmt = $this->pdo->prepare("SELECT blocked_until FROM login_attempts WHERE ip_address = ? AND blocked_until > NOW()");
            $stmt->execute([$ip]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("خطأ في التحقق من حظر تسجيل الدخول: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * مسح محاولات تسجيل الدخول
     */
    private function clearLoginAttempts() {
        $ip = $_SERVER['REMOTE_ADDR'];
        try {
            $stmt = $this->pdo->prepare("DELETE FROM login_attempts WHERE ip_address = ?");
            $stmt->execute([$ip]);
        } catch (PDOException $e) {
            error_log("خطأ في مسح محاولات تسجيل الدخول: " . $e->getMessage());
        }
    }
}
?>
