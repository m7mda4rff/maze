<?php
/**
 * صفحة قائمة الفعاليات
 * 
 * تعرض جميع الفعاليات مع إمكانية التصفية والبحث
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

// معالجة تصفية النتائج
$filters = [];
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($config['pagination_limit']) ? $config['pagination_limit'] : 20;
$offset = ($page - 1) * $limit;

// تصفية حسب الحالة
if (isset($_GET['status_id']) && !empty($_GET['status_id'])) {
    $filters['status_id'] = $_GET['status_id'];
}

// تصفية حسب نوع الفعالية
if (isset($_GET['event_type_id']) && !empty($_GET['event_type_id'])) {
    $filters['event_type_id'] = $_GET['event_type_id'];
}

// تصفية حسب التاريخ
if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $filters['date_from'] = $_GET['date_from'];
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $filters['date_to'] = $_GET['date_to'];
}

// تصفية حسب العميل
if (isset($_GET['customer_id']) && !empty($_GET['customer_id'])) {
    $filters['customer_id'] = $_GET['customer_id'];
}

// البحث
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// جلب قائمة الفعاليات
$events = $eventObj->getEvents($filters, $limit, $offset);

// عدد الفعاليات الكلي (للتصفح)
$totalEvents = $eventObj->countEvents($filters);
$totalPages = ceil($totalEvents / $limit);

// جلب قائمة حالات الفعاليات وأنواعها للتصفية
$eventStatuses = $eventObj->getEventStatuses();
$eventTypes = $eventObj->getEventTypes();

// إعداد عنوان الصفحة
$pageTitle = 'قائمة الفعاليات';

// تضمين قالب رأس الصفحة
include_once TEMPLATES_PATH . '/header.php';
?>

<!-- بداية محتوى الصفحة -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?php echo $pageTitle; ?></h1>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus-circle"></i> إضافة فعالية جديدة
        </a>
    </div>

    <!-- بطاقة تصفية النتائج -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">تصفية النتائج</h6>
        </div>
        <div class="card-body">
            <form method="get" action="index.php" class="row">
                <!-- حقل البحث -->
                <div class="col-md-3 mb-3">
                    <label for="search">بحث:</label>
                    <input type="text" class="form-control" id="search" name="search" placeholder="ابحث بالعنوان أو اسم العميل" value="<?php echo isset($_GET['search']) ? htmlspecialchars($_GET['search']) : ''; ?>">
                </div>
                
                <!-- تصفية حسب الحالة -->
                <div class="col-md-2 mb-3">
                    <label for="status_id">الحالة:</label>
                    <select class="form-control" id="status_id" name="status_id">
                        <option value="">جميع الحالات</option>
                        <?php foreach ($eventStatuses as $status): ?>
                        <option value="<?php echo $status['id']; ?>" <?php echo (isset($_GET['status_id']) && $_GET['status_id'] == $status['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($status['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- تصفية حسب نوع الفعالية -->
                <div class="col-md-2 mb-3">
                    <label for="event_type_id">نوع الفعالية:</label>
                    <select class="form-control" id="event_type_id" name="event_type_id">
                        <option value="">جميع الأنواع</option>
                        <?php foreach ($eventTypes as $type): ?>
                        <option value="<?php echo $type['id']; ?>" <?php echo (isset($_GET['event_type_id']) && $_GET['event_type_id'] == $type['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($type['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- تصفية حسب الفترة -->
                <div class="col-md-2 mb-3">
                    <label for="date_from">من تاريخ:</label>
                    <input type="date" class="form-control" id="date_from" name="date_from" value="<?php echo isset($_GET['date_from']) ? htmlspecialchars($_GET['date_from']) : ''; ?>">
                </div>
                
                <div class="col-md-2 mb-3">
                    <label for="date_to">إلى تاريخ:</label>
                    <input type="date" class="form-control" id="date_to" name="date_to" value="<?php echo isset($_GET['date_to']) ? htmlspecialchars($_GET['date_to']) : ''; ?>">
                </div>
                
                <!-- أزرار التصفية -->
                <div class="col-md-1 mb-3 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary">تصفية</button>
                </div>
            </form>
            
            <div class="mt-2">
                <a href="index.php" class="btn btn-secondary btn-sm">إعادة ضبط</a>
                <a href="calendar.php" class="btn btn-info btn-sm">عرض التقويم</a>
            </div>
        </div>
    </div>

    <!-- جدول الفعاليات -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">الفعاليات (<?php echo $totalEvents; ?>)</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-bordered table-hover" id="dataTable" width="100%" cellspacing="0">
                    <thead>
                        <tr>
                            <th width="5%">رقم</th>
                            <th width="15%">العنوان</th>
                            <th width="10%">التاريخ</th>
                            <th width="15%">العميل</th>
                            <th width="10%">نوع الفعالية</th>
                            <th width="10%">عدد الضيوف</th>
                            <th width="10%">تكلفة الباقة</th>
                            <th width="10%">الحالة</th>
                            <th width="15%">الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($events)): ?>
                        <tr>
                            <td colspan="9" class="text-center">لا توجد فعاليات</td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($events as $event): ?>
                            <tr>
                                <td><?php echo $event['id']; ?></td>
                                <td><?php echo htmlspecialchars($event['title']); ?></td>
                                <td><?php echo formatDate($event['date']); ?></td>
                                <td><?php echo htmlspecialchars($event['customer_name']); ?></td>
                                <td><?php echo htmlspecialchars($event['event_type_name']); ?></td>
                                <td><?php echo $event['guest_count']; ?></td>
                                <td><?php echo formatCurrency($event['total_package_cost']); ?></td>
                                <td>
                                    <span class="badge badge-<?php echo getStatusColor($event['status_name']); ?>">
                                        <?php echo htmlspecialchars($event['status_name']); ?>
                                    </span>
                                </td>
                                <td>
                                    <a href="view.php?id=<?php echo $event['id']; ?>" class="btn btn-info btn-sm" title="عرض التفاصيل">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $event['id']; ?>" class="btn btn-primary btn-sm" title="تعديل">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="#" class="btn btn-danger btn-sm delete-event" data-id="<?php echo $event['id']; ?>" data-title="<?php echo htmlspecialchars($event['title']); ?>" title="حذف">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- التصفح -->
            <?php if ($totalPages > 1): ?>
            <div class="d-flex justify-content-center">
                <nav aria-label="Page navigation">
                    <ul class="pagination">
                        <?php if ($page > 1): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo buildQueryParams($filters, ['page']); ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                        
                        <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php echo $page == $i ? 'active' : ''; ?>">
                            <a class="page-link" href="?page=<?php echo $i; ?><?php echo buildQueryParams($filters, ['page']); ?>">
                                <?php echo $i; ?>
                            </a>
                        </li>
                        <?php endfor; ?>
                        
                        <?php if ($page < $totalPages): ?>
                        <li class="page-item">
                            <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo buildQueryParams($filters, ['page']); ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- نافذة تأكيد الحذف -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">تأكيد الحذف</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                هل أنت متأكد من حذف الفعالية: <span id="eventTitle"></span>؟
                <p class="text-danger mt-2">هذا الإجراء لا يمكن التراجع عنه.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                <form id="deleteForm" method="post" action="delete.php">
                    <input type="hidden" name="event_id" id="eventId">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <button type="submit" class="btn btn-danger">تأكيد الحذف</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- دالة مساعدة للحصول على لون الحالة -->
<?php
function getStatusColor($statusName) {
    switch ($statusName) {
        case 'محجوزة':
            return 'warning';
        case 'قيد التنفيذ':
            return 'info';
        case 'منتهية':
            return 'success';
        case 'ملغاة':
            return 'danger';
        default:
            return 'secondary';
    }
}

// دالة مساعدة لبناء معلمات URL
function buildQueryParams($filters, $excludeParams = []) {
    $queryParams = [];
    
    foreach ($filters as $key => $value) {
        if (!in_array($key, $excludeParams) && !empty($value)) {
            $queryParams[] = $key . '=' . urlencode($value);
        }
    }
    
    return !empty($queryParams) ? '&' . implode('&', $queryParams) : '';
}
?>

<!-- سكريبت لنافذة تأكيد الحذف -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // الحصول على جميع أزرار الحذف
        var deleteButtons = document.querySelectorAll('.delete-event');
        
        // إضافة حدث النقر لكل زر
        deleteButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                
                // الحصول على معرف وعنوان الفعالية
                var eventId = this.getAttribute('data-id');
                var eventTitle = this.getAttribute('data-title');
                
                // تعيين القيم في النافذة
                document.getElementById('eventId').value = eventId;
                document.getElementById('eventTitle').textContent = eventTitle;
                
                // عرض النافذة
                $('#deleteModal').modal('show');
            });
        });
    });
</script>

<!-- نهاية محتوى الصفحة -->

<?php
// تضمين قالب تذييل الصفحة
include_once TEMPLATES_PATH . '/footer.php';
?>
