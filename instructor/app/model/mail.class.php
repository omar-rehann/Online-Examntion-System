<?php

class mail extends dbh {

    // دالة لإضافة رسالة جديدة في جدول mails
    public function insertMail($resultID, $type, $sends_at) {
        try {
            $sql = "SELECT r.studentID, t.instructorID 
                    FROM result r 
                    JOIN test t ON r.testID = t.id 
                    WHERE r.id = :resultID";
            $stmt = $this->connect()->prepare($sql);
            $stmt->bindParam(":resultID", $resultID);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ);
            if (!$result || empty($result->studentID) || empty($result->instructorID)) {
                throw new Exception("Invalid resultID: Cannot find valid studentID or instructorID");
            }
            $sql = "INSERT INTO mails (resultID, studentID, instructorID, type, sends_at, sent) 
                    VALUES (:resultID, :studentID, :instructorID, :type, :sends_at, 0)";
            $stmt = $this->connect()->prepare($sql);
            $stmt->bindParam(":resultID", $resultID);
            $stmt->bindParam(":studentID", $result->studentID);
            $stmt->bindParam(":instructorID", $result->instructorID);
            $stmt->bindParam(":type", $type);
            $stmt->bindParam(":sends_at", $sends_at);
            $stmt->execute();
            return true;
        } catch (Exception $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    // تحديث البريد بالقيم الصحيحة
    public function fixInvalidMails() {
        try {
            $sql = "UPDATE mails m
                    JOIN result r ON m.resultID = r.id
                    JOIN test t ON r.testID = t.id
                    SET m.studentID = r.studentID, m.instructorID = t.instructorID
                    WHERE m.studentID = 0 OR m.instructorID = 0";
            $stmt = $this->connect()->prepare($sql);
            $stmt->execute();
            return $stmt->rowCount() . " mails fixed.";
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return "Error fixing mails: " . $e->getMessage();
        }
    }

    // بيانات الطالب مع التحقق من Password Token
    public function getStudentToken($id) {
        try {
            $sql = "SELECT name, email, password_token FROM student WHERE id = :id AND token_expire > NOW()";
            $stmt = $this->connect()->prepare($sql);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ) ?: false;
            if ($result) {
                error_log("Student token for ID $id: {$result->password_token}");
            } else {
                error_log("No valid student token found for ID $id");
            }
            return $result;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    // بيانات دكتور مع التحقق من Password Token
    public function getInstructorToken($id) {
        try {
            $sql = "SELECT name, email, password_token FROM instructor WHERE id = :id AND token_expire > NOW()";
            $stmt = $this->connect()->prepare($sql);
            $stmt->bindParam(":id", $id);
            $stmt->execute();
            $result = $stmt->fetch(PDO::FETCH_OBJ) ?: false;
            if ($result) {
                error_log("Instructor token for ID $id: {$result->password_token}");
            } else {
                error_log("No valid instructor token found for ID $id");
            }
            return $result;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }

    // نتيجة اختبار طالب معين
    public function getResult($rid) {
        try {
            $sql = "SELECT 
                      r.id,
                      t.name AS testName,
                      s.name AS studentName,
                      s.id AS studentID,
                      s.email AS studentMail,
                      getResultGrade(r.id) AS FinalGrade,
                      getTestGrade(r.id) AS TestDegree,
                      i.name AS instructorName,
                      i.email AS instructorMail
                    FROM result r
                    INNER JOIN test t ON t.id = r.testID
                    INNER JOIN student s ON s.id = r.studentID
                    INNER JOIN instructor i ON i.id = t.instructorID
                    WHERE r.id = :rid
                    GROUP BY t.id, r.id";
            $stmt = $this->connect()->prepare($sql);
            $stmt->bindParam(":rid", $rid);
            $stmt->execute();
            $result = $stmt->fetchAll(PDO::FETCH_OBJ);
            return !empty($result) ? $result[0] : false;
        } catch (PDOException $e) {
            error_log($e->getMessage());
            return false;
        }
    }
}