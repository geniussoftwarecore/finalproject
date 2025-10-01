<?php
/**
 * صفحة إدارة المستخدمين
 * صفحة خاصة بالمديرين لإدارة جميع المستخدمين في النظام
 */

require_once __DIR__ . '/../app/config/config.php';
require_once __DIR__ . '/../app/models/UserModel.php';

requireAdmin();

$userModel = new UserModel($pdo);
$message = '';
$error = '';

$perPage = 20;
$currentPage = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$search = isset($_GET['search']) ? sanitizeInput($_GET['search']) : '';

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
                
            case 'create_admin':
                $username = sanitizeInput($_POST['username'] ?? '');
                $email = sanitizeInput($_POST['email'] ?? '');
                $password = $_POST['password'] ?? '';
                
                $errors = [];
                
                if (empty($username) || strlen($username) < 3) {
                    $errors['username'] = 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل';
                } elseif ($userModel->usernameExists($username)) {
                    $errors['username'] = 'اسم المستخدم موجود بالفعل';
                }
                
                if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    $errors['email'] = 'البريد الإلكتروني غير صالح';
                } elseif ($userModel->emailExists($email)) {
                    $errors['email'] = 'البريد الإلكتروني موجود بالفعل';
                }
                
                if (empty($password) || strlen($password) < 6) {
                    $errors['password'] = 'كلمة المرور يجب أن تكون 6 أحرف على الأقل';
                }
                
                if (empty($errors)) {
                    if ($userModel->createAdmin($username, $email, $password)) {
                        $message = 'تم إنشاء حساب المدير بنجاح';
                        $_POST = [];
                    } else {
                        $error = 'فشل في إنشاء حساب المدير';
                    }
                } else {
                    $error = implode(', ', $errors);
                }
                break;
        }
    }
}

$users = $userModel->getUsersWithPagination($currentPage, $perPage, $search);
$totalUsers = $userModel->getUsersCountBySearch($search);
$totalPages = ceil($totalUsers / $perPage);
$stats = $userModel->getUserStats();
$stats['recent_logins'] = $userModel->getRecentLoginsCount();
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إدارة المستخدمين - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .user-management {
            margin-top: 20px;
        }
        
        .search-bar {
            background: var(--white);
            border-radius: var(--border-radius);
            box-shadow: var(--box-shadow);
            padding: 20px;
            margin-bottom: 20px;
        }
        
        .search-form {
            display: flex;
            gap: 10px;
            align-items: center;
        }
        
        .search-form input[type="text"] {
            flex: 1;
            padding: 12px 15px;
            border: 2px solid var(--gray-300);
            border-radius: var(--border-radius);
            font-size: 1rem;
        }
        
        .search-form input[type="text"]:focus {
            outline: none;
            border-color: var(--primary-color);
        }
        
        .pagination {
            display: flex;
            justify-content: center;
            gap: 10px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        
        .pagination a,
        .pagination span {
            padding: 8px 12px;
            border: 1px solid var(--gray-300);
            border-radius: 5px;
            text-decoration: none;
            color: var(--gray-700);
            background: var(--white);
            transition: var(--transition);
        }
        
        .pagination a:hover {
            background: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
        }
        
        .pagination .active {
            background: var(--primary-color);
            color: var(--white);
            border-color: var(--primary-color);
        }
        
        .pagination .disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }
        
        .modal {
            display: none;
            position: fixed;
            z-index: 1000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0,0,0,0.5);
            animation: fadeIn 0.3s ease;
        }
        
        .modal-content {
            background-color: var(--white);
            margin: 10% auto;
            padding: 0;
            border-radius: var(--border-radius);
            width: 90%;
            max-width: 500px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.2);
            animation: slideUp 0.3s ease;
        }
        
        @keyframes slideUp {
            from {
                transform: translateY(50px);
                opacity: 0;
            }
            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
        
        .modal-header {
            background: var(--primary-color);
            color: var(--white);
            padding: 20px;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .modal-header h2 {
            margin: 0;
            font-size: 1.3rem;
        }
        
        .close-modal {
            background: transparent;
            border: none;
            color: var(--white);
            font-size: 1.5rem;
            cursor: pointer;
            padding: 0;
            width: 30px;
            height: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 50%;
            transition: var(--transition);
        }
        
        .close-modal:hover {
            background: rgba(255,255,255,0.2);
        }
        
        .modal-body {
            padding: 30px;
        }
        
        .user-detail-modal .detail-row {
            display: flex;
            justify-content: space-between;
            padding: 12px 0;
            border-bottom: 1px solid var(--gray-200);
        }
        
        .user-detail-modal .detail-row:last-child {
            border-bottom: none;
        }
        
        .user-detail-modal .detail-label {
            font-weight: 600;
            color: var(--gray-700);
        }
        
        .user-detail-modal .detail-value {
            color: var(--gray-600);
        }
        
        .action-btn-group {
            display: flex;
            gap: 5px;
            align-items: center;
        }
        
        .btn-icon {
            padding: 8px 12px;
            font-size: 0.875rem;
        }
        
        @media (max-width: 768px) {
            .search-form {
                flex-direction: column;
            }
            
            .search-form input[type="text"],
            .search-form button {
                width: 100%;
            }
            
            .admin-table {
                font-size: 0.875rem;
            }
            
            .admin-table th,
            .admin-table td {
                padding: 8px;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <div class="header-content">
                <h1><i class="fas fa-users-cog"></i> إدارة المستخدمين</h1>
                <div class="user-menu">
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-right"></i> العودة للوحة التحكم
                    </a>
                    <a href="logout.php" class="btn btn-danger">
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
        
        <div class="user-management">
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
                        <i class="fas fa-crown"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['admins']; ?></div>
                        <div class="stat-label">المديرين</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-clock"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['recent_logins']; ?></div>
                        <div class="stat-label">تسجيلات دخول (24 ساعة)</div>
                    </div>
                </div>
                
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <div class="stat-content">
                        <div class="stat-value"><?php echo $stats['new_users']; ?></div>
                        <div class="stat-label">مستخدمين جدد (شهر)</div>
                    </div>
                </div>
            </div>
            
            <div class="search-bar">
                <form method="GET" class="search-form">
                    <input type="text" name="search" placeholder="البحث بالاسم أو البريد الإلكتروني..." value="<?php echo htmlspecialchars($search); ?>">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-search"></i> بحث
                    </button>
                    <?php if (!empty($search)): ?>
                        <a href="users.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> مسح
                        </a>
                    <?php endif; ?>
                    <button type="button" class="btn btn-success" onclick="openCreateAdminModal()">
                        <i class="fas fa-user-plus"></i> إنشاء مدير
                    </button>
                </form>
            </div>
            
            <div class="admin-card">
                <div class="card-header">
                    <h2>
                        <i class="fas fa-table"></i> قائمة المستخدمين 
                        <?php if (!empty($search)): ?>
                            (نتائج البحث: <?php echo $totalUsers; ?>)
                        <?php endif; ?>
                    </h2>
                </div>
                <div class="card-content">
                    <div class="table-responsive">
                        <table class="admin-table">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>اسم المستخدم</th>
                                    <th>البريد الإلكتروني</th>
                                    <th>الدور</th>
                                    <th>آخر تسجيل دخول</th>
                                    <th>تاريخ الإنشاء</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($users)): ?>
                                <tr>
                                    <td colspan="7" style="text-align: center; padding: 30px;">
                                        <i class="fas fa-inbox" style="font-size: 3rem; color: var(--gray-400); margin-bottom: 10px;"></i>
                                        <p style="color: var(--gray-600);">لا توجد نتائج</p>
                                    </td>
                                </tr>
                                <?php else: ?>
                                    <?php foreach ($users as $user): ?>
                                    <tr>
                                        <td><?php echo $user['id']; ?></td>
                                        <td>
                                            <i class="fas fa-user"></i>
                                            <?php echo htmlspecialchars($user['username']); ?>
                                        </td>
                                        <td>
                                            <i class="fas fa-envelope"></i>
                                            <?php echo htmlspecialchars($user['email']); ?>
                                        </td>
                                        <td>
                                            <form method="POST" class="inline-form">
                                                <input type="hidden" name="action" value="update_role">
                                                <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                <select name="role" onchange="if(confirm('هل تريد تغيير دور هذا المستخدم؟')) this.form.submit();">
                                                    <option value="user" <?php echo $user['role'] === 'user' ? 'selected' : ''; ?>>مستخدم</option>
                                                    <option value="admin" <?php echo $user['role'] === 'admin' ? 'selected' : ''; ?>>مدير</option>
                                                </select>
                                            </form>
                                        </td>
                                        <td>
                                            <?php 
                                            if ($user['last_login']) {
                                                echo '<i class="fas fa-clock"></i> ' . date('Y-m-d H:i', strtotime($user['last_login']));
                                            } else {
                                                echo '<span style="color: var(--gray-400);"><i class="fas fa-times"></i> لم يسجل دخول</span>';
                                            }
                                            ?>
                                        </td>
                                        <td>
                                            <i class="fas fa-calendar"></i>
                                            <?php echo date('Y-m-d H:i', strtotime($user['created_at'])); ?>
                                        </td>
                                        <td>
                                            <div class="action-btn-group">
                                                <button onclick='viewUserDetails(<?php echo json_encode($user); ?>)' class="btn btn-info btn-icon" title="عرض التفاصيل">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                
                                                <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                                <form method="POST" class="inline-form" onsubmit="return confirm('هل أنت متأكد من حذف هذا المستخدم؟ هذا الإجراء لا يمكن التراجع عنه!')">
                                                    <input type="hidden" name="action" value="delete_user">
                                                    <input type="hidden" name="user_id" value="<?php echo $user['id']; ?>">
                                                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                                    <button type="submit" class="btn btn-danger btn-icon" title="حذف">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </form>
                                                <?php else: ?>
                                                <button class="btn btn-secondary btn-icon" disabled title="لا يمكنك حذف نفسك">
                                                    <i class="fas fa-lock"></i>
                                                </button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    
                    <?php if ($totalPages > 1): ?>
                    <div class="pagination">
                        <?php if ($currentPage > 1): ?>
                            <a href="?page=<?php echo $currentPage - 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                <i class="fas fa-chevron-right"></i> السابق
                            </a>
                        <?php else: ?>
                            <span class="disabled">
                                <i class="fas fa-chevron-right"></i> السابق
                            </span>
                        <?php endif; ?>
                        
                        <?php
                        $startPage = max(1, $currentPage - 2);
                        $endPage = min($totalPages, $currentPage + 2);
                        
                        if ($startPage > 1) {
                            echo '<a href="?page=1' . ($search ? '&search=' . urlencode($search) : '') . '">1</a>';
                            if ($startPage > 2) echo '<span>...</span>';
                        }
                        
                        for ($i = $startPage; $i <= $endPage; $i++):
                            if ($i == $currentPage): ?>
                                <span class="active"><?php echo $i; ?></span>
                            <?php else: ?>
                                <a href="?page=<?php echo $i; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                    <?php echo $i; ?>
                                </a>
                            <?php endif;
                        endfor;
                        
                        if ($endPage < $totalPages) {
                            if ($endPage < $totalPages - 1) echo '<span>...</span>';
                            echo '<a href="?page=' . $totalPages . ($search ? '&search=' . urlencode($search) : '') . '">' . $totalPages . '</a>';
                        }
                        ?>
                        
                        <?php if ($currentPage < $totalPages): ?>
                            <a href="?page=<?php echo $currentPage + 1; ?><?php echo $search ? '&search=' . urlencode($search) : ''; ?>">
                                التالي <i class="fas fa-chevron-left"></i>
                            </a>
                        <?php else: ?>
                            <span class="disabled">
                                التالي <i class="fas fa-chevron-left"></i>
                            </span>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <div id="userDetailModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user"></i> تفاصيل المستخدم</h2>
                <button class="close-modal" onclick="closeUserDetailModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body user-detail-modal" id="userDetailContent">
            </div>
        </div>
    </div>
    
    <div id="createAdminModal" class="modal">
        <div class="modal-content">
            <div class="modal-header">
                <h2><i class="fas fa-user-plus"></i> إنشاء حساب مدير جديد</h2>
                <button class="close-modal" onclick="closeCreateAdminModal()">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            <div class="modal-body">
                <form method="POST" id="createAdminForm">
                    <input type="hidden" name="action" value="create_admin">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    
                    <div class="form-group">
                        <label for="admin_username">
                            <i class="fas fa-user"></i> اسم المستخدم
                        </label>
                        <input type="text" id="admin_username" name="username" required minlength="3">
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_email">
                            <i class="fas fa-envelope"></i> البريد الإلكتروني
                        </label>
                        <input type="email" id="admin_email" name="email" required>
                    </div>
                    
                    <div class="form-group">
                        <label for="admin_password">
                            <i class="fas fa-lock"></i> كلمة المرور
                        </label>
                        <input type="password" id="admin_password" name="password" required minlength="6">
                    </div>
                    
                    <div class="form-group">
                        <button type="submit" class="btn btn-success btn-full">
                            <i class="fas fa-check"></i> إنشاء حساب المدير
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script>
        function viewUserDetails(user) {
            const modal = document.getElementById('userDetailModal');
            const content = document.getElementById('userDetailContent');
            
            const roleText = user.role === 'admin' ? 'مدير' : 'مستخدم';
            const lastLogin = user.last_login ? new Date(user.last_login).toLocaleString('ar-EG') : 'لم يسجل دخول';
            const createdAt = new Date(user.created_at).toLocaleString('ar-EG');
            
            content.innerHTML = `
                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-id-badge"></i> المعرف:</span>
                    <span class="detail-value">${user.id}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-user"></i> اسم المستخدم:</span>
                    <span class="detail-value">${user.username}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-envelope"></i> البريد الإلكتروني:</span>
                    <span class="detail-value">${user.email}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-shield-alt"></i> الدور:</span>
                    <span class="detail-value"><span class="role-badge role-${user.role}">${roleText}</span></span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-clock"></i> آخر تسجيل دخول:</span>
                    <span class="detail-value">${lastLogin}</span>
                </div>
                <div class="detail-row">
                    <span class="detail-label"><i class="fas fa-calendar"></i> تاريخ الإنشاء:</span>
                    <span class="detail-value">${createdAt}</span>
                </div>
            `;
            
            modal.style.display = 'block';
        }
        
        function closeUserDetailModal() {
            document.getElementById('userDetailModal').style.display = 'none';
        }
        
        function openCreateAdminModal() {
            document.getElementById('createAdminModal').style.display = 'block';
        }
        
        function closeCreateAdminModal() {
            document.getElementById('createAdminModal').style.display = 'none';
            document.getElementById('createAdminForm').reset();
        }
        
        window.onclick = function(event) {
            const userModal = document.getElementById('userDetailModal');
            const adminModal = document.getElementById('createAdminModal');
            
            if (event.target == userModal) {
                closeUserDetailModal();
            }
            if (event.target == adminModal) {
                closeCreateAdminModal();
            }
        }
    </script>
</body>
</html>
