<?php
/**
 * قالب رأس الصفحة
 * يستخدم في جميع صفحات العرض
 */

// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    exit('لا يمكن الوصول المباشر لهذا الملف');
}

// تحميل إعدادات النظام
$config = require CONFIG_PATH . '/config.php';

// التحقق من دخول المستخدم (إذا كان مطلوباً)
if (!isset($skipAuth) || $skipAuth !== true) {
    require_once INCLUDES_PATH . '/auth.php';
    
    // إعادة توجيه المستخدم غير المسجل دخوله إلى صفحة الدخول
    if (!isLoggedIn()) {
        redirect('login.php');
    }
    
    // الحصول على معلومات المستخدم الحالي
    $currentUser = User::getCurrentUser();
}

// متغيرات للتحكم في العرض
$pageTitle = isset($pageTitle) ? $pageTitle . ' | ' . APP_NAME : APP_NAME;
$activeMenu = isset($activeMenu) ? $activeMenu : '';

// متغيرات للأصول
$cssVersion = '1.0.' . time();
$jsVersion = '1.0.' . time();

// تحديد ما إذا كان يجب عرض الشريط الجانبي
$showSidebar = !isset($hideSidebar) || $hideSidebar !== true;
?>
<!DOCTYPE html>
<html lang="<?php echo $config['language']; ?>" dir="<?php echo $config['direction']; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="ie=edge">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    
    <!-- تنسيقات بوتستراب -->
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/bootstrap.rtl.min.css">
    <!-- تنسيقات Font Awesome -->
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/all.min.css">
    <!-- تنسيقات التقويم -->
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/fullcalendar.min.css">
    <!-- تنسيقات مخصصة -->
    <link rel="stylesheet" href="<?php echo ASSETS_PATH; ?>/css/style.css?v=<?php echo $cssVersion; ?>">
    
    <?php if (isset($extraStyles) && !empty($extraStyles)): ?>
        <!-- تنسيقات إضافية خاصة بالصفحة -->
        <?php foreach ($extraStyles as $style): ?>
            <link rel="stylesheet" href="<?php echo ASSETS_PATH . $style . '?v=' . $cssVersion; ?>">
        <?php endforeach; ?>
    <?php endif; ?>
    
    <!-- أيقونة الموقع -->
    <link rel="shortcut icon" href="<?php echo ASSETS_PATH; ?>/images/favicon.ico" type="image/x-icon">
    
    <!-- سكريبت جيكويري -->
    <script src="<?php echo ASSETS_PATH; ?>/js/jquery.min.js"></script>
</head>
<body>
    <!-- إشعار الصيانة -->
    <?php if (isset($config['maintenance_mode']) && $config['maintenance_mode'] === true): ?>
        <div class="alert alert-warning maintenance-alert">
            <i class="fas fa-exclamation-triangle"></i>
            الموقع في وضع الصيانة. بعض الميزات قد لا تعمل بشكل صحيح.
        </div>
    <?php endif; ?>
    
    <!-- رأس الصفحة -->
    <header class="navbar navbar-expand-md navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <!-- شعار التطبيق -->
            <a class="navbar-brand" href="<?php echo BASE_URL; ?>/index.php">
                <img src="<?php echo ASSETS_PATH; ?>/images/logo-white.png" alt="<?php echo APP_NAME; ?>" height="40">
                <?php echo APP_NAME; ?>
            </a>
            
            <!-- زر للقائمة المنسدلة في الشاشات الصغيرة -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarTop" aria-controls="navbarTop" aria-expanded="false">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <!-- قائمة الروابط -->
            <div class="collapse navbar-collapse" id="navbarTop">
                <?php if (isset($currentUser) && $currentUser): ?>
                    <ul class="navbar-nav ms-auto">
                        <!-- إشعارات -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <?php
                                // عدد الإشعارات غير المقروءة (يجب تنفيذ هذه الوظيفة)
                                $unreadNotifications = 0; // استبدل هذا بالعدد الفعلي من قاعدة البيانات
                                if ($unreadNotifications > 0):
                                ?>
                                <span class="badge bg-danger"><?php echo $unreadNotifications; ?></span>
                                <?php endif; ?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                                <span class="dropdown-header">الإشعارات</span>
                                <div class="dropdown-divider"></div>
                                <?php if ($unreadNotifications > 0): ?>
                                    <!-- هنا يمكن عرض الإشعارات غير المقروءة من قاعدة البيانات -->
                                    <a class="dropdown-item" href="#">
                                        <i class="fas fa-calendar-check text-success"></i>
                                        تم تأكيد الفعالية: حفل زفاف آل سعيد
                                        <span class="text-muted small">منذ 5 دقائق</span>
                                    </a>
                                    <div class="dropdown-divider"></div>
                                <?php else: ?>
                                    <span class="dropdown-item text-center">لا توجد إشعارات جديدة</span>
                                <?php endif; ?>
                                <a class="dropdown-item text-center small" href="<?php echo BASE_URL; ?>/notifications.php">
                                    عرض جميع الإشعارات
                                </a>
                            </div>
                        </li>
                        
                        <!-- المهام -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="tasksDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-tasks"></i>
                                <?php
                                // عدد المهام المستحقة (يجب تنفيذ هذه الوظيفة)
                                $upcomingTasks = 0; // استبدل هذا بالعدد الفعلي من قاعدة البيانات
                                if ($upcomingTasks > 0):
                                ?>
                                <span class="badge bg-warning"><?php echo $upcomingTasks; ?></span>
                                <?php endif; ?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="tasksDropdown">
                                <span class="dropdown-header">المهام المستحقة</span>
                                <div class="dropdown-divider"></div>
                                <?php if ($upcomingTasks > 0): ?>
                                    <!-- هنا يمكن عرض المهام المستحقة من قاعدة البيانات -->
                                    <a class="dropdown-item" href="#">
                                        <i class="fas fa-exclamation-circle text-danger"></i>
                                        التواصل مع المورد: شركة الزهور
                                        <span class="text-muted small">مستحق اليوم</span>
                                    </a>
                                    <div class="dropdown-divider"></div>
                                <?php else: ?>
                                    <span class="dropdown-item text-center">لا توجد مهام مستحقة</span>
                                <?php endif; ?>
                                <a class="dropdown-item text-center small" href="<?php echo BASE_URL; ?>/views/tasks/index.php">
                                    عرض جميع المهام
                                </a>
                            </div>
                        </li>
                        
                        <!-- معلومات المستخدم -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-user-circle me-1"></i>
                                <?php echo htmlspecialchars($currentUser->getName()); ?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end" aria-labelledby="userDropdown">
                                <span class="dropdown-header">
                                    <?php echo htmlspecialchars($currentUser->getName()); ?>
                                    <small class="d-block text-muted">
                                        <?php
                                        // ترجمة دور المستخدم إلى العربية
                                        $roles = [
                                            'admin' => 'مدير',
                                            'manager' => 'مشرف',
                                            'staff' => 'موظف'
                                        ];
                                        echo isset($roles[$currentUser->getRole()]) ? $roles[$currentUser->getRole()] : $currentUser->getRole();
                                        ?>
                                    </small>
                                </span>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>/views/settings/profile.php">
                                    <i class="fas fa-id-card me-2"></i>
                                    الملف الشخصي
                                </a>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>/views/settings/password.php">
                                    <i class="fas fa-key me-2"></i>
                                    تغيير كلمة المرور
                                </a>
                                <?php if ($currentUser->isAdmin()): ?>
                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>/views/settings/users.php">
                                        <i class="fas fa-users-cog me-2"></i>
                                        إدارة المستخدمين
                                    </a>
                                    <a class="dropdown-item" href="<?php echo BASE_URL; ?>/views/settings/system.php">
                                        <i class="fas fa-cogs me-2"></i>
                                        إعدادات النظام
                                    </a>
                                <?php endif; ?>
                                <div class="dropdown-divider"></div>
                                <a class="dropdown-item" href="<?php echo BASE_URL; ?>/logout.php">
                                    <i class="fas fa-sign-out-alt me-2"></i>
                                    تسجيل الخروج
                                </a>
                            </div>
                        </li>
                    </ul>
                <?php endif; ?>
            </div>
        </div>
    </header>
    
    <!-- محتوى الصفحة مع الشريط الجانبي -->
    <div class="container-fluid mt-5 pt-3">
        <div class="row">
            <?php if ($showSidebar && isset($currentUser) && $currentUser): ?>
                <!-- عرض الشريط الجانبي -->
                <?php include TEMPLATES_PATH . '/sidebar.php'; ?>
                
                <!-- محتوى الصفحة الرئيسي -->
                <main class="col-md-9 col-lg-10 ms-md-auto px-4 py-3">
            <?php else: ?>
                <!-- محتوى الصفحة الرئيسي بدون شريط جانبي -->
                <main class="col-12 px-4 py-3">
            <?php endif; ?>
            
            <?php if (isset($pageTitle) && !empty($pageTitle)): ?>
                <div class="mb-4">
                    <h1 class="h2 mb-2"><?php echo htmlspecialchars($pageTitle); ?></h1>
                    <?php if (isset($pageDescription) && !empty($pageDescription)): ?>
                        <p class="text-muted"><?php echo htmlspecialchars($pageDescription); ?></p>
                    <?php endif; ?>
                    <hr>
                </div>
            <?php endif; ?>
            
            <?php 
            // عرض رسائل النجاح
            if (isset($_SESSION[SESSION_NAME]['success_message']) && !empty($_SESSION[SESSION_NAME]['success_message'])): 
            ?>
                <div class="alert alert-success alert-dismissible fade show mb-4">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php 
                    echo htmlspecialchars($_SESSION[SESSION_NAME]['success_message']); 
                    unset($_SESSION[SESSION_NAME]['success_message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
                </div>
            <?php endif; ?>
            
            <?php 
            // عرض رسائل الخطأ
            if (isset($_SESSION[SESSION_NAME]['error_message']) && !empty($_SESSION[SESSION_NAME]['error_message'])): 
            ?>
                <div class="alert alert-danger alert-dismissible fade show mb-4">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php 
                    echo htmlspecialchars($_SESSION[SESSION_NAME]['error_message']); 
                    unset($_SESSION[SESSION_NAME]['error_message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
                </div>
            <?php endif; ?>
