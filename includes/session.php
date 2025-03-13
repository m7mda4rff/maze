<?php
/**
 * ملف إدارة الجلسات
 * 
 * يستخدم لإنشاء وإدارة جلسات المستخدم
 */

// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    exit('لا يمكن الوصول المباشر لهذا الملف');
}

/**
 * بدء جلسة جديدة أو استئناف جلسة موجودة
 * 
 * @return void
 */
function init_session() {
    // تعيين خيارات الجلسة الآمنة
    $session_options = [
        'cookie_httponly' => true,     // منع JavaScript من الوصول للكوكي
        'cookie_secure' => isset($_SERVER['HTTPS']), // استخدام الكوكي فقط عبر HTTPS
        'cookie_samesite' => 'Lax',    // حماية من هجمات CSRF
        'use_strict_mode' => true,     // تفعيل الوضع الصارم
        'use_only_cookies' => true,    // استخدام الكوكي فقط
        'gc_maxlifetime' => SESSION_LIFETIME, // مدة حياة الجلسة
    ];
    
    // تعيين اسم الجلسة المخصص
    session_name(SESSION_NAME);
    
    // تعيين خيارات الجلسة
    session_set_cookie_params($session_options);
    
    // بدء الجلسة
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // إنشاء مصفوفة الجلسة الخاصة بالتطبيق إذا لم تكن موجودة
    if (!isset($_SESSION[SESSION_NAME])) {
        $_SESSION[SESSION_NAME] = [];
    }
    
    // إعادة توليد معرف الجلسة دورياً لمنع ثبات الجلسة
    if (isset($_SESSION[SESSION_NAME]['last_regeneration'])) {
        $regenerate_after = 60 * 30; // كل 30 دقيقة
        
        if ($_SESSION[SESSION_NAME]['last_regeneration'] < (time() - $regenerate_after)) {
            regenerate_session_id();
        }
    } else {
        $_SESSION[SESSION_NAME]['last_regeneration'] = time();
    }
    
    // التحقق من انتهاء مدة الجلسة
    check_session_timeout();
}

/**
 * إعادة توليد معرف الجلسة
 * 
 * @return void
 */
function regenerate_session_id() {
    // إعادة توليد معرف الجلسة
    session_regenerate_id(true);
    
    // تحديث وقت إعادة التوليد
    $_SESSION[SESSION_NAME]['last_regeneration'] = time();
}

/**
 * تسجيل نشاط جديد لتمديد عمر الجلسة
 * 
 * @return void
 */
function update_session_activity() {
    $_SESSION[SESSION_NAME]['last_activity'] = time();
}

/**
 * إعداد جلسة المستخدم بعد تسجيل الدخول
 * 
 * @param array $user بيانات المستخدم
 * @return void
 */
function set_session_user($user) {
    $_SESSION[SESSION_NAME]['user_id'] = $user['id'];
    $_SESSION[SESSION_NAME]['username'] = $user['username'];
    $_SESSION[SESSION_NAME]['name'] = $user['name'];
    $_SESSION[SESSION_NAME]['role'] = $user['role'];
    $_SESSION[SESSION_NAME]['login_time'] = time();
    $_SESSION[SESSION_NAME]['last_activity'] = time();
    
    // إعادة توليد معرف الجلسة بعد تسجيل الدخول
    regenerate_session_id();
}

/**
 * الحصول على بيانات المستخدم من الجلسة
 * 
 * @param string $key المفتاح المراد الحصول عليه (اختياري)
 * @return mixed
 */
function get_session_user($key = null) {
    if (!is_logged_in()) {
        return null;
    }
    
    if ($key !== null) {
        return isset($_SESSION[SESSION_NAME][$key]) ? $_SESSION[SESSION_NAME][$key] : null;
    }
    
    return [
        'user_id' => $_SESSION[SESSION_NAME]['user_id'],
        'username' => $_SESSION[SESSION_NAME]['username'],
        'name' => $_SESSION[SESSION_NAME]['name'],
        'role' => $_SESSION[SESSION_NAME]['role'],
        'login_time' => $_SESSION[SESSION_NAME]['login_time'],
        'last_activity' => $_SESSION[SESSION_NAME]['last_activity']
    ];
}

/**
 * مسح بيانات المستخدم من الجلسة
 * 
 * @return void
 */
function clear_session_user() {
    $keys_to_keep = ['csrf_token', 'flash_messages'];
    $temp_data = [];
    
    // الاحتفاظ ببيانات معينة
    foreach ($keys_to_keep as $key) {
        if (isset($_SESSION[SESSION_NAME][$key])) {
            $temp_data[$key] = $_SESSION[SESSION_NAME][$key];
        }
    }
    
    // إعادة تعيين مصفوفة الجلسة
    $_SESSION[SESSION_NAME] = $temp_data;
}

/**
 * تخزين بيانات مؤقتة في الجلسة
 * 
 * @param string $key مفتاح البيانات
 * @param mixed $value قيمة البيانات
 * @return void
 */
function set_session_data($key, $value) {
    $_SESSION[SESSION_NAME]['data'][$key] = $value;
}

/**
 * الحصول على بيانات مؤقتة من الجلسة
 * 
 * @param string $key مفتاح البيانات
 * @param mixed $default القيمة الافتراضية
 * @return mixed
 */
function get_session_data($key, $default = null) {
    return isset($_SESSION[SESSION_NAME]['data'][$key]) ? $_SESSION[SESSION_NAME]['data'][$key] : $default;
}

/**
 * حذف بيانات مؤقتة من الجلسة
 * 
 * @param string $key مفتاح البيانات
 * @return void
 */
function remove_session_data($key) {
    if (isset($_SESSION[SESSION_NAME]['data'][$key])) {
        unset($_SESSION[SESSION_NAME]['data'][$key]);
    }
}

/**
 * تخزين بيانات مؤقتة في الجلسة لمرة واحدة
 * (تحذف بعد استردادها)
 * 
 * @param string $key مفتاح البيانات
 * @param mixed $value قيمة البيانات
 * @return void
 */
function set_session_flash_data($key, $value) {
    $_SESSION[SESSION_NAME]['flash_data'][$key] = $value;
}

/**
 * الحصول على بيانات مؤقتة لمرة واحدة من الجلسة
 * (تحذف بعد استردادها)
 * 
 * @param string $key مفتاح البيانات
 * @param mixed $default القيمة الافتراضية
 * @return mixed
 */
function get_session_flash_data($key, $default = null) {
    $value = $default;
    
    if (isset($_SESSION[SESSION_NAME]['flash_data'][$key])) {
        $value = $_SESSION[SESSION_NAME]['flash_data'][$key];
        unset($_SESSION[SESSION_NAME]['flash_data'][$key]);
    }
    
    return $value;
}

/**
 * حفظ عنوان URL للتوجيه إليه بعد تسجيل الدخول
 * 
 * @param string $url عنوان URL
 * @return void
 */
function set_redirect_url($url) {
    $_SESSION[SESSION_NAME]['redirect_after_login'] = $url;
}

/**
 * الحصول على عنوان URL للتوجيه إليه بعد تسجيل الدخول
 * 
 * @param string $default عنوان URL الافتراضي
 * @return string
 */
function get_redirect_url($default = 'dashboard.php') {
    if (isset($_SESSION[SESSION_NAME]['redirect_after_login'])) {
        $url = $_SESSION[SESSION_NAME]['redirect_after_login'];
        unset($_SESSION[SESSION_NAME]['redirect_after_login']);
        return $url;
    }
    
    return $default;
}

/**
 * إعداد لغة الجلسة
 * 
 * @param string $lang رمز اللغة
 * @return void
 */
function set_session_language($lang) {
    $allowed_languages = ['ar', 'en']; // اللغات المدعومة
    
    if (in_array($lang, $allowed_languages)) {
        $_SESSION[SESSION_NAME]['language'] = $lang;
    }
}

/**
 * الحصول على لغة الجلسة
 * 
 * @return string
 */
function get_session_language() {
    global $config;
    
    return isset($_SESSION[SESSION_NAME]['language']) ? 
           $_SESSION[SESSION_NAME]['language'] : 
           $config['language'];
}

/**
 * تسجيل محاولات تسجيل الدخول الفاشلة
 * 
 * @param string $username اسم المستخدم
 * @return int عدد المحاولات الفاشلة
 */
function log_failed_login($username) {
    if (!isset($_SESSION[SESSION_NAME]['failed_logins'])) {
        $_SESSION[SESSION_NAME]['failed_logins'] = [];
    }
    
    $ip = $_SERVER['REMOTE_ADDR'];
    $now = time();
    
    if (!isset($_SESSION[SESSION_NAME]['failed_logins'][$username])) {
        $_SESSION[SESSION_NAME]['failed_logins'][$username] = [];
    }
    
    // إضافة محاولة فاشلة جديدة
    $_SESSION[SESSION_NAME]['failed_logins'][$username][] = [
        'ip' => $ip,
        'time' => $now
    ];
    
    // حفظ آخر 5 محاولات فقط
    if (count($_SESSION[SESSION_NAME]['failed_logins'][$username]) > 5) {
        array_shift($_SESSION[SESSION_NAME]['failed_logins'][$username]);
    }
    
    return count($_SESSION[SESSION_NAME]['failed_logins'][$username]);
}

/**
 * الحصول على عدد محاولات تسجيل الدخول الفاشلة
 * 
 * @param string $username اسم المستخدم
 * @param int $timeframe الإطار الزمني بالثواني (للمحاولات الحديثة فقط)
 * @return int
 */
function get_failed_login_count($username, $timeframe = 1800) { // 30 دقيقة افتراضياً
    if (!isset($_SESSION[SESSION_NAME]['failed_logins'][$username])) {
        return 0;
    }
    
    $now = time();
    $count = 0;
    
    foreach ($_SESSION[SESSION_NAME]['failed_logins'][$username] as $attempt) {
        if ($now - $attempt['time'] <= $timeframe) {
            $count++;
        }
    }
    
    return $count;
}

/**
 * إعادة تعيين محاولات تسجيل الدخول الفاشلة
 * 
 * @param string $username اسم المستخدم
 * @return void
 */
function reset_failed_logins($username) {
    if (isset($_SESSION[SESSION_NAME]['failed_logins'][$username])) {
        unset($_SESSION[SESSION_NAME]['failed_logins'][$username]);
    }
}

/**
 * التحقق مما إذا كان المستخدم مقفلاً بسبب محاولات تسجيل الدخول الفاشلة
 * 
 * @param string $username اسم المستخدم
 * @param int $max_attempts الحد الأقصى للمحاولات المسموح بها
 * @param int $timeframe الإطار الزمني بالثواني
 * @return bool
 */
function is_account_locked($username, $max_attempts = 5, $timeframe = 1800) {
    $count = get_failed_login_count($username, $timeframe);
    return $count >= $max_attempts;
}

/**
 * الحصول على الوقت المتبقي لفتح الحساب
 * 
 * @param string $username اسم المستخدم
 * @param int $timeframe الإطار الزمني بالثواني
 * @return int
 */
function get_account_unlock_time($username, $timeframe = 1800) {
    if (!isset($_SESSION[SESSION_NAME]['failed_logins'][$username]) || 
        empty($_SESSION[SESSION_NAME]['failed_logins'][$username])) {
        return 0;
    }
    
    // الحصول على وقت أحدث محاولة
    $latest_attempt = end($_SESSION[SESSION_NAME]['failed_logins'][$username]);
    $unlock_time = $latest_attempt['time'] + $timeframe - time();
    
    return max(0, $unlock_time);
}
