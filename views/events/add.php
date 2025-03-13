<?php
/**
 * صفحة إضافة فعالية جديدة
 * 
 * تستخدم لإضافة فعالية جديدة في النظام
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
require_once '../../../classes/Customer.php';

// التحقق من تسجيل الدخول
$user = User::getCurrentUser();
if (!$user || !$user->isLoggedIn()) {
    redirect(BASE_URL . '/public/login.php');
}

// التحقق من الصلاحيات
if (!$user->hasPermission(['admin', 'manager'])) {
    setSystemMessage('error', 'ليس لديك صلاحية للوصول لهذه الصفحة');
    redirect(BASE_URL . '/public/views/events/index.php');
}

// إنشاء كائن الفعاليات والعملاء
$eventObj = new Event();
$customerObj = new Customer();

// الحصول على قوائم مرجعية
$eventTypes = $eventObj->getEventTypes();
$eventStatuses = $eventObj->getEventStatuses();
$customers = $customerObj->getCustomersForDropdown();

// متغيرات للتحقق من الأخطاء والقيم الافتراضية
$errors = [];
$formData = [
    'title' => '',
    'customer_id' => '',
    'event_type_id' => '',
    'date' => date('Y-m-d'),
    'start_time' => '18:00:00',
    'end_time' => '22:00:00',
    'location' => '',
    'guest_count' => '',
    'package_details' => '',
    'total_package_cost' => '',
    'status_id' => $eventObj->getDefaultStatusId(),
    'additional_notes' => ''
];

// التحقق من البيانات المرسلة
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من رمز CSRF
    if (!validateCSRFToken($_POST['csrf_token'])) {
        $errors[] = 'خطأ في التحقق من الأمان. يرجى تحديث الصفحة والمحاولة مرة أخرى.';
    } else {
        // استقبال البيانات من النموذج
        $formData = [
            'title' => isset($_POST['title']) ? trim($_POST['title']) : '',
            'customer_id' => isset($_POST['customer_id']) ? (int)$_POST['customer_id'] : 0,
            'event_type_id' => isset($_POST['event_type_id']) ? (int)$_POST['event_type_id'] : 0,
            'date' => isset($_POST['date']) ? $_POST['date'] : '',
            'start_time' => isset($_POST['start_time']) ? $_POST['start_time'] : '',
            'end_time' => isset($_POST['end_time']) ? $_POST['end_time'] : '',
            'location' => isset($_POST['location']) ? trim($_POST['location']) : '',
            'guest_count' => isset($_POST['guest_count']) ? (int)$_POST['guest_count'] : 0,
            'package_details' => isset($_POST['package_details']) ? trim($_POST['package_details']) : '',
            'total_package_cost' => isset($_POST['total_package_cost']) ? (float)$_POST['total_package_cost'] : 0,
            'status_id' => isset($_POST['status_id']) ? (int)$_POST['status_id'] : 0,
            'additional_notes' => isset($_POST['additional_notes']) ? trim($_POST['additional_notes']) : ''
        ];
        
        // التحقق من البيانات المطلوبة
        if (empty($formData['title'])) {
            $errors[] = 'يرجى إدخال عنوان الفعالية';
        }
        
        if (empty($formData['customer_id'])) {
            $errors[] = 'يرجى اختيار العميل';
        }
        
        if (empty($formData['event_type_id'])) {
            $errors[] = 'يرجى اختيار نوع الفعالية';
        }
        
        if (empty($formData['date'])) {
            $errors[] = 'يرجى إدخال تاريخ الفعالية';
        }
        
        if (empty($formData['start_time'])) {
            $errors[] = 'يرجى إدخال وقت بدء الفعالية';
        }
        
        if (empty($formData['end_time'])) {
            $errors[] = 'يرجى إدخال وقت انتهاء الفعالية';
        }
        
        // التحقق من صحة التاريخ والوقت
        if (!empty($formData['date']) && !empty($formData['start_time']) && !empty($formData['end_time'])) {
            $startDateTime = strtotime($formData['date'] . ' ' . $formData['start_time']);
            $endDateTime = strtotime($formData['date'] . ' ' . $formData['end_time']);
            
            if ($startDateTime >= $endDateTime) {
                $errors[] = 'وقت الانتهاء يجب أن يكون بعد وقت البدء';
            }
            
            // التحقق من تعارض المواعيد
            $conflicts = $eventObj->checkTimeConflicts(
                $formData['date'],
                $formData['start_time'],
                $formData['end_time']
            );
            
            if ($conflicts) {
                $errors[] = 'هناك تعارض في المواعيد مع فعاليات أخرى:';
                foreach ($conflicts as $conflict) {
                    $errors[] = '- ' . $conflict['title'] . ' (' . formatTime($conflict['start_time']) . ' - ' . formatTime($conflict['end_time']) . ')';
                }
            }
        }
        
        // إذا لم تكن هناك أخطاء، قم بإضافة الفعالية
        if (empty($errors)) {
            // إضافة الفعالية
            $eventId = $eventObj->create($formData);
            
            if ($eventId) {
                setSystemMessage('success', 'تمت إضافة الفعالية بنجاح');
                redirect(BASE_URL . '/public/views/events/view.php?id=' . $eventId);
            } else {
                $errors[] = 'حدث خطأ أثناء إضافة الفعالية. يرجى المحاولة مرة أخرى.';
            }
        }
    }
}

// إعداد عنوان الصفحة
$pageTitle = 'إضافة فعالية جديدة';

// تضمين قالب رأس الصفحة
include_once TEMPLATES_PATH . '/header.php';
?>

<!-- بداية محتوى الصفحة -->
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?php echo $pageTitle; ?></h1>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> العودة لقائمة الفعاليات
        </a>
    </div>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger">
        <h5><i class="fas fa-exclamation-triangle"></i> يرجى تصحيح الأخطاء التالية:</h5>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
            <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">بيانات الفعالية</h6>
        </div>
        <div class="card-body">
            <form method="post" action="add.php">
                <input type="hidden" name="csrf_token" value="<?php echo generateCSRFToken(); ?>">
                
                <div class="row">
                    <!-- معلومات الفعالية الأساسية -->
                    <div class="col-md-6">
                        <h5 class="mb-3">المعلومات الأساسية</h5>
                        
                        <div class="form-group">
                            <label for="title">عنوان الفعالية <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($formData['title']); ?>" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="customer_id">العميل <span class="text-danger">*</span></label>
                            <select class="form-control" id="customer_id" name="customer_id" required>
                                <option value="">اختر العميل</option>
                                <?php foreach ($customers as $customer): ?>
                                <option value="<?php echo $customer['id']; ?>" <?php echo ($formData['customer_id'] == $customer['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($customer['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                            <small class="form-text text-muted">
                                <a href="../customers/add.php" target="_blank">
                                    <i class="fas fa-plus-circle"></i> إضافة عميل جديد
                                </a>
                            </small>
                        </div>
                        
                        <div class="form-group">
                            <label for="event_type_id">نوع الفعالية <span class="text-danger">*</span></label>
                            <select class="form-control" id="event_type_id" name="event_type_id" required>
                                <option value="">اختر نوع الفعالية</option>
                                <?php foreach ($eventTypes as $type): ?>
                                <option value="<?php echo $type['id']; ?>" <?php echo ($formData['event_type_id'] == $type['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($type['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <div class="form-group">
                            <label for="location">موقع الفعالية</label>
                            <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($formData['location']); ?>">
                        </div>
                        
                        <div class="form-group">
                            <label for="guest_count">عدد الضيوف</label>
                            <input type="number" class="form-control" id="guest_count" name="guest_count" value="<?php echo htmlspecialchars($formData['guest_count']); ?>" min="0">
                        </div>
                    </div>
                    
                    <!-- معلومات التاريخ والوقت -->
                    <div class="col-md-6">
                        <h5 class="mb-3">التاريخ والوقت</h5>
                        
                        <div class="form-group">
                            <label for="date">تاريخ الفعالية <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="date" name="date" value="<?php echo htmlspecialchars($formData['date']); ?>" required>
                        </div>
                        
                        <div class="form-row">
                            <div class="form-group col-md-6">
                                <label for="start_time">وقت البدء <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="start_time" name="start_time" value="<?php echo htmlspecialchars($formData['start_time']); ?>" required>
                            </div>
                            
                            <div class="form-group col-md-6">
                                <label for="end_time">وقت الانتهاء <span class="text-danger">*</span></label>
                                <input type="time" class="form-control" id="end_time" name="end_time" value="<?php echo htmlspecialchars($formData['end_time']); ?>" required>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="status_id">حالة الفعالية <span class="text-danger">*</span></label>
                            <select class="form-control" id="status_id" name="status_id" required>
                                <?php foreach ($eventStatuses as $status): ?>
                                <option value="<?php echo $status['id']; ?>" <?php echo ($formData['status_id'] == $status['id']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($status['name']); ?>
                                </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <hr class="my-4">
                
                <!-- معلومات الباقة والتكلفة -->
                <div class="row">
                    <div class="col-md-12">
                        <h5 class="mb-3">تفاصيل الباقة والتكلفة</h5>
                        
                        <div class="form-group">
                            <label for="package_details">تفاصيل الباقة</label>
                            <textarea class="form-control" id="package_details" name="package_details" rows="4"><?php echo htmlspecialchars($formData['package_details']); ?></textarea>
                        </div>
                        
                        <div class="form-group">
                            <label for="total_package_cost">تكلفة الباقة الإجمالية</label>
                            <div class="input-group">
                                <input type="number" class="form-control" id="total_package_cost" name="total_package_cost" value="<?php echo htmlspecialchars($formData['total_package_cost']); ?>" step="0.01" min="0">
                                <div class="input-group-append">
                                    <span class="input-group-text"><?php echo $config['currency']; ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="form-group">
                            <label for="additional_notes">ملاحظات إضافية</label>
                            <textarea class="form-control" id="additional_notes" name="additional_notes" rows="3"><?php echo htmlspecialchars($formData['additional_notes']); ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="mt-4">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save"></i> حفظ الفعالية
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times"></i> إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php
// دالة مساعدة لتنسيق الوقت
function formatTime($time) {
    return date('h:i A', strtotime($time));
}
?>

<!-- نهاية محتوى الصفحة -->

<?php
// تضمين قالب تذييل الصفحة
include_once TEMPLATES_PATH . '/footer.php';
?>
