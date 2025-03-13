<?php
/**
 * فئة المهام
 * 
 * تستخدم لإدارة المهام المرتبطة بالفعاليات
 */

// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    exit('لا يمكن الوصول المباشر لهذا الملف');
}

class Task {
    /**
     * معرف المهمة
     * @var int
     */
    private $id;
    
    /**
     * معرف الفعالية
     * @var int
     */
    private $event_id;
    
    /**
     * عنوان المهمة
     * @var string
     */
    private $title;
    
    /**
     * وصف المهمة
     * @var string
     */
    private $description;
    
    /**
     * معرف المستخدم المسؤول عن المهمة
     * @var int
     */
    private $assigned_to;
    
    /**
     * تاريخ استحقاق المهمة
     * @var string
     */
    private $due_date;
    
    /**
     * حالة المهمة
     * @var string
     */
    private $status;
    
    /**
     * أولوية المهمة
     * @var string
     */
    private $priority;
    
    /**
     * معرف منشئ المهمة
     * @var int
     */
    private $created_by;
    
    /**
     * كائن قاعدة البيانات
     * @var Database
     */
    private $db;
    
    /**
     * إنشاء كائن جديد للمهام
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * تحميل معلومات المهمة بواسطة المعرف
     * 
     * @param int $taskId معرف المهمة
     * @return bool
     */
    public function loadById($taskId) {
        $task = $this->db->fetchOne('SELECT * FROM tasks WHERE id = ?', [$taskId]);
        
        if (!$task) {
            return false;
        }
        
        $this->setProperties($task);
        
        return true;
    }
    
    /**
     * تعيين خصائص المهمة من مصفوفة
     * 
     * @param array $data مصفوفة بيانات المهمة
     * @return void
     */
    private function setProperties($data) {
        $this->id = $data['id'];
        $this->event_id = $data['event_id'];
        $this->title = $data['title'];
        $this->description = $data['description'];
        $this->assigned_to = $data['assigned_to'];
        $this->due_date = $data['due_date'];
        $this->status = $data['status'];
        $this->priority = $data['priority'];
        $this->created_by = $data['created_by'];
    }
    
    /**
     * إنشاء مهمة جديدة
     * 
     * @param array $taskData بيانات المهمة
     * @return int|false معرف المهمة الجديدة أو false في حالة الفشل
     */
    public function create($taskData) {
        // التحقق من وجود الفعالية
        if (!$this->db->exists('events', 'id = ?', [$taskData['event_id']])) {
            return false;
        }
        
        // تحضير بيانات المهمة للإدراج
        $data = [
            'event_id' => $taskData['event_id'],
            'title' => $taskData['title'],
            'status' => isset($taskData['status']) ? $taskData['status'] : 'open'
        ];
        
        // إضافة الحقول الاختيارية إذا كانت موجودة
        $optionalFields = [
            'description', 'assigned_to', 'due_date', 'priority', 'created_by'
        ];
        
        foreach ($optionalFields as $field) {
            if (isset($taskData[$field])) {
                $data[$field] = $taskData[$field];
            }
        }
        
        // إدراج المهمة في قاعدة البيانات
        $taskId = $this->db->insert('tasks', $data);
        
        if ($taskId) {
            // تحميل بيانات المهمة بعد الإدراج
            $this->loadById($taskId);
            
            // إرسال إشعار للمستخدم المسؤول إذا كان محدداً (يمكن تنفيذه لاحقاً)
        }
        
        return $taskId;
    }
    
    /**
     * تحديث معلومات المهمة
     * 
     * @param int $taskId معرف المهمة
     * @param array $taskData بيانات المهمة للتحديث
     * @return int عدد الصفوف المتأثرة
     */
    public function update($taskId, $taskData) {
        // التحقق من وجود المهمة
        if (!$this->db->exists('tasks', 'id = ?', [$taskId])) {
            return 0;
        }
        
        // تحضير بيانات التحديث
        $data = [];
        
        // تحديث الحقول المقدمة
        $fields = [
            'title', 'description', 'assigned_to', 'due_date', 'status', 'priority'
        ];
        
        foreach ($fields as $field) {
            if (isset($taskData[$field])) {
                $data[$field] = $taskData[$field];
            }
        }
        
        // التحقق من وجود بيانات للتحديث
        if (empty($data)) {
            return 0;
        }
        
        // تحديث تاريخ آخر تحديث
        $data['updated_at'] = date('Y-m-d H:i:s');
        
        // تحديث البيانات في قاعدة البيانات
        $result = $this->db->update('tasks', $data, 'id = ?', [$taskId]);
        
        // تحديث الخصائص المحلية إذا تم تحديث نفس المهمة المحملة
        if ($result && $this->id == $taskId) {
            foreach ($data as $key => $value) {
                if (property_exists($this, $key)) {
                    $this->$key = $value;
                }
            }
        }
        
        // إذا تم تغيير حالة المهمة إلى "مكتملة"، تحديث تاريخ الإكمال
        if (isset($data['status']) && $data['status'] === 'completed') {
            $this->db->update('tasks', ['completed_at' => date('Y-m-d H:i:s')], 'id = ?', [$taskId]);
        }
        
        return $result;
    }
    
    /**
     * تغيير حالة المهمة
     * 
     * @param int $taskId معرف المهمة
     * @param string $status الحالة الجديدة
     * @return int عدد الصفوف المتأثرة
     */
    public function changeStatus($taskId, $status) {
        // التحقق من صحة الحالة
        $validStatuses = ['open', 'in_progress', 'completed', 'overdue'];
        if (!in_array($status, $validStatuses)) {
            return 0;
        }
        
        $data = ['status' => $status, 'updated_at' => date('Y-m-d H:i:s')];
        
        // إذا كانت الحالة "مكتملة"، تحديث تاريخ الإكمال
        if ($status === 'completed') {
            $data['completed_at'] = date('Y-m-d H:i:s');
        }
        
        return $this->db->update('tasks', $data, 'id = ?', [$taskId]);
    }
    
    /**
     * تعيين مهمة لمستخدم
     * 
     * @param int $taskId معرف المهمة
     * @param int $userId معرف المستخدم
     * @return int عدد الصفوف المتأثرة
     */
    public function assignToUser($taskId, $userId) {
        // التحقق من وجود المستخدم
        if (!$this->db->exists('users', 'id = ?', [$userId])) {
            return 0;
        }
        
        return $this->db->update('tasks', 
            ['assigned_to' => $userId, 'updated_at' => date('Y-m-d H:i:s')], 
            'id = ?', 
            [$taskId]
        );
    }
    
    /**
     * حذف مهمة
     * 
     * @param int $taskId معرف المهمة
     * @return int عدد الصفوف المتأثرة
     */
    public function delete($taskId) {
        return $this->db->delete('tasks', 'id = ?', [$taskId]);
    }
    
    /**
     * الحصول على قائمة المهام
     * 
     * @param array $filters مرشحات للبحث
     * @param int $limit عدد النتائج
     * @param int $offset البداية
     * @return array
     */
    public function getTasks($filters = [], $limit = 0, $offset = 0) {
        $sql = 'SELECT t.*, e.title as event_title, e.date as event_date, 
                c.name as customer_name, u.name as assigned_to_name, u2.name as created_by_name
                FROM tasks t
                LEFT JOIN events e ON t.event_id = e.id
                LEFT JOIN customers c ON e.customer_id = c.id
                LEFT JOIN users u ON t.assigned_to = u.id
                LEFT JOIN users u2 ON t.created_by = u2.id';
        $params = [];
        
        // إضافة الشروط إذا وجدت
        if (!empty($filters)) {
            $sql .= ' WHERE';
            $whereAdded = false;
            
            if (isset($filters['event_id'])) {
                $sql .= ' t.event_id = ?';
                $params[] = $filters['event_id'];
                $whereAdded = true;
            }
            
            if (isset($filters['assigned_to'])) {
                $sql .= ($whereAdded ? ' AND' : '') . ' t.assigned_to = ?';
                $params[] = $filters['assigned_to'];
                $whereAdded = true;
            }
            
            if (isset($filters['status'])) {
                $sql .= ($whereAdded ? ' AND' : '') . ' t.status = ?';
                $params[] = $filters['status'];
                $whereAdded = true;
            }
            
            if (isset($filters['priority'])) {
                $sql .= ($whereAdded ? ' AND' : '') . ' t.priority = ?';
                $params[] = $filters['priority'];
                $whereAdded = true;
            }
            
            if (isset($filters['due_date_from'])) {
                $sql .= ($whereAdded ? ' AND' : '') . ' t.due_date >= ?';
                $params[] = $filters['due_date_from'];
                $whereAdded = true;
            }
            
            if (isset($filters['due_date_to'])) {
                $sql .= ($whereAdded ? ' AND' : '') . ' t.due_date <= ?';
                $params[] = $filters['due_date_to'];
                $whereAdded = true;
            }
            
            if (isset($filters['search'])) {
                $sql .= ($whereAdded ? ' AND' : '') . ' (t.title LIKE ? OR t.description LIKE ? OR e.title LIKE ?)';
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
        }
        
        // إضافة الترتيب
        if (isset($filters['order_by']) && isset($filters['order_direction'])) {
            $sql .= ' ORDER BY ' . $filters['order_by'] . ' ' . $filters['order_direction'];
        } else {
            // ترتيب افتراضي: حسب تاريخ الاستحقاق (المهام المتأخرة أولاً) ثم حسب الأولوية
            $sql .= ' ORDER BY 
                CASE 
                    WHEN t.status = "overdue" THEN 1 
                    WHEN t.due_date < CURDATE() AND t.status != "completed" THEN 2
                    ELSE 3 
                END,
                t.due_date ASC,
                CASE 
                    WHEN t.priority = "high" THEN 1
                    WHEN t.priority = "medium" THEN 2
                    ELSE 3
                END';
        }
        
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
     * الحصول على المهام المتأخرة
     * 
     * @param int $assignedTo معرف المستخدم المسؤول، اختياري
     * @return array
     */
    public function getOverdueTasks($assignedTo = null) {
        $sql = 'SELECT t.*, e.title as event_title, e.date as event_date, 
                c.name as customer_name, u.name as assigned_to_name
                FROM tasks t
                LEFT JOIN events e ON t.event_id = e.id
                LEFT JOIN customers c ON e.customer_id = c.id
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.due_date < CURDATE() AND t.status != "completed"';
        $params = [];
        
        if ($assignedTo) {
            $sql .= ' AND t.assigned_to = ?';
            $params[] = $assignedTo;
        }
        
        $sql .= ' ORDER BY t.due_date ASC, t.priority ASC';
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * الحصول على المهام القادمة
     * 
     * @param int $days عدد الأيام
     * @param int $assignedTo معرف المستخدم المسؤول، اختياري
     * @return array
     */
    public function getUpcomingTasks($days = 7, $assignedTo = null) {
        $today = date('Y-m-d');
        $futureDate = date('Y-m-d', strtotime("+{$days} days"));
        
        $sql = 'SELECT t.*, e.title as event_title, e.date as event_date, 
                c.name as customer_name, u.name as assigned_to_name
                FROM tasks t
                LEFT JOIN events e ON t.event_id = e.id
                LEFT JOIN customers c ON e.customer_id = c.id
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.due_date BETWEEN ? AND ? AND t.status != "completed"';
        $params = [$today, $futureDate];
        
        if ($assignedTo) {
            $sql .= ' AND t.assigned_to = ?';
            $params[] = $assignedTo;
        }
        
        $sql .= ' ORDER BY t.due_date ASC, t.priority ASC';
        
        return $this->db->fetchAll($sql, $params);
    }
    
    /**
     * تحديث حالة المهام المتأخرة
     * 
     * @return int عدد الصفوف المتأثرة
     */
    public function updateOverdueTasks() {
        $today = date('Y-m-d');
        
        // تحديث حالة المهام التي تجاوزت تاريخ الاستحقاق ولم تكتمل
        return $this->db->update(
            'tasks', 
            ['status' => 'overdue', 'updated_at' => date('Y-m-d H:i:s')], 
            'due_date < ? AND status != "completed"', 
            [$today]
        );
    }
    
    /**
     * تحضير قائمة المهام ليوم محدد
     * 
     * @param string $date التاريخ، اختياري
     * @return array
     */
    public function getDailyTaskList($date = null) {
        if (!$date) {
            $date = date('Y-m-d');
        }
        
        // الحصول على المهام المستحقة في هذا اليوم
        $sql = 'SELECT t.*, e.title as event_title, e.date as event_date, 
                c.name as customer_name, u.name as assigned_to_name
                FROM tasks t
                LEFT JOIN events e ON t.event_id = e.id
                LEFT JOIN customers c ON e.customer_id = c.id
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.due_date = ? AND t.status != "completed"
                ORDER BY t.priority ASC, t.id ASC';
        
        $dueTasks = $this->db->fetchAll($sql, [$date]);
        
        // الحصول على المهام المتأخرة
        $sql = 'SELECT t.*, e.title as event_title, e.date as event_date, 
                c.name as customer_name, u.name as assigned_to_name
                FROM tasks t
                LEFT JOIN events e ON t.event_id = e.id
                LEFT JOIN customers c ON e.customer_id = c.id
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.due_date < ? AND t.status != "completed"
                ORDER BY t.due_date ASC, t.priority ASC';
        
        $overdueTasks = $this->db->fetchAll($sql, [$date]);
        
        // الحصول على المهام المرتبطة بفعاليات اليوم
        $sql = 'SELECT t.*, e.title as event_title, e.date as event_date, 
                c.name as customer_name, u.name as assigned_to_name
                FROM tasks t
                LEFT JOIN events e ON t.event_id = e.id
                LEFT JOIN customers c ON e.customer_id = c.id
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE e.date = ? AND t.status != "completed" AND t.due_date != ?
                ORDER BY t.priority ASC, t.id ASC';
        
        $eventTasks = $this->db->fetchAll($sql, [$date, $date]);
        
        return [
            'due_tasks' => $dueTasks,
            'overdue_tasks' => $overdueTasks,
            'event_tasks' => $eventTasks
        ];
    }
    
    /**
     * الحصول على إحصائيات المهام
     * 
     * @param int $userId معرف المستخدم، اختياري
     * @return array
     */
    public function getTaskStats($userId = null) {
        $stats = [
            'total' => 0,
            'open' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'overdue' => 0,
            'by_priority' => [],
            'by_user' => [],
            'upcoming' => []
        ];
        
        // إضافة شرط المستخدم إذا كان محدداً
        $userCondition = '';
        $params = [];
        
        if ($userId) {
            $userCondition = ' WHERE t.assigned_to = ?';
            $params = [$userId];
        }
        
        // إجمالي عدد المهام
        $stats['total'] = $this->db->fetchValue('SELECT COUNT(*) FROM tasks t' . $userCondition, $params);
        
        // عدد المهام حسب الحالة
        $statusCountsParams = $params;
        $statusCounts = $this->db->fetchAll('
            SELECT t.status, COUNT(*) as count
            FROM tasks t
            ' . $userCondition . '
            GROUP BY t.status
        ', $statusCountsParams);
        
        foreach ($statusCounts as $row) {
            $stats[$row['status']] = (int) $row['count'];
        }
        
        // عدد المهام حسب الأولوية
        $priorityCountsParams = $params;
        $stats['by_priority'] = $this->db->fetchAll('
            SELECT t.priority, COUNT(*) as count
            FROM tasks t
            ' . $userCondition . '
            GROUP BY t.priority
            ORDER BY 
            CASE 
                WHEN t.priority = "high" THEN 1
                WHEN t.priority = "medium" THEN 2
                ELSE 3
            END
        ', $priorityCountsParams);
        
        // عدد المهام حسب المستخدم
        if (!$userId) {
            $stats['by_user'] = $this->db->fetchAll('
                SELECT u.name, COUNT(t.id) as count
                FROM tasks t
                LEFT JOIN users u ON t.assigned_to = u.id
                GROUP BY t.assigned_to
                ORDER BY count DESC
            ');
        }
        
        // المهام القادمة
        $today = date('Y-m-d');
        $sevenDaysLater = date('Y-m-d', strtotime("+7 days"));
        
        $upcomingParams = $params ? array_merge([$today, $sevenDaysLater], $params) : [$today, $sevenDaysLater];
        $upcomingCondition = $userCondition ? $userCondition . ' AND' : ' WHERE';
        
        $stats['upcoming'] = $this->db->fetchAll('
            SELECT t.*, e.title as event_title, e.date as event_date, u.name as assigned_to_name
            FROM tasks t
            LEFT JOIN events e ON t.event_id = e.id
            LEFT JOIN users u ON t.assigned_to = u.id
            ' . $upcomingCondition . ' t.due_date BETWEEN ? AND ? AND t.status != "completed"
            ORDER BY t.due_date ASC, t.priority ASC
            LIMIT 10
        ', $upcomingParams);
        
        return $stats;
    }
    
    /**
     * الحصول على معرف المهمة
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
     * الحصول على عنوان المهمة
     * 
     * @return string
     */
    public function getTitle() {
        return $this->title;
    }
    
    /**
     * الحصول على وصف المهمة
     * 
     * @return string
     */
    public function getDescription() {
        return $this->description;
    }
    
    /**
     * الحصول على معرف المستخدم المسؤول
     * 
     * @return int
     */
    public function getAssignedTo() {
        return $this->assigned_to;
    }
    
    /**
     * الحصول على اسم المستخدم المسؤول
     * 
     * @return string
     */
    public function getAssignedToName() {
        if (!$this->assigned_to) {
            return '';
        }
        
        return $this->db->fetchValue('SELECT name FROM users WHERE id = ?', [$this->assigned_to]);
    }
    
    /**
     * الحصول على تاريخ استحقاق المهمة
     * 
     * @param string $format صيغة التاريخ، اختيارية
     * @return string
     */
    public function getDueDate($format = null) {
        if ($format && $this->due_date) {
            return date($format, strtotime($this->due_date));
        }
        return $this->due_date;
    }
    
    /**
     * الحصول على حالة المهمة
     * 
     * @return string
     */
    public function getStatus() {
        return $this->status;
    }
    
    /**
     * الحصول على أولوية المهمة
     * 
     * @return string
     */
    public function getPriority() {
        return $this->priority;
    }
    
    /**
     * الحصول على معرف منشئ المهمة
     * 
     * @return int
     */
    public function getCreatedBy() {
        return $this->created_by;
    }
    
    /**
     * التحقق مما إذا كانت المهمة متأخرة
     * 
     * @return bool
     */
    public function isOverdue() {
        if ($this->status === 'completed') {
            return false;
        }
        
        return $this->due_date && strtotime($this->due_date) < strtotime(date('Y-m-d'));
    }
    
    /**
     * الحصول على عنوان الفعالية المرتبطة بالمهمة
     * 
     * @return string
     */
    public function getEventTitle() {
        return $this->db->fetchValue('SELECT title FROM events WHERE id = ?', [$this->event_id]);
    }
}
