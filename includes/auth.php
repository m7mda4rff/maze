<?php
/**
 * ملف التحقق من الصلاحيات
 * 
 * يستخدم للتحقق من تسجيل دخول المستخدم وصلاحياته
 */

// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    exit('لا يمكن الوصول المباشر لهذا الملف');
}

/**
 * التحقق من تسجيل دخول المستخدم
 * 
 * @return bool
 */
function is_logged_in() {
    return isset($_SESSION[SESSION_NAME]['user_id']) && !empty($_SESSION[SESSION_NAME]['user_id']);
}

/**
 * التحقق من دور المستخدم
 * 
 * @param string|array $allowed_roles الأدوار المسموح بها
 * @return bool
 */
function check_role($allowed_roles) {
    if (!is_logged_in()) {
        return false;
    }
    
    if (!isset($_SESSION[SESSION_NAME]['role'])) {
        return false;
    }
    
    // تحويل الدور إلى مصفوفة إذا كان نصاً
    if (!is_array($allowed_roles)) {
        $allowed_roles = [$allowed_roles];
    }
    
    return in_array($_SESSION[SESSION_NAME]['role'], $allowed_roles);
}

/**
 * التأكد من تسجيل دخول المستخدم، وإعادة التوجيه إذا لم يكن
 * 
 * @param string $redirect_url عنوان URL للتوجيه إليه (اختياري)
 * @return void
 */
function require_login($redirect_url = null) {
    if (!is_logged_in()) {
        // تخزين الصفحة الحالية للعودة إليها بعد تسجيل الدخول
        if (!empty($_SERVER['REQUEST_URI']) && strpos($_SERVER['REQUEST_URI'], 'login.php') === false) {
            $_SESSION[SESSION_NAME]['redirect_after_login'] = $_SERVER['REQUEST_URI'];
        }
        
        // إعادة التوجيه إلى صفحة تسجيل الدخول
        if ($redirect_url) {
            redirect($redirect_url);
        } else {
            redirect('login.php');
        }
    }
}

/**
 * التأكد من أن المستخدم لديه الدور المطلوب، وإعادة التوجيه إذا لم يكن
 * 
 * @param string|array $allowed_roles الأدوار المسموح بها
 * @param string $redirect_url عنوان URL للتوجيه إليه (اختياري)
 * @return void
 */
function require_role($allowed_roles, $redirect_url = null) {
    require_login();
    
    if (!check_role($allowed_roles)) {
        // تخزين رسالة خطأ
        set_flash_message('ليس لديك صلاحية للوصول إلى هذه الصفحة.', 'error');
        
        // إعادة التوجيه
        if ($redirect_url) {
            redirect($redirect_url);
        } else {
            redirect('dashboard.php');
        }
    }
}

/**
 * التحقق من وجود فترة زمنية لخروج المستخدم التلقائي
 * 
 * @return void
 */
function check_session_timeout() {
    if (is_logged_in()) {
        $max_lifetime = SESSION_LIFETIME;
        
        // التحقق من وقت آخر تحديث للجلسة
        if (isset($_SESSION[SESSION_NAME]['last_activity'])) {
            $inactive_time = time() - $_SESSION[SESSION_NAME]['last_activity'];
            
            // إذا تجاوز المستخدم وقت عدم النشاط المسموح به
            if ($inactive_time > $max_lifetime) {
                // تسجيل الخروج
                logout();
                
                // إعادة التوجيه إلى صفحة تسجيل الدخول مع رسالة
                set_flash_message('تم تسجيل خروجك تلقائياً بسبب عدم النشاط.', 'info');
                redirect('login.php');
            }
        }
        
        // تحديث وقت آخر نشاط
        $_SESSION[SESSION_NAME]['last_activity'] = time();
    }
}

/**
 * تسجيل خروج المستخدم وإنهاء الجلسة
 * 
 * @return void
 */
function logout() {
    // إزالة معلومات المستخدم من الجلسة
    unset($_SESSION[SESSION_NAME]);
    
    // إعادة تهيئة مصفوفة الجلسة
    $_SESSION[SESSION_NAME] = [];
    
    // تدمير الجلسة
    session_destroy();
}

/**
 * الحصول على معرف المستخدم الحالي
 * 
 * @return int|null
 */
function get_current_user_id() {
    return is_logged_in() ? $_SESSION[SESSION_NAME]['user_id'] : null;
}

/**
 * الحصول على اسم المستخدم الحالي
 * 
 * @return string
 */
function get_current_user_name() {
    return is_logged_in() ? $_SESSION[SESSION_NAME]['name'] : '';
}

/**
 * الحصول على دور المستخدم الحالي
 * 
 * @return string
 */
function get_current_user_role() {
    return is_logged_in() ? $_SESSION[SESSION_NAME]['role'] : '';
}

/**
 * التحقق مما إذا كان المستخدم الحالي مديراً
 * 
 * @return bool
 */
function is_admin() {
    return is_logged_in() && $_SESSION[SESSION_NAME]['role'] === 'admin';
}

/**
 * التحقق مما إذا كان المستخدم الحالي مديراً أو مشرفاً
 * 
 * @return bool
 */
function is_manager() {
    return is_logged_in() && in_array($_SESSION[SESSION_NAME]['role'], ['admin', 'manager']);
}

/**
 * تحديث بيانات المستخدم في الجلسة
 * 
 * @param array $user_data بيانات المستخدم المراد تحديثها
 * @return void
 */
function update_session_user_data($user_data) {
    if (!is_logged_in()) {
        return;
    }
    
    foreach ($user_data as $key => $value) {
        if (in_array($key, ['user_id', 'username', 'name', 'role', 'email'])) {
            $_SESSION[SESSION_NAME][$key] = $value;
        }
    }
}

/**
 * توليد توكن تغيير كلمة المرور
 * 
 * @param int $user_id معرف المستخدم
 * @return string
 */
function generate_password_reset_token($user_id) {
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 86400); // صلاحية لمدة 24 ساعة
    
    // الحصول على كائن قاعدة البيانات
    $db = Database::getInstance();
    
    // حذف أي توكن سابق لهذا المستخدم
    $db->delete('password_reset_tokens', 'user_id = ?', [$user_id]);
    
    // إنشاء توكن جديد
    $db->insert('password_reset_tokens', [
        'user_id' => $user_id,
        'token' => $token,
        'expires_at' => $expires
    ]);
    
    return $token;
}

/**
 * التحقق من صحة توكن تغيير كلمة المرور
 * 
 * @param string $token التوكن
 * @return int|false معرف المستخدم أو false إذا كان التوكن غير صالح
 */
function validate_password_reset_token($token) {
    // الحصول على كائن قاعدة البيانات
    $db = Database::getInstance();
    
    // البحث عن التوكن
    $reset_info = $db->fetchOne('
        SELECT user_id, expires_at
        FROM password_reset_tokens
        WHERE token = ?
    ', [$token]);
    
    if (!$reset_info) {
        return false;
    }
    
    // التحقق من انتهاء صلاحية التوكن
    if (strtotime($reset_info['expires_at']) < time()) {
        // حذف التوكن منتهي الصلاحية
        $db->delete('password_reset_tokens', 'token = ?', [$token]);
        return false;
    }
    
    return $reset_info['user_id'];
}

/**
 * حذف توكن تغيير كلمة المرور بعد استخدامه
 * 
 * @param string $token التوكن
 * @return bool
 */
function consume_password_reset_token($token) {
    // الحصول على كائن قاعدة البيانات
    $db = Database::getInstance();
    
    // حذف التوكن
    return $db->delete('password_reset_tokens', 'token = ?', [$token]) > 0;
}
