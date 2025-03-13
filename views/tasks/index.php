<?php
/**
 * صفحة قائمة المهام
 * تعرض جميع المهام مع إمكانية التصفية حسب الحالة والأولوية والمستخدم
 */

// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    exit('لا يمكن الوصول المباشر لهذا الملف');
}

// التحقق من تسجيل الدخول
if (!isset($_SESSION[SESSION_NAME]['user_id'])) {
    redirect('login.php');
}

// تهيئة كائنات الفئات المطلوبة
$taskObj = new Task();
$userObj = new User();
$eventObj = new Event();

// الحصول على المستخدم الحالي
$currentUser = User::getCurrentUser();

// تحديد حالة التصفية الافتراضية (المهام النشطة)
$defaultStatus = 'pending';
if (isset($_GET['status']) && in_array($_GET['status'], ['all', 'pending', 'in_progress', 'completed', 'cancelled'])) {
    $defaultStatus = $_GET['status'];
}

// تحديد الأولوية (اختياري)
$priority = isset($_GET['priority']) ? $_GET['priority'] : '';

// تحديد المستخدم المعين (اختياري)
$assignedTo = isset($_GET['assigned_to']) ? (int)$_GET['assigned_to'] : 0;

// تحديد الفعالية (اختياري)
$eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

// تحديد كلمة البحث (اختياري)
$searchTerm = isset($_GET['search']) ? trim($_GET['search']) : '';

// بناء مرشحات البحث
$filters = [];

// إضافة حالة المهمة إلى المرشحات
if ($defaultStatus !== 'all') {
    $filters['status'] = $defaultStatus;
}

// إضافة الأولوية إلى المرشحات إذا تم تحديدها
if (!empty($priority)) {
    $filters['priority'] = $priority;
}

// إضافة المستخدم المعين إلى المرشحات إذا تم تحديده
if ($assignedTo > 0) {
    $filters['assigned_to'] = $assignedTo;
}

// إضافة الفعالية إلى المرشحات إذا تم تحديدها
if ($eventId > 0) {
    $filters['event_id'] = $eventId;
}

// إضافة كلمة البحث إلى المرشحات إذا تم إدخالها
if (!empty($searchTerm)) {
    $filters['search'] = $searchTerm;
}

// تحديد ما إذا كان المستخدم مديراً (يمكنه رؤية جميع المهام)
$isManager = $currentUser->isManager();

// إذا لم يكن المستخدم مديراً، قم بتقييد العرض إلى المهام المعينة له فقط
if (!$isManager && empty($filters['assigned_to'])) {
    $filters['assigned_to'] = $currentUser->getId();
}

// تحديد الصفحة الحالية للتصفح
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// عدد العناصر في كل صفحة
$itemsPerPage = isset($config['pagination_limit']) ? $config['pagination_limit'] : 20;

// حساب موضع البداية
$offset = ($page - 1) * $itemsPerPage;

// الحصول على قائمة المهام مع التصفية
$tasks = $taskObj->getTasks($filters, $itemsPerPage, $offset);

// الحصول على إجمالي عدد المهام للتصفح
$totalTasks = $taskObj->countTasks($filters);

// حساب عدد الصفحات
$totalPages = ceil($totalTasks / $itemsPerPage);

// الحصول على قائمة المستخدمين للعرض في قائمة التصفية
$users = $isManager ? $userObj->getUsers() : [];

// إذا تم طلب الفعالية، احصل على بياناتها
$event = null;
if ($eventId > 0) {
    $event = new Event();
    $event->loadEventById($eventId);
}

// تضمين رأس الصفحة
include TEMPLATES_PATH . '/header.php';
?>

<div class="container-fluid py-4">
    <div class="row mb-4">
        <div class="col-md-6">
            <h1 class="h3 mb-2 text-gray-800">قائمة المهام</h1>
            <?php if ($event): ?>
                <p class="mb-0">المهام المرتبطة بالفعالية: <a href="<?= BASE_URL ?>/views/events/view.php?id=<?= $event->getId() ?>"><?= $event->getTitle() ?></a></p>
            <?php endif; ?>
        </div>
        <div class="col-md-6 text-left">
            <a href="<?= BASE_URL ?>/views/tasks/add.php<?= $eventId ? '?event_id=' . $eventId : '' ?>" class="btn btn-primary">
                <i class="fas fa-plus"></i> إضافة مهمة جديدة
            </a>
        </div>
    </div>

    <!-- بطاقة تصفية المهام -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">تصفية المهام</h6>
            <a data-toggle="collapse" href="#filterCollapse" role="button" aria-expanded="true" aria-controls="filterCollapse">
                <i class="fas fa-chevron-down"></i>
            </a>
        </div>
        <div class="collapse show" id="filterCollapse">
            <div class="card-body">
                <form method="get" action="<?= BASE_URL ?>/views/tasks/index.php">
                    <?php if ($eventId): ?>
                        <input type="hidden" name="event_id" value="<?= $eventId ?>">
                    <?php endif; ?>
                    
                    <div class="row">
                        <!-- تصفية حسب الحالة -->
                        <div class="col-md-3 mb-3">
                            <label for="status">الحالة</label>
                            <select name="status" id="status" class="form-control">
                                <option value="all" <?= $defaultStatus === 'all' ? 'selected' : '' ?>>جميع المهام</option>
                                <option value="pending" <?= $defaultStatus === 'pending' ? 'selected' : '' ?>>قيد الانتظار</option>
                                <option value="in_progress" <?= $defaultStatus === 'in_progress' ? 'selected' : '' ?>>قيد التنفيذ</option>
                                <option value="completed" <?= $defaultStatus === 'completed' ? 'selected' : '' ?>>مكتملة</option>
                                <option value="cancelled" <?= $defaultStatus === 'cancelled' ? 'selected' : '' ?>>ملغاة</option>
                            </select>
                        </div>
                        
                        <!-- تصفية حسب الأولوية -->
                        <div class="col-md-3 mb-3">
                            <label for="priority">الأولوية</label>
                            <select name="priority" id="priority" class="form-control">
                                <option value="">جميع الأولويات</option>
                                <option value="high" <?= $priority === 'high' ? 'selected' : '' ?>>عالية</option>
                                <option value="medium" <?= $priority === 'medium' ? 'selected' : '' ?>>متوسطة</option>
                                <option value="low" <?= $priority === 'low' ? 'selected' : '' ?>>منخفضة</option>
                            </select>
                        </div>
                        
                        <!-- تصفية حسب المستخدم المعين (للمدراء فقط) -->
                        <?php if ($isManager): ?>
                        <div class="col-md-3 mb-3">
                            <label for="assigned_to">معين إلى</label>
                            <select name="assigned_to" id="assigned_to" class="form-control">
                                <option value="">جميع المستخدمين</option>
                                <option value="<?= $currentUser->getId() ?>" <?= $assignedTo === $currentUser->getId() ? 'selected' : '' ?>>مهامي</option>
                                <?php foreach ($users as $user): ?>
                                    <?php if ($user['id'] != $currentUser->getId()): ?>
                                        <option value="<?= $user['id'] ?>" <?= $assignedTo == $user['id'] ? 'selected' : '' ?>><?= $user['name'] ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <?php endif; ?>
                        
                        <!-- البحث حسب النص -->
                        <div class="col-md-3 mb-3">
                            <label for="search">بحث</label>
                            <input type="text" name="search" id="search" class="form-control" placeholder="عنوان المهمة أو الوصف" value="<?= htmlspecialchars($searchTerm) ?>">
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-12">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-filter"></i> تصفية
                            </button>
                            <a href="<?= BASE_URL ?>/views/tasks/index.php<?= $eventId ? '?event_id=' . $eventId : '' ?>" class="btn btn-secondary">
                                <i class="fas fa-sync"></i> إعادة تعيين
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- جدول المهام -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">المهام (<?= $totalTasks ?>)</h6>
        </div>
        <div class="card-body">
            <?php if (empty($tasks)): ?>
                <div class="alert alert-info">
                    لا توجد مهام متطابقة مع معايير البحث.
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="tasksTable" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th width="5%">#</th>
                                <th width="20%">العنوان</th>
                                <th width="15%">الفعالية</th>
                                <th width="10%">تاريخ الاستحقاق</th>
                                <th width="10%">الأولوية</th>
                                <th width="10%">الحالة</th>
                                <th width="15%">معين إلى</th>
                                <th width="15%">الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($tasks as $task): ?>
                                <?php 
                                    // تحديد لون صف المهمة حسب الحالة والأولوية
                                    $rowClass = '';
                                    if ($task['status'] === 'completed') {
                                        $rowClass = 'table-success';
                                    } elseif ($task['status'] === 'cancelled') {
                                        $rowClass = 'table-secondary';
                                    } elseif ($task['priority'] === 'high' && $task['status'] !== 'completed') {
                                        $rowClass = 'table-danger';
                                    }
                                    
                                    // تنسيق الأولوية
                                    $priorityClass = '';
                                    $priorityText = '';
                                    if ($task['priority'] === 'high') {
                                        $priorityClass = 'badge badge-danger';
                                        $priorityText = 'عالية';
                                    } elseif ($task['priority'] === 'medium') {
                                        $priorityClass = 'badge badge-warning';
                                        $priorityText = 'متوسطة';
                                    } else {
                                        $priorityClass = 'badge badge-info';
                                        $priorityText = 'منخفضة';
                                    }
                                    
                                    // تنسيق الحالة
                                    $statusClass = '';
                                    $statusText = '';
                                    if ($task['status'] === 'pending') {
                                        $statusClass = 'badge badge-secondary';
                                        $statusText = 'قيد الانتظار';
                                    } elseif ($task['status'] === 'in_progress') {
                                        $statusClass = 'badge badge-primary';
                                        $statusText = 'قيد التنفيذ';
                                    } elseif ($task['status'] === 'completed') {
                                        $statusClass = 'badge badge-success';
                                        $statusText = 'مكتملة';
                                    } elseif ($task['status'] === 'cancelled') {
                                        $statusClass = 'badge badge-dark';
                                        $statusText = 'ملغاة';
                                    }
                                    
                                    // تحديد تاريخ الانتهاء المتأخر
                                    $isOverdue = false;
                                    if ($task['due_date'] && $task['status'] !== 'completed' && $task['status'] !== 'cancelled') {
                                        $dueDate = new DateTime($task['due_date']);
                                        $today = new DateTime();
                                        $isOverdue = ($dueDate < $today);
                                    }
                                ?>
                                <tr class="<?= $rowClass ?>">
                                    <td><?= $task['id'] ?></td>
                                    <td>
                                        <?= htmlspecialchars($task['title']) ?>
                                        <?php if (!empty($task['description'])): ?>
                                            <i class="fas fa-info-circle text-info" data-toggle="tooltip" title="<?= htmlspecialchars($task['description']) ?>"></i>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/views/events/view.php?id=<?= $task['event_id'] ?>">
                                            <?= htmlspecialchars($task['event_title']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?php if ($task['due_date']): ?>
                                            <?= date(DATE_FORMAT, strtotime($task['due_date'])) ?>
                                            <?php if ($isOverdue): ?>
                                                <span class="badge badge-danger">متأخر</span>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            -
                                        <?php endif; ?>
                                    </td>
                                    <td><span class="<?= $priorityClass ?>"><?= $priorityText ?></span></td>
                                    <td><span class="<?= $statusClass ?>"><?= $statusText ?></span></td>
                                    <td><?= htmlspecialchars($task['assigned_to_name'] ?: '-') ?></td>
                                    <td>
                                        <a href="<?= BASE_URL ?>/views/tasks/edit.php?id=<?= $task['id'] ?>" class="btn btn-sm btn-primary">
                                            <i class="fas fa-edit"></i> تعديل
                                        </a>
                                        
                                        <?php if ($task['status'] !== 'completed' && ($isManager || $task['assigned_to'] == $currentUser->getId())): ?>
                                            <a href="<?= BASE_URL ?>/views/tasks/edit.php?id=<?= $task['id'] ?>&action=complete" class="btn btn-sm btn-success">
                                                <i class="fas fa-check"></i> إكمال
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                
                <!-- شريط التصفح -->
                <?php if ($totalPages > 1): ?>
                    <div class="d-flex justify-content-center mt-4">
                        <nav aria-label="صفحات المهام">
                            <ul class="pagination">
                                <?php
                                    // بناء عنوان URL للتصفح مع الحفاظ على معايير التصفية
                                    $queryParams = $_GET;
                                    
                                    // زر الصفحة السابقة
                                    if ($page > 1):
                                        $queryParams['page'] = $page - 1;
                                        $prevUrl = BASE_URL . '/views/tasks/index.php?' . http_build_query($queryParams);
                                ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= $prevUrl ?>" aria-label="السابق">
                                            <span aria-hidden="true">&laquo;</span>
                                            <span class="sr-only">السابق</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php
                                    // أزرار الصفحات
                                    $startPage = max(1, $page - 2);
                                    $endPage = min($totalPages, $page + 2);
                                    
                                    for ($i = $startPage; $i <= $endPage; $i++):
                                        $queryParams['page'] = $i;
                                        $pageUrl = BASE_URL . '/views/tasks/index.php?' . http_build_query($queryParams);
                                ?>
                                    <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                        <a class="page-link" href="<?= $pageUrl ?>"><?= $i ?></a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php
                                    // زر الصفحة التالية
                                    if ($page < $totalPages):
                                        $queryParams['page'] = $page + 1;
                                        $nextUrl = BASE_URL . '/views/tasks/index.php?' . http_build_query($queryParams);
                                ?>
                                    <li class="page-item">
                                        <a class="page-link" href="<?= $nextUrl ?>" aria-label="التالي">
                                            <span aria-hidden="true">&raquo;</span>
                                            <span class="sr-only">التالي</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    </div>
                <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // تفعيل tooltips لوصف المهام
        $('[data-toggle="tooltip"]').tooltip();
    });
</script>

<?php
// تضمين تذييل الصفحة
include TEMPLATES_PATH . '/footer.php';
?>
