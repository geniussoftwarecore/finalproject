<?php
/**
 * صفحة الإعدادات
 * تسمح للمستخدمين بتحديث معلومات حسابهم
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/lib/auth.php';
require_once __DIR__ . '/../app/lib/validation.php';
require_once __DIR__ . '/../app/models/UserModel.php';

// التحقق من تسجيل الدخول
requireLogin();

$userModel = new UserModel($pdo);
$user = $userModel->getUserById($_SESSION['user_id']);

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

$errors = [];
$success = '';

// معالجة طلب تحديث الإعدادات
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من رمز CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'رمز الحماية غير صحيح';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newEmail = sanitizeInput($_POST['email'] ?? '');
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // التحقق من كلمة المرور الحالية
        $auth = new Auth($pdo);
        $stmt = $pdo->prepare("SELECT password_hash FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $userPasswordData = $stmt->fetch();
        
        if (!password_verify($currentPassword, $userPasswordData['password_hash'])) {
            $errors['current_password'] = 'كلمة المرور الحالية غير صحيحة';
        } else {
            $updateSuccess = false;
            
            // التحقق من البريد الإلكتروني الجديد
            if (!empty($newEmail) && $newEmail !== $user['email']) {
                $emailValidation = Validation::validateEmail($newEmail);
                if (!$emailValidation['valid']) {
                    $errors['email'] = $emailValidation['message'];
                } else {
                    // التحقق من عدم تكرار الإيميل
                    if ($userModel->emailExists($newEmail)) {
                        $errors['email'] = 'البريد الإلكتروني مستخدم بالفعل';
                    } else {
                        // تحديث الإيميل
                        if ($userModel->updateUser($_SESSION['user_id'], ['email' => $newEmail])) {
                            $_SESSION['email'] = $newEmail;
                            $user['email'] = $newEmail;
                            $updateSuccess = true;
                        } else {
                            $errors['email'] = 'فشل في تحديث البريد الإلكتروني';
                        }
                    }
                }
            }
            
            // التحقق من كلمة المرور الجديدة
            if (!empty($newPassword)) {
                $passwordValidation = Validation::validatePassword($newPassword);
                if (!$passwordValidation['valid']) {
                    $errors['new_password'] = $passwordValidation['message'];
                } elseif ($newPassword !== $confirmPassword) {
                    $errors['confirm_password'] = 'كلمات المرور غير متطابقة';
                } else {
                    // تحديث كلمة المرور
                    if ($userModel->updatePassword($_SESSION['user_id'], $newPassword)) {
                        $updateSuccess = true;
                    } else {
                        $errors['new_password'] = 'فشل في تحديث كلمة المرور';
                    }
                }
            }
            
            // عرض رسالة النجاح إذا تم التحديث بنجاح
            if ($updateSuccess && empty($errors)) {
                $success = 'تم تحديث الإعدادات بنجاح';
            }
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الإعدادات - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <div class="header-content">
                <h1><i class="fas fa-cog"></i> الإعدادات</h1>
                <div class="user-menu">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-right"></i> العودة للوحة التحكم
                    </a>
                    <a href="activity.php" class="btn btn-secondary">
                        <i class="fas fa-history"></i> سجل النشاط
                    </a>
                    <a href="contact.php" class="btn btn-secondary">
                        <i class="fas fa-headset"></i> الدعم
                    </a>
                    <a href="logout.php" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                    </a>
                </div>
            </div>
        </header>
        
        <div class="auth-container">
            <div class="auth-header">
                <h1><i class="fas fa-user-cog"></i> تحديث معلومات الحساب</h1>
                <p>قم بتحديث بريدك الإلكتروني أو كلمة المرور</p>
            </div>
            
            <?php if (!empty($success)): ?>
                <div class="alert alert-success">
                    <i class="fas fa-check-circle"></i>
                    <?php echo $success; ?>
                </div>
            <?php endif; ?>
            
            <?php if (!empty($errors['general'])): ?>
                <div class="alert alert-error">
                    <i class="fas fa-exclamation-circle"></i>
                    <?php echo $errors['general']; ?>
                </div>
            <?php endif; ?>
            
            <form method="POST" class="auth-form" id="settingsForm">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="form-group">
                    <label for="username">
                        <i class="fas fa-user"></i> اسم المستخدم
                    </label>
                    <input type="text" id="username" name="username" 
                           value="<?php echo htmlspecialchars($user['username']); ?>" 
                           readonly disabled
                           style="background-color: #f0f0f0; cursor: not-allowed;">
                    <span class="form-help">اسم المستخدم لا يمكن تغييره</span>
                </div>
                
                <div class="form-group">
                    <label for="current_password">
                        <i class="fas fa-lock"></i> كلمة المرور الحالية *
                    </label>
                    <input type="password" id="current_password" name="current_password" 
                           required autocomplete="current-password">
                    <span class="form-help">أدخل كلمة المرور الحالية للتحقق من هويتك</span>
                    <?php if (!empty($errors['current_password'])): ?>
                        <span class="error-message"><?php echo $errors['current_password']; ?></span>
                    <?php endif; ?>
                </div>
                
                <hr style="margin: 30px 0; border: none; border-top: 2px solid #e9ecef;">
                
                <div class="form-group">
                    <label for="email">
                        <i class="fas fa-envelope"></i> البريد الإلكتروني الجديد
                    </label>
                    <input type="email" id="email" name="email" 
                           value="<?php echo htmlspecialchars($user['email']); ?>" 
                           autocomplete="email">
                    <span class="form-help">يجب أن يكون من gmail.com أو hotmail.com</span>
                    <?php if (!empty($errors['email'])): ?>
                        <span class="error-message"><?php echo $errors['email']; ?></span>
                    <?php endif; ?>
                </div>
                
                <hr style="margin: 30px 0; border: none; border-top: 2px solid #e9ecef;">
                
                <div class="form-group">
                    <label for="new_password">
                        <i class="fas fa-key"></i> كلمة المرور الجديدة
                    </label>
                    <input type="password" id="new_password" name="new_password" 
                           autocomplete="new-password">
                    <span class="form-help">اتركه فارغاً إذا كنت لا تريد تغيير كلمة المرور</span>
                    <?php if (!empty($errors['new_password'])): ?>
                        <span class="error-message"><?php echo $errors['new_password']; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <label for="confirm_password">
                        <i class="fas fa-key"></i> تأكيد كلمة المرور الجديدة
                    </label>
                    <input type="password" id="confirm_password" name="confirm_password" 
                           autocomplete="new-password">
                    <span class="form-help">أعد إدخال كلمة المرور الجديدة للتأكيد</span>
                    <?php if (!empty($errors['confirm_password'])): ?>
                        <span class="error-message"><?php echo $errors['confirm_password']; ?></span>
                    <?php endif; ?>
                </div>
                
                <div class="form-group">
                    <button type="submit" class="btn btn-primary btn-full">
                        <i class="fas fa-save"></i> حفظ التغييرات
                    </button>
                </div>
                
                <div class="form-footer">
                    <p>
                        <i class="fas fa-info-circle"></i>
                        <strong>ملاحظة:</strong> كلمة المرور يجب أن تحتوي على 8 أحرف على الأقل، مع أحرف وأرقام ورموز خاصة (@$!%*?&)
                    </p>
                </div>
            </form>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script>
        document.getElementById('settingsForm').addEventListener('submit', function(e) {
            const currentPassword = document.getElementById('current_password').value;
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (!currentPassword) {
                e.preventDefault();
                alert('يرجى إدخال كلمة المرور الحالية');
                return false;
            }
            
            if (newPassword && newPassword !== confirmPassword) {
                e.preventDefault();
                alert('كلمات المرور الجديدة غير متطابقة');
                return false;
            }
            
            if (newPassword && newPassword.length < 8) {
                e.preventDefault();
                alert('كلمة المرور يجب أن تكون 8 أحرف على الأقل');
                return false;
            }
        });
    </script>
</body>
</html>
