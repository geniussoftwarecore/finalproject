<?php
/**
 * مكتبة التحقق من صحة البيانات
 * تحتوي على دوال التحقق من المدخلات والتحقق من صحة البيانات
 */

class Validation {
    
    /**
     * التحقق من صحة اسم المستخدم
     */
    public static function validateUsername($username) {
        if (empty($username)) {
            return ['valid' => false, 'message' => 'اسم المستخدم مطلوب'];
        }
        
        if (strlen($username) < 3) {
            return ['valid' => false, 'message' => 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل'];
        }
        
        if (strlen($username) > 50) {
            return ['valid' => false, 'message' => 'اسم المستخدم يجب أن يكون أقل من 50 حرف'];
        }
        
        if (!preg_match('/^[a-zA-Z0-9_]+$/', $username)) {
            return ['valid' => false, 'message' => 'اسم المستخدم يجب أن يحتوي على أحرف وأرقام وشرطة سفلية فقط'];
        }
        
        return ['valid' => true, 'message' => 'اسم المستخدم صحيح'];
    }
    
    /**
     * التحقق من صحة الإيميل
     */
    public static function validateEmail($email) {
        if (empty($email)) {
            return ['valid' => false, 'message' => 'الإيميل مطلوب'];
        }
        
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return ['valid' => false, 'message' => 'صيغة الإيميل غير صحيحة'];
        }
        
        // التحقق من النطاق المسموح
        $domain = substr(strrchr($email, "@"), 1);
        if (!in_array($domain, ['gmail.com', 'hotmail.com'])) {
            return ['valid' => false, 'message' => 'يجب استخدام إيميل من gmail.com أو hotmail.com فقط'];
        }
        
        return ['valid' => true, 'message' => 'الإيميل صحيح'];
    }
    
    /**
     * التحقق من قوة كلمة المرور
     */
    public static function validatePassword($password) {
        if (empty($password)) {
            return ['valid' => false, 'message' => 'كلمة المرور مطلوبة'];
        }
        
        if (strlen($password) < 8) {
            return ['valid' => false, 'message' => 'كلمة المرور يجب أن تكون 8 أحرف على الأقل'];
        }
        
        // التحقق من وجود حروف
        if (!preg_match('/[a-zA-Z]/', $password)) {
            return ['valid' => false, 'message' => 'كلمة المرور يجب أن تحتوي على حروف'];
        }
        
        // التحقق من وجود أرقام
        if (!preg_match('/[0-9]/', $password)) {
            return ['valid' => false, 'message' => 'كلمة المرور يجب أن تحتوي على أرقام'];
        }
        
        // التحقق من وجود رموز خاصة
        if (!preg_match('/[@$!%*?&]/', $password)) {
            return ['valid' => false, 'message' => 'كلمة المرور يجب أن تحتوي على رموز خاصة (@$!%*?&)'];
        }
        
        return ['valid' => true, 'message' => 'كلمة المرور قوية'];
    }
    
    /**
     * التحقق من تطابق كلمات المرور
     */
    public static function validatePasswordConfirm($password, $confirmPassword) {
        if ($password !== $confirmPassword) {
            return ['valid' => false, 'message' => 'كلمات المرور غير متطابقة'];
        }
        
        return ['valid' => true, 'message' => 'كلمات المرور متطابقة'];
    }
    
    /**
     * تنظيف وتأمين المدخلات
     */
    public static function sanitizeInput($input) {
        // إزالة المسافات الزائدة
        $input = trim($input);
        
        // إزالة الرموز الخطيرة
        $input = strip_tags($input);
        
        // تحويل الرموز الخاصة إلى HTML entities
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
        
        return $input;
    }
    
    /**
     * التحقق من صحة البيانات الكاملة للتسجيل
     */
    public static function validateRegistration($username, $email, $password, $confirmPassword) {
        $errors = [];
        
        // التحقق من اسم المستخدم
        $usernameValidation = self::validateUsername($username);
        if (!$usernameValidation['valid']) {
            $errors['username'] = $usernameValidation['message'];
        }
        
        // التحقق من الإيميل
        $emailValidation = self::validateEmail($email);
        if (!$emailValidation['valid']) {
            $errors['email'] = $emailValidation['message'];
        }
        
        // التحقق من كلمة المرور
        $passwordValidation = self::validatePassword($password);
        if (!$passwordValidation['valid']) {
            $errors['password'] = $passwordValidation['message'];
        }
        
        // التحقق من تأكيد كلمة المرور
        $confirmValidation = self::validatePasswordConfirm($password, $confirmPassword);
        if (!$confirmValidation['valid']) {
            $errors['confirm_password'] = $confirmValidation['message'];
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * التحقق من صحة بيانات تسجيل الدخول
     */
    public static function validateLogin($username, $password) {
        $errors = [];
        
        if (empty($username)) {
            $errors['username'] = 'اسم المستخدم أو الإيميل مطلوب';
        }
        
        if (empty($password)) {
            $errors['password'] = 'كلمة المرور مطلوبة';
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
    
    /**
     * التحقق من صحة رمز CSRF
     */
    public static function validateCSRFToken($token) {
        if (!isset($_SESSION[CSRF_TOKEN_NAME])) {
            return false;
        }
        
        return hash_equals($_SESSION[CSRF_TOKEN_NAME], $token);
    }
    
    /**
     * التحقق من صحة البيانات العامة
     */
    public static function validateGeneralInput($data, $rules) {
        $errors = [];
        
        foreach ($rules as $field => $rule) {
            $value = isset($data[$field]) ? $data[$field] : '';
            
            // التحقق من الحقول المطلوبة
            if (isset($rule['required']) && $rule['required'] && empty($value)) {
                $errors[$field] = $rule['message'] ?? "الحقل {$field} مطلوب";
                continue;
            }
            
            // التحقق من الطول الأدنى
            if (isset($rule['min_length']) && strlen($value) < $rule['min_length']) {
                $errors[$field] = $rule['message'] ?? "الحقل {$field} يجب أن يكون {$rule['min_length']} أحرف على الأقل";
            }
            
            // التحقق من الطول الأقصى
            if (isset($rule['max_length']) && strlen($value) > $rule['max_length']) {
                $errors[$field] = $rule['message'] ?? "الحقل {$field} يجب أن يكون أقل من {$rule['max_length']} حرف";
            }
            
            // التحقق من النمط
            if (isset($rule['pattern']) && !preg_match($rule['pattern'], $value)) {
                $errors[$field] = $rule['message'] ?? "صيغة الحقل {$field} غير صحيحة";
            }
        }
        
        return [
            'valid' => empty($errors),
            'errors' => $errors
        ];
    }
}
?>
