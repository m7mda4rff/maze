<?php
/**
 * صفحة عرض تفاصيل العميل
 * تعرض جميع المعلومات المتعلقة بالعميل والفعاليات المرتبطة به
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

// التحقق من توفر معرف العميل
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Location: index.php');
    exit;
}

$customerId = (int)$_GET['id'];

// إنشاء كائن العميل
$customerObj = new Customer();

// تحميل بيانات العميل
if (!$customerObj->loadCustomerById($customerId)) {
    // إذا لم يتم العثور على العميل
    header('Location: index.php');
    exit;
}

// تهيئة المتغيرات
$pageTitle = 'بيانات العميل: ' . $customerObj->getName();

// الحصول على مصدر العميل
$sourceName = '-';
if ($customerObj->getSourceId()) {
    $sourceName = $customerObj->getSourceName($customerObj->getSourceId());
}

// الحصول على اسم العميل المُوصي إذا كان مصدر العميل هو توصية
$referralCustomerName = '-';
if ($customerObj->getReferralCustomerId()) {
    $referralObj = new Customer();
    if ($referralObj->loadCustomerById($customerObj->getReferralCustomerId())) {
        $referralCustomerName = $referralObj->getName();
    }
}

// الحصول على فعاليات العميل
$filters = [];
if (isset($_GET['status_id']) && !empty($_GET['status_id'])) {
    $filters['status_id'] = (int)$_GET['status_id'];
}

if (isset($_GET['date_from']) && !empty($_GET['date_from'])) {
    $filters['date_from'] = $_GET['date_from'];
}

if (isset($_GET['date_to']) && !empty($_GET['date_to'])) {
    $filters['date_to'] = $_GET['date_to'];
}

$customerEvents = $customerObj->getCustomerEvents($customerId, $filters);

// تضمين قالب الهيدر
include '../../../templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?php echo $pageTitle; ?></h1>
        <div>
            <a href="edit.php?id=<?php echo $customerId; ?>" class="btn btn-warning">
                <i class="fas fa-edit ml-1"></i> تعديل البيانات
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right ml-1"></i> العودة إلى قائمة العملاء
            </a>
        </div>
        
        <!-- فعاليات العميل -->
        <div class="col-md-8">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">فعاليات العميل</h6>
                    <a href="../events/add.php?customer_id=<?php echo $customerId; ?>" class="btn btn-primary btn-sm">
                        <i class="fas fa-plus ml-1"></i> إضافة فعالية جديدة
                    </a>
                </div>
                <div class="card-body">
                    <!-- فلاتر الفعاليات -->
                    <form method="get" action="" class="row g-3 mb-4">
                        <input type="hidden" name="id" value="<?php echo $customerId; ?>">
                        
                        <div class="col-md-4">
                            <label for="status_id" class="form-label">حالة الفعالية:</label>
                            <select class="form-select" id="status_id" name="status_id">
                                <option value="">جميع الحالات</option>
                                <?php 
                                $eventStatuses = $customerObj->db->fetchAll('SELECT id, name FROM event_statuses ORDER BY id ASC');
                                foreach ($eventStatuses as $status): 
                                ?>
                                <option value="<?php echo $status['id']; ?>" <?php echo (isset($_GET['status_id']) && $_GET['status_id'] == $status['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlentities($status['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="col-md-3">
                            <label for="date_from" class="form-label">من تاريخ:</label>
                            <input type="date" class="form-control" id="date_from" name="date_from" 
                                   value="<?php echo isset($_GET['date_from']) ? htmlentities($_GET['date_from']) : ''; ?>">
                        </div>
                        
                        <div class="col-md-3">
                            <label for="date_to" class="form-label">إلى تاريخ:</label>
                            <input type="date" class="form-control" id="date_to" name="date_to" 
                                   value="<?php echo isset($_GET['date_to']) ? htmlentities($_GET['date_to']) : ''; ?>">
                        </div>
                        
                        <div class="col-md-2 d-flex align-items-end">
                            <button type="submit" class="btn btn-primary w-100">تصفية</button>
                        </div>
                    </form>
                    
                    <?php if (empty($customerEvents)): ?>
                    <div class="alert alert-info">
                        لا توجد فعاليات مسجلة لهذا العميل.
                    </div>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>التاريخ</th>
                                    <th>العنوان</th>
                                    <th>النوع</th>
                                    <th>التكلفة</th>
                                    <th>الحالة</th>
                                    <th>الإجراءات</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($customerEvents as $event): ?>
                                <tr>
                                    <td dir="ltr">
                                        <?php echo date('d/m/Y', strtotime($event['date'])); ?>
                                    </td>
                                    <td>
                                        <a href="../events/view.php?id=<?php echo $event['id']; ?>">
                                            <?php echo htmlentities($event['title']); ?>
                                        </a>
                                    </td>
                                    <td><?php echo htmlentities($event['event_type_name'] ?: '-'); ?></td>
                                    <td class="text-end"><?php echo number_format($event['total_package_cost'], 2); ?></td>
                                    <td>
                                        <?php 
                                        $statusLabels = [
                                            'محجوزة' => '<span class="badge bg-primary">محجوزة</span>',
                                            'قيد التنفيذ' => '<span class="badge bg-warning">قيد التنفيذ</span>',
                                            'منتهية' => '<span class="badge bg-success">منتهية</span>',
                                            'ملغاة' => '<span class="badge bg-danger">ملغاة</span>',
                                        ];
                                        echo isset($statusLabels[$event['status_name']]) ? $statusLabels[$event['status_name']] : $event['status_name'];
                                        ?>
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="../events/view.php?id=<?php echo $event['id']; ?>" class="btn btn-info" title="عرض">
                                                <i class="fas fa-eye"></i>
                                            </a>
                                            <a href="../events/edit.php?id=<?php echo $event['id']; ?>" class="btn btn-warning" title="تعديل">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- ملخص مالي -->
            <?php if (!empty($customerEvents)): ?>
            <?php 
            // حساب إجماليات مالية
            $totalCost = 0;
            $totalPaid = 0;
            $totalRemaining = 0;
            
            foreach ($customerEvents as $event) {
                if ($event['status_name'] !== 'ملغاة') {
                    $totalCost += $event['total_package_cost'];
                    
                    // حساب المدفوعات
                    $eventPayments = $customerObj->db->fetchValue(
                        'SELECT SUM(amount) FROM payments WHERE event_id = ?', 
                        [$event['id']]
                    );
                    
                    $eventPaid = $eventPayments ?: 0;
                    $totalPaid += $eventPaid;
                }
            }
            
            $totalRemaining = $totalCost - $totalPaid;
            ?>
            
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">ملخص مالي</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5>إجمالي الفعاليات</h5>
                                    <h2 class="text-primary"><?php echo number_format($totalCost, 2); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5>المدفوع</h5>
                                    <h2 class="text-success"><?php echo number_format($totalPaid, 2); ?></h2>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-light">
                                <div class="card-body text-center">
                                    <h5>المتبقي</h5>
                                    <h2 class="<?php echo $totalRemaining > 0 ? 'text-danger' : 'text-dark'; ?>">
                                        <?php echo number_format($totalRemaining, 2); ?>
                                    </h2>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
    </div>

    <div class="row">
        <!-- معلومات العميل -->
        <div class="col-md-4">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold">بيانات العميل الأساسية</h6>
                    <span>
                        <?php 
                        $categoryLabels = [
                            'vip' => '<span class="badge bg-primary">VIP</span>',
                            'regular' => '<span class="badge bg-secondary">منتظم</span>',
                            'new' => '<span class="badge bg-success">جديد</span>'
                        ];
                        echo isset($categoryLabels[$customerObj->getCategory()]) ? $categoryLabels[$customerObj->getCategory()] : '';
                        ?>
                    </span>
                </div>
                <div class="card-body">
                    <table class="table table-striped">
                        <tbody>
                            <tr>
                                <th>رقم العميل</th>
                                <td><?php echo $customerObj->getId(); ?></td>
                            </tr>
                            <tr>
                                <th>الاسم</th>
                                <td><?php echo htmlentities($customerObj->getName()); ?></td>
                            </tr>
                            <tr>
                                <th>رقم الهاتف</th>
                                <td>
                                    <a href="tel:<?php echo $customerObj->getPhone(); ?>" dir="ltr">
                                        <?php echo htmlentities($customerObj->getPhone()); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php if ($customerObj->getAltPhone()): ?>
                            <tr>
                                <th>رقم بديل</th>
                                <td>
                                    <a href="tel:<?php echo $customerObj->getAltPhone(); ?>" dir="ltr">
                                        <?php echo htmlentities($customerObj->getAltPhone()); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($customerObj->getEmail()): ?>
                            <tr>
                                <th>البريد الإلكتروني</th>
                                <td>
                                    <a href="mailto:<?php echo $customerObj->getEmail(); ?>" dir="ltr">
                                        <?php echo htmlentities($customerObj->getEmail()); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endif; ?>
                            <?php if ($customerObj->getAddress()): ?>
                            <tr>
                                <th>العنوان</th>
                                <td><?php echo htmlentities($customerObj->getAddress()); ?></td>
                            </tr>
                            <?php endif; ?>
                            <tr>
                                <th>المصدر</th>
                                <td><?php echo htmlentities($sourceName); ?></td>
                            </tr>
                            <?php if ($customerObj->getReferralCustomerId()): ?>
                            <tr>
                                <th>العميل المُوصي</th>
                                <td>
                                    <a href="view.php?id=<?php echo $customerObj->getReferralCustomerId(); ?>">
                                        <?php echo htmlentities($referralCustomerName); ?>
                                    </a>
                                </td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <?php if ($customerObj->getNotes()): ?>
                    <div class="mt-3">
                        <h6 class="font-weight-bold">ملاحظات:</h6>
                        <p class="card-text"><?php echo nl2br(htmlentities($customerObj->getNotes())); ?></p>
                    </div>
                    <?php endif; ?>
                    
                    <div class="mt-3">
                        <a href="edit.php?id=<?php echo $customerId; ?>" class="btn btn-sm btn-warning">
                            <i class="fas fa-edit ml-1"></i> تعديل البيانات
                        </a>
                        <a href="https://wa.me/<?php echo preg_replace('/[^0-9]/', '', $customerObj->getPhone()); ?>" target="_blank" class="btn btn-sm btn-success">
                            <i class="fab fa-whatsapp ml-1"></i> واتساب
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- العملاء المُوصى بهم -->
            <?php 
            // الحصول على العملاء الذين تمت التوصية بهم عن طريق هذا العميل
            $referredCustomers = $customerObj->db->fetchAll(
                'SELECT id, name, phone, category FROM customers WHERE referral_customer_id = ? ORDER BY created_at DESC', 
                [$customerId]
            );
            
            if (!empty($referredCustomers)): 
            ?>
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold">العملاء المُوصى بهم</h6>
                </div>
                <div class="card-body">
                    <ul class="list-group">
                        <?php foreach ($referredCustomers as $refCustomer): ?>
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <a href="view.php?id=<?php echo $refCustomer['id']; ?>">
                                    <?php echo htmlentities($refCustomer['name']); ?>
                                </a>
                                <small class="d-block text-muted" dir="ltr"><?php echo htmlentities($refCustomer['phone']); ?></small>
                            </div>
                            <?php 
                            $catBadge = '';
                            switch ($refCustomer['category']) {
                                case 'vip':
                                    $catBadge = '<span class="badge bg-primary">VIP</span>';
                                    break;
                                case 'regular':
                                    $catBadge = '<span class="badge bg-secondary">منتظم</span>';
                                    break;
                                case 'new':
                                    $catBadge = '<span class="badge bg-success">جديد</span>';
                                    break;
                            }
                            echo $catBadge;
                            ?>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
            <?php endif; ?>
