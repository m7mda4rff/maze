<?php
/**
 * فئة المستخدم
 * 
 * تستخدم لإدارة المستخدمين والتحقق من صلاحياتهم
 */

// منع الوصول المباشر للملف
if (!defined('BASEPATH')) {
    exit('لا يمكن الوصول المباشر لهذا الملف');
}

class User {
    /**
     * معرف المستخدم
     * @var int
     */
    private $id;
    
    /**
     * اسم المستخدم
     * @var string
     */
    private $username;
    
    /**
     * الاسم الكامل للمستخدم
     * @var string
     */
    private $name;
    
    /**
     * البريد الإلكتروني للمستخدم
     * @var string
     */
    private $email;
    
    /**
     * دور المستخدم (مدير، مشرف، موظف)
     * @var string
     */
    private $role;
    
    /**
     * كائن قاعدة البيانات
     * @var Database
     */
    private $db;
    
    /**
     * المستخدم المسجل دخوله حالياً (نمط Singleton)
     * @var User
     */
    private static $currentUser = null;
    
    /**
     * إنشاء كائن جديد للمستخدم
     */
    public function __construct() {
        $this->db = Database::getInstance();
    }
    
    /**
     * الحصول على معلومات المستخدم الحالي المسجل دخوله
     * 
     * @return User|null
     */
    public static function getCurrentUser() {
        // التحقق من وجود جلسة نشطة
        if (!isset($_SESSION[SESSION_NAME]) || empty($_SESSION[SESSION_NAME]['user_id'])) {
            return null;
        }
        
        // إنشاء الكائن إذا لم يكن موجوداً
        if (self::$currentUser === null) {
            self::$currentUser = new self();
            self::$currentUser->loadUserById($_SESSION[SESSION_NAME]['user_id']);
        }
        
        return self::$currentUser;
    }
    
    /**
     * تحميل معلومات المستخدم بواسطة المعرف
     * 
     * @param int $userId معرف المستخدم
     * @return bool
     */
    public function loadUserById($userId) {
        $user = $this->db->fetchOne('SELECT * FROM users WHERE id = ?', [$userId]);
        
        if (!$user) {
            return false;
        }
        
        $this->id = $user['id'];
        $this->username = $user['username'];
        $this->name = $user['name'];
        $this->email = $user['email'];
        $this->role = $user['role'];
        
        return true;
    }
    
    /**
     * تحميل معلومات المستخدم بواسطة اسم المستخدم
     * 
     * @param string $username اسم المستخدم
     * @return bool
     */
    public function loadUserByUsername($username) {
        $user = $this->db->fetchOne('SELECT * FROM users WHERE username = ?', [$username]);
        
        if (!$user) {
            return false;
        }
        
        $this->id = $user['id'];
        $this->username = $user['username'];
        $this->name = $user['name'];
        $this->email = $user['email'];
        $this->role = $user['role'];
        
        return true;
    }
    
    /**
     * التحقق من كلمة المرور وتسجيل الدخول
     * 
     * @param string $username اسم المستخدم
     * @param string $password كلمة المرور
     * @return bool
     */
    public function login($username, $password) {
        // تحميل معلومات المستخدم
        if (!$this->loadUserByUsername($username)) {
            return false;
        }
        
        // الحصول على كلمة المرور المخزنة
        $hashedPassword = $this->db->fetchValue('SELECT password FROM users WHERE id = ?', [$this->id]);
        
        // التحقق من كلمة المرور
        if (!password_verify($password, $hashedPassword)) {
            return false;
        }
        
        // تسجيل معلومات المستخدم في الجلسة
        $_SESSION[SESSION_NAME]['user_id'] = $this->id;
        $_SESSION[SESSION_NAME]['username'] = $this->username;
        $_SESSION[SESSION_NAME]['name'] = $this->name;
        $_SESSION[SESSION_NAME]['role'] = $this->role;
        $_SESSION[SESSION_NAME]['login_time'] = time();
        
        // تعيين المستخدم الحالي
        self::$currentUser = $this;
        
        // تسجيل تاريخ آخر تسجيل دخول
        $this->updateLastLogin();
        
        return true;
    }
    
    /**
     * تسجيل الخروج
     * 
     * @return void
     */
    public function logout() {
        // إزالة معلومات المستخدم من الجلسة
        unset($_SESSION[SESSION_NAME]);
        
        // إعادة تهيئة مصفوفة الجلسة
        $_SESSION[SESSION_NAME] = [];
        
        // تعيين المستخدم الحالي كفارغ
        self::$currentUser = null;
    }
    
    /**
     * إنشاء مستخدم جديد
     * 
     * @param array $userData بيانات المستخدم (اسم المستخدم، كلمة المرور، الاسم، البريد الإلكتروني، الدور)
     * @return int|false معرف المستخدم الجديد أو false في حالة الفشل
     */
    public function create($userData) {
        // التحقق من وجود اسم المستخدم
        if ($this->db->exists('users', 'username = ?', [$userData['username']])) {
            return false;
        }
        
        // تشفير كلمة المرور
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT);
        
        // تحضير بيانات الإدراج
        $data = [
            'username' => $userData['username'],
            'password' => $hashedPassword,
            'name' => $userData['name'],
            'email' => $userData['email'],
            'role' => isset($userData['role']) ? $userData['role'] : 'staff',
        ];
        
        // إدراج المستخدم في قاعدة البيانات
        return $this->db->insert('users', $data);
    }
    
    /**
     * تحديث معلومات المستخدم
     * 
     * @param int $userId معرف المستخدم
     * @param array $userData بيانات المستخدم للتحديث
     * @return int عدد الصفوف المتأثرة
     */
    public function update($userId, $userData) {
        // إنشاء مصفوفة البيانات للتحديث
        $data = [];
        
        // تحديث الاسم إذا تم توفيره
        if (isset($userData['name'])) {
            $data['name'] = $userData['name'];
        }
        
        // تحديث البريد الإلكتروني إذا تم توفيره
        if (isset($userData['email'])) {
            $data['email'] = $userData['email'];
        }
        
        // تحديث الدور إذا تم توفيره
        if (isset($userData['role'])) {
            $data['role'] = $userData['role'];
        }
        
        // تحديث كلمة المرور إذا تم توفيرها
        if (isset($userData['password']) && !empty($userData['password'])) {
            $data['password'] = password_hash($userData['password'], PASSWORD_DEFAULT);
        }
        
        // التحقق من وجود بيانات للتحديث
        if (empty($data)) {
            return 0;
        }
        
        // تحديث البيانات في قاعدة البيانات
        return $this->db->update('users', $data, 'id = ?', [$userId]);
    }
    
    /**
     * تحديث تاريخ آخر تسجيل دخول
     * 
     * @return int
     */
    private function updateLastLogin() {
        return $this->db->query('UPDATE users SET last_login = NOW() WHERE id = ?', [$this->id])->getRowCount();
    }
    
    /**
     * التحقق من صلاحية المستخدم
     * 
     * @param string|array $allowedRoles الأدوار المسموح بها
     * @return bool
     */
    public function hasPermission($allowedRoles) {
        // التحول إلى مصفوفة إذا كانت نصاً
        if (!is_array($allowedRoles)) {
            $allowedRoles = [$allowedRoles];
        }
        
        // التحقق مما إذا كان دور المستخدم مسموحاً به
        return in_array($this->role, $allowedRoles);
    }
    
    /**
     * الحصول على قائمة المستخدمين
     * 
     * @param array $filters مرشحات للبحث
     * @param int $limit عدد النتائج
     * @param int $offset البداية
     * @return array
     */
    public function getUsers($filters = [], $limit = 0, $offset = 0) {
        $sql = 'SELECT id, username, name, email, role, created_at, updated_at FROM users';
        $params = [];
        
        // إضافة الشروط إذا وجدت
        if (!empty($filters)) {
            $sql .= ' WHERE';
            
            if (isset($filters['role'])) {
                $sql .= ' role = ?';
                $params[] = $filters['role'];
            }
            
            if (isset($filters['search'])) {
                $sql .= (empty($params) ? '' : ' AND') . ' (username LIKE ? OR name LIKE ? OR email LIKE ?)';
                $searchTerm = '%' . $filters['search'] . '%';
                $params[] = $searchTerm;
                $params[] = $searchTerm;
                $params[] = $searchTerm;
            }
        }
        
        // إضافة الترتيب
        $sql .= ' ORDER BY name ASC';
        
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
     * حذف مستخدم
     * 
     * @param int $userId معرف المستخدم
     * @return int عدد الصفوف المتأثرة
     */
    public function delete($userId) {
        return $this->db->delete('users', 'id = ?', [$userId]);
    }
    
    /**
     * الحصول على معرف المستخدم
     * 
     * @return int
     */
    public function getId() {
        return $this->id;
    }
    
    /**
     * الحصول على اسم المستخدم
     * 
     * @return string
     */
    public function getUsername() {
        return $this->username;
    }
    
    /**
     * الحصول على الاسم الكامل للمستخدم
     * 
     * @return string
     */
    public function getName() {
        return $this->name;
    }
    
    /**
     * الحصول على البريد الإلكتروني للمستخدم
     * 
     * @return string
     */
    public function getEmail() {
        return $this->email;
    }
    
    /**
     * الحصول على دور المستخدم
     * 
     * @return string
     */
    public function getRole() {
        return $this->role;
    }
    
    /**
     * التحقق مما إذا كان المستخدم مديراً
     * 
     * @return bool
     */
    public function isAdmin() {
        return $this->role === 'admin';
    }
    
    /**
     * التحقق مما إذا كان المستخدم مشرفاً
     * 
     * @return bool
     */
    public function isManager() {
        return $this->role === 'manager' || $this->role === 'admin';
    }
    
    /**
     * التحقق مما إذا كان المستخدم مسجل الدخول
     * 
     * @return bool
     */
    public function isLoggedIn() {
        return $this->id !== null;
    }
}
