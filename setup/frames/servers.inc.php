<?php
/**
 * Server create and edit view
 *
 * @package PhpMyAdmin-setup
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

$mode = filter_input(INPUT_GET, 'mode');
$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);

$cf = ConfigFile::getInstance();
$server_exists = !empty($id) && $cf->get("Servers/$id") !== null;

if ($mode == 'edit' && $server_exists) {
    $page_title = __('Edit server')
        . ' ' . $id . ' <small>(' . htmlspecialchars($cf->getServerDSN($id)) . ')</small>';
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
$form_display = new FormDisplay();
foreach ($forms['Servers'] as $form_name => $form) {
    $form_display->registerForm($form_name, $form, $id);
}
process_formset($form_display);
?>
