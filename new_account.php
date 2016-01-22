<?php 
require_once( 'Scene.class.php'); 
require_once( 'Storyboard.class.php'); 
require_once( 'User.class.php' );
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
unset($_SESSION['boardid']);
if (isset($_POST['email'])) {
    if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) { 
        $_SESSION['active_user'] = new User;
        $curr_user =& $_SESSION['active_user'];
        $success = $curr_user->make_new_account($db, $_POST);
        if (!$success[0]) {
            $error_array[] = $success[1];
            unset($_SESSION['active_user']);
        } else {
            $is_logged_in = true;
        } 
    } else {
        $error_array[] = 'Account creation failed: Please enter a valid email address.';
    }
}
$show_available = false;
if (isset($_SESSION['active_user'])) {
    $curr_user =& $_SESSION['active_user'];
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
    
    <?php

if ($is_logged_in) {
    echo '<h2>Account creation successful.</h2>';
    echo '<p>You are now logged in. <a href="/storyboardapp/">Proceed to the main page.</a></p>';
} else {
    
        $em = '';
        $fn = '';
        $ln = '';
        $co = '';
        if ( isset( $_POST['email'] ) ) {
            $em = $_POST['email'];
        }
        if ( isset( $_POST['firstname'] ) ) {
            $fn = $_POST['firstname'];
        }
        if ( isset( $_POST['lastname'] ) ) {
            $ln = $_POST['lastname'];
        }
        if ( isset( $_POST['company'] ) ) {
            $co = $_POST['company'];
        }
            ?>

        <form method="post" class="new-acct-form">
<label>Email: <input type="email" required id="email" name="email" placeholder="email address" value="<?php echo $em; ?>"></label>
            <label>First Name: <input type="text" required id="firstname" name="firstname" placeholder="first name" value="<?php echo $fn; ?>"></label>
        <label>Last Name: 
        <input type="text" required id="lastname" name="lastname" placeholder="last name" value="<?php echo $ln; ?>"></label>
        <label>Company (optional): 
        <input type="text" id="company" name="company" placeholder="company" value="<?php echo $co; ?>"></label>
        <label>Password: <input type="password" required id="password" name="password"></label>
        <label>Repeat Password: <input type="password" required id="password_verify" name="password_verify"></label>
        <button id="user-create-button" class="user-create-button">Create Account</button>
        </form>
        
        <?php } ?>
        
    </div>
<script src="storyboard.js"></script>
    </body>
</html>