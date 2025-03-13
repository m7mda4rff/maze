<?php
/**
 * صفحة تقويم الفعاليات
 * 
 * تعرض الفعاليات بطريقة التقويم الشهري
 */

// تعريف ثابت للتحقق من صحة الوصول
define('BASEPATH', true);

// تضمين ملفات النظام الأساسية
require_once '../../../config/config.php';
require_once '../../../includes/session.php';
require_once '../../../includes/functions.php';
require_once '../../../classes/Database.php';
require_once '../../../classes/User.php';
require_once '../../../classes/Event.php';

// التحقق من تسجيل الدخول
$user = User::getCurrentUser();
if (!$user || !$user->isLoggedIn()) {
    redirect(BASE_URL . '/public/login.php');
}

// إنشاء كائن الفعاليات
$eventObj = new Event();

// تحديد الشهر والسنة للتقويم
if (isset($_GET['month']) && isset($_GET['year'])) {
    $month = (int)$_GET['month'];
    $year = (int)$_GET['year'];
} else {
    // استخدام الشهر والسنة الحالية كقيمة افتراضية
    $month = (int)date('m');
    $year = (int)date('Y');
}

// التأكد من صحة الشهر والسنة
if ($month < 1 || $month > 12) {
    $month = (int)date('m');
}

if ($year < 2020 || $year > 2030) {
    $year = (int)date('Y');
}

// تحديد أول يوم من الشهر
$firstDayOfMonth = mktime(0, 0, 0, $month, 1, $year);
$lastDayOfMonth = mktime(0, 0, 0, $month + 1, 0, $year);
$numDays = date('t', $firstDayOfMonth);
$startingDayOfWeek = date('N', $firstDayOfMonth); // 1 للاثنين، 7 للأحد

// جلب الفعاليات للشهر الحالي
$startDate = date('Y-m-d', $firstDayOfMonth);
$endDate = date('Y-m-d', $lastDayOfMonth);

$filters = [
    'date_from' => $startDate,
    'date_to' => $endDate
];

// تصفية حسب الحالة إذا تم تحديدها
if (isset($_GET['status_id']) && !empty($_GET['status_id'])) {
    $filters['status_id'] = $_GET['status_id'];
}

// جلب قائمة الفعاليات
$events = $eventObj->getEvents($filters);

// تنظيم الفعاليات حسب اليوم
$eventsByDay = [];
foreach ($events as $event) {
    $eventDate = $event['date'];
    $day = (int)date('j', strtotime($eventDate));
    
    if (!isset($eventsByDay[$day])) {
        $eventsByDay[$day] = [];
    }
    
    $eventsByDay[$day][] = $event;
}

// جلب قائمة حالات الفعاليات للتصفية
$eventStatuses = $eventObj->getEventStatuses();

// إعداد عنوان الصفحة
$pageTitle = 'تقويم الفعاليات: ' . date('F Y', $firstDayOfMonth);

// أسماء الأشهر بالعربية
$arabicMonths = [
    1 => 'يناير', 2 => 'فبراير', 3 => 'مارس', 4 => 'أبريل',
    5 => 'مايو', 6 => 'يونيو', 7 => 'يوليو', 8 => 'أغسطس',
    9 => 'سبتمبر', 10 => 'أكتوبر', 11 => 'نوفمبر', 12 => 'ديسمبر'
];

// حساب الشهر السابق والتالي
$prevMonth = $month - 1;
$prevYear = $year;
if ($prevMonth == 0) {
    $prevMonth = 12;
    $prevYear--;
}

$nextMonth = $month + 1;
$nextYear = $year;
if ($nextMonth == 13) {
    $nextMonth = 1;
    $nextYear++;
}

// تضمين قالب رأس الصفحة
include_once TEMPLATES_PATH . '/header.php';
?>

<!-- إضافة روابط إضافية للستايل -->
<style>
    .calendar {
        width: 100%;
        border-collapse: collapse;
    }
    .calendar th {
        background-color: #f8f9fc;
        color: #4e73df;
        text-align: center;
        padding: 10px;
        border: 1px solid #e3e6f0;
    }
    .calendar td {
        height: 120px;
        vertical-align: top;
        padding: 5px;
        border: 1px solid #e3e6f0;
        width: 14.28%;
    }
    .calendar .other-month {
        background-color: #f8f9fc;
        color: #b7b9cc;
    }
    .calendar .today {
        background-color: #fff3cd;
    }
    .calendar .day-number {
        font-weight: bold;
        margin-bottom: 5px;
        text-align: right;
    }
    .calendar .event {
        padding: 5px;
        margin-bottom: 5px;
        border-radius: 3px;
        font-size: 0.8rem;
    }
    .calendar .event-reserved {
        background-color: #fff3cd;
        border-left: 3px solid #ffc107;
    }
    .calendar .event-inprogress {
        background-color: #d1ecf1;
        border-left: 3px solid #17a2b8;
    }
    .calendar .event-completed {
        background-color: #d4edda;
        border-left: 3px solid #28a745;
    }
    .calendar .event-cancelled {
        background-color: #f8d7da;
        border-left: 3px solid #dc3545;
    }
    .calendar .event a {
        color: #333;
        text-decoration: none;
    }
    .calendar .event a:hover {
        text-decoration: underline;
    }
</style>

<!-- بداية محتوى الصفحة -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">تقويم الفعاليات: <?php echo $arabicMonths[$month] . ' ' . $year; ?></h1>
        <div>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-list"></i> قائمة الفعاليات
            </a>
            <a href="add.php" class="btn btn-primary">
                <i class="fas fa-plus-circle"></i> إضافة فعالية جديدة
            </a>
        </div>
    </div>

    <!-- بطاقة التنقل بين الشهور والتصفية -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">عرض التقويم</h6>
            <div class="dropdown no-arrow">
                <a class="dropdown-toggle" href="#" role="button" id="dropdownMenuLink" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                    <i class="fas fa-filter fa-sm fa-fw text-gray-400"></i> تصفية
                </a>
                <div class="dropdown-menu dropdown-menu-right shadow animated--fade-in" aria-labelledby="dropdownMenuLink">
                    <div class="dropdown-header">تصفية حسب الحالة:</div>
                    <a class="dropdown-item" href="calendar.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>">جميع الحالات</a>
                    <?php foreach ($eventStatuses as $status): ?>
                        <a class="dropdown-item" href="calendar.php?month=<?php echo $month; ?>&year=<?php echo $year; ?>&status_id=<?php echo $status['id']; ?>">
                            <?php echo htmlspecialchars($status['name']); ?>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <a href="calendar.php?month=<?php echo $prevMonth; ?>&year=<?php echo $prevYear; ?><?php echo isset($_GET['status_id']) ? '&status_id=' . $_GET['status_id'] : ''; ?>" class="btn btn-sm btn-outline-primary">
                    &laquo; <?php echo $arabicMonths[$prevMonth] . ' ' . $prevYear; ?>
                </a>
                <a href="calendar.php" class="btn btn-sm btn-primary">العودة للشهر الحالي</a>
                <a href="calendar.php?month=<?php echo $nextMonth; ?>&year=<?php echo $nextYear; ?><?php echo isset($_GET['status_id']) ? '&status_id=' . $_GET['status_id'] : ''; ?>" class="btn btn-sm btn-outline-primary">
                    <?php echo $arabicMonths[$nextMonth] . ' ' . $nextYear; ?> &raquo;
                </a>
            </div>
            
            <!-- عرض التقويم -->
            <table class="calendar">
                <thead>
                    <tr>
                        <th>الأحد</th>
                        <th>الاثنين</th>
                        <th>الثلاثاء</th>
                        <th>الأربعاء</th>
                        <th>الخميس</th>
                        <th>الجمعة</th>
                        <th>السبت</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // تعديل ترتيب أيام الأسبوع ليبدأ من الأحد (1) بدلاً من الاثنين
                    $startingDayOfWeek = $startingDayOfWeek % 7;
                    
                    // عدد الخانات الفارغة في بداية الشهر
                    $emptyDays = $startingDayOfWeek;
                    
                    // مؤشر ترقيم اليوم
                    $dayCounter = 1;
                    
                    // حساب عدد الأسابيع في الشهر
                    $numberOfWeeks = ceil(($numDays + $emptyDays) / 7);
                    
                    // إنشاء التقويم
                    for ($week = 0; $week < $numberOfWeeks; $week++) {
                        echo '<tr>';
                        
                        for ($day = 0; $day < 7; $day++) {
                            // تحديد ما إذا كان اليوم من الشهر الحالي أم لا
                            if (($week == 0 && $day < $emptyDays) || ($dayCounter > $numDays)) {
                                // يوم من الشهر السابق أو التالي
                                echo '<td class="other-month"></td>';
                            } else {
                                // تحديد ما إذا كان اليوم هو اليوم الحالي
                                $isToday = ($dayCounter == date('j') && $month == date('m') && $year == date('Y'));
                                $tdClass = $isToday ? 'today' : '';
                                
                                echo '<td class="' . $tdClass . '">';
                                echo '<div class="day-number">' . $dayCounter . '</div>';
                                
                                // عرض الفعاليات لهذا اليوم
                                if (isset($eventsByDay[$dayCounter])) {
                                    foreach ($eventsByDay[$dayCounter] as $event) {
                                        // تحديد فئة الفعالية بناءً على حالتها
                                        $eventClass = '';
                                        switch ($event['status_name']) {
                                            case 'محجوزة':
                                                $eventClass = 'event-reserved';
                                                break;
                                            case 'قيد التنفيذ':
                                                $eventClass = 'event-inprogress';
                                                break;
                                            case 'منتهية':
                                                $eventClass = 'event-completed';
                                                break;
                                            case 'ملغاة':
                                                $eventClass = 'event-cancelled';
                                                break;
                                        }
                                        
                                        // عرض معلومات الفعالية
                                        echo '<div class="event ' . $eventClass . '">';
                                        echo '<a href="view.php?id=' . $event['id'] . '" title="' . htmlspecialchars($event['title']) . '">';
                                        echo '<strong>' . formatTime($event['start_time']) . '</strong> - ';
                                        echo htmlspecialchars(limitText($event['title'], 20));
                                        echo '</a>';
                                        echo '</div>';
                                    }
                                }
                                
                                echo '</td>';
                                $dayCounter++;
                            }
                        }
                        
                        echo '</tr>';
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- دالة مساعدة لتقصير النص -->
<?php
function limitText($text, $length) {
    if (mb_strlen($text) > $length) {
        return mb_substr($text, 0, $length) . '...';
    }
    return $text;
}

function formatTime($time) {
    return date('h:i A', strtotime($time));
}
?>

<!-- نهاية محتوى الصفحة -->

<?php
// تضمين قالب تذييل الصفحة
include_once TEMPLATES_PATH . '/footer.php';
?>
