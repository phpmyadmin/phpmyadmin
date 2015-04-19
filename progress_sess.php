<?php
header("Content-Type: application/json");
session_id($_COOKIE['phpMyAdmin']);
session_start();
$result = $_SESSION['export_progress'];
$percentage = $_SESSION['percentage'];
if ($result==null)
{
	$result = '';
}
$arr = array(progress_result=>$result, percentage=>$percentage);
echo json_encode($arr);
?>