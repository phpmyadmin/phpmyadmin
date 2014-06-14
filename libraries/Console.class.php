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

            // Console toolbar
            $retval .= '<div class="toolbar collapsed">';

            $retval .= '<div class="switch_button console_switch">';
            $retval .= PMA_Util::getImage('console.png', __('SQL Query Console'));
            $retval .= '<span>' . __('Console') . '</span></div>';

            $retval .= '<div class="button clear"><span>'
                    . __('Clear') .'</span></div>';

            $retval .= '<div class="button history"><span>'
                    . __('History') .'</span></div>';

            $retval .= '<div class="button options"><span>'
                    . __('Options') .'</span></div>';

            $retval .= '<div class="button bookmarks"><span>'
                    . __('Bookmarks') .'</span></div>';

            $retval .= '</div>'; // Toolbar end

            // Console messages
            $retval .= '<div class="content">';
            $retval .= '<div class="message_container">'
                    .  '<div class="message welcome"><span>'
                    .  __('Press') . ' Ctrl+Enter ' . __('to Execute query')
                    .  '<!--[if lte IE 7]><br /><span style="color: red;">'
                    .  __('Sorry, phpMyAdmin console doesn\'t support')
                    .  ' Internet Explorer 7 ' . __('or lower version') . '</span><![endif]-->'
                    .  '</span></div></div>';

            $retval .= '<div class="query_input"><span id="query_input"></span></div>'
                    .  '</div>'; // Messages end

            $retval .= '<div class="templates">'
            // Templates for console message actions
                    .  '<span class="action collapse">' . __('Collapse') . '</span>'
                    .  '<span class="action expand">' . __('Expand') . '</span>'
                    .  '<span class="action requery">' . __('Requery') . '</span>'
                    .  '<span class="action reedit">' . __('Reedit') . '</span>'
                    .  '<span class="action bookmark">' . __('Bookmark') . '</span>'
                    .  '<span class="text query_time"> ' . __('Queried time') . ': </span>'
                    .  '</div>';

            // Dark the console while other cards cover it
            $retval .= '<div class="mid_layer"></div>';

            // Bookmarks card:
            $retval .= '<div class="card" id="pma_bookmarks">';
            $retval .= '<div class="toolbar">'
                    .  '<div class="switch_button"><span>' . __('Bookmarks') . '</span></div>';

            $retval .= '<div class="button refresh"><span>'
                    . __('Refresh') .'</span></div>';

            $retval .= '</div><div class="content"></div>';
            $retval .= '</div>'; // Bookmarks card

            // Options card:
            $retval .= '<div class="card" id="pma_console_options">';
            $retval .= '<div class="toolbar">'
                    .  '<div class="switch_button"><span>' . __('Options') . '</span></div>';

            $retval .= '<div class="button refresh"><span>'
                    . __('Set default') .'</span></div>';

            $retval .= '</div><div class="content"></div>';
            $retval .= '</div>'; // Options card

            $retval .= '</div></div>'; // #console and #pma_console_container ends
        }
        return $retval;
    }

}
