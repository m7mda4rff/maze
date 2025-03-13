/**
     * الحصول على الملاحظات الإضافية
     * 
     * @return string
     */
    public function getAdditionalNotes() {
        return $this->additional_notes;
    }
    
    /**
     * الحصول على معرف حالة الفعالية
     * 
     * @return int
     */
    public function getStatusId() {
        return $this->status_id;
    }
    
    /**
     * الحصول على اسم حالة الفعالية
     * 
     * @return string
     */
    public function getStatusName() {
        return $this->db->fetchValue('SELECT name FROM event_statuses WHERE id = ?', [$this->status_id]);
    }
    
    /**
     * الحصول على اسم نوع الفعالية
     * 
     * @return string
     */
    public function getEventTypeName() {
        return $this->db->fetchValue('SELECT name FROM event_types WHERE id = ?', [$this->event_type_id]);
    }
    
    /**
     * الحصول على اسم العميل
     * 
     * @return string
     */
    public function getCustomerName() {
        return $this->db->fetchValue('SELECT name FROM customers WHERE id = ?', [$this->customer_id]);
    }
    
    /**
     * التحقق مما إذا كانت الفعالية قد انتهت
     * 
     * @return bool
     */
    public function isCompleted() {
        $statusName = $this->getStatusName();
        return $statusName === 'منتهية';
    }
    
    /**
     * التحقق مما إذا كانت الفعالية ملغاة
     * 
     * @return bool
     */
    public function isCancelled() {
        $statusName = $this->getStatusName();
        return $statusName === 'ملغاة';
    }
    
    /**
     * التحقق مما إذا كانت الفعالية قادمة
     * 
     * @return bool
     */
    public function isUpcoming() {
        return strtotime($this->date) >= strtotime(date('Y-m-d')) && !$this->isCancelled();
    }
    
    /**
     * الحصول على مدة الفعالية بالساعات
     * 
     * @return float|null
     */
    public function getDurationHours() {
        if (!$this->start_time || !$this->end_time) {
            return null;
        }
        
        $start = strtotime($this->start_time);
        $end = strtotime($this->end_time);
        
        // حساب الفرق بالثواني وتحويله إلى ساعات
        $durationSeconds = $end - $start;
        return $durationSeconds / 3600;
    }
    
    /**
     * الحصول على التكاليف الخارجية للفعالية
     * 
     * @return array
     */
    public function getExternalCosts() {
        $sql = 'SELECT ec.*, ct.name as cost_type_name
                FROM external_costs ec
                LEFT JOIN cost_types ct ON ec.cost_type_id = ct.id
                WHERE ec.event_id = ?
                ORDER BY ec.id ASC';
        
        return $this->db->fetchAll($sql, [$this->id]);
    }
    
    /**
     * الحصول على مدفوعات الفعالية
     * 
     * @return array
     */
    public function getPayments() {
        $sql = 'SELECT p.*, u.name as created_by_name
                FROM payments p
                LEFT JOIN users u ON p.created_by = u.id
                WHERE p.event_id = ?
                ORDER BY p.payment_date ASC, p.id ASC';
        
        return $this->db->fetchAll($sql, [$this->id]);
    }
    
    /**
     * الحصول على مهام الفعالية
     * 
     * @return array
     */
    public function getTasks() {
        $sql = 'SELECT t.*, u.name as assigned_to_name
                FROM tasks t
                LEFT JOIN users u ON t.assigned_to = u.id
                WHERE t.event_id = ?
                ORDER BY t.due_date ASC, t.priority ASC';
        
        return $this->db->fetchAll($sql, [$this->id]);
    }
    
    /**
     * إضافة تكلفة خارجية للفعالية
     * 
     * @param array $costData بيانات التكلفة
     * @return int|false
     */
    public function addExternalCost($costData) {
        // التحقق من وجود الفعالية
        if (!$this->id) {
            return false;
        }
        
        // تحضير بيانات التكلفة
        $data = [
            'event_id' => $this->id,
            'amount' => $costData['amount'],
            'description' => $costData['description']
        ];
        
        // إضافة الحقول الاختيارية
        if (isset($costData['cost_type_id'])) {
            $data['cost_type_id'] = $costData['cost_type_id'];
        }
        
        if (isset($costData['vendor'])) {
            $data['vendor'] = $costData['vendor'];
        }
        
        if (isset($costData['notes'])) {
            $data['notes'] = $costData['notes'];
        }
        
        // إدراج التكلفة
        return $this->db->insert('external_costs', $data);
    }
    
    /**
     * إضافة دفعة للفعالية
     * 
     * @param array $paymentData بيانات الدفعة
     * @return int|false
     */
    public function addPayment($paymentData) {
        // التحقق من وجود الفعالية
        if (!$this->id) {
            return false;
        }
        
        // تحضير بيانات الدفعة
        $data = [
            'event_id' => $this->id,
            'amount' => $paymentData['amount'],
            'payment_date' => $paymentData['payment_date'],
            'payment_method' => $paymentData['payment_method']
        ];
        
        // إضافة الحقول الاختيارية
        if (isset($paymentData['payment_type'])) {
            $data['payment_type'] = $paymentData['payment_type'];
        }
        
        if (isset($paymentData['notes'])) {
            $data['notes'] = $paymentData['notes'];
        }
        
        if (isset($paymentData['created_by'])) {
            $data['created_by'] = $paymentData['created_by'];
        }
        
        // إدراج الدفعة
        return $this->db->insert('payments', $data);
    }
    
    /**
     * إضافة مهمة للفعالية
     * 
     * @param array $taskData بيانات المهمة
     * @return int|false
     */
    public function addTask($taskData) {
        // التحقق من وجود الفعالية
        if (!$this->id) {
            return false;
        }
        
        // تحضير بيانات المهمة
        $data = [
            'event_id' => $this->id,
            'title' => $taskData['title']
        ];
        
        // إضافة الحقول الاختيارية
        $optionalFields = ['description', 'assigned_to', 'due_date', 'status', 'priority', 'created_by'];
        foreach ($optionalFields as $field) {
            if (isset($taskData[$field])) {
                $data[$field] = $taskData[$field];
            }
        }
        
        // إدراج المهمة
        return $this->db->insert('tasks', $data);
    }
    
    /**
     * التحقق من تعارض المواعيد
     * 
     * @param string $date التاريخ (Y-m-d)
     * @param string $startTime وقت البدء (H:i:s)
     * @param string $endTime وقت الانتهاء (H:i:s)
     * @param int $excludeEventId معرف الفعالية المستثناة (اختياري)
     * @return array|false التعارضات أو false إذا لم توجد
     */
    public function checkTimeConflicts($date, $startTime, $endTime, $excludeEventId = 0) {
        // بناء الاستعلام للتحقق من التعارض
        $sql = 'SELECT e.id, e.title, e.start_time, e.end_time, c.name as customer_name
                FROM events e
                JOIN customers c ON e.customer_id = c.id
                WHERE e.date = ?
                AND e.id != ?
                AND e.status_id IN (SELECT id FROM event_statuses WHERE name != "ملغاة")
                AND (
                    (? BETWEEN e.start_time AND e.end_time)
                    OR (? BETWEEN e.start_time AND e.end_time)
                    OR (e.start_time BETWEEN ? AND ?)
                    OR (e.end_time BETWEEN ? AND ?)
                )';
        
        $params = [
            $date,
            $excludeEventId,
            $startTime,
            $endTime,
            $startTime,
            $endTime,
            $startTime,
            $endTime
        ];
        
        $conflicts = $this->db->fetchAll($sql, $params);
        
        if (empty($conflicts)) {
            return false;
        }
        
        return $conflicts;
    }
    
    /**
     * الحصول على ربحية الفعالية
     * 
     * @return array
     */
    public function getProfitability() {
        // التحقق من وجود الفعالية
        if (!$this->id) {
            return [
                'total_revenue' => 0,
                'total_cost' => 0,
                'profit' => 0,
                'profit_margin' => 0
            ];
        }
        
        // إجمالي الإيرادات (تكلفة الباقة)
        $totalRevenue = $this->total_package_cost;
        
        // إجمالي التكاليف الخارجية
        $totalExternalCost = (float) $this->db->fetchValue('SELECT SUM(amount) FROM external_costs WHERE event_id = ?', [$this->id]);
        
        // الربح
        $profit = $totalRevenue - $totalExternalCost;
        
        // هامش الربح
        $profitMargin = $totalRevenue > 0 ? ($profit / $totalRevenue) * 100 : 0;
        
        return [
            'total_revenue' => $totalRevenue,
            'total_cost' => $totalExternalCost,
            'profit' => $profit,
            'profit_margin' => $profitMargin
        ];
    }
    
    /**
     * استرجاع إحصائيات ربحية الفعاليات
     * 
     * @param string $startDate تاريخ البداية (Y-m-d)، اختياري
     * @param string $endDate تاريخ النهاية (Y-m-d)، اختياري
     * @return array
     */
    public function getProfitabilityStats($startDate = null, $endDate = null) {
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
        
        // استعلام لحساب الإيرادات والتكاليف والأرباح
        $sql = "SELECT 
                SUM(e.total_package_cost) as total_revenue,
                COALESCE(SUM(ec_total.total_cost), 0) as total_cost,
                SUM(e.total_package_cost) - COALESCE(SUM(ec_total.total_cost), 0) as total_profit
                FROM events e
                LEFT JOIN (
                    SELECT event_id, SUM(amount) as total_cost 
                    FROM external_costs 
                    GROUP BY event_id
                ) ec_total ON e.id = ec_total.event_id
                {$dateCondition}";
        
        $result = $this->db->fetchOne($sql, $params);
        
        // حساب هامش الربح
        $profitMargin = $result['total_revenue'] > 0 ? 
            ($result['total_profit'] / $result['total_revenue']) * 100 : 0;
        
        // إضافة هامش الربح للنتائج
        $result['profit_margin'] = $profitMargin;
        
        return $result;
    }
}
