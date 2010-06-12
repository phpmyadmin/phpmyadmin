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

$GLOBALS['js_include'][] = 'config.js';

require_once './libraries/header.inc.php';

// Any message to display?
if (! empty($message)) {
    PMA_showMessage($message);
    unset($message);
}

$tabs = array();
$form_param = filter_input(INPUT_GET, 'form');
if (!isset($forms[$form_param])) {
    $form_param = array_shift(array_keys($forms));
}
foreach (array_keys($forms) as $form) {
    $tabs[] = array(
        'link' => 'user_preferences.php',
        'text' => PMA_ifSetOr($GLOBALS['strSetupForm_' . $form], $form), // TODO: remove ifSetOr
        'active' => $form == $form_param,
        'url_params' => array('form' => $form)
    );
}

echo PMA_generate_html_tabs($tabs, array());

$form_display = new FormDisplay();
foreach ($forms[$form_param] as $form_name => $form) {
    $form_display->registerForm($form_name, $form);
}

if (filter_input(INPUT_GET, 'mode') == 'revert') {
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
        // form has errors, show warning
        $separator = PMA_get_arg_separator('html');
        ?>
        <div class="warning">
            <h4><?php echo __('Warning') ?></h4>
            <?php echo PMA_lang('error_form') ?><br />
            <a href="?form=<?php echo $form_param ?>&amp;mode=revert"><?php echo PMA_lang('RevertErroneousFields') ?></a>
        </div>
        <?php $form_display->displayErrors() ?>
        <a class="btn" href="user_preferences.php"><?php echo PMA_lang('IgnoreErrors') ?></a>
        &nbsp;
        <a class="btn" href="?form=<?php echo $form_param ?>&amp;mode=edit"><?php echo PMA_lang('ShowForm') ?></a>
        <?php
    } else {
        // redirect
        $url_params = array('form' => $form_param);
        PMA_sendHeaderLocation($cfg['PmaAbsoluteUri'] . 'user_preferences.php'
                . PMA_generate_common_url($url_params, '&'));
        exit;
    }
}

/**
 * Displays the footer
 */
require_once './libraries/footer.inc.php';
?>