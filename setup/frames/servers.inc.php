<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server create and edit view
 *
 * @package PhpMyAdmin-Setup
 */

if (!defined('PHPMYADMIN')) {
    exit;
}

/**
 * Core libraries.
 */
require_once './libraries/config/Form.class.php';
require_once './libraries/config/FormDisplay.class.php';
require_once './setup/lib/form_processing.lib.php';

require './libraries/config/setup.forms.php';

$mode = isset($_GET['mode']) ? $_GET['mode'] : null;
$id = PMA_isValid($_GET['id'], 'numeric') ? $_GET['id'] : null;

$cf = $GLOBALS['ConfigFile'];
$server_exists = !empty($id) && $cf->get("Servers/$id") !== null;

if ($mode == 'edit' && $server_exists) {
    $page_title = __('Edit server')
        . ' ' . $id
        . ' <small>(' . htmlspecialchars($cf->getServerDSN($id)) . ')</small>';
} elseif ($mode == 'remove' && $server_exists) {
    $cf->removeServer($id);
    header('Location: index.php');
    exit;
} elseif ($mode == 'revert' && $server_exists) {
    // handled by process_formset()
} else {
    $page_title = __('Add a new server');
    $id = 0;
}
if (isset($page_title)) {
    echo '<h2>' . $page_title . '</h2>';
}
$form_display = new FormDisplay($cf);
foreach ($forms['Servers'] as $form_name => $form) {
    $form_display->registerForm($form_name, $form, $id);
}
PMA_Process_formset($form_display);
?>
