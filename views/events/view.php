<?php
/**
 * صفحة عرض تفاصيل الفعالية
 * 
 * تستخدم لعرض تفاصيل فعالية محددة مع التكاليف والمدفوعات والمهام الخاصة بها
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
require_once '../../../classes/ExternalCost.php';

// التحقق من تسجيل الدخول
$user = User::getCurrentUser();
if (!$user || !$user->isLoggedIn()) {
    redirect(BASE_URL . '/public/login.php');
}

// التحقق من وجود معرف الفعالية
if (!isset($_GET['id']) || empty($_GET['id'])) {
    setSystemMessage('error', 'معرف الفعالية غير صحيح');
    redirect(BASE_URL . '/public/views/events/index.php');
}

// إنشاء كائن الفعاليات والتكاليف
$eventObj = new Event();
$costObj = new ExternalCost();

// تحميل بيانات الفعالية
$eventId = (int)$_GET['id'];
if (!$eventObj->loadEventById($eventId)) {
    setSystemMessage('error', 'الفعالية غير موجودة أو تم حذفها');
    redirect(BASE_URL . '/public/views/events/index.php');
}

// الحصول على بيانات التكاليف والمدفوعات والمهام
$externalCosts = $eventObj->getExternalCosts();
$payments = $eventObj->getPayments();
$tasks = $eventObj->getTasks();

// حساب ربحية الفعالية
$profitability = $eventObj->getProfitability();

// إعداد عنوان الصفحة
$pageTitle = 'تفاصيل الفعالية: ' . $eventObj->getTitle();

// تضمين قالب رأس الصفحة
include_once TEMPLATES_PATH . '/header.php';
?>

<!-- بداية محتوى الصفحة -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?php echo $pageTitle; ?></h1>
        <div>
            <?php if ($user->hasPermission(['admin', 'manager'])): ?>
            <a href="edit.php?id=<?php echo $eventId; ?>" class="btn btn-primary">
                <i class="fas fa-edit"></i> تعديل الفعالية
            </a>
            <?php endif; ?>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-left"></i> العودة لقائمة الفعاليات
            </a>
        </div>
    </div>

    <!-- بطاقة حالة الفعالية -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">حالة الفعالية</h6>
            <span class="badge badge-<?php echo getStatusColor($eventObj->getStatusName()); ?> badge-lg">
                <?php echo htmlspecialchars($eventObj->getStatusName()); ?>
            </span>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-6">
                    <p class="mb-2">
                        <strong>العميل:</strong> 
                        <a href="<?php echo BASE_URL; ?>/public/views/customers/view.php?id=<?php echo $eventObj->getCustomerId(); ?>">
                            <?php echo htmlspecialchars($eventObj->getCustomerName()); ?>
                        </a>
                    </p>
                    <p class="mb-2">
                        <strong>نوع الفعالية:</strong> 
                        <?php echo htmlspecialchars($eventObj->getEventTypeName()); ?>
                    </p>
                    <p class="mb-2">
                        <strong>التاريخ:</strong> 
                        <?php echo formatDate($eventObj->getDate()); ?>
                    </p>
                    <p class="mb-2">
                        <strong>الوقت:</strong> 
                        <?php echo formatTime($eventObj->getStartTime()); ?> - <?php echo formatTime($eventObj->getEndTime()); ?>
                        <small class="text-muted">(<?php echo round($eventObj->getDurationHours(), 1); ?> ساعة)</small>
                    </p>
                </div>
                <div class="col-md-6">
                    <p class="mb-2">
                        <strong>الموقع:</strong> 
                        <?php echo !empty($eventObj->getLocation()) ? htmlspecialchars($eventObj->getLocation()) : '<span class="text-muted">غير محدد</span>'; ?>
                    </p>
                    <p class="mb-2">
                        <strong>عدد الضيوف:</strong> 
                        <?php echo $eventObj->getGuestCount(); ?> ضيف
                    </p>
                    <p class="mb-2">
                        <strong>تكلفة الباقة:</strong> 
                        <span class="text-success"><?php echo formatCurrency($eventObj->getTotalPackageCost()); ?></span>
                    </p>
                    <p class="mb-2">
                        <strong>تاريخ الإنشاء:</strong> 
                        <?php echo formatDateTime($eventObj->getCreatedAt()); ?>
                    </p>
                </div>
            </div>
            
            <?php if (!empty($eventObj->getPackageDetails())): ?>
            <div class="mt-3">
                <strong>تفاصيل الباقة:</strong>
                <p class="mb-0 mt-2"><?php echo nl2br(htmlspecialchars($eventObj->getPackageDetails())); ?></p>
            </div>
            <?php endif; ?>
            
            <?php if (!empty($eventObj->getAdditionalNotes())): ?>
            <div class="mt-3">
                <strong>ملاحظات إضافية:</strong>
                <p class="mb-0 mt-2"><?php echo nl2br(htmlspecialchars($eventObj->getAdditionalNotes())); ?></p>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- بطاقة ملخص الإيرادات والتكاليف -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">إجمالي الإيرادات</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatCurrency($profitability['total_revenue']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-money-bill-wave fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">إجمالي التكاليف</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatCurrency($profitability['total_cost']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-hand-holding-usd fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">صافي الربح</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?php echo formatCurrency($profitability['profit']); ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">هامش الربح</div>
                            <div class="row no-gutters align-items-center">
                                <div class="col-auto">
                                    <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800"><?php echo number_format($profitability['profit_margin'], 1); ?>%</div>
                                </div>
                                <div class="col">
                                    <div class="progress progress-sm mr-2">
                                        <div class="progress-bar bg-info" role="progressbar" style="width: <?php echo min(100, max(0, $profitability['profit_margin'])); ?>%"></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-percent fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- التبويبات: التكاليف، المدفوعات، المهام -->
    <ul class="nav nav-tabs mb-4" id="eventTabs" role="tablist">
        <li class="nav-item">
            <a class="nav-link active" id="costs-tab" data-toggle="tab" href="#costs" role="tab" aria-controls="costs" aria-selected="true">
                <i class="fas fa-receipt"></i> التكاليف الخارجية <span class="badge badge-warning"><?php echo count($externalCosts); ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="payments-tab" data-toggle="tab" href="#payments" role="tab" aria-controls="payments" aria-selected="false">
                <i class="fas fa-money-check-alt"></i> المدفوعات <span class="badge badge-primary"><?php echo count($payments); ?></span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link" id="tasks-tab" data-toggle="tab" href="#tasks" role="tab" aria-controls="tasks" aria-selected="false">
                <i class="fas fa-tasks"></i> المهام <span class="badge badge-info"><?php echo count($tasks); ?></span>
            </a>
        </li>
    </ul>
    
    <div class="tab-content" id="eventTabsContent">
        <!-- تبويب التكاليف الخارجية -->
        <div class="tab-pane fade show active" id="costs" role="tabpanel" aria-labelledby="costs-tab">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">التكاليف الخارجية</h6>
                    <?php if ($user->hasPermission(['admin', 'manager'])): ?>
                    <a href="<?php echo BASE_URL; ?>/public/views/costs/add.php?event_id=<?php echo $eventId; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus-circle"></i> إضافة تكلفة جديدة
                    </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($externalCosts)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-receipt fa-4x text-gray-300 mb-3"></i>
                        <p class="mb-0">لا توجد تكاليف خارجية لهذه الفعالية</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">رقم</th>
                                    <th width="15%">نوع التكلفة</th>
                                    <th width="30%">الوصف</th>
                                    <th width="15%">المورد</th>
                                    <th width="15%">المبلغ</th>
                                    <th width="20%">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($externalCosts as $cost): ?>
                                <tr>
                                    <td><?php echo $cost['id']; ?></td>
                                    <td><?php echo htmlspecialchars($cost['cost_type_name'] ?? 'غير محدد'); ?></td>
                                    <td><?php echo htmlspecialchars($cost['description']); ?></td>
                                    <td><?php echo !empty($cost['vendor']) ? htmlspecialchars($cost['vendor']) : '<span class="text-muted">غير محدد</span>'; ?></td>
                                    <td><?php echo formatCurrency($cost['amount']); ?></td>
                                    <td>
                                        <?php if ($user->hasPermission(['admin', 'manager'])): ?>
                                        <a href="<?php echo BASE_URL; ?>/public/views/costs/edit.php?id=<?php echo $cost['id']; ?>" class="btn btn-primary btn-sm" title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="#" class="btn btn-danger btn-sm delete-cost" data-id="<?php echo $cost['id']; ?>" title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php else: ?>
                                        <button class="btn btn-info btn-sm" disabled>
                                            <i class="fas fa-eye"></i> عرض
                                        </button>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-right">الإجمالي:</th>
                                    <th colspan="2"><?php echo formatCurrency($profitability['total_cost']); ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- تبويب المدفوعات -->
        <div class="tab-pane fade" id="payments" role="tabpanel" aria-labelledby="payments-tab">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">المدفوعات</h6>
                    <?php if ($user->hasPermission(['admin', 'manager'])): ?>
                    <a href="<?php echo BASE_URL; ?>/public/views/payments/add.php?event_id=<?php echo $eventId; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus-circle"></i> إضافة دفعة جديدة
                    </a>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if (empty($payments)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-money-check-alt fa-4x text-gray-300 mb-3"></i>
                        <p class="mb-0">لا توجد مدفوعات لهذه الفعالية</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">رقم</th>
                                    <th width="15%">التاريخ</th>
                                    <th width="15%">نوع الدفعة</th>
                                    <th width="15%">طريقة الدفع</th>
                                    <th width="15%">المبلغ</th>
                                    <th width="20%">ملاحظات</th>
                                    <th width="15%">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php 
                                $totalPaid = 0;
                                foreach ($payments as $payment): 
                                    $totalPaid += $payment['amount'];
                                ?>
                                <tr>
                                    <td><?php echo $payment['id']; ?></td>
                                    <td><?php echo formatDate($payment['payment_date']); ?></td>
                                    <td>
                                        <?php 
                                        $paymentTypeClass = '';
                                        switch ($payment['payment_type']) {
                                            case 'deposit':
                                                echo 'عربون';
                                                $paymentTypeClass = 'text-warning';
                                                break;
                                            case 'partial':
                                                echo 'دفعة جزئية';
                                                $paymentTypeClass = 'text-info';
                                                break;
                                            case 'final':
                                                echo 'دفعة نهائية';
                                                $paymentTypeClass = 'text-success';
                                                break;
                                            default:
                                                echo 'دفعة';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo htmlspecialchars($payment['payment_method']); ?></td>
                                    <td class="<?php echo $paymentTypeClass; ?>">
                                        <?php echo formatCurrency($payment['amount']); ?>
                                    </td>
                                    <td>
                                        <?php echo !empty($payment['notes']) ? htmlspecialchars($payment['notes']) : '<span class="text-muted">لا توجد ملاحظات</span>'; ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/public/views/payments/receipt.php?id=<?php echo $payment['id']; ?>" class="btn btn-info btn-sm" title="عرض الإيصال" target="_blank">
                                            <i class="fas fa-file-invoice"></i>
                                        </a>
                                        <?php if ($user->hasPermission(['admin', 'manager'])): ?>
                                        <a href="#" class="btn btn-danger btn-sm delete-payment" data-id="<?php echo $payment['id']; ?>" title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-right">إجمالي المدفوع:</th>
                                    <th colspan="3"><?php echo formatCurrency($totalPaid); ?></th>
                                </tr>
                                <tr>
                                    <th colspan="4" class="text-right">المتبقي:</th>
                                    <th colspan="3" class="<?php echo ($eventObj->getTotalPackageCost() - $totalPaid) > 0 ? 'text-danger' : 'text-success'; ?>">
                                        <?php echo formatCurrency(max(0, $eventObj->getTotalPackageCost() - $totalPaid)); ?>
                                    </th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- تبويب المهام -->
        <div class="tab-pane fade" id="tasks" role="tabpanel" aria-labelledby="tasks-tab">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">المهام</h6>
                    <a href="<?php echo BASE_URL; ?>/public/views/tasks/add.php?event_id=<?php echo $eventId; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus-circle"></i> إضافة مهمة جديدة
                    </a>
                </div>
                <div class="card-body">
                    <?php if (empty($tasks)): ?>
                    <div class="text-center py-4">
                        <i class="fas fa-tasks fa-4x text-gray-300 mb-3"></i>
                        <p class="mb-0">لا توجد مهام لهذه الفعالية</p>
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead>
                                <tr>
                                    <th width="5%">رقم</th>
                                    <th width="25%">العنوان</th>
                                    <th width="15%">المسؤول</th>
                                    <th width="10%">الأولوية</th>
                                    <th width="15%">تاريخ الاستحقاق</th>
                                    <th width="10%">الحالة</th>
                                    <th width="20%">الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($tasks as $task): ?>
                                <tr>
                                    <td><?php echo $task['id']; ?></td>
                                    <td><?php echo htmlspecialchars($task['title']); ?></td>
                                    <td><?php echo !empty($task['assigned_to_name']) ? htmlspecialchars($task['assigned_to_name']) : '<span class="text-muted">غير محدد</span>'; ?></td>
                                    <td>
                                        <?php 
                                        switch ($task['priority']) {
                                            case 'high':
                                                echo '<span class="badge badge-danger">عالية</span>';
                                                break;
                                            case 'medium':
                                                echo '<span class="badge badge-warning">متوسطة</span>';
                                                break;
                                            case 'low':
                                                echo '<span class="badge badge-info">منخفضة</span>';
                                                break;
                                            default:
                                                echo '<span class="badge badge-secondary">عادية</span>';
                                        }
                                        ?>
                                    </td>
                                    <td><?php echo !empty($task['due_date']) ? formatDate($task['due_date']) : '<span class="text-muted">غير محدد</span>'; ?></td>
                                    <td>
                                        <?php 
                                        switch ($task['status']) {
                                            case 'pending':
                                                echo '<span class="badge badge-warning">قيد الانتظار</span>';
                                                break;
                                            case 'in_progress':
                                                echo '<span class="badge badge-info">قيد التنفيذ</span>';
                                                break;
                                            case 'completed':
                                                echo '<span class="badge badge-success">مكتملة</span>';
                                                break;
                                            case 'cancelled':
                                                echo '<span class="badge badge-danger">ملغاة</span>';
                                                break;
                                            default:
                                                echo '<span class="badge badge-secondary">غير محدد</span>';
                                        }
                                        ?>
                                    </td>
                                    <td>
                                        <a href="<?php echo BASE_URL; ?>/public/views/tasks/edit.php?id=<?php echo $task['id']; ?>" class="btn btn-primary btn-sm" title="تعديل">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="#" class="btn btn-success btn-sm update-task-status" data-id="<?php echo $task['id']; ?>" data-status="<?php echo $task['status']; ?>" title="تحديث الحالة">
                                            <i class="fas fa-check"></i>
                                        </a>
                                        <a href="#" class="btn btn-danger btn-sm delete-task" data-id="<?php echo $task['id']; ?>" title="حذف">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- نوافذ تأكيد الحذف -->
<!-- نافذة حذف التكلفة -->
<div class="modal fade" id="deleteCostModal" tabindex="-1" role="dialog" aria-labelledby="deleteCostModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCostModalLabel">تأكيد حذف التكلفة</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                هل أنت متأكد من حذف هذه التكلفة؟
                <p class="text-danger mt-2">هذا الإجراء لا يمكن التراجع عنه.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                <form id="deleteCostForm" method="post" action="<?php echo BASE_URL; ?>/public/views/costs/delete.php">
                    <input type="hidden" name="cost_id" id="costId">
                    <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <button type="submit" class="btn btn-danger">تأكيد الحذف</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- نافذة حذف المدفوعات -->
<div class="modal fade" id="deletePaymentModal" tabindex="-1" role="dialog" aria-labelledby="deletePaymentModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deletePaymentModalLabel">تأكيد حذف المدفوعات</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                هل أنت متأكد من حذف هذه الدفعة؟
                <p class="text-danger mt-2">هذا الإجراء لا يمكن التراجع عنه.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                <form id="deletePaymentForm" method="post" action="<?php echo BASE_URL; ?>/public/views/payments/delete.php">
                    <input type="hidden" name="payment_id" id="paymentId">
                    <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <button type="submit" class="btn btn-danger">تأكيد الحذف</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- نافذة حذف المهمة -->
<div class="modal fade" id="deleteTaskModal" tabindex="-1" role="dialog" aria-labelledby="deleteTaskModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteTaskModalLabel">تأكيد حذف المهمة</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                هل أنت متأكد من حذف هذه المهمة؟
                <p class="text-danger mt-2">هذا الإجراء لا يمكن التراجع عنه.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                <form id="deleteTaskForm" method="post" action="<?php echo BASE_URL; ?>/public/views/tasks/delete.php">
                    <input type="hidden" name="task_id" id="taskId">
                    <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                    <button type="submit" class="btn btn-danger">تأكيد الحذف</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- نافذة تحديث حالة المهمة -->
<div class="modal fade" id="updateTaskStatusModal" tabindex="-1" role="dialog" aria-labelledby="updateTaskStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateTaskStatusModalLabel">تحديث حالة المهمة</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <form id="updateTaskStatusForm" method="post" action="<?php echo BASE_URL; ?>/public/views/tasks/update_status.php">
                <div class="modal-body">
                    <div class="form-group">
                        <label for="taskStatus">الحالة الجديدة</label>
                        <select class="form-control" id="taskStatus" name="status">
                            <option value="pending">قيد الانتظار</option>
                            <option value="in_progress">قيد التنفيذ</option>
                            <option value="completed">مكتملة</option>
                            <option value="cancelled">ملغاة</option>
                        </select>
                    </div>
                    <input type="hidden" name="task_id" id="updateTaskId">
                    <input type="hidden" name="event_id" value="<?php echo $eventId; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                    <button type="submit" class="btn btn-primary">تحديث الحالة</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// دالة مساعدة للحصول على لون الحالة
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

// دالة مساعدة لتنسيق التاريخ
function formatDate($date) {
    return date(DATE_FORMAT, strtotime($date));
}

// دالة مساعدة لتنسيق الوقت
function formatTime($time) {
    return date(TIME_FORMAT, strtotime($time));
}

// دالة مساعدة لتنسيق التاريخ والوقت
function formatDateTime($dateTime) {
    return date(DATETIME_FORMAT, strtotime($dateTime));
}

// دالة مساعدة لتنسيق العملة
function formatCurrency($amount) {
    global $config;
    
    $formattedAmount = number_format($amount, $config['decimal_places'], $config['decimal_separator'], $config['thousand_separator']);
    
    if ($config['currency_position'] === 'before') {
        return $config['currency'] . ' ' . $formattedAmount;
    } else {
        return $formattedAmount . ' ' . $config['currency'];
    }
}
?>

<!-- سكريبت لنوافذ تأكيد الحذف وتحديث الحالة -->
<script>
    document.addEventListener('DOMContentLoaded', function() {
        // إعداد نافذة حذف التكلفة
        var deleteCostButtons = document.querySelectorAll('.delete-cost');
        deleteCostButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                var costId = this.getAttribute('data-id');
                document.getElementById('costId').value = costId;
                $('#deleteCostModal').modal('show');
            });
        });
        
        // إعداد نافذة حذف المدفوعات
        var deletePaymentButtons = document.querySelectorAll('.delete-payment');
        deletePaymentButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                var paymentId = this.getAttribute('data-id');
                document.getElementById('paymentId').value = paymentId;
                $('#deletePaymentModal').modal('show');
            });
        });
        
        // إعداد نافذة حذف المهمة
        var deleteTaskButtons = document.querySelectorAll('.delete-task');
        deleteTaskButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                var taskId = this.getAttribute('data-id');
                document.getElementById('taskId').value = taskId;
                $('#deleteTaskModal').modal('show');
            });
        });
        
        // إعداد نافذة تحديث حالة المهمة
        var updateTaskStatusButtons = document.querySelectorAll('.update-task-status');
        updateTaskStatusButtons.forEach(function(button) {
            button.addEventListener('click', function(e) {
                e.preventDefault();
                var taskId = this.getAttribute('data-id');
                var currentStatus = this.getAttribute('data-status');
                document.getElementById('updateTaskId').value = taskId;
                document.getElementById('taskStatus').value = currentStatus;
                $('#updateTaskStatusModal').modal('show');
            });
        });
    });
</script>

<!-- نهاية محتوى الصفحة -->

<?php
// تضمين قالب تذييل الصفحة
include_once TEMPLATES_PATH . '/footer.php';
?>
