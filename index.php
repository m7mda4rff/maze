<?php
/**
 * نقطة الدخول الرئيسية لنظام ميز للضيافة
 * 
 * هذا الملف هو نقطة الدخول الرئيسية للنظام ويتعامل مع جميع الطلبات الواردة
 * ويقوم بتوجيهها إلى الصفحات المناسبة
 */

// تعريف ثابت لمنع الوصول المباشر للملفات
define('BASEPATH', true);

// تحديد المسار الجذري للنظام
define('ROOT_PATH', dirname(__DIR__));

// استدعاء ملف الإعدادات العامة
require_once ROOT_PATH . '/config/config.php';

// استدعاء ملفات النظام الضرورية
require_once INCLUDES_PATH . '/functions.php';
require_once INCLUDES_PATH . '/session.php';

// تهيئة الجلسة
initSession();

// استدعاء فئة قاعدة البيانات
require_once CLASSES_PATH . '/Database.php';

// توجيه الطلب إلى المسار المناسب
$page = isset($_GET['page']) ? sanitize($_GET['page']) : 'dashboard';

// التحقق من تسجيل الدخول
require_once INCLUDES_PATH . '/auth.php';

// حماية الصفحات المقيدة
$public_pages = ['login', 'logout', 'register', 'forgot-password', 'reset-password'];

if (!isLoggedIn() && !in_array($page, $public_pages)) {
    // إعادة توجيه المستخدم غير المسجل إلى صفحة تسجيل الدخول
    redirect('login');
}

// توجيه الطلب بناءً على نوع الصفحة
$page_path = '';

// تحديد مسار الصفحة
if (strpos($page, '/') !== false) {
    // للصفحات المتداخلة مثل customers/add
    list($directory, $file) = explode('/', $page, 2);
    $page_path = VIEWS_PATH . '/' . $directory . '/' . $file . '.php';
} else {
    // للصفحات المباشرة مثل dashboard
    $page_path = VIEWS_PATH . '/' . $page . '.php';
}

// التحقق من وجود الصفحة
if (file_exists($page_path)) {
    // استدعاء الصفحة المطلوبة
    require_once $page_path;
} else {
    // عرض صفحة الخطأ 404
    require_once VIEWS_PATH . '/errors/404.php';
}
