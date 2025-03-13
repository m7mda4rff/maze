<?php
/**
 * صفحة عرض قائمة المدفوعات
 */

// تعريف ثابت لمنع الوصول المباشر للملف
define('BASEPATH', true);

// استيراد الملفات المطلوبة
require_once '../../../includes/session.php';
require_once '../../../includes/auth.php';
require_once '../../../config/config.php';
require_once '../../../classes/Database.php';
require_once '../../../classes/User.php';
require_once '../../../classes/Payment.php';
require_once '../../../includes/functions.php';

// التحقق من تسجيل الدخول
checkLogin();

// الحصول على معلومات المستخدم الحالي
$currentUser = User::getCurrentUser();

// التحقق من الصلاحيات (موظف أو أعلى)
if (!$currentUser->hasPermission(['admin', 'manager', 'staff'])) {
    redirect('../dashboard.php', 'لا تملك الصلاحية للوصول إلى هذه الصفحة', 'error');
}

// إنشاء كائن من فئة Payment
$payment = new Payment();

// تعيين متغيرات البحث والتصفية
$filter = [
    'date_from' => isset($_GET['date_from']) ? $_GET['date_from'] : '',
    'date_to' => isset($_GET['date_to']) ? $_GET['date_to'] : '',
    'payment_method' => isset($_GET['payment_method']) ? $_GET['payment_method'] : '',
    'payment_type' => isset($_GET['payment_type']) ? $_GET['payment_type'] : '',
    'customer_id' => isset($_GET['customer_id']) ? (int)$_GET['customer_id'] : 0,
    'event_id' => isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0,
    'search' => isset($_GET['search']) ? $_GET['search'] : '',
];

// الصفحة الحالية للتصفح
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = ($page < 1) ? 1 : $page;

// عدد العناصر في الصفحة
$perPage = isset($config['pagination_limit']) ? $config['pagination_limit'] : 20;

// حساب نقطة البداية
$offset = ($page - 1) * $perPage;

// الحصول على قائمة المدفوعات
$payments = $payment->getPayments($filter, $perPage, $offset);

// الحصول على إجمالي عدد المدفوعات للتصفح
$totalPayments = $payment->countPayments($filter);

// حساب عدد الصفحات
$totalPages = ceil($totalPayments / $perPage);

// الحصول على إحصائيات المدفوعات الحالية
$stats = $payment->getPaymentStats($filter['date_from'], $filter['date_to']);

// الحصول على قائمة طرق الدفع للفلترة
$paymentMethods = $payment->getPaymentMethods();

// الحصول على قائمة أنواع الدفع للفلترة
$paymentTypes = $payment->getPaymentTypes();

// تضمين رأس الصفحة
$pageTitle = 'قائمة المدفوعات';
include_once '../../../templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">قائمة المدفوعات</h1>
        <?php if ($currentUser->hasPermission(['admin', 'manager'])): ?>
        <a href="add.php" class="btn btn-primary">
            <i class="fas fa-plus"></i> إضافة دفعة جديدة
        </a>
        <?php endif; ?>
    </div>

    <!-- بطاقات الإحصائيات -->
    <div class="row mb-4">
        <div class="col-md-3 mb-3">
            <div class="card border-right-primary h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">إجمالي المدفوعات</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= formatCurrency($stats['total_amount']) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-right-success h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-xs font-weight-bold text-success text-uppercase mb-1">المدفوعات (دفعة مقدمة)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= formatCurrency($stats['total_by_type']['دفعة مقدمة'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-right-info h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-xs font-weight-bold text-info text-uppercase mb-1">المدفوعات (دفعة نهائية)</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= formatCurrency($stats['total_by_type']['دفعة نهائية'] ?? 0) ?></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 mb-3">
            <div class="card border-right-warning h-100">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">عدد المدفوعات</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $totalPayments ?></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- فلاتر البحث -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">بحث وتصفية</h6>
        </div>
        <div class="card-body">
            <form method="get" action="" class="mb-0">
                <div class="row">
                    <div class="col-md-2 mb-3">
                        <label for="date_from">من تاريخ</label>
                        <input type="date" id="date_from" name="date_from" class="form-control" value="<?= $filter['date_from'] ?>">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="date_to">إلى تاريخ</label>
                        <input type="date" id="date_to" name="date_to" class="form-control" value="<?= $filter['date_to'] ?>">
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="payment_method">طريقة الدفع</label>
                        <select id="payment_method" name="payment_method" class="form-control">
                            <option value="">الكل</option>
                            <?php foreach ($paymentMethods as $method): ?>
                                <option value="<?= $method ?>" <?= ($filter['payment_method'] == $method) ? 'selected' : '' ?>><?= $method ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-2 mb-3">
                        <label for="payment_type">نوع الدفعة</label>
                        <select id="payment_type" name="payment_type" class="form-control">
                            <option value="">الكل</option>
                            <?php foreach ($paymentTypes as $type): ?>
                                <option value="<?= $type ?>" <?= ($filter['payment_type'] == $type) ? 'selected' : '' ?>><?= $type ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-4 mb-3">
                        <label for="search">بحث</label>
                        <input type="text" id="search" name="search" class="form-control" placeholder="اسم العميل، عنوان الفعالية..." value="<?= $filter['search'] ?>">
                    </div>
                </div>
                <div class="row">
                    <div class="col-12 text-left">
                        <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i> بحث</button>
                        <a href="index.php" class="btn btn-secondary"><i class="fas fa-redo"></i> إعادة تعيين</a>
                        <?php if ($currentUser->hasPermission(['admin', 'manager'])): ?>
                        <a href="export.php?<?= http_build_query($filter) ?>" class="btn btn-success"><i class="fas fa-file-export"></i> تصدير إلى ملف Excel</a>
                        <?php endif; ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- جدول المدفوعات -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">المدفوعات (<?= $totalPayments ?>)</h6>
        </div>
        <div class="card-body">
            <?php if (empty($payments)): ?>
                <div class="alert alert-info">لا توجد مدفوعات تطابق معايير البحث</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" width="100%" cellspacing="0">
                        <thead>
                            <tr>
                                <th>المعرف</th>
                                <th>تاريخ الدفع</th>
                                <th>المبلغ</th>
                                <th>نوع الدفعة</th>
                                <th>طريقة الدفع</th>
                                <th>العميل</th>
                                <th>الفعالية</th>
                                <th>بواسطة</th>
                                <th>الإجراءات</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($payments as $p): ?>
                                <tr>
                                    <td><?= $p['id'] ?></td>
                                    <td><?= formatDate($p['payment_date']) ?></td>
                                    <td class="text-left"><?= formatCurrency($p['amount']) ?></td>
                                    <td><?= $p['payment_type'] ?></td>
                                    <td><?= $p['payment_method'] ?></td>
                                    <td><?= $p['customer_name'] ?></td>
                                    <td><?= $p['event_title'] ?></td>
                                    <td><?= $p['created_by_name'] ?></td>
                                    <td>
                                        <a href="receipt.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-info" title="عرض الإيصال">
                                            <i class="fas fa-file-invoice"></i>
                                        </a>
                                        <?php if ($currentUser->hasPermission(['admin', 'manager'])): ?>
                                            <a href="edit.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-primary" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="delete.php?id=<?= $p['id'] ?>" class="btn btn-sm btn-danger delete-item" title="حذف" data-confirm="هل أنت متأكد من حذف هذه الدفعة؟">
                                                <i class="fas fa-trash"></i>
                                            </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- عناصر التصفح -->
                <?php if ($totalPages > 1): ?>
                    <div class="d-flex justify-content-center mt-4">
                        <nav aria-label="Page navigation">
                            <ul class="pagination">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=1&<?= http_build_query($filter) ?>" aria-label="First">
                                            <span aria-hidden="true">&laquo;&laquo;</span>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page - 1 ?>&<?= http_build_query($filter) ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>

                                <?php
                                // عرض أرقام الصفحات
                                $startPage = max(1, $page - 2);
                                $endPage = min($totalPages, $page + 2);

                                if ($startPage > 1) {
                                    echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                }

                                for ($i = $startPage; $i <= $endPage; $i++) {
                                    echo '<li class="page-item ' . ($i == $page ? 'active' : '') . '">
                                            <a class="page-link" href="?page=' . $i . '&' . http_build_query($filter) . '">' . $i . '</a>
                                          </li>';
                                }

                                if ($endPage < $totalPages) {
                                    echo '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
                                }
                                ?>

                                <?php if ($page < $totalPages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $page + 1 ?>&<?= http_build_query($filter) ?>" aria-label="Next">
                                            <span aria-hidden="true">&raquo;</span>
                                        </a>
                                    </li>
                                    <li class="page-item">
                                        <a class="page-link" href="?page=<?= $totalPages ?>&<?= http_build_query($filter) ?>" aria-label="Last">
                                            <span aria-hidden="true">&raquo;&raquo;</span>
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

<!-- إضافة سكريبت تأكيد الحذف -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    // تأكيد الحذف
    const deleteLinks = document.querySelectorAll('.delete-item');
    
    deleteLinks.forEach(link => {
        link.addEventListener('click', function(e) {
            e.preventDefault();
            const confirmMessage = this.getAttribute('data-confirm');
            
            if (confirm(confirmMessage)) {
                window.location.href = this.getAttribute('href');
            }
        });
    });
});
</script>

<?php
// تضمين تذييل الصفحة
include_once '../../../templates/footer.php';
?>
