<?php
/**
 * مكتبة التحقق من reCAPTCHA
 * تحتوي على دوال التحقق من reCAPTCHA v2
 */

class Captcha {
    
    /**
     * التحقق من صحة reCAPTCHA
     */
    public static function verifyCaptcha($response) {
        if (empty($response)) {
            return false;
        }
        
        $secretKey = RECAPTCHA_SECRET_KEY;
        $url = 'https://www.google.com/recaptcha/api/siteverify';
        
        $data = [
            'secret' => $secretKey,
            'response' => $response,
            'remoteip' => $_SERVER['REMOTE_ADDR']
        ];
        
        $options = [
            'http' => [
                'header' => "Content-type: application/x-www-form-urlencoded\r\n",
                'method' => 'POST',
                'content' => http_build_query($data)
            ]
        ];
        
        $context = stream_context_create($options);
        $result = file_get_contents($url, false, $context);
        
        if ($result === false) {
            error_log("خطأ في الاتصال بخدمة reCAPTCHA");
            return false;
        }
        
        $resultJson = json_decode($result, true);
        
        return isset($resultJson['success']) && $resultJson['success'] === true;
    }
    
    /**
     * إنشاء HTML لـ reCAPTCHA
     */
    public static function renderCaptcha() {
        $siteKey = RECAPTCHA_SITE_KEY;
        return '<div class="g-recaptcha" data-sitekey="' . htmlspecialchars($siteKey) . '"></div>';
    }
    
    /**
     * إنشاء JavaScript لـ reCAPTCHA
     */
    public static function renderCaptchaScript() {
        return '<script src="https://www.google.com/recaptcha/api.js" async defer></script>';
    }
    
    /**
     * التحقق من وجود reCAPTCHA في الطلب
     */
    public static function hasCaptchaResponse() {
        return isset($_POST['g-recaptcha-response']) && !empty($_POST['g-recaptcha-response']);
    }
    
    /**
     * الحصول على استجابة reCAPTCHA من الطلب
     */
    public static function getCaptchaResponse() {
        return isset($_POST['g-recaptcha-response']) ? $_POST['g-recaptcha-response'] : '';
    }
    
    /**
     * التحقق من صحة reCAPTCHA مع رسالة خطأ
     */
    public static function validateCaptcha() {
        if (!self::hasCaptchaResponse()) {
            return [
                'valid' => false,
                'message' => 'يرجى إكمال التحقق من reCAPTCHA'
            ];
        }
        
        $response = self::getCaptchaResponse();
        
        if (!self::verifyCaptcha($response)) {
            return [
                'valid' => false,
                'message' => 'فشل التحقق من reCAPTCHA. يرجى المحاولة مرة أخرى'
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'تم التحقق بنجاح'
        ];
    }
    
    /**
     * إنشاء HTML كامل لصفحة مع reCAPTCHA
     */
    public static function renderCaptchaForm($formAction = '', $formMethod = 'POST') {
        $html = '<form action="' . htmlspecialchars($formAction) . '" method="' . htmlspecialchars($formMethod) . '">';
        $html .= self::renderCaptcha();
        $html .= '<br><button type="submit">تحقق</button>';
        $html .= '</form>';
        $html .= self::renderCaptchaScript();
        
        return $html;
    }
    
    /**
     * التحقق من إعدادات reCAPTCHA
     */
    public static function checkConfig() {
        $siteKey = RECAPTCHA_SITE_KEY;
        $secretKey = RECAPTCHA_SECRET_KEY;
        
        if (empty($siteKey) || $siteKey === 'YOUR_RECAPTCHA_SITE_KEY') {
            return [
                'valid' => false,
                'message' => 'مفتاح الموقع reCAPTCHA غير مُعرّف'
            ];
        }
        
        if (empty($secretKey) || $secretKey === 'YOUR_RECAPTCHA_SECRET_KEY') {
            return [
                'valid' => false,
                'message' => 'المفتاح السري reCAPTCHA غير مُعرّف'
            ];
        }
        
        return [
            'valid' => true,
            'message' => 'إعدادات reCAPTCHA صحيحة'
        ];
    }
    
    /**
     * إنشاء رسالة خطأ مخصصة
     */
    public static function getErrorMessage($errorCode) {
        $messages = [
            'missing-input-secret' => 'المفتاح السري مفقود',
            'invalid-input-secret' => 'المفتاح السري غير صحيح',
            'missing-input-response' => 'استجابة التحقق مفقودة',
            'invalid-input-response' => 'استجابة التحقق غير صحيحة',
            'bad-request' => 'طلب غير صحيح',
            'timeout-or-duplicate' => 'انتهت صلاحية التحقق أو تم استخدامه مسبقاً'
        ];
        
        return $messages[$errorCode] ?? 'خطأ غير معروف في التحقق';
    }
}
?>
