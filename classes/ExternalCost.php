<?php
/**
 * فئة التكاليف الخارجية
 * 
 * تستخدم لإدارة التكاليف الخارجية المرتبطة بالفعاليات
 */

// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    exit('لا يمكن الوصول المباشر لهذا الملف');
}

class ExternalCost {
    /**
     * معرف التكلفة الخارجية
     * @var int
     */
    private $id;
    
    /**
     * معرف الفعالية
     * @var int
     */
    private $event_id;
    
    /**
     * معرف نوع التكلفة
     * @var int
     */
    private $cost_type_id;
    
    /**
     * وصف التكلفة
     * @var string
     */
    private $description;
    
    /**
     * مبلغ التكلفة
     * @var float
     */
    private $amount;
    
    /**
     * المورد/المزود
     * @var string
     */
    private $vendor;
    
    /**
     * ملاحظات
     * @var string
     */
    private $notes;
    
    /**
     * كائن قاعدة البيانات
     * @var Database
     */
    private $db;
    
    /**
     * إنشاء كائن جديد للتكاليف الخارجية
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * تحميل معلومات التكلفة الخارجية بواسطة المعرف
     * 
     * @param int $costId معرف التكلفة
     * @return bool
     */
    public function loadById($costId) {
        $cost = $this->db->fetchOne('SELECT * FROM external_costs WHERE id = ?', [$costId]);
        
        if (!$cost) {
            return false;
        }
        
        $this->setProperties($cost);
        
        return true;
    }
    
    /**
     * تعيين خصائص التكلفة الخارجية من مصفوفة
     * 
     * @param array $data مصفوفة بيانات التكلفة
     * @return void
     */
    private function setProperties($data) {
        $this->id = $data['id'];
        $this->event_id = $data['event_id'];
        $this->cost_type_id = $data['cost_type_id'];
        $this->description = $data['description'];
        $this->amount = $data['amount'];
        $this->vendor = $data['vendor'];
        $this->notes = $data['notes'];
    }
    
    /**
     * إنشاء تكلفة خارجية جديدة
     * 
     * @param array $costData بيانات التكلفة
     * @return int|false معرف التكلفة الجديدة أو false في حالة الفشل
     */
    public function create($costData) {
        // التحقق من وجود الفعالية
        if (!$this->db->exists('events', 'id = ?', [$costData['event_id']])) {
            return false;
        }
        
        // تحضير بيانات التكلفة للإدراج
        $data = [
            'event_id' => $costData['event_id'],
            'amount' => $costData['amount'],
            'description' => $costData['description']
        ];
        
        // إضافة الحقول الاختيارية إذا كانت موجودة
        if (isset($costData['cost_type_id'])) {
            $data['cost_type_id'] = $costData['cost_type_id'];
        }
        
        if (isset($costData['vendor'])) {
            $data['vendor'] = $costData['vendor'];
        }
        
        if (isset($costData['notes'])) {
            $data['notes'] = $costData['notes'];
        }
        
        // إدراج التكلفة في قاعدة البيانات
        $costId = $this->db->insert('external_costs', $data);
        
        if ($costId) {
            // تحميل بيانات التكلفة بعد الإدراج
            $this->loadById($costId);
        }
        
        return $costId;
    }
    
    /**
     * تحديث معلومات التكلفة الخارجية
     * 
     * @param int $costId معرف التكلفة
     * @param array $costData بيانات التكلفة للتحديث
     * @return int عدد الصفوف المتأثرة
     */
    public function update($costId, $costData) {
        // التحقق من وجود التكلفة
        if (!$this->db->exists('external_costs', 'id = ?', [$costId])) {
            return 0;
        }
        
        // تحضير بيانات التحديث
        $data = [];
        
        // تحديث الحقول المقدمة
        if (isset($costData['cost_type_id'])) {
            $data['cost_type_id'] = $costData['cost_type_id'];
        }
        
        if (isset($costData['description'])) {
            $data['description'] = $costData['description'];
        }
        
        if (isset($costData['amount'])) {
            $data['amount'] = $costData['amount'];
        }
        
        if (isset($costData['vendor'])) {
            $data['vendor'] = $costData['vendor'];
        }
        
        if (isset($costData['notes'])) {
            $data['notes'] = $costData['notes'];
        }
        
        // التحقق من وجود بيانات للتحديث
        if (empty($data)) {
            return 0;
        }
        
        // تحديث البيانات في قاعدة البيانات
        $result = $this->db->update('external_costs', $data, 'id = ?', [$costId]);
        
        // تحديث الخصائص المحلية إذا تم تحديث نفس التكلفة المحملة
        if ($result && $this->id == $costId) {
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }
        }
        
        return $result;
    }
    
    /**
     * حذف تكلفة خارجية
     * 
     * @param int $costId معرف التكلفة
     * @return int عدد الصفوف المتأثرة
     */
    public function delete($costId) {
        return $this->db->delete('external_costs', 'id = ?', [$costId]);
    }
    
    /**
     * الحصول على قائمة التكاليف الخارجية
     * 
     * @param array $filters مرشحات للبحث
     * @param int $limit عدد النتائج
     * @param int $offset البداية
     * @return array
     */
    public function getCosts($filters = [], $limit = 0, $offset = 0) {
        $sql = 'SELECT ec.*, e.title as event_title, e.date as event_date, 
                ct.name as cost_type_name, c.name as customer_name
                FROM external_costs ec
                LEFT JOIN events e ON ec.event_id = e.id
                LEFT JOIN cost_types ct ON ec.cost_type_id = ct.id
                LEFT JOIN customers c ON e.customer_id = c.id';
        $params = [];
        
        // إضافة الشروط إذا وجدت
        if (!empty($filters)) {
            $sql .= ' WHERE';
            $whereAdded = false;
            
            if (isset($filters['event_id'])) {
                $sql .= ' ec.event_id = ?';
                $params[] = $filters['event_id'];
                $whereAdded = true;
            }
            
            if (isset($filters['cost_type_id'])) {
                $sql .= ($whereAdded ? ' AND' : '') . ' ec.cost_type_id = ?';
                $params[] = $filters['cost_type_id'];
                $whereAdded = true;
            }
            
            if (isset($filters['customer_id'])) {
                $sql .= ($whereAdded ? ' AND' : '') . ' e.customer_id = ?';
                $params[] = $filters['customer_id'];
                $whereAdded = true;
            }
            
            if (isset($filters['date_from'])) {
                $sql .= ($whereAdded ? ' AND' : '') . ' e.date >= ?';
                $params[] = $filters['date_from'];
                $whereAdded = true;
            }
            
            if (isset($filters['date_to'])) {
                $sql .= ($whereAdded ? ' AND' : '') . ' e.date <= ?';
                $params[] = $filters['date_to'];
                $whereAdded = true;
            }
            
            if (isset($filters['search'])) {
                $sql .= ($whereAdded ? ' AND' : '') . ' (ec.description LIKE ? OR ec.vendor LIKE ? OR e.title LIKE ?)';
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
        }
        
        // إضافة الترتيب
        $sql .= ' ORDER BY e.date DESC, ec.id DESC';
        
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
     * الحصول على إجمالي التكاليف الخارجية للفعالية
     * 
     * @param int $eventId معرف الفعالية
     * @return float
     */
    public function getTotalCostsByEvent($eventId) {
        $totalCost = $this->db->fetchValue('SELECT SUM(amount) FROM external_costs WHERE event_id = ?', [$eventId]);
        
        return $totalCost ? (float) $totalCost : 0;
    }
    
    /**
     * الحصول على التكاليف الخارجية للفعالية حسب النوع
     * 
     * @param int $eventId معرف الفعالية
     * @return array
     */
    public function getCostsByEventGroupedByType($eventId) {
        $sql = 'SELECT ct.name as cost_type, SUM(ec.amount) as total_amount
                FROM external_costs ec
                LEFT JOIN cost_types ct ON ec.cost_type_id = ct.id
                WHERE ec.event_id = ?
                GROUP BY ec.cost_type_id
                ORDER BY total_amount DESC';
        
        return $this->db->fetchAll($sql, [$eventId]);
    }
    
    /**
     * الحصول على أنواع التكاليف
     * 
     * @return array
     */
    public function getCostTypes() {
        return $this->db->fetchAll('SELECT * FROM cost_types ORDER BY name ASC');
    }
    
    /**
     * إضافة نوع تكلفة جديد
     * 
     * @param string $typeName اسم نوع التكلفة
     * @return int|false
     */
    public function addCostType($typeName) {
        // التحقق من عدم وجود نوع بنفس الاسم
        if ($this->db->exists('cost_types', 'name = ?', [$typeName])) {
            return false;
        }
        
        return $this->db->insert('cost_types', ['name' => $typeName]);
    }
    
    /**
     * الحصول على إحصائيات التكاليف
     * 
     * @param string $startDate تاريخ البداية (Y-m-d)، اختياري
     * @param string $endDate تاريخ النهاية (Y-m-d)، اختياري
     * @return array
     */
    public function getCostStats($startDate = null, $endDate = null) {
        $stats = [
            'total_costs' => 0,
            'by_type' => [],
            'by_vendor' => [],
            'by_month' => [],
            'average_cost_per_event' => 0
        ];
        
        // بناء شرط الفترة الزمنية
        $dateCondition = '';
        $params = [];
        
        if ($startDate && $endDate) {
            $dateCondition = ' WHERE e.date BETWEEN ? AND ?';
            $params = [$startDate, $endDate];
        } elseif ($startDate) {
            $dateCondition = ' WHERE e.date >= ?';
            $params = [$startDate];
        } elseif ($endDate) {
            $dateCondition = ' WHERE e.date <= ?';
            $params = [$endDate];
        }
        
        // إجمالي التكاليف
        $totalCostsParams = $params;
        $stats['total_costs'] = $this->db->fetchValue('
            SELECT SUM(ec.amount)
            FROM external_costs ec
            JOIN events e ON ec.event_id = e.id
            ' . $dateCondition, $totalCostsParams);
        
        if ($stats['total_costs'] === false) {
            $stats['total_costs'] = 0;
        }
        
        // التكاليف حسب النوع
        $byTypeParams = $params;
        $stats['by_type'] = $this->db->fetchAll('
            SELECT ct.name, SUM(ec.amount) as total_amount, COUNT(ec.id) as count
            FROM external_costs ec
            JOIN events e ON ec.event_id = e.id
            LEFT JOIN cost_types ct ON ec.cost_type_id = ct.id
            ' . $dateCondition . '
            GROUP BY ec.cost_type_id
            ORDER BY total_amount DESC
        ', $byTypeParams);
        
        // التكاليف حسب المورد
        $byVendorParams = $params;
        $stats['by_vendor'] = $this->db->fetchAll('
            SELECT ec.vendor, SUM(ec.amount) as total_amount, COUNT(ec.id) as count
            FROM external_costs ec
            JOIN events e ON ec.event_id = e.id
            ' . $dateCondition . '
            WHERE ec.vendor IS NOT NULL AND ec.vendor != ""
            GROUP BY ec.vendor
            ORDER BY total_amount DESC
            LIMIT 10
        ', $byVendorParams);
        
        // التكاليف حسب الشهر
        $byMonthParams = $params;
        $stats['by_month'] = $this->db->fetchAll('
            SELECT DATE_FORMAT(e.date, "%Y-%m") as month, SUM(ec.amount) as total_amount
            FROM external_costs ec
            JOIN events e ON ec.event_id = e.id
            ' . $dateCondition . '
            GROUP BY DATE_FORMAT(e.date, "%Y-%m")
            ORDER BY month ASC
        ', $byMonthParams);
        
        // متوسط التكلفة لكل فعالية
        $avgCostParams = $params;
        $eventCount = $this->db->fetchValue('
            SELECT COUNT(DISTINCT ec.event_id)
            FROM external_costs ec
            JOIN events e ON ec.event_id = e.id
            ' . $dateCondition, $avgCostParams);
        
        if ($eventCount && $eventCount > 0) {
            $stats['average_cost_per_event'] = $stats['total_costs'] / $eventCount;
        }
        
        return $stats;
    }
    
    /**
     * تصدير بيانات التكاليف إلى CSV
     * 
     * @param array $filters مرشحات للبحث
     * @return string
     */
    public function exportToCSV($filters = []) {
        // الحصول على بيانات التكاليف
        $costs = $this->getCosts($filters);
        
        if (empty($costs)) {
            return '';
        }
        
        // إنشاء مقبض ملف مؤقت
        $output = fopen('php://temp', 'w');
        
        // كتابة رأس الملف (UTF-8 BOM)
        fputs($output, "\xEF\xBB\xBF");
        
        // كتابة رأس الأعمدة
        fputcsv($output, [
            'معرف التكلفة',
            'الفعالية',
            'تاريخ الفعالية',
            'العميل',
            'نوع التكلفة',
            'الوصف',
            'المبلغ',
            'المورد',
            'ملاحظات',
            'تاريخ الإضافة'
        ]);
        
        // كتابة البيانات
        foreach ($costs as $cost) {
            fputcsv($output, [
                $cost['id'],
                $cost['event_title'],
                $cost['event_date'],
                $cost['customer_name'],
                $cost['cost_type_name'],
                $cost['description'],
                $cost['amount'],
                $cost['vendor'],
                $cost['notes'],
                $cost['created_at']
            ]);
        }
        
        // الحصول على المحتوى والإغلاق
        rewind($output);
        $csv = stream_get_contents($output);
        fclose($output);
        
        return $csv;
    }
    
    /**
     * الحصول على معرف التكلفة
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
     * الحصول على معرف نوع التكلفة
     * 
     * @return int
     */
    public function getCostTypeId() {
        return $this->cost_type_id;
    }
    
    /**
     * الحصول على وصف التكلفة
     * 
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }
    
    /**
     * الحصول على مبلغ التكلفة
     * 
     * @return float
     */
    public function getAmount() {
        return $this->amount;
    }
    
    /**
     * الحصول على المورد
     * 
     * @return string
     */
    public function getVendor() {
        return $this->vendor;
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
     * الحصول على اسم نوع التكلفة
     * 
     * @return string
     */
    public function getCostTypeName() {
        return $this->db->fetchValue('SELECT name FROM cost_types WHERE id = ?', [$this->cost_type_id]);
    }
}
