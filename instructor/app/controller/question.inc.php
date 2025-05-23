
<?php
session_start();
include_once 'autoloader.inc.php';
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;


if (isset($_GET['uploadImage'])) {
  $up = uploadFile($_FILES['file']['tmp_name']);
  echo '../style/images/uploads/' . $up . '.jpg';
}elseif (isset($_GET['deleteImage'])) {
    deleteImage($_POST['src']);
    echo 'success';
}elseif (isset($_GET['deleteAnswer'])){
  $q = new question;
  if(is_numeric($_POST['ansID'])){
    $q->deleteAnswer($_POST['ansID']);
    echo 'success';
  }
}elseif (isset($_GET['addQuestion'])){
    $question = isset($_POST['questionText']) ? trim($_POST['questionText']) : null;
    $qtype = isset($_POST['qtype']) ? trim($_POST['qtype']) : null;
    $isTrue = isset($_POST['isTrue']) ? trim($_POST['isTrue']) : 0;
    $points = isset($_POST['points']) ? trim($_POST['points']) : 0;
    $difficulty = isset($_POST['difficulty']) ? trim($_POST['difficulty']) : 1;
    $course = $_POST['Course'];
    if($question == null){
      $_SESSION["error"][] = 'Question Can\' Be Empty';
      header('Location: ' . $_SERVER['HTTP_REFERER']);exit;
    }elseif($qtype == null){
      $_SESSION["error"][] = 'Question Type is not selected';
      header('Location: ' . $_SERVER['HTTP_REFERER']);exit;
    }

    $newQuestion = new question;
    $newQuestion->insertQuestion($question,$qtype,$course,$isTrue,$points,$difficulty);
    $_SESSION["info"][] = 'Question Successfully Added';
    if ($qtype == 0) {
        foreach ($_POST['MCQanswer'] as $key=>$qanswer) {
            $answer = !empty($qanswer['answertext']) ? trim($qanswer['answertext']) : null;
            $isCorrect = !empty($qanswer['isCorrect']) ? 1 : 0;
            if ($answer != null) {
                $newQuestion->insertAnswersToLast($answer, $isCorrect,null);
            }
        }
    } elseif ($qtype == 3) {
      foreach ($_POST['MSQanswer'] as $key=>$qanswer) {
          $answer = !empty($qanswer['answertext']) ? trim($qanswer['answertext']) : null;
          $isCorrect = !empty($qanswer['isCorrect']) ? 1 : 0;
          if ($answer != null) {
              $newQuestion->insertAnswersToLast($answer, $isCorrect,null);
          }
      }
    }elseif ($qtype == 2) {
        foreach ($_POST['Canswer'] as $key=>$canswer) {
            $answer = $canswer['answertext'];
            if ($answer != '') {
                $newQuestion->insertAnswersToLast($answer, 1, null);
            }
        }
    }elseif ($qtype == 4) {
        foreach ($_POST['match'] as $key=>$manswer) {
            $matchAnswer = $_POST['matchAnswer'][$key];
            $matchPoints = $_POST['matchPoints'][$key];
            $answer = $manswer;
            if ($manswer != '' and $matchAnswer != '') {
                $newQuestion->insertAnswersToLast($manswer, 1, $matchAnswer,$matchPoints);
            }
        }
    }
    header('Location: ../../?questions=add&topic=' . $course);exit;
} elseif (isset($_GET['deleteQuestion'])) {
    $qst = new question;
    $qst->setQuestionDelete($_GET['deleteQuestion']); // حذف مؤقت 
    header('Location: ../../?questions');
} elseif (isset($_GET['restoreQuestion'])) {
    $qst = new question;
    $qst->restoreQuestion($_GET['restoreQuestion']);
    header('Location: ' . $_SERVER['HTTP_REFERER']);
} elseif (isset($_GET['PDeleteQuestion'])) { //  الحذف الدائم
    $qst = new question;
    $qst->pDeleteQuestion($_GET['PDeleteQuestion']);
    header('Location: ' . $_SERVER['HTTP_REFERER']);

} elseif (isset($_GET['updateQuestion'])) {
    $id = isset($_POST['qid']) ? trim($_POST['qid']) : null;
    $question = isset($_POST['questionText']) ? trim($_POST['questionText']) : null;
    $qtype = isset($_POST['qtype']) ? trim($_POST['qtype']) : 0;
    $isTrue = isset($_POST['isTrue']) ? trim($_POST['isTrue']) : 0;
    $points = isset($_POST['points']) ? trim($_POST['points']) : 0;
    $difficulty = isset($_POST['difficulty']) ? trim($_POST['difficulty']) : 1;
    $course = $_POST['Course'];

    $newQuestion = new question;
    $newQuestion->updateQuestion($id,$question,$course,$points,$difficulty);
    $newQuestion->updateTF($id, $isTrue);

    if ($qtype == 0 || $qtype == 3) {
        foreach ($_POST['Qanswer'] as $key=>$qanswer) {
            $ansID = isset($qanswer['ansID']) ? trim($qanswer['ansID']) : null;
            $answer = !empty($qanswer['answertext']) ? trim($qanswer['answertext']) : null;
            $isCorrect = !empty($qanswer['isCorrect']) ? trim($qanswer['isCorrect']) : 0;
            if ($ansID == null) {
                if ($answer != null) {
                    $newQuestion->insertAnswers($id, $answer, $isCorrect);
                }
              } else {
                $newQuestion->updateAnswer($ansID, $answer, $isCorrect,null);
            }
        }
    } elseif ($qtype == 2) {
        foreach ($_POST['Canswer'] as $key=>$canswer) {
            $answer = $canswer['answertext'];
            if ($answer != '') {
                $newQuestion->insertAnswers($id,$answer,1);
            }
        }
    } elseif ($qtype == 4) {
      foreach ($_POST['match'] as $key=>$manswer) {
          $oldAns = isset($_POST['oldID'][$key]) ? $_POST['oldID'][$key] : null;
          $matchAnswer = $_POST['matchAnswer'][$key];
          $matchPoints = $_POST['matchPoints'][$key];
          if ($manswer != '' and $matchAnswer != '') {
            if($oldAns == null){
              $newQuestion->insertAnswers($id,$manswer,1,$matchAnswer,$matchPoints);
            }else{
              $newQuestion->updateAnswer($oldAns, $manswer, 1,$matchAnswer,$matchPoints);
            }
          }
      }
    }
    header('Location: ' . $_SERVER['HTTP_REFERER']);
}elseif (isset($_GET['duplicateQuestion']) and is_numeric($_GET['duplicateQuestion'])){
        $id = $_GET['duplicateQuestion'];
        $q = new question;
        $q->duplicateQuestion($id);
        $newID = $q->getLastQuestion()->id;
        header('Location:../../?questions=view&id='. $newID);
}elseif (isset($_GET['export'])) {
    try {
        // تفريغ أي مخرجات سابقة
        ob_end_clean();

        // التحقق من وجود اسم المقرر الدراسي
        if (!isset($_POST['course']) || empty($_POST['course'])) {
            die("Error: Course not specified.");
        }

        $courseName = $_POST['course']; // اسم المقرر الدراسي

        // إنشاء كائن للتعامل مع الأسئلة
        $questionObject = new question();
        $questionsList = $questionObject->getByCourse($courseName); // جلب الأسئلة حسب المقرر

        // التحقق من وجود أسئلة
        if (empty($questionsList)) {
            die("Error: No questions found for this course.");
        }

        // تعريف أنواع الأسئلة
        $questionTypes = [
            0 => 'Multiple Choice', // اختيار متعدد
            1 => 'True/False',      // صح أو خطأ
            2 => 'Complete',        // تكميل
            3 => 'Multiple Select', // اختيار متعدد الإجابات
            4 => 'Matching',        // مطابقة
            5 => 'Essay'            // مقالي
        ];

        // إعداد مصفوفة لتخزين بيانات الأسئلة
        $exportData = [];

        // معالجة كل سؤال من الأسئلة
        foreach ($questionsList as $question) {
            $questionID = $question->id; // معرف السؤال

            // تنظيف نص السؤال من علامات HTML و #! والمسافات الزائدة
            $questionText = strip_tags($question->question); // إزالة علامات HTML مثل <p>
            $questionText = str_replace(['#!', '#'], '', $questionText); // إزالة #! و #
            $questionText = str_replace("\xc2\xa0", ' ', $questionText); // استبدال المسافة غير المرئية بمسافة عادية
            $questionText = trim($questionText); // إزالة جميع المسافات الزائدة

            $typeID = $question->type; // نوع السؤال (رقم)
            $typeText = $questionTypes[$typeID]; // اسم نوع السؤال
            $points = $question->points; // النقاط
            $difficulty = $question->difficulty; // مستوى الصعوبة
            $isTrue = $question->isTrue; // للأسئلة من نوع صح/خطأ

            // جلب الإجابات للسؤال
            $answersList = $questionObject->getQuestionAnswers($questionID);

            // تعيين الإجابات (حتى 4 إجابات كحد أقصى) مع تنظيف النصوص
            $answer1 = isset($answersList[0]) ? trim(str_replace(['#!', '#'], '', str_replace("\xc2\xa0", ' ', strip_tags($answersList[0]->answer)))) : '';
            $answer2 = isset($answersList[1]) ? trim(str_replace(['#!', '#'], '', str_replace("\xc2\xa0", ' ', strip_tags($answersList[1]->answer)))) : '';
            $answer3 = isset($answersList[2]) ? trim(str_replace(['#!', '#'], '', str_replace("\xc2\xa0", ' ', strip_tags($answersList[2]->answer)))) : '';
            $answer4 = isset($answersList[3]) ? trim(str_replace(['#!', '#'], '', str_replace("\xc2\xa0", ' ', strip_tags($answersList[3]->answer)))) : '';

            // إضافة #! إلى الإجابة الصحيحة فقط
            if (isset($answersList[0]) && $answersList[0]->isCorrect) {
                $answer1 = '#!' . $answer1;
            }
            if (isset($answersList[1]) && $answersList[1]->isCorrect) {
                $answer2 = '#!' . $answer2;
            }
            if (isset($answersList[2]) && $answersList[2]->isCorrect) {
                $answer3 = '#!' . $answer3;
            }
            if (isset($answersList[3]) && $answersList[3]->isCorrect) {
                $answer4 = '#!' . $answer4;
            }

            // تنسيق الإجابات حسب نوع السؤال
            if ($typeID == 0 || $typeID == 3) { // اختيار متعدد أو متعدد الإجابات
                // تمت معالجة #! للإجابة الصحيحة أعلاه
            } elseif ($typeID == 4) { // مطابقة
                $answer1 = isset($answersList[0]) ? trim(str_replace(['#!', '#'], '', $answersList[0]->answer)) . " >> " . trim(str_replace(['#!', '#'], '', $answersList[0]->matchAnswer)) : '';
                $answer2 = isset($answersList[1]) ? trim(str_replace(['#!', '#'], '', $answersList[1]->answer)) . " >> " . trim(str_replace(['#!', '#'], '', $answersList[1]->matchAnswer)) : '';
                $answer3 = isset($answersList[2]) ? trim(str_replace(['#!', '#'], '', $answersList[2]->answer)) . " >> " . trim(str_replace(['#!', '#'], '', $answersList[2]->matchAnswer)) : '';
                $answer4 = isset($answersList[3]) ? trim(str_replace(['#!', '#'], '', $answersList[3]->answer)) . " >> " . trim(str_replace(['#!', '#'], '', $answersList[3]->matchAnswer)) : '';
                // إضافة #! إلى الإجابة الصحيحة
                if (isset($answersList[0]) && $answersList[0]->isCorrect) {
                    $answer1 = '#!' . $answer1;
                }
                if (isset($answersList[1]) && $answersList[1]->isCorrect) {
                    $answer2 = '#!' . $answer2;
                }
                if (isset($answersList[2]) && $answersList[2]->isCorrect) {
                    $answer3 = '#!' . $answer3;
                }
                if (isset($answersList[3]) && $answersList[3]->isCorrect) {
                    $answer4 = '#!' . $answer4;
                }
            } elseif ($typeID == 1) { // صح/خطأ
                $answer1 = ($isTrue == 1) ? '#!True' : 'True';
                if ($isTrue == 0) {
                    $answer1 = 'False';
                    $answer2 = '#!False';
                } else {
                    $answer2 = 'False';
                }
                $answer3 = '';
                $answer4 = '';
            } elseif ($typeID == 5) { // مقالي
                $answer1 = '';
                $answer2 = '';
                $answer3 = '';
                $answer4 = '';
            }

            // إضافة السؤال وإجاباته إلى المصفوفة
            $exportData[] = [$questionText, $typeText, $points, $difficulty, $answer1, $answer2, $answer3, $answer4];
        }

        // إنشاء ملف Excel جديد
        $excelFile = new Spreadsheet();
        $sheet = $excelFile->getActiveSheet();

        // إضافة العناوين في الصف الأول
        $headers = ['Question', 'Question Type', 'Points', 'Difficulty', 'Answer 1', 'Answer 2', 'Answer 3', 'Answer 4'];
        $column = 'A';
        foreach ($headers as $headerText) {
            $sheet->setCellValue($column . '1', $headerText);
            $column++;
        }

        // تنسيق العناوين: لون خلفية أزرق غامق وخط أبيض عريض
        $headerStyle = [
            'font' => [
                'bold' => true, // خط عريض
                'color' => ['rgb' => 'FFFFFF'] // لون الخط أبيض
            ],
            'fill' => [
                'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                'startColor' => ['rgb' => '4472C4'] // لون خلفية أزرق غامق
            ],
            'alignment' => [
                'horizontal' => \PhpOffice\PhpSpreadsheet\Style\Alignment::HORIZONTAL_CENTER // محاذاة مركزية
            ]
        ];
        $sheet->getStyle('A1:H1')->applyFromArray($headerStyle);

        // إضافة البيانات إلى الملف مع تنسيق الصفوف
        $rowNumber = 2; // بدء الكتابة من الصف الثاني
        foreach ($exportData as $dataRow) {
            $column = 'A';
            foreach ($dataRow as $cellValue) {
                $sheet->setCellValue($column . $rowNumber, $cellValue);
                $column++;
            }

            // تلوين الصفوف المتناوبة بلون رمادي فاتح
            if ($rowNumber % 2 == 0) {
                $sheet->getStyle("A$rowNumber:H$rowNumber")->getFill()
                    ->setFillType(\PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID)
                    ->getStartColor()->setRGB('E9ECEF'); // لون رمادي فاتح
            }
            $rowNumber++;
        }

        // إضافة حدود خفيفة لجميع الخلايا
        $borderStyle = [
            'borders' => [
                'allBorders' => [
                    'borderStyle' => \PhpOffice\PhpSpreadsheet\Style\Border::BORDER_THIN,
                    'color' => ['rgb' => 'D3D3D3'] // لون حدود رمادي خفيف
                ]
            ]
        ];
        $lastRow = $rowNumber - 1; // آخر صف تمت كتابته
        $sheet->getStyle("A1:H$lastRow")->applyFromArray($borderStyle);

        // ضبط حجم الأعمدة تلقائيًا
        foreach (range('A', 'H') as $columnLetter) {
            $sheet->getColumnDimension($columnLetter)->setAutoSize(true);
        }

        // إعداد الملف للتحميل
        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment;filename="' . $courseName . '_Questions.xlsx"');
        header('Cache-Control: max-age=0');

        // حفظ الملف وإرساله للمستخدم
        $writer = IOFactory::createWriter($excelFile, 'Xlsx');
        $writer->save('php://output');
        exit;

    } catch (Exception $error) {
        // في حالة حدوث خطأ، عرض رسالة الخطأ
        die("Error: " . $error->getMessage());
    }
}
else if (isset($_GET['import']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // التحقق من وجود ملف مرفوع
    if (!isset($_FILES['excel']) || $_FILES['excel']['error'] !== UPLOAD_ERR_OK) {
        die(json_encode([
            'status' => 'error',
            'message' => 'File not uploaded or upload error: ' . $_FILES['excel']['error']
        ]));
    }

    $excelFile = $_FILES['excel']['tmp_name'];

    // التحقق من صحة الملف
    if (!file_exists($excelFile)) {
        die(json_encode([
            'status' => 'error',
            'message' => 'File not found on server'
        ]));
    }

    if (!is_readable($excelFile)) {
        die(json_encode([
            'status' => 'error',
            'message' => 'Cannot read file'
        ]));
    }

    try {
        // تحديد نوع الملف وإنشاء القارئ المناسب
        $reader = IOFactory::createReaderForFile($excelFile);
        $reader->setReadDataOnly(true);
        $spreadsheet = $reader->load($excelFile);

        // الحصول على البيانات من الورقة الأولى
        $sheet = $spreadsheet->getActiveSheet();
        $sheetData = $sheet->toArray(null, true, true, true);

        if (empty($sheetData)) {
            die(json_encode([
                'status' => 'error',
                'message' => 'Excel file is empty'
            ]));
        }

        // تضمين ملف نموذج الأسئلة
        require_once '../model/question.class.php';
        if (!class_exists('question')) {
            die(json_encode([
                'status' => 'error',
                'message' => 'Question class not found'
            ]));
        }

        $q = new question();
        $course = $_POST['course'] ?? null;

        if (!$course) {
            die(json_encode([
                'status' => 'error',
                'message' => 'Course not specified'
            ]));
        }

        // أنواع الأسئلة المتاحة
        $qTypes = [
            'Multiple Choice' => 0,
            'True/False' => 1,
            'Complete' => 2,
            'Multiple Select' => 3,
            'Matching' => 4,
            'Essay' => 5
        ];

        // بدء عملية الاستيراد
        $importedCount = 0;
        $skippedCount = 0;
        $errors = [];

        // تفعيل التعامل مع المعاملات إذا كانت مدعومة
        $transactionSupported = method_exists($q, 'beginTransaction');
        if ($transactionSupported) {
            $q->beginTransaction();
        }

        foreach ($sheetData as $rowIndex => $row) {
            // تخطي الصفوف الأولى (العناوين)
            if ($rowIndex < 2) continue;

            $questionText = trim($row['A'] ?? '');
            $questionTypeText = trim($row['B'] ?? '');
            $points = (int)trim($row['C'] ?? 0);
            $difficulty = (int)trim($row['D'] ?? 1);

            // التحقق من صحة البيانات الأساسية
            if (empty($questionText)) {
                $errors[] = "Row $rowIndex: Question text is empty";
                $skippedCount++;
                continue;
            }

            if (!isset($qTypes[$questionTypeText])) {
                $errors[] = "Row $rowIndex: Invalid question type '$questionTypeText'";
                $skippedCount++;
                continue;
            }

            $qtype = $qTypes[$questionTypeText];

            // إدخال السؤال الأساسي
            try {
                $insertResult = $q->insertQuestion($questionText, $qtype, $course, 0, $points, $difficulty);

                if (!$insertResult) {
                    $errors[] = "Row $rowIndex: Failed to insert question into database";
                    $skippedCount++;
                    continue;
                }

                $lastInsertedId = $q->getLastQuestion()->id;
                $importedCount++;

                // معالجة الإجابات حسب نوع السؤال
                switch ($qtype) {
                    case 0: // Multiple Choice
                    case 3: // Multiple Select
                        for ($i = 'E'; $i <= 'H'; $i++) {
                            $answerText = trim($row[$i] ?? '');
                            if (empty($answerText)) continue;

                            $isCorrect = strpos($answerText, '#!') === 0 ? 1 : 0;
                            $answerText = str_replace('#!', '', $answerText);

                            if (!$q->insertAnswers($lastInsertedId, $answerText, $isCorrect)) {
                                $errors[] = "Row $rowIndex: Failed to insert answer in column $i";
                            }
                        }
                        break;

                    case 1: // True/False
                        $answerText = trim($row['E'] ?? '');
                        $answerText2 = trim($row['F'] ?? '');

                        if (empty($answerText)) {
                            $errors[] = "Row $rowIndex: Missing True/False answer";
                            continue 2; // يتخطى تكرار الـ foreach
                        }

                        $isTrue = null;
                        if (strpos($answerText, '#!') === 0) {
                            $answerText = str_replace('#!', '', $answerText);
                            if (strtolower($answerText) === 'true') {
                                $isTrue = 1;
                            } elseif (strtolower($answerText) === 'false') {
                                $isTrue = 0;
                            }
                        } elseif (strpos($answerText2, '#!') === 0) {
                            $answerText2 = str_replace('#!', '', $answerText2);
                            if (strtolower($answerText2) === 'true') {
                                $isTrue = 1;
                            } elseif (strtolower($answerText2) === 'false') {
                                $isTrue = 0;
                            }
                        } else {
                            // الاعتماد على القيمة بدون #! إذا لم يكن موجودًا
                            if (strtolower($answerText) === 'true') {
                                $isTrue = 1;
                            } elseif (strtolower($answerText) === 'false') {
                                $isTrue = 0;
                            }
                        }

                        if ($isTrue === null) {
                            $errors[] = "Row $rowIndex: Invalid True/False answer";
                            continue 2;
                        }

                        if (!$q->updateTF($lastInsertedId, $isTrue)) {
                            $errors[] = "Row $rowIndex: Failed to update True/False answer";
                        }
                        break;

                    case 2: // Complete
                        for ($i = 'E'; $i <= 'H'; $i++) {
                            $answerText = trim($row[$i] ?? '');
                            if (empty($answerText)) continue;

                            $isCorrect = strpos($answerText, '#!') === 0 ? 1 : 0;
                            $answerText = str_replace('#!', '', $answerText);

                            if (!$q->insertAnswers($lastInsertedId, $answerText, $isCorrect)) {
                                $errors[] = "Row $rowIndex: Failed to insert completion answer in column $i";
                            }
                        }
                        break;

                    case 4: // Matching
                        for ($i = 'E'; $i <= 'H'; $i++) {
                            $matchText = trim($row[$i] ?? '');
                            if (empty($matchText)) continue;

                            $isCorrect = strpos($matchText, '#!') === 0 ? 1 : 0;
                            $matchText = str_replace('#!', '', $matchText);

                            $matchParts = explode('>>', $matchText);
                            if (count($matchParts) !== 2) {
                                $errors[] = "Row $rowIndex: Invalid matching format in column $i";
                                continue;
                            }

                            if (!$q->insertAnswers($lastInsertedId, trim($matchParts[0]), $isCorrect, trim($matchParts[1]))) {
                                $errors[] = "Row $rowIndex: Failed to insert matching in column $i";
                            }
                        }
                        break;

                    case 5: // Essay
                        // No specific answers needed
                        break;
                }
            } catch (Exception $e) {
                $errors[] = "Row $rowIndex: " . $e->getMessage();
                $skippedCount++;
                continue;
            }
        }

        // إذا كان التعامل مع المعاملات مدعومًا، تأكيد العملية
        if ($transactionSupported) {
            $q->commit();
        }

        // نتيجة الاستيراد
        $result = [
            'status' => 'success',
            'imported' => $importedCount,
            'skipped' => $skippedCount,
            'errors' => $errors
        ];

        // إعادة التوجيه بعد النجاح
        header('Location: ../../?questions');
        echo json_encode($result);
        exit;

    } catch (Exception $e) {
        // في حالة حدوث خطأ، التراجع عن المعاملة إذا كانت مدعومة
        if ($transactionSupported && isset($q)) {
            $q->rollBack();
        }

        die(json_encode([
            'status' => 'error',
            'message' => 'Error processing file: ' . $e->getMessage()
        ]));
    }
} else {
    die(json_encode([
        'status' => 'error',
        'message' => 'Invalid request'
    ]));
}
  
?>