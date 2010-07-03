<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * User preferences page
 *
 * @package phpMyAdmin
 */

/**
 * Gets some core libraries and displays a top message if required
 */
require_once './libraries/common.inc.php';
require_once './libraries/user_preferences.lib.php';
require_once './libraries/config/config_functions.lib.php';
require_once './libraries/config/messages.inc.php';
require_once './libraries/config/ConfigFile.class.php';
require_once './libraries/config/Form.class.php';
require_once './libraries/config/FormDisplay.class.php';
require './libraries/config/user_preferences.forms.php';

$GLOBALS['js_include'][] = 'config.js';

require_once './libraries/header.inc.php';

// build tabs
$tabs = array();
$form_param = filter_input(INPUT_GET, 'form');
if (!isset($forms[$form_param])) {
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
foreach (array_keys($forms) as $formset) {
    $tabs[] = array(
        'link' => 'user_preferences.php',
        'text' => PMA_lang('Form_' . $formset),
        'icon' => $tabs_icons[$formset],
        'active' => $formset == $form_param,
        'url_params' => array('form' => $formset));
}

echo PMA_generate_html_tabs($tabs, array());

// show "configuration saved" message and reload navigation frame if needed
if (!empty($_GET['saved'])) {
    $message = PMA_Message::rawSuccess(__('Configuration has been saved'));
    $message->display();
    if (isset($_GET['refresh_left_frame']) && $_GET['refresh_left_frame'] == '1') {
?>
<script type="text/javascript">
if (window.parent && window.parent.frame_navigation) {
    window.parent.frame_navigation.location.reload();
}
</script>
<?php
    }
}

// handle form display and processing

$forms_all_keys = PMA_read_userprefs_fieldnames($forms);
$cf = ConfigFile::getInstance();
$cf->setAllowedKeys($forms_all_keys);
$cf->updateWithGlobalConfig($GLOBALS['PMA_Config']);

// todo: debug - remove
$arr = $cf->getConfigArray();
$arr2 = array();
foreach ($arr as $k => $v) {
    $arr2[] = "<b>$k</b> " . var_export($v, true);
}
$arr2 = implode(', ', $arr2);
$arr2 .= '<br />Blacklist: ' . (empty($cfg['UserprefsDisallow'])
        ? '<i>empty</i>'
        : implode(', ', $cfg['UserprefsDisallow']));
$msg = PMA_Message::notice('Debug: ' . $arr2);
$msg->display();

$form_display = new FormDisplay();
foreach ($forms[$form_param] as $form_name => $form) {
    $form_display->registerForm($form_name, $form);
}

if (isset($_POST['revert'])) {
    // revert erroneous fields to their default values
    $form_display->fixErrors();
    // redirect
    $url_params = array('form' => $form_param);
    PMA_sendHeaderLocation($cfg['PmaAbsoluteUri'] . 'user_preferences.php'
            . PMA_generate_common_url($url_params, '&'));
    exit;
}
if (!$form_display->process(false)) {
    // handle form view and failed POST
    $form_display->display(true, true);
} else {
    // check for form errors
    if ($form_display->hasErrors()) {
        // form has errors
        ?>
        <fieldset>
            <b><?php echo __('Submitted form contains errors') ?></b>
            <?php $form_display->displayErrors(); ?>
        </fieldset>
        <?php
        $form_display->display(true, true);
    } else {
        // save settings
        $old_settings = PMA_load_userprefs();
        $result = PMA_save_userprefs();
        if ($result === true) {
            // compute differences and check whether left frame should be refreshed
            $old_settings = isset($old_settings['config_data'])
                    ? $old_settings['config_data']
                    : array();
            $new_settings = ConfigFile::getInstance()->getConfigArray();
            $diff_keys = array_keys(array_diff_assoc($old_settings, $new_settings)
                    + array_diff_assoc($new_settings, $old_settings));
            $check_keys = array('NaturalOrder', 'MainPageIconic', 'DefaultTabDatabase');
            $check_keys = array_merge($check_keys, $forms['Left_frame']['Left_frame'],
                 $forms['Left_frame']['Left_servers'], $forms['Left_frame']['Left_databases']);
            $diff = array_intersect($check_keys, $diff_keys);
            $refresh_left_frame = !empty($diff);

            // redirect
            $url_params = array(
                'form' => $form_param,
                'saved' => 1,
                'refresh_left_frame' => $refresh_left_frame);
            PMA_sendHeaderLocation($cfg['PmaAbsoluteUri'] . 'user_preferences.php'
                    . PMA_generate_common_url($url_params, '&'));
            exit;
        } else {
            $result->display();
        }
        $form_display->display(true, true);
    }
}
$GLOBALS['error_handler']->dispAllErrors();

/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';
?>