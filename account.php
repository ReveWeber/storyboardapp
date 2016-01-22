<?php 
require_once( 'Scene.class.php' ); 
require_once( 'Storyboard.class.php' ); 
require_once( 'User.class.php' );
session_start(); 
if ($_GET['logout'] == true) {
    // later: unlock shared boards if needed
    $_SESSION = array();
}
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
$is_logged_in = true;
if (!$_SESSION['active_user']) {
    $is_logged_in = false;
    if (isset($_POST['email'])) {
        if (filter_var($_POST['email'], FILTER_VALIDATE_EMAIL)) { 
            $email = $_POST['email'];
            $password = $_POST['password'];
            $_SESSION['active_user'] = new User;
            $curr_user =& $_SESSION['active_user'];
            $curr_user->modify( ['email'=>$email] );
            $success = $curr_user->login_user($password, $db);
            if (!$success[0]) {
                $error_array[] = $success[1];
                unset($_SESSION['active_user']);
            } else {
                $is_logged_in = true;
            } 
        } else {
            $error_array[] = 'Login failed: Please enter a valid email address.';
        }
    }
}
$show_available = false;
if (isset($_SESSION['active_user'])) {
    $curr_user =& $_SESSION['active_user'];
    if (isset($_POST['firstname'])) {
        $change_info = $curr_user->save_new_info($db, $_POST);
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
    
    <?php

if (isset($change_info)) {
    if($change_info[0]) {
        $changed_data = implode(', ', $change_info[1]);
        echo "<h2>Successfully changed $changed_data.</h2>";
    } else {
        echo "<h2>$change_info[1]</h2>";
    }
}
     ?>
    
        <?php if (isset($curr_user)) { ?>
    <div class="account-info-wrapper">    
        <div class="account-info-form" id="account-info-form">
            <?php $curr_user->account_info_changeable(); ?>
        </div><!-- .account-info-form -->
    </div><!-- .account-info-wrapper -->
    
<?php } else { include('login_form.php'); } ?>
    </div><!-- .main-body-wrapper -->
    
    
    
<script src="storyboard.js"></script>
</body>
</html>