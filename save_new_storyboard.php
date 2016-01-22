<?php
require_once( 'Scene.class.php' ); 
require_once( 'Storyboard.class.php' ); 
require_once( 'User.class.php' );
session_start();
try {
    require_once('PDO_connect.php');
} catch (Exception $e) {
    $error = $e->getMessage();
}
$curr_user =& $_SESSION['active_user'];
$result = $_SESSION['storyboard']->save_board($db, 'new', $_POST, $curr_user->userid);
$_SESSION['has_boards'] = true;
if ($result[0]) {
    echo 'New storyboard saved.';
} else {
    echo $result[1];
}