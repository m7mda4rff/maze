<?php
/**
 * صفحة تسجيل الدخول
 * 
 * تستخدم للتحقق من بيانات المستخدم وتسجيل الدخول
 */

// تعريف ثابت للتحقق من الوصول المباشر
define('BASEPATH', true);

// استيراد ملفات الإعدادات
require_once '../config/config.php';
require_once '../includes/session.php';
require_once '../includes/functions.php';
require_once '../includes/auth.php';
require_once '../classes/Database.php';
require_once '../classes/User.php';

// بدء الجلسة
init_session();

// التحقق إذا كان المستخدم مسجل دخوله بالفعل
if (is_logged_in()) {
    redirect(get_redirect_url());
}

// تهيئة متغيرات الخطأ والنجاح
$error = '';
$success = '';
$username = '';

// معالجة نموذج تسجيل الدخول
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من توكن CSRF
    if (!verify_csrf_token(get_request('csrf_token', 'POST'))) {
        $error = 'فشل التحقق من الأمان. يرجى المحاولة مرة أخرى.';
    } else {
        $username = get_request('username', 'POST', '');
        $password = get_request('password', 'POST', '');
        
        // التحقق من وجود اسم المستخدم وكلمة المرور
        if (empty($username) || empty($password)) {
            $error = 'يرجى إدخال اسم المستخدم وكلمة المرور.';
        } else {
            // التحقق من قفل الحساب بسبب كثرة المحاولات الفاشلة
            if (is_account_locked($username)) {
                $unlock_time = get_account_unlock_time($username);
                $minutes = ceil($unlock_time / 60);
                $error = "تم قفل الحساب مؤقتاً. يرجى المحاولة بعد {$minutes} دقيقة.";
            } else {
                // إنشاء كائن المستخدم ومحاولة تسجيل الدخول
                $user = new User();
                
                if ($user->login($username, $password)) {
                    // نجاح تسجيل الدخول
                    
                    // إعادة تعيين محاولات تسجيل الدخول الفاشلة
                    reset_failed_logins($username);
                    
                    // تحديث نشاط الجلسة
                    update_session_activity();
                    
                    // توجيه المستخدم إلى الصفحة المناسبة
                    redirect(get_redirect_url());
                } else {
                    // فشل تسجيل الدخول
                    $error = 'اسم المستخدم أو كلمة المرور غير صحيحة.';
                    
                    // تسجيل محاولة فاشلة
                    $failed_attempts = log_failed_login($username);
                    
                    // إذا وصل المستخدم إلى عتبة التحذير
                    if ($failed_attempts >= 3) {
                        $remaining = 5 - $failed_attempts;
                        $error .= " بقي لديك {$remaining} محاولات قبل قفل الحساب مؤقتاً.";
                    }
                }
            }
        }
    }
}

// إنشاء توكن CSRF جديد
$csrf_token = generate_csrf_token();

// عنوان الصفحة
$page_title = 'تسجيل الدخول';
?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $page_title; ?> | <?php echo APP_NAME; ?></title>
    
    <!-- بوتستراب RTL -->
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/bootstrap.rtl.min.css">
    
    <!-- أيقونات بوتستراب -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    
    <!-- التنسيقات المخصصة -->
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/style.css">
    
    <style>
        body {
            background-color: #f8f9fa;
            font-family: 'Tajawal', sans-serif;
        }
        
        .login-container {
            max-width: 400px;
            margin: 100px auto;
        }
        
        .login-card {
            border-radius: 15px;
            box-shadow: 0 8px 20px rgba(0, 0, 0, 0.1);
            overflow: hidden;
        }
        
        .login-header {
            background-color: #007bff;
            color: white;
            padding: 20px;
            text-align: center;
        }
        
        .login-form {
            padding: 25px;
        }
        
        .login-logo {
            max-width: 150px;
            margin-bottom: 15px;
        }
        
        .form-floating > label {
            right: 0;
            left: auto;
            padding-right: 13px;
        }
    </style>
</head>

<body>
    <div class="container login-container">
        <div class="card login-card">
            <div class="login-header">
                <img src="<?php echo ASSETS_PATH; ?>/images/logo.png" alt="<?php echo APP_NAME; ?>" class="login-logo">
                <h3><?php echo APP_NAME; ?></h3>
                <p>نظام إدارة شركات الكاترينج والضيافة</p>
            </div>
            
            <div class="login-form">
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger" role="alert">
                        <i class="bi bi-exclamation-triangle-fill me-2"></i>
                        <?php echo $error; ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($success)): ?>
                    <div class="alert alert-success" role="alert">
                        <i class="bi bi-check-circle-fill me-2"></i>
                        <?php echo $success; ?>
                    </div>
                <?php endif; ?>
                
                <form method="POST" action="<?php echo $_SERVER['PHP_SELF']; ?>" class="needs-validation" novalidate>
                    <!-- حقل CSRF -->
                    <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                    
                    <div class="form-floating mb-3">
                        <input type="text" class="form-control" id="username" name="username" placeholder="اسم المستخدم" 
                            value="<?php echo html_escape($username); ?>" required>
                        <label for="username">اسم المستخدم</label>
                        <div class="invalid-feedback">
                            يرجى إدخال اسم المستخدم.
                        </div>
                    </div>
                    
                    <div class="form-floating mb-3">
                        <input type="password" class="form-control" id="password" name="password" placeholder="كلمة المرور" required>
                        <label for="password">كلمة المرور</label>
                        <div class="invalid-feedback">
                            يرجى إدخال كلمة المرور.
                        </div>
                    </div>
                    
                    <div class="form-check mb-3">
                        <input class="form-check-input" type="checkbox" id="remember" name="remember">
                        <label class="form-check-label" for="remember">
                            تذكرني
                        </label>
                    </div>
                    
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary btn-lg">
                            <i class="bi bi-box-arrow-in-left me-2"></i>
                            تسجيل الدخول
                        </button>
                    </div>
                </form>
                
                <div class="text-center mt-3">
                    <a href="forgot_password.php" class="text-decoration-none">نسيت كلمة المرور؟</a>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-3 text-muted">
            <small>&copy; <?php echo date('Y'); ?> <?php echo APP_NAME; ?>. جميع الحقوق محفوظة.</small>
        </div>
    </div>
    
    <!-- مكتبة jQuery -->
    <script src="<?php echo ASSETS_PATH; ?>/js/jquery.min.js"></script>
    
    <!-- بوتستراب -->
    <script src="<?php echo ASSETS_PATH; ?>/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // التحقق من صحة النموذج
        (function() {
            'use strict';
            
            // الحصول على جميع النماذج التي نريد تطبيق التحقق عليها
            var forms = document.querySelectorAll('.needs-validation');
            
            // الحلقة فوق النماذج ومنع الإرسال
            Array.prototype.slice.call(forms).forEach(function(form) {
                form.addEventListener('submit', function(event) {
                    if (!form.checkValidity()) {
                        event.preventDefault();
                        event.stopPropagation();
                    }
                    
                    form.classList.add('was-validated');
                }, false);
            });
        })();
    </script>
</body>

</html>
