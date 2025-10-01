<?php
/**
 * صفحة سجل النشاط
 * تعرض سجل تسجيل الدخول والأنشطة للمستخدم
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/lib/activity.php';
require_once __DIR__ . '/../app/models/UserModel.php';

// التحقق من تسجيل الدخول
requireLogin();

$activityLog = new ActivityLog($pdo);
$userModel = new UserModel($pdo);
$user = $userModel->getUserById($_SESSION['user_id']);

if (!$user) {
    session_destroy();
    header('Location: login.php');
    exit();
}

$success = '';
$errors = [];

// معالجة طلب مسح السجل
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'رمز الحماية غير صحيح';
    } else {
        if ($_POST['action'] === 'clear_history') {
            if ($user['role'] === 'admin') {
                if ($activityLog->clearAllLoginHistory()) {
                    $success = 'تم مسح جميع سجلات تسجيل الدخول بنجاح';
                } else {
                    $errors['general'] = 'فشل في مسح سجلات تسجيل الدخول';
                }
            } else {
                $errors['general'] = 'ليس لديك صلاحية لتنفيذ هذا الإجراء';
            }
        } elseif ($_POST['action'] === 'clear_my_history') {
            if ($activityLog->clearUserLoginHistory($_SESSION['user_id'])) {
                $success = 'تم مسح سجل تسجيل الدخول الخاص بك بنجاح';
            } else {
                $errors['general'] = 'فشل في مسح سجل تسجيل الدخول';
            }
        }
    }
}

// الحصول على سجل تسجيل الدخول
$loginHistory = $activityLog->getUserLoginHistory($_SESSION['user_id'], 20);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>سجل النشاط - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .activity-table {
            width: 100%;
            background: var(--white);
            border-radius: var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            margin-top: 20px;
        }
        
        .activity-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .activity-table thead {
            background: var(--primary-color);
            color: var(--white);
        }
        
        .activity-table th {
            padding: 15px;
            text-align: right;
            font-weight: 600;
        }
        
        .activity-table td {
            padding: 12px 15px;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .activity-table tbody tr:hover {
            background: var(--gray-100);
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .status-success {
            background: #d4edda;
            color: #155724;
        }
        
        .status-failed {
            background: #f8d7da;
            color: #721c24;
        }
        
        .no-data {
            text-align: center;
            padding: 40px;
            color: var(--gray-600);
        }
        
        .activity-actions {
            display: flex;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        @media (max-width: 768px) {
            .activity-table {
                overflow-x: auto;
            }
            
            .activity-table table {
                min-width: 600px;
            }
            
            .activity-table th,
            .activity-table td {
                padding: 10px 8px;
                font-size: 0.875rem;
            }
            
            .header-content {
                flex-direction: column;
                align-items: flex-start;
            }
            
            .user-menu {
                width: 100%;
            }
            
            .user-menu .btn {
                flex: 1;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <div class="header-content">
                <h1><i class="fas fa-history"></i> سجل النشاط</h1>
                <div class="user-menu">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-right"></i> العودة للوحة التحكم
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
        
        <div class="dashboard-content">
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
            
            <div class="dashboard-card">
                <div class="card-header">
                    <h2><i class="fas fa-list"></i> سجل تسجيل الدخول (آخر 20 محاولة)</h2>
                </div>
                <div class="card-content" style="padding: 0;">
                    <?php if (empty($loginHistory)): ?>
                        <div class="no-data">
                            <i class="fas fa-inbox" style="font-size: 3rem; color: var(--gray-400); margin-bottom: 15px;"></i>
                            <p>لا توجد سجلات متاحة حالياً</p>
                        </div>
                    <?php else: ?>
                        <div class="activity-table">
                            <table>
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>التاريخ والوقت</th>
                                        <th>عنوان IP</th>
                                        <th>الحالة</th>
                                        <th>المتصفح/الجهاز</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($loginHistory as $index => $log): ?>
                                        <tr>
                                            <td><?php echo $index + 1; ?></td>
                                            <td>
                                                <i class="fas fa-clock"></i>
                                                <?php echo $activityLog->formatArabicDate($log['login_time']); ?>
                                            </td>
                                            <td>
                                                <i class="fas fa-network-wired"></i>
                                                <?php echo htmlspecialchars($log['ip_address']); ?>
                                            </td>
                                            <td>
                                                <?php if ($log['status'] === 'success'): ?>
                                                    <span class="status-badge status-success">
                                                        <i class="fas fa-check-circle"></i> نجح
                                                    </span>
                                                <?php else: ?>
                                                    <span class="status-badge status-failed">
                                                        <i class="fas fa-times-circle"></i> فشل
                                                    </span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <i class="fas fa-desktop"></i>
                                                <?php echo htmlspecialchars($activityLog->parseUserAgent($log['user_agent'])); ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <?php if (!empty($loginHistory)): ?>
            <div class="activity-actions">
                <form method="POST" style="display: inline;" onsubmit="return confirm('هل أنت متأكد من مسح سجل تسجيل الدخول الخاص بك؟');">
                    <input type="hidden" name="action" value="clear_my_history">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <button type="submit" class="btn btn-warning">
                        <i class="fas fa-eraser"></i> مسح سجلي
                    </button>
                </form>
                
                <?php if ($user['role'] === 'admin'): ?>
                <form method="POST" style="display: inline;" onsubmit="return confirm('هل أنت متأكد من مسح جميع سجلات تسجيل الدخول؟ هذا الإجراء لا يمكن التراجع عنه.');">
                    <input type="hidden" name="action" value="clear_history">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i> مسح جميع السجلات (مدير)
                    </button>
                </form>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
</body>
</html>
