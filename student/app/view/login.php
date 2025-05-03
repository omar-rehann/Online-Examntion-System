<?php
if (!defined('NotDirectAccess')){
	die('Direct Access is not allowed to this page');
}
require_once 'header.php';
?>

<body class="login-body" style="padding:20px">
	<div class="preloader"></div>
	<div class="login-wrap">
	<div class="login-html rounded">
    <input id="tab-1" type="radio" name="tab" class="sign-in" checked><label for="tab-1" class="tab">Login</label>
    <input id="tab-2" type="radio" name="tab" class="for-pwd"><label for="tab-2" class="tab">Forgot Password</label>
    <div class="login-form">
        <!-- Loginn -->
        <form class="sign-in-htm" id="loginForm" action="app/controller/student.inc.php?action=login" method="post">
            <div class="group">
                <label for="id" class="label mt-3 text-light">Student ID</label>
                <input type="text" name="id" class="input input-holder" title="Please Enter Your Real Student ID" placeholder="20*******" required pattern="\b20\w[0-9]*">
            </div>
            <div class="group">
                <label for="password" class="label mt-3 text-light">Password</label>
                <input type="password" name="password" class="input input-holder" placeholder="Enter Password">
            </div>
            <div class="group">
                <input type="submit" class="button" value="Sign In">
            </div>
        </form>
        <!-- Forgot Password -->
        <form class="for-pwd-htm" id="requestResetForm" action="app/controller/student.inc.php?action=requestReset" method="post">
            <div class="group">
                <label for="email" class="label text-light mt-3">Email Address</label>
                <input type="email" name="email" class="input input-holder" placeholder="Email Address" minlength="8" required>
            </div>
            <div class="group">
                <input type="submit" class="button" value="Reset Password">
            </div>
            <div class="hr"></div>
        </form>
        <div class="hr"></div>
    </div>
</div>
</div>
</body>
<?php
define('ContainsBackground', true);
require_once 'footer.php';
?>
