<?php
/**
 * ملف إعدادات قاعدة البيانات
 * يحتوي على معلومات الاتصال بقاعدة البيانات
 */

// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    exit('لا يمكن الوصول المباشر لهذا الملف');
}

// إعدادات قاعدة البيانات
$db_config = [
    'host'     => 'localhost',        // خادم قاعدة البيانات
    'username' => 'root',             // اسم المستخدم لقاعدة البيانات
    'password' => '',                 // كلمة المرور لقاعدة البيانات
    'db_name'  => 'majlis_catering',  // اسم قاعدة البيانات
    'charset'  => 'utf8mb4',          // ترميز الأحرف
    'options'  => [                   // خيارات PDO
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ],
];

// إعادة معلومات الاتصال بقاعدة البيانات
return $db_config;
