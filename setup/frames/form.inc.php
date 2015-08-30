<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Form edit view
 *
 * @package PhpMyAdmin-Setup
 */

use PMA\libraries\config\FormDisplay;

if (!defined('PHPMYADMIN')) {
    exit;
}

/**
 * Core libraries.
 */
require_once './setup/lib/form_processing.lib.php';

require './libraries/config/setup.forms.php';

$formset_id = isset($_GET['formset']) ? $_GET['formset'] : null;
$mode = isset($_GET['mode']) ? $_GET['mode'] : null;
if (! isset($forms[$formset_id])) {
    PMA_fatalError(__('Incorrect formset, check $formsets array in setup/frames/form.inc.php!'));
}

if (isset($GLOBALS['strConfigFormset_' . $formset_id])) {
    echo '<h2>' . $GLOBALS['strConfigFormset_' . $formset_id] . '</h2>';
}
$form_display = new FormDisplay($GLOBALS['ConfigFile']);
foreach ($forms[$formset_id] as $form_name => $form) {
    $form_display->registerForm($form_name, $form);
}
PMA_Process_formset($form_display);
