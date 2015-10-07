<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Page-related settings
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries\config;

use PMA\libraries\Message;
use PMA\libraries\Response;

require_once 'libraries/user_preferences.lib.php';
require_once 'libraries/config/config_functions.lib.php';
require_once 'libraries/config/messages.inc.php';
require 'libraries/config/user_preferences.forms.php';
require 'libraries/config/page_settings.forms.php';

/**
 * Page-related settings
 *
 * @package PhpMyAdmin
 */
class PageSettings
{

    /**
     * Contains id of the form element
     * @var string
     */
    private $_elemId = 'page_settings_modal';

    /**
     * Name of the group to show
     * @var string
     */
    private $_groupName = '';

    /**
     * Contains HTML of errors
     * @var string
     */
    private $_errorHTML = '';

    /**
     * Contains HTML of settings
     * @var string
     */
    private $_HTML = '';

    /**
     * Constructor
     *
     * @param string $formGroupName The name of config form group to display
     * @param string $elemId        Id of the div containing settings
     */
    public function __construct($formGroupName, $elemId = null)
    {
        global $forms;
        if (empty($forms[$formGroupName])) {
            return;
        }

        if (isset($_REQUEST['printview']) && $_REQUEST['printview'] == '1') {
            return;
        }

        if (!empty($elemId)) {
            $this->_elemId = $elemId;
        }
        $this->_groupName = $formGroupName;

        $cf = new ConfigFile($GLOBALS['PMA_Config']->base_settings);
        PMA_userprefsPageInit($cf);

        $form_display = new FormDisplay($cf);
        foreach ($forms[$formGroupName] as $form_name => $form) {
            // skip Developer form if no setting is available
            if ($form_name == 'Developer'
                && !$GLOBALS['cfg']['UserprefsDeveloperTab']
            ) {
                continue;
            }
            $form_display->registerForm($form_name, $form, 1);
        }

        // Process form
        $error = null;
        if (isset($_POST['submit_save'])
            && $_POST['submit_save'] == $formGroupName
        ) {
            $this->_processPageSettings($form_display, $cf, $error);
        }

        // Display forms
        $this->_HTML = $this->_getPageSettingsDisplay($form_display, $error);
    }

    /**
     * Process response to form
     *
     * @param FormDisplay  &$form_display Form
     * @param ConfigFile   &$cf           Configuration file
     * @param Message|null &$error        Error message
     *
     * @return void
     */
    private function _processPageSettings(&$form_display, &$cf, &$error)
    {
        if ($form_display->process(false) && !$form_display->hasErrors()) {
            // save settings
            $result = PMA_saveUserprefs($cf->getConfigArray());
            if ($result === true) {
                // reload page
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit();
            } else {
                $error = $result;
            }
        }
    }

    /**
     * Store errors in _errorHTML
     *
     * @param FormDisplay  &$form_display Form
     * @param Message|null &$error        Error message
     *
     * @return void
     */
    private function _storeError(&$form_display, &$error)
    {
        $retval = '';
        if ($error) {
            $retval .= $error->getDisplay();
        }
        if ($form_display->hasErrors()) {
            // form has errors
            $retval .= '<div class="error config-form">'
                . '<b>' . __(
                    'Cannot save settings, submitted configuration form contains '
                    . 'errors!'
                ) . '</b>'
                . $form_display->displayErrors()
                . '</div>';
        }
        $this->_errorHTML = $retval;
    }

    /**
     * Display page-related settings
     *
     * @param FormDisplay &$form_display Form
     * @param Message     &$error        Error message
     *
     * @return string
     */
    private function _getPageSettingsDisplay(&$form_display, &$error)
    {
        $response = Response::getInstance();

        $retval = '';

        $this->_storeError($form_display, $error);

        $retval .= '<div id="' . $this->_elemId . '">';
        $retval .= '<div class="page_settings">';
        $retval .= $form_display->getDisplay(
            true,
            true,
            false,
            $response->getFooter()->getSelfUrl('unencoded'),
            array(
                'submit_save' => $this->_groupName
            )
        );
        $retval .= '</div>';
        $retval .= '</div>';

        return $retval;
    }

    /**
     * Get HTML output
     *
     * @return string
     */
    public function getHTML()
    {
        return $this->_HTML;
    }

    /**
     * Get error HTML output
     *
     * @return string
     */
    public function getErrorHTML()
    {
        return $this->_errorHTML;
    }

    /**
     * Group to show for Page-related settings
     * @param string $formGroupName The name of config form group to display
     * @return PageSettings
     */
    public static function showGroup($formGroupName)
    {
        $object = new PageSettings($formGroupName);

        $response = Response::getInstance();
        $response->addHTML($object->getErrorHTML());
        $response->addHTML($object->getHTML());

        return $object;
    }

    /**
     * Get HTML for navigation settings
     * @return string
     */
    public static function getNaviSettings()
    {
        $object = new PageSettings('Navi_panel', 'pma_navigation_settings');

        $response = Response::getInstance();
        $response->addHTML($object->getErrorHTML());
        return $object->getHTML();
    }
}
