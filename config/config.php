<?php
/**
 * ملف الإعدادات العامة للنظام
 * يحتوي على المتغيرات والثوابت العامة المستخدمة في النظام
 */

// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    exit('لا يمكن الوصول المباشر لهذا الملف');
}

// تعريف المتغيرات الأساسية
define('APP_NAME', 'ميز للضيافة');  // اسم التطبيق
define('APP_VERSION', '1.0.0');      // إصدار التطبيق

// إعدادات URL
define('BASE_URL', 'http://localhost/majlis_catering');  // عنوان URL الأساسي (يجب تغييره حسب التثبيت)
define('PUBLIC_PATH', BASE_URL . '/public');             // مسار الملفات العامة
define('ASSETS_PATH', PUBLIC_PATH . '/assets');          // مسار ملفات الأصول

// إعدادات المسارات
define('ROOT_PATH', dirname(__DIR__));                    // المسار الجذري للنظام
define('CONFIG_PATH', ROOT_PATH . '/config');             // مسار ملفات الإعدادات
define('INCLUDES_PATH', ROOT_PATH . '/includes');         // مسار الملفات المشتركة
define('CLASSES_PATH', ROOT_PATH . '/classes');           // مسار الفئات
define('TEMPLATES_PATH', ROOT_PATH . '/templates');       // مسار القوالب
define('VIEWS_PATH', ROOT_PATH . '/views');               // مسار صفحات العرض

// إعدادات الجلسة
define('SESSION_NAME', 'majlis_session');                 // اسم الجلسة
define('SESSION_LIFETIME', 86400);                        // مدة الجلسة بالثواني (24 ساعة)

// إعدادات التاريخ والوقت
define('DATE_FORMAT', 'd/m/Y');                           // صيغة التاريخ
define('TIME_FORMAT', 'h:i A');                           // صيغة الوقت
define('DATETIME_FORMAT', 'd/m/Y h:i A');                 // صيغة التاريخ والوقت
define('DEFAULT_TIMEZONE', 'Asia/Riyadh');                // المنطقة الزمنية الافتراضية

// إعدادات الأمان
define('AUTH_SALT', 'majlis_catering_salt_123');          // ملح تشفير كلمات المرور
define('CSRF_TOKEN_NAME', 'csrf_token');                  // اسم توكن حماية CSRF

// إعدادات السجلات
define('ENABLE_ERROR_LOG', true);                         // تفعيل سجل الأخطاء
define('LOG_PATH', ROOT_PATH . '/logs');                  // مسار ملفات السجلات

// الإعدادات الافتراضية
$config = [
    'language' => 'ar',                                   // اللغة الافتراضية
    'direction' => 'rtl',                                 // اتجاه النص (من اليمين إلى اليسار)
    'pagination_limit' => 20,                             // عدد العناصر في الصفحة الواحدة
    'currency' => 'ر.س',                                 // العملة الافتراضية
    'currency_position' => 'after',                       // موضع رمز العملة (before/after)
    'decimal_separator' => '.',                           // فاصل العلامة العشرية
    'thousand_separator' => ',',                          // فاصل الآلاف
    'decimal_places' => 2,                                // عدد الخانات العشرية
    'allow_registration' => false,                        // السماح بالتسجيل في النظام
    'default_role' => 'staff',                            // الدور الافتراضي للمستخدمين الجدد
    'maintenance_mode' => false,                          // وضع الصيانة
];

// إعادة إعدادات النظام
return $config;
