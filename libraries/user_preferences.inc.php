<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Common header for user preferences pages
 *
 * @package PhpMyAdmin
 */
if (!defined('PHPMYADMIN')) {
    exit;
}

// show server tabs
require './libraries/server_links.inc.php';

// build user preferences menu

$form_param = filter_input(INPUT_GET, 'form');
if (! isset($forms[$form_param])) {
    $forms_keys = array_keys($forms);
    $form_param = array_shift($forms_keys);
}
$tabs_icons = array(
    'Features'    => 'b_tblops.png',
    'Sql_queries' => 'b_sql.png',
    'Left_frame'  => 'b_select.png',
    'Main_frame'  => 'b_props.png',
    'Import'      => 'b_import.png',
    'Export'      => 'b_export.png');
echo '<ul id="topmenu2">';
echo PMA_generate_html_tab(
    array(
        'link' => 'prefs_manage.php',
        'text' => __('Manage your settings')
    )
) . "\n";
echo '<li>&nbsp; &nbsp;</li>' . "\n";
$script_name = basename($GLOBALS['PMA_PHP_SELF']);
foreach (array_keys($forms) as $formset) {
    $tab = array(
        'link' => 'prefs_forms.php',
        'text' => PMA_lang('Form_' . $formset),
        'icon' => $tabs_icons[$formset],
        'active' => ($script_name == 'prefs_forms.php' && $formset == $form_param));
    echo PMA_generate_html_tab($tab, array('form' => $formset)) . "\n";
}
echo '</ul><div class="clearfloat"></div>';

// show "configuration saved" message and reload navigation frame if needed
if (!empty($_GET['saved'])) {
    $message = PMA_Message::rawSuccess(__('Configuration has been saved'));
    $message->display();
}

/* debug code
$arr = ConfigFile::getInstance()->getConfigArray();
$arr2 = array();
foreach ($arr as $k => $v) {
    $arr2[] = "<b>$k</b> " . var_export($v, true);
}
$arr2 = implode(', ', $arr2);
$arr2 .= '<br />Blacklist: ' . (empty($cfg['UserprefsDisallow'])
        ? '<i>empty</i>'
        : implode(', ', $cfg['UserprefsDisallow']));
$msg = PMA_Message::notice('Settings: ' . $arr2);
$msg->display();
//*/

// warn about using session storage for settings
$cfgRelation = PMA_getRelationsParam();
if (! $cfgRelation['userconfigwork']) {
    $msg = __('Your preferences will be saved for current session only. Storing them permanently requires %sphpMyAdmin configuration storage%s.');
    $msg = PMA_sanitize(sprintf($msg, '[a@./Documentation.html#linked-tables@_blank]', '[/a]'));
    PMA_Message::notice($msg)->display();
}
