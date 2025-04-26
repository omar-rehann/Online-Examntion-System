<?php

class admin extends dbh {

    // الحصول على جميع المدرسين (غير الـ Admin)
    public function getAllInstructors() {
        $db = $this->connect();
        $query = "SELECT * FROM instructor WHERE !isAdmin";
        $result = $db->query($query);
        return $result->fetchAll(PDO::FETCH_OBJ);
    }

    // الحصول على جميع الطلاب
    public function getAllStudents() {
        $db = $this->connect();
        $query = "SELECT * FROM student";
        $result = $db->query($query);
        return $result->fetchAll(PDO::FETCH_OBJ);
    }

    // الحصول على الطلاب غير المسجلين (بدون كلمة مرور)
    public function getUnregistered() {
        $db = $this->connect();
        $query = "SELECT * FROM student WHERE password IS NULL";
        $result = $db->query($query);
        return $result->fetchAll(PDO::FETCH_OBJ);
    }

    // الحصول على نتائج طالب معين
    public function getStudentResults($studentID) {
        $db = $this->connect();
        $query = "SELECT r.id, r.testID, t.name AS testName, s.name AS studentName, 
                         r.studentID, r.startTime, r.endTime,
                         (SELECT name FROM student WHERE id = r.studentID) AS student,
                         ipaddr, hostname,
                         getResultGrade(r.id) AS FinalGrade,
                         getResultMaxGrade(r.id) AS TestDegree
                  FROM result r
                  JOIN test t ON t.id = r.testID
                  JOIN student s ON s.id = r.studentID
                  WHERE r.studentID = ? AND !r.isTemp
                  GROUP BY t.id, r.id
                  ORDER BY r.endTime DESC";
        
        $stmt = $db->prepare($query);
        $stmt->execute([$studentID]);
        return $stmt->fetchAll(PDO::FETCH_OBJ);
    }

    // تعليق حساب طالب
    public function suspendStudent($studentID) {
        $db = $this->connect();
        $query = "UPDATE student SET suspended = 1, sessionID = NULL WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$studentID]);
        return true;
    }

    // تفعيل حساب طالب
    public function activateStudent($studentID) {
        $db = $this->connect();
        $query = "UPDATE student SET suspended = 0 WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$studentID]);
        return true;
    }

    // تعليق حساب مدرس
    public function suspendInstructor($instructorID) {
        $db = $this->connect();
        $query = "UPDATE instructor SET suspended = 1 WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$instructorID]);
        return true;
    }

    // تفعيل حساب دكتور
    public function activateInstructor($instructorID) {
        $db = $this->connect();
        $query = "UPDATE instructor SET suspended = 0 WHERE id = ?";
        $stmt = $db->prepare($query);
        $stmt->execute([$instructorID]);
        return true;
    }

    // استيراد طلاب
    public function importStudents($values) {
        try {
            $db = $this->connect();
            $query = "INSERT IGNORE INTO student(id, name, email, phone, password) VALUES " . $values;
            $db->exec($query);
            error_log("Imported students with values: $values");
            return true;
        } catch (Exception $e) {
            error_log("Error importing students: " . $e->getMessage());
            return false;
        }
    }

    // إضافة طالب
    public function addStudent($id, $name, $email, $phone, $password) {
        try {
            $db = $this->connect();
            $query = "INSERT INTO student(id, name, email, phone, password) VALUES (?, ?, ?, ?, ?)";
            $stmt = $db->prepare($query);
            $stmt->execute([$id, $name, $email, $phone, $password]);
            error_log("Added student: ID=$id, Email=$email, Password=$password");
            return true;
        } catch (Exception $e) {
            error_log("Error adding student: " . $e->getMessage());
            return false;
        }
    }

    // دالة لفحص الـ password_token (اختياري)
    public function getPasswordToken($table, $id) {
        try {
            $db = $this->connect();
            $query = "SELECT password_token FROM $table WHERE id = ?";
            $stmt = $db->prepare($query);
            $stmt->execute([$id]);
            $token = $stmt->fetchColumn();
            error_log("Fetched password_token for $table ID=$id: $token");
            return $token ?: false;
        } catch (Exception $e) {
            error_log("Error fetching password_token for $table ID=$id: " . $e->getMessage());
            return false;
        }
    }
}