/**
 * ملف JavaScript الرئيسي للمشروع
 * يحتوي على الوظائف الأساسية والتفاعل مع المستخدم
 */

// انتظار تحميل الصفحة
document.addEventListener('DOMContentLoaded', function() {
    // تهيئة الوظائف
    initFormValidation();
    initUsernameCheck();
    initPasswordStrength();
    initNavigation();
    initAlerts();
});

/**
 * تهيئة التحقق من النماذج
 */
function initFormValidation() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            if (!validateForm(this)) {
                e.preventDefault();
            }
        });
    });
}

/**
 * التحقق من صحة النموذج
 */
function validateForm(form) {
    let isValid = true;
    const requiredFields = form.querySelectorAll('[required]');
    
    // مسح رسائل الخطأ السابقة
    clearErrorMessages(form);
    
    requiredFields.forEach(field => {
        if (!field.value.trim()) {
            showFieldError(field, 'هذا الحقل مطلوب');
            isValid = false;
        }
    });
    
    // التحقق من كلمات المرور
    const password = form.querySelector('input[name="password"]');
    const confirmPassword = form.querySelector('input[name="confirm_password"]');
    
    if (password && confirmPassword) {
        if (password.value !== confirmPassword.value) {
            showFieldError(confirmPassword, 'كلمات المرور غير متطابقة');
            isValid = false;
        }
    }
    
    // التحقق من قوة كلمة المرور
    if (password && password.value.length > 0) {
        const strength = checkPasswordStrength(password.value);
        if (strength.score < 3) {
            showFieldError(password, 'كلمة المرور ضعيفة. يجب أن تحتوي على حروف وأرقام ورموز خاصة');
            isValid = false;
        }
    }
    
    return isValid;
}

/**
 * التحقق من قوة كلمة المرور
 */
function checkPasswordStrength(password) {
    let score = 0;
    let feedback = [];
    
    // طول كلمة المرور
    if (password.length >= 8) score++;
    else feedback.push('يجب أن تكون 8 أحرف على الأقل');
    
    // وجود حروف
    if (/[a-zA-Z]/.test(password)) score++;
    else feedback.push('يجب أن تحتوي على حروف');
    
    // وجود أرقام
    if (/[0-9]/.test(password)) score++;
    else feedback.push('يجب أن تحتوي على أرقام');
    
    // وجود رموز خاصة
    if (/[@$!%*?&]/.test(password)) score++;
    else feedback.push('يجب أن تحتوي على رموز خاصة (@$!%*?&)');
    
    return {
        score: score,
        feedback: feedback
    };
}

/**
 * التحقق من اسم المستخدم محلياً
 */
function initUsernameCheck() {
    const usernameField = document.querySelector('input[name="username"]');
    
    if (usernameField) {
        usernameField.addEventListener('input', function() {
            const username = this.value.trim();
            validateUsernameLocally(username);
        });
    }
}

/**
 * التحقق من صحة اسم المستخدم محلياً
 */
function validateUsernameLocally(username) {
    const usernameField = document.querySelector('input[name="username"]');
    
    if (username.length < 3) {
        showFieldError(usernameField, 'اسم المستخدم يجب أن يكون 3 أحرف على الأقل');
        return;
    }
    
    if (username.length > 50) {
        showFieldError(usernameField, 'اسم المستخدم يجب أن يكون أقل من 50 حرف');
        return;
    }
    
    if (!/^[a-zA-Z0-9_]+$/.test(username)) {
        showFieldError(usernameField, 'اسم المستخدم يجب أن يحتوي على أحرف وأرقام وشرطة سفلية فقط');
        return;
    }
    
    clearFieldStatus(usernameField);
}

/**
 * تهيئة مؤشر قوة كلمة المرور
 */
function initPasswordStrength() {
    const passwordField = document.querySelector('input[name="password"]');
    
    if (passwordField) {
        passwordField.addEventListener('input', function() {
            const password = this.value;
            const strength = checkPasswordStrength(password);
            
            updatePasswordStrengthIndicator(strength);
        });
    }
}

/**
 * تحديث مؤشر قوة كلمة المرور
 */
function updatePasswordStrengthIndicator(strength) {
    let indicator = document.getElementById('password-strength');
    
    if (!indicator) {
        const passwordField = document.querySelector('input[name="password"]');
        if (passwordField) {
            indicator = document.createElement('div');
            indicator.id = 'password-strength';
            indicator.className = 'password-strength';
            passwordField.parentNode.appendChild(indicator);
        }
    }
    
    if (indicator) {
        const strengthLabels = ['ضعيفة جداً', 'ضعيفة', 'متوسطة', 'قوية', 'قوية جداً'];
        const strengthColors = ['#dc3545', '#fd7e14', '#ffc107', '#20c997', '#28a745'];
        
        indicator.innerHTML = `
            <div class="strength-bar">
                <div class="strength-fill" style="width: ${(strength.score / 4) * 100}%; background-color: ${strengthColors[strength.score]};"></div>
            </div>
            <div class="strength-text">${strengthLabels[strength.score]}</div>
        `;
    }
}

/**
 * تهيئة التنقل بالأسهم
 */
function initNavigation() {
    const inputs = document.querySelectorAll('input[type="text"], input[type="email"], input[type="password"]');
    
    inputs.forEach((input, index) => {
        input.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowDown' || e.key === 'Enter') {
                e.preventDefault();
                if (index < inputs.length - 1) {
                    inputs[index + 1].focus();
                }
            } else if (e.key === 'ArrowUp') {
                e.preventDefault();
                if (index > 0) {
                    inputs[index - 1].focus();
                }
            }
        });
    });
}

/**
 * تهيئة التنبيهات
 */
function initAlerts() {
    // إخفاء التنبيهات تلقائياً بعد 5 ثوان
    const alerts = document.querySelectorAll('.alert');
    
    alerts.forEach(alert => {
        setTimeout(() => {
            alert.style.opacity = '0';
            setTimeout(() => {
                alert.remove();
            }, 300);
        }, 5000);
    });
}

/**
 * عرض خطأ في حقل
 */
function showFieldError(field, message) {
    clearFieldStatus(field);
    
    const errorElement = document.createElement('span');
    errorElement.className = 'error-message';
    errorElement.textContent = message;
    
    field.parentNode.appendChild(errorElement);
    field.style.borderColor = '#dc3545';
}

/**
 * عرض نجاح في حقل
 */
function showFieldSuccess(field, message) {
    clearFieldStatus(field);
    
    const successElement = document.createElement('span');
    successElement.className = 'success-message';
    successElement.textContent = message;
    successElement.style.color = '#28a745';
    successElement.style.fontSize = '0.875rem';
    successElement.style.display = 'block';
    successElement.style.marginTop = '5px';
    
    field.parentNode.appendChild(successElement);
    field.style.borderColor = '#28a745';
}

/**
 * مسح حالة الحقل
 */
function clearFieldStatus(field) {
    const existingError = field.parentNode.querySelector('.error-message');
    const existingSuccess = field.parentNode.querySelector('.success-message');
    
    if (existingError) existingError.remove();
    if (existingSuccess) existingSuccess.remove();
    
    field.style.borderColor = '';
}

/**
 * مسح حالة اسم المستخدم
 */
function clearUsernameStatus() {
    const usernameField = document.querySelector('input[name="username"]');
    if (usernameField) {
        clearFieldStatus(usernameField);
    }
}

/**
 * مسح رسائل الخطأ
 */
function clearErrorMessages(form) {
    const errorMessages = form.querySelectorAll('.error-message');
    errorMessages.forEach(error => error.remove());
}

/**
 * تأكيد الحذف
 */
function confirmDelete(message = 'هل أنت متأكد من الحذف؟') {
    return confirm(message);
}

/**
 * تحويل التاريخ إلى تنسيق عربي
 */
function formatArabicDate(dateString) {
    const date = new Date(dateString);
    const options = {
        year: 'numeric',
        month: 'long',
        day: 'numeric',
        hour: '2-digit',
        minute: '2-digit'
    };
    
    return date.toLocaleDateString('ar-SA', options);
}

/**
 * تحميل البيانات محلياً
 */
function loadData(url, callback) {
    // تم إزالة وظيفة AJAX - استخدام التحقق المحلي بدلاً من ذلك
    console.log('تم إزالة وظيفة AJAX - استخدام التحقق المحلي');
}

/**
 * إرسال البيانات محلياً
 */
function sendData(url, data, callback) {
    // تم إزالة وظيفة AJAX - استخدام التحقق المحلي بدلاً من ذلك
    console.log('تم إزالة وظيفة AJAX - استخدام التحقق المحلي');
}

/**
 * عرض رسالة تنبيه
 */
function showAlert(message, type = 'info') {
    const alertDiv = document.createElement('div');
    alertDiv.className = `alert alert-${type}`;
    alertDiv.innerHTML = `
        <i class="fas fa-${type === 'error' ? 'exclamation-circle' : type === 'success' ? 'check-circle' : 'info-circle'}"></i>
        ${message}
    `;
    
    // إدراج التنبيه في بداية الصفحة
    const container = document.querySelector('.container');
    if (container) {
        container.insertBefore(alertDiv, container.firstChild);
        
        // إخفاء التنبيه تلقائياً
        setTimeout(() => {
            alertDiv.style.opacity = '0';
            setTimeout(() => {
                alertDiv.remove();
            }, 300);
        }, 5000);
    }
}

/**
 * تحسين تجربة المستخدم للتنقل
 */
function enhanceNavigation() {
    // إضافة تأثيرات بصرية للأزرار
    const buttons = document.querySelectorAll('.btn');
    buttons.forEach(button => {
        button.addEventListener('mouseenter', function() {
            this.style.transform = 'translateY(-2px)';
        });
        
        button.addEventListener('mouseleave', function() {
            this.style.transform = 'translateY(0)';
        });
    });
}

/**
 * تهيئة التحقق من reCAPTCHA
 */
function initCaptchaValidation() {
    const forms = document.querySelectorAll('form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const captchaResponse = document.querySelector('[name="g-recaptcha-response"]');
            
            if (captchaResponse && !captchaResponse.value) {
                e.preventDefault();
                showAlert('يرجى إكمال التحقق من reCAPTCHA', 'error');
                return false;
            }
        });
    });
}

// تهيئة التحقق من reCAPTCHA
initCaptchaValidation();

// تهيئة تحسينات التنقل
enhanceNavigation();

/**
 * وظائف مساعدة إضافية
 */

// تحسين الأداء
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// تحسين البحث
function initSearch() {
    const searchInput = document.querySelector('input[type="search"]');
    
    if (searchInput) {
        const debouncedSearch = debounce(function(value) {
            // تنفيذ البحث هنا
            console.log('البحث عن:', value);
        }, 300);
        
        searchInput.addEventListener('input', function() {
            debouncedSearch(this.value);
        });
    }
}

// تهيئة البحث
initSearch();
