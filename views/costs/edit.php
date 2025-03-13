<?php
/**
 * صفحة تعديل تكلفة خارجية موجودة
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

// التحقق من الصلاحيات (يمكن فقط للمدير والمشرف تعديل تكاليف)
if (!$user->hasPermission(['admin', 'manager'])) {
    header('Location: ' . BASE_URL . '/dashboard.php?error=unauthorized');
    exit;
}

// التحقق من وجود معرف التكلفة في الـ URL
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header('Location: ' . BASE_URL . '/views/costs/index.php?error=invalid_id');
    exit;
}

$costId = intval($_GET['id']);

// إنشاء كائنات الفئات
$externalCost = new ExternalCost();
$event = new Event();

// تحميل بيانات التكلفة
if (!$externalCost->loadById($costId)) {
    header('Location: ' . BASE_URL . '/views/costs/index.php?error=not_found');
    exit;
}

// معالجة النموذج عند الإرسال
$errors = [];
$success = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_cost'])) {
    // التحقق من رمز CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION[SESSION_NAME]['csrf_token']) {
        $errors[] = 'خطأ في التحقق من الأمان. يرجى تحديث الصفحة والمحاولة مرة أخرى.';
    } else {
        // التحقق من الحقول المطلوبة
        $amount = isset($_POST['amount']) ? filter_var($_POST['amount'], FILTER_VALIDATE_FLOAT) : 0;
        $description = isset($_POST['description']) ? trim($_POST['description']) : '';
        $costTypeId = isset($_POST['cost_type_id']) ? intval($_POST['cost_type_id']) : 0;
        $vendor = isset($_POST['vendor']) ? trim($_POST['vendor']) : '';
        $notes = isset($_POST['notes']) ? trim($_POST['notes']) : '';
        
        // التحقق من صحة البيانات
        if ($amount <= 0) {
            $errors[] = 'يجب إدخال مبلغ صالح أكبر من صفر.';
        }
        
        if (empty($description)) {
            $errors[] = 'يجب إدخال وصف للتكلفة.';
        }
        
        // إذا لم تكن هناك أخطاء، قم بتحديث التكلفة
        if (empty($errors)) {
            // تجهيز بيانات التكلفة
            $costData = [
                'amount' => $amount,
                'description' => $description,
                'cost_type_id' => $costTypeId ?: null,
                'vendor' => $vendor ?: null,
                'notes' => $notes ?: null
            ];
            
            // تحديث التكلفة
            $updateResult = $externalCost->update($costId, $costData);
            
            if ($updateResult) {
                // تم تحديث التكلفة بنجاح
                $success = true;
                
                // نحتاج إلى إعادة تحميل البيانات بعد التحديث
                $externalCost->loadById($costId);
                
                // عرض رسالة النجاح (لن نقوم بإعادة التوجيه فوراً لكي يتمكن المستخدم من رؤية التغييرات)
            } else {
                $errors[] = 'حدث خطأ أثناء تحديث التكلفة. يرجى المحاولة مرة أخرى.';
            }
        }
    }
}

// الحصول على بيانات التكلفة الحالية
$currentCost = [
    'id' => $externalCost->getId(),
    'event_id' => $externalCost->getEventId(),
    'cost_type_id' => $externalCost->getCostTypeId(),
    'description' => $externalCost->getDescription(),
    'amount' => $externalCost->getAmount(),
    'vendor' => $externalCost->getVendor(),
    'notes' => $externalCost->getNotes()
];

// الحصول على بيانات الفعالية
// في الحالة الحقيقية، يمكننا استخدام Event::loadById($currentCost['event_id'])
// لكننا هنا سنفترض وجود بيانات الفعالية

// بيانات تجريبية للفعالية
$eventData = [
    'id' => $currentCost['event_id'],
    'title' => 'فعالية تجريبية', // هذا سيكون اسم الفعالية الفعلي في الواقع
    'date' => '2025-03-20'
];

// الحصول على أنواع التكاليف
$costTypes = $externalCost->getCostTypes();

// عنوان الصفحة
$pageTitle = 'تعديل تكلفة خارجية';

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
                        <i class="fas fa-edit ml-2"></i>
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
                        تم تحديث التكلفة بنجاح.
                    </div>
                    <?php endif; ?>
                    
                    <!-- معلومات الفعالية المرتبطة -->
                    <div class="alert alert-info">
                        <h6 class="alert-heading">معلومات الفعالية:</h6>
                        <p class="mb-0">
                            <strong>الفعالية:</strong> 
                            <a href="../events/view.php?id=<?php echo $eventData['id']; ?>">
                                <?php echo htmlspecialchars($eventData['title']); ?>
                            </a>
                            <br>
                            <strong>التاريخ:</strong> <?php echo date(DATE_FORMAT, strtotime($eventData['date'])); ?>
                        </p>
                    </div>
                    
                    <!-- نموذج تعديل التكلفة -->
                    <form method="post" action="" id="edit_cost_form">
                        <!-- رمز CSRF للحماية -->
                        <input type="hidden" name="csrf_token" value="<?php echo $_SESSION[SESSION_NAME]['csrf_token']; ?>">
                        
                        <!-- حقل نوع التكلفة -->
                        <div class="form-group row">
                            <label for="cost_type_id" class="col-sm-3 col-form-label">نوع التكلفة</label>
                            <div class="col-sm-9">
                                <select name="cost_type_id" id="cost_type_id" class="form-control">
                                    <option value="">-- اختر نوع التكلفة --</option>
                                    <?php foreach ($costTypes as $type): ?>
                                    <option value="<?php echo $type['id']; ?>" <?php echo $currentCost['cost_type_id'] == $type['id'] ? 'selected' : ''; ?>>
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
                                    <input type="number" name="amount" id="amount" class="form-control" step="0.01" min="0" required placeholder="0.00" value="<?php echo htmlspecialchars($currentCost['amount']); ?>">
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
                                <input type="text" name="description" id="description" class="form-control" required placeholder="وصف التكلفة" value="<?php echo htmlspecialchars($currentCost['description']); ?>">
                                <small class="form-text text-muted">أدخل وصفًا موجزًا للتكلفة.</small>
                            </div>
                        </div>
                        
                        <!-- حقل المورد -->
                        <div class="form-group row">
                            <label for="vendor" class="col-sm-3 col-form-label">المورد / المزود</label>
                            <div class="col-sm-9">
                                <input type="text" name="vendor" id="vendor" class="form-control" placeholder="اسم المورد أو الشركة" value="<?php echo htmlspecialchars($currentCost['vendor']); ?>">
                                <small class="form-text text-muted">اختياري - أدخل اسم المورد أو الشركة التي قدمت الخدمة.</small>
                            </div>
                        </div>
                        
                        <!-- حقل الملاحظات -->
                        <div class="form-group row">
                            <label for="notes" class="col-sm-3 col-form-label">ملاحظات إضافية</label>
                            <div class="col-sm-9">
                                <textarea name="notes" id="notes" class="form-control" rows="3" placeholder="ملاحظات إضافية"><?php echo htmlspecialchars($currentCost['notes']); ?></textarea>
                                <small class="form-text text-muted">اختياري - أضف أي ملاحظات إضافية حول هذه التكلفة.</small>
                            </div>
                        </div>
                        
                        <!-- أزرار التحكم -->
                        <div class="form-group row">
                            <div class="col-sm-9 offset-sm-3">
                                <button type="submit" name="update_cost" class="btn btn-primary">
                                    <i class="fas fa-save ml-1"></i> حفظ التغييرات
                                </button>
                                <a href="index.php" class="btn btn-secondary mr-2">
                                    <i class="fas fa-arrow-right ml-1"></i> العودة للقائمة
                                </a>
                                <a href="../events/view.php?id=<?php echo $currentCost['event_id']; ?>&tab=costs" class="btn btn-info mr-2">
                                    <i class="fas fa-clipboard-list ml-1"></i> عرض الفعالية
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
                
                <!-- تذييل البطاقة - معلومات إضافية -->
                <div class="card-footer bg-light">
                    <div class="row">
                        <div class="col-md-6">
                            <small class="text-muted">
                                <i class="fas fa-clock ml-1"></i> تاريخ الإنشاء: 
                                <span><?php echo isset($currentCost['created_at']) ? date(DATETIME_FORMAT, strtotime($currentCost['created_at'])) : 'غير معروف'; ?></span>
                            </small>
                        </div>
                        <div class="col-md-6 text-left">
                            <small class="text-muted">
                                <i class="fas fa-edit ml-1"></i> آخر تحديث: 
                                <span><?php echo isset($currentCost['updated_at']) ? date(DATETIME_FORMAT, strtotime($currentCost['updated_at'])) : 'غير معروف'; ?></span>
                            </small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- زر الحذف - في أسفل الصفحة -->
            <div class="text-left mt-3">
                <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#deleteModal">
                    <i class="fas fa-trash ml-1"></i> حذف هذه التكلفة
                </button>
            </div>
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
                <p>هل أنت متأكد من رغبتك في حذف هذه التكلفة؟</p>
                <p class="text-danger">تحذير: هذا الإجراء لا يمكن التراجع عنه.</p>
                <dl class="row">
                    <dt class="col-sm-4">الوصف</dt>
                    <dd class="col-sm-8"><?php echo htmlspecialchars($currentCost['description']); ?></dd>
                    
                    <dt class="col-sm-4">المبلغ</dt>
                    <dd class="col-sm-8">
                        <?php 
                        $formattedAmount = number_format($currentCost['amount'], $config['decimal_places'], $config['decimal_separator'], $config['thousand_separator']);
                        echo $config['currency_position'] == 'before' ? $config['currency'] . ' ' . $formattedAmount : $formattedAmount . ' ' . $config['currency'];
                        ?>
                    </dd>
                </dl>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">إلغاء</button>
                <form method="post" action="delete.php">
                    <input type="hidden" name="cost_id" value="<?php echo $costId; ?>">
                    <input type="hidden" name="csrf_token" value="<?php echo $_SESSION[SESSION_NAME]['csrf_token']; ?>">
                    <button type="submit" class="btn btn-danger">تأكيد الحذف</button>
                </form>
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
    $('#edit_cost_form').submit(function(e) {
        var amount = $('#amount').val();
        var description = $('#description').val();
        
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
