<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Used to render the console of PMA's pages
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/Scripts.class.php';
require_once 'libraries/Util.class.php';

/**
 * Class used to output the console
 *
 * @package PhpMyAdmin
 */
class PMA_Console
{
    /**
     * PMA_Scripts instance
     *
     * @access private
     * @var PMA_Scripts
     */
    private $_scripts;
    /**
     * Whether to display anything
     *
     * @access private
     * @var bool
     */
    private $_isEnabled;

    /**
     * Creates a new class instance
     */
    public function __construct()
    {
        $this->_isEnabled = true;
        $this->_scripts   = new PMA_Scripts();
    }

    /**
     * Whether we are servicing an ajax request.
     * We can't simply use $GLOBALS['is_ajax_request']
     * here since it may have not been initialised yet.
     *
     * @access private
     * @var bool
     */
    private $_isAjax;

    /**
     * Set the ajax flag to indicate whether
     * we are sevicing an ajax request
     *
     * @param bool $isAjax Whether we are sevicing an ajax request
     *
     * @return void
     */
    public function setAjax($isAjax)
    {
        $this->_isAjax = ($isAjax == true);
    }

    /**
     * Disables the rendering of the footer
     *
     * @return void
     */
    public function disable()
    {
        $this->_isEnabled = false;
    }

    /**
     * Renders the console
     *
     * @access public
     * @return string
     */
    public function getDisplay()
    {
        $retval  = '';
        if(! $this->_isAjax && $this->_isEnabled) {

            $this->_scripts->addFile('codemirror/lib/codemirror.js');
            $this->_scripts->addFile('codemirror/mode/sql/sql.js');
            $this->_scripts->addFile('codemirror/addon/runmode/runmode.js');

            $this->_scripts->addFile('console.js');
            $retval .= $this->_scripts->getDisplay();
            $retval .= '<div id="pma_console_container"><div id="pma_console">';

            $retval .= '<div class="toolbar collapsed">';

            $retval .= '<div class="switch_button">';
            $retval .= PMA_Util::getImage('console.png', __('SQL Query Console'));
            $retval .= '<span class="mid_text">' . __('Console') . '</span></div>';

            $retval .= '</div>'; // Toolbar

            $retval .= '<div class="content">';
            $retval .= '<div class="message_container">';
            $retval .= '<div class="message"><span>Console message A</span></div>';
            $retval .= '<div class="message"><span>Console message B</span></div>';
            $retval .= '<div class="message"><span>Console message C</span></div>';
            $retval .= '<div class="query_input"><span id="query_input"></span></div>';

            $retval .= '</div></div></div></div>';
        }
        return $retval;
    }

}
