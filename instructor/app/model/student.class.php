<?php

class Student extends dbh {

    // دالة لجلب طلاب الدكتور
    public function getMyStudents() {
        $query = "SELECT DISTINCT s.id, s.name, s.email, s.phone, s.suspended 
                  FROM result r
                  INNER JOIN student s ON r.studentID = s.id
                  INNER JOIN test t ON t.id = r.testID AND t.instructorID = :instID";
        $statement = $this->connect()->prepare($query);
        $statement->bindParam(":instID", $_SESSION['mydata']->id);
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_OBJ);
        return $results;
    }

    // دالة لجلب جميع IDs الطلاب
    public function getAllIDs() {
        $query = "SELECT id FROM student";
        $statement = $this->connect()->prepare($query);
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_OBJ);
        return $results;
    }

    // دالة لجلب الطلاب غير المسجلين
    public function getUnregistered() {
        $query = "SELECT * FROM student WHERE password IS NULL";
        $statement = $this->connect()->prepare($query);
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_OBJ);
        return $results;
    }

    // دالة لجلب نتائج طالب معين
    public function getStudentResults($studentID) {
        $query = "SELECT r.id, r.testID, t.name AS testName, s.name AS studentName, 
                  r.studentID, r.startTime, r.endTime,
                  (SELECT name FROM student WHERE id = r.studentID) AS student, 
                  ipaddr, hostname,
                  getResultGrade(r.id) AS FinalGrade,
                  getResultMaxGrade(r.id) AS TestDegree
                  FROM result r
                  INNER JOIN test t ON t.id = r.testID AND t.instructorID = :instID
                  INNER JOIN student s ON s.id = r.studentID
                  WHERE r.studentID = :studentID AND !r.isTemp
                  GROUP BY t.id, r.id
                  ORDER BY r.endTime DESC";
        $statement = $this->connect()->prepare($query);
        $statement->bindParam(":instID", $_SESSION['mydata']->id);
        $statement->bindParam(":studentID", $studentID);
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_OBJ);
        return $results;
    }

    // دالة لإضافة طلاب جدد
    public function addStudents($students) {
        try {
            $query = 'INSERT IGNORE INTO student(id) VALUES (?)';
            $statement = $this->connect()->prepare($query);
            foreach ($students as $studentID) {
                $statement->execute([$studentID]);
            }
            return true;
        } catch (PDOException $error) {
            error_log($error->getMessage());
            return false;
        }
    }

    // دالة لحذف طالب
    public function deleteStudent($studentID) {
        try {
            $query = "DELETE FROM student WHERE id = :id";
            $statement = $this->connect()->prepare($query);
            $statement->bindParam(":id", $studentID);
            $statement->execute();
            return $statement->rowCount() > 0;
        } catch (PDOException $error) {
            error_log($error->getMessage());
            return false;
        }
    }
}