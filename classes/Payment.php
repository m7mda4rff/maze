<?php
/**
 * فئة المدفوعات
 * 
 * تستخدم لإدارة المدفوعات المرتبطة بالفعاليات
 */

// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    exit('لا يمكن الوصول المباشر لهذا الملف');
}

class Payment {
    /**
     * معرف الدفعة
     * @var int
     */
    private $id;
    
    /**
     * معرف الفعالية
     * @var int
     */
    private $event_id;
    
    /**
     * مبلغ الدفعة
     * @var float
     */
    private $amount;
    
    /**
     * تاريخ الدفع
     * @var string
     */
    private $payment_date;
    
    /**
     * طريقة الدفع
     * @var string
     */
    private $payment_method;
    
    /**
     * نوع الدفع
     * @var string
     */
    private $payment_type;
    
    /**
     * ملاحظات
     * @var string
     */
    private $notes;
    
    /**
     * معرف منشئ الدفعة
     * @var int
     */
    private $created_by;
    
    /**
     * كائن قاعدة البيانات
     * @var Database
     */
    private $db;
    
    /**
     * إنشاء كائن جديد للمدفوعات
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * تحميل معلومات الدفعة بواسطة المعرف
     * 
     * @param int $paymentId معرف الدفعة
     * @return bool
     */
    public function loadById($paymentId) {
        $payment = $this->db->fetchOne('SELECT * FROM payments WHERE id = ?', [$paymentId]);
        
        if (!$payment) {
            return false;
        }
        
        $this->setProperties($payment);
        
        return true;
    }
    
    /**
     * تعيين خصائص الدفعة من مصفوفة
     * 
     * @param array $data مصفوفة بيانات الدفعة
     * @return void
     */
    private function setProperties($data) {
        $this->id = $data['id'];
        $this->event_id = $data['event_id'];
        $this->amount = $data['amount'];
        $this->payment_date = $data['payment_date'];
        $this->payment_method = $data['payment_method'];
        $this->payment_type = $data['payment_type'];
        $this->notes = $data['notes'];
        $this->created_by = $data['created_by'];
    }
    
    /**
     * إنشاء دفعة جديدة
     * 
     * @param array $paymentData بيانات الدفعة
     * @return int|false معرف الدفعة الجديدة أو false في حالة الفشل
     */
    public function create($paymentData) {
        // التحقق من وجود الفعالية
        if (!$this->db->exists('events', 'id = ?', [$paymentData['event_id']])) {
            return false;
        }
        
        // تحضير بيانات الدفعة للإدراج
        $data = [
            'event_id' => $paymentData['event_id'],
            'amount' => $paymentData['amount'],
            'payment_date' => $paymentData['payment_date'],
            'payment_method' => $paymentData['payment_method']
        ];
        
        // إضافة نوع الدفع
        if (isset($paymentData['payment_type'])) {
            $data['payment_type'] = $paymentData['payment_type'];
        } else {
            // افتراضي: إذا كانت أول دفعة فهي عربون، وإلا فهي دفعة جزئية
            $existingPayments = $this->db->count('payments', 'event_id = ?', [$paymentData['event_id']]);
            $data['payment_type'] = ($existingPayments == 0) ? 'deposit' : 'partial';
        }
        
        // إضافة الحقول الاختيارية إذا كانت موجودة
        if (isset($paymentData['notes'])) {
            $data['notes'] = $paymentData['notes'];
        }
        
        if (isset($paymentData['created_by'])) {
            $data['created_by'] = $paymentData['created_by'];
        }
        
        // إدراج الدفعة في قاعدة البيانات
        $paymentId = $this->db->insert('payments', $data);
        
        if ($paymentId) {
            // تحميل بيانات الدفعة بعد الإدراج
            $this->loadById($paymentId);
            
            // تحديث حالة الفعالية إذا كانت الدفعة نهائية
            if ($data['payment_type'] === 'final') {
                // التحقق من سداد كامل المبلغ
                $eventCost = $this->getEventTotalCost($data['event_id']);
                $totalPaid = $this->getTotalPaymentsByEvent($data['event_id']);
                
                if ($totalPaid >= $eventCost) {
                    // الحصول على معرف الحالة "منتهية"
                    $completedStatusId = $this->db->fetchValue(
                        'SELECT id FROM event_statuses WHERE name = "منتهية" OR name = "completed" LIMIT 1'
                    );
                    
                    if ($completedStatusId) {
                        // تحديث حالة الفعالية إلى "منتهية"
                        $this->db->update('events', ['status_id' => $completedStatusId], 'id = ?', [$data['event_id']]);
                    }
                }
            }
        }
        
        return $paymentId;
    }
    
    /**
     * تحديث معلومات الدفعة
     * 
     * @param int $paymentId معرف الدفعة
     * @param array $paymentData بيانات الدفعة للتحديث
     * @return int عدد الصفوف المتأثرة
     */
    public function update($paymentId, $paymentData) {
        // التحقق من وجود الدفعة
        if (!$this->db->exists('payments', 'id = ?', [$paymentId])) {
            return 0;
        }
        
        // تحضير بيانات التحديث
        $data = [];
        
        // تحديث الحقول المقدمة
        $fields = [
            'amount', 'payment_date', 'payment_method', 'payment_type', 'notes'
        ];
        
        foreach ($fields as $field) {
            if (isset($paymentData[$field])) {
                $data[$field] = $paymentData[$field];
            }
        }
        
        // التحقق من وجود بيانات للتحديث
        if (empty($data)) {
            return 0;
        }
        
        // تحديث البيانات في قاعدة البيانات
        $result = $this->db->update('payments', $data, 'id = ?', [$paymentId]);
        
        // تحديث الخصائص المحلية إذا تم تحديث نفس الدفعة المحملة
        if ($result && $this->id == $paymentId) {
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }
        }
        
        return $result;
    }
    
    /**
     * حذف دفعة
     * 
     * @param int $paymentId معرف الدفعة
     * @return int عدد الصفوف المتأثرة
     */
    public function delete($paymentId) {
        return $this->db->delete('payments', 'id = ?', [$paymentId]);
    }
    
    /**
     * الحصول على قائمة المدفوعات
     * 
     * @param array $filters مرشحات للبحث
     * @param int $limit عدد النتائج
     * @param int $offset البداية
     * @return array
     */
    public function getPayments($filters = [], $limit = 0, $offset = 0) {
        $sql = 'SELECT p.*, e.title as event_title, e.date as event_date, 
                c.name as customer_name, u.name as created_by_name
                FROM payments p
                LEFT JOIN events e ON p.event_id = e.id
                LEFT JOIN customers c ON e.customer_id = c.id
                LEFT JOIN users u ON p.created_by = u.id';
        $params = [];
        
        // إضافة الشروط إذا وجدت
        if (!empty($filters)) {
            $sql .= ' WHERE';
            $whereAdded = false;
            
            if (isset($filters['event_id'])) {
                $sql .= ' p.event_id = ?';
                $params[] = $filters['event_id'];
                $whereAdded = true;
            }
            
            if (isset($filters['customer_id'])) {
                $sql .= ($whereAdded ? ' AND' : '') . ' e.customer_id = ?';
                $params[] = $filters['customer_id'];
                $whereAdded = true;
            }
            
            if (isset($filters['payment_method'])) {
                $sql .= ($whereAdded ? ' AND' : '') . ' p.payment_method = ?';
                $params[] = $filters['payment_method'];
                $whereAdded = true;
            }
            
            if (isset($filters['payment_type'])) {
                $sql .= ($whereAdded ? ' AND' : '') . ' p.payment_type = ?';
                $params[] = $filters['payment_type'];
                $whereAdded = true;
            }
            
            if (isset($filters['date_from'])) {
                $sql .= ($whereAdded ? ' AND' : '') . ' p.payment_date >= ?';
                $params[] = $filters['date_from'];
                $whereAdded = true;
            }
            
            if (isset($filters['date_to'])) {
                $sql .= ($whereAdded ? ' AND' : '') . ' p.payment_date <= ?';
                $params[] = $filters['date_to'];
                $whereAdded = true;
            }
            
            if (isset($filters['search'])) {
                $sql .= ($whereAdded ? ' AND' : '') . ' (e.title LIKE ? OR c.name LIKE ? OR p.notes LIKE ?)';
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
        }
        
        // إضافة الترتيب
        $sql .= ' ORDER BY p.payment_date DESC, p.id DESC';
        
        // إضافة الحد والبداية
        if ($limit > 0) {
            $sql .= ' LIMIT ' . (int)$limit;
            
            if ($offset > 0) {
                $sql .= ' OFFSET ' . (int)$offset;
            }
        }
        
        // تنفيذ الاستعلام
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * الحصول على إجمالي المدفوعات للفعالية
     * 
     * @param int $eventId معرف الفعالية
     * @return float
     */
    public function getTotalPaymentsByEvent($eventId) {
        $totalPayments = $this->db->fetchValue('SELECT SUM(amount) FROM payments WHERE event_id = ?', [$eventId]);
        
        return $totalPayments ? (float) $totalPayments : 0;
    }
    
    /**
     * الحصول على إجمالي تكلفة الفعالية
     * 
     * @param int $eventId معرف الفعالية
     * @return float
     */
    public function getEventTotalCost($eventId) {
        // الحصول على تكلفة الباقة
        $packageCost = $this->db->fetchValue('SELECT total_package_cost FROM events WHERE id = ?', [$eventId]);
        
        // الحصول على مجموع التكاليف الخارجية
        $externalCosts = $this->db->fetchValue('SELECT SUM(amount) FROM external_costs WHERE event_id = ?', [$eventId]);
        
        // في حالة عدم وجود تكاليف خارجية
        if ($externalCosts === false) {
            $externalCosts = 0;
        }
        
        // إجمالي التكلفة
        return (float) $packageCost + (float) $externalCosts;
    }
    
    /**
     * الحصول على المبلغ المتبقي للفعالية
     * 
     * @param int $eventId معرف الفعالية
     * @return float
     */
    public function getRemainingAmount($eventId) {
        // الحصول على إجمالي التكلفة
        $totalCost = $this->getEventTotalCost($eventId);
        
        // الحصول على إجمالي المدفوعات
        $totalPayments = $this->getTotalPaymentsByEvent($eventId);
        
        // المبلغ المتبقي
        return $totalCost - $totalPayments;
    }
    
    /**
     * إنشاء إيصال دفع
     * 
     * @param int $paymentId معرف الدفعة
     * @return array بيانات الإيصال
     */
    public function generateReceipt($paymentId) {
        // الحصول على بيانات الدفعة
        $sql = 'SELECT p.*, e.title as event_title, e.date as event_date, 
                c.name as customer_name, c.phone as customer_phone,
                u.name as created_by_name
                FROM payments p
                LEFT JOIN events e ON p.event_id = e.id
                LEFT JOIN customers c ON e.customer_id = c.id
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.id = ?';
        
        $payment = $this->db->fetchOne($sql, [$paymentId]);
        
        if (!$payment) {
            return null;
        }
        
        // الحصول على إجمالي التكلفة والمبلغ المتبقي
        $totalCost = $this->getEventTotalCost($payment['event_id']);
        $totalPaid = $this->getTotalPaymentsByEvent($payment['event_id']);
        $remainingAmount = $totalCost - $totalPaid;
        
        // إضافة المعلومات الإضافية
        $payment['total_cost'] = $totalCost;
        $payment['total_paid'] = $totalPaid;
        $payment['remaining_amount'] = $remainingAmount;
        $payment['receipt_date'] = date('Y-m-d');
        $payment['receipt_number'] = 'REC-' . str_pad($payment['id'], 6, '0', STR_PAD_LEFT);
        
        return $payment;
    }
    
    /**
     * الحصول على إحصائيات المدفوعات
     * 
     * @param string $startDate تاريخ البداية (Y-m-d)، اختياري
     * @param string $endDate تاريخ النهاية (Y-m-d)، اختياري
     * @return array
     */
    public function getPaymentStats($startDate = null, $endDate = null) {
        $stats = [
            'total_payments' => 0,
            'by_method' => [],
            'by_type' => [],
            'by_month' => [],
            'recent_payments' => []
        ];
        
        // بناء شرط الفترة الزمنية
        $dateCondition = '';
        $params = [];
        
        if ($startDate && $endDate) {
            $dateCondition = ' WHERE p.payment_date BETWEEN ? AND ?';
            $params = [$startDate, $endDate];
        } elseif ($startDate) {
            $dateCondition = ' WHERE p.payment_date >= ?';
            $params = [$startDate];
        } elseif ($endDate) {
            $dateCondition = ' WHERE p.payment_date <= ?';
            $params = [$endDate];
        }
        
        // إجمالي المدفوعات
        $totalPaymentsParams = $params;
        $stats['total_payments'] = $this->db->fetchValue('
            SELECT SUM(p.amount)
            FROM payments p
            ' . $dateCondition, $totalPaymentsParams);
        
        if ($stats['total_payments'] === false) {
            $stats['total_payments'] = 0;
        }
        
        // المدفوعات حسب طريقة الدفع
        $byMethodParams = $params;
        $stats['by_method'] = $this->db->fetchAll('
            SELECT p.payment_method, SUM(p.amount) as total_amount, COUNT(p.id) as count
            FROM payments p
            ' . $dateCondition . '
            GROUP BY p.payment_method
            ORDER BY total_amount DESC
        ', $byMethodParams);
        
        // المدفوعات حسب نوع الدفع
        $byTypeParams = $params;
        $stats['by_type'] = $this->db->fetchAll('
            SELECT p.payment_type, SUM(p.amount) as total_amount, COUNT(p.id) as count
            FROM payments p
            ' . $dateCondition . '
            GROUP BY p.payment_type
            ORDER BY total_amount DESC
        ', $byTypeParams);
        
        // المدفوعات حسب الشهر
        $byMonthParams = $params;
        $stats['by_month'] = $this->db->fetchAll('
            SELECT DATE_FORMAT(p.payment_date, "%Y-%m") as month, SUM(p.amount) as total_amount
            FROM payments p
            ' . $dateCondition . '
            GROUP BY DATE_FORMAT(p.payment_date, "%Y-%m")
            ORDER BY month ASC
        ', $byMonthParams);
        
        // آخر المدفوعات
        $recentPaymentsParams = $params;
        $stats['recent_payments'] = $this->db->fetchAll('
            SELECT p.id, p.amount, p.payment_date, p.payment_method, 
                   e.title as event_title, c.name as customer_name
            FROM payments p
            LEFT JOIN events e ON p.event_id = e.id
            LEFT JOIN customers c ON e.customer_id = c.id
            ' . $dateCondition . '
            ORDER BY p.payment_date DESC, p.id DESC
            LIMIT 10
        ', $recentPaymentsParams);
        
        return $stats;
    }
    
    /**
     * تصدير بيانات المدفوعات إلى CSV
     * 
     * @param array $filters مرشحات للبحث
     * @return string
     */
    public function exportToCSV($filters = []) {
        // الحصول على بيانات المدفوعات
        $payments = $this->getPayments($filters);
        
        if (empty($payments)) {
            return '';
        }
        
        // إنشاء مقبض ملف مؤقت
        $output = fopen('php://temp', 'w');
        
        // كتابة رأس الملف (UTF-8 BOM)
        fputs($output, "\xEF\xBB\xBF");
        
        // كتابة رأس الأعمدة
        fputcsv($output, [
            'معرف الدفعة',
            'الفعالية',
            'تاريخ الفعالية',
            'العميل',
            'المبلغ',
            'تاريخ الدفع',
            'طريقة الدفع',
            'نوع الدفع',
            'ملاحظات',
            'تم بواسطة',
            'تاريخ الإضافة'
        ]);
        
        // كتابة البيانات
        foreach ($payments as $payment) {
            // تحويل نوع الدفع إلى صيغة مفهومة
            $paymentType = $payment['payment_type'];
            switch ($paymentType) {
                case 'deposit':
                    $paymentType = 'عربون';
                    break;
                case 'partial':
                    $paymentType = 'دفعة جزئية';
                    break;
                case 'final':
                    $paymentType = 'دفعة نهائية';
                    break;
            }
            
            // تحويل طريقة الدفع إلى صيغة مفهومة
            $paymentMethod = $payment['payment_method'];
            switch ($paymentMethod) {
                case 'cash':
                    $paymentMethod = 'نقدي';
                    break;
                case 'bank_transfer':
                    $paymentMethod = 'تحويل بنكي';
                    break;
                case 'credit_card':
                    $paymentMethod = 'بطاقة ائتمان';
                    break;
                case 'other':
                    $paymentMethod = 'أخرى';
                    break;
            }
            
            fputcsv($output, [
                $payment['id'],
                $payment['event_title'],
                $payment['event_date'],
                $payment['customer_name'],
                $payment['amount'],
                $payment['payment_date'],
                $paymentMethod,
                $paymentType,
                $payment['notes'],
                $payment['created_by_name'],
                $payment['created_at']
            ]);
        }
        
        // الحصول على المحتوى والإغلاق
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * الحصول على معرف الدفعة
     * 
     * @return int
     */
    public function getId() {
        return $this->id;
    }
    
    /**
     * الحصول على معرف الفعالية
     * 
     * @return int
     */
    public function getEventId() {
        return $this->event_id;
    }
    
    /**
     * الحصول على مبلغ الدفعة
     * 
     * @return float
     */
    public function getAmount() {
        return $this->amount;
    }
    
    /**
     * الحصول على تاريخ الدفع
     * 
     * @param string $format صيغة التاريخ، اختيارية
     * @return string
     */
    public function getPaymentDate($format = null) {
        if ($format && $this->payment_date) {
            return date($format, strtotime($this->payment_date));
        }
        return $this->payment_date;
    }
    
    /**
     * الحصول على طريقة الدفع
     * 
     * @return string
     */
    public function getPaymentMethod() {
        return $this->payment_method;
    }
    
    /**
     * الحصول على نوع الدفع
     * 
     * @return string
     */
    public function getPaymentType() {
        return $this->payment_type;
    }
    
    /**
     * الحصول على الملاحظات
     * 
     * @return string
     */
    public function getNotes() {
        return $this->notes;
    }
    
    /**
     * الحصول على معرف منشئ الدفعة
     * 
     * @return int
     */
    public function getCreatedBy() {
        return $this->created_by;
    }
    
    /**
     * الحصول على اسم منشئ الدفعة
     * 
     * @return string
     */
    public function getCreatedByName() {
        if (!$this->created_by) {
            return '';
        }
        
        return $this->db->fetchValue('SELECT name FROM users WHERE id = ?', [$this->created_by]);
    }
}
