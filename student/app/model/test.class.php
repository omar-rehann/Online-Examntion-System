<?php
require_once 'dbh.class.php'; // استيراد كلاس قاعدة البيانات

class Test extends dbh { // الكلاس يرث من كلاس قاعدة البيانات dbh

    // دالة لجلب الاختبارات المتاحة للطالب
    public function getMyTests() {
        // استعلام لجلب الاختبارات التي لم يأخذها الطالب بعد
        $query = "SELECT t.id, t.name, g.name AS groupName, i.name AS instructor, ts.endTime, ts.id AS settingID,
                  CASE 
                      WHEN (convert_tz(NOW(), @@session.time_zone, '+02:00') BETWEEN ts.startTime AND ts.endTime) THEN 'Available'
                      WHEN convert_tz(NOW(), @@session.time_zone, '+02:00') < ts.startTime THEN 'Not Started Yet'
                      WHEN convert_tz(NOW(), @@session.time_zone, '+02:00') > ts.endTime THEN 'Missed'
                      ELSE 'Not Available'
                  END AS status
                  FROM groups g
                  INNER JOIN groups_has_students gs ON gs.studentID = :studID AND g.id = gs.groupID
                  INNER JOIN test t ON t.id = g.assignedTest
                  INNER JOIN test_settings ts ON ts.id = g.settingID
                  INNER JOIN instructor i ON i.id = t.instructorID
                  WHERE t.id NOT IN (SELECT testID FROM result WHERE studentID = gs.studentID)";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":studID", $_SESSION['student']->id); // ربط معرف الطالب
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج ككائنات
        return $results; // إرجاع النتائج
    }

    // دالة لجلب تفاصيل اختبار معين
    public function getTest($testID) {
        $query = "SELECT t.id, t.name, c.name AS course, i.name AS instructor, 
                  ts.startTime, ts.duration, ts.passPercent, ts.endTime, ts.prevQuestion, 
                  ts.id AS settingID, g.id AS groupID,
                  (SELECT count(*) FROM tests_has_questions WHERE testID = t.id) AS questions
                  FROM groups g
                  INNER JOIN groups_has_students gs ON gs.studentID = :studID AND g.id = gs.groupID
                  INNER JOIN test t ON t.id = g.assignedTest
                  INNER JOIN test_settings ts ON ts.id = g.settingID
                  INNER JOIN course c ON c.id = t.courseID
                  INNER JOIN instructor i ON i.id = t.instructorID
                  WHERE (convert_tz(NOW(), @@session.time_zone, '+02:00') BETWEEN ts.startTime AND ts.endTime)
                  AND t.id NOT IN (SELECT testID FROM result WHERE studentID = gs.studentID) 
                  AND t.id = :tID";
        
        $statement = $this->connect()->prepare($query);
        $statement->bindParam(":studID", $_SESSION['student']->id);
        $statement->bindParam(":tID", $testID);
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_OBJ);
        return $results[0];
    }
    // دالة للتحقق مما إذا تم أخذ الاختبار من قبل
    public function checkTestTaken() {
        // استعلام للتحقق من وجود نتيجة للاختبار
        $query = "SELECT id FROM result WHERE testID = :testID AND studentID = :studentID";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":testID", $_SESSION['CurrentTest']->id); // ربط معرف الاختبار
        $statement->bindParam(":studentID", $_SESSION['student']->id); // ربط معرف الطالب
        $statement->execute(); // تنفيذ الاستعلام
        $count = $statement->rowCount(); // جلب عدد الصفوف
        
        if ($count > 0) {
            return true; // إرجاع نجاح إذا تم أخذ الاختبار
        } else {
            return false; // إرجاع فشل إذا لم يتم أخذه
        }
    }

    // دالة لجلب الاختبار النشط حاليًا
    public function getActiveTest() {
        $query = "SELECT t.id, t.name, ts.passPercent, ts.endTime, ts.duration, ts.viewAnswers, ts.prevQuestion,
                  (CASE 
                      WHEN ((ts.duration * 60) - TIMESTAMPDIFF(SECOND, r.startTime, convert_tz(NOW(), @@session.time_zone, '+02:00'))) < 
                           TIMESTAMPDIFF(SECOND, convert_tz(NOW(), @@session.time_zone, '+02:00'), ts.endTime) 
                      THEN ((ts.duration * 60) - TIMESTAMPDIFF(SECOND, r.startTime, convert_tz(NOW(), @@session.time_zone, '+02:00')))
                      ELSE TIMESTAMPDIFF(SECOND, convert_tz(NOW(), @@session.time_zone, '+02:00'), ts.endTime)
                  END) AS remainingTime
                  FROM test t
                  INNER JOIN result r ON r.testID = t.id AND r.isTemp AND r.studentID = :studID
                  INNER JOIN test_settings ts ON ts.id = r.settingID
                  WHERE r.isTemp";
        
        $statement = $this->connect()->prepare($query);
        $statement->bindParam(":studID", $_SESSION['student']->id);
        $statement->execute();
        $results = $statement->fetchAll(PDO::FETCH_OBJ);
        
        if (!empty($results)) {
            if ($results[0]->remainingTime < 0) {
                $this->FinishTest();
                return false;
            } else {
                return $results[0];
            }
        } else {
            return false;
        }
    }

    // دالة للتحقق من وجود اختبار نشط
    public function checkActiveTest() {
        // استعلام للتحقق من وجود اختبار نشط
        $query = "SELECT testID AS id FROM result 
                  WHERE studentID = :studID AND isTemp
                  AND ((convert_tz(NOW(), @@session.time_zone, '+02:00') BETWEEN startTime AND endTime) OR ISNULL(endTime))";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":studID", $_SESSION['student']->id); // ربط معرف الطالب
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        
        if (isset($results[0]->id)) {
            return $results[0]->id; // إرجاع معرف الاختبار النشط
        } else {
            return 0; // إرجاع صفر إذا لم يكن هناك اختبار نشط
        }
    }

    // دالة لجلب الأسئلة الخاصة بالطالب
    public function getMyQuestions() {
        // استعلام لجلب الأسئلة من الاختبار الحالي
        $query = "SELECT q.id, question, type, isTrue, instructorID, courseID, deleted, points, difficulty
                  FROM tempquestions temp
                  JOIN question q ON q.id = temp.questionID
                  WHERE resultID = (SELECT MAX(id) FROM result WHERE studentID = :studID AND isTemp)";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":studID", $_SESSION['student']->id); // ربط معرف الطالب
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_ASSOC); // جلب النتائج كمصفوفة
        
        $finalArray = array(); // مصفوفة لتخزين النتائج النهائية
        foreach ($results as $item) {
            $question = array(); // مصفوفة لكل سؤال
            $question['id'] = $item['id'];
            $question['question'] = $item['question'];
            $question['type'] = $item['type'];
            $question['difficulty'] = $item['difficulty'];
            $question['isTrue'] = $item['isTrue'];
            $question['instructorID'] = $item['instructorID'];
            $question['courseID'] = $item['courseID'];
            $question['deleted'] = $item['deleted'];
            $question['points'] = $item['points'];
            $question['answers'] = $this->getQuestionAnswers($item['id']); // جلب الإجابات
            $question['matches'] = $this->getQuestionMatches($item['id']); // جلب التطابقات
            array_push($finalArray, $question); // إضافة السؤال للمصفوفة
        }
        return json_encode($finalArray); // إرجاع النتائج بصيغة JSON
    }

    // دالة لجلب إجابات سؤال معين
    public function getQuestionAnswers($questionID) {
        // استعلام لجلب الإجابات المتاحة للسؤال
        $query = "SELECT id, questionID, answer 
                  FROM question_answers 
                  WHERE questionID = :id";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":id", $questionID); // ربط معرف السؤال
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_ASSOC); // جلب النتائج
        return $results; // إرجاع الإجابات
    }

    // دالة لجلب تطابقات سؤال معين
    public function getQuestionMatches($questionID) {
        // استعلام لجلب التطابقات للسؤال
        $query = "SELECT matchAnswer 
                  FROM question_answers 
                  WHERE questionID = :id";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":id", $questionID); // ربط معرف السؤال
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_ASSOC); // جلب النتائج
        return $results; // إرجاع التطابقات
    }

    // دالة لجلب آخر نتيجة للطالب
    public function getLastResult() {
        // استعلام لجلب معرف آخر نتيجة
        $query = "SELECT MAX(id) AS id FROM result WHERE studentID = :StudentID";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":StudentID", $_SESSION['student']->id); // ربط معرف الطالب
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        return $results[0]; // إرجاع النتيجة
    }

    // دالة لتقديم الإجابات
  public function submitAnswers($answers) {
    try {
        $resultID = $this->getLastResult()->id; // جلب معرف آخر نتيجة
        $query = 'INSERT INTO result_answers(resultID, questionID, answerID, isTrue, textAnswer) VALUES ';
        foreach ($answers as $answer) {
            $query .= '(' . $resultID . ',';
            $query .= $answer['questionID'] . ',';
            $query .= (($answer['answerID']) ? $answer['answerID'] : 'NULL') . ',';
            $query .= (($answer['isTrue']) ? 1 : 0) . ',';
            $query .= (($answer['textAnswer']) ? '"' . $this->connect()->quote($answer['textAnswer']) . '"' : 'NULL') . '),';
            
            // التحقق من الإجابة الصحيحة
            $is_correct = false;
            if ($answer['answerID']) {
                $checkQuery = "SELECT isCorrect FROM question_answers WHERE id = :answerID";
                $checkStatement = $this->connect()->prepare($checkQuery);
                $checkStatement->bindParam(":answerID", $answer['answerID']);
                $checkStatement->execute();
                $result = $checkStatement->fetch(PDO::FETCH_ASSOC);
                $is_correct = $result && $result['isCorrect'] == 1;
            } elseif ($answer['isTrue'] !== null) {
                $checkQuery = "SELECT isTrue FROM question WHERE id = :questionID";
                $checkStatement = $this->connect()->prepare($checkQuery);
                $checkStatement->bindParam(":questionID", $answer['questionID']);
                $checkStatement->execute();
                $result = $checkStatement->fetch(PDO::FETCH_ASSOC);
                $is_correct = $result && $result['isTrue'] == $answer['isTrue'];
            }
            
            // إضافة إجابة في جدول answers
            $this->insertOrUpdateAnswer(
                $_SESSION['CurrentTest']->id,
                $answer['questionID'],
                $_SESSION['student']->id,
                $is_correct
            );
        }
        $query = rtrim($query, ","); // إزالة الفاصلة الأخيرة
        $query .= '; INSERT INTO result_answers(resultID, questionID, answerID, isTrue, textAnswer) 
                   SELECT resultID, questionID, NULL, NULL, NULL 
                   FROM tempquestions 
                   WHERE resultID = ' . $resultID . '
                   AND questionID NOT IN (SELECT questionID FROM result_answers WHERE resultID = ' . $resultID . ')';
        
        $statement = $this->connect()->prepare($query);
        $statement->execute();
    
        // تحديث نقاط الإجابات
        $this->reviewAnswers();
    
        // حساب الدرجة وتحديث score
        $scoreQuery = "SELECT getResultGrade(:resultID) AS score";
        $scoreStatement = $this->connect()->prepare($scoreQuery);
        $scoreStatement->bindParam(":resultID", $resultID);
        $scoreStatement->execute();
        $score = $scoreStatement->fetch(PDO::FETCH_ASSOC)['score'] ?? 0;
    
        $updateQuery = "UPDATE result SET score = :score WHERE id = :resultID";
        $updateStatement = $this->connect()->prepare($updateQuery);
        $updateStatement->bindParam(":score", $score);
        $updateStatement->bindParam(":resultID", $resultID);
        $updateStatement->execute();
    
        // إنهاء الاختبار
        $finishResult = $this->FinishTest();
        if ($finishResult !== true) {
            error_log("Failed to finish test: " . $finishResult);
            return "Failed to finish test: " . $finishResult;
        }
    
        return 'success';
    } catch (PDOException $error) {
        error_log("Error in submitAnswers: " . $error->getMessage());
        return $error->getMessage();
    }
}
    // دالة لمراجعة الإجابات
    public function reviewAnswers() {
        // استعلام لتحديث حالة الإجابات ونقاطها
        $query = "UPDATE result_answers
                  SET isCorrect = checkAnswer(resultID, questionID),
                      points = CASE isCorrect 
                                  WHEN 1 THEN (SELECT points FROM question WHERE id = questionID)
                                  ELSE 0 
                               END
                  WHERE resultID = (SELECT MAX(id) FROM result WHERE studentID = :studentID)
                  AND (SELECT type FROM question WHERE id = questionID) IN (0, 2, 3)";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":studentID", $_SESSION['student']->id); // ربط معرف الطالب
        $statement->execute(); // تنفيذ الاستعلام
        return 1; // إرجاع نجاح العملية
    }

    // دالة لتهيئة الاختبار بالأسئلة الثابتة فقط)
    public function InitiateFixed() {
        // استعلام لحذف الأسئلة المؤقتة وإضافة الأسئلة الثابتة
        $query = "DELETE FROM tempquestions 
                  WHERE resultID IN (SELECT MAX(id) FROM result WHERE studentID = :studID AND isTemp);
                  INSERT INTO tempquestions(resultID, questionID)
                  SELECT (SELECT MAX(id) FROM result WHERE studentID = :studID AND isTemp) AS resultID, questionID
                  FROM tests_has_questions 
                  WHERE testID = :tid";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":studID", $_SESSION['student']->id); // ربط معرف الطالب
        $statement->bindParam(":tid", $_SESSION['CurrentTest']->id); // ربط معرف الاختبار
        $statement->execute(); // تنفيذ الاستعلام
    }

    // دالة لبدء الاختبار
    public function InitiateTest() {
        $groupID = (($_SESSION['CurrentTest']->groupID) ? $_SESSION['CurrentTest']->groupID : NULL); // تحديد معرف المجموعة إذا وجد
        
        // استعلام لإضافة نتيجة جديدة للاختبار
        $query = "INSERT INTO result(testID, studentID, groupID, settingID, startTime, score)
                  VALUES (:tid, :studID, :groupID, :settingID, convert_tz(NOW(), @@session.time_zone, '+02:00'), 0)";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":studID", $_SESSION['student']->id); // ربط معرف الطالب
        $statement->bindParam(":tid", $_SESSION['CurrentTest']->id); // ربط معرف الاختبار
        $statement->bindParam(":settingID", $_SESSION['CurrentTest']->settingID); // ربط معرف الإعداد
        $statement->bindParam(":groupID", $groupID); // ربط معرف المجموعة
        $statement->execute(); // تنفيذ الاستعلام
        return 1; // إرجاع نجاح العملية
    }

    // دالة لإنهاء الاختبار
    public function FinishTest() {
    try {
        $ip_address = $this->getClientIP(); // تعديل هنا
        $hostname = gethostbyaddr($ip_address);
        $resultID = $this->getLastResult()->id;
        
        $this->reviewAnswers();
        
        $scoreQuery = "SELECT getResultGrade(:resultID) AS score";
        $scoreStatement = $this->connect()->prepare($scoreQuery);
        $scoreStatement->bindParam(":resultID", $resultID);
        $scoreStatement->execute();
        $score = $scoreStatement->fetch(PDO::FETCH_ASSOC)['score'] ?? 0;
        
        $query = "UPDATE result 
                  SET isTemp = 0, 
                      endTime = convert_tz(NOW(), @@session.time_zone, '+02:00'), 
                      hostname = :hostname, 
                      ipaddr = :ipaddr,
                      score = :score
                  WHERE studentID = :studID AND isTemp 
                  ORDER BY id DESC 
                  LIMIT 1;
                  DELETE FROM tempquestions 
                  WHERE resultID = (SELECT MAX(id) FROM result WHERE studentID = :studID)";
        
        $statement = $this->connect()->prepare($query);
        $statement->bindParam(":studID", $_SESSION['student']->id);
        $statement->bindParam(":hostname", $hostname);
        $statement->bindParam(":ipaddr", $ip_address);
        $statement->bindParam(":score", $score);
        $statement->execute();
        return true;
    } catch (PDOException $error) {
        return $error->getMessage();
    }
}

    // دالة لجلب جميع نتائج الطالب
    public function getMyResults() {
        // استعلام لجلب نتائج الطالب
        $query = "SELECT r.id, r.testID, t.name AS testName, r.studentID, r.endTime, ts.releaseResult,
                  (SELECT name FROM instructor WHERE id = t.instructorID) AS Instructor,
                  getResultGrade(r.id) AS FinalGrade,
                  getResultMaxGrade(r.id) AS TestDegree
                  FROM result r
                  INNER JOIN test t ON t.id = r.testID
                  INNER JOIN test_settings ts ON ts.id = r.settingID
                  WHERE r.studentID = :sid
                  GROUP BY t.id, r.id
                  ORDER BY r.endTime DESC";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":sid", $_SESSION['student']->id); // ربط معرف الطالب
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        return $results; // إرجاع النتائج
    }

    // دالة لجلب نتيجة معينة للطالب
    public function getMyResult($resultID) {
        // استعلام لجلب تفاصيل نتيجة معينة
        $query = "SELECT r.id, t.name AS testName, t.id AS testID, r.startTime, r.endTime, ts.endTime AS testEnd, ts.releaseResult,
                  TIMESTAMPDIFF(MINUTE, r.startTime, r.endTime) AS resultDuration,
                  ts.passPercent, ts.duration AS testDuration,
                  (SELECT name FROM instructor WHERE id = t.instructorID) AS Instructor,
                  (SELECT COUNT(DISTINCT questionID) FROM result_answers WHERE resultID = r.id) AS Questions,
                  Result_CorrectQuestions(r.id) AS RightQuestions,
                  Result_WrongQuestions(r.id) AS WrongQuestions,
                  getResultGrade(r.id) AS FinalGrade,
                  getResultMaxGrade(r.id) AS TestDegree
                  FROM result r
                  INNER JOIN test_settings ts ON r.settingID = ts.id
                  INNER JOIN test t ON r.testID = t.id
                  WHERE r.id = :rid AND r.studentID = :sid";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":sid", $_SESSION['student']->id); // ربط معرف الطالب
        $statement->bindParam(":rid", $resultID); // ربط معرف النتيجة
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        return $results[0]; // إرجاع أول نتيجة
    }

    // دالة لجلب آخر نتيجة منتهية
    public function getFinishedResult() {
        // استعلام لجلب تفاصيل آخر نتيجة منتهية
        $query = "SELECT r.id, t.name AS testName, t.id AS testID, r.startTime, r.endTime, ts.endTime AS testEnd, ts.releaseResult,
                  TIMESTAMPDIFF(MINUTE, r.startTime, r.endTime) AS resultDuration,
                  ts.passPercent, ts.duration AS testDuration,
                  (SELECT name FROM instructor WHERE id = t.instructorID) AS Instructor,
                  (SELECT COUNT(DISTINCT questionID) FROM result_answers WHERE resultID = r.id) AS Questions,
                  Result_CorrectQuestions(r.id) AS RightQuestions,
                  Result_WrongQuestions(r.id) AS WrongQuestions,
                  getResultGrade(r.id) AS FinalGrade,
                  getResultMaxGrade(r.id) AS TestDegree
                  FROM result r
                  INNER JOIN test_settings ts ON r.settingID = ts.id
                  INNER JOIN test t ON r.testID = t.id
                  WHERE r.id = (SELECT MAX(id) FROM result WHERE studentID = :sid) AND r.studentID = :sid";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":sid", $_SESSION['student']->id); // ربط معرف الطالب
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        return $results[0]; // إرجاع أول نتيجة
    }

    // دالة لجلب إجابات نتيجة معينة
    public function getResultAnswers($resultID) {
        // استعلام لجلب إجابات الطالب في نتيجة معينة
        $query = "SELECT q.id, q.question, type, q.isTrue,
                  CASE type 
                      WHEN 4 THEN TRIM(ROUND(SUM(ra.points), 1)) + 0
                      ELSE TRIM(ROUND(MIN(ra.points), 1)) + 0 
                  END AS points
                  FROM result_answers ra
                  LEFT JOIN question q ON q.id = ra.questionID
                  WHERE resultID = :rid
                  GROUP BY q.id";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":rid", $resultID); // ربط معرف النتيجة
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        return $results; // إرجاع النتائج
    }

    // دالة لجلب الإجابات الصحيحة لسؤال معين
    public function getCorrectAnswers($questionID) {
        // استعلام لجلب الإجابات الصحيحة
        $query = "SELECT * FROM question_answers 
                  WHERE questionID = :qid AND isCorrect 
                  ORDER BY id";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":qid", $questionID); // ربط معرف السؤال
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        return $results; // إرجاع النتائج
    }

    // دالة لجلب الإجابات التي قدمها الطالب
    public function getGivenAnswers($resultID, $questionID) {
        // استعلام لجلب إجابات الطالب لسؤال معين
        $query = "SELECT answer, textAnswer, ra.isCorrect, TRIM(ra.points) + 0 AS points, isTrue 
                  FROM result_answers ra
                  LEFT JOIN question_answers qa ON qa.id = ra.answerID
                  WHERE ra.questionID = :qid AND ra.resultID = :rid 
                  ORDER BY qa.id";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":qid", $questionID); // ربط معرف السؤال
        $statement->bindParam(":rid", $resultID); // ربط معرف النتيجة
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        return $results; // إرجاع النتائج
    }

    // دالة للتحقق من إمكانية عرض النتائج
    public function canViewResults($resultID) {
        // استعلام للتحقق من حالة عرض الإجابات
        $query = "SELECT (SELECT viewAnswers FROM test_settings WHERE id = r.settingID) AS viewAnswers 
                  FROM result r 
                  WHERE id = :id";
        
        $statement = $this->connect()->prepare($query); // تحضير الاستعلام
        $statement->bindParam(":id", $resultID); // ربط معرف النتيجة
        $statement->execute(); // تنفيذ الاستعلام
        $results = $statement->fetchAll(PDO::FETCH_OBJ); // جلب النتائج
        return $results[0]->viewAnswers; // إرجاع حالة العرض
    }

  
    // دالة لإرسال رسائل النتائج
    public function sendResultMails() {
        $query = "INSERT INTO mails(resultID, studentID, instructorID, sends_at, type)
                  SELECT r.id, r.studentID, t.instructorID, convert_tz(NOW(), @@session.time_zone, '+02:00'), 2 
                  FROM result r
                  INNER JOIN test t ON r.testID = t.id
                  INNER JOIN test_settings ts ON r.settingID = ts.id
                  WHERE r.studentID = :studID AND ts.sendToStudent AND ts.releaseResult
                  ORDER BY r.id DESC LIMIT 1;
                  INSERT INTO mails(resultID, studentID, instructorID, sends_at, type)
                  SELECT r.id, r.studentID, t.instructorID, convert_tz(NOW(), @@session.time_zone, '+02:00'), 3 
                  FROM result r
                  INNER JOIN test t ON r.testID = t.id
                  INNER JOIN test_settings ts ON r.settingID = ts.id
                  WHERE r.studentID = :studID AND ts.sendToInstructor
                  ORDER BY r.id DESC LIMIT 1";
        
        $statement = $this->connect()->prepare($query);
        $statement->bindParam(":studID", $_SESSION['student']->id);
        $statement->execute();
    }
    
    // دالة   تحديث إجابة الطالب في قاعدة البيانات

    public function insertOrUpdateAnswer($exam_id, $question_id, $student_id, $is_correct) {
        try {
            // تحقق إذا كانت الإجابة موجودة بالفعل
            $query = "SELECT id FROM answers 
                      WHERE exam_id = :exam_id 
                      AND question_id = :question_id 
                      AND student_id = :student_id";
            $statement = $this->connect()->prepare($query);
            $statement->bindParam(":exam_id", $exam_id);
            $statement->bindParam(":question_id", $question_id);
            $statement->bindParam(":student_id", $student_id);
            $statement->execute();
            
            if ($statement->rowCount() > 0) {
                // تحديث الإجابة إذا كانت موجودة
                $query = "UPDATE answers 
                          SET is_correct = :is_correct, created_at = NOW()
                          WHERE exam_id = :exam_id 
                          AND question_id = :question_id 
                          AND student_id = :student_id";
            } else {
                // إضافة إجابة جديدة
                $query = "INSERT INTO answers (exam_id, question_id, student_id, is_correct, created_at)
                          VALUES (:exam_id, :question_id, :student_id, :is_correct, NOW())";
            }
            
            $statement = $this->connect()->prepare($query);
            $statement->bindParam(":exam_id", $exam_id);
            $statement->bindParam(":question_id", $question_id);
            $statement->bindParam(":student_id", $student_id);
            $statement->bindParam(":is_correct", $is_correct, PDO::PARAM_BOOL);
            $statement->execute();
            
            return true;
        } catch (PDOException $error) {
            error_log("Error in insertOrUpdateAnswer: " . $error->getMessage());
            return false;
        }
    }
    public function getClientIP() {
    if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
        return $_SERVER['HTTP_CLIENT_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        return $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else {
        return $_SERVER['REMOTE_ADDR'];
    }
}
}

?>