<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Front controller for setup script
 *
 * @package PhpMyAdmin-Setup
 * @license https://www.gnu.org/licenses/gpl.html GNU GPL 2.0
 */

/**
 * Core libraries.
 */
require './lib/common.inc.php';

if (file_exists(CONFIG_FILE)) {
    PMA_fatalError(__('Configuration already exists, setup is disabled!'));
}

$page = PMA_isValid($_GET['page'], 'scalar') ? $_GET['page'] : null;
$page = preg_replace('/[^a-z]/', '', $page);
if ($page === '') {
    $page = 'index';
}
if (!file_exists("./setup/frames/$page.inc.php")) {
    // it will happen only when entering URL by hand, we don't care for these cases
    PMA_fatalError(__('Wrong GET file attribute value'));
}

// Handle done action info
$action_done = PMA_isValid($_GET['action_done'], 'scalar') ? $_GET['action_done'] : null;
$action_done = preg_replace('/[^a-z_]/', '', $action_done);

PMA_noCacheHeader();

?>
<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="utf-8" />
<title>phpMyAdmin setup</title>
<link href="../favicon.ico" rel="icon" type="image/x-icon" />
<link href="../favicon.ico" rel="shortcut icon" type="image/x-icon" />
<link href="styles.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="../js/jquery/jquery.min.js"></script>
<script type="text/javascript" src="../js/jquery/jquery-ui.min.js">
</script>
<script type="text/javascript" src="ajax.js"></script>
<script type="text/javascript" src="../js/config.js"></script>
<script type="text/javascript" src="scripts.js"></script>
<script type="text/javascript" src="../js/messages.php"></script>
</head>
<body>
<h1><span class="blue">php</span><span class="orange">MyAdmin</span>  setup</h1>
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
