<?php
/**
 * صفحة إضافة تكلفة خارجية جديدة
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
require_once '../../classes/Event.php';

// التحقق من تسجيل الدخول
$user = User::getCurrentUser();
if (!$user || !$user->isLoggedIn()) {
    header('Location: ' . BASE_URL . '/login.php');
    exit;
}

// التحقق من الصلاحيات (يمكن فقط للمدير والمشرف إضافة تكاليف)
if (!$user->hasPermission(['admin', 'manager'])) {
    header('Location: ' . BASE_URL . '/dashboard.php?error=unauthorized');
    exit;
}

// إنشاء كائنات الفئات
$externalCost = new ExternalCost();
$event = new Event();

// معالجة النموذج عند الإرسال
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_cost'])) {
    // التحقق من رمز CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION[SESSION_NAME]['csrf_token']) {
        $errors[] = 'خطأ في التحقق من الأمان. يرجى تحديث الصفحة والمحاولة مرة أخرى.';
    } else {
        // التحقق من الحقول المطلوبة
        $eventId = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
        $amount = isset($_POST['amount']) ? filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT) : 0;
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $costTypeId = isset($_POST['cost_type_id']) ? intval($_POST['cost_type_id']) : 0;
        $vendor = isset($_POST['vendor']) ? trim($_POST['vendor']) : '';
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
        
        // التحقق من صحة البيانات
        if ($eventId <= 0) {
            $errors[] = 'يجب اختيار فعالية صالحة.';
        }
        
        if ($amount <= 0) {
            $errors[] = 'يجب إدخال مبلغ صالح أكبر من صفر.';
        }
        
        if (empty($description)) {
            $errors[] = 'يجب إدخال وصف للتكلفة.';
        }
        
        // إذا لم تكن هناك أخطاء، قم بإضافة التكلفة
        if (empty($errors)) {
            // تجهيز بيانات التكلفة
            $costData = [
                'event_id' => $eventId,
                'amount' => $amount,
                'description' => $description,
                'cost_type_id' => $costTypeId ?: null,
                'vendor' => $vendor ?: null,
                'notes' => $notes ?: null
            ];
            
            // إضافة التكلفة
            $newCostId = $externalCost->create($costData);
            
            if ($newCostId) {
                // تم إضافة التكلفة بنجاح
                $success = true;
                
                // إعادة التوجيه إلى صفحة قائمة التكاليف أو صفحة تفاصيل الفعالية
                if (isset($_POST['redirect_to_event']) && $_POST['redirect_to_event'] == 1) {
                    header('Location: ' . BASE_URL . '/views/events/view.php?id=' . $eventId . '&tab=costs&success=cost_added');
                    exit;
                } else {
                    header('Location: ' . BASE_URL . '/views/costs/index.php?success=cost_added');
                    exit;
                }
            } else {
                $errors[] = 'حدث خطأ أثناء إضافة التكلفة. يرجى المحاولة مرة أخرى.';
            }
        }
    }
}

// الحصول على قائمة الفعاليات للقائمة المنسدلة
// ملاحظة: في الحالة الحقيقية، قد نحتاج إلى الحصول على الفعاليات النشطة فقط أو فلترة القائمة
$eventParam = isset($_GET['event_id']) ? intval($_GET['event_id']) : 0;

// لنفترض أن هناك دالة للحصول على الفعاليات للقائمة المنسدلة
// هذه الدالة غير موجودة في كود الفئة المقدم، لذا سنفترض وجودها
// $events = $event->getEventsForDropdown();

// سنقوم بعمل مصفوفة تجريبية للفعاليات
$events = [
    ['id' => 1, 'title' => 'حفل زفاف محمد وسارة', 'date' => '2025-03-20'],
    ['id' => 2, 'title' => 'معرض تقنية المعلومات السنوي', 'date' => '2025-04-15'],
    ['id' => 3, 'title' => 'افتتاح مقر الشركة الجديد', 'date' => '2025-03-25']
];

// الحصول على أنواع التكاليف
$costTypes = $externalCost->getCostTypes();

// عنوان الصفحة
$pageTitle = 'إضافة تكلفة خارجية جديدة';

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
                        <i class="fas fa-plus-circle ml-2"></i>
                        <?php echo $pageTitle; ?>
                    </h5>
                </div>
                
                <div class="card-body">
                    <!-- عرض الأخطاء إن وجدت -->
                    <?php if (!empty($errors)): ?>
                    <div class="alert alert-danger">
                        <ul class="mb-0">
                            <?php foreach ($errors as $error): ?>
                            <li><?php echo $error; ?></li>
                            <?php endforeach; ?>
                        </ul>
                    </div>
                    <?php endif; ?>
                    
                    <!-- عرض رسالة النجاح إن وجدت -->
                    <?php if ($success): ?>
                    <div class="alert alert-success">
                        تمت إضافة التكلفة بنجاح.
                    </div>
                    <?php endif; ?>
                    
                    <!-- نموذج إضافة تكلفة جديدة -->
                    <form method="post" action="" id="add_cost_form">
                        <!-- رمز CSRF للحماية -->
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION[SESSION_NAME]['csrf_token']; ?>">
                        
                        <!-- حقل الفعالية -->
                        <div class="form-group row">
                            <label for="event_id" class="col-sm-3 col-form-label">الفعالية <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <select name="event_id" id="event_id" class="form-control" required>
                                    <option value="">-- اختر الفعالية --</option>
                                    <?php foreach ($events as $evt): ?>
                                    <option value="<?php echo $evt['id']; ?>" <?php echo $eventParam == $evt['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($evt['title']) . ' (' . date(DATE_FORMAT, strtotime($evt['date'])) . ')'; ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">حدد الفعالية التي تريد إضافة التكلفة لها.</small>
                            </div>
                        </div>
                        
                        <!-- حقل نوع التكلفة -->
                        <div class="form-group row">
                            <label for="cost_type_id" class="col-sm-3 col-form-label">نوع التكلفة</label>
                            <div class="col-sm-9">
                                <select name="cost_type_id" id="cost_type_id" class="form-control">
                                    <option value="">-- اختر نوع التكلفة --</option>
                                    <?php foreach ($costTypes as $type): ?>
                                    <option value="<?php echo $type['id']; ?>">
                                        <?php echo htmlspecialchars($type['name']); ?>
                                    </option>
                                    <?php endforeach; ?>
                                </select>
                                <small class="form-text text-muted">اختياري - حدد تصنيف التكلفة.</small>
                            </div>
                        </div>
                        
                        <!-- حقل المبلغ -->
                        <div class="form-group row">
                            <label for="amount" class="col-sm-3 col-form-label">المبلغ <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <div class="input-group">
                                    <input type="number" name="amount" id="amount" class="form-control" step="0.01" min="0" required placeholder="0.00" value="<?php echo isset($_POST['amount']) ? htmlspecialchars($_POST['amount']) : ''; ?>">
                                    <div class="input-group-append">
                                        <span class="input-group-text"><?php echo $config['currency']; ?></span>
                                    </div>
                                </div>
                                <small class="form-text text-muted">أدخل مبلغ التكلفة.</small>
                            </div>
                        </div>
                        
                        <!-- حقل الوصف -->
                        <div class="form-group row">
                            <label for="description" class="col-sm-3 col-form-label">الوصف <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" name="description" id="description" class="form-control" required placeholder="وصف التكلفة" value="<?php echo isset($_POST['description']) ? htmlspecialchars($_POST['description']) : ''; ?>">
                                <small class="form-text text-muted">أدخل وصفًا موجزًا للتكلفة.</small>
                            </div>
                        </div>
                        
                        <!-- حقل المورد -->
                        <div class="form-group row">
                            <label for="vendor" class="col-sm-3 col-form-label">المورد / المزود</label>
                            <div class="col-sm-9">
                                <input type="text" name="vendor" id="vendor" class="form-control" placeholder="اسم المورد أو الشركة" value="<?php echo isset($_POST['vendor']) ? htmlspecialchars($_POST['vendor']) : ''; ?>">
                                <small class="form-text text-muted">اختياري - أدخل اسم المورد أو الشركة التي قدمت الخدمة.</small>
                            </div>
                        </div>
                        
                        <!-- حقل الملاحظات -->
                        <div class="form-group row">
                            <label for="notes" class="col-sm-3 col-form-label">ملاحظات إضافية</label>
                            <div class="col-sm-9">
                                <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="ملاحظات إضافية"><?php echo isset($_POST['notes']) ? htmlspecialchars($_POST['notes']) : ''; ?></textarea>
                                <small class="form-text text-muted">اختياري - أضف أي ملاحظات إضافية حول هذه التكلفة.</small>
                            </div>
                        </div>
                        
                        <!-- خيار إعادة التوجيه -->
                        <div class="form-group row">
                            <div class="col-sm-9 offset-sm-3">
                                <div class="form-check">
                                    <input type="checkbox" name="redirect_to_event" id="redirect_to_event" class="form-check-input" value="1" <?php echo (isset($_POST['redirect_to_event']) && $_POST['redirect_to_event'] == 1) || isset($_GET['event_id']) ? 'checked' : ''; ?>>
                                    <label for="redirect_to_event" class="form-check-label">العودة إلى صفحة الفعالية بعد الإضافة</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- أزرار التحكم -->
                        <div class="form-group row">
                            <div class="col-sm-9 offset-sm-3">
                                <button type="submit" name="add_cost" class="btn btn-primary">
                                    <i class="fas fa-save ml-1"></i> حفظ التكلفة
                                </button>
                                <a href="index.php" class="btn btn-secondary mr-2">
                                    <i class="fas fa-times ml-1"></i> إلغاء
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // تنسيق حقل المبلغ
    $('#amount').on('input', function() {
        var value = $(this).val();
        if (value && !isNaN(value)) {
            $(this).val(parseFloat(value).toFixed(2));
        }
    });
    
    // التحقق من صحة النموذج قبل الإرسال
    $('#add_cost_form').submit(function(e) {
        var eventId = $('#event_id').val();
        var amount = $('#amount').val();
        var description = $('#description').val();
        
        if (!eventId) {
            e.preventDefault();
            alert('يرجى اختيار فعالية');
            $('#event_id').focus();
            return false;
        }
        
        if (!amount || amount <= 0) {
            e.preventDefault();
            alert('يرجى إدخال مبلغ صحيح أكبر من صفر');
            $('#amount').focus();
            return false;
        }
        
        if (!description.trim()) {
            e.preventDefault();
            alert('يرجى إدخال وصف للتكلفة');
            $('#description').focus();
            return false;
        }
    });
});
</script>

<?php include '../../templates/footer.php'; ?>
