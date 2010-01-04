<?php
/**
 * Front controller for setup script
 *
 * @package    phpMyAdmin-setup
 * @author     Piotr Przybylski <piotrprz@gmail.com>
 * @copyright  Copyright (c) 2008, Piotr Przybylski <piotrprz@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl.html GNU GPL 2.0
 * @version    $Id$
 */

/**
 * Core libraries.
 */
require './lib/common.inc.php';

$page = filter_input(INPUT_GET, 'page');
$page = preg_replace('/[^a-z]/', '', $page);
if ($page === '') {
    $page = 'index';
}
if (!file_exists("./setup/frames/$page.inc.php")) {
    // it will happen only when enterung URL by hand, we don't care for these cases
    die('Wrong GET file attribute value');
}

// send no-cache headers
require './libraries/header_http.inc.php';
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8" />
<title>phpMyAdmin <?php echo $_SESSION['PMA_Config']->get('PMA_VERSION'); ?> setup</title>
<link href="../favicon.ico" rel="icon" type="image/x-icon" />
<link href="../favicon.ico" rel="shortcut icon" type="image/x-icon" />
<link href="styles.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="../js/mootools.js"></script>
<script type="text/javascript" src="../js/mootools-more.js"></script>
<script type="text/javascript" src="scripts.js"></script>
</head>
<body>
<h1><span class="blue">php</span><span class="orange">MyAdmin</span> <?php echo $_SESSION['PMA_Config']->get('PMA_VERSION'); ?> setup</h1>
<div id="menu">
<?php
require './setup/frames/menu.inc.php';
?>
</div>
<div id="page">
<?php
require "./setup/frames/$page.inc.php";
?>
</div>
</body>
</html>
