<?php
/**
 * صفحة تعديل المهمة
 * تتيح للمستخدم تعديل تفاصيل المهمة وتحديث حالتها
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
$userObj = new User();
$eventObj = new Event();

// الحصول على المستخدم الحالي
$currentUser = User::getCurrentUser();

// التحقق من توفير معرف المهمة
if (!isset($_GET['id']) || empty($_GET['id'])) {
    set_flash_message('error', 'معرف المهمة غير صالح');
    redirect('tasks/index.php');
}

// تحميل معلومات المهمة
$taskId = (int)$_GET['id'];
if (!$taskObj->loadTaskById($taskId)) {
    set_flash_message('error', 'المهمة غير موجودة');
    redirect('tasks/index.php');
}

// تحديد ما إذا كان المستخدم مديراً (يمكنه تعديل جميع المهام)
$isManager = $currentUser->isManager();

// التحقق من صلاحية المستخدم لتعديل المهمة (المدراء أو صاحب المهمة أو المعين إليه)
if (!$isManager && $taskObj->getAssignedTo() != $currentUser->getId() && $taskObj->getCreatedBy() != $currentUser->getId()) {
    set_flash_message('error', 'ليس لديك صلاحية لتعديل هذه المهمة');
    redirect('tasks/index.php');
}

// التحقق من طلب إكمال المهمة
$completeTask = isset($_GET['action']) && $_GET['action'] === 'complete';
if ($completeTask) {
    // إكمال المهمة
    $result = $taskObj->update($taskId, [
        'status' => 'completed',
        'completed_at' => date('Y-m-d H:i:s'),
        'completed_by' => $currentUser->getId()
    ]);
    
    if ($result) {
        set_flash_message('success', 'تم إكمال المهمة بنجاح');
    } else {
        set_flash_message('error', 'حدث خطأ أثناء إكمال المهمة');
    }
    
    // التوجيه إلى قائمة المهام
    $eventId = $taskObj->getEventId();
    if ($eventId) {
        redirect('events/view.php?id=' . $eventId . '#tasks');
    } else {
        redirect('tasks/index.php');
    }
}

// تحميل بيانات الفعالية المرتبطة بالمهمة
$event = new Event();
$event->loadEventById($taskObj->getEventId());

// معالجة إرسال النموذج
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من توكن CSRF
    if (!isset($_POST['csrf_token']) || $_POST['csrf_token'] !== $_SESSION[SESSION_NAME]['csrf_token']) {
        set_flash_message('error', 'خطأ في التحقق من الأمان. يرجى المحاولة مرة أخرى.');
        redirect('tasks/edit.php?id=' . $taskId);
    }
    
    // التحقق من إدخال العنوان (حقل إلزامي)
    if (empty($_POST['title'])) {
        set_flash_message('error', 'يرجى إدخال عنوان المهمة');
    } else {
        // تحضير بيانات المهمة للتحديث
        $taskData = [
            'title' => trim($_POST['title'])
        ];
        
        // إضافة وصف المهمة إذا تم توفيره
        if (isset($_POST['description'])) {
            $taskData['description'] = trim($_POST['description']);
        }
        
        // إضافة المستخدم المعين إذا تم توفيره وكان المستخدم الحالي مديراً
        if (!empty($_POST['assigned_to']) && $isManager) {
            $taskData['assigned_to'] = (int)$_POST['assigned_to'];
        }
        
        // إضافة تاريخ الاستحقاق إذا تم توفيره
        if (isset($_POST['due_date'])) {
            $taskData['due_date'] = $_POST['due_date'] ? $_POST['due_date'] : null;
        }
        
        // إضافة الأولوية إذا تم توفيرها
        if (!empty($_POST['priority'])) {
            $taskData['priority'] = $_POST['priority'];
        }
        
        // إضافة الحالة إذا تم توفيرها
        if (!empty($_POST['status'])) {
            $taskData['status'] = $_POST['status'];
            
            // إذا تم تحديث الحالة إلى "مكتملة"، قم بتسجيل وقت الإكمال والمستخدم الذي قام بالإكمال
            if ($_POST['status'] === 'completed' && $taskObj->getStatus() !== 'completed') {
                $taskData['completed_at'] = date('Y-m-d H:i:s');
                $taskData['completed_by'] = $currentUser->getId();
            }
        }
        
        // محاولة تحديث المهمة
        $result = $taskObj->update($taskId, $taskData);
        
        if ($result) {
            // تم تحديث المهمة بنجاح
            set_flash_message('success', 'تم تحديث المهمة بنجاح');
            
            // إعادة تحميل المهمة للحصول على البيانات المحدثة
            $taskObj->loadTaskById($taskId);
            
            // التوجيه إلى صفحة المهام أو صفحة الفعالية
            if (isset($_POST['redirect_to']) && $_POST['redirect_to'] === 'event') {
                redirect('events/view.php?id=' . $taskObj->getEventId() . '#tasks');
            } else {
                redirect('tasks/index.php');
            }
        } else {
            set_flash_message('error', 'حدث خطأ أثناء تحديث المهمة. يرجى المحاولة مرة أخرى.');
        }
    }
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
                    <h6 class="m-0 font-weight-bold text-primary">تعديل المهمة</h6>
                </div>
                <div class="card-body">
                    <!-- عرض رسائل النجاح أو الخطأ -->
                    <?php display_flash_messages(); ?>
                    
                    <form method="post" action="<?= BASE_URL ?>/views/tasks/edit.php?id=<?= $taskId ?>">
                        <!-- توكن CSRF -->
                        <input type="hidden" name="csrf_token" value="<?= $_SESSION[SESSION_NAME]['csrf_token'] ?>">
                        
                        <!-- معلومات الفعالية -->
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">الفعالية</label>
                            <div class="col-sm-9">
                                <p class="form-control-static">
                                    <a href="<?= BASE_URL ?>/views/events/view.php?id=<?= $taskObj->getEventId() ?>">
                                        <?= htmlspecialchars($event->getTitle()) ?>
                                    </a>
                                    (<?= date(DATE_FORMAT, strtotime($event->getDate())) ?>)
                                </p>
                            </div>
                        </div>
                        
                        <!-- عنوان المهمة -->
                        <div class="form-group row">
                            <label for="title" class="col-sm-3 col-form-label">عنوان المهمة <span class="text-danger">*</span></label>
                            <div class="col-sm-9">
                                <input type="text" class="form-control" id="title" name="title" required 
                                       value="<?= htmlspecialchars($taskObj->getTitle()) ?>">
                            </div>
                        </div>
                        
                        <!-- وصف المهمة -->
                        <div class="form-group row">
                            <label for="description" class="col-sm-3 col-form-label">وصف المهمة</label>
                            <div class="col-sm-9">
                                <textarea class="form-control" id="description" name="description" rows="4"><?= htmlspecialchars($taskObj->getDescription()) ?></textarea>
                                <small class="form-text text-muted">وصف تفصيلي للمهمة وما هو المطلوب إنجازه</small>
                            </div>
                        </div>
                        
                        <!-- المستخدم المعين (للمدراء فقط) -->
                        <?php if ($isManager): ?>
                        <div class="form-group row">
                            <label for="assigned_to" class="col-sm-3 col-form-label">تعيين إلى</label>
                            <div class="col-sm-9">
                                <select name="assigned_to" id="assigned_to" class="form-control">
                                    <option value="">-- غير معين --</option>
                                    <?php foreach ($users as $user): ?>
                                        <option value="<?= $user['id'] ?>" <?= $taskObj->getAssignedTo() == $user['id'] ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($user['name']) ?>
                                        </option>
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
                                       value="<?= $taskObj->getDueDate() ? date('Y-m-d', strtotime($taskObj->getDueDate())) : '' ?>">
                                <small class="form-text text-muted">التاريخ المطلوب إنجاز المهمة فيه</small>
                            </div>
                        </div>
                        
                        <!-- أولوية المهمة -->
                        <div class="form-group row">
                            <label for="priority" class="col-sm-3 col-form-label">الأولوية</label>
                            <div class="col-sm-9">
                                <select name="priority" id="priority" class="form-control">
                                    <option value="high" <?= $taskObj->getPriority() === 'high' ? 'selected' : '' ?>>عالية</option>
                                    <option value="medium" <?= $taskObj->getPriority() === 'medium' ? 'selected' : '' ?>>متوسطة</option>
                                    <option value="low" <?= $taskObj->getPriority() === 'low' ? 'selected' : '' ?>>منخفضة</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- حالة المهمة -->
                        <div class="form-group row">
                            <label for="status" class="col-sm-3 col-form-label">الحالة</label>
                            <div class="col-sm-9">
                                <select name="status" id="status" class="form-control">
                                    <option value="pending" <?= $taskObj->getStatus() === 'pending' ? 'selected' : '' ?>>قيد الانتظار</option>
                                    <option value="in_progress" <?= $taskObj->getStatus() === 'in_progress' ? 'selected' : '' ?>>قيد التنفيذ</option>
                                    <option value="completed" <?= $taskObj->getStatus() === 'completed' ? 'selected' : '' ?>>مكتملة</option>
                                    <option value="cancelled" <?= $taskObj->getStatus() === 'cancelled' ? 'selected' : '' ?>>ملغاة</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- معلومات إضافية -->
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">تاريخ الإنشاء</label>
                            <div class="col-sm-9">
                                <p class="form-control-static">
                                    <?= date(DATETIME_FORMAT, strtotime($taskObj->getCreatedAt())) ?>
                                    <?php if ($taskObj->getCreatedByName()): ?>
                                        بواسطة <?= htmlspecialchars($taskObj->getCreatedByName()) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        
                        <?php if ($taskObj->getStatus() === 'completed' && $taskObj->getCompletedAt()): ?>
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">تاريخ الإكمال</label>
                            <div class="col-sm-9">
                                <p class="form-control-static">
                                    <?= date(DATETIME_FORMAT, strtotime($taskObj->getCompletedAt())) ?>
                                    <?php if ($taskObj->getCompletedByName()): ?>
                                        بواسطة <?= htmlspecialchars($taskObj->getCompletedByName()) ?>
                                    <?php endif; ?>
                                </p>
                            </div>
                        </div>
                        <?php endif; ?>
                        
                        <!-- المكان الذي سيتم التوجيه إليه بعد الحفظ -->
                        <div class="form-group row">
                            <label class="col-sm-3 col-form-label">بعد الحفظ</label>
                            <div class="col-sm-9">
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="redirect_tasks" name="redirect_to" value="tasks" class="custom-control-input" checked>
                                    <label class="custom-control-label" for="redirect_tasks">العودة إلى قائمة المهام</label>
                                </div>
                                <div class="custom-control custom-radio custom-control-inline">
                                    <input type="radio" id="redirect_event" name="redirect_to" value="event" class="custom-control-input">
                                    <label class="custom-control-label" for="redirect_event">العودة إلى صفحة الفعالية</label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- أزرار الإجراءات -->
                        <div class="form-group row">
                            <div class="col-sm-9 offset-sm-3">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-save"></i> حفظ التغييرات
                                </button>
                                
                                <?php if ($taskObj->getStatus() !== 'completed' && $taskObj->getStatus() !== 'cancelled'): ?>
                                    <a href="<?= BASE_URL ?>/views/tasks/edit.php?id=<?= $taskId ?>&action=complete" class="btn btn-success ml-2">
                                        <i class="fas fa-check"></i> إكمال المهمة
                                    </a>
                                <?php endif; ?>
                                
                                <a href="<?= BASE_URL ?>/views/tasks/index.php" class="btn btn-secondary ml-2">
                                    <i class="fas fa-times"></i> إلغاء
                                </a>
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
