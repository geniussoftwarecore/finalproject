<?php
/**
 * نموذج المستخدم
 * يحتوي على دوال إدارة بيانات المستخدمين
 */

require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../lib/validation.php';

class UserModel {
    private $pdo;
    
    public function __construct($pdo) {
        $this->pdo = $pdo;
    }
    
    /**
     * الحصول على معلومات المستخدم بالمعرف
     */
    public function getUserById($userId) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, username, email, role, last_login, created_at FROM users WHERE id = ?");
            $stmt->execute([$userId]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("خطأ في الحصول على المستخدم: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * الحصول على معلومات المستخدم باسم المستخدم
     */
    public function getUserByUsername($username) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, username, email, role, last_login, created_at FROM users WHERE username = ?");
            $stmt->execute([$username]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("خطأ في الحصول على المستخدم: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * الحصول على معلومات المستخدم بالإيميل
     */
    public function getUserByEmail($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT id, username, email, role, last_login, created_at FROM users WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->fetch();
        } catch (PDOException $e) {
            error_log("خطأ في الحصول على المستخدم: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * التحقق من وجود اسم المستخدم
     */
    public function usernameExists($username) {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE username = ?");
            $stmt->execute([$username]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("خطأ في التحقق من وجود اسم المستخدم: " . $e->getMessage());
            return true; // في حالة الخطأ، نفترض أن الاسم موجود
        }
    }
    
    /**
     * التحقق من وجود الإيميل
     */
    public function emailExists($email) {
        try {
            $stmt = $this->pdo->prepare("SELECT id FROM users WHERE email = ?");
            $stmt->execute([$email]);
            return $stmt->fetch() !== false;
        } catch (PDOException $e) {
            error_log("خطأ في التحقق من وجود الإيميل: " . $e->getMessage());
            return true; // في حالة الخطأ، نفترض أن الإيميل موجود
        }
    }
    
    /**
     * تحديث معلومات المستخدم
     */
    public function updateUser($userId, $data) {
        $allowedFields = ['username', 'email'];
        $updateFields = [];
        $values = [];
        
        foreach ($data as $field => $value) {
            if (in_array($field, $allowedFields)) {
                $updateFields[] = "{$field} = ?";
                $values[] = $value;
            }
        }
        
        if (empty($updateFields)) {
            return false;
        }
        
        $values[] = $userId;
        
        try {
            $sql = "UPDATE users SET " . implode(', ', $updateFields) . ", updated_at = NOW() WHERE id = ?";
            $stmt = $this->pdo->prepare($sql);
            return $stmt->execute($values);
        } catch (PDOException $e) {
            error_log("خطأ في تحديث المستخدم: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * تحديث كلمة المرور
     */
    public function updatePassword($userId, $newPassword) {
        $passwordHash = password_hash($newPassword, PASSWORD_DEFAULT);
        
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET password_hash = ?, updated_at = NOW() WHERE id = ?");
            return $stmt->execute([$passwordHash, $userId]);
        } catch (PDOException $e) {
            error_log("خطأ في تحديث كلمة المرور: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * حذف المستخدم
     */
    public function deleteUser($userId) {
        try {
            $stmt = $this->pdo->prepare("DELETE FROM users WHERE id = ?");
            return $stmt->execute([$userId]);
        } catch (PDOException $e) {
            error_log("خطأ في حذف المستخدم: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * الحصول على جميع المستخدمين (للمدير)
     */
    public function getAllUsers($limit = 50, $offset = 0) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, username, email, role, last_login, created_at 
                FROM users 
                ORDER BY created_at DESC 
                LIMIT ? OFFSET ?
            ");
            $stmt->execute([$limit, $offset]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("خطأ في الحصول على المستخدمين: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * الحصول على عدد المستخدمين
     */
    public function getUsersCount() {
        try {
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM users");
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['count'];
        } catch (PDOException $e) {
            error_log("خطأ في الحصول على عدد المستخدمين: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * البحث عن المستخدمين
     */
    public function searchUsers($query, $limit = 50) {
        try {
            $stmt = $this->pdo->prepare("
                SELECT id, username, email, role, last_login, created_at 
                FROM users 
                WHERE username LIKE ? OR email LIKE ? 
                ORDER BY created_at DESC 
                LIMIT ?
            ");
            $searchTerm = "%{$query}%";
            $stmt->execute([$searchTerm, $searchTerm, $limit]);
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("خطأ في البحث عن المستخدمين: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * الحصول على إحصائيات المستخدمين
     */
    public function getUserStats() {
        try {
            $stats = [];
            
            // إجمالي المستخدمين
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as total FROM users");
            $stmt->execute();
            $stats['total'] = $stmt->fetch()['total'];
            
            // المستخدمين العاديين
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as users FROM users WHERE role = 'user'");
            $stmt->execute();
            $stats['users'] = $stmt->fetch()['users'];
            
            // المديرين
            $stmt = $this->pdo->prepare("SELECT COUNT(*) as admins FROM users WHERE role = 'admin'");
            $stmt->execute();
            $stats['admins'] = $stmt->fetch()['admins'];
            
            // المستخدمين الجدد هذا الشهر
            $stmt = $this->pdo->prepare("
                SELECT COUNT(*) as new_users 
                FROM users 
                WHERE created_at >= DATE_SUB(NOW(), INTERVAL 1 MONTH)
            ");
            $stmt->execute();
            $stats['new_users'] = $stmt->fetch()['new_users'];
            
            return $stats;
        } catch (PDOException $e) {
            error_log("خطأ في الحصول على إحصائيات المستخدمين: " . $e->getMessage());
            return [
                'total' => 0,
                'users' => 0,
                'admins' => 0,
                'new_users' => 0
            ];
        }
    }
    
    /**
     * تحديث دور المستخدم
     */
    public function updateUserRole($userId, $role) {
        if (!in_array($role, ['user', 'admin'])) {
            return false;
        }
        
        try {
            $stmt = $this->pdo->prepare("UPDATE users SET role = ?, updated_at = NOW() WHERE id = ?");
            return $stmt->execute([$role, $userId]);
        } catch (PDOException $e) {
            error_log("خطأ في تحديث دور المستخدم: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * التحقق من صحة بيانات المستخدم قبل التحديث
     */
    public function validateUserData($data, $userId = null) {
        $errors = [];
        
        // التحقق من اسم المستخدم
        if (isset($data['username'])) {
            $usernameValidation = Validation::validateUsername($data['username']);
            if (!$usernameValidation['valid']) {
                $errors['username'] = $usernameValidation['message'];
            } elseif ($this->usernameExists($data['username']) && 
                     (!$userId || $this->getUserById($userId)['username'] !== $data['username'])) {
                $errors['username'] = 'اسم المستخدم مستخدم بالفعل';
            }
        }
        
        // التحقق من الإيميل
        if (isset($data['email'])) {
            $emailValidation = Validation::validateEmail($data['email']);
            if (!$emailValidation['valid']) {
                $errors['email'] = $emailValidation['message'];
            } elseif ($this->emailExists($data['email']) && 
                     (!$userId || $this->getUserById($userId)['email'] !== $data['email'])) {
                $errors['email'] = 'الإيميل مستخدم بالفعل';
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }

    /**
     * الحصول على عدد تسجيلات الدخول في آخر 24 ساعة
     */
    public function getRecentLoginsCount() {
        try {
            $stmt = $this->pdo->prepare("
                SELECT COUNT(DISTINCT user_id) as count 
                FROM users 
                WHERE last_login >= NOW() - INTERVAL '24 hours'
            ");
            $stmt->execute();
            $result = $stmt->fetch();
            return $result['count'];
        } catch (PDOException $e) {
            error_log("خطأ في الحصول على عدد تسجيلات الدخول الأخيرة: " . $e->getMessage());
            return 0;
        }
    }
    
    /**
     * إنشاء حساب مدير جديد
     */
    public function createAdmin($username, $email, $password) {
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        
        try {
            $stmt = $this->pdo->prepare("
                INSERT INTO users (username, email, password_hash, role, created_at) 
                VALUES (?, ?, ?, 'admin', NOW())
            ");
            return $stmt->execute([$username, $email, $passwordHash]);
        } catch (PDOException $e) {
            error_log("خطأ في إنشاء حساب مدير: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * الحصول على المستخدمين مع الترقيم
     */
    public function getUsersWithPagination($page = 1, $perPage = 20, $search = '') {
        $offset = ($page - 1) * $perPage;
        
        try {
            if (!empty($search)) {
                $stmt = $this->pdo->prepare("
                    SELECT id, username, email, role, last_login, created_at 
                    FROM users 
                    WHERE username LIKE ? OR email LIKE ? 
                    ORDER BY created_at DESC 
                    LIMIT ? OFFSET ?
                ");
                $searchTerm = "%{$search}%";
                $stmt->execute([$searchTerm, $searchTerm, $perPage, $offset]);
            } else {
                $stmt = $this->pdo->prepare("
                    SELECT id, username, email, role, last_login, created_at 
                    FROM users 
                    ORDER BY created_at DESC 
                    LIMIT ? OFFSET ?
                ");
                $stmt->execute([$perPage, $offset]);
            }
            
            return $stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("خطأ في الحصول على المستخدمين مع الترقيم: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * الحصول على عدد المستخدمين حسب البحث
     */
    public function getUsersCountBySearch($search = '') {
        try {
            if (!empty($search)) {
                $stmt = $this->pdo->prepare("
                    SELECT COUNT(*) as count 
                    FROM users 
                    WHERE username LIKE ? OR email LIKE ?
                ");
                $searchTerm = "%{$search}%";
                $stmt->execute([$searchTerm, $searchTerm]);
            } else {
                $stmt = $this->pdo->prepare("SELECT COUNT(*) as count FROM users");
                $stmt->execute();
            }
            
            $result = $stmt->fetch();
            return $result['count'];
        } catch (PDOException $e) {
            error_log("خطأ في الحصول على عدد المستخدمين: " . $e->getMessage());
            return 0;
        }
    }
}
?>
