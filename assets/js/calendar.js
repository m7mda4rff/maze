/**
 * سكريبت التقويم الخاص بنظام ميز للضيافة
 * يستخدم مكتبة FullCalendar لعرض وإدارة الفعاليات
 */

// عند تحميل الصفحة بالكامل
document.addEventListener('DOMContentLoaded', function() {
    initCalendar();
});

/**
 * تهيئة التقويم
 */
function initCalendar() {
    var calendarEl = document.getElementById('events-calendar');
    
    // التحقق من وجود عنصر التقويم
    if (!calendarEl) return;
    
    // تهيئة كائن التقويم
    var calendar = new FullCalendar.Calendar(calendarEl, {
        // الإعدادات العامة
        initialView: 'dayGridMonth',
        direction: 'rtl',
        locale: 'ar',
        headerToolbar: {
            start: 'prev,next today',
            center: 'title',
            end: 'dayGridMonth,timeGridWeek,timeGridDay,listWeek'
        },
        buttonText: {
            today: 'اليوم',
            month: 'شهر',
            week: 'أسبوع',
            day: 'يوم',
            list: 'قائمة'
        },
        
        // إعدادات الأحداث
        eventTimeFormat: {
            hour: 'numeric',
            minute: '2-digit',
            meridiem: 'short'
        },
        navLinks: true,
        editable: true,
        selectable: true,
        nowIndicator: true,
        dayMaxEvents: true,
        selectMirror: true,
        
        // عند تحديد فترة زمنية
        select: function(info) {
            openNewEventModal(info.startStr, info.endStr);
        },
        
        // عند النقر على حدث
        eventClick: function(info) {
            openViewEventModal(info.event.id);
        },
        
        // عند سحب حدث (تغيير موعده)
        eventDrop: function(info) {
            updateEventDates(info.event);
        },
        
        // عند تغيير حجم الحدث (تغيير مدته)
        eventResize: function(info) {
            updateEventDates(info.event);
        },
        
        // تحميل الأحداث من الخادم
        events: function(info, successCallback, failureCallback) {
            loadEvents(info.start, info.end, successCallback, failureCallback);
        },
        
        // تنسيق الأحداث
        eventClassNames: function(arg) {
            // تطبيق فئات CSS مختلفة بناءً على حالة الفعالية
            var classes = [];
            
            switch (arg.event.extendedProps.status) {
                case 'محجوزة':
                    classes.push('event-reserved');
                    break;
                case 'قيد التنفيذ':
                    classes.push('event-in-progress');
                    break;
                case 'منتهية':
                    classes.push('event-completed');
                    break;
                case 'ملغاة':
                    classes.push('event-cancelled');
                    break;
                default:
                    classes.push('event-default');
            }
            
            return classes;
        },
        
        // عرض معلومات إضافية عند المرور بالمؤشر
        eventDidMount: function(info) {
            var tooltip = new bootstrap.Tooltip(info.el, {
                title: getEventTooltipContent(info.event),
                placement: 'top',
                trigger: 'hover',
                container: 'body',
                html: true
            });
        }
    });
    
    // عرض التقويم
    calendar.render();
    
    // إضافة معالج لتصفية الفعاليات
    setupEventFilters(calendar);
}

/**
 * تحميل الفعاليات من الخادم
 * 
 * @param {Date} start تاريخ البداية
 * @param {Date} end تاريخ النهاية
 * @param {function} successCallback دالة النجاح
 * @param {function} failureCallback دالة الفشل
 */
function loadEvents(start, end, successCallback, failureCallback) {
    // تنسيق التواريخ
    var startDate = formatDate(start);
    var endDate = formatDate(end);
    
    // تجميع معلمات التصفية
    var filters = getEventFilters();
    
    // طلب البيانات بـ AJAX
    $.ajax({
        url: 'events/get-calendar-events',
        type: 'GET',
        dataType: 'json',
        data: {
            start_date: startDate,
            end_date: endDate,
            ...filters
        },
        success: function(response) {
            if (response.success) {
                var events = transformEvents(response.data);
                successCallback(events);
            } else {
                failureCallback(response.message || 'حدث خطأ أثناء تحميل الفعاليات');
            }
        },
        error: function(xhr, status, error) {
            failureCallback('حدث خطأ في الاتصال بالخادم');
        }
    });
}

/**
 * تحويل بيانات الفعاليات إلى الصيغة المطلوبة للتقويم
 * 
 * @param {Array} events مصفوفة الفعاليات
 * @return {Array} مصفوفة الفعاليات بالصيغة المطلوبة
 */
function transformEvents(events) {
    return events.map(function(event) {
        // إنشاء كائن البداية والنهاية
        var startDateTime = event.date + 'T' + (event.start_time || '00:00:00');
        var endDateTime = event.date + 'T' + (event.end_time || '23:59:59');
        
        // اختيار لون الفعالية بناءً على نوعها أو حالتها
        var eventColor = getEventColor(event.status, event.event_type_id);
        
        return {
            id: event.id,
            title: event.title,
            start: startDateTime,
            end: endDateTime,
            allDay: !event.start_time || !event.end_time,
            backgroundColor: eventColor.background,
            borderColor: eventColor.border,
            textColor: eventColor.text,
            extendedProps: {
                customer: event.customer_name,
                location: event.location,
                status: event.status_name,
                type: event.event_type_name,
                guests: event.guest_count,
                package: event.package_name,
                totalCost: event.total_package_cost,
                description: event.description
            }
        };
    });
}

/**
 * الحصول على ألوان الفعالية بناءً على حالتها ونوعها
 * 
 * @param {string} status حالة الفعالية
 * @param {number} typeId معرف نوع الفعالية
 * @return {Object} كائن يحتوي على ألوان الفعالية
 */
function getEventColor(status, typeId) {
    // الألوان الافتراضية
    var colors = {
        background: '#3788d8',
        border: '#2c6fae',
        text: '#ffffff'
    };
    
    // تحديد الألوان بناءً على الحالة
    switch (status) {
        case 'محجوزة':
            colors.background = '#ffc107';
            colors.border = '#e0a800';
            colors.text = '#212529';
            break;
        case 'قيد التنفيذ':
            colors.background = '#17a2b8';
            colors.border = '#138496';
            colors.text = '#ffffff';
            break;
        case 'منتهية':
            colors.background = '#28a745';
            colors.border = '#1e7e34';
            colors.text = '#ffffff';
            break;
        case 'ملغاة':
            colors.background = '#dc3545';
            colors.border = '#bd2130';
            colors.text = '#ffffff';
            break;
    }
    
    return colors;
}

/**
 * تنسيق التاريخ بصيغة YYYY-MM-DD
 * 
 * @param {Date} date كائن التاريخ
 * @return {string} التاريخ المنسق
 */
function formatDate(date) {
    var year = date.getFullYear();
    var month = (date.getMonth() + 1).toString().padStart(2, '0');
    var day = date.getDate().toString().padStart(2, '0');
    
    return year + '-' + month + '-' + day;
}

/**
 * الحصول على محتوى التلميح للفعالية
 * 
 * @param {Object} event كائن الفعالية
 * @return {string} محتوى HTML للتلميح
 */
function getEventTooltipContent(event) {
    var props = event.extendedProps;
    
    var content = '<div class="event-tooltip">';
    content += '<h6>' + event.title + '</h6>';
    content += '<p><strong>العميل:</strong> ' + props.customer + '</p>';
    
    if (props.location) {
        content += '<p><strong>الموقع:</strong> ' + props.location + '</p>';
    }
    
    content += '<p><strong>الحالة:</strong> ' + props.status + '</p>';
    content += '<p><strong>النوع:</strong> ' + props.type + '</p>';
    
    if (props.guests) {
        content += '<p><strong>عدد الضيوف:</strong> ' + props.guests + '</p>';
    }
    
    content += '</div>';
    
    return content;
}

/**
 * فتح نافذة إضافة فعالية جديدة
 * 
 * @param {string} startDate تاريخ البداية
 * @param {string} endDate تاريخ النهاية
 */
function openNewEventModal(startDate, endDate) {
    // تحديث قيم التاريخ في النموذج
    var formattedStartDate = formatDateForForm(startDate);
    $('#event_date').val(formattedStartDate);
    
    // فتح النافذة المنبثقة
    var modal = new bootstrap.Modal(document.getElementById('add-event-modal'));
    modal.show();
}

/**
 * فتح نافذة عرض تفاصيل الفعالية
 * 
 * @param {string} eventId معرف الفعالية
 */
function openViewEventModal(eventId) {
    // طلب تفاصيل الفعالية بـ AJAX
    $.ajax({
        url: 'events/get-event-details',
        type: 'GET',
        dataType: 'html',
        data: {
            event_id: eventId
        },
        success: function(response) {
            // تحديث محتوى النافذة المنبثقة
            $('#view-event-modal .modal-body').html(response);
            
            // فتح النافذة المنبثقة
            var modal = new bootstrap.Modal(document.getElementById('view-event-modal'));
            modal.show();
        },
        error: function() {
            showNotification('error', 'حدث خطأ أثناء تحميل تفاصيل الفعالية');
        }
    });
}

/**
 * تحديث تواريخ الفعالية بعد السحب أو تغيير الحجم
 * 
 * @param {Object} event كائن الفعالية
 */
function updateEventDates(event) {
    var eventId = event.id;
    var startDate = formatDate(event.start);
    var startTime = formatTime(event.start);
    var endTime = event.end ? formatTime(event.end) : null;
    
    // طلب تحديث الفعالية بـ AJAX
    $.ajax({
        url: 'events/update-event-dates',
        type: 'POST',
        dataType: 'json',
        data: {
            event_id: eventId,
            date: startDate,
            start_time: startTime,
            end_time: endTime,
            csrf_token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                showNotification('success', 'تم تحديث موعد الفعالية بنجاح');
            } else {
                showNotification('error', response.message || 'حدث خطأ أثناء تحديث الفعالية');
                // إعادة تحميل التقويم لإلغاء التغييرات
                calendar.refetchEvents();
            }
        },
        error: function() {
            showNotification('error', 'حدث خطأ في الاتصال بالخادم');
            // إعادة تحميل التقويم لإلغاء التغييرات
            calendar.refetchEvents();
        }
    });
}

/**
 * تنسيق التاريخ لاستخدامه في نماذج الإدخال
 * 
 * @param {string} dateString سلسلة التاريخ بصيغة ISO
 * @return {string} التاريخ المنسق بصيغة DD/MM/YYYY
 */
function formatDateForForm(dateString) {
    var date = new Date(dateString);
    var day = date.getDate().toString().padStart(2, '0');
    var month = (date.getMonth() + 1).toString().padStart(2, '0');
    var year = date.getFullYear();
    
    return day + '/' + month + '/' + year;
}

/**
 * تنسيق الوقت بصيغة HH:MM:SS
 * 
 * @param {Date} date كائن التاريخ
 * @return {string} الوقت المنسق
 */
function formatTime(date) {
    var hours = date.getHours().toString().padStart(2, '0');
    var minutes = date.getMinutes().toString().padStart(2, '0');
    var seconds = date.getSeconds().toString().padStart(2, '0');
    
    return hours + ':' + minutes + ':' + seconds;
}

/**
 * الحصول على معلمات تصفية الفعاليات
 * 
 * @return {Object} كائن يحتوي على معلمات التصفية
 */
function getEventFilters() {
    return {
        customer_id: $('#filter-customer').val() || '',
        event_type_id: $('#filter-event-type').val() || '',
        status_id: $('#filter-status').val() || ''
    };
}

/**
 * إعداد معالجات تصفية الفعاليات
 * 
 * @param {Object} calendar كائن التقويم
 */
function setupEventFilters(calendar) {
    // تسجيل حدث التغيير لعناصر التصفية
    $('#filter-customer, #filter-event-type, #filter-status').on('change', function() {
        // إعادة تحميل الفعاليات
        calendar.refetchEvents();
    });
    
    // تسجيل حدث النقر لزر إعادة تعيين التصفية
    $('#reset-filters').on('click', function() {
        // إعادة تعيين القيم
        $('#filter-customer, #filter-event-type, #filter-status').val('');
        
        // إعادة تحميل الفعاليات
        calendar.refetchEvents();
    });
}
