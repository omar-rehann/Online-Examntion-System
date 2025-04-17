<?php
class assign extends dbh {

 

  // الحصول على إعدادات المجموعات
  public function getGroupSettings() {
    $db = $this->connect();
    $query = "SELECT g.`name`, ts.id 
              FROM groups g
              JOIN test_settings ts ON ts.id = g.settingID 
              WHERE g.instructorID = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$_SESSION['mydata']->id]);
    return $stmt->fetchAll(PDO::FETCH_OBJ);
  }

  // الحصول على الإعدادات
  public function getSettings() {
    return $this->getGroupSettings(); // نفس وظيفة getGroupSettings
  }

  // الحصول على إعدادات بواسطة ID
  public function getSettingByID($id) {
    $db = $this->connect();
    $query = "SELECT * FROM test_settings WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$id]);
    $result = $stmt->fetchAll(PDO::FETCH_OBJ);
    
    return !empty($result) ? $result[0] : false;
  }

  // إنشاء إعدادات جديدة
  public function createSetting($start, $end, $prevQuestion, $duration, $percent, $viewAnswers, $releaseResult, $sendToS, $sendToI) {
    $this->deleteUnusedSettings();
    
    try {
      $db = $this->connect();
      $query = "INSERT INTO test_settings(startTime, endTime, duration, prevQuestion, 
                viewAnswers, releaseResult, sendToStudent, sendToInstructor, passPercent, instructorID)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
      
      $stmt = $db->prepare($query);
      $stmt->execute([$start, $end, $duration, $prevQuestion, $viewAnswers, 
                     $releaseResult, $sendToS, $sendToI, $percent, $_SESSION['mydata']->id]);
      
      return true;
    } catch (PDOException $e) {
      error_log("Error creating setting: " . $e->getMessage());
      return false;
    }
  }

  // تعيين اختبار لمجموعة
  public function AssignToGroup($groupID, $testID) {
    $db = $this->connect();
    $query = "UPDATE groups
              SET assignedTest = ?,
              settingID = (SELECT MAX(id) FROM test_settings WHERE instructorID = ?)
              WHERE id = ? AND instructorID = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$testID, $_SESSION['mydata']->id, $groupID, $_SESSION['mydata']->id]);
    return true;
  }

  // حذف تعيين مجموعة
  public function deleteGroupAssign($groupID) {
    $db = $this->connect();
    $query = "UPDATE groups
              SET assignedTest = NULL,
              settingID = NULL
              WHERE id = ? AND instructorID = ?";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$groupID, $_SESSION['mydata']->id]);
    $this->deleteUnusedSettings();
    return true;
  }

  // حذف الإعدادات غير المستخدمة
  public function deleteUnusedSettings() {
    $db = $this->connect();
    $query = "DELETE FROM test_settings
              WHERE NOT EXISTS(SELECT 1 FROM groups WHERE settingID=test_settings.id)
              AND NOT EXISTS(SELECT 1 FROM result WHERE settingID=test_settings.id)";
    
    $db->exec($query);
    return true;
}
}