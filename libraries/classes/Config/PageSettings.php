<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Page-related settings
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin\Config;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\FormDisplay;
use PhpMyAdmin\Config\Forms\Page\PageFormList;
use PhpMyAdmin\Core;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\UserPreferences;

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
     * @var UserPreferences
     */
    private $userPreferences;

    /**
     * Constructor
     *
     * @param string $formGroupName The name of config form group to display
     * @param string $elemId        Id of the div containing settings
     */
    public function __construct($formGroupName, $elemId = null)
    {
        $this->userPreferences = new UserPreferences();

        $formClass = PageFormList::get($formGroupName);
        if ($formClass === null) {
            return;
        }

        if (isset($_REQUEST['printview']) && $_REQUEST['printview'] == '1') {
            return;
        }

        if (! empty($elemId)) {
            $this->_elemId = $elemId;
        }
        $this->_groupName = $formGroupName;

        $cf = new ConfigFile($GLOBALS['PMA_Config']->base_settings);
        $this->userPreferences->pageInit($cf);

        $formDisplay = new $formClass($cf);

        // Process form
        $error = null;
        if (isset($_POST['submit_save'])
            && $_POST['submit_save'] == $formGroupName
        ) {
            $this->_processPageSettings($formDisplay, $cf, $error);
        }

        // Display forms
        $this->_HTML = $this->_getPageSettingsDisplay($formDisplay, $error);
    }

    /**
     * Process response to form
     *
     * @param FormDisplay  $formDisplay Form
     * @param ConfigFile   $cf          Configuration file
     * @param Message|null $error       Error message
     *
     * @return void
     */
    private function _processPageSettings(&$formDisplay, &$cf, &$error)
    {
        if ($formDisplay->process(false) && ! $formDisplay->hasErrors()) {
            // save settings
            $result = $this->userPreferences->save($cf->getConfigArray());
            if ($result === true) {
                // reload page
                $response = Response::getInstance();
                Core::sendHeaderLocation(
                    $response->getFooter()->getSelfUrl()
                );
                exit;
            } else {
                $error = $result;
            }
        }
    }

    /**
     * Store errors in _errorHTML
     *
     * @param FormDisplay  $formDisplay Form
     * @param Message|null $error       Error message
     *
     * @return void
     */
    private function _storeError(&$formDisplay, &$error)
    {
        $retval = '';
        if ($error) {
            $retval .= $error->getDisplay();
        }
        if ($formDisplay->hasErrors()) {
            // form has errors
            $retval .= '<div class="error config-form">'
                . '<b>' . __(
                    'Cannot save settings, submitted configuration form contains '
                    . 'errors!'
                ) . '</b>'
                . $formDisplay->displayErrors()
                . '</div>';
        }
        $this->_errorHTML = $retval;
    }

    /**
     * Display page-related settings
     *
     * @param FormDisplay $formDisplay Form
     * @param Message     $error       Error message
     *
     * @return string
     */
    private function _getPageSettingsDisplay(&$formDisplay, &$error)
    {
        $response = Response::getInstance();

        $retval = '';

        $this->_storeError($formDisplay, $error);

        $retval .= '<div id="' . $this->_elemId . '">';
        $retval .= '<div class="page_settings">';
        $retval .= $formDisplay->getDisplay(
            true,
            true,
            false,
            $response->getFooter()->getSelfUrl(),
            [
                'submit_save' => $this->_groupName,
            ]
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
        $object = new PageSettings('Navi', 'pma_navigation_settings');

        $response = Response::getInstance();
        $response->addHTML($object->getErrorHTML());
        return $object->getHTML();
    }
}
