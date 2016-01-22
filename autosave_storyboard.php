<?php
require_once( 'Scene.class.php' ); 
require_once( 'Storyboard.class.php' ); 
require_once( 'User.class.php' );
session_start();

// back up in browser
$_SESSION['storyboard']->modify($_SESSION['boardid'], $_POST);