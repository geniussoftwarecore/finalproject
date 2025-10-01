<?php
/**
 * صفحة تسجيل الدخول
 * تحتوي على نموذج تسجيل الدخول مع reCAPTCHA ووظيفة "تذكرني"
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/validation.php';
require_once __DIR__ . '/../app/lib/captcha.php';

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

// معالجة طلب تسجيل الدخول
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من رمز CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'رمز الحماية غير صحيح';
    } else {
        // تنظيف المدخلات
        $username = sanitizeInput($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);
        
        // التحقق من صحة البيانات
        $validation = Validation::validateLogin($username, $password);
        
        if ($validation['valid']) {
            // التحقق من reCAPTCHA
            $captchaValidation = Captcha::validateCaptcha();
            if ($captchaValidation['valid']) {
                // محاولة تسجيل الدخول
                if ($auth->login($username, $password, $remember)) {
                    header('Location: dashboard.php');
                    exit();
                } else {
                    $errors['general'] = 'اسم المستخدم أو كلمة المرور غير صحيحة';
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
    <title>تسجيل الدخول - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="auth-container">
            <div class="auth-header">
                <h1><i class="fas fa-sign-in-alt"></i> تسجيل الدخول</h1>
                <p>مرحباً بك مرة أخرى</p>
            </div>
            
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $errors['general']; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form" id="loginForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> اسم المستخدم أو الإيميل
                    </label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo htmlspecialchars($username ?? ''); ?>" 
                           required autocomplete="username">
                    <?php if (!empty($errors['username'])): ?>
                        <span class="error-message"><?php echo $errors['username']; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="password">
                        <i class="fas fa-lock"></i> كلمة المرور
                    </label>
                    <input type="password" id="password" name="password" 
                           required autocomplete="current-password">
                    <?php if (!empty($errors['password'])): ?>
                        <span class="error-message"><?php echo $errors['password']; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label>
                        <input type="checkbox" name="remember" value="1">
                        تذكرني لمدة 30 يوم
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
                    <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
                </button>
            </form>
            
            <div class="auth-footer">
                <p>ليس لديك حساب؟ <a href="index.php">إنشاء حساب جديد</a></p>
            </div>
        </div>
    </div>
    
    <?php echo Captcha::renderCaptchaScript(); ?>
    <script src="assets/js/main.js"></script>
    <script>
        // إضافة التنقل بالأسهم بين الحقول
        document.addEventListener('DOMContentLoaded', function() {
            const inputs = document.querySelectorAll('input[type="text"], input[type="password"]');
            
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
