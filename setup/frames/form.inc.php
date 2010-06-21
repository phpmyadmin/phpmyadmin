<?php
/**
 * Form edit view
 *
 * @package    phpMyAdmin-setup
 * @license    http://www.gnu.org/licenses/gpl.html GNU GPL 2.0
 * @version    $Id$
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

$formset_id = filter_input(INPUT_GET, 'formset');
$mode = filter_input(INPUT_GET, 'mode');
if (!isset($forms[$formset_id])) {
    die('Incorrect formset, check $formsets array in setup/frames/form.inc.php');
}

if (isset($GLOBALS['strSetupFormset_' . $formset_id])) {
    echo '<h2>' . $GLOBALS['strSetupFormset_' . $formset_id] . '</h2>';
}
$form_display = new FormDisplay();
foreach ($forms[$formset_id] as $form_name => $form) {
    $form_display->registerForm($form_name, $form);
}
process_formset($form_display);
?>