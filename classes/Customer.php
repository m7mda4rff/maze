<?php
/**
 * فئة العميل
 * 
 * تستخدم لإدارة بيانات العملاء
 */

// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    exit('لا يمكن الوصول المباشر لهذا الملف');
}

class Customer {
    /**
     * الحصول على مصادر العملاء
     * 
     * @return array
     */
    public function getCustomerSources() {
        return $this->db->fetchAll('SELECT * FROM customer_sources ORDER BY name ASC');
    }
    
    /**
     * الحصول على اسم مصدر العميل
     * 
     * @param int $sourceId معرف المصدر
     * @return string
     */
    public function getSourceName($sourceId) {
        return $this->db->fetchValue('SELECT name FROM customer_sources WHERE id = ?', [$sourceId]);
    }
    
    /**
     * إضافة مصدر جديد للعملاء
     * 
     * @param string $sourceName اسم المصدر
     * @return int|false
     */
    public function addSource($sourceName) {
        return $this->db->insert('customer_sources', ['name' => $sourceName]);
    }
    
    /**
     * التحقق من وجود العميل برقم الهاتف
     * 
     * @param string $phone رقم الهاتف
     * @return bool
     */
    public function phoneExists($phone) {
        return $this->db->exists('customers', 'phone = ?', [$phone]);
    }
    
    /**
     * الحصول على العملاء من أجل قائمة منسدلة
     * 
     * @return array
     */
    public function getCustomersForDropdown() {
        return $this->db->fetchAll('SELECT id, name FROM customers ORDER BY name ASC');
    }
    
    /**
     * الحصول على معرف العميل
     * 
     * @return int
     */
    public function getId() {
        return $this->id;
    }
    
    /**
     * الحصول على اسم العميل
     * 
     * @return string
     */
    public function getName() {
        return $this->name;
    }
    
    /**
     * الحصول على رقم هاتف العميل
     * 
     * @return string
     */
    public function getPhone() {
        return $this->phone;
    }
    
    /**
     * الحصول على رقم الهاتف البديل للعميل
     * 
     * @return string
     */
    public function getAltPhone() {
        return $this->alt_phone;
    }
    
    /**
     * الحصول على البريد الإلكتروني للعميل
     * 
     * @return string
     */
    public function getEmail() {
        return $this->email;
    }
    
    /**
     * الحصول على عنوان العميل
     * 
     * @return string
     */
    public function getAddress() {
        return $this->address;
    }
    
    /**
     * الحصول على معرف مصدر العميل
     * 
     * @return int
     */
    public function getSourceId() {
        return $this->source_id;
    }
    
    /**
     * الحصول على معرف العميل المُوصي
     * 
     * @return int
     */
    public function getReferralCustomerId() {
        return $this->referral_customer_id;
    }
    
    /**
     * الحصول على تصنيف العميل
     * 
     * @return string
     */
    public function getCategory() {
        return $this->category;
    }
    
    /**
     * الحصول على ملاحظات العميل
     * 
     * @return string
     */
    public function getNotes() {
        return $this->notes;
    }
}* معرف العميل
     * @var int
     */
    private $id;
    
    /**
     * اسم العميل
     * @var string
     */
    private $name;
    
    /**
     * رقم هاتف العميل
     * @var string
     */
    private $phone;
    
    /**
     * رقم هاتف بديل للعميل
     * @var string
     */
    private $alt_phone;
    
    /**
     * البريد الإلكتروني للعميل
     * @var string
     */
    private $email;
    
    /**
     * عنوان العميل
     * @var string
     */
    private $address;
    
    /**
     * معرف مصدر العميل
     * @var int
     */
    private $source_id;
    
    /**
     * معرف العميل المُوصي (إذا كان المصدر توصية)
     * @var int
     */
    private $referral_customer_id;
    
    /**
     * تصنيف العميل (VIP، منتظم، جديد)
     * @var string
     */
    private $category;
    
    /**
     * ملاحظات خاصة بالعميل
     * @var string
     */
    private $notes;
    
    /**
     * كائن قاعدة البيانات
     * @var Database
     */
    private $db;
    
    /**
     * إنشاء كائن جديد للعميل
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * تحميل معلومات العميل بواسطة المعرف
     * 
     * @param int $customerId معرف العميل
     * @return bool
     */
    public function loadCustomerById($customerId) {
        $customer = $this->db->fetchOne('SELECT * FROM customers WHERE id = ?', [$customerId]);
        
        if (!$customer) {
            return false;
        }
        
        $this->setProperties($customer);
        
        return true;
    }
    
    /**
     * تحميل معلومات العميل بواسطة رقم الهاتف
     * 
     * @param string $phone رقم الهاتف
     * @return bool
     */
    public function loadCustomerByPhone($phone) {
        $customer = $this->db->fetchOne('SELECT * FROM customers WHERE phone = ?', [$phone]);
        
        if (!$customer) {
            return false;
        }
        
        $this->setProperties($customer);
        
        return true;
    }
    
    /**
     * تعيين خصائص العميل من مصفوفة
     * 
     * @param array $data مصفوفة بيانات العميل
     * @return void
     */
    private function setProperties($data) {
        $this->id = $data['id'];
        $this->name = $data['name'];
        $this->phone = $data['phone'];
        $this->alt_phone = $data['alt_phone'];
        $this->email = $data['email'];
        $this->address = $data['address'];
        $this->source_id = $data['source_id'];
        $this->referral_customer_id = $data['referral_customer_id'];
        $this->category = $data['category'];
        $this->notes = $data['notes'];
    }
    
    /**
     * إضافة عميل جديد
     * 
     * @param array $customerData بيانات العميل
     * @return int|false معرف العميل الجديد أو false في حالة الفشل
     */
    public function create($customerData) {
        // التحقق من وجود العميل برقم الهاتف
        if (isset($customerData['phone']) && !empty($customerData['phone'])) {
            if ($this->db->exists('customers', 'phone = ?', [$customerData['phone']])) {
                return false;
            }
        }
        
        // تحضير بيانات العميل للإدراج
        $data = [
            'name' => $customerData['name'],
            'phone' => $customerData['phone']
        ];
        
        // إضافة الحقول الاختيارية إذا كانت موجودة
        if (isset($customerData['alt_phone'])) {
            $data['alt_phone'] = $customerData['alt_phone'];
        }
        
        if (isset($customerData['email'])) {
            $data['email'] = $customerData['email'];
        }
        
        if (isset($customerData['address'])) {
            $data['address'] = $customerData['address'];
        }
        
        if (isset($customerData['source_id'])) {
            $data['source_id'] = $customerData['source_id'];
        }
        
        if (isset($customerData['referral_customer_id'])) {
            $data['referral_customer_id'] = $customerData['referral_customer_id'];
        }
        
        if (isset($customerData['category'])) {
            $data['category'] = $customerData['category'];
        }
        
        if (isset($customerData['notes'])) {
            $data['notes'] = $customerData['notes'];
        }
        
        // إدراج العميل في قاعدة البيانات
        $customerId = $this->db->insert('customers', $data);
        
        if ($customerId) {
            // تحميل بيانات العميل بعد الإدراج
            $this->loadCustomerById($customerId);
        }
        
        return $customerId;
    }
    
    /**
     * تحديث معلومات العميل
     * 
     * @param int $customerId معرف العميل
     * @param array $customerData بيانات العميل للتحديث
     * @return int عدد الصفوف المتأثرة
     */
    public function update($customerId, $customerData) {
        // التحقق من وجود العميل
        if (!$this->db->exists('customers', 'id = ?', [$customerId])) {
            return 0;
        }
        
        // تحضير بيانات التحديث
        $data = [];
        
        // تحديث الاسم إذا تم توفيره
        if (isset($customerData['name'])) {
            $data['name'] = $customerData['name'];
        }
        
        // تحديث رقم الهاتف إذا تم توفيره
        if (isset($customerData['phone'])) {
            // التحقق من عدم وجود رقم الهاتف مع عميل آخر
            if ($this->db->exists('customers', 'phone = ? AND id != ?', [$customerData['phone'], $customerId])) {
                return 0;
            }
            
            $data['phone'] = $customerData['phone'];
        }
        
        // تحديث باقي الحقول إذا تم توفيرها
        $optionalFields = ['alt_phone', 'email', 'address', 'source_id', 'referral_customer_id', 'category', 'notes'];
        foreach ($optionalFields as $field) {
            if (isset($customerData[$field])) {
                $data[$field] = $customerData[$field];
            }
        }
        
        // التحقق من وجود بيانات للتحديث
        if (empty($data)) {
            return 0;
        }
        
        // تحديث البيانات في قاعدة البيانات
        $result = $this->db->update('customers', $data, 'id = ?', [$customerId]);
        
        // تحديث الخصائص المحلية إذا تم تحديث نفس العميل المحمل
        if ($result && $this->id == $customerId) {
            foreach ($data as $key => $value) {
                $this->$key = $value;
            }
        }
        
        return $result;
    }
    
    /**
     * حذف عميل
     * 
     * @param int $customerId معرف العميل
     * @return int عدد الصفوف المتأثرة
     */
    public function delete($customerId) {
        return $this->db->delete('customers', 'id = ?', [$customerId]);
    }
    
    /**
     * الحصول على قائمة العملاء
     * 
     * @param array $filters مرشحات للبحث
     * @param int $limit عدد النتائج
     * @param int $offset البداية
     * @return array
     */
    public function getCustomers($filters = [], $limit = 0, $offset = 0) {
        $sql = 'SELECT c.*, cs.name as source_name
                FROM customers c
                LEFT JOIN customer_sources cs ON c.source_id = cs.id';
        $params = [];
        
        // إضافة الشروط إذا وجدت
        if (!empty($filters)) {
            $sql .= ' WHERE';
            $whereAdded = false;
            
            if (isset($filters['category'])) {
                $sql .= ' c.category = ?';
                $params[] = $filters['category'];
                $whereAdded = true;
            }
            
            if (isset($filters['source_id'])) {
                $sql .= ($whereAdded ? ' AND' : '') . ' c.source_id = ?';
                $params[] = $filters['source_id'];
                $whereAdded = true;
            }
            
            if (isset($filters['search'])) {
                $sql .= ($whereAdded ? ' AND' : '') . ' (c.name LIKE ? OR c.phone LIKE ? OR c.email LIKE ?)';
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
        }
        
        // إضافة الترتيب
        $sql .= ' ORDER BY c.name ASC';
        
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
     * الحصول على عدد العملاء
     * 
     * @param array $filters مرشحات للبحث
     * @return int
     */
    public function countCustomers($filters = []) {
        $sql = 'SELECT COUNT(*) FROM customers';
        $params = [];
        
        // إضافة الشروط إذا وجدت
        if (!empty($filters)) {
            $sql .= ' WHERE';
            $whereAdded = false;
            
            if (isset($filters['category'])) {
                $sql .= ' category = ?';
                $params[] = $filters['category'];
                $whereAdded = true;
            }
            
            if (isset($filters['source_id'])) {
                $sql .= ($whereAdded ? ' AND' : '') . ' source_id = ?';
                $params[] = $filters['source_id'];
                $whereAdded = true;
            }
            
            if (isset($filters['search'])) {
                $sql .= ($whereAdded ? ' AND' : '') . ' (name LIKE ? OR phone LIKE ? OR email LIKE ?)';
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
        }
        
        // تنفيذ الاستعلام
        return (int) $this->db->fetchValue($sql, $params);
    }
    
    /**
     * الحصول على فعاليات العميل
     * 
     * @param int $customerId معرف العميل
     * @param array $filters مرشحات للبحث
     * @return array
     */
    public function getCustomerEvents($customerId, $filters = []) {
        $sql = 'SELECT e.*, et.name as event_type_name, es.name as status_name
                FROM events e
                LEFT JOIN event_types et ON e.event_type_id = et.id
                LEFT JOIN event_statuses es ON e.status_id = es.id
                WHERE e.customer_id = ?';
        $params = [$customerId];
        
        // إضافة شرط الحالة إذا تم توفيره
        if (isset($filters['status_id'])) {
            $sql .= ' AND e.status_id = ?';
            $params[] = $filters['status_id'];
        }
        
        // إضافة شرط التاريخ إذا تم توفيره
        if (isset($filters['date_from'])) {
            $sql .= ' AND e.date >= ?';
            $params[] = $filters['date_from'];
        }
        
        if (isset($filters['date_to'])) {
            $sql .= ' AND e.date <= ?';
            $params[] = $filters['date_to'];
        }
        
        // إضافة الترتيب
        $sql .= ' ORDER BY e.date DESC';
        
        // تنفيذ الاستعلام
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * الحصول على إحصائيات العملاء
     * 
     * @return array
     */
    public function getCustomerStats() {
        $stats = [
            'total' => 0,
            'vip' => 0,
            'regular' => 0,
            'new' => 0,
            'by_source' => [],
            'recent' => []
        ];
        
        // إجمالي عدد العملاء
        $stats['total'] = $this->db->fetchValue('SELECT COUNT(*) FROM customers');
        
        // عدد العملاء حسب التصنيف
        $categoryCounts = $this->db->fetchAll('SELECT category, COUNT(*) as count FROM customers GROUP BY category');
        foreach ($categoryCounts as $row) {
            $stats[$row['category']] = (int) $row['count'];
        }
        
        // عدد العملاء حسب المصدر
        $sourceCounts = $this->db->fetchAll('
            SELECT cs.name, COUNT(c.id) as count
            FROM customers c
            LEFT JOIN customer_sources cs ON c.source_id = cs.id
            GROUP BY c.source_id
            ORDER BY count DESC
        ');
        $stats['by_source'] = $sourceCounts;
        
        // آخر العملاء المضافين
        $stats['recent'] = $this->db->fetchAll('
            SELECT id, name, phone, created_at
            FROM customers
            ORDER BY created_at DESC
            LIMIT 5
        ');
        
        return $stats;
    }
    
    /**
