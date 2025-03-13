<?php
/**
 * دوال مساعدة للنظام
 * 
 * هذا الملف يحتوي على دوال عامة مساعدة تستخدم في مختلف أجزاء النظام
 */

// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    exit('لا يمكن الوصول المباشر لهذا الملف');
}

/**
 * إعادة توجيه المستخدم إلى صفحة محددة
 * 
 * @param string $url مسار الصفحة (نسبي أو مطلق)
 * @return void
 */
function redirect($url) {
    if (strpos($url, 'http://') !== 0 && strpos($url, 'https://') !== 0) {
        $url = BASE_URL . '/' . ltrim($url, '/');
    }
    
    header('Location: ' . $url);
    exit;
}

/**
 * التحقق من وجود متغير في الطلب (GET أو POST)
 * 
 * @param string $key اسم المتغير
 * @param string $method طريقة الطلب (GET/POST/REQUEST)
 * @return bool
 */
function has_request($key, $method = 'REQUEST') {
    switch (strtoupper($method)) {
        case 'GET':
            return isset($_GET[$key]);
        case 'POST':
            return isset($_POST[$key]);
        case 'REQUEST':
        default:
            return isset($_REQUEST[$key]);
    }
}

/**
 * الحصول على قيمة متغير من الطلب (GET أو POST)
 * مع تنظيف وحماية البيانات
 * 
 * @param string $key اسم المتغير
 * @param string $method طريقة الطلب (GET/POST/REQUEST)
 * @param mixed $default القيمة الافتراضية
 * @return mixed
 */
function get_request($key, $method = 'REQUEST', $default = null) {
    $value = $default;
    
    switch (strtoupper($method)) {
        case 'GET':
            if (isset($_GET[$key])) {
                $value = $_GET[$key];
            }
            break;
        case 'POST':
            if (isset($_POST[$key])) {
                $value = $_POST[$key];
            }
            break;
        case 'REQUEST':
        default:
            if (isset($_REQUEST[$key])) {
                $value = $_REQUEST[$key];
            }
            break;
    }
    
    // تطبيق التنظيف الأساسي
    if (is_string($value)) {
        $value = trim($value);
    }
    
    return $value;
}

/**
 * حماية النصوص لعرضها في HTML
 * 
 * @param string $text النص المراد حمايته
 * @return string
 */
function html_escape($text) {
    return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
}

/**
 * تقصير النص إلى طول محدد
 * 
 * @param string $text النص المراد تقصيره
 * @param int $length الطول المطلوب
 * @param string $append النص المضاف في النهاية
 * @return string
 */
function truncate_text($text, $length = 100, $append = '...') {
    if (mb_strlen($text, 'UTF-8') <= $length) {
        return $text;
    }
    
    $text = mb_substr($text, 0, $length, 'UTF-8');
    return rtrim($text) . $append;
}

/**
 * تنسيق التاريخ
 * 
 * @param string $date التاريخ بصيغة Y-m-d
 * @param string $format صيغة التاريخ المطلوبة
 * @return string
 */
function format_date($date, $format = null) {
    if (empty($date) || $date == '0000-00-00') {
        return '';
    }
    
    if ($format === null) {
        $format = DATE_FORMAT;
    }
    
    $dateObj = new DateTime($date);
    return $dateObj->format($format);
}

/**
 * تنسيق الوقت
 * 
 * @param string $time الوقت بصيغة H:i:s
 * @param string $format صيغة الوقت المطلوبة
 * @return string
 */
function format_time($time, $format = null) {
    if (empty($time)) {
        return '';
    }
    
    if ($format === null) {
        $format = TIME_FORMAT;
    }
    
    $timeObj = new DateTime($time);
    return $timeObj->format($format);
}

/**
 * تنسيق التاريخ والوقت
 * 
 * @param string $datetime التاريخ والوقت
 * @param string $format صيغة التاريخ والوقت المطلوبة
 * @return string
 */
function format_datetime($datetime, $format = null) {
    if (empty($datetime) || $datetime == '0000-00-00 00:00:00') {
        return '';
    }
    
    if ($format === null) {
        $format = DATETIME_FORMAT;
    }
    
    $datetimeObj = new DateTime($datetime);
    return $datetimeObj->format($format);
}

/**
 * تنسيق المبالغ المالية
 * 
 * @param float $amount المبلغ
 * @param bool $withCurrency إظهار العملة
 * @return string
 */
function format_currency($amount, $withCurrency = true) {
    global $config;
    
    // تقريب المبلغ إلى عدد الخانات العشرية المحدد
    $amount = number_format(
        $amount,
        $config['decimal_places'],
        $config['decimal_separator'],
        $config['thousand_separator']
    );
    
    // إضافة رمز العملة
    if ($withCurrency) {
        if ($config['currency_position'] === 'before') {
            return $config['currency'] . ' ' . $amount;
        } else {
            return $amount . ' ' . $config['currency'];
        }
    }
    
    return $amount;
}

/**
 * إنشاء توكن CSRF
 * 
 * @return string
 */
function generate_csrf_token() {
    $token = bin2hex(random_bytes(32));
    $_SESSION[SESSION_NAME][CSRF_TOKEN_NAME] = $token;
    return $token;
}

/**
 * التحقق من صحة توكن CSRF
 * 
 * @param string $token التوكن المقدم للتحقق
 * @return bool
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION[SESSION_NAME][CSRF_TOKEN_NAME])) {
        return false;
    }
    
    $stored_token = $_SESSION[SESSION_NAME][CSRF_TOKEN_NAME];
    
    // استخدام مقارنة آمنة لمنع هجمات التوقيت
    return hash_equals($stored_token, $token);
}

/**
 * إنشاء حقل توكن CSRF
 * 
 * @return string حقل HTML
 */
function csrf_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="' . CSRF_TOKEN_NAME . '" value="' . $token . '">';
}

/**
 * التحقق من صحة البريد الإلكتروني
 * 
 * @param string $email البريد الإلكتروني
 * @return bool
 */
function is_valid_email($email) {
    return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
}

/**
 * التحقق من صحة رقم الهاتف (أرقام ورموز فقط)
 * 
 * @param string $phone رقم الهاتف
 * @return bool
 */
function is_valid_phone($phone) {
    return preg_match('/^[0-9+\-\(\) ]+$/', $phone) === 1;
}

/**
 * توليد سلسلة عشوائية
 * 
 * @param int $length طول السلسلة
 * @return string
 */
function generate_random_string($length = 16) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
    $string = '';
    
    for ($i = 0; $i < $length; $i++) {
        $string .= $characters[random_int(0, strlen($characters) - 1)];
    }
    
    return $string;
}

/**
 * تحويل التاريخ من الصيغة العربية (d/m/Y) إلى الصيغة القياسية (Y-m-d)
 * 
 * @param string $date التاريخ بالصيغة العربية
 * @return string التاريخ بالصيغة القياسية
 */
function date_to_mysql($date) {
    if (empty($date)) {
        return null;
    }
    
    $parts = explode('/', $date);
    if (count($parts) !== 3) {
        return $date;
    }
    
    return $parts[2] . '-' . $parts[1] . '-' . $parts[0];
}

/**
 * عرض رسالة تأكيد أو خطأ
 * 
 * @param string $message نص الرسالة
 * @param string $type نوع الرسالة (success/error/warning/info)
 * @return string كود HTML للرسالة
 */
function show_message($message, $type = 'info') {
    $icon = '';
    
    switch ($type) {
        case 'success':
            $icon = '<i class="bi bi-check-circle-fill"></i>';
            break;
        case 'error':
            $icon = '<i class="bi bi-x-circle-fill"></i>';
            break;
        case 'warning':
            $icon = '<i class="bi bi-exclamation-triangle-fill"></i>';
            break;
        case 'info':
        default:
            $icon = '<i class="bi bi-info-circle-fill"></i>';
            break;
    }
    
    return '<div class="alert alert-' . $type . ' alert-dismissible fade show" role="alert">
                ' . $icon . ' ' . $message . '
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>
            </div>';
}

/**
 * التحقق من حالة الطلب إذا كان AJAX
 * 
 * @return bool
 */
function is_ajax_request() {
    return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');
}

/**
 * تخزين رسالة في الجلسة لعرضها في الصفحة التالية
 * 
 * @param string $message الرسالة
 * @param string $type نوع الرسالة (success/error/warning/info)
 * @return void
 */
function set_flash_message($message, $type = 'info') {
    $_SESSION[SESSION_NAME]['flash_messages'][] = [
        'message' => $message,
        'type' => $type
    ];
}

/**
 * عرض رسائل الفلاش المخزنة في الجلسة
 * 
 * @return string كود HTML للرسائل
 */
function display_flash_messages() {
    $output = '';
    
    if (isset($_SESSION[SESSION_NAME]['flash_messages']) && 
        !empty($_SESSION[SESSION_NAME]['flash_messages'])) {
        
        foreach ($_SESSION[SESSION_NAME]['flash_messages'] as $flash) {
            $output .= show_message($flash['message'], $flash['type']);
        }
        
        // مسح الرسائل بعد عرضها
        $_SESSION[SESSION_NAME]['flash_messages'] = [];
    }
    
    return $output;
}

/**
 * تحويل النص إلى slug مناسب للروابط
 * 
 * @param string $text النص
 * @return string
 */
function create_slug($text) {
    // إزالة الأحرف الخاصة
    $text = preg_replace('/[^\p{L}\p{N}\s]/u', '', $text);
    
    // تحويل المسافات إلى شرطات
    $text = preg_replace('/\s+/', '-', $text);
    
    // تحويل إلى أحرف صغيرة
    $text = mb_strtolower($text, 'UTF-8');
    
    // إزالة الشرطات المتكررة
    $text = preg_replace('/-+/', '-', $text);
    
    // قص الشرطات من البداية والنهاية
    return trim($text, '-');
}

/**
 * تحميل ملف عرض
 * 
 * @param string $view اسم ملف العرض
 * @param array $data البيانات المراد تمريرها للعرض
 * @param bool $return إرجاع المحتوى كنص بدلاً من طباعته
 * @return string|void
 */
function load_view($view, $data = [], $return = false) {
    $view_path = VIEWS_PATH . '/' . $view . '.php';
    
    if (!file_exists($view_path)) {
        die('خطأ: ملف العرض غير موجود: ' . $view);
    }
    
    // استخراج المتغيرات من المصفوفة
    extract($data);
    
    // بدء التخزين المؤقت
    ob_start();
    
    // تضمين ملف العرض
    include $view_path;
    
    // الحصول على المحتوى
    $output = ob_get_clean();
    
    if ($return) {
        return $output;
    }
    
    echo $output;
}

/**
 * إنشاء أزرار التنقل بين الصفحات
 * 
 * @param int $total إجمالي عدد العناصر
 * @param int $limit عدد العناصر في كل صفحة
 * @param int $current_page الصفحة الحالية
 * @param string $url نمط رابط URL (يجب أن يحتوي على %d)
 * @return string كود HTML لأزرار التنقل
 */
function pagination($total, $limit, $current_page, $url) {
    if ($total <= $limit) {
        return '';
    }
    
    $total_pages = ceil($total / $limit);
    
    $output = '<nav aria-label="تنقل الصفحات">
                <ul class="pagination justify-content-center">';
    
    // زر الصفحة السابقة
    if ($current_page > 1) {
        $output .= '<li class="page-item">
                    <a class="page-link" href="' . sprintf($url, $current_page - 1) . '" aria-label="السابق">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                  </li>';
    } else {
        $output .= '<li class="page-item disabled">
                    <a class="page-link" href="#" aria-label="السابق">
                        <span aria-hidden="true">&laquo;</span>
                    </a>
                  </li>';
    }
    
    // حساب نطاق الصفحات للعرض
    $range = 2;
    $start_page = max(1, $current_page - $range);
    $end_page = min($total_pages, $current_page + $range);
    
    // إضافة الصفحة الأولى إذا لم تكن مشمولة في النطاق
    if ($start_page > 1) {
        $output .= '<li class="page-item"><a class="page-link" href="' . sprintf($url, 1) . '">1</a></li>';
        
        if ($start_page > 2) {
            $output .= '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
        }
    }
    
    // إضافة صفحات النطاق
    for ($i = $start_page; $i <= $end_page; $i++) {
        if ($i == $current_page) {
            $output .= '<li class="page-item active"><a class="page-link" href="#">' . $i . '</a></li>';
        } else {
            $output .= '<li class="page-item"><a class="page-link" href="' . sprintf($url, $i) . '">' . $i . '</a></li>';
        }
    }
    
    // إضافة الصفحة الأخيرة إذا لم تكن مشمولة في النطاق
    if ($end_page < $total_pages) {
        if ($end_page < $total_pages - 1) {
            $output .= '<li class="page-item disabled"><a class="page-link" href="#">...</a></li>';
        }
        
        $output .= '<li class="page-item"><a class="page-link" href="' . sprintf($url, $total_pages) . '">' . $total_pages . '</a></li>';
    }
    
    // زر الصفحة التالية
    if ($current_page < $total_pages) {
        $output .= '<li class="page-item">
                    <a class="page-link" href="' . sprintf($url, $current_page + 1) . '" aria-label="التالي">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                  </li>';
    } else {
        $output .= '<li class="page-item disabled">
                    <a class="page-link" href="#" aria-label="التالي">
                        <span aria-hidden="true">&raquo;</span>
                    </a>
                  </li>';
    }
    
    $output .= '</ul></nav>';
    
    return $output;
}

/**
 * حساب الفرق بين تاريخين بأي وحدة زمنية
 * 
 * @param string $date1 التاريخ الأول
 * @param string $date2 التاريخ الثاني (اختياري، يستخدم التاريخ الحالي إذا لم يتم توفيره)
 * @param string $unit الوحدة المطلوبة (days/months/years/hours/minutes/seconds)
 * @return int
 */
function date_diff_in($date1, $date2 = null, $unit = 'days') {
    $date1Obj = new DateTime($date1);
    
    if ($date2 === null) {
        $date2Obj = new DateTime();
    } else {
        $date2Obj = new DateTime($date2);
    }
    
    $interval = $date1Obj->diff($date2Obj);
    
    switch ($unit) {
        case 'years':
            return $interval->y;
        case 'months':
            return $interval->y * 12 + $interval->m;
        case 'days':
            return $interval->days;
        case 'hours':
            return $interval->days * 24 + $interval->h;
        case 'minutes':
            return ($interval->days * 24 + $interval->h) * 60 + $interval->i;
        case 'seconds':
            return (($interval->days * 24 + $interval->h) * 60 + $interval->i) * 60 + $interval->s;
        default:
            return $interval->days;
    }
}

/**
 * تنظيف المدخلات النصية من الرموز غير المرغوبة
 * 
 * @param string $input النص المدخل
 * @param bool $preserve_html السماح بأكواد HTML أم لا
 * @return string
 */
function clean_input($input, $preserve_html = false) {
    if (!is_string($input)) {
        return $input;
    }
    
    // إزالة المسافات الزائدة
    $input = trim($input);
    
    if ($preserve_html) {
        // تنظيف مع الحفاظ على HTML
        $input = htmlentities($input, ENT_QUOTES, 'UTF-8');
        $input = str_replace('&amp;', '&', $input);
    } else {
        // تنظيف مع إزالة HTML
        $input = strip_tags($input);
        $input = htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    return $input;
}
