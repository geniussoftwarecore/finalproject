<?php
/**
 * لوحة الإدارة
 * صفحة خاصة بالمديرين لإدارة المستخدمين
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/models/UserModel.php';

// التحقق من تسجيل الدخول وصلاحيات المدير
requireAdmin();

$userModel = new UserModel($pdo);
$message = '';
$error = '';

// معالجة طلبات الإدارة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = 'رمز الحماية غير صحيح';
    } else {
        $action = $_POST['action'] ?? '';
        
        switch ($action) {
            case 'update_role':
                $userId = (int)($_POST['user_id'] ?? 0);
                $newRole = $_POST['role'] ?? '';
                
                if ($userId && in_array($newRole, ['user', 'admin'])) {
                    if ($userModel->updateUserRole($userId, $newRole)) {
                        $message = 'تم تحديث دور المستخدم بنجاح';
                    } else {
                        $error = 'فشل في تحديث دور المستخدم';
                    }
                } else {
                    $error = 'بيانات غير صحيحة';
                }
                break;
                
            case 'delete_user':
                $userId = (int)($_POST['user_id'] ?? 0);
                
                if ($userId && $userId != $_SESSION['user_id']) {
                    if ($userModel->deleteUser($userId)) {
                        $message = 'تم حذف المستخدم بنجاح';
                    } else {
                        $error = 'فشل في حذف المستخدم';
                    }
                } else {
                    $error = 'لا يمكن حذف نفسك أو بيانات غير صحيحة';
                }
                break;
        }
    }
}

// الحصول على قائمة المستخدمين
$users = $userModel->getAllUsers();
$stats = $userModel->getUserStats();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>لوحة الإدارة - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <div class="header-content">
                <h1><i class="fas fa-crown"></i> لوحة الإدارة</h1>
                <div class="user-menu">
                    <a href="../dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-right"></i> العودة للوحة التحكم
                    </a>
                    <a href="../logout.php" class="btn btn-danger">
                        <i class="fas fa-sign-out-alt"></i> تسجيل الخروج
                    </a>
                </div>
            </div>
        </header>
        
        <?php if (!empty($message)): ?>
            <div class="alert alert-success">
                <i class="fas fa-check-circle"></i>
                <?php echo $message; ?>
            </div>
        <?php endif; ?>
        
        <?php if (!empty($error)): ?>
            <div class="alert alert-error">
                <i class="fas fa-exclamation-circle"></i>
                <?php echo $error; ?>
            </div>
        <?php endif; ?>
        
        <div class="admin-content">
            <!-- إحصائيات سريعة -->
            <div class="stats-grid">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['total']; ?></div>
                        <div class="stat-label">إجمالي المستخدمين</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['users']; ?></div>
                        <div class="stat-label">مستخدمين عاديين</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-crown"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['admins']; ?></div>
                        <div class="stat-label">مديرين</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['new_users']; ?></div>
                        <div class="stat-label">مستخدمين جدد هذا الشهر</div>
                    </div>
                </div>
            </div>
            
            <!-- جدول المستخدمين -->
            <div class="admin-card">
                <div class="card-header">
                    <h2><i class="fas fa-users"></i> إدارة المستخدمين</h2>
                </div>
                <div class="card-content">
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>المعرف</th>
                                    <th>اسم المستخدم</th>
                                    <th>البريد الإلكتروني</th>
                                    <th>الدور</th>
                                    <th>تاريخ الإنشاء</th>
                                    <th>آخر تسجيل دخول</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($users as $user): ?>
                                <tr>
                                    <td><?php echo $user['id']; ?></td>
                                    <td><?php echo htmlspecialchars($user['username']); ?></td>
                                    <td><?php echo htmlspecialchars($user['email']); ?></td>
                                    <td>
                                        <span class="role-badge role-<?php echo $user['role']; ?>">
                                            <?php echo $user['role'] === 'admin' ? 'مدير' : 'مستخدم'; ?>
                                        </span>
                                    </td>
                                    <td><?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?></td>
                                    <td>
                                        <?php echo $user['last_login'] ? date('Y-m-d H:i', strtotime($user['last_login'])) : 'لم يسجل دخول'; ?>
                                    </td>
                                    <td>
                                        <div class="action-buttons">
                                            <!-- تحديث الدور -->
                                            <form method="POST" class="inline-form">
                                                <input type="hidden" name="action" value="update_role">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <select name="role" onchange="this.form.submit()">
                                                    <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>مستخدم</option>
                                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>مدير</option>
                                                </select>
                                            </form>
                                            
                                            <!-- حذف المستخدم -->
                                            <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                            <form method="POST" class="inline-form" 
                                                  onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم؟')">
                                                <input type="hidden" name="action" value="delete_user">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <button type="submit" class="btn btn-danger btn-sm">
                                                    <i class="fas fa-trash"></i>
                                                </button>
                                            </form>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            
            <!-- أدوات إضافية -->
            <div class="admin-tools">
                <div class="admin-card">
                    <div class="card-header">
                        <h2><i class="fas fa-tools"></i> أدوات الإدارة</h2>
                    </div>
                    <div class="card-content">
                        <div class="tools-grid">
                            <div class="tool-item">
                                <i class="fas fa-download"></i>
                                <h3>تصدير البيانات</h3>
                                <p>تصدير قائمة المستخدمين إلى ملف Excel</p>
                                <button class="btn btn-primary" onclick="exportUsers()">
                                    <i class="fas fa-download"></i> تصدير
                                </button>
                            </div>
                            
                            <div class="tool-item">
                                <i class="fas fa-chart-line"></i>
                                <h3>التقارير</h3>
                                <p>عرض تقارير مفصلة عن المستخدمين</p>
                                <button class="btn btn-info" onclick="showReports()">
                                    <i class="fas fa-chart-line"></i> التقارير
                                </button>
                            </div>
                            
                            <div class="tool-item">
                                <i class="fas fa-cog"></i>
                                <h3>إعدادات النظام</h3>
                                <p>تعديل إعدادات النظام العامة</p>
                                <button class="btn btn-warning" onclick="showSettings()">
                                    <i class="fas fa-cog"></i> الإعدادات
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="../assets/js/main.js"></script>
    <script>
        function exportUsers() {
            alert('سيتم إضافة وظيفة التصدير في التحديثات القادمة');
        }
        
        function showReports() {
            alert('سيتم إضافة التقارير في التحديثات القادمة');
        }
        
        function showSettings() {
            alert('سيتم إضافة الإعدادات في التحديثات القادمة');
        }
    </script>
</body>
</html>
