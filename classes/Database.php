<?php
/**
 * فئة قاعدة البيانات
 * 
 * تستخدم للاتصال بقاعدة البيانات وتنفيذ الاستعلامات
 */

// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    exit('لا يمكن الوصول المباشر لهذا الملف');
}

class Database {
    /**
     * رابط قاعدة البيانات PDO
     * @var PDO
     */
    private $db;
    
    /**
     * كائن استعلام PDO Statement
     * @var PDOStatement
     */
    private $statement;
    
    /**
     * كائن Database - نمط Singleton
     * @var Database
     */
    private static $instance;
    
    /**
     * عدد الصفوف المتأثرة بآخر استعلام
     * @var int
     */
    private $rowCount = 0;
    
    /**
     * آخر معرف تم إدراجه
     * @var int
     */
    private $lastInsertId = 0;

    /**
     * إنشاء اتصال بقاعدة البيانات
     */
    private function __construct() {
        try {
            // استيراد إعدادات قاعدة البيانات
            $db_config = require CONFIG_PATH . '/database.php';
            
            // إنشاء سلسلة الاتصال DSN
            $dsn = "mysql:host={$db_config['host']};dbname={$db_config['db_name']};charset={$db_config['charset']}";
            
            // إنشاء اتصال PDO جديد
            $this->db = new PDO($dsn, $db_config['username'], $db_config['password'], $db_config['options']);
            
        } catch (PDOException $e) {
            // تسجيل الخطأ وعرض رسالة خطأ
            $this->logError($e);
            exit('فشل الاتصال بقاعدة البيانات: ' . $e->getMessage());
        }
    }
    
    /**
     * الحصول على كائن Database (نمط Singleton)
     * 
     * @return Database
     */
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        
        return self::$instance;
    }
    
    /**
     * تنفيذ استعلام SQL
     * 
     * @param string $sql استعلام SQL
     * @param array $params معلمات مُعدة للاستعلام
     * @return Database
     */
    public function query($sql, $params = []) {
        try {
            // تحضير الاستعلام
            $this->statement = $this->db->prepare($sql);
            
            // تنفيذ الاستعلام مع المعلمات
            $this->statement->execute($params);
            
            // تعيين عدد الصفوف المتأثرة
            $this->rowCount = $this->statement->rowCount();
            
            // تعيين آخر معرف تم إدراجه (إذا كان استعلام INSERT)
            if (stripos($sql, 'INSERT') === 0) {
                $this->lastInsertId = $this->db->lastInsertId();
            }
            
            return $this;
            
        } catch (PDOException $e) {
            // تسجيل الخطأ وإعادة توجيهه
            $this->logError($e);
            throw $e;
        }
    }
    
    /**
     * تنفيذ استعلام وإرجاع سجل واحد
     * 
     * @param string $sql استعلام SQL
     * @param array $params معلمات مُعدة للاستعلام
     * @return array|false
     */
    public function fetchOne($sql, $params = []) {
        $this->query($sql, $params);
        return $this->statement->fetch();
    }
    
    /**
     * تنفيذ استعلام وإرجاع جميع السجلات
     * 
     * @param string $sql استعلام SQL
     * @param array $params معلمات مُعدة للاستعلام
     * @return array
     */
    public function fetchAll($sql, $params = []) {
        $this->query($sql, $params);
        return $this->statement->fetchAll();
    }
    
    /**
     * تنفيذ استعلام وإرجاع قيمة واحدة (العمود الأول من الصف الأول)
     * 
     * @param string $sql استعلام SQL
     * @param array $params معلمات مُعدة للاستعلام
     * @return mixed
     */
    public function fetchValue($sql, $params = []) {
        $this->query($sql, $params);
        return $this->statement->fetchColumn();
    }
    
    /**
     * إدراج سجل في جدول
     * 
     * @param string $table اسم الجدول
     * @param array $data البيانات للإدراج (مصفوفة اقترانية)
     * @return int معرف السجل المدرج
     */
    public function insert($table, $data) {
        // تحضير أسماء الأعمدة وعلامات الاستفهام
        $columns = implode(', ', array_keys($data));
        $placeholders = implode(', ', array_fill(0, count($data), '?'));
        
        // بناء استعلام الإدراج
        $sql = "INSERT INTO {$table} ({$columns}) VALUES ({$placeholders})";
        
        // تنفيذ الاستعلام مع القيم
        $this->query($sql, array_values($data));
        
        // إرجاع معرف السجل المدرج
        return $this->lastInsertId;
    }
    
    /**
     * تحديث سجل أو أكثر في جدول
     * 
     * @param string $table اسم الجدول
     * @param array $data البيانات للتحديث (مصفوفة اقترانية)
     * @param string $where شرط WHERE (بدون كلمة WHERE)
     * @param array $params معلمات للشرط
     * @return int عدد الصفوف المتأثرة
     */
    public function update($table, $data, $where, $params = []) {
        // تحضير عبارات SET
        $set = [];
        foreach ($data as $column => $value) {
            $set[] = "{$column} = ?";
        }
        $setStr = implode(', ', $set);
        
        // بناء استعلام التحديث
        $sql = "UPDATE {$table} SET {$setStr} WHERE {$where}";
        
        // دمج قيم البيانات ومعلمات الشرط
        $allParams = array_merge(array_values($data), $params);
        
        // تنفيذ الاستعلام
        $this->query($sql, $allParams);
        
        // إرجاع عدد الصفوف المتأثرة
        return $this->rowCount;
    }
    
    /**
     * حذف سجل أو أكثر من جدول
     * 
     * @param string $table اسم الجدول
     * @param string $where شرط WHERE (بدون كلمة WHERE)
     * @param array $params معلمات للشرط
     * @return int عدد الصفوف المتأثرة
     */
    public function delete($table, $where, $params = []) {
        // بناء استعلام الحذف
        $sql = "DELETE FROM {$table} WHERE {$where}";
        
        // تنفيذ الاستعلام
        $this->query($sql, $params);
        
        // إرجاع عدد الصفوف المتأثرة
        return $this->rowCount;
    }
    
    /**
     * التحقق من وجود سجل
     * 
     * @param string $table اسم الجدول
     * @param string $where شرط WHERE (بدون كلمة WHERE)
     * @param array $params معلمات للشرط
     * @return bool
     */
    public function exists($table, $where, $params = []) {
        $sql = "SELECT 1 FROM {$table} WHERE {$where} LIMIT 1";
        $result = $this->fetchValue($sql, $params);
        return ($result !== false);
    }
    
    /**
     * الحصول على عدد السجلات
     * 
     * @param string $table اسم الجدول
     * @param string $where شرط WHERE (بدون كلمة WHERE)، اختياري
     * @param array $params معلمات للشرط
     * @return int
     */
    public function count($table, $where = '', $params = []) {
        $sql = "SELECT COUNT(*) FROM {$table}";
        
        if (!empty($where)) {
            $sql .= " WHERE {$where}";
        }
        
        return (int) $this->fetchValue($sql, $params);
    }
    
    /**
     * بدء معاملة (Transaction)
     * 
     * @return bool
     */
    public function beginTransaction() {
        return $this->db->beginTransaction();
    }
    
    /**
     * تأكيد المعاملة (Commit)
     * 
     * @return bool
     */
    public function commit() {
        return $this->db->commit();
    }
    
    /**
     * التراجع عن المعاملة (Rollback)
     * 
     * @return bool
     */
    public function rollback() {
        return $this->db->rollBack();
    }
    
    /**
     * الحصول على آخر معرف تم إدراجه
     * 
     * @return int
     */
    public function getLastInsertId() {
        return $this->lastInsertId;
    }
    
    /**
     * الحصول على عدد الصفوف المتأثرة
     * 
     * @return int
     */
    public function getRowCount() {
        return $this->rowCount;
    }
    
    /**
     * الحصول على آخر خطأ
     * 
     * @return array
     */
    public function getLastError() {
        return $this->statement ? $this->statement->errorInfo() : $this->db->errorInfo();
    }
    
    /**
     * تسجيل أخطاء قاعدة البيانات
     * 
     * @param PDOException $e كائن الاستثناء
     * @return void
     */
    private function logError(PDOException $e) {
        if (defined('ENABLE_ERROR_LOG') && ENABLE_ERROR_LOG) {
            $errorMsg = "[" . date('Y-m-d H:i:s') . "] Database Error: " . $e->getMessage();
            $errorMsg .= " in " . $e->getFile() . " on line " . $e->getLine() . PHP_EOL;
            
            // إنشاء مجلد السجلات إذا لم يكن موجوداً
            if (!file_exists(LOG_PATH)) {
                mkdir(LOG_PATH, 0755, true);
            }
            
            // كتابة الخطأ في ملف السجل
            file_put_contents(LOG_PATH . '/db_error.log', $errorMsg, FILE_APPEND);
        }
    }
    
    /**
     * منع نسخ الكائن (جزء من نمط Singleton)
     */
    private function __clone() {}
    
    /**
     * منع إعادة إنشاء الكائن من سلسلة (جزء من نمط Singleton)
     */
    private function __wakeup() {}
    
    /**
     * إغلاق الاتصال عند تدمير الكائن
     */
    public function __destruct() {
        $this->db = null;
        $this->statement = null;
    }
}
