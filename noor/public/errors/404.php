<?php
/**
 * صفحة خطأ 404 - الصفحة غير موجودة
 * تظهر عندما يحاول المستخدم الوصول لصفحة غير موجودة
 */

http_response_code(404);
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>خطأ 404 - الصفحة غير موجودة</title>
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body>
    <div class="container">
        <div class="error-container">
            <div class="error-content">
                <div class="error-icon">
                    <i class="fas fa-search"></i>
                </div>
                <h1>404 - الصفحة غير موجودة</h1>
                <p>عذراً، الصفحة التي تبحث عنها غير موجودة أو تم نقلها.</p>
                <div class="error-actions">
                    <a href="../dashboard.php" class="btn btn-primary">
                        <i class="fas fa-home"></i> العودة للوحة التحكم
                    </a>
                    <a href="../index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-right"></i> الصفحة الرئيسية
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
            color: #6c757d;
            margin-bottom: 20px;
        }
        
        .error-content h1 {
            color: #6c757d;
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
