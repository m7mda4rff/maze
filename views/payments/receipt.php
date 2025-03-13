<?php
/**
 * صفحة عرض إيصال الدفع
 */

// تعريف ثابت لمنع الوصول المباشر للملف
define('BASEPATH', true);

// استيراد الملفات المطلوبة
require_once '../../../includes/session.php';
require_once '../../../includes/auth.php';
require_once '../../../config/config.php';
require_once '../../../classes/Database.php';
require_once '../../../classes/User.php';
require_once '../../../classes/Customer.php';
require_once '../../../classes/Event.php';
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

// التحقق من وجود معرف الدفعة
if (!isset($_GET['id']) || empty($_GET['id'])) {
    redirect('index.php', 'لم يتم تحديد الدفعة المطلوبة', 'error');
}

// الحصول على معرف الدفعة
$payment_id = (int)$_GET['id'];

// إنشاء كائن من فئة Payment
$payment = new Payment();

// تحميل معلومات الدفعة
$paymentInfo = $payment->getPaymentById($payment_id);

// التحقق من وجود الدفعة
if (!$paymentInfo) {
    redirect('index.php', 'الدفعة غير موجودة أو تم حذفها', 'error');
}

// الحصول على معلومات الفعالية والعميل المرتبطين بالدفعة
$event = new Event();
$event->loadEventById($paymentInfo['event_id']);
$eventInfo = [
    'title' => $event->getTitle(),
    'date' => $event->getDate(),
    'customer_id' => $event->getCustomerId(),
    'total_package_cost' => $event->getTotalPackageCost()
];

$customer = new Customer();
$customer->loadCustomerById($eventInfo['customer_id']);
$customerInfo = [
    'name' => $customer->getName(),
    'phone' => $customer->getPhone(),
    'address' => $customer->getAddress()
];

// وضع الصفحة للطباعة
$isPrintView = isset($_GET['print']) && $_GET['print'] == 'true';

// تضمين رأس الصفحة (ما لم تكن في وضع الطباعة)
if (!$isPrintView) {
    $pageTitle = 'إيصال دفع';
    include_once '../../../templates/header.php';
}
?>

<?php if ($isPrintView): ?>
<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>إيصال دفع #<?= $payment_id ?> - <?= APP_NAME ?></title>
    <link rel="stylesheet" href="<?= ASSETS_PATH ?>/css/bootstrap.rtl.min.css">
    <link rel="stylesheet" href="<?= ASSETS_PATH ?>/css/print-style.css">
    <style>
        @media print {
            body {
                padding: 0;
                margin: 0;
            }
            .receipt-container {
                page-break-after: always;
            }
            .no-print {
                display: none !important;
            }
        }
        
        .receipt-container {
            max-width: 800px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #ddd;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            background-color: #fff;
        }
        
        .receipt-header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 2px solid #ddd;
            padding-bottom: 10px;
        }
        
        .receipt-logo {
            max-width: 200px;
            margin-bottom: 10px;
        }
        
        .receipt-title {
            font-size: 24px;
            font-weight: bold;
            margin-bottom: 5px;
        }
        
        .receipt-number {
            font-size: 16px;
            color: #666;
        }
        
        .section-title {
            background-color: #f5f5f5;
            padding: 8px;
            margin-bottom: 15px;
            border-right: 4px solid #007bff;
        }
        
        .receipt-footer {
            margin-top: 30px;
            border-top: 1px solid #ddd;
            padding-top: 15px;
            text-align: center;
        }
        
        .signature-area {
            display: flex;
            justify-content: space-between;
            margin-top: 40px;
            margin-bottom: 20px;
        }
        
        .signature-box {
            width: 45%;
        }
    </style>
</head>
<body>
<?php endif; ?>

<div class="container-fluid py-4 <?= $isPrintView ? 'receipt-container' : '' ?>">
    <?php if (!$isPrintView): ?>
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">إيصال دفع</h1>
        <div>
            <a href="receipt.php?id=<?= $payment_id ?>&print=true" target="_blank" class="btn btn-primary">
                <i class="fas fa-print"></i> طباعة الإيصال
            </a>
            <a href="index.php" class="btn btn-secondary">
                <i class="fas fa-arrow-right"></i> العودة إلى قائمة المدفوعات
            </a>
        </div>
    </div>
    <?php endif; ?>
    
    <!-- بداية الإيصال -->
    <div class="receipt-header">
        <?php if ($isPrintView): ?>
        <img src="<?= ASSETS_PATH ?>/images/logo.png" alt="<?= APP_NAME ?>" class="receipt-logo">
        <?php endif; ?>
        <h2 class="receipt-title"><?= APP_NAME ?></h2>
        <p class="mb-1">إيصال دفع</p>
        <p class="receipt-number">رقم الإيصال: <?= $payment_id ?></p>
    </div>
    
    <div class="row">
        <div class="col-md-6">
            <div class="section-title">
                <h5 class="m-0">معلومات العميل</h5>
            </div>
            <div class="mb-4">
                <p class="mb-1"><strong>اسم العميل:</strong> <?= $customerInfo['name'] ?></p>
                <p class="mb-1"><strong>رقم الهاتف:</strong> <?= $customerInfo['phone'] ?></p>
                <?php if (!empty($customerInfo['address'])): ?>
                <p class="mb-1"><strong>العنوان:</strong> <?= $customerInfo['address'] ?></p>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="section-title">
                <h5 class="m-0">معلومات الدفع</h5>
            </div>
            <div class="mb-4">
                <p class="mb-1"><strong>تاريخ الدفع:</strong> <?= formatDate($paymentInfo['payment_date']) ?></p>
                <p class="mb-1"><strong>طريقة الدفع:</strong> <?= $paymentInfo['payment_method'] ?></p>
                <p class="mb-1"><strong>نوع الدفعة:</strong> <?= $paymentInfo['payment_type'] ?></p>
                <?php if (!empty($paymentInfo['created_at'])): ?>
                <p class="mb-1"><strong>تاريخ الإنشاء:</strong> <?= formatDateTime($paymentInfo['created_at']) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <div class="section-title">
        <h5 class="m-0">تفاصيل الفعالية</h5>
    </div>
    <div class="mb-4">
        <p class="mb-1"><strong>اسم الفعالية:</strong> <?= $eventInfo['title'] ?></p>
        <p class="mb-1"><strong>تاريخ الفعالية:</strong> <?= formatDate($eventInfo['date']) ?></p>
        <p class="mb-1"><strong>إجمالي تكلفة الباقة:</strong> <?= formatCurrency($eventInfo['total_package_cost']) ?></p>
    </div>
    
    <div class="section-title">
        <h5 class="m-0">تفاصيل المدفوعات</h5>
    </div>
    <div class="mb-4">
        <div class="table-responsive">
            <table class="table table-bordered">
                <thead class="bg-light">
                    <tr>
                        <th>المبلغ</th>
                        <th>البيان</th>
                        <th>ملاحظات</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-left h5 font-weight-bold"><?= formatCurrency($paymentInfo['amount']) ?></td>
                        <td>
                            <?php
                            switch ($paymentInfo['payment_type']) {
                                case 'دفعة مقدمة':
                                    echo 'دفعة مقدمة (عربون) لحجز الفعالية';
                                    break;
                                case 'دفعة نهائية':
                                    echo 'دفعة نهائية لتسوية حساب الفعالية';
                                    break;
                                case 'دفعة جزئية':
                                    echo 'دفعة جزئية من قيمة الفعالية';
                                    break;
                                default:
                                    echo 'دفعة من قيمة الفعالية';
                            }
                            ?>
                        </td>
                        <td><?= $paymentInfo['notes'] ? $paymentInfo['notes'] : 'لا توجد ملاحظات' ?></td>
                    </tr>
                </tbody>
                <tfoot class="bg-light">
                    <tr>
                        <th class="text-left"><?= formatCurrency($paymentInfo['amount']) ?></th>
                        <th colspan="2">المجموع</th>
                    </tr>
                </tfoot>
            </table>
        </div>
    </div>
    
    <!-- إجمالي المدفوعات والمتبقي -->
    <?php
    // الحصول على إجمالي المدفوعات للفعالية
    $totalPaid = $payment->getTotalPaidForEvent($paymentInfo['event_id']);
    $remaining = $eventInfo['total_package_cost'] - $totalPaid;
    ?>
    <div class="row mb-4">
        <div class="col-md-6">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3">ملخص المدفوعات</h5>
                    <table class="table table-sm">
                        <tr>
                            <td>إجمالي تكلفة الفعالية:</td>
                            <td class="text-left"><?= formatCurrency($eventInfo['total_package_cost']) ?></td>
                        </tr>
                        <tr>
                            <td>إجمالي المدفوعات:</td>
                            <td class="text-left"><?= formatCurrency($totalPaid) ?></td>
                        </tr>
                        <tr class="font-weight-bold <?= ($remaining > 0) ? 'text-danger' : 'text-success' ?>">
                            <td>المبلغ المتبقي:</td>
                            <td class="text-left"><?= formatCurrency($remaining) ?></td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
    </div>
    
    <!-- منطقة التوقيعات -->
    <div class="signature-area">
        <div class="signature-box">
            <p class="mb-5">توقيع المستلم</p>
            <div class="border-top pt-2">
                <p class="mb-0"><?= $paymentInfo['created_by_name'] ?></p>
            </div>
        </div>
        
        <div class="signature-box">
            <p class="mb-5">توقيع العميل</p>
            <div class="border-top pt-2">
                <p class="mb-0"><?= $customerInfo['name'] ?></p>
            </div>
        </div>
    </div>
    
    <div class="receipt-footer">
        <p class="mb-1">نشكركم على التعامل معنا</p>
        <p class="mb-1"><?= APP_NAME ?></p>
        <p class="mb-0">هاتف: +966 5XXXXXXXX | البريد الإلكتروني: support@majlis-catering.com</p>
    </div>
    
    <?php if (!$isPrintView): ?>
    <div class="text-center mt-4">
        <a href="receipt.php?id=<?= $payment_id ?>&print=true" target="_blank" class="btn btn-primary">
            <i class="fas fa-print"></i> طباعة الإيصال
        </a>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-right"></i> العودة إلى قائمة المدفوعات
        </a>
    </div>
    <?php else: ?>
    <div class="text-center mt-4 no-print">
        <button onclick="window.print()" class="btn btn-primary">
            <i class="fas fa-print"></i> طباعة
        </button>
        <button onclick="window.close()" class="btn btn-secondary">
            <i class="fas fa-times"></i> إغلاق
        </button>
    </div>
    <?php endif; ?>
</div>

<?php if ($isPrintView): ?>
<script>
    // اقتراح الطباعة تلقائياً عند تحميل الصفحة
    window.onload = function() {
        setTimeout(function() {
            window.print();
        }, 500);
    };
</script>
</body>
</html>
<?php else: ?>
    <?php
    // تضمين تذييل الصفحة
    include_once '../../../templates/footer.php';
    ?>
<?php endif; ?>
