<?php
/**
 * صفحة إضافة مهمة جديدة
 * تسمح للمستخدم بإضافة مهمة جديدة وربطها بفعالية
 */

// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    exit('لا يمكن الوصول المباشر لهذا الملف');
}

// التحقق من تسجيل الدخول
if (!isset($_SESSION[SESSION_NAME]['user_id'])) {
    redirect('login.php');
}

// تهيئة كائنات الفئات المطلوبة
$taskObj = new Task();
$eventObj = new Event();
$userObj = new User();

// الحصول على المستخدم الحالي
$currentUser = User::getCurrentUser();

// التحقق من صلاحية المستخدم لإضافة مهام
if (!$currentUser->hasPermission(['admin', 'manager', 'staff'])) {
    set_flash_message('error', 'ليس لديك صلاحية للوصول لهذه الصفحة');
    redirect('dashboard.php');
}

// تحديد ما إذا كان المستخدم مديراً (يمكنه تعيين المهام للآخرين)
$isManager = $currentUser->isManager();

// التحقق من تحديد فعالية (اختياري)
$eventId = isset($_GET['event_id']) ? (int)$_GET['event_id'] : 0;
$event = null;

// إذا تم تحديد فعالية، تحقق من وجودها
if ($eventId > 0) {
    $event = new Event();
    if (!$event->loadEventById($eventId)) {
        set_flash_message('error', 'الفعالية غير موجودة');
        redirect('events/index.php');
    }
}

// معالجة إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من توكن CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION[SESSION_NAME]['csrf_token']) {
        set_flash_message('error', 'خطأ في التحقق من الأمان. يرجى المحاولة مرة أخرى.');
        redirect('tasks/add.php' . ($eventId ? "?event_id=$eventId" : ''));
    }
    
    // التحقق من إدخال العنوان (حقل إلزامي)
    if (empty($_POST['title'])) {
        set_flash_message('error', 'يرجى إدخال عنوان المهمة');
    } else {
        // تحضير بيانات المهمة
        $taskData = [
            'title' => trim($_POST['title']),
            'created_by' => $currentUser->getId()
        ];
        
        // إضافة معرف الفعالية إذا تم توفيره
        if (!empty($_POST['event_id'])) {
            $taskData['event_id'] = (int)$_POST['event_id'];
        } elseif ($eventId > 0) {
            $taskData['event_id'] = $eventId;
        } else {
            set_flash_message('error', 'يرجى اختيار فعالية');
            $errors = true;
        }
        
        // إضافة وصف المهمة إذا تم توفيره
        if (!empty($_POST['description'])) {
            $taskData['description'] = trim($_POST['description']);
        }
        
        // إضافة المستخدم المعين إذا تم توفيره وكان المستخدم الحالي مديراً
        if (!empty($_POST['assigned_to']) && $isManager) {
            $taskData['assigned_to'] = (int)$_POST['assigned_to'];
        } else {
            // تعيين المهمة للمستخدم الحالي إذا لم يتم تحديد مستخدم
            $taskData['assigned_to'] = $currentUser->getId();
        }
        
        // إضافة تاريخ الاستحقاق إذا تم توفيره
        if (!empty($_POST['due_date'])) {
            $taskData['due_date'] = $_POST['due_date'];
        }
        
        // إضافة الأولوية إذا تم توفيرها
        if (!empty($_POST['priority'])) {
            $taskData['priority'] = $_POST['priority'];
        } else {
            $taskData['priority'] = 'medium'; // الأولوية الافتراضية
        }
        
        // إضافة الحالة إذا تم توفيرها
        if (!empty($_POST['status'])) {
            $taskData['status'] = $_POST['status'];
        } else {
            $taskData['status'] = 'pending'; // الحالة الافتراضية
        }
        
        // محاولة إنشاء المهمة
        if (!isset($errors)) {
            $taskId = $taskObj->create($taskData);
            
            if ($taskId) {
                // تمت إضافة المهمة بنجاح
                set_flash_message('success', 'تمت إضافة المهمة بنجاح');
                
                // إذا تم تحديد فعالية، قم بالتوجيه إلى صفحتها
                if ($eventId > 0) {
                    redirect('events/view.php?id=' . $eventId . '#tasks');
                } else {
                    redirect('tasks/index.php');
                }
            } else {
                set_flash_message('error', 'حدث خطأ أثناء إضافة المهمة. يرجى المحاولة مرة أخرى.');
            }
        }
    }
}

// الحصول على قائمة الفعاليات النشطة للاختيار منها (إذا لم يتم تحديد فعالية مسبقاً)
$events = [];
if ($eventId === 0) {
    $events = $eventObj->getEvents(['status' => 'active']);
}

// الحصول على قائمة المستخدمين للاختيار منهم (للمدراء فقط)
$users = [];
if ($isManager) {
    $users = $userObj->getUsers(['role' => ['admin', 'manager', 'staff']]);
}

// إنشاء توكن CSRF للنموذج
$_SESSION[SESSION_NAME]['csrf_token'] = md5(uniqid(mt_rand(), true));

// تضمين رأس الصفحة
include TEMPLATES_PATH . '/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold text-primary">إضافة مهمة جديدة</h6>
                </div>
                <div class="card-body">
                    <!-- عرض رسائل النجاح أو الخطأ -->
                    <?php display_flash_messages(); ?>
                    
                    <form method="post" action="<?= BASE_URL ?>/views/tasks/add.php<?= $eventId ? "?event_id=$eventId" : '' ?>">
                        <!-- توكن CSRF -->
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION[SESSION_NAME]['csrf_token'] ?>">
                        
                        <!-- اختيار الفعالية -->
                        <div class="form-group row">
                            <label for="event_id" class="col-sm-3 col-form-label">الفعالية <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <?php if ($eventId > 0 && $event): ?>
                                    <input type="hidden" name="event_id" value="<?= $eventId ?>">
                                    <p class="form-control-static">
                                        <a href="<?= BASE_URL ?>/views/events/view.php?id=<?= $eventId ?>"><?= htmlspecialchars($event->getTitle()) ?></a>
                                        (<?= date(DATE_FORMAT, strtotime($event->getDate())) ?>)
                                    </p>
                                <?php else: ?>
                                    <select name="event_id" id="event_id" class="form-control" required>
                                        <option value="">-- اختر فعالية --</option>
                                        <?php foreach ($events as $evt): ?>
                                            <option value="<?= $evt['id'] ?>"><?= htmlspecialchars($evt['title']) ?> (<?= date(DATE_FORMAT, strtotime($evt['date'])) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- عنوان المهمة -->
                        <div class="form-group row">
                            <label for="title" class="col-sm-3 col-form-label">عنوان المهمة <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="title" name="title" required 
                                       value="<?= isset($_POST['title']) ? htmlspecialchars($_POST['title']) : '' ?>">
                            </div>
                        </div>
                        
                        <!-- وصف المهمة -->
                        <div class="form-group row">
                            <label for="description" class="col-sm-3 col-form-label">وصف المهمة</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="description" name="description" rows="4"><?= isset($_POST['description']) ? htmlspecialchars($_POST['description']) : '' ?></textarea>
                                <small class="form-text text-muted">وصف تفصيلي للمهمة وما هو المطلوب إنجازه</small>
                            </div>
                        </div>
                        
                        <!-- المستخدم المعين (للمدراء فقط) -->
                        <?php if ($isManager): ?>
                        <div class="form-group row">
                            <label for="assigned_to" class="col-sm-3 col-form-label">تعيين إلى</label>
                            <div class="col-sm-9">
                                <select name="assigned_to" id="assigned_to" class="form-control">
                                    <option value="<?= $currentUser->getId() ?>">أنا (<?= htmlspecialchars($currentUser->getName()) ?>)</option>
                                    <?php foreach ($users as $user): ?>
                                        <?php if ($user['id'] != $currentUser->getId()): ?>
                                            <option value="<?= $user['id'] ?>" <?= isset($_POST['assigned_to']) && $_POST['assigned_to'] == $user['id'] ? 'selected' : '' ?>>
                                                <?= htmlspecialchars($user['name']) ?>
                                            </option>
                                        <?php endif; ?>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- تاريخ الاستحقاق -->
                        <div class="form-group row">
                            <label for="due_date" class="col-sm-3 col-form-label">تاريخ الاستحقاق</label>
                            <div class="col-sm-9">
                                <input type="date" class="form-control" id="due_date" name="due_date" 
                                       value="<?= isset($_POST['due_date']) ? $_POST['due_date'] : date('Y-m-d') ?>">
                                <small class="form-text text-muted">التاريخ المطلوب إنجاز المهمة فيه</small>
                            </div>
                        </div>
                        
                        <!-- أولوية المهمة -->
                        <div class="form-group row">
                            <label for="priority" class="col-sm-3 col-form-label">الأولوية</label>
                            <div class="col-sm-9">
                                <select name="priority" id="priority" class="form-control">
                                    <option value="high" <?= isset($_POST['priority']) && $_POST['priority'] === 'high' ? 'selected' : '' ?>>عالية</option>
                                    <option value="medium" <?= (!isset($_POST['priority']) || $_POST['priority'] === 'medium') ? 'selected' : '' ?>>متوسطة</option>
                                    <option value="low" <?= isset($_POST['priority']) && $_POST['priority'] === 'low' ? 'selected' : '' ?>>منخفضة</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- حالة المهمة -->
                        <div class="form-group row">
                            <label for="status" class="col-sm-3 col-form-label">الحالة</label>
                            <div class="col-sm-9">
                                <select name="status" id="status" class="form-control">
                                    <option value="pending" <?= (!isset($_POST['status']) || $_POST['status'] === 'pending') ? 'selected' : '' ?>>قيد الانتظار</option>
                                    <option value="in_progress" <?= isset($_POST['status']) && $_POST['status'] === 'in_progress' ? 'selected' : '' ?>>قيد التنفيذ</option>
                                    <option value="completed" <?= isset($_POST['status']) && $_POST['status'] === 'completed' ? 'selected' : '' ?>>مكتملة</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- أزرار الإجراءات -->
                        <div class="form-group row">
                            <div class="col-sm-9 offset-sm-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> حفظ المهمة
                                </button>
                                
                                <?php if ($eventId > 0): ?>
                                    <a href="<?= BASE_URL ?>/views/events/view.php?id=<?= $eventId ?>#tasks" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> إلغاء
                                    </a>
                                <?php else: ?>
                                    <a href="<?= BASE_URL ?>/views/tasks/index.php" class="btn btn-secondary">
                                        <i class="fas fa-times"></i> إلغاء
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// تضمين تذييل الصفحة
include TEMPLATES_PATH . '/footer.php';
?>
