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

$formsets = array(
    'features' => array(
        'forms' => array('Import_export', 'Security', 'Sql_queries', 'Page_titles',
                         'Other_core_settings')),
    'left_frame' => array(
        'forms' => array('Left_frame', 'Left_servers', 'Left_databases', 'Left_tables')),
    'main_frame' => array(
        'forms' => array('Startup', 'Browse', 'Edit', 'Tabs', 'Sql_box')),
    'import' => array(
        'forms' => array('Import_defaults')),
    'export' => array(
        'forms' => array('Export_defaults'))
);

$formset_id = filter_input(INPUT_GET, 'formset');
$mode = filter_input(INPUT_GET, 'mode');
if (!isset($formsets[$formset_id])) {
    die('Incorrect formset, check $formsets array in setup/frames/form.inc.php');
}

$formset = $formsets[$formset_id];
if (isset($GLOBALS['strSetupFormset_' . $formset_id])) {
    echo '<h2>' . $GLOBALS['strSetupFormset_' . $formset_id] . '</h2>';
}
$form_display = new FormDisplay();
foreach ($formset['forms'] as $form_name) {
    $form_display->registerForm($form_name, $forms[$form_name]);
}
process_formset($form_display);
?>
