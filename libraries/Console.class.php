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
     * Renders the console input line
     *
     * @access private
     * @return string
     */
    private function _getForm()
    {
        $retval  = '';

        $table  = '';
        $db     = '';
        if (! strlen($GLOBALS['db'])) {
            // prepare for server related
            $goto   = empty($GLOBALS['goto']) ?
                        'server_sql.php' : $GLOBALS['goto'];
        } elseif (! strlen($GLOBALS['table'])) {
            // prepare for db related
            $db     = $GLOBALS['db'];
            $goto   = empty($GLOBALS['goto']) ?
                        'db_sql.php' : $GLOBALS['goto'];
        } else {
            $table  = $GLOBALS['table'];
            $db     = $GLOBALS['db'];
            $goto   = empty($GLOBALS['goto']) ?
                        'tbl_sql.php' : $GLOBALS['goto'];
        }

        $retval .= '<form method="post" action="import.php" ';
        $retval .= ' name="sqlform" id="console_input" class="ajax">' . "\n";
        $retval .= '<input type="hidden" name="is_js_confirmed" value="0" />'
            . "\n" . PMA_URL_getHiddenInputs($db, $table) . "\n"
            . '<input type="hidden" name="pos" value="0" />' . "\n"
            . '<input type="hidden" name="goto" value="'
            . htmlspecialchars($goto) . '" />' . "\n"
            . '<input type="hidden" name="message_to_show" value="'
            . __('Your SQL query has been executed successfully.') . '" />';
            // Waiting for archive
            // . "\n" . '<input type="hidden" name="prev_sql_query" value="'
            // . htmlspecialchars($query) . '" />' . "\n";
        $retval .= '<textarea tabindex="100" name="sql_query" style="display: none;"></textarea>';

        $retval .= '</form>';
        return $retval;
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
            $retval .= '<span>' . __('Console') . '</span></div>';

            $retval .= '<div id="console_clear" class="collapsible button"><span>'
                    . __('Clear') .'</span></div>';

            $retval .= '</div>'; // Toolbar

            $retval .= '<div class="content">';
            $retval .= '<div class="message_container">'
                    .  '<div class="message"><span>'
                    .  __('Press Ctrl+Enter to Execute query')
                    .  '</span></div></div>';

            $retval .= $this->_getForm();

            $retval .= '<div class="query_input"><span id="query_input"></span></div>';

            $retval .= '</div></div></div>';
        }
        return $retval;
    }

}
