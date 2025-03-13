<?php
/**
 * صفحة إضافة دفعة جديدة
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

// التحقق من الصلاحيات (مدير أو مشرف فقط)
if (!$currentUser->hasPermission(['admin', 'manager'])) {
    redirect('../dashboard.php', 'لا تملك الصلاحية للوصول إلى هذه الصفحة', 'error');
}

// إنشاء كائنات للفئات المطلوبة
$customerObj = new Customer();
$eventObj = new Event();
$paymentObj = new Payment();

// معالجة النموذج عند الإرسال
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_payment'])) {
    // القيام بعملية التحقق من البيانات المدخلة
    $event_id = isset($_POST['event_id']) ? (int)$_POST['event_id'] : 0;
    $amount = isset($_POST['amount']) ? trim($_POST['amount']) : '';
    $payment_date = isset($_POST['payment_date']) ? trim($_POST['payment_date']) : '';
    $payment_method = isset($_POST['payment_method']) ? trim($_POST['payment_method']) : '';
    $payment_type = isset($_POST['payment_type']) ? trim($_POST['payment_type']) : 'دفعة عادية';
    $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
    
    // التحقق من صحة البيانات
    if ($event_id <= 0) {
        $errors[] = 'يرجى اختيار فعالية صالحة';
    }
    
    if (empty($amount) || !is_numeric($amount) || $amount <= 0) {
        $errors[] = 'يرجى إدخال مبلغ صالح أكبر من صفر';
    }
    
    if (empty($payment_date)) {
        $errors[] = 'يرجى إدخال تاريخ الدفع';
    }
    
    if (empty($payment_method)) {
        $errors[] = 'يرجى اختيار طريقة الدفع';
    }
    
    // إذا لم تكن هناك أخطاء، قم بإدراج الدفعة
    if (empty($errors)) {
        // تحضير بيانات الدفعة
        $paymentData = [
            'event_id' => $event_id,
            'amount' => $amount,
            'payment_date' => $payment_date,
            'payment_method' => $payment_method,
            'payment_type' => $payment_type,
            'notes' => $notes,
            'created_by' => $currentUser->getId()
        ];
        
        // إدراج الدفعة في قاعدة البيانات
        $payment_id = $paymentObj->createPayment($paymentData);
        
        if ($payment_id) {
            // توجيه المستخدم إلى صفحة الإيصال أو العودة إلى قائمة المدفوعات
            redirect('receipt.php?id=' . $payment_id, 'تمت إضافة الدفعة بنجاح', 'success');
        } else {
            $errors[] = 'حدث خطأ أثناء إضافة الدفعة. يرجى المحاولة مرة أخرى';
        }
    }
}

// الحصول على معرف الفعالية من المعلمات (إذا تم تمريرها)
$selected_event_id = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;

// تضمين رأس الصفحة
$pageTitle = 'إضافة دفعة جديدة';
include_once '../../../templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800">إضافة دفعة جديدة</h1>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-right"></i> العودة إلى قائمة المدفوعات
        </a>
    </div>
    
    <?php if (!empty($errors)): ?>
        <div class="alert alert-danger">
            <ul class="mb-0">
                <?php foreach ($errors as $error): ?>
                    <li><?= $error ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>
    
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">بيانات الدفعة</h6>
        </div>
        <div class="card-body">
            <form method="post" action="">
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label for="event_id" class="required">الفعالية</label>
                        <select id="event_id" name="event_id" class="form-control" required>
                            <option value="">-- اختر الفعالية --</option>
                            <?php
                            // الحصول على قائمة الفعاليات النشطة
                            $events = $eventObj->getEvents(['status_id' => [1, 2, 3]], 100, 0); // نفترض أن الحالات 1-3 هي للفعاليات النشطة
                            
                            foreach ($events as $event):
                                $selected = ($event['id'] == $selected_event_id) ? 'selected' : '';
                            ?>
                                <option value="<?= $event['id'] ?>" <?= $selected ?>>
                                    <?= $event['title'] ?> - <?= formatDate($event['date']) ?> (<?= $event['customer_name'] ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="form-text text-muted">اختر الفعالية التي تريد تسجيل الدفعة لها</small>
                    </div>
                    
                    <div class="col-md-6 mb-3">
                        <label for="amount" class="required">المبلغ</label>
                        <div class="input-group">
                            <input type="number" step="0.01" min="0.01" id="amount" name="amount" class="form-control text-start" placeholder="0.00" required>
                            <div class="input-group-append">
                                <span class="input-group-text"><?= $config['currency'] ?></span>
                            </div>
                        </div>
                        <small class="form-text text-muted">أدخل مبلغ الدفعة بالريال السعودي</small>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <label for="payment_date" class="required">تاريخ الدفع</label>
                        <input type="date" id="payment_date" name="payment_date" class="form-control" value="<?= date('Y-m-d') ?>" required>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="payment_method" class="required">طريقة الدفع</label>
                        <select id="payment_method" name="payment_method" class="form-control" required>
                            <option value="">-- اختر طريقة الدفع --</option>
                            <option value="نقداً">نقداً</option>
                            <option value="تحويل بنكي">تحويل بنكي</option>
                            <option value="بطاقة ائتمان">بطاقة ائتمان</option>
                            <option value="شيك">شيك</option>
                            <option value="نقاط البيع">نقاط البيع</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 mb-3">
                        <label for="payment_type">نوع الدفعة</label>
                        <select id="payment_type" name="payment_type" class="form-control">
                            <option value="دفعة عادية">دفعة عادية</option>
                            <option value="دفعة مقدمة">دفعة مقدمة (عربون)</option>
                            <option value="دفعة نهائية">دفعة نهائية</option>
                            <option value="دفعة جزئية">دفعة جزئية</option>
                        </select>
                    </div>
                </div>
                
                <div class="row">
                    <div class="col-12 mb-3">
                        <label for="notes">ملاحظات</label>
                        <textarea id="notes" name="notes" class="form-control" rows="3" placeholder="أي ملاحظات إضافية عن الدفعة..."></textarea>
                    </div>
                </div>

                <hr>
                
                <div class="row">
                    <div class="col-12">
                        <button type="submit" name="add_payment" class="btn btn-primary">
                            <i class="fas fa-save"></i> حفظ الدفعة
                        </button>
                        <a href="index.php" class="btn btn-secondary">
                            <i class="fas fa-times"></i> إلغاء
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // إضافة مستمع حدث للتغيير في حقل الفعالية
    const eventSelect = document.getElementById('event_id');
    
    eventSelect.addEventListener('change', function() {
        if (this.value) {
            // يمكن إضافة أي منطق إضافي هنا عند اختيار الفعالية
            // مثلاً: جلب معلومات العميل أو الباقة أو المبلغ المتبقي... إلخ
        }
    });
});
</script>

<?php
// تضمين تذييل الصفحة
include_once '../../../templates/footer.php';
?>
