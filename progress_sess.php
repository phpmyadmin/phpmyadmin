<?php
include_once 'libraries/common.inc.php';
session_id($_COOKIE['phpMyAdmin']);
if(!isset($_SESSION))
{
    session_start();
}
if(isset($_REQUEST['done_status'])) {
    $_SESSION['export_progress'] = "";
    $_SESSION['percentage']="";
}
else {
    if(isset($_SESSION['export_progress'])) {
        $result = $_SESSION['export_progress'];
    }
    else {
        $result = '';
    }
    if(isset($_SESSION['percentage'])) {
        $percentage = $_SESSION['percentage'];
    }
    else {
        $percentage = '';
    }
    $response = PMA_Response::getInstance();
    $response->disable();
    $response->addJSON("progress_result", $result);
    $response->addJSON("percentage", $percentage);
}
session_write_close();
?>
