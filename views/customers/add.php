<?php
/**
 * صفحة إضافة عميل جديد
 * تسمح بإضافة عميل جديد إلى النظام
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

// إنشاء كائن العميل
$customerObj = new Customer();

// تهيئة المتغيرات
$pageTitle = 'إضافة عميل جديد';
$errors = [];
$success = false;

// الحصول على مصادر العملاء
$customerSources = $customerObj->getCustomerSources();

// الحصول على العملاء القدامى (للتوصيات)
$existingCustomers = $customerObj->getCustomersForDropdown();

// معالجة النموذج عند الإرسال
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_customer'])) {
    // التحقق من البيانات المطلوبة
    if (empty($_POST['name'])) {
        $errors[] = 'يرجى إدخال اسم العميل.';
    }
    
    if (empty($_POST['phone'])) {
        $errors[] = 'يرجى إدخال رقم الهاتف.';
    } elseif ($customerObj->phoneExists($_POST['phone'])) {
        $errors[] = 'رقم الهاتف موجود بالفعل. يرجى استخدام رقم مختلف.';
    }
    
    // التحقق من البريد الإلكتروني
    if (!empty($_POST['email']) && !filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'يرجى إدخال بريد إلكتروني صحيح.';
    }
    
    // إذا كان المصدر توصية، تأكد من تحديد العميل المُوصي
    if (!empty($_POST['source_id']) && $_POST['source_id'] == '2' && empty($_POST['referral_customer_id'])) {
        $errors[] = 'يرجى تحديد العميل المُوصي.';
    }
    
    // إذا لم تكن هناك أخطاء، قم بإضافة العميل
    if (empty($errors)) {
        // تحضير بيانات العميل
        $customerData = [
            'name' => $_POST['name'],
            'phone' => $_POST['phone'],
            'alt_phone' => isset($_POST['alt_phone']) ? $_POST['alt_phone'] : null,
            'email' => isset($_POST['email']) ? $_POST['email'] : null,
            'address' => isset($_POST['address']) ? $_POST['address'] : null,
            'source_id' => isset($_POST['source_id']) ? $_POST['source_id'] : null,
            'category' => isset($_POST['category']) ? $_POST['category'] : 'new',
            'notes' => isset($_POST['notes']) ? $_POST['notes'] : null
        ];
        
        // إضافة معرف العميل المُوصي إذا كان متوفراً
        if (!empty($_POST['referral_customer_id'])) {
            $customerData['referral_customer_id'] = $_POST['referral_customer_id'];
        }
        
        // إضافة العميل
        $newCustomerId = $customerObj->create($customerData);
        
        if ($newCustomerId) {
            $success = true;
        } else {
            $errors[] = 'حدث خطأ أثناء إضافة العميل. يرجى المحاولة مرة أخرى.';
        }
    }
}

// تضمين قالب الهيدر
include '../../../templates/header.php';
?>

<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h1 class="h3 mb-0 text-gray-800"><?php echo $pageTitle; ?></h1>
        <a href="index.php" class="btn btn-secondary">
            <i class="fas fa-arrow-right ml-1"></i> العودة إلى قائمة العملاء
        </a>
    </div>

    <?php if ($success): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        تم إضافة العميل بنجاح!
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
        <hr>
        <div class="d-flex justify-content-between">
            <a href="index.php" class="btn btn-outline-success btn-sm">العودة إلى قائمة العملاء</a>
            <a href="add.php" class="btn btn-success btn-sm">إضافة عميل آخر</a>
        </div>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <h6 class="alert-heading">يوجد أخطاء في النموذج:</h6>
        <ul class="mb-0">
            <?php foreach ($errors as $error): ?>
            <li><?php echo $error; ?></li>
            <?php endforeach; ?>
        </ul>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
    </div>
    <?php endif; ?>

    <?php if (!$success): ?>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold">معلومات العميل</h6>
        </div>
        <div class="card-body">
            <form method="post" action="" class="row g-3" id="addCustomerForm">
                <!-- البيانات الأساسية -->
                <div class="col-md-6">
                    <label for="name" class="form-label">اسم العميل <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="name" name="name" required 
                           value="<?php echo isset($_POST['name']) ? htmlentities($_POST['name']) : ''; ?>">
                </div>
                
                <div class="col-md-6">
                    <label for="category" class="form-label">تصنيف العميل</label>
                    <select class="form-select" id="category" name="category">
                        <option value="new" <?php echo (isset($_POST['category']) && $_POST['category'] === 'new') || !isset($_POST['category']) ? 'selected' : ''; ?>>جديد</option>
                        <option value="regular" <?php echo isset($_POST['category']) && $_POST['category'] === 'regular' ? 'selected' : ''; ?>>منتظم</option>
                        <option value="vip" <?php echo isset($_POST['category']) && $_POST['category'] === 'vip' ? 'selected' : ''; ?>>VIP</option>
                    </select>
                </div>
                
                <div class="col-md-6">
                    <label for="phone" class="form-label">رقم الهاتف <span class="text-danger">*</span></label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-phone"></i></span>
                        <input type="text" class="form-control" id="phone" name="phone" required dir="ltr"
                               value="<?php echo isset($_POST['phone']) ? htmlentities($_POST['phone']) : ''; ?>">
                    </div>
                </div>
                
                <!-- معلومات المصدر -->
                <div class="col-md-6">
                    <label for="source_id" class="form-label">مصدر العميل</label>
                    <select class="form-select" id="source_id" name="source_id">
                        <option value="">-- اختر المصدر --</option>
                        <?php foreach ($customerSources as $source): ?>
                        <option value="<?php echo $source['id']; ?>" 
                                <?php echo isset($_POST['source_id']) && $_POST['source_id'] == $source['id'] ? 'selected' : ''; ?> 
                                data-is-referral="<?php echo $source['name'] === 'توصية' ? '1' : '0'; ?>">
                            <?php echo htmlentities($source['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="col-md-6" id="referralCustomerContainer" style="display: none;">
                    <label for="referral_customer_id" class="form-label">العميل المُوصي</label>
                    <select class="form-select" id="referral_customer_id" name="referral_customer_id">
                        <option value="">-- اختر العميل المُوصي --</option>
                        <?php foreach ($existingCustomers as $customer): ?>
                        <option value="<?php echo $customer['id']; ?>" 
                                <?php echo isset($_POST['referral_customer_id']) && $_POST['referral_customer_id'] == $customer['id'] ? 'selected' : ''; ?>>
                            <?php echo htmlentities($customer['name']); ?>
                        </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <!-- ملاحظات -->
                <div class="col-12">
                    <label for="notes" class="form-label">ملاحظات</label>
                    <textarea class="form-control" id="notes" name="notes" rows="3"><?php echo isset($_POST['notes']) ? htmlentities($_POST['notes']) : ''; ?></textarea>
                </div>
                
                <div class="col-12 mt-4">
                    <hr>
                    <button type="submit" name="add_customer" class="btn btn-primary">
                        <i class="fas fa-plus ml-1"></i> إضافة العميل
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-times ml-1"></i> إلغاء
                    </a>
                </div>
            </form>
        </div>
    </div>
    <?php endif; ?>
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
    
    // التعامل مع تغيير مصدر العميل
    const sourceSelect = document.getElementById('source_id');
    const referralContainer = document.getElementById('referralCustomerContainer');
    const referralSelect = document.getElementById('referral_customer_id');
    
    // دالة للتحقق من عرض أو إخفاء حقل العميل المُوصي
    function checkReferralVisibility() {
        const selectedOption = sourceSelect.options[sourceSelect.selectedIndex];
        if (selectedOption && selectedOption.getAttribute('data-is-referral') === '1') {
            referralContainer.style.display = 'block';
            referralSelect.setAttribute('required', 'required');
        } else {
            referralContainer.style.display = 'none';
            referralSelect.removeAttribute('required');
        }
    }
    
    // تنفيذ الدالة عند تحميل الصفحة وعند تغيير المصدر
    checkReferralVisibility();
    sourceSelect.addEventListener('change', checkReferralVisibility);
});
</script>
                </div>
                
                <div class="col-md-6">
                    <label for="alt_phone" class="form-label">رقم هاتف بديل</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-phone-alt"></i></span>
                        <input type="text" class="form-control" id="alt_phone" name="alt_phone" dir="ltr"
                               value="<?php echo isset($_POST['alt_phone']) ? htmlentities($_POST['alt_phone']) : ''; ?>">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="email" class="form-label">البريد الإلكتروني</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                        <input type="email" class="form-control" id="email" name="email" dir="ltr"
                               value="<?php echo isset($_POST['email']) ? htmlentities($_POST['email']) : ''; ?>">
                    </div>
                </div>
                
                <div class="col-md-6">
                    <label for="address" class="form-label">العنوان</label>
                    <div class="input-group">
                        <span class="input-group-text"><i class="fas fa-map-marker-alt"></i></span>
                        <input type="text" class="form-control" id="address" name="address"
                               value="<?php echo isset($_POST['address']) ? htmlentities($_POST['address']) : ''; ?>">
                    </div>
