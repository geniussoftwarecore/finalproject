<?php
/**
 * صفحة الملف الشخصي
 * تعرض معلومات المستخدم بالتفصيل
 */

require_once __DIR__ . '/../app/config/config.php';
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
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الملف الشخصي - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <div class="header-content">
                <h1><i class="fas fa-user"></i> الملف الشخصي</h1>
                <div class="user-menu">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-right"></i> العودة للوحة التحكم
                    </a>
                    <a href="activity.php" class="btn btn-secondary">
                        <i class="fas fa-history"></i> سجل النشاط
                    </a>
                    <a href="logout.php" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                    </a>
                </div>
            </div>
        </header>
        
        <div class="profile-container">
            <div class="profile-card">
                <div class="profile-header">
                    <div class="profile-avatar">
                        <i class="fas fa-user-circle"></i>
                    </div>
                    <div class="profile-info">
                        <h2><?php echo htmlspecialchars($user['username']); ?></h2>
                        <p class="profile-role">
                            <span class="role-badge role-<?php echo $user['role']; ?>">
                                <?php echo $user['role'] === 'admin' ? 'مدير' : 'مستخدم'; ?>
                            </span>
                        </p>
                    </div>
                </div>
                
                <div class="profile-details">
                    <div class="detail-section">
                        <h3><i class="fas fa-info-circle"></i> المعلومات الأساسية</h3>
                        <div class="detail-grid">
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
                        </div>
                    </div>
                    
                    <div class="detail-section">
                        <h3><i class="fas fa-clock"></i> معلومات الحساب</h3>
                        <div class="detail-grid">
                            <div class="detail-item">
                                <label>تاريخ الإنشاء:</label>
                                <span><?php echo date('Y-m-d H:i:s', strtotime($user['created_at'])); ?></span>
                            </div>
                            <?php if ($user['last_login']): ?>
                            <div class="detail-item">
                                <label>آخر تسجيل دخول:</label>
                                <span><?php echo date('Y-m-d H:i:s', strtotime($user['last_login'])); ?></span>
                            </div>
                            <?php endif; ?>
                            <div class="detail-item">
                                <label>مدة العضوية:</label>
                                <span><?php 
                                    $created = new DateTime($user['created_at']);
                                    $now = new DateTime();
                                    $diff = $now->diff($created);
                                    echo $diff->days . ' يوم';
                                ?></span>
                            </div>
                        </div>
                    </div>
                    
                    <?php if ($user['role'] === 'admin'): ?>
                    <div class="detail-section">
                        <h3><i class="fas fa-crown"></i> صلاحيات الإدارة</h3>
                        <div class="admin-permissions">
                            <div class="permission-item">
                                <i class="fas fa-users"></i>
                                <span>إدارة المستخدمين</span>
                            </div>
                            <div class="permission-item">
                                <i class="fas fa-chart-bar"></i>
                                <span>عرض الإحصائيات</span>
                            </div>
                            <div class="permission-item">
                                <i class="fas fa-cog"></i>
                                <span>إعدادات النظام</span>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <div class="profile-actions">
                    <a href="dashboard.php" class="btn btn-primary">
                        <i class="fas fa-edit"></i> تعديل الملف الشخصي
                    </a>
                    <?php if ($user['role'] === 'admin'): ?>
                    <a href="admin.php" class="btn btn-warning">
                        <i class="fas fa-cog"></i> لوحة الإدارة
                    </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>
