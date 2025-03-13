/**
 * الملف الرئيسي للدوال والإعدادات الخاصة بجافاسكريبت
 * نظام ميز للضيافة
 */

// عند تحميل الصفحة بالكامل
$(document).ready(function() {
    // تهيئة البوبوفر والتولتيب
    initTooltipsAndPopovers();
    
    // تهيئة النماذج المتقدمة
    initForms();
    
    // تهيئة الجداول
    initTables();
    
    // إعدادات عامة
    setupGeneralBehavior();
    
    // تهيئة المعالجات للأزرار والإجراءات
    setupEventHandlers();
});

/**
 * تهيئة التلميحات والبوبوفر
 */
function initTooltipsAndPopovers() {
    // تهيئة التلميحات
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    tooltipTriggerList.map(function (tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // تهيئة البوبوفر
    var popoverTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="popover"]'));
    popoverTriggerList.map(function (popoverTriggerEl) {
        return new bootstrap.Popover(popoverTriggerEl);
    });
}

/**
 * تهيئة النماذج المتقدمة
 */
function initForms() {
    // التحقق من النماذج
    $('.needs-validation').on('submit', function(event) {
        if (!this.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
        }
        $(this).addClass('was-validated');
    });
    
    // تهيئة منتقي التاريخ
    if ($.fn.datepicker) {
        $('.datepicker').datepicker({
            format: 'dd/mm/yyyy',
            autoclose: true,
            language: 'ar',
            rtl: true,
            todayHighlight: true,
            orientation: "bottom"
        });
    }
    
    // تهيئة منتقي الوقت
    if ($.fn.timepicker) {
        $('.timepicker').timepicker({
            showMeridian: true,
            minuteStep: 5
        });
    }
    
    // تهيئة القوائم المنسدلة المتقدمة
    if ($.fn.select2) {
        $('.select2').select2({
            dir: "rtl",
            width: '100%',
            language: "ar"
        });
    }
    
    // تنسيق حقول الأرقام والعملة
    if ($.fn.inputmask) {
        $('.currency-input').inputmask('decimal', {
            rightAlign: false,
            radixPoint: '.',
            groupSeparator: ',',
            autoGroup: true,
            suffix: ' ر.س'
        });
        
        $('.phone-input').inputmask('999-999-9999');
    }
}

/**
 * تهيئة الجداول
 */
function initTables() {
    // تهيئة الجداول القابلة للفرز والتصفية
    if ($.fn.DataTable) {
        $('.datatable').DataTable({
            "language": {
                "url": "//cdn.datatables.net/plug-ins/1.10.24/i18n/ar.json"
            },
            "dom": "<'row'<'col-sm-6'l><'col-sm-6'f>>" +
                   "<'row'<'col-sm-12'tr>>" +
                   "<'row'<'col-sm-5'i><'col-sm-7'p>>",
            "responsive": true,
            "order": []
        });
    }
}

/**
 * إعدادات عامة للسلوك
 */
function setupGeneralBehavior() {
    // تحديث الساعة الحية
    if ($('#live-clock').length) {
        setInterval(updateClock, 1000);
    }
    
    // تفعيل القائمة الجانبية المتجاوبة
    $('.sidebar-toggle').on('click', function() {
        $('.sidebar').toggleClass('collapsed');
        $('.main-content').toggleClass('expanded');
    });
    
    // تبديل وضع الإظلام
    $('#dark-mode-toggle').on('click', function() {
        $('body').toggleClass('dark-mode');
        let isDarkMode = $('body').hasClass('dark-mode');
        localStorage.setItem('darkMode', isDarkMode);
    });
    
    // تحميل وضع الإظلام المحفوظ
    if (localStorage.getItem('darkMode') === 'true') {
        $('body').addClass('dark-mode');
    }
}

/**
 * تحديث الساعة الحية
 */
function updateClock() {
    var now = new Date();
    var hours = now.getHours();
    var minutes = now.getMinutes();
    var seconds = now.getSeconds();
    var ampm = hours >= 12 ? 'م' : 'ص';
    
    hours = hours % 12;
    hours = hours ? hours : 12;
    minutes = minutes < 10 ? '0' + minutes : minutes;
    seconds = seconds < 10 ? '0' + seconds : seconds;
    
    var timeString = hours + ':' + minutes + ':' + seconds + ' ' + ampm;
    $('#live-clock').text(timeString);
}

/**
 * إعداد معالجات الأحداث
 */
function setupEventHandlers() {
    // التأكيد قبل الحذف
    $(document).on('click', '.delete-btn', function(e) {
        e.preventDefault();
        var deleteUrl = $(this).attr('href');
        
        if (confirm('هل أنت متأكد من رغبتك في حذف هذا العنصر؟ هذا الإجراء لا يمكن التراجع عنه.')) {
            window.location.href = deleteUrl;
        }
    });
    
    // عرض التفاصيل في نافذة منبثقة
    $(document).on('click', '.view-details', function(e) {
        e.preventDefault();
        var detailsUrl = $(this).data('url');
        
        // طلب البيانات بـ AJAX
        $.ajax({
            url: detailsUrl,
            type: 'GET',
            dataType: 'html',
            success: function(response) {
                // عرض البيانات في النافذة المنبثقة
                $('#detailsModal .modal-body').html(response);
                var modal = new bootstrap.Modal(document.getElementById('detailsModal'));
                modal.show();
            },
            error: function() {
                alert('حدث خطأ أثناء تحميل البيانات. يرجى المحاولة مرة أخرى.');
            }
        });
    });
    
    // تحديث حالة الفعالية
    $(document).on('change', '.event-status-select', function() {
        var eventId = $(this).data('event-id');
        var newStatus = $(this).val();
        
        $.ajax({
            url: 'events/update-status',
            type: 'POST',
            data: {
                event_id: eventId,
                status: newStatus,
                csrf_token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            success: function(response) {
                if (response.success) {
                    showNotification('success', 'تم تحديث حالة الفعالية بنجاح');
                } else {
                    showNotification('error', response.message || 'حدث خطأ أثناء تحديث الحالة');
                }
            },
            error: function() {
                showNotification('error', 'حدث خطأ في الاتصال بالخادم');
            }
        });
    });
}

/**
 * عرض إشعار للمستخدم
 * 
 * @param {string} type نوع الإشعار (success, error, warning, info)
 * @param {string} message نص الرسالة
 */
function showNotification(type, message) {
    var alertClass = 'alert-info';
    
    switch (type) {
        case 'success':
            alertClass = 'alert-success';
            break;
        case 'error':
            alertClass = 'alert-danger';
            break;
        case 'warning':
            alertClass = 'alert-warning';
            break;
    }
    
    var alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                   message +
                   '<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="إغلاق"></button>' +
                   '</div>';
    
    // إضافة الإشعار للصفحة
    var $notificationArea = $('#notification-area');
    if ($notificationArea.length === 0) {
        $('body').append('<div id="notification-area" style="position: fixed; top: 20px; left: 20px; right: 20px; z-index: 9999;"></div>');
        $notificationArea = $('#notification-area');
    }
    
    // إضافة الإشعار وإخفاؤه تلقائياً بعد 5 ثواني
    var $alert = $(alertHtml).appendTo($notificationArea);
    setTimeout(function() {
        $alert.alert('close');
    }, 5000);
}

/**
 * تحميل بيانات مرتبطة بعنصر آخر (مثلاً تحميل الفعاليات للعميل)
 * 
 * @param {string} sourceSelector محدد عنصر المصدر
 * @param {string} targetSelector محدد عنصر الهدف
 * @param {string} url رابط الطلب
 * @param {string} paramName اسم المعلمة
 * @param {function} callback دالة يتم تنفيذها بعد التحميل
 */
function loadRelatedData(sourceSelector, targetSelector, url, paramName, callback) {
    $(sourceSelector).on('change', function() {
        var selectedId = $(this).val();
        var $target = $(targetSelector);
        
        if (!selectedId) {
            // إفراغ القائمة المستهدفة واستدعاء الدالة التابعة إن وجدت
            $target.empty().append('<option value="">-- اختر --</option>');
            if (typeof callback === 'function') callback(null);
            return;
        }
        
        // بناء البيانات
        var data = {};
        data[paramName] = selectedId;
        
        // إضافة توكن CSRF
        data.csrf_token = $('meta[name="csrf-token"]').attr('content');
        
        // طلب البيانات بـ AJAX
        $.ajax({
            url: url,
            type: 'POST',
            data: data,
            dataType: 'json',
            beforeSend: function() {
                $target.attr('disabled', 'disabled');
            },
            success: function(response) {
                $target.empty().append('<option value="">-- اختر --</option>');
                
                if (response.success && response.data) {
                    $.each(response.data, function(index, item) {
                        $target.append('<option value="' + item.id + '">' + item.name + '</option>');
                    });
                }
                
                $target.removeAttr('disabled');
                
                if (typeof callback === 'function') callback(response);
            },
            error: function() {
                $target.empty().append('<option value="">-- حدث خطأ --</option>');
                $target.removeAttr('disabled');
                
                if (typeof callback === 'function') callback(null);
            }
        });
    });
}

/**
 * حساب إجمالي التكلفة
 * 
 * @param {Array} inputSelectors مصفوفة بمحددات حقول الإدخال
 * @param {string} totalSelector محدد عنصر المجموع
 */
function calculateTotal(inputSelectors, totalSelector) {
    // دالة لحساب المجموع
    function updateTotal() {
        var total = 0;
        
        // جمع القيم من جميع الحقول
        inputSelectors.forEach(function(selector) {
            var value = $(selector).val();
            // إزالة أي رموز غير رقمية (مثل رمز العملة والفواصل)
            value = value.replace(/[^\d.-]/g, '');
            // تحويل إلى رقم
            value = parseFloat(value) || 0;
            // جمع القيمة
            total += value;
        });
        
        // تنسيق الرقم وعرضه
        var formattedTotal = new Intl.NumberFormat('ar-SA', {
            style: 'currency',
            currency: 'SAR'
        }).format(total);
        
        // عرض المجموع
        $(totalSelector).text(formattedTotal);
    }
    
    // تسجيل حدث التغيير لجميع الحقول
    inputSelectors.forEach(function(selector) {
        $(document).on('change keyup', selector, updateTotal);
    });
    
    // حساب المجموع الأولي
    updateTotal();
}
