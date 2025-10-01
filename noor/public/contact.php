<?php
/**
 * صفحة الاتصال والدعم
 * تسمح للمستخدمين بإرسال رسائل الدعم والاستفسارات
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

$errors = [];
$success = '';

// معالجة طلب إرسال رسالة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من رمز CSRF
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $errors['general'] = 'رمز الحماية غير صحيح';
    } else {
        $subject = sanitizeInput($_POST['subject'] ?? '');
        $message = sanitizeInput($_POST['message'] ?? '');
        $priority = sanitizeInput($_POST['priority'] ?? 'medium');
        
        // التحقق من صحة البيانات
        if (empty($subject)) {
            $errors['subject'] = 'الرجاء اختيار موضوع الرسالة';
        }
        
        if (empty($message)) {
            $errors['message'] = 'الرجاء كتابة رسالتك';
        } elseif (strlen($message) < 10) {
            $errors['message'] = 'الرسالة يجب أن تكون 10 أحرف على الأقل';
        }
        
        if (!in_array($priority, ['low', 'medium', 'high'])) {
            $errors['priority'] = 'الأولوية غير صالحة';
        }
        
        // إذا لم تكن هناك أخطاء، قم بحفظ الرسالة
        if (empty($errors)) {
            try {
                $stmt = $pdo->prepare("
                    INSERT INTO contact_messages (user_id, subject, message, priority, status) 
                    VALUES (?, ?, ?, ?, 'pending')
                ");
                $stmt->execute([$_SESSION['user_id'], $subject, $message, $priority]);
                
                // إرسال إشعار للمدير (محاكاة - يمكن تطويره لاحقاً لإرسال بريد إلكتروني)
                // في بيئة حقيقية، يمكن استخدام دالة mail() أو خدمة بريد إلكتروني
                $messageId = $pdo->lastInsertId();
                error_log("رسالة جديدة من المستخدم {$user['username']} - الموضوع: {$subject} - الأولوية: {$priority}");
                
                $success = 'تم إرسال رسالتك بنجاح! سنقوم بالرد عليك في أقرب وقت ممكن.';
                
                // مسح الحقول بعد النجاح
                $_POST = [];
            } catch (PDOException $e) {
                error_log("خطأ في حفظ رسالة الاتصال: " . $e->getMessage());
                $errors['general'] = 'حدث خطأ أثناء إرسال الرسالة. الرجاء المحاولة مرة أخرى.';
            }
        }
    }
}

// الحصول على الرسائل السابقة للمستخدم
try {
    $stmt = $pdo->prepare("
        SELECT id, subject, priority, status, created_at 
        FROM contact_messages 
        WHERE user_id = ? 
        ORDER BY created_at DESC 
        LIMIT 5
    ");
    $stmt->execute([$_SESSION['user_id']]);
    $previousMessages = $stmt->fetchAll();
} catch (PDOException $e) {
    error_log("خطأ في جلب الرسائل السابقة: " . $e->getMessage());
    $previousMessages = [];
}
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>الاتصال والدعم - <?php echo APP_NAME; ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .contact-grid {
            display: grid;
            grid-template-columns: 1fr;
            gap: 30px;
            margin-top: 30px;
        }
        
        @media (min-width: 768px) {
            .contact-grid {
                grid-template-columns: 2fr 1fr;
            }
        }
        
        .faq-section {
            margin-top: 30px;
        }
        
        .faq-item {
            background: var(--gray-100);
            border-radius: var(--border-radius);
            padding: 20px;
            margin-bottom: 15px;
        }
        
        .faq-question {
            font-weight: 600;
            color: var(--primary-color);
            margin-bottom: 10px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .faq-answer {
            color: var(--gray-700);
            line-height: 1.6;
        }
        
        .admin-contact-info {
            background: linear-gradient(135deg, var(--primary-color), #0056b3);
            color: var(--white);
            border-radius: var(--border-radius);
            padding: 25px;
            margin-top: 20px;
        }
        
        .admin-contact-info h3 {
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        
        .contact-item {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 10px;
            padding: 10px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 5px;
        }
        
        .priority-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .priority-low {
            background: #d1ecf1;
            color: #0c5460;
        }
        
        .priority-medium {
            background: #fff3cd;
            color: #856404;
        }
        
        .priority-high {
            background: #f8d7da;
            color: #721c24;
        }
        
        .status-badge {
            display: inline-block;
            padding: 5px 12px;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 600;
        }
        
        .status-pending {
            background: #fff3cd;
            color: #856404;
        }
        
        .status-resolved {
            background: #d4edda;
            color: #155724;
        }
        
        .message-history {
            margin-top: 20px;
        }
        
        .message-item {
            background: var(--gray-100);
            border-radius: var(--border-radius);
            padding: 15px;
            margin-bottom: 10px;
            border-right: 4px solid var(--primary-color);
        }
        
        .message-item-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 10px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .message-item-subject {
            font-weight: 600;
            color: var(--gray-800);
        }
        
        .message-item-date {
            font-size: 0.875rem;
            color: var(--gray-600);
        }
    </style>
</head>
<body>
    <div class="container">
        <header class="dashboard-header">
            <div class="header-content">
                <h1><i class="fas fa-headset"></i> الاتصال والدعم</h1>
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
            
            <div class="contact-grid">
                <!-- نموذج الاتصال -->
                <div>
                    <div class="dashboard-card">
                        <div class="card-header">
                            <h2><i class="fas fa-envelope"></i> إرسال رسالة دعم</h2>
                        </div>
                        <div class="card-content">
                            <form method="POST" class="form" id="contactForm">
                                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                                
                                <div class="form-group">
                                    <label for="subject">
                                        <i class="fas fa-tag"></i> موضوع الرسالة *
                                    </label>
                                    <select id="subject" name="subject" required>
                                        <option value="">-- اختر الموضوع --</option>
                                        <option value="مشكلة تقنية" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'مشكلة تقنية') ? 'selected' : ''; ?>>مشكلة تقنية</option>
                                        <option value="استفسار عام" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'استفسار عام') ? 'selected' : ''; ?>>استفسار عام</option>
                                        <option value="طلب تحديث معلومات" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'طلب تحديث معلومات') ? 'selected' : ''; ?>>طلب تحديث معلومات</option>
                                        <option value="شكوى" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'شكوى') ? 'selected' : ''; ?>>شكوى</option>
                                        <option value="اقتراح" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'اقتراح') ? 'selected' : ''; ?>>اقتراح</option>
                                        <option value="طلب إلغاء الحساب" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'طلب إلغاء الحساب') ? 'selected' : ''; ?>>طلب إلغاء الحساب</option>
                                        <option value="مشكلة في الحساب" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'مشكلة في الحساب') ? 'selected' : ''; ?>>مشكلة في الحساب</option>
                                        <option value="أخرى" <?php echo (isset($_POST['subject']) && $_POST['subject'] === 'أخرى') ? 'selected' : ''; ?>>أخرى</option>
                                    </select>
                                    <?php if (!empty($errors['subject'])): ?>
                                        <span class="error-message"><?php echo $errors['subject']; ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label for="priority">
                                        <i class="fas fa-exclamation-triangle"></i> الأولوية *
                                    </label>
                                    <select id="priority" name="priority" required>
                                        <option value="low" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'low') ? 'selected' : ''; ?>>منخفضة</option>
                                        <option value="medium" <?php echo (!isset($_POST['priority']) || $_POST['priority'] === 'medium') ? 'selected' : ''; ?>>متوسطة</option>
                                        <option value="high" <?php echo (isset($_POST['priority']) && $_POST['priority'] === 'high') ? 'selected' : ''; ?>>عالية</option>
                                    </select>
                                    <span class="form-help">اختر الأولوية بناءً على مدى إلحاح مشكلتك</span>
                                    <?php if (!empty($errors['priority'])): ?>
                                        <span class="error-message"><?php echo $errors['priority']; ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <label for="message">
                                        <i class="fas fa-comment-alt"></i> الرسالة *
                                    </label>
                                    <textarea id="message" name="message" rows="8" required 
                                              placeholder="اكتب رسالتك هنا... الرجاء تقديم تفاصيل كافية عن مشكلتك أو استفسارك"><?php echo isset($_POST['message']) ? htmlspecialchars($_POST['message']) : ''; ?></textarea>
                                    <span class="form-help">يجب أن تكون الرسالة 10 أحرف على الأقل</span>
                                    <?php if (!empty($errors['message'])): ?>
                                        <span class="error-message"><?php echo $errors['message']; ?></span>
                                    <?php endif; ?>
                                </div>
                                
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary btn-full">
                                        <i class="fas fa-paper-plane"></i> إرسال الرسالة
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- الرسائل السابقة -->
                    <?php if (!empty($previousMessages)): ?>
                    <div class="message-history">
                        <div class="dashboard-card">
                            <div class="card-header">
                                <h2><i class="fas fa-history"></i> آخر رسائلك (آخر 5 رسائل)</h2>
                            </div>
                            <div class="card-content">
                                <?php foreach ($previousMessages as $msg): ?>
                                <div class="message-item">
                                    <div class="message-item-header">
                                        <div>
                                            <div class="message-item-subject">
                                                <i class="fas fa-envelope-open"></i>
                                                <?php echo htmlspecialchars($msg['subject']); ?>
                                            </div>
                                            <div class="message-item-date">
                                                <i class="fas fa-clock"></i>
                                                <?php echo date('Y-m-d H:i', strtotime($msg['created_at'])); ?>
                                            </div>
                                        </div>
                                        <div>
                                            <span class="priority-badge priority-<?php echo $msg['priority']; ?>">
                                                <?php 
                                                    $priorities = ['low' => 'منخفضة', 'medium' => 'متوسطة', 'high' => 'عالية'];
                                                    echo $priorities[$msg['priority']]; 
                                                ?>
                                            </span>
                                            <span class="status-badge status-<?php echo $msg['status']; ?>">
                                                <?php 
                                                    $statuses = ['pending' => 'قيد المعالجة', 'resolved' => 'تم الحل'];
                                                    echo $statuses[$msg['status']] ?? 'قيد المعالجة'; 
                                                ?>
                                            </span>
                                        </div>
                                    </div>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
                
                <!-- معلومات الاتصال والأسئلة الشائعة -->
                <div>
                    <!-- معلومات الاتصال بالإدارة -->
                    <div class="admin-contact-info">
                        <h3><i class="fas fa-info-circle"></i> معلومات الاتصال</h3>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <div>
                                <strong>البريد الإلكتروني:</strong><br>
                                support@noor-system.com
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <div>
                                <strong>الهاتف:</strong><br>
                                +966 50 123 4567
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-clock"></i>
                            <div>
                                <strong>ساعات العمل:</strong><br>
                                السبت - الخميس: 9 صباحاً - 5 مساءً
                            </div>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <div>
                                <strong>العنوان:</strong><br>
                                الرياض، المملكة العربية السعودية
                            </div>
                        </div>
                    </div>
                    
                    <!-- الأسئلة الشائعة -->
                    <div class="faq-section">
                        <div class="dashboard-card">
                            <div class="card-header">
                                <h2><i class="fas fa-question-circle"></i> الأسئلة الشائعة</h2>
                            </div>
                            <div class="card-content">
                                <div class="faq-item">
                                    <div class="faq-question">
                                        <i class="fas fa-chevron-left"></i>
                                        كيف يمكنني تغيير كلمة المرور؟
                                    </div>
                                    <div class="faq-answer">
                                        يمكنك تغيير كلمة المرور من خلال صفحة الإعدادات. انتقل إلى لوحة التحكم، ثم اختر "الإعدادات"، وأدخل كلمة المرور الحالية والجديدة.
                                    </div>
                                </div>
                                
                                <div class="faq-item">
                                    <div class="faq-question">
                                        <i class="fas fa-chevron-left"></i>
                                        كيف يمكنني تحديث بريدي الإلكتروني؟
                                    </div>
                                    <div class="faq-answer">
                                        من صفحة الإعدادات، يمكنك تحديث البريد الإلكتروني بعد التحقق من كلمة المرور الحالية. تأكد من أن البريد الجديد من gmail.com أو hotmail.com.
                                    </div>
                                </div>
                                
                                <div class="faq-item">
                                    <div class="faq-question">
                                        <i class="fas fa-chevron-left"></i>
                                        ماذا أفعل إذا نسيت كلمة المرور؟
                                    </div>
                                    <div class="faq-answer">
                                        للأسف، يجب عليك الاتصال بالدعم الفني لإعادة تعيين كلمة المرور. أرسل رسالة من خلال نموذج الاتصال أو تواصل معنا مباشرة.
                                    </div>
                                </div>
                                
                                <div class="faq-item">
                                    <div class="faq-question">
                                        <i class="fas fa-chevron-left"></i>
                                        كم من الوقت يستغرق الرد على رسالتي؟
                                    </div>
                                    <div class="faq-answer">
                                        نحاول الرد على جميع الرسائل خلال 24-48 ساعة. الرسائل ذات الأولوية العالية يتم معالجتها بشكل أسرع.
                                    </div>
                                </div>
                                
                                <div class="faq-item">
                                    <div class="faq-question">
                                        <i class="fas fa-chevron-left"></i>
                                        هل يمكنني حذف حسابي؟
                                    </div>
                                    <div class="faq-answer">
                                        نعم، يمكنك طلب حذف حسابك عن طريق إرسال رسالة مع اختيار موضوع "طلب إلغاء الحساب". سنقوم بمعالجة طلبك خلال 3-5 أيام عمل.
                                    </div>
                                </div>
                                
                                <div class="faq-item">
                                    <div class="faq-question">
                                        <i class="fas fa-chevron-left"></i>
                                        كيف يمكنني عرض سجل نشاطي؟
                                    </div>
                                    <div class="faq-answer">
                                        انتقل إلى لوحة التحكم واختر "سجل النشاط" لعرض آخر محاولات تسجيل الدخول وأنشطة حسابك.
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="assets/js/main.js"></script>
    <script>
        document.getElementById('contactForm').addEventListener('submit', function(e) {
            const message = document.getElementById('message').value.trim();
            const subject = document.getElementById('subject').value;
            
            if (!subject) {
                e.preventDefault();
                alert('الرجاء اختيار موضوع الرسالة');
                return false;
            }
            
            if (message.length < 10) {
                e.preventDefault();
                alert('الرسالة يجب أن تكون 10 أحرف على الأقل');
                return false;
            }
            
            return confirm('هل أنت متأكد من إرسال هذه الرسالة؟');
        });
    </script>
</body>
</html>
