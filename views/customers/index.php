<?php
/**
 * صفحة قائمة العملاء
 * تعرض جميع العملاء مع إمكانية البحث والتصفية
 */

// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    define('BASEPATH', true);
}

// استيراد الملفات المطلوبة
require_once '../../../includes/auth.php';
require_once '../../../classes/Customer.php';

// التحقق من تسجيل الدخول
$user = User::getCurrentUser();
if (!$user || !$user->isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// إنشاء كائن العميل
$customerObj = new Customer();

// تهيئة المتغيرات
$pageTitle = 'قائمة العملاء';
$currentPage = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 20;
$offset = ($currentPage - 1) * $limit;

// معالجة المرشحات
$filters = [];

// البحث
if (isset($_GET['search']) && !empty($_GET['search'])) {
    $filters['search'] = $_GET['search'];
}

// التصفية حسب التصنيف
if (isset($_GET['category']) && !empty($_GET['category'])) {
    $filters['category'] = $_GET['category'];
}

// التصفية حسب المصدر
if (isset($_GET['source_id']) && !empty($_GET['source_id'])) {
    $filters['source_id'] = (int)$_GET['source_id'];
}

// الحصول على مصادر العملاء
$customerSources = $customerObj->getCustomerSources();

// الحصول على قائمة العملاء
$customers = $customerObj->getCustomers($filters, $limit, $offset);

// الحصول على إجمالي عدد العملاء
$totalCustomers = $customerObj->countCustomers($filters);
$totalPages = ceil($totalCustomers / $limit);

// عند الحذف
$deleteSuccess = false;
$deleteError = false;

if (isset($_POST['delete_customer']) && isset($_POST['customer_id']) && !empty($_POST['customer_id'])) {
    // التحقق من صلاحيات الحذف
    if ($user->hasPermission(['admin', 'manager'])) {
        // حذف العميل
        $deleteResult = $customerObj->delete($_POST['customer_id']);
        
        if ($deleteResult) {
            $deleteSuccess = true;
            // إعادة تحميل قائمة العملاء
            $customers = $customerObj->getCustomers($filters, $limit, $offset);
            $totalCustomers = $customerObj->countCustomers($filters);
            $totalPages = ceil($totalCustomers / $limit);
        } else {
            $deleteError = 'حدث خطأ أثناء حذف العميل. قد يكون لديه فعاليات مرتبطة.';
        }
    } else {
        $deleteError = 'ليس لديك صلاحية لحذف العملاء.';
    }
}

// تضمين قالب الهيدر
include '../../../templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?php echo $pageTitle; ?></h1>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus ml-1"></i> إضافة عميل جديد
        </a>
    </div>

    <?php if ($deleteSuccess): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        تم حذف العميل بنجاح.
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
    </div>
    <?php endif; ?>

    <?php if ($deleteError): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?php echo $deleteError; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
    </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold">البحث والتصفية</h6>
        </div>
        <div class="card-body">
            <form method="get" action="" class="row g-3">
                <div class="col-md-4">
                    <label for="search" class="form-label">بحث:</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="اسم العميل، رقم الهاتف، البريد الإلكتروني" 
                           value="<?php echo isset($filters['search']) ? htmlentities($filters['search']) : ''; ?>">
                </div>
                <div class="col-md-3">
                    <label for="category" class="form-label">التصنيف:</label>
                    <select class="form-select" id="category" name="category">
                        <option value="">جميع التصنيفات</option>
                        <option value="vip" <?php echo (isset($filters['category']) && $filters['category'] === 'vip') ? 'selected' : ''; ?>>VIP</option>
                        <option value="regular" <?php echo (isset($filters['category']) && $filters['category'] === 'regular') ? 'selected' : ''; ?>>منتظم</option>
                        <option value="new" <?php echo (isset($filters['category']) && $filters['category'] === 'new') ? 'selected' : ''; ?>>جديد</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="source_id" class="form-label">المصدر:</label>
                    <select class="form-select" id="source_id" name="source_id">
                        <option value="">جميع المصادر</option>
                        <?php foreach ($customerSources as $source): ?>
                        <option value="<?php echo $source['id']; ?>" <?php echo (isset($filters['source_id']) && $filters['source_id'] == $source['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlentities($source['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-search ml-1"></i> بحث
                    </button>
                </div>
            </form>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold">قائمة العملاء (<?php echo $totalCustomers; ?>)</h6>
            <div>
                <select class="form-select form-select-sm d-inline-block w-auto" id="limit-select" onchange="window.location.href='?limit='+this.value<?php echo isset($filters['search']) ? '&search='.urlencode($filters['search']) : ''; ?><?php echo isset($filters['category']) ? '&category='.urlencode($filters['category']) : ''; ?><?php echo isset($filters['source_id']) ? '&source_id='.urlencode($filters['source_id']) : ''; ?>'">
                    <option value="10" <?php echo $limit == 10 ? 'selected' : ''; ?>>10</option>
                    <option value="20" <?php echo $limit == 20 ? 'selected' : ''; ?>>20</option>
                    <option value="50" <?php echo $limit == 50 ? 'selected' : ''; ?>>50</option>
                    <option value="100" <?php echo $limit == 100 ? 'selected' : ''; ?>>100</option>
                </select>
            </div>
        </div>
        <div class="card-body">
            <?php if (empty($customers)): ?>
            <div class="alert alert-info">
                لا يوجد عملاء مطابقين لمعايير البحث.
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-bordered table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>الاسم</th>
                            <th>رقم الهاتف</th>
                            <th>البريد الإلكتروني</th>
                            <th>المصدر</th>
                            <th>التصنيف</th>
                            <th>عدد الفعاليات</th>
                            <th>الإجراءات</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($customers as $customer): ?>
                        <tr>
                            <td><?php echo $customer['id']; ?></td>
                            <td>
                                <a href="view.php?id=<?php echo $customer['id']; ?>">
                                    <?php echo htmlentities($customer['name']); ?>
                                </a>
                            </td>
                            <td dir="ltr"><?php echo htmlentities($customer['phone']); ?></td>
                            <td><?php echo htmlentities($customer['email'] ?: '-'); ?></td>
                            <td><?php echo htmlentities($customer['source_name'] ?: '-'); ?></td>
                            <td>
                                <?php 
                                $categoryLabels = [
                                    'vip' => '<span class="badge bg-primary">VIP</span>',
                                    'regular' => '<span class="badge bg-secondary">منتظم</span>',
                                    'new' => '<span class="badge bg-success">جديد</span>'
                                ];
                                echo isset($categoryLabels[$customer['category']]) ? $categoryLabels[$customer['category']] : '-';
                                ?>
                            </td>
                            <?php 
                            // الحصول على عدد الفعاليات لكل عميل
                            $eventCount = $customerObj->db->fetchValue(
                                'SELECT COUNT(*) FROM events WHERE customer_id = ?', 
                                [$customer['id']]
                            ); 
                            ?>
                            <td>
                                <?php if ($eventCount > 0): ?>
                                <a href="../events/index.php?customer_id=<?php echo $customer['id']; ?>">
                                    <?php echo $eventCount; ?>
                                </a>
                                <?php else: ?>
                                0
                                <?php endif; ?>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    <a href="view.php?id=<?php echo $customer['id']; ?>" class="btn btn-info" title="عرض">
                                        <i class="fas fa-eye"></i>
                                    </a>
                                    <a href="edit.php?id=<?php echo $customer['id']; ?>" class="btn btn-warning" title="تعديل">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <?php if ($user->hasPermission(['admin', 'manager'])): ?>
                                    <button type="button" class="btn btn-danger" title="حذف" 
                                            data-bs-toggle="modal" data-bs-target="#deleteModal<?php echo $customer['id']; ?>">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                    <?php endif; ?>
                                </div>

                                <!-- نافذة تأكيد الحذف -->
                                <div class="modal fade" id="deleteModal<?php echo $customer['id']; ?>" tabindex="-1" aria-labelledby="deleteModalLabel<?php echo $customer['id']; ?>" aria-hidden="true">
                                    <div class="modal-dialog">
                                        <div class="modal-content">
                                            <div class="modal-header">
                                                <h5 class="modal-title" id="deleteModalLabel<?php echo $customer['id']; ?>">تأكيد الحذف</h5>
                                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="إغلاق"></button>
                                            </div>
                                            <div class="modal-body">
                                                <p>هل أنت متأكد من حذف العميل: <strong><?php echo htmlentities($customer['name']); ?></strong>؟</p>
                                                <?php if ($eventCount > 0): ?>
                                                <div class="alert alert-warning">
                                                    <i class="fas fa-exclamation-triangle ml-1"></i>
                                                    هذا العميل لديه <?php echo $eventCount; ?> فعالية مرتبطة. حذف العميل سيؤثر على هذه البيانات.
                                                </div>
                                                <?php endif; ?>
                                            </div>
                                            <div class="modal-footer">
                                                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">إلغاء</button>
                                                <form method="post">
                                                    <input type="hidden" name="customer_id" value="<?php echo $customer['id']; ?>">
                                                    <button type="submit" name="delete_customer" class="btn btn-danger">تأكيد الحذف</button>
                                                </form>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <?php if ($totalPages > 1): ?>
            <!-- ترقيم الصفحات -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center">
                    <?php if ($currentPage > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=1<?php echo isset($filters['search']) ? '&search='.urlencode($filters['search']) : ''; ?><?php echo isset($filters['category']) ? '&category='.urlencode($filters['category']) : ''; ?><?php echo isset($filters['source_id']) ? '&source_id='.urlencode($filters['source_id']) : ''; ?><?php echo $limit != 20 ? '&limit='.$limit : ''; ?>" aria-label="First">
                            <span aria-hidden="true">&laquo;&laquo;</span>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $currentPage - 1; ?><?php echo isset($filters['search']) ? '&search='.urlencode($filters['search']) : ''; ?><?php echo isset($filters['category']) ? '&category='.urlencode($filters['category']) : ''; ?><?php echo isset($filters['source_id']) ? '&source_id='.urlencode($filters['source_id']) : ''; ?><?php echo $limit != 20 ? '&limit='.$limit : ''; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php endif; ?>

                    <?php
                    // عرض أرقام الصفحات
                    $startPage = max(1, $currentPage - 2);
                    $endPage = min($totalPages, $currentPage + 2);

                    for ($i = $startPage; $i <= $endPage; $i++):
                    ?>
                    <li class="page-item <?php echo $i == $currentPage ? 'active' : ''; ?>">
                        <a class="page-link" href="?page=<?php echo $i; ?><?php echo isset($filters['search']) ? '&search='.urlencode($filters['search']) : ''; ?><?php echo isset($filters['category']) ? '&category='.urlencode($filters['category']) : ''; ?><?php echo isset($filters['source_id']) ? '&source_id='.urlencode($filters['source_id']) : ''; ?><?php echo $limit != 20 ? '&limit='.$limit : ''; ?>"><?php echo $i; ?></a>
                    </li>
                    <?php endfor; ?>

                    <?php if ($currentPage < $totalPages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $currentPage + 1; ?><?php echo isset($filters['search']) ? '&search='.urlencode($filters['search']) : ''; ?><?php echo isset($filters['category']) ? '&category='.urlencode($filters['category']) : ''; ?><?php echo isset($filters['source_id']) ? '&source_id='.urlencode($filters['source_id']) : ''; ?><?php echo $limit != 20 ? '&limit='.$limit : ''; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $totalPages; ?><?php echo isset($filters['search']) ? '&search='.urlencode($filters['search']) : ''; ?><?php echo isset($filters['category']) ? '&category='.urlencode($filters['category']) : ''; ?><?php echo isset($filters['source_id']) ? '&source_id='.urlencode($filters['source_id']) : ''; ?><?php echo $limit != 20 ? '&limit='.$limit : ''; ?>" aria-label="Last">
                            <span aria-hidden="true">&raquo;&raquo;</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- تضمين قالب الفوتر -->
<?php include '../../../templates/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // تنشيط الصفحة في القائمة الجانبية
    const sidebarItems = document.querySelectorAll('.nav-item');
    sidebarItems.forEach(function(item) {
        if (item.querySelector('a').getAttribute('href').includes('customers/')) {
            item.classList.add('active');
        }
    });
});
</script>
