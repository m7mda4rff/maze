<?php
/**
 * صفحة لوحة التحكم الرئيسية
 * 
 * تعرض نظرة عامة على أداء النظام والإحصائيات
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
require_once '../classes/Customer.php';
require_once '../classes/Event.php';
require_once '../classes/ExternalCost.php';

// بدء الجلسة
init_session();

// التحقق من تسجيل دخول المستخدم
require_login();

// عنوان الصفحة
$page_title = 'لوحة التحكم';

// تاريخ اليوم
$today = date('Y-m-d');

// الحصول على معلومات المستخدم الحالي
$user = User::getCurrentUser();

// إنشاء كائنات الفئات
$customerObj = new Customer();
$eventObj = new Event();
$costObj = new ExternalCost();

// الحصول على إحصائيات العملاء
$customerStats = $customerObj->getCustomerStats();

// الحصول على إحصائيات الفعاليات
// (نفترض وجود دالة مماثلة في فئة Event)
$eventStats = [
    'upcoming' => $eventObj->countEvents(['status' => 'upcoming', 'date_from' => $today]),
    'today' => $eventObj->countEvents(['date' => $today]),
    'this_week' => $eventObj->countEvents(['date_from' => date('Y-m-d', strtotime('this week')), 'date_to' => date('Y-m-d', strtotime('this week +6 days'))]),
    'this_month' => $eventObj->countEvents(['date_from' => date('Y-m-01'), 'date_to' => date('Y-m-t')]),
    'cancelled' => $eventObj->countEvents(['status' => 'cancelled']),
    'completed' => $eventObj->countEvents(['status' => 'completed']),
    'recent' => $eventObj->getEvents(['limit' => 5])
];

// الحصول على إحصائيات مالية
// الشهر الحالي
$start_of_month = date('Y-m-01');
$end_of_month = date('Y-m-t');

// الإحصائيات المالية للشهر الحالي
$financialStats = $eventObj->getProfitabilityStats($start_of_month, $end_of_month);

// الحصول على تكاليف الشهر الحالي
$costStats = $costObj->getCostStats($start_of_month, $end_of_month);

// الحصول على رسم بياني للإيرادات والأرباح
// نفترض أن الدالة تعيد مصفوفة بالبيانات حسب الشهر
$chartData = $eventObj->getMonthlyProfitData(date('Y'));

// تضمين رأس الصفحة
include_once '../templates/header.php';
?>

<!-- بداية المحتوى الرئيسي -->
<div class="content-wrapper">
    <div class="container-fluid">
        <!-- رأس الصفحة -->
        <div class="row">
            <div class="col-12">
                <div class="page-title-box">
                    <h4 class="page-title">لوحة التحكم</h4>
                    <div class="page-title-left">
                        <ol class="breadcrumb m-0">
                            <li class="breadcrumb-item active">لوحة التحكم</li>
                        </ol>
                    </div>
                </div>
            </div>
        </div>

        <!-- رسالة الترحيب -->
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title mb-3">
                            <i class="bi bi-hand-thumbs-up-fill me-2 text-primary"></i>
                            مرحباً، <?php echo get_current_user_name(); ?>!
                        </h5>
                        <p>
                            أهلاً بك في نظام "ميز للضيافة". اليوم هو <?php echo format_date($today, 'l، j F Y'); ?>.
                            <?php if ($eventStats['today'] > 0): ?>
                                <strong>لديك <?php echo $eventStats['today']; ?> فعاليات اليوم.</strong>
                            <?php endif; ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <!-- بطاقات الإحصائيات السريعة -->
        <div class="row">
            <!-- فعاليات اليوم -->
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="flex-grow-1">
                                <h5 class="mb-2">فعاليات اليوم</h5>
                                <h3 class="mb-0"><?php echo $eventStats['today']; ?></h3>
                            </div>
                            <div class="avatar bg-primary text-white">
                                <i class="bi bi-calendar-check fs-3"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-primary text-white">
                        <a href="events/index.php?date=<?php echo $today; ?>" class="text-white text-decoration-none">
                            <small>عرض التفاصيل <i class="bi bi-arrow-left-circle"></i></small>
                        </a>
                    </div>
                </div>
            </div>

            <!-- فعاليات هذا الأسبوع -->
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="flex-grow-1">
                                <h5 class="mb-2">فعاليات الأسبوع</h5>
                                <h3 class="mb-0"><?php echo $eventStats['this_week']; ?></h3>
                            </div>
                            <div class="avatar bg-info text-white">
                                <i class="bi bi-calendar-week fs-3"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-info text-white">
                        <a href="events/index.php?period=week" class="text-white text-decoration-none">
                            <small>عرض التفاصيل <i class="bi bi-arrow-left-circle"></i></small>
                        </a>
                    </div>
                </div>
            </div>

            <!-- إجمالي العملاء -->
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="flex-grow-1">
                                <h5 class="mb-2">إجمالي العملاء</h5>
                                <h3 class="mb-0"><?php echo $customerStats['total']; ?></h3>
                            </div>
                            <div class="avatar bg-success text-white">
                                <i class="bi bi-people fs-3"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-success text-white">
                        <a href="customers/index.php" class="text-white text-decoration-none">
                            <small>عرض التفاصيل <i class="bi bi-arrow-left-circle"></i></small>
                        </a>
                    </div>
                </div>
            </div>

            <!-- إيرادات الشهر -->
            <div class="col-md-6 col-xl-3">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex">
                            <div class="flex-grow-1">
                                <h5 class="mb-2">إيرادات الشهر</h5>
                                <h3 class="mb-0"><?php echo format_currency($financialStats['total_revenue']); ?></h3>
                            </div>
                            <div class="avatar bg-warning text-white">
                                <i class="bi bi-cash-stack fs-3"></i>
                            </div>
                        </div>
                    </div>
                    <div class="card-footer bg-warning text-white">
                        <a href="reports/financial.php?period=month" class="text-white text-decoration-none">
                            <small>عرض التفاصيل <i class="bi bi-arrow-left-circle"></i></small>
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <!-- الفعاليات القادمة والأداء المالي -->
        <div class="row">
            <!-- الفعاليات القادمة -->
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header bg-transparent border-bottom">
                        <div class="d-flex align-items-center">
                            <h5 class="card-title mb-0 flex-grow-1">
                                <i class="bi bi-calendar-event me-2"></i>
                                الفعاليات القادمة
                            </h5>
                            <div class="flex-shrink-0">
                                <a href="events/index.php?status=upcoming" class="btn btn-sm btn-primary">
                                    عرض الكل
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($eventStats['recent'])): ?>
                            <div class="table-responsive">
                                <table class="table table-hover mb-0">
                                    <thead>
                                        <tr>
                                            <th>الفعالية</th>
                                            <th>العميل</th>
                                            <th>التاريخ</th>
                                            <th>الوقت</th>
                                            <th>الحالة</th>
                                            <th>إجراءات</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($eventStats['recent'] as $event): ?>
                                            <tr>
                                                <td><?php echo html_escape($event['title']); ?></td>
                                                <td><?php echo html_escape($event['customer_name']); ?></td>
                                                <td><?php echo format_date($event['date']); ?></td>
                                                <td><?php echo format_time($event['start_time']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo get_status_color($event['status_id']); ?>">
                                                        <?php echo html_escape($event['status_name']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <a href="events/view.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-info">
                                                        <i class="bi bi-eye"></i>
                                                    </a>
                                                    <a href="events/edit.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <div class="avatar avatar-lg bg-light-subtle mb-3">
                                    <i class="bi bi-calendar-x fs-2 text-muted"></i>
                                </div>
                                <h5>لا توجد فعاليات قادمة</h5>
                                <p class="text-muted">
                                    لم يتم العثور على فعاليات قادمة في النظام.
                                </p>
                                <a href="events/add.php" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i>
                                    إضافة فعالية جديدة
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- الأداء المالي -->
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header bg-transparent border-bottom">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-graph-up me-2"></i>
                            الأداء المالي لهذا الشهر
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="text-center">
                            <div class="row">
                                <div class="col-6">
                                    <div class="card mb-0 border-0">
                                        <div class="card-body py-2">
                                            <h5 class="text-muted mb-1">الإيرادات</h5>
                                            <h3 class="mb-0 text-primary"><?php echo format_currency($financialStats['total_revenue']); ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card mb-0 border-0">
                                        <div class="card-body py-2">
                                            <h5 class="text-muted mb-1">التكاليف</h5>
                                            <h3 class="mb-0 text-danger"><?php echo format_currency($financialStats['total_cost']); ?></h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            
                            <hr>
                            
                            <div class="row">
                                <div class="col-6">
                                    <div class="card mb-0 border-0">
                                        <div class="card-body py-2">
                                            <h5 class="text-muted mb-1">الأرباح</h5>
                                            <h3 class="mb-0 text-success"><?php echo format_currency($financialStats['total_profit']); ?></h3>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="card mb-0 border-0">
                                        <div class="card-body py-2">
                                            <h5 class="text-muted mb-1">هامش الربح</h5>
                                            <h3 class="mb-0 text-info"><?php echo number_format($financialStats['profit_margin'], 1); ?>%</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <div class="mt-4">
                            <h6 class="mb-3">التكاليف حسب النوع</h6>
                            <?php if (!empty($costStats['by_type'])): ?>
                                <?php foreach (array_slice($costStats['by_type'], 0, 4) as $costType): ?>
                                    <div class="mb-2">
                                        <div class="d-flex justify-content-between mb-1">
                                            <span><?php echo html_escape($costType['name']); ?></span>
                                            <span class="text-muted"><?php echo format_currency($costType['total_amount']); ?></span>
                                        </div>
                                        <div class="progress" style="height: 7px;">
                                            <?php 
                                            $percentage = 0;
                                            if ($costStats['total_costs'] > 0) {
                                                $percentage = ($costType['total_amount'] / $costStats['total_costs']) * 100;
                                            }
                                            ?>
                                            <div class="progress-bar" role="progressbar" style="width: <?php echo $percentage; ?>%;" 
                                                aria-valuenow="<?php echo $percentage; ?>" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <div class="text-center text-muted py-3">
                                    لا توجد تكاليف مسجلة لهذا الشهر.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- الرسم البياني والعملاء الجدد -->
        <div class="row">
            <!-- الرسم البياني -->
            <div class="col-xl-8">
                <div class="card">
                    <div class="card-header bg-transparent border-bottom">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-bar-chart me-2"></i>
                            الإيرادات والأرباح (<?php echo date('Y'); ?>)
                        </h5>
                    </div>
                    <div class="card-body">
                        <div style="height: 300px;">
                            <canvas id="financialChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- العملاء الجدد -->
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-header bg-transparent border-bottom">
                        <div class="d-flex align-items-center">
                            <h5 class="card-title mb-0 flex-grow-1">
                                <i class="bi bi-people me-2"></i>
                                العملاء الجدد
                            </h5>
                            <div class="flex-shrink-0">
                                <a href="customers/index.php" class="btn btn-sm btn-primary">
                                    عرض الكل
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($customerStats['recent'])): ?>
                            <ul class="list-group list-group-flush">
                                <?php foreach ($customerStats['recent'] as $customer): ?>
                                    <li class="list-group-item px-0 py-3">
                                        <div class="d-flex align-items-center">
                                            <div class="avatar avatar-sm bg-primary-subtle text-primary me-3">
                                                <?php echo strtoupper(substr($customer['name'], 0, 1)); ?>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h6 class="mb-1"><?php echo html_escape($customer['name']); ?></h6>
                                                <div class="text-muted small">
                                                    <i class="bi bi-phone me-1"></i>
                                                    <?php echo html_escape($customer['phone']); ?>
                                                </div>
                                            </div>
                                            <div class="flex-shrink-0">
                                                <small class="text-muted">
                                                    <?php echo date_diff_in($customer['created_at'], null, 'days'); ?> يوم
                                                </small>
                                            </div>
                                        </div>
                                    </li>
                                <?php endforeach; ?>
                            </ul>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <div class="avatar avatar-lg bg-light-subtle mb-3">
                                    <i class="bi bi-person-x fs-2 text-muted"></i>
                                </div>
                                <h5>لا يوجد عملاء جدد</h5>
                                <p class="text-muted">
                                    لم يتم إضافة عملاء جدد مؤخراً.
                                </p>
                                <a href="customers/add.php" class="btn btn-primary">
                                    <i class="bi bi-plus-circle me-1"></i>
                                    إضافة عميل جديد
                                </a>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- مصادر العملاء وأنواع الفعاليات -->
        <div class="row">
            <!-- مصادر العملاء -->
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header bg-transparent border-bottom">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-funnel me-2"></i>
                            مصادر العملاء
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($customerStats['by_source'])): ?>
                            <div style="height: 240px;">
                                <canvas id="customerSourceChart"></canvas>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <div class="avatar avatar-lg bg-light-subtle mb-3">
                                    <i class="bi bi-pie-chart fs-2 text-muted"></i>
                                </div>
                                <h5>لا توجد بيانات</h5>
                                <p class="text-muted">
                                    لا توجد بيانات كافية لعرض مصادر العملاء.
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- أنواع الفعاليات -->
            <div class="col-xl-6">
                <div class="card">
                    <div class="card-header bg-transparent border-bottom">
                        <h5 class="card-title mb-0">
                            <i class="bi bi-tags me-2"></i>
                            أنواع الفعاليات
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php 
                        // نفترض وجود بيانات إحصائية لأنواع الفعاليات
                        $eventTypeStats = [
                            ['name' => 'حفل زفاف', 'count' => 15, 'percentage' => 45],
                            ['name' => 'حفل تخرج', 'count' => 8, 'percentage' => 25],
                            ['name' => 'مناسبة شركات', 'count' => 5, 'percentage' => 15],
                            ['name' => 'مناسبة خاصة', 'count' => 5, 'percentage' => 15],
                        ];
                        ?>
                        
                        <?php if (!empty($eventTypeStats)): ?>
                            <div style="height: 240px;">
                                <canvas id="eventTypeChart"></canvas>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-4">
                                <div class="avatar avatar-lg bg-light-subtle mb-3">
                                    <i class="bi bi-pie-chart fs-2 text-muted"></i>
                                </div>
                                <h5>لا توجد بيانات</h5>
                                <p class="text-muted">
                                    لا توجد بيانات كافية لعرض أنواع الفعاليات.
                                </p>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- نهاية المحتوى الرئيسي -->

<!-- سكريبت الرسوم البيانية -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // بيانات الرسم البياني المالي
    var financialData = <?php echo json_encode($chartData); ?>;
    
    // بيانات مصادر العملاء
    var customerSourceData = <?php 
        $sourceLabels = [];
        $sourceCounts = [];
        $sourceColors = [];
        
        $colors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b', '#fd7e14', '#6f42c1', '#20c9a6'];
        
        if (!empty($customerStats['by_source'])) {
            foreach ($customerStats['by_source'] as $index => $source) {
                $sourceLabels[] = $source['name'];
                $sourceCounts[] = $source['count'];
                $sourceColors[] = $colors[$index % count($colors)];
            }
        }
        
        echo json_encode([
            'labels' => $sourceLabels,
            'data' => $sourceCounts,
            'colors' => $sourceColors
        ]);
    ?>;
    
    // بيانات أنواع الفعاليات
    var eventTypeData = <?php 
        $typeLabels = [];
        $typeCounts = [];
        $typeColors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#e74a3b'];
        
        if (!empty($eventTypeStats)) {
            foreach ($eventTypeStats as $index => $type) {
                $typeLabels[] = $type['name'];
                $typeCounts[] = $type['count'];
            }
        }
        
        echo json_encode([
            'labels' => $typeLabels,
            'data' => $typeCounts,
            'colors' => $typeColors
        ]);
    ?>;
    
    // إنشاء الرسم البياني المالي
    var ctx1 = document.getElementById('financialChart').getContext('2d');
    var financialChart = new Chart(ctx1, {
        type: 'bar',
        data: {
            labels: financialData.labels,
            datasets: [
                {
                    label: 'الإيرادات',
                    data: financialData.revenue,
                    backgroundColor: 'rgba(78, 115, 223, 0.5)',
                    borderColor: 'rgba(78, 115, 223, 1)',
                    borderWidth: 1
                },
                {
                    label: 'الأرباح',
                    data: financialData.profit,
                    backgroundColor: 'rgba(28, 200, 138, 0.5)',
                    borderColor: 'rgba(28, 200, 138, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });
    
    // إنشاء رسم بياني لمصادر العملاء
    var ctx2 = document.getElementById('customerSourceChart').getContext('2d');
    var customerSourceChart = new Chart(ctx2, {
        type: 'doughnut',
        data: {
            labels: customerSourceData.labels,
            datasets: [{
                data: customerSourceData.data,
                backgroundColor: customerSourceData.colors,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
    
    // إنشاء رسم بياني لأنواع الفعاليات
    var ctx3 = document.getElementById('eventTypeChart').getContext('2d');
    var eventTypeChart = new Chart(ctx3, {
        type: 'doughnut',
        data: {
            labels: eventTypeData.labels,
            datasets: [{
                data: eventTypeData.data,
                backgroundColor: eventTypeData.colors,
                borderWidth: 0
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            cutout: '65%',
            plugins: {
                legend: {
                    position: 'right'
                }
            }
        }
    });
});
</script>

<?php
// دالة مساعدة للحصول على لون حالة الفعالية
function get_status_color($status_id) {
    // نفترض أن هذه الحالات موجودة في قاعدة البيانات
    $status_colors = [
        1 => 'primary',   // محجوز
        2 => 'warning',   // قيد التنفيذ
        3 => 'success',   // منتهية
        4 => 'danger',    // ملغاة
        5 => 'info'       // مؤجلة
    ];
    
    return isset($status_colors[$status_id]) ? $status_colors[$status_id] : 'secondary';
}

// تضمين تذييل الصفحة
include_once '../templates/footer.php';
?>
