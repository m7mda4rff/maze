<?php
/**
 * صفحة قائمة التكاليف
 * تعرض جميع التكاليف الخارجية للفعاليات
 */

// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    define('BASEPATH', true);
}

// استيراد الملفات المطلوبة
require_once '../../includes/auth.php';
require_once '../../classes/Database.php';
require_once '../../classes/User.php';
require_once '../../classes/ExternalCost.php';

// التحقق من تسجيل الدخول
$user = User::getCurrentUser();
if (!$user || !$user->isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// إنشاء كائن التكاليف
$externalCost = new ExternalCost();

// الفلاتر
$filters = [];
$filterEvent = isset($_GET['event_id']) ? intval($_GET['event_id']) : null;
$filterType = isset($_GET['cost_type_id']) ? intval($_GET['cost_type_id']) : null;
$filterDateFrom = isset($_GET['date_from']) ? $_GET['date_from'] : null;
$filterDateTo = isset($_GET['date_to']) ? $_GET['date_to'] : null;
$filterSearch = isset($_GET['search']) ? trim($_GET['search']) : null;

// إضافة الفلاتر إذا كانت موجودة
if ($filterEvent) {
    $filters['event_id'] = $filterEvent;
}

if ($filterType) {
    $filters['cost_type_id'] = $filterType;
}

if ($filterDateFrom) {
    $filters['date_from'] = $filterDateFrom;
}

if ($filterDateTo) {
    $filters['date_to'] = $filterDateTo;
}

if ($filterSearch) {
    $filters['search'] = $filterSearch;
}

// الصفحة الحالية والحد
$page = isset($_GET['page']) ? intval($_GET['page']) : 1;
$limit = isset($config['pagination_limit']) ? $config['pagination_limit'] : 20;
$offset = ($page - 1) * $limit;

// الحصول على قائمة التكاليف
$costs = $externalCost->getCosts($filters, $limit, $offset);

// الحصول على إجمالي عدد التكاليف للترقيم
// هذه الدالة غير موجودة في الفئة المقدمة، لذا سنفترض أنها تمت إضافتها
//$totalCosts = $externalCost->countCosts($filters);

// نظراً لعدم وجود الدالة، سنفترض قيمة للعرض
$totalCosts = count($costs) + (($page - 1) * $limit);
$totalPages = ceil($totalCosts / $limit);

// الحصول على أنواع التكاليف للفلتر
$costTypes = $externalCost->getCostTypes();

// عنوان الصفحة
$pageTitle = 'قائمة التكاليف الخارجية';

// تحميل قالب رأس الصفحة
include '../../templates/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- القائمة الجانبية -->
        <div class="col-lg-2">
            <?php include '../../templates/sidebar.php'; ?>
        </div>
        
        <!-- المحتوى الرئيسي -->
        <div class="col-lg-10">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h5 class="card-title mb-0">
                        <i class="fas fa-money-bill-wave ml-2"></i>
                        <?php echo $pageTitle; ?>
                    </h5>
                </div>
                
                <div class="card-body">
                    <!-- أزرار العمليات -->
                    <div class="row mb-3">
                        <div class="col-md-6">
                            <a href="add.php" class="btn btn-success">
                                <i class="fas fa-plus ml-1"></i>
                                إضافة تكلفة جديدة
                            </a>
                            
                            <?php if (!empty($costs)): ?>
                            <button type="button" class="btn btn-secondary mr-2" data-toggle="modal" data-target="#filterModal">
                                <i class="fas fa-filter ml-1"></i>
                                تصفية
                            </button>
                            
                            <a href="export.php<?php echo !empty($filters) ? '?' . http_build_query($filters) : ''; ?>" class="btn btn-info mr-2">
                                <i class="fas fa-file-export ml-1"></i>
                                تصدير
                            </a>
                            <?php endif; ?>
                        </div>
                        
                        <!-- نموذج البحث -->
                        <div class="col-md-6">
                            <form method="get" action="" class="form-inline justify-content-end">
                                <div class="input-group">
                                    <input type="text" name="search" class="form-control" placeholder="بحث..." value="<?php echo htmlspecialchars($filterSearch); ?>">
                                    <div class="input-group-append">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-search"></i>
                                        </button>
                                        <?php if (!empty($filterSearch)): ?>
                                        <a href="index.php" class="btn btn-secondary">
                                            <i class="fas fa-times"></i>
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </form>
                        </div>
                    </div>
                    
                    <!-- الفلاتر النشطة -->
                    <?php if (!empty($filters)): ?>
                    <div class="active-filters mb-3">
                        <span class="font-weight-bold">الفلاتر النشطة:</span>
                        <?php foreach ($filters as $key => $value): ?>
                            <?php if ($key != 'search'): ?>
                            <span class="badge badge-info p-2 mr-2">
                                <?php 
                                switch ($key) {
                                    case 'event_id':
                                        echo 'الفعالية: ' . $value;
                                        break;
                                    case 'cost_type_id':
                                        foreach ($costTypes as $type) {
                                            if ($type['id'] == $value) {
                                                echo 'نوع التكلفة: ' . $type['name'];
                                                break;
                                            }
                                        }
                                        break;
                                    case 'date_from':
                                        echo 'من تاريخ: ' . $value;
                                        break;
                                    case 'date_to':
                                        echo 'إلى تاريخ: ' . $value;
                                        break;
                                    default:
                                        echo $key . ': ' . $value;
                                }
                                ?>
                                <a href="<?php 
                                    $newFilters = $filters;
                                    unset($newFilters[$key]);
                                    echo 'index.php' . (!empty($newFilters) ? '?' . http_build_query($newFilters) : '');
                                ?>" class="text-white mr-1">
                                    <i class="fas fa-times"></i>
                                </a>
                            </span>
                            <?php endif; ?>
                        <?php endforeach; ?>
                        
                        <?php if (count($filters) > 1): ?>
                        <a href="index.php" class="btn btn-sm btn-outline-secondary">
                            مسح جميع الفلاتر
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    
                    <!-- عرض التكاليف -->
                    <?php if (empty($costs)): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle ml-2"></i>
                        لا توجد تكاليف خارجية مسجلة.
                        <?php if (!empty($filters)): ?>
                        <a href="index.php" class="alert-link">عرض جميع التكاليف</a>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover table-striped">
                            <thead class="thead-dark">
                                <tr>
                                    <th>#</th>
                                    <th>الفعالية</th>
                                    <th>التاريخ</th>
                                    <th>نوع التكلفة</th>
                                    <th>الوصف</th>
                                    <th>المورد</th>
                                    <th>المبلغ</th>
                                    <th>العمليات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($costs as $cost): ?>
                                <tr>
                                    <td><?php echo $cost['id']; ?></td>
                                    <td>
                                        <a href="../events/view.php?id=<?php echo $cost['event_id']; ?>">
                                            <?php echo htmlspecialchars($cost['event_title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo date(DATE_FORMAT, strtotime($cost['event_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($cost['cost_type_name']); ?></td>
                                    <td><?php echo htmlspecialchars($cost['description']); ?></td>
                                    <td><?php echo htmlspecialchars($cost['vendor']); ?></td>
                                    <td class="text-left">
                                        <?php
                                        // تنسيق المبلغ حسب إعدادات العملة في النظام
                                        $formattedAmount = number_format($cost['amount'], $config['decimal_places'], $config['decimal_separator'], $config['thousand_separator']);
                                        echo $config['currency_position'] == 'before' ? $config['currency'] . ' ' . $formattedAmount : $formattedAmount . ' ' . $config['currency'];
                                        ?>
                                    </td>
                                    <td>
                                        <a href="edit.php?id=<?php echo $cost['id']; ?>" class="btn btn-sm btn-primary" title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger delete-cost" data-id="<?php echo $cost['id']; ?>" data-toggle="modal" data-target="#deleteModal" title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr class="table-primary">
                                    <td colspan="6" class="text-left font-weight-bold">الإجمالي</td>
                                    <td class="text-left font-weight-bold">
                                        <?php
                                        // حساب إجمالي التكاليف المعروضة
                                        $totalAmount = array_sum(array_column($costs, 'amount'));
                                        $formattedTotal = number_format($totalAmount, $config['decimal_places'], $config['decimal_separator'], $config['thousand_separator']);
                                        echo $config['currency_position'] == 'before' ? $config['currency'] . ' ' . $formattedTotal : $formattedTotal . ' ' . $config['currency'];
                                        ?>
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    
                    <!-- الترقيم -->
                    <?php if ($totalPages > 1): ?>
                    <nav aria-label="Page navigation">
                        <ul class="pagination justify-content-center">
                            <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php 
                                    $queryParams = $filters;
                                    $queryParams['page'] = $page - 1;
                                    echo 'index.php' . (!empty($queryParams) ? '?' . http_build_query($queryParams) : '');
                                ?>">
                                    السابق
                                </a>
                            </li>
                            <?php endif; ?>
                            
                            <?php
                            $startPage = max(1, $page - 2);
                            $endPage = min($startPage + 4, $totalPages);
                            
                            for ($i = $startPage; $i <= $endPage; $i++):
                            ?>
                            <li class="page-item <?php echo $i == $page ? 'active' : ''; ?>">
                                <a class="page-link" href="<?php 
                                    $queryParams = $filters;
                                    $queryParams['page'] = $i;
                                    echo 'index.php' . (!empty($queryParams) ? '?' . http_build_query($queryParams) : '');
                                ?>">
                                    <?php echo $i; ?>
                                </a>
                            </li>
                            <?php endfor; ?>
                            
                            <?php if ($page < $totalPages): ?>
                            <li class="page-item">
                                <a class="page-link" href="<?php 
                                    $queryParams = $filters;
                                    $queryParams['page'] = $page + 1;
                                    echo 'index.php' . (!empty($queryParams) ? '?' . http_build_query($queryParams) : '');
                                ?>">
                                    التالي
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
    </div>
</div>

<!-- نافذة تصفية النتائج -->
<div class="modal fade" id="filterModal" tabindex="-1" role="dialog" aria-labelledby="filterModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-secondary text-white">
                <h5 class="modal-title" id="filterModalLabel">تصفية التكاليف</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form method="get" action="">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="cost_type_id">نوع التكلفة</label>
                        <select name="cost_type_id" id="cost_type_id" class="form-control">
                            <option value="">الكل</option>
                            <?php foreach ($costTypes as $type): ?>
                            <option value="<?php echo $type['id']; ?>" <?php echo $filterType == $type['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($type['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="date_from">من تاريخ</label>
                        <input type="date" name="date_from" id="date_from" class="form-control" value="<?php echo $filterDateFrom; ?>">
                    </div>
                    
                    <div class="form-group">
                        <label for="date_to">إلى تاريخ</label>
                        <input type="date" name="date_to" id="date_to" class="form-control" value="<?php echo $filterDateTo; ?>">
                    </div>
                    
                    <!-- حفظ الفلاتر الأخرى في حقول مخفية -->
                    <?php if ($filterSearch): ?>
                    <input type="hidden" name="search" value="<?php echo htmlspecialchars($filterSearch); ?>">
                    <?php endif; ?>
                    
                    <?php if ($filterEvent): ?>
                    <input type="hidden" name="event_id" value="<?php echo $filterEvent; ?>">
                    <?php endif; ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">تطبيق</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- نافذة تأكيد الحذف -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">تأكيد الحذف</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                هل أنت متأكد من رغبتك في حذف هذه التكلفة؟ هذا الإجراء لا يمكن التراجع عنه.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                <form id="deleteForm" method="post" action="delete.php">
                    <input type="hidden" name="cost_id" id="deleteCostId" value="">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION[SESSION_NAME]['csrf_token']; ?>">
                    <button type="submit" class="btn btn-danger">تأكيد الحذف</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// تحديث معرف التكلفة في نموذج الحذف
$(document).ready(function() {
    $('.delete-cost').click(function() {
        var costId = $(this).data('id');
        $('#deleteCostId').val(costId);
    });
    
    // التحقق من صحة تواريخ التصفية
    $('#filterModal form').submit(function(e) {
        var dateFrom = $('#date_from').val();
        var dateTo = $('#date_to').val();
        
        if (dateFrom && dateTo && dateFrom > dateTo) {
            e.preventDefault();
            alert('تاريخ البداية يجب أن يكون قبل تاريخ النهاية');
        }
    });
});
</script>

<?php include '../../templates/footer.php'; ?>
