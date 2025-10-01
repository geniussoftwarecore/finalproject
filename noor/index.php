<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>تنبيه أمني - نظام إدارة المستخدمين</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 15px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
            padding: 40px;
            max-width: 600px;
            text-align: center;
        }
        
        .warning-icon {
            font-size: 80px;
            color: #f39c12;
            margin-bottom: 20px;
        }
        
        h1 {
            color: #e74c3c;
            margin-bottom: 20px;
            font-size: 28px;
        }
        
        .message {
            color: #555;
            line-height: 1.8;
            margin-bottom: 30px;
            font-size: 18px;
        }
        
        .info-box {
            background: #f8f9fa;
            border-right: 4px solid #667eea;
            padding: 20px;
            margin: 20px 0;
            text-align: right;
        }
        
        .info-box strong {
            color: #667eea;
            display: block;
            margin-bottom: 10px;
            font-size: 20px;
        }
        
        .redirect-btn {
            display: inline-block;
            background: #667eea;
            color: white;
            padding: 15px 40px;
            border-radius: 50px;
            text-decoration: none;
            font-size: 18px;
            font-weight: bold;
            transition: all 0.3s;
            box-shadow: 0 4px 15px rgba(102, 126, 234, 0.4);
        }
        
        .redirect-btn:hover {
            background: #5568d3;
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(102, 126, 234, 0.6);
        }
        
        .countdown {
            color: #999;
            margin-top: 20px;
            font-size: 14px;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="warning-icon">⚠️</div>
        <h1>تنبيه أمني مهم</h1>
        
        <div class="message">
            <p>لا يجب الوصول إلى المجلد الجذري مباشرة!</p>
        </div>
        
        <div class="info-box">
            <strong>نقطة الدخول الصحيحة:</strong>
            <p>يجب عليك استخدام مجلد <code>public/</code> كنقطة دخول للتطبيق لأسباب أمنية.</p>
            <p>هذا يحمي الملفات الحساسة من الوصول المباشر.</p>
        </div>
        
        <a href="public/" class="redirect-btn">الانتقال إلى الصفحة الرئيسية</a>
        
        <div class="countdown">
            سيتم التوجيه تلقائياً خلال <span id="countdown">5</span> ثواني...
        </div>
    </div>
    
    <script>
        let seconds = 5;
        const countdownElement = document.getElementById('countdown');
        
        const timer = setInterval(() => {
            seconds--;
            countdownElement.textContent = seconds;
            
            if (seconds <= 0) {
                clearInterval(timer);
                window.location.href = 'public/';
            }
        }, 1000);
    </script>
</body>
</html>
