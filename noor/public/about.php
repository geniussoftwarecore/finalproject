<?php
/**
 * صفحة حول النظام
 * تعرض معلومات عن النظام وميزاته
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
    <title>حول النظام - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .about-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        .about-hero {
            background: linear-gradient(135deg, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 60px 30px;
            border-radius: var(--border-radius);
            text-align: center;
            margin-bottom: 40px;
        }
        
        .about-hero h2 {
            font-size: 2.5rem;
            margin-bottom: 15px;
        }
        
        .about-hero p {
            font-size: 1.2rem;
            opacity: 0.95;
        }
        
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        
        .feature-card {
            background: white;
            border-radius: var(--border-radius);
            padding: 30px;
            text-align: center;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease, box-shadow 0.3s ease;
        }
        
        .feature-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 5px 20px rgba(0, 0, 0, 0.15);
        }
        
        .feature-icon {
            font-size: 3rem;
            color: var(--primary-color);
            margin-bottom: 20px;
        }
        
        .feature-card h3 {
            color: var(--text-color);
            margin-bottom: 15px;
            font-size: 1.4rem;
        }
        
        .feature-card p {
            color: var(--text-muted);
            line-height: 1.6;
        }
        
        .stats-section {
            background: var(--gray-100);
            border-radius: var(--border-radius);
            padding: 40px;
            margin-bottom: 40px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 30px;
            margin-top: 30px;
        }
        
        .stat-card {
            text-align: center;
        }
        
        .stat-number {
            font-size: 3rem;
            font-weight: bold;
            color: var(--primary-color);
            margin-bottom: 10px;
        }
        
        .stat-label {
            color: var(--text-muted);
            font-size: 1.1rem;
        }
        
        .tech-stack {
            background: white;
            border-radius: var(--border-radius);
            padding: 40px;
            box-shadow: 0 2px 10px rgba(0, 0, 0, 0.1);
        }
        
        .tech-stack h3 {
            color: var(--text-color);
            margin-bottom: 25px;
            font-size: 1.8rem;
        }
        
        .tech-list {
            display: flex;
            flex-wrap: wrap;
            gap: 15px;
        }
        
        .tech-badge {
            background: var(--primary-color);
            color: white;
            padding: 10px 20px;
            border-radius: 20px;
            font-size: 0.95rem;
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <div class="header-content">
                <h1><i class="fas fa-info-circle"></i> حول النظام</h1>
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

        <div class="about-container">
            <div class="about-hero">
                <h2><i class="fas fa-shield-alt"></i> <?php echo APP_NAME; ?></h2>
                <p>نظام متكامل وآمن لإدارة المستخدمين والحسابات</p>
                <p style="font-size: 1rem; margin-top: 10px;">الإصدار <?php echo APP_VERSION; ?></p>
            </div>

            <div class="features-grid">
                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <h3>أمان متقدم</h3>
                    <p>حماية CSRF، تشفير كلمات المرور، وتحقق reCAPTCHA لضمان أقصى درجات الأمان</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-users-cog"></i>
                    </div>
                    <h3>إدارة شاملة</h3>
                    <p>لوحة تحكم متكاملة لإدارة المستخدمين وصلاحياتهم بسهولة وكفاءة</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-history"></i>
                    </div>
                    <h3>تتبع النشاط</h3>
                    <p>سجل كامل لتتبع نشاط المستخدمين ومحاولات تسجيل الدخول</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-mobile-alt"></i>
                    </div>
                    <h3>تصميم متجاوب</h3>
                    <p>واجهة متجاوبة تعمل بسلاسة على جميع الأجهزة والشاشات</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-language"></i>
                    </div>
                    <h3>دعم كامل للعربية</h3>
                    <p>واجهة عربية بالكامل مع دعم الاتجاه من اليمين لليسار</p>
                </div>

                <div class="feature-card">
                    <div class="feature-icon">
                        <i class="fas fa-check-circle"></i>
                    </div>
                    <h3>تحقق من البيانات</h3>
                    <p>التحقق الشامل من صحة البيانات وتنظيف المدخلات</p>
                </div>
            </div>

            <div class="stats-section">
                <h3 style="text-align: center; color: var(--text-color); font-size: 1.8rem;">إحصائيات النظام</h3>
                <div class="stats-grid">
                    <div class="stat-card">
                        <div class="stat-number">
                            <i class="fas fa-lock"></i>
                        </div>
                        <div class="stat-label">تشفير قوي</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">
                            <i class="fas fa-code"></i>
                        </div>
                        <div class="stat-label">كود نظيف</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">
                            <i class="fas fa-database"></i>
                        </div>
                        <div class="stat-label">قاعدة بيانات منظمة</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-number">
                            <i class="fas fa-rocket"></i>
                        </div>
                        <div class="stat-label">أداء عالي</div>
                    </div>
                </div>
            </div>

            <div class="tech-stack">
                <h3><i class="fas fa-layer-group"></i> التقنيات المستخدمة</h3>
                <div class="tech-list">
                    <span class="tech-badge"><i class="fab fa-php"></i> PHP 7.4+</span>
                    <span class="tech-badge"><i class="fas fa-database"></i> MySQL / PDO</span>
                    <span class="tech-badge"><i class="fab fa-html5"></i> HTML5</span>
                    <span class="tech-badge"><i class="fab fa-css3-alt"></i> CSS3</span>
                    <span class="tech-badge"><i class="fab fa-js"></i> JavaScript</span>
                    <span class="tech-badge"><i class="fas fa-shield-alt"></i> reCAPTCHA v2</span>
                    <span class="tech-badge"><i class="fas fa-server"></i> Apache / XAMPP</span>
                </div>
            </div>
        </div>
    </div>

    <script src="assets/js/main.js"></script>
</body>
</html>
