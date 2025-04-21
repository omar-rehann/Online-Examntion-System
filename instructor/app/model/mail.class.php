<?php


class mail extends dbh
{

// دالة لإضافة رسالة جديدة في جدول mails    
public function insertMail($resultID, $type, $sends_at) {
  // استعلام لجلب studentID وinstructorID بناءً على resultID من جدول result وtest
  $sql = "SELECT r.studentID, t.instructorID 
          FROM result r 
          JOIN test t ON r.testID = t.id 
          WHERE r.id = :resultID";
  
  // تحضير الاستعلام وربط القيم
  $stmt = $this->connect()->prepare($sql);
  $stmt->bindParam(":resultID", $resultID);
  $stmt->execute();
  $result = $stmt->fetch(PDO::FETCH_OBJ); // جلب النتيجة ككائن

  // التحقق من وجود النتيجة وصحة studentID وinstructorID
  if (!$result || empty($result->studentID) || empty($result->instructorID)) {
      throw new Exception("Invalid resultID: Cannot find valid studentID or instructorID");
  }

  // استعلام لإدخال رسالة جديدة في جدول mails مع القيم الصحيحة
  $sql = "INSERT INTO mails (resultID, studentID, instructorID, type, sends_at, sent) 
          VALUES (:resultID, :studentID, :instructorID, :type, :sends_at, 0)";
  
  // تحضير الاستعلام وربط القيم
  $stmt = $this->connect()->prepare($sql);
  $stmt->bindParam(":resultID", $resultID);
  $stmt->bindParam(":studentID", $result->studentID);
  $stmt->bindParam(":instructorID", $result->instructorID);
  $stmt->bindParam(":type", $type);
  $stmt->bindParam(":sends_at", $sends_at);
  $stmt->execute(); // تنفيذ الاستعلام

  return true; // إرجاع true لتأكيد نجاح العملية
}

// تحديث البريد بالقيم الصحيحة.

public function fixInvalidMails() {
  // استعلام لتحديث الرسائل اللي فيها studentID أو instructorID بقيمة 0 باستخدام القيم الصحيحة من result وtest
  $sql = "UPDATE mails m
          JOIN result r ON m.resultID = r.id
          JOIN test t ON r.testID = t.id
          SET m.studentID = r.studentID, m.instructorID = t.instructorID
          WHERE m.studentID = 0 OR m.instructorID = 0";
  
  // تحضير وتنفيذ الاستعلام
  $stmt = $this->connect()->prepare($sql);
  $stmt->execute();

  // إرجاع عدد الرسائل اللي تم تصليحها مع رسالة توضيحية
  return $stmt->rowCount() . " mails fixed.";
}

  //  بيانات الطالب مع التحقق من  Password Token
  public function getStudentToken($id) {
    $sql = "SELECT name, email, password_token FROM student WHERE id = :id AND token_expire > NOW()";
    $stmt = $this->connect()->prepare($sql);
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    $result = $stmt->fetch();

    if (!empty($result)) {
      return $result;
    } else {
      return false;
    }
  }

  //  بيانات المعلم مع التحقق من  Password Token
  public function getInstructorToken($id) {
    $sql = "SELECT name, email, password_token FROM instructor WHERE id = :id AND token_expire > NOW()";
    $stmt = $this->connect()->prepare($sql);
    $stmt->bindParam(":id", $id);
    $stmt->execute();
    $result = $stmt->fetch();

    if (!empty($result)) {
      return $result;
    } else {
      return false;
    }
  }

  //  نتيجة اختبار طالب معين
  public function getResult($rid) {
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
    return $result[0];
  }

}
?>
