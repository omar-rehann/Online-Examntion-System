<?php
class dbh {
    // معلومات الاتصال بقاعدة البيانات
    private $host = "localhost";
     private $port = "3306";    
    private $username = "root"; 
    private $password = "";     
    private $dbName = "online_exam";   

    // دالة الاتصال بقاعدة البيانات
    public function connect() {
        try {
            // إنشاء سلسلة الاتصال
            $dsn = "mysql:host={$this->host};port={$this->port};dbname={$this->dbName};charset=utf8mb4";
            
            // إنشاء كائن PDO للاتصال
            $pdo = new PDO($dsn, $this->username, $this->password);
            
            // ضبط خيارات PDO
            $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION); // عرض الأخطاء
            $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC); // نمط جلب البيانات الافتراضي
            
            return $pdo; // إرجاع كائن الاتصال
            
        } catch (PDOException $e) {
            // في حالة حدوث خطأ، إيقاف البرنامج وعرض الرسالة
            die("   Eror Connection Database " . $e->getMessage());
        }
    }
}