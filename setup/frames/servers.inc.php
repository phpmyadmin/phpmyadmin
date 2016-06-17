<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Server create and edit view
 *
 * @package PhpMyAdmin-Setup
 */

use PMA\libraries\config\ConfigFile;
use PMA\libraries\config\FormDisplay;
use PMA\libraries\URL;

if (!defined('PHPMYADMIN')) {
    exit;
}

/**
 * Core libraries.
 */
require_once './setup/lib/form_processing.lib.php';

require './libraries/config/setup.forms.php';

$mode = isset($_GET['mode']) ? $_GET['mode'] : null;
$id = PMA_isValid($_GET['id'], 'numeric') ? intval($_GET['id']) : null;

/** @var ConfigFile $cf */
$cf = $GLOBALS['ConfigFile'];
$server_exists = !empty($id) && $cf->get("Servers/$id") !== null;

if ($mode == 'edit' && $server_exists) {
    $page_title = __('Edit server')
        . ' ' . $id
        . ' <small>(' . htmlspecialchars($cf->getServerDSN($id)) . ')</small>';
} elseif ($mode == 'remove' && $server_exists) {
    $cf->removeServer($id);
    header('Location: index.php' . URL::getCommonRaw());
    exit;
} elseif ($mode == 'revert' && $server_exists) {
    // handled by process_formset()
} else {
    $page_title = __('Add a new server');
    $id = 0;
}
if (isset($page_title)) {
    echo '<h2>' , $page_title . '</h2>';
}
$form_display = new FormDisplay($cf);
foreach ($forms['Servers'] as $form_name => $form) {
    $form_display->registerForm($form_name, $form, $id);
}
PMA_Process_formset($form_display);
