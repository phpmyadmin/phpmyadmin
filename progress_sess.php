<?php
	header("Content-Type: application/json");
	session_id($_COOKIE['phpMyAdmin']);
	session_start();
	$result = $_SESSION['export_progress'];
	if ($result==null) 
	{
		$result = '';
	}
	$arr = array(progress_result=>$result);
	echo json_encode($arr);
?>