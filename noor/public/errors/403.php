<?php
/**
 * صفحة خطأ 403 - ممنوع الوصول
 * تظهر عندما يحاول المستخدم الوصول لصفحة لا يملك صلاحيات لها
 */

http_response_code(403);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خطأ 403 - ممنوع الوصول</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="error-container">
            <div class="error-content">
                <div class="error-icon">
                    <i class="fas fa-ban"></i>
                </div>
                <h1>403 - ممنوع الوصول</h1>
                <p>عذراً، ليس لديك صلاحيات للوصول إلى هذه الصفحة.</p>
                <div class="error-actions">
                    <a href="../dashboard.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> العودة للوحة التحكم
                    </a>
                    <a href="../login.php" class="btn btn-secondary">
                        <i class="fas fa-sign-in-alt"></i> تسجيل الدخول
                    </a>
                </div>
            </div>
        </div>
    </div>
    
    <style>
        .error-container {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            text-align: center;
        }
        
        .error-content {
            background: white;
            padding: 40px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.1);
            max-width: 500px;
        }
        
        .error-icon {
            font-size: 4rem;
            color: #dc3545;
            margin-bottom: 20px;
        }
        
        .error-content h1 {
            color: #dc3545;
            margin-bottom: 20px;
            font-size: 2rem;
        }
        
        .error-content p {
            color: #6c757d;
            margin-bottom: 30px;
            font-size: 1.1rem;
        }
        
        .error-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
            flex-wrap: wrap;
        }
    </style>
</body>
</html>
