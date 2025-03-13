<?php
/**
 * قالب القائمة الجانبية
 * يستخدم في جميع صفحات العرض
 */

// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    exit('لا يمكن الوصول المباشر لهذا الملف');
}

// التحقق من تسجيل دخول المستخدم
if (!isset($currentUser) || !$currentUser) {
    return;
}

// تحديد القائمة النشطة
$activeMenu = isset($activeMenu) ? $activeMenu : '';
?>

<!-- الشريط الجانبي -->
<aside class="col-md-3 col-lg-2 d-md-block bg-light sidebar collapse">
    <div class="position-sticky pt-4">
        <ul class="nav flex-column">
            <!-- لوحة التحكم -->
            <li class="nav-item">
                <a class="nav-link <?php echo $activeMenu === 'dashboard' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/index.php">
                    <i class="fas fa-tachometer-alt me-2"></i>
                    لوحة التحكم
                </a>
            </li>
            
            <!-- الفعاليات -->
            <li class="nav-item">
                <a class="nav-link <?php echo $activeMenu === 'events' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/views/events/index.php">
                    <i class="fas fa-calendar-alt me-2"></i>
                    الفعاليات
                </a>
            </li>
            
            <!-- روابط فرعية للفعاليات -->
            <li class="nav-item">
                <ul class="nav flex-column sub-menu <?php echo $activeMenu === 'events' ? 'show' : ''; ?>">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeMenu === 'events-calendar' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/views/events/calendar.php">
                            <i class="far fa-calendar-alt me-2"></i>
                            التقويم
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeMenu === 'events-add' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/views/events/add.php">
                            <i class="fas fa-plus me-2"></i>
                            إضافة فعالية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeMenu === 'events-upcoming' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/views/events/index.php?status=upcoming">
                            <i class="fas fa-hourglass-start me-2"></i>
                            الفعاليات القادمة
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeMenu === 'events-completed' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/views/events/index.php?status=completed">
                            <i class="fas fa-check-circle me-2"></i>
                            الفعاليات المنتهية
                        </a>
                    </li>
                </ul>
            </li>
            
            <!-- العملاء -->
            <li class="nav-item">
                <a class="nav-link <?php echo $activeMenu === 'customers' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/views/customers/index.php">
                    <i class="fas fa-users me-2"></i>
                    العملاء
                </a>
            </li>
            
            <!-- روابط فرعية للعملاء -->
            <li class="nav-item">
                <ul class="nav flex-column sub-menu <?php echo $activeMenu === 'customers' ? 'show' : ''; ?>">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeMenu === 'customers-add' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/views/customers/add.php">
                            <i class="fas fa-user-plus me-2"></i>
                            إضافة عميل
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeMenu === 'customers-vip' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/views/customers/index.php?category=vip">
                            <i class="fas fa-crown me-2"></i>
                            العملاء VIP
                        </a>
                    </li>
                </ul>
            </li>
            
            <!-- التكاليف الخارجية -->
            <li class="nav-item">
                <a class="nav-link <?php echo $activeMenu === 'costs' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/views/costs/index.php">
                    <i class="fas fa-money-bill-wave me-2"></i>
                    التكاليف الخارجية
                </a>
            </li>
            
            <!-- المدفوعات -->
            <li class="nav-item">
                <a class="nav-link <?php echo $activeMenu === 'payments' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/views/payments/index.php">
                    <i class="fas fa-file-invoice-dollar me-2"></i>
                    المدفوعات
                </a>
            </li>
            
            <!-- المهام -->
            <li class="nav-item">
                <a class="nav-link <?php echo $activeMenu === 'tasks' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/views/tasks/index.php">
                    <i class="fas fa-tasks me-2"></i>
                    المهام
                    <?php
                    // عدد المهام المستحقة اليوم (يجب تنفيذ هذه الوظيفة)
                    $dueTodayTasks = 0; // استبدل هذا بالعدد الفعلي من قاعدة البيانات
                    if ($dueTodayTasks > 0):
                    ?>
                    <span class="badge bg-danger ms-1"><?php echo $dueTodayTasks; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            
            <!-- التقارير - عرضها فقط للمشرفين والمدراء -->
            <?php if ($currentUser->isManager()): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $activeMenu === 'reports' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/views/reports/index.php">
                    <i class="fas fa-chart-bar me-2"></i>
                    التقارير والإحصائيات
                </a>
            </li>
            
            <!-- روابط فرعية للتقارير -->
            <li class="nav-item">
                <ul class="nav flex-column sub-menu <?php echo $activeMenu === 'reports' ? 'show' : ''; ?>">
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeMenu === 'reports-events' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/views/reports/events.php">
                            <i class="fas fa-calendar-check me-2"></i>
                            تقارير الفعاليات
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeMenu === 'reports-customers' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/views/reports/customers.php">
                            <i class="fas fa-user-check me-2"></i>
                            تقارير العملاء
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeMenu === 'reports-financial' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/views/reports/financial.php">
                            <i class="fas fa-file-invoice me-2"></i>
                            التقارير المالية
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeMenu === 'reports-stats' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/views/reports/stats.php">
                            <i class="fas fa-chart-pie me-2"></i>
                            الإحصائيات العامة
                        </a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>
            
            <!-- الإعدادات - عرضها فقط للمشرفين والمدراء -->
            <?php if ($currentUser->isManager()): ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $activeMenu === 'settings' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/views/settings/index.php">
                    <i class="fas fa-cog me-2"></i>
                    الإعدادات
                </a>
            </li>
            
            <!-- روابط فرعية للإعدادات -->
            <li class="nav-item">
                <ul class="nav flex-column sub-menu <?php echo $activeMenu === 'settings' ? 'show' : ''; ?>">
                    <?php if ($currentUser->isAdmin()): ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeMenu === 'settings-users' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/views/settings/users.php">
                            <i class="fas fa-users-cog me-2"></i>
                            المستخدمون
                        </a>
                    </li>
                    <?php endif; ?>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeMenu === 'settings-sources' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/views/settings/sources.php">
                            <i class="fas fa-network-wired me-2"></i>
                            مصادر العملاء
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeMenu === 'settings-types' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/views/settings/types.php">
                            <i class="fas fa-list-alt me-2"></i>
                            أنواع الفعاليات
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link <?php echo $activeMenu === 'settings-costs' ? 'active' : ''; ?>" href="<?php echo BASE_URL; ?>/views/settings/cost-types.php">
                            <i class="fas fa-tags me-2"></i>
                            أنواع التكاليف
                        </a>
                    </li>
                </ul>
            </li>
            <?php endif; ?>
        </ul>
        
        <!-- معلومات النظام -->
        <div class="system-info mt-4 p-3 small">
            <hr>
            <p class="mb-1">
                <i class="fas fa-info-circle me-1"></i>
                <?php echo APP_NAME; ?> <?php echo APP_VERSION; ?>
            </p>
            <p class="mb-0 text-muted">
                © <?php echo date('Y'); ?> جميع الحقوق محفوظة
            </p>
        </div>
    </div>
</aside>
