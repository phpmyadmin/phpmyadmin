<?php
/**
 * Server create and edit view
 *
 * @package    phpMyAdmin-setup
 * @author     Piotr Przybylski <piotrprz@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl.html GNU GPL 2.0
 * @version    $Id$
 */

if (!defined('PHPMYADMIN')) {
    exit;
}

/**
 * Core libraries.
 */
require_once './setup/lib/Form.class.php';
require_once './setup/lib/FormDisplay.class.php';
require_once './setup/lib/form_processing.lib.php';

$mode = filter_input(INPUT_GET, 'mode');
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$cf = ConfigFile::getInstance();
$server_exists = !empty($id) && $cf->get("Servers/$id") !== null;

if ($mode == 'edit' && $server_exists) {
    $page_title = $GLOBALS['strSetupServersEdit']
        . ' ' . $id . ' <small>(' . $cf->getServerDSN($id) . ')</small>';
} elseif ($mode == 'remove' && $server_exists) {
    $cf->removeServer($id);
    header('Location: index.php');
    exit;
} elseif ($mode == 'revert' && $server_exists) {
    // handled by process_formset()
} else {
    $page_title = $GLOBALS['strSetupServersAdd'];
    $id = 0;
}
?>
<h2><?php echo $page_title ?></h2>
<?php
$form_display = new FormDisplay();
$form_display->registerForm('Server', $id);
$form_display->registerForm('Server_login_options', $id);
$form_display->registerForm('Server_config', $id);
$form_display->registerForm('Server_pmadb', $id);
process_formset($form_display);
?>
