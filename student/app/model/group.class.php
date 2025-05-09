<?php
// نستدعي ملف الاتصال بقاعدة البيانات
require_once 'dbh.class.php';

// ننشئ كلاس group الذي يرث من كلاس الاتصال بقاعدة البيانات
class group extends dbh {

    // .دالة للحصول على المجموعات التي ينتمي إليها الطالب
    function getMyGroups() {
        // نقوم بالاتصال بقاعدة البيانات
        $database_connection = $this->connect();
        
        // نجهز استعلام SQL لجلب المجموعات
        $sql_query = "SELECT g.id, g.`name`,
                     (SELECT name FROM instructor WHERE id = g.instructorID) AS instructor, 
                     gs.joinDate
                     FROM groups_has_students gs
                     INNER JOIN groups g ON gs.groupID = g.id
                     WHERE gs.studentID = ?";
        
        // نجهز الاستعلام ونربط المعاملات
        $prepared_statement = $database_connection->prepare($sql_query);
        $prepared_statement->bindParam(1, $_SESSION['student']->id);
        
        // ننفذ الاستعلام
        $prepared_statement->execute();
        
        // نعيد النتائج ككائنات
        return $prepared_statement->fetchAll(PDO::FETCH_OBJ);
    }

     // دالة للتحقق من وجود كود الدعوة في قاعدة البيانات

  public function checkCode($code){
      $stmt = $this->connect()->prepare("select * from group_invitations where code = :code");
      $stmt->bindparam(":code",$code);
      $stmt->execute();
      $result=$stmt->rowCount();
      if($result > 0){
              return true;
      }else{
              return false;
      }
    }
    // دالة للتحقق مما إذا كان الطالب عضوًا بالفعل في المجموعة المرتبطة بالكود

    public function alreadyMemeber($code){
        $stmt = $this->connect()->prepare("select * from groups_has_students where groupID = (SELECT groupID from group_invitations where code = :code) and studentID = :studID");
        $stmt->bindparam(":code",$code);
        $stmt->bindparam(":studID",$_SESSION['student']->id);
        $stmt->execute();
        $result=$stmt->rowCount();
        if($result > 0){
                return true;
        }else{
                return false;
        }
      }
    // دالة لإضافة الطالب إلى المجموعة المرتبطة بالكود، ثم حذف الكود من جدول الدعوات
    public function joinGroup($code){
        $stmt = $this->connect()->prepare("insert into groups_has_students (GroupID,studentID,joinDate)
                                select groupID,:studID,convert_tz(now(),@@session.time_zone,'+02:00') from group_invitations where code = :code;
                                DELETE FROM group_invitations where code = :code");
        $stmt->bindparam(":code",$code);
        $stmt->bindparam(":studID",$_SESSION['student']->id);
        $stmt->execute();
        $result=$stmt->rowCount();
        return 1;
}
        // الخروج من جروب 
      public function leaveGroup($groupID){
          $stmt = $this->connect()->prepare("Delete From groups_has_students where groupID = :groupID and studentID = :studID");
          $stmt->bindparam(":groupID",$groupID);
          $stmt->bindparam(":studID",$_SESSION['student']->id);
          $stmt->execute();
          return 1;
        }
}