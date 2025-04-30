<?php

class Instructor extends dbh {

    // الحصول على كل الدكاترة 
    public function getAll() {
        $sql = "SELECT id, name, password, email, phone, isAdmin FROM instructor";
        $stmt = $this->connect()->query($sql);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // الحصول على دكتور عن طريق الإيميل
    public function getByEmail($email) {
        $sql = "SELECT id, name, password, email, phone, isAdmin FROM instructor WHERE email = :email";
        $stmt = $this->connect()->prepare($sql);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_OBJ);
    }

    // التحقق هل الإيميل موجود
    public function checkEmail($email) {
        $sql = "SELECT id FROM instructor WHERE email = :email";
        $stmt = $this->connect()->prepare($sql);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // تسجيل دخول
    public function login($email, $password) {
        $sql = "SELECT email, password FROM instructor WHERE email = :email AND password = :password AND !suspended";
        $stmt = $this->connect()->prepare($sql);
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":password", $password);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // التحقق من حساب دكتور موجود ولا لا
    public function checkAccount($id) {
        $sql = "SELECT 1 FROM instructor WHERE id = :id AND !suspended";
        $stmt = $this->connect()->prepare($sql);
        $stmt->bindParam(":id", $id);
        $stmt->execute();
        return $stmt->rowCount() > 0;
    }

    // تسجيل حساب جديد
    public function register($name, $pass, $email, $phone) {
        $sql = "INSERT INTO instructor (name, password, email, phone) VALUES (:name, :pass, :email, :phone)";
        $stmt = $this->connect()->prepare($sql);
        $stmt->bindParam(":name", $name);
        $stmt->bindParam(":pass", $pass); // بدون تشفير
        $stmt->bindParam(":email", $email);
        $stmt->bindParam(":phone", $phone);
        $stmt->execute();
    }
    // 
    public function sendPassword($email) {
        $sql = "SELECT password FROM instructor WHERE email = :email";
        $stmt = $this->connect()->prepare($sql);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        $password = $stmt->fetchColumn();
    
        // تبعت الإيميل هنا
        mail($email, "Your Password", "Your password is: " . $password);
    }
    
    // تعديل كلمة المرور
    public function updatePassword($email, $password) {
        $sql = "UPDATE instructor SET password = :password WHERE email = :email";
        $stmt = $this->connect()->prepare($sql);
        $stmt->bindParam(":password", $password); // بدون تشفير
        $stmt->bindParam(":email", $email);
        $stmt->execute();
    }
    // تحديث معلومات الادمن او الدكتور 
    public function updateInfo($name,$email,$phone)
    {
     try
       {
          $stmt = $this->connect()->prepare("UPDATE instructor SET name = :name,email = :email, phone = :phone
            WHERE id = :id");
          $stmt->bindparam(":id",$_SESSION['mydata']->id);
          $stmt->bindparam(":name",$name);
          $stmt->bindparam(":email",$email);
          $stmt->bindparam(":phone",$phone);
          $stmt->execute();
          return true;
       }
     catch(PDOException $e)
       {
          echo $e->getMessage();
          return false;
       }
    }
    

    // إعادة تعيين كلمة المرور
    public function resetPassword($email, $password) {
        try {
            $sql = "UPDATE instructor 
                    SET password = :password, password_token = null, token_expire = null 
                    WHERE email = :email";
            $stmt = $this->connect()->prepare($sql);
            $stmt->bindParam(":password", $password);
            $stmt->bindParam(":email", $email);
            $stmt->execute();
            return true;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    // توليد رمز لتغيير كلمة المرور
    public function generatePasswordToken($email, $token) {
        try {
            $sql = "UPDATE instructor 
                    SET password_token = :token, token_expire = DATE_ADD(NOW(), INTERVAL 30 MINUTE) 
                    WHERE email = :email;
                    INSERT INTO mails(instructorID, sends_at, type)
                    SELECT id, CONVERT_TZ(NOW(), @@session.time_zone, '+02:00'), 1 FROM instructor WHERE email = :email";
            $stmt = $this->connect()->prepare($sql);
            $stmt->bindParam(":email", $email);
            $stmt->bindParam(":token", $token);
            $stmt->execute();
            // Logging لتأكيد تخزين الـ token
            $sql = "SELECT password_token FROM instructor WHERE email = :email";
            $stmt = $this->connect()->prepare($sql);
            $stmt->bindParam(":email", $email);
            $stmt->execute();
            $stored_token = $stmt->fetchColumn();
            error_log("Stored token for $email: $stored_token");
            return true;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    // التحقق من صحة رمز تغيير كلمة المرور
    public function isValidReset($email, $token) {
        $sql = "SELECT password_token, token_expiry FROM instructor WHERE email = :email";
        $stmt = $this->connect()->prepare($sql);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($row && $row['password_token'] === $token && $row['token_expiry'] > date('Y-m-d H:i:s')) {
            return true;
        }
        return false;
    }
}