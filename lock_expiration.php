<?php
require_once( 'Scene.class.php' ); 
require_once( 'Storyboard.class.php' ); 
require_once( 'User.class.php' );
session_start();
if (isset($_SESSION['storyboard'])) {
    echo $_SESSION['storyboard']->time_to_lock_expiry();
} else {
    echo 0;
}