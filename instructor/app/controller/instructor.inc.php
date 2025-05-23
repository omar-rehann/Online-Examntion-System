<?php
session_start();
include_once 'autoloader.inc.php';

if (isset($_GET['action']) && $_GET['action'] == 'login') {
    $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
    $pass = !empty($_POST['password']) ? trim($_POST['password']) : null;
    $inst = new Instructor();
    $loggedIn = $inst->login($email, $pass);
    if ($loggedIn) {
        $mydata = $inst->getByEmail($email);
        $_SESSION['mydata'] = $mydata;
        header("Location: ../../");
    } else {
        $_SESSION["error"][] = 'Email or password is wrong!';
        header("Location: ../../");
    }
} else if (isset($_GET['action']) && $_GET['action'] == 'register') {
    $name = !empty($_POST['name']) ? trim($_POST['name']) : null;
    $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
    $phone = !empty($_POST['phone']) ? trim($_POST['phone']) : null;
    $pass = !empty($_POST['password']) ? trim($_POST['password']) : null;
    $inst = new Instructor();
    if ($inst->checkEmail($email)) {
        $_SESSION["error"][] = 'Email Already Exists!';
    }
    if (!is_numeric($phone)) {
        $_SESSION["error"][] = 'Phone Number is not valid';
    }
    if (isset($_SESSION["error"])) {
        header("Location: ../../?register");
    } else {
        $inst->register($name, $pass, $email, $phone);
        $_SESSION["info"][] = 'Your account has been registered!<br> You can login now';
        header("Location: ../../?login");
    }
} else if (isset($_GET['action']) && $_GET['action'] == 'manualinsert') {
    $name = !empty($_POST['name']) ? trim($_POST['name']) : null;
    $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
    $phone = !empty($_POST['phone']) ? trim($_POST['phone']) : null;
    $pass = !empty($_POST['password']) ? trim($_POST['password']) : null;
    $inst = new Instructor();
    if ($inst->checkEmail($email)) {
        $_SESSION["error"][] = 'Email Already Exists!';
    }
    if (!is_numeric($phone)) {
        $_SESSION["error"][] = 'Phone Number is not valid';
    }
    if (empty($_SESSION["error"])) {
        $inst->register($name, $pass, $email, $phone);
        $_SESSION["info"][] = 'The account has been registered';
    }
    header("Location: ../../?instructors");
} else if (isset($_GET['action']) && $_GET['action'] == 'requestReset') {
    $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
    // إنشاء token كـ plain text (حروف وأرقام فقط)
    $token = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 15);
    // Logging لتتبع الـ token
    error_log("Generated token for $email: $token");
    $inst = new Instructor();
    if ($inst->checkEmail($email)) {
        $inst->generatePasswordToken($email, $token);
        // التحقق من الـ token المخزن
        $sql = "SELECT password_token FROM instructor WHERE email = :email";
        $stmt = $inst->connect()->prepare($sql);
        $stmt->bindParam(":email", $email);
        $stmt->execute();
        $stored_token = $stmt->fetchColumn();
        error_log("Stored token for $email: $stored_token");
        $_SESSION["info"][] = "Reset Email will be sent in 2 minutes";
    } else {
        $_SESSION["error"][] = 'Email Address is not registered';
    }
    header("Location: ../../?login");
} else if (isset($_GET['action']) && $_GET['action'] == 'requestReset') {
  $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
  $token = substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyz"), 0, 15);
  
  // For debugging (server logs only)
  error_log("Password reset token generated for $email");
  
  $inst = new Instructor();
  if ($inst->checkEmail($email)) {
      $inst->generatePasswordToken($email, $token);
      
      // In a real application, you would SEND THE EMAIL here
      // mail($email, "Password Reset", "Your token: $token");
      
      $_SESSION["info"][] = "If this email exists, a reset link has been sent";
  } else {
      // Don't reveal whether email exists (security best practice)
      $_SESSION["info"][] = "If this email exists, a reset link has been sent";
  }
  header("Location: ../../?login");
  exit();
}else if (isset($_GET['action']) && $_GET['action'] == 'updatePassword') {
    $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
    $password = !empty($_POST['password']) ? trim($_POST['password']) : null;
    $repassword = !empty($_POST['repassword']) ? trim($_POST['repassword']) : null;

    if (empty($email) || empty($password) || empty($repassword)) {
        $_SESSION["error"][] = 'All fields are required';
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }

    if ($password !== $repassword) {
        $_SESSION["error"][] = 'Passwords do not match';
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }

    if (strlen($password) < 6) {
        $_SESSION["error"][] = 'Password must be at least 6 characters';
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }

    $inst = new Instructor();
    if (!$inst->checkEmail($email)) {
        $_SESSION["error"][] = 'Email not found';
        header('Location: ' . $_SERVER['HTTP_REFERER']);
        exit();
    }

    $inst->updatePassword($email, $password);
    $_SESSION["info"][] = 'Password updated successfully';
    header('Location: ' . $_SERVER['HTTP_REFERER']);
    exit();
}else if (isset($_GET['action']) && $_GET['action'] == 'updateInfo'){
    $name = !empty($_POST['profname']) ? trim($_POST['profname']) : null;
    $email = !empty($_POST['email']) ? trim($_POST['email']) : null;
    $phonenum = !empty($_POST['phonenum']) ? trim($_POST['phonenum']) : null;
    if (!(preg_match('/^[0-9]+$/', $phonenum) and strlen($phonenum) == 11)) {
      $_SESSION["error"][] = 'Phone Number Is not valid';
    }
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
      $_SESSION["error"][] = "Email format is not valid";
    }
    if(empty($_SESSION["error"])){
      $inst = new instructor();
      $inst->updateInfo($name,$email,$phonenum);
      $mydata = $inst->getByEmail($email);
      $_SESSION['mydata']= $mydata;
    }
    header('Location: ' . $_SERVER['HTTP_REFERER']);
  
  }