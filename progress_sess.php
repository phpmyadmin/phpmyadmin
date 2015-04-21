<?php
header("Content-Type: application/json");
session_id($_COOKIE['phpMyAdmin']);
if(!isset($_SESSION))
{
    session_start();
}
if(isset($_SESSION['export_progress']))
{
$result = $_SESSION['export_progress'];
}
else
{
$result = '';
}
if(isset($_SESSION['percentage']))
{
$percentage = $_SESSION['percentage'];
}
else
{
$percentage = '';
}
$arr = array('progress_result'=>$result, 'percentage'=>$percentage);
echo json_encode($arr);
session_write_close();
?>
