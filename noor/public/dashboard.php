<?php
/**
 * لوحة تحكم المستخدم
 * تعرض معلومات المستخدم والتحكم في حسابه
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/lib/auth.php';
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

// معالجة طلب تحديث الملف الشخصي
$updateSuccess = '';
$updateErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'update_profile') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $updateErrors['general'] = 'رمز الحماية غير صحيح';
    } else {
        $newUsername = sanitizeInput($_POST['username'] ?? '');
        $newEmail = sanitizeInput($_POST['email'] ?? '');
        
        $validation = $userModel->validateUserData([
            'username' => $newUsername,
            'email' => $newEmail
        ], $_SESSION['user_id']);
        
        if ($validation['valid']) {
            if ($userModel->updateUser($_SESSION['user_id'], [
                'username' => $newUsername,
                'email' => $newEmail
            ])) {
                $updateSuccess = 'تم تحديث الملف الشخصي بنجاح';
                $_SESSION['username'] = $newUsername;
                $_SESSION['email'] = $newEmail;
                $user = $userModel->getUserById($_SESSION['user_id']); // تحديث بيانات المستخدم
            } else {
                $updateErrors['general'] = 'فشل في تحديث الملف الشخصي';
            }
        } else {
            $updateErrors = $validation['errors'];
        }
    }
}

// معالجة طلب تغيير كلمة المرور
$passwordSuccess = '';
$passwordErrors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'change_password') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $passwordErrors['general'] = 'رمز الحماية غير صحيح';
    } else {
        $currentPassword = $_POST['current_password'] ?? '';
        $newPassword = $_POST['new_password'] ?? '';
        $confirmPassword = $_POST['confirm_password'] ?? '';
        
        // التحقق من كلمة المرور الحالية
        $auth = new Auth($pdo);
        if (!$auth->login($user['username'], $currentPassword)) {
            $passwordErrors['current_password'] = 'كلمة المرور الحالية غير صحيحة';
        } else {
            // التحقق من كلمة المرور الجديدة
            $passwordValidation = Validation::validatePassword($newPassword);
            if (!$passwordValidation['valid']) {
                $passwordErrors['new_password'] = $passwordValidation['message'];
            } elseif ($newPassword !== $confirmPassword) {
                $passwordErrors['confirm_password'] = 'كلمات المرور غير متطابقة';
            } else {
                if ($userModel->updatePassword($_SESSION['user_id'], $newPassword)) {
                    $passwordSuccess = 'تم تغيير كلمة المرور بنجاح';
                } else {
                    $passwordErrors['general'] = 'فشل في تغيير كلمة المرور';
                }
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
    <title>لوحة التحكم - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <div class="header-content">
                <h1><i class="fas fa-tachometer-alt"></i> لوحة التحكم</h1>
                <div class="user-info">
                    <span>مرحباً، <?php echo htmlspecialchars($user['username']); ?></span>
                    <div class="user-menu">
                        <a href="profile.php" class="btn btn-secondary">
                            <i class="fas fa-user"></i> الملف الشخصي
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
            </div>
        </header>
        
        <div class="dashboard-content">
            <div class="dashboard-grid">
                <!-- معلومات المستخدم -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2><i class="fas fa-user"></i> معلومات المستخدم</h2>
                    </div>
                    <div class="card-content">
                        <div class="user-details">
                            <div class="detail-item">
                                <label>اسم المستخدم:</label>
                                <span><?php echo htmlspecialchars($user['username']); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>البريد الإلكتروني:</label>
                                <span><?php echo htmlspecialchars($user['email']); ?></span>
                            </div>
                            <div class="detail-item">
                                <label>الدور:</label>
                                <span class="role-badge role-<?php echo $user['role']; ?>">
                                    <?php echo $user['role'] === 'admin' ? 'مدير' : 'مستخدم'; ?>
                                </span>
                            </div>
                            <div class="detail-item">
                                <label>تاريخ الإنشاء:</label>
                                <span><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></span>
                            </div>
                            <?php if ($user['last_login']): ?>
                            <div class="detail-item">
                                <label>آخر تسجيل دخول:</label>
                                <span><?php echo date('Y-m-d H:i', strtotime($user['last_login'])); ?></span>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <!-- تحديث الملف الشخصي -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2><i class="fas fa-edit"></i> تحديث الملف الشخصي</h2>
                    </div>
                    <div class="card-content">
                        <?php if (!empty($updateSuccess)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <?php echo $updateSuccess; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($updateErrors['general'])): ?>
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo $updateErrors['general']; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="form">
                            <input type="hidden" name="action" value="update_profile">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="form-group">
                                <label for="username">اسم المستخدم:</label>
                                <input type="text" id="username" name="username" 
                                       value="<?php echo htmlspecialchars($user['username']); ?>" required>
                                <?php if (!empty($updateErrors['username'])): ?>
                                    <span class="error-message"><?php echo $updateErrors['username']; ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">البريد الإلكتروني:</label>
                                <input type="email" id="email" name="email" 
                                       value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                <?php if (!empty($updateErrors['email'])): ?>
                                    <span class="error-message"><?php echo $updateErrors['email']; ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save"></i> تحديث الملف الشخصي
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- تغيير كلمة المرور -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2><i class="fas fa-lock"></i> تغيير كلمة المرور</h2>
                    </div>
                    <div class="card-content">
                        <?php if (!empty($passwordSuccess)): ?>
                            <div class="alert alert-success">
                                <i class="fas fa-check-circle"></i>
                                <?php echo $passwordSuccess; ?>
                            </div>
                        <?php endif; ?>
                        
                        <?php if (!empty($passwordErrors['general'])): ?>
                            <div class="alert alert-error">
                                <i class="fas fa-exclamation-circle"></i>
                                <?php echo $passwordErrors['general']; ?>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" class="form">
                            <input type="hidden" name="action" value="change_password">
                            <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                            
                            <div class="form-group">
                                <label for="current_password">كلمة المرور الحالية:</label>
                                <input type="password" id="current_password" name="current_password" required>
                                <?php if (!empty($passwordErrors['current_password'])): ?>
                                    <span class="error-message"><?php echo $passwordErrors['current_password']; ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="new_password">كلمة المرور الجديدة:</label>
                                <input type="password" id="new_password" name="new_password" required>
                                <small class="form-help">يجب أن تحتوي على حروف وأرقام ورموز خاصة (8 أحرف على الأقل)</small>
                                <?php if (!empty($passwordErrors['new_password'])): ?>
                                    <span class="error-message"><?php echo $passwordErrors['new_password']; ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <div class="form-group">
                                <label for="confirm_password">تأكيد كلمة المرور الجديدة:</label>
                                <input type="password" id="confirm_password" name="confirm_password" required>
                                <?php if (!empty($passwordErrors['confirm_password'])): ?>
                                    <span class="error-message"><?php echo $passwordErrors['confirm_password']; ?></span>
                                <?php endif; ?>
                            </div>
                            
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key"></i> تغيير كلمة المرور
                            </button>
                        </form>
                    </div>
                </div>
                
                <!-- إحصائيات سريعة -->
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2><i class="fas fa-chart-bar"></i> إحصائيات</h2>
                    </div>
                    <div class="card-content">
                        <div class="stats-grid">
                            <div class="stat-item">
                                <div class="stat-value"><?php echo date('Y-m-d', strtotime($user['created_at'])); ?></div>
                                <div class="stat-label">تاريخ الانضمام</div>
                            </div>
                            <div class="stat-item">
                                <div class="stat-value"><?php echo $user['role'] === 'admin' ? 'مدير' : 'مستخدم'; ?></div>
                                <div class="stat-label">نوع الحساب</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <?php if ($user['role'] === 'admin'): ?>
            <div class="admin-section">
                <div class="dashboard-card">
                    <div class="card-header">
                        <h2><i class="fas fa-crown"></i> أدوات الإدارة</h2>
                    </div>
                    <div class="card-content">
                        <p>بصفتك مدير، يمكنك الوصول إلى لوحة الإدارة لإدارة المستخدمين.</p>
                        <a href="admin.php" class="btn btn-warning">
                            <i class="fas fa-cog"></i> لوحة الإدارة
                        </a>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>
