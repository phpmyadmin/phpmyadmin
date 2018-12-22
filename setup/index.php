<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Front controller for setup script
 *
 * @package PhpMyAdmin-Setup
 * @license https://www.gnu.org/licenses/gpl.html GNU GPL 2.0
 */
declare(strict_types=1);

use PhpMyAdmin\Core;

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', dirname(__DIR__) . DIRECTORY_SEPARATOR);
}

/**
 * Core libraries.
 */
require ROOT_PATH . 'setup/lib/common.inc.php';

if (@file_exists(CONFIG_FILE) && ! $cfg['DBG']['demo']) {
    Core::fatalError(__('Configuration already exists, setup is disabled!'));
}

$page = Core::isValid($_GET['page'], 'scalar') ? (string) $_GET['page'] : null;
$page = preg_replace('/[^a-z]/', '', $page);
if ($page === '') {
    $page = 'index';
}
if (!@file_exists(ROOT_PATH . "setup/frames/$page.inc.php")) {
    // it will happen only when entering URL by hand, we don't care for these cases
    Core::fatalError(__('Wrong GET file attribute value'));
}

// Handle done action info
$action_done = Core::isValid($_GET['action_done'], 'scalar') ? $_GET['action_done'] : null;
$action_done = preg_replace('/[^a-z_]/', '', $action_done);

Core::noCacheHeader();

?>
<!DOCTYPE HTML>
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta charset="utf-8">
<title>phpMyAdmin setup</title>
<link href="../favicon.ico" rel="icon" type="image/x-icon">
<link href="../favicon.ico" rel="shortcut icon" type="image/x-icon">
<link href="styles.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="../js/vendor/jquery/jquery.min.js"></script>
<script type="text/javascript" src="../js/vendor/jquery/jquery-ui.min.js">
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
require ROOT_PATH . 'setup/frames/menu.inc.php';
?>
</div>
<div id="page">
<?php
require ROOT_PATH . "setup/frames/$page.inc.php";
?>
</div>
</body>
</html>
