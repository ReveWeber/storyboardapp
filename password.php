<?php 
require_once( 'Scene.class.php'); 
require_once( 'Storyboard.class.php'); 
require_once( 'User.class.php' );
require_once( 'pw_recovery_fns.php' );
session_start();
try { 
    require_once( 'PDO_connect.php'); 
} catch (Exception $e) { 
    $error=$e->getMessage(); 
} 
$error_array = array();
if(!$db) {
    $error_mss = '<p>There has been an error connecting to the database';
    if (isset($error)) {
        $error_mss .= ": $error</p>";
    } else {
        $error_mss .= '.</p>';
    }
    $error_array[] = $error_mss;
}
$password_reset = false;
$email_sent = false;
$email_verified = false;
if (isset($_SESSION['key_confirmed'])) {
    $email_verified = true;
} else if (isset($_GET['key'])) {
    // check key against db
    // if good set $email_verified to true and mark in db as used
    // also get email of request to double check, put in session
    $checked_request = check_request($_GET['key'], $db);
    // if not good throw error.
    // this will include expired keys
    if ($checked_request[0]) {
        // set key variable in session so typos don't force new request
        $_SESSION['key_confirmed'] = true;
        $email_verified = true;
    } else {
        $error_array[] = $checked_request[1];
    }
}
if (isset($_POST['email'])) {
    if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) { 
        // check whether email belongs to an account
        // if so create request in DB and send password email
        $email_generation = generate_request($_POST['email'], $db);
        // set $email_sent to true
        // if not throw error.
        if ($email_generation[0]) {
            $email_sent = true;
        } else {
            $error_array[] = $email_generation[1];
        }
    } else {
        $error_array[] = 'Error: Please enter a valid email address.';
    }
}
if (isset($_POST['repeat_email'])) {
    // check that passwords match and are nonempty
    $password = $_POST['password'];
    $pass_ver = $_POST['password_verify'];
    $email = $_POST['repeat_email'];
    $is_error = false;
    if ($email != $_SESSION['request_email']) {
        $error_array[] = 'Error: Email entered is not email of request.';
        $is_error = true;
    } 
    if ($password != $pass_ver) {
        $error_array[] = 'Error: Password entries do not match.';
        $is_error = true;
    }
    if ($password == '' || $pass_ver == '' || $email == '') {
        $error_array[] = 'Error: All fields must be filled in.';
        $is_error = true;
    }
    if (!$is_error) {
        // reset password for user, set $password_reset to true,
        // unset session key variable because otherwise things are weird.
        $password_result = reset_password($email, $password, $db);
        if ($password_result[0]) {
            $password_reset = true;
            unset($_SESSION['key_confirmed']);
        } else {
            $error_array[] = $password_result[1];
        }
    }
}
?><!DOCTYPE html>
<html>
<head>
    <script src="js/html5shiv.min.js"></script>
    <script src="js/html5shiv-printshiv.min.js"></script>
    <script src="js/jquery-1.11.2.min.js"></script>
    <script src="js/jquery-ui.min.js"></script>
    <script src="js/wColorPicker.min.js"></script>
    <!-- following is not min because I added a path fixer function! -->
    <script src="js/wPaint.js"></script>
    <script src="js/wPaint.utils.js"></script>
    <script src="js/plugins/file/wPaint.menu.main.file.min.js"></script>
    <script src="js/plugins/main/wPaint.menu.main.min.js"></script>
    <script src="js/plugins/shapes/wPaint.menu.main.shapes.min.js"></script>
    <script src="js/plugins/text/wPaint.menu.text.min.js"></script>
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.4.0/css/font-awesome.min.css">
    <link rel="stylesheet" type="text/css" href="css/jquery-ui.min.css" media="all">
    <link rel="stylesheet" type="text/css" href="css/wColorPicker.min.css" media="all">
    <link rel="stylesheet" type="text/css" href="css/wPaint.min.css" media="all">
    <link rel="stylesheet" type="text/css" href="css/style.css" media="screen">
    <link rel="stylesheet" type="text/css" href="css/print.css" media="print">
    <title>
        Aquilino Arts Storyboard Alpha Version
    </title>
</head>

<body>
    
    <?php include("header-bar.php"); ?>
    
    <div class="main-body-wrapper">
        
    <div class="error-area">
    <h2 id="no-js-warning">JavaScript is required for use of this program.</h2>
    <?php 
        if (count($error_array) > 0 ) {
            reset($error_array);
            while (list($key, $val) = each($error_array)) {
                echo "<h2>$val</h2>";
            }
        }
    ?>
        </div>
        
        <div class="password-reset-wrapper">
    
    <?php

        $em = '';
        if ( isset( $_POST['email'] ) ) {
            $em = $_POST['email'];
        }
        if (isset ($_POST['repeat_email'])) {
            $em = $_POST['repeat_email'];
        }

if ($password_reset) {
    echo '<h2>Password reset successful.</h2>';
    echo '<p><a href="/storyboardapp/">Proceed to the main page to log in.</a></p>';
} else if ($email_verified) {
    ?>
        <h2>Password Reset Step 2</h2>
<form method="post" class="password-recovery-form">
                <label>Email: <input type="email" required id="repeat_email" name="repeat_email" placeholder="email address" value="<?php echo $em; ?>"></label>
        <label>New Password: <input type="password" required id="password" name="password"></label>
        <label>Repeat Password: <input type="password" required id="password_verify" name="password_verify"></label>
        <button id="password-create-button" class="password-recovery-button">Set Password</button>
        </form>
        <?php
} else if ($email_sent) {
    ?>
        <h2>Password reset email sent. Please click or paste the link in the email.</h2>
        <?php
} else {
            ?>
<h2>Password Reset Step 1</h2>
        
        <h3>You will be sent an email with a password reset link. Please note this link will expire in one hour.</h3>
        <form method="post" class="password-recovery-form">
<label>Email: <input type="email" required id="email" name="email" placeholder="email address" value="<?php echo $em; ?>"></label>
            <button id="password-recovery-button" class="password-recovery-button">Request Password Reset Email</button>
        </form>
            
        
        <?php } ?>
        
    </div><!-- .password-reset-wrapper -->
        </div><!-- .main-body-wrapper -->
<script src="storyboard.js"></script>
    </body>
</html>