<?php
/**
 * الصفحة الرئيسية - إنشاء حساب جديد
 * هذه الصفحة مخصصة لإنشاء حسابات جديدة فقط
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/validation.php';
require_once __DIR__ . '/../app/lib/captcha.php';
require_once __DIR__ . '/../app/models/UserModel.php';

// التحقق من تسجيل الدخول - إذا كان المستخدم مسجل دخول، يتم توجيهه للوحة التحكم
if (isset($_SESSION['user_id'])) {
    header('Location: dashboard.php');
    exit();
}

// التحقق من رمز التذكر
$auth = new Auth($pdo);
if ($auth->checkRememberToken()) {
    header('Location: dashboard.php');
    exit();
}

$errors = [];
$success = '';

// معالجة طلب التسجيل
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من رمز CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'رمز الحماية غير صحيح';
    } else {
        // تنظيف المدخلات
        $username = sanitizeInput($_POST['username'] ?? '');
        $email = sanitizeInput($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // التحقق من صحة البيانات
        $validation = Validation::validateRegistration($username, $email, $password, $confirmPassword);
        
        if ($validation['valid']) {
            // التحقق من reCAPTCHA
            $captchaValidation = Captcha::validateCaptcha();
            if ($captchaValidation['valid']) {
                // محاولة تسجيل المستخدم
                if ($auth->register($username, $email, $password)) {
                    $success = 'تم إنشاء الحساب بنجاح! يمكنك الآن تسجيل الدخول.';
                    // مسح البيانات من النموذج
                    $username = $email = '';
                } else {
                    $errors['general'] = 'فشل في إنشاء الحساب. يرجى المحاولة مرة أخرى.';
                }
            } else {
                $errors['captcha'] = $captchaValidation['message'];
            }
        } else {
            $errors = $validation['errors'];
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إنشاء حساب جديد - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="auth-container">
            <div class="auth-header">
                <h1><i class="fas fa-user-plus"></i> إنشاء حساب جديد</h1>
                <p>انضم إلينا وأنشئ حسابك الجديد</p>
            </div>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                    <br>
                    <a href="login.php" class="btn btn-primary">تسجيل الدخول</a>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $errors['general']; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form" id="registerForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> اسم المستخدم
                    </label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo htmlspecialchars($username ?? ''); ?>" 
                           required autocomplete="username">
                    <?php if (!empty($errors['username'])): ?>
                        <span class="error-message"><?php echo $errors['username']; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> البريد الإلكتروني
                    </label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($email ?? ''); ?>" 
                           required autocomplete="email">
                    <small class="form-help">يجب أن يكون من gmail.com أو hotmail.com فقط</small>
                    <?php if (!empty($errors['email'])): ?>
                        <span class="error-message"><?php echo $errors['email']; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> كلمة المرور
                    </label>
                    <input type="password" id="password" name="password" 
                           required autocomplete="new-password">
                    <small class="form-help">يجب أن تحتوي على حروف وأرقام ورموز خاصة (8 أحرف على الأقل)</small>
                    <?php if (!empty($errors['password'])): ?>
                        <span class="error-message"><?php echo $errors['password']; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-lock"></i> تأكيد كلمة المرور
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           required autocomplete="new-password">
                    <?php if (!empty($errors['confirm_password'])): ?>
                        <span class="error-message"><?php echo $errors['confirm_password']; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="remember" value="1">
                        تذكرني
                    </label>
                </div>
                
                <div class="form-group">
                    <label>التحقق من الأمان</label>
                    <?php echo Captcha::renderCaptcha(); ?>
                    <?php if (!empty($errors['captcha'])): ?>
                        <span class="error-message"><?php echo $errors['captcha']; ?></span>
                    <?php endif; ?>
                </div>
                
                <button type="submit" class="btn btn-primary btn-full">
                    <i class="fas fa-user-plus"></i> إنشاء الحساب
                </button>
            </form>
            
            <div class="auth-footer">
                <p>هل لديك حساب بالفعل؟ <a href="login.php">تسجيل الدخول</a></p>
            </div>
        </div>
    </div>
    
    <?php echo Captcha::renderCaptchaScript(); ?>
    <script src="assets/js/main.js"></script>
    <script>
        // إضافة التنقل بالأسهم بين الحقول
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="password"]');
            
            inputs.forEach((input, index) => {
                input.addEventListener('keydown', function(e) {
                    if (e.key === 'ArrowDown' || e.key === 'Enter') {
                        e.preventDefault();
                        if (index < inputs.length - 1) {
                            inputs[index + 1].focus();
                        }
                    } else if (e.key === 'ArrowUp') {
                        e.preventDefault();
                        if (index > 0) {
                            inputs[index - 1].focus();
                        }
                    }
                });
            });
        });
    </script>
</body>
</html>
