<?php
/* $Id$ */

/* ---- DEFINE VARIABLES & CONSTANTS ---- */
// Overview:
//	MYSQL_MAJOR_VERSION	(double)	- eg: 3.23
//	MYSQL_MINOR_VERSION	(double)	- eg: 39
//	PHPMYADMIN_VERSION	(string)	-	PHPMYADMIN VERSION STRING
//	PMA_INT_VERSION			(int)		- (eg: 30017 instead of 3.0.17 or 40006 instead of 4.0.6RC3)
//	PMA_WINDOWS					(bool)	- mark if phpMyAdmin running on windows server


define("PHPMYADMIN_VERSION", "2.2.0rc1"); 

if (!ereg("([0-9]).([0-9]).([0-9])", phpversion(), $match))
	$result=ereg("([0-9]).([0-9])",phpversion(),$match);
if (isset($match) && !empty($match[1]))
{
	if (!isset($match[2])) $match[2]=0;
	if (!isset($match[3])) $match[3]=0;
	define ("PMA_INT_VERSION", (int)sprintf("%d%02d%02d",$match[1],$match[2],$match[3]));
	unset ($match);
}
else define ("PMA_INT_VERSION", false);

if (defined("PHP_OS") && eregi("win", PHP_OS)) define ("PMA_WINDOWS", true);
else define ("PMA_WINDOWS", false);

$result = mysql_query("SELECT VERSION() AS version") or mysql_die();
$row = mysql_fetch_array($result);
define("MYSQL_MAJOR_VERSION", (double)substr($row["version"], 0, 4));
define("MYSQL_MINOR_VERSION", (double)substr($row["version"], 5));

/* ------------------------- */

?>
