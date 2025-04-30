<?php
session_start();
require_once '../model/student.class.php';
require_once 'function.php';

if ($_GET['action'] == 'login') {
    $id = $_POST['id'];
    $pass = $_POST['password'];
    $_student = new student();
    $loggedIn = $_student->login($id, $pass);
    if ($loggedIn) {
        $student = $_student->getByID($id);
        $_student->setSession($id);
        if ($student->suspended) {
            echo 'Your account has been disabled for violating our terms';
            exit;
        } else {
            $_SESSION['student'] = $student;
            echo 'success';
            exit;
        }
    } else {
        echo 'Your ID or password is not correct!';
    }
} else if ($_GET['action'] == 'requestReset') {
    $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
    $_student = new student();
    if ($_student->checkEmail($email)) {
        $token = bin2hex(random_bytes(25));
        $_student->generatePasswordToken($email, $token);
        echo 'success';
    } else {
        echo 'Email Address is not Registered';
    }
} else if ($_GET['action'] == 'resetPassword') {
    $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
    $password = !empty($_POST['password']) ? trim($_POST['password']) : null;
    $repassword = !empty($_POST['repassword']) ? trim($_POST['repassword']) : null;

    if (empty($email) || empty($password) || empty($repassword)) {
        echo 'All fields are required';
        exit;
    }

    if ($password !== $repassword) {
        echo 'Passwords do not match';
        exit;
    }

    if (strlen($password) < 6) {
        echo 'Password must be at least 6 characters';
        exit;
    }

    if (!isset($_SESSION['student']) || $_SESSION['student']->email !== $email) {
        echo 'Unauthorized access';
        exit;
    }

    $_student = new student();
    if (!$_student->checkEmail($email)) {
        echo 'Email not found';
        exit;
    }

    $_student->updatePassword($email, $password);
    echo 'success';
    exit;
}
else if ($_GET['action'] == 'updateInfo'){
    $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
    $oldemail = !empty($_POST['oldemail']) ? trim($_POST['oldemail']) : null;
    $phonenum = !empty($_POST['phonenum']) ? trim($_POST['phonenum']) : null;
    $_student = new student();
    if ($email != $oldemail && $_student->checkEmail($email))
      echo 'Email already used';
    elseif (strlen($phonenum) != 11)
      echo 'Phone Number Is not valid';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL))
      echo 'Email format is not valid';
    else{
      $_student->updateInfo($email,$phonenum);
      $newDATA = $_student->getByID($_SESSION['student']->id);
      $_SESSION['student'] = $newDATA;
      echo "success";
      header('Location: ' . $_SERVER['HTTP_REFERER']);

      exit;

    }
    exit;

  } 

else if ($_GET['action'] == 'updatePassword') {
    $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
    $password = !empty($_POST['password']) ? trim($_POST['password']) : null;
    $repassword = !empty($_POST['repassword']) ? trim($_POST['repassword']) : null;

    if (empty($email) || empty($password) || empty($repassword)) {
        echo 'All fields are required';
        exit;
    }

    if ($password !== $repassword) {
        echo 'Passwords do not match';
        exit;
    }

    if (strlen($password) < 6) {
        echo 'Password must be at least 6 characters';
        exit;
    }

    if (!isset($_SESSION['student']) || $_SESSION['student']->email !== $email) {
        echo 'Unauthorized access';
        exit;
    }

    $_student = new student();
    if (!$_student->checkEmail($email)) {
        echo 'Email not found';
        exit;
    }

    $updated = $_student->updatePassword($email, $password);
    error_log("Update password for email $email: " . ($updated ? "Success" : "Failed"));

    if ($updated) {
        echo 'success';
    } else {
        echo 'Failed to update password';
    }
    exit;
}

?>