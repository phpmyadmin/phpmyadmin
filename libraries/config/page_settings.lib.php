<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Page related settings
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';
require_once 'libraries/user_preferences.lib.php';
require_once 'libraries/config/config_functions.lib.php';
require_once 'libraries/config/messages.inc.php';
require_once 'libraries/config/ConfigFile.class.php';
require_once 'libraries/config/Form.class.php';
require_once 'libraries/config/FormDisplay.class.php';
require 'libraries/config/user_preferences.forms.php';
require 'libraries/config/page_settings.forms.php';

/**
 * Page related settings
 *
 * @package PhpMyAdmin
 */
class PMA_PageSettings {

    /**
     * Is class initiated
     * @var initiated
     */
    static $initiated = false;

    /**
     * Constructor
     *
     * @param string $formGroupName The name of config form group to display
     */
    function __construct($formGroupName) {
        global $forms;

        if (PMA_PageSettings::$initiated) {
            return false;
        }

        if (empty($forms[$formGroupName])) {
            return false;
        }
        PMA_PageSettings::$initiated = true;

        $cf = new ConfigFile($GLOBALS['PMA_Config']->base_settings);
        PMA_userprefsPageInit($cf);

        $form_display = new FormDisplay($cf);
        foreach ($forms[$formGroupName] as $form_name => $form) {
            // skip Developer form if no setting is available
            if ($form_name == 'Developer' && !$GLOBALS['cfg']['UserprefsDeveloperTab']) {
                continue;
            }
            $form_display->registerForm($form_name, $form, 1);
        }

        // Process form
        $error = null;
        $this->_processPageSettings($form_display, $cf, $error);

        // Display forms
        $this->_displayPageSettings($form_display, $error);
    }

    /**
     * Process response to form
     *
     * @return void
     */
    private function _processPageSettings(&$form_display, &$cf, &$error) {
        if ($form_display->process(false) && !$form_display->hasErrors()) {
            // save settings
            $result = PMA_saveUserprefs($cf->getConfigArray());
            if ($result === true) {
                // reload config
                $GLOBALS['PMA_Config']->loadUserPreferences();
                $hash = ltrim(filter_input(INPUT_POST, 'tab_hash'), '#');
                header('Location: '.$_SERVER['REQUEST_URI']);
                exit();
            } else {
                $error = $result;
            }
        }
    }

    /**
     * Display page related settings
     *
     * @return void
     */
    private function _displayPageSettings(&$form_display, &$error) {
        $response = PMA_Response::getInstance();
        $header   = $response->getHeader();
        $scripts  = $header->getScripts();
        $scripts->addFile('config.js');
        $scripts->addFile('page_settings.js');

        if ($error) {
            $response->addHTML($error->getDisplay());
        }
        if ($form_display->hasErrors()) {
            // form has errors
            $response->addHTML(
                '<div class="error config-form">'
                . '<b>' . __('Cannot save settings, submitted configuration form contains errors!') . '</b>'
                . $form_display->displayErrors()
                . '</div>'
            );
        }

        $response->addHTML('<div class="page_settings_modal">');
        $response->addHTML($form_display->getDisplay(
            true,
            true,
            false,
            $response->getFooter()->getSelfUrl('unencoded'),
            array(
                'submit_save' => 'Submit'
            )
        ));
        $response->addHTML('</div>');
    }
}

/**
 * Wrapper for PMA_PageSettings class
 *
 * @param string $formGroupName The name of config form group to display
 */
function PMA_PageSettings($formGroupName) {
    if (!PMA_PageSettings::$initiated) {
        return new PMA_PageSettings($formGroupName);
    }
    return false;
}
