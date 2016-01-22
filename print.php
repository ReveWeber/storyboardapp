<?php 
require_once( 'Scene.class.php'); 
require_once( 'Storyboard.class.php'); 
require_once( 'User.class.php' );
session_start();
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
    <link rel="stylesheet" type="text/css" href="css/jquery-ui.min.css" media="all">
    <link rel="stylesheet" type="text/css" href="css/wColorPicker.min.css" media="all">
    <link rel="stylesheet" type="text/css" href="css/wPaint.min.css" media="all">
    <link rel="stylesheet" type="text/css" href="css/printing-page.css" media="all">
    <title>
        Aquilino Arts Storyboard Alpha Version
    </title>
</head>

<body>
    <h1 id="no-js-warning" class="no-js-warning">JavaScript is required for use of this program.</h1>
    
    <?php if (isset($_SESSION['active_user'])) { ?>
    <div class="storyboard-wrapper">
        <div class="attribution">Made using Aquilino Arts' Storyboard App, <span class="text-url">aquilinoarts.com/storyboardapp/</span>.</div>
        <?php $_SESSION['storyboard']->printable_view(); ?>
    </div><!-- .storyboard-wrapper -->
    <?php } else { include('login_form.php'); } ?>

    
        <script src="storyboard.js"></script>

</body>

</html>