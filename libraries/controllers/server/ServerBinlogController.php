<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Holds the PMA\libraries\controllers\server\ServerBinlogController
 *
 * @package PMA\libraries\controllers\server
 */

namespace PMA\libraries\controllers\server;

use PMA\libraries\controllers\Controller;
use PMA\libraries\DatabaseInterface;
use PMA\libraries\Message;
use PMA\libraries\Util;
use PMA\libraries\Template;

/**
 * Handles viewing binary logs
 *
 * @package PMA\libraries\controllers\server
 */
class ServerBinlogController extends Controller
{
    /**
     * array binary log files
     */
    protected $binary_logs;

    /**
     * Constructs ServerBinlogController
     */
    public function __construct()
    {
        parent::__construct();
        $this->binary_logs = $this->dbi->fetchResult(
            'SHOW MASTER LOGS',
            'Log_name',
            null,
            null,
            DatabaseInterface::QUERY_STORE
        );
    }

    /**
     * Index action
     *
     * @return void
     */
    public function indexAction()
    {
        /**
         * Does the common work
         */
        include_once 'libraries/server_common.inc.php';

        $url_params = array();
        if (! isset($_REQUEST['log'])
            || ! array_key_exists($_REQUEST['log'], $this->binary_logs)
        ) {
            $_REQUEST['log'] = '';
        } else {
            $url_params['log'] = $_REQUEST['log'];
        }

        if (!empty($_REQUEST['dontlimitchars'])) {
            $url_params['dontlimitchars'] = 1;
        }

        $this->response->addHTML(PMA_getHtmlForSubPageHeader('binlog'));
        $this->response->addHTML($this->_getLogSelector($url_params));
        $this->response->addHTML($this->_getLogInfo($url_params));
    }

    /**
     * Returns the html for log selector.
     *
     * @param array $url_params links parameters
     *
     * @return string
     */
    private function _getLogSelector($url_params)
    {
        return Template::get('server/binlog/log_selector')->render(
            array(
                'url_params' => $url_params,
                'binary_logs' => $this->binary_logs,
            )
        );
    }

    /**
     * Returns the html for binary log information.
     *
     * @param array $url_params links parameters
     *
     * @return string
     */
    private function _getLogInfo($url_params)
    {
        /**
         * Need to find the real end of rows?
         */
        if (! isset($_REQUEST['pos'])) {
            $pos = 0;
        } else {
            /* We need this to be a integer */
            $pos = (int) $_REQUEST['pos'];
        }

        $sql_query = 'SHOW BINLOG EVENTS';
        if (! empty($_REQUEST['log'])) {
            $sql_query .= ' IN \'' . $_REQUEST['log'] . '\'';
        }
        $sql_query .= ' LIMIT ' . $pos . ', ' . intval($GLOBALS['cfg']['MaxRows']);

        /**
         * Sends the query
         */
        $result = $this->dbi->query($sql_query);

        /**
         * prepare some vars for displaying the result table
         */
        // Gets the list of fields properties
        if (isset($result) && $result) {
            $num_rows = $this->dbi->numRows($result);
        } else {
            $num_rows = 0;
        }

        if (empty($_REQUEST['dontlimitchars'])) {
            $dontlimitchars = false;
        } else {
            $dontlimitchars = true;
            $url_params['dontlimitchars'] = 1;
        }

        //html output
        $html  = Util::getMessage(Message::success(), $sql_query);
        $html .= '<table id="binlogTable">'
            . '<thead>'
            . '<tr>'
            . '<td colspan="6" class="center">';

        $html .= $this->_getNavigationRow($url_params, $pos, $num_rows, $dontlimitchars);

        $html .=  '</td>'
            . '</tr>'
            . '<tr>'
            . '<th>' . __('Log name') . '</th>'
            . '<th>' . __('Position') . '</th>'
            . '<th>' . __('Event type') . '</th>'
            . '<th>' . __('Server ID') . '</th>'
            . '<th>' . __('Original position') . '</th>'
            . '<th>' . __('Information') . '</th>'
            . '</tr>'
            . '</thead>'
            . '<tbody>';

        $html .= $this->_getAllLogItemInfo($result, $dontlimitchars);

        $html .= '</tbody>'
            . '</table>';

        return $html;
    }

    /**
     * Returns the html for Navigation Row.
     *
     * @param array $url_params     Links parameters
     * @param int   $pos            Position to display
     * @param int   $num_rows       Number of results row
     * @param bool  $dontlimitchars Whether limit chars
     *
     * @return string
     */
    private function _getNavigationRow($url_params, $pos, $num_rows, $dontlimitchars)
    {
        $html = "";
        // we do not know how much rows are in the binlog
        // so we can just force 'NEXT' button
        if ($pos > 0) {
            $this_url_params = $url_params;
            if ($pos > $GLOBALS['cfg']['MaxRows']) {
                $this_url_params['pos'] = $pos - $GLOBALS['cfg']['MaxRows'];
            }

            $html .= '<a href="server_binlog.php'
                . PMA_URL_getCommon($this_url_params) . '"';
            if (Util::showIcons('TableNavigationLinksMode')) {
                $html .= ' title="' . _pgettext('Previous page', 'Previous') . '">';
            } else {
                $html .= '>' . _pgettext('Previous page', 'Previous');
            } // end if... else...
            $html .= ' &lt; </a> - ';
        }

        $this_url_params = $url_params;
        if ($pos > 0) {
            $this_url_params['pos'] = $pos;
        }
        if ($dontlimitchars) {
            unset($this_url_params['dontlimitchars']);
            $tempTitle = __('Truncate Shown Queries');
            $tempImgMode = 'partial';
        } else {
            $this_url_params['dontlimitchars'] = 1;
            $tempTitle = __('Show Full Queries');
            $tempImgMode = 'full';
        }
        $html .= '<a href="server_binlog.php' . PMA_URL_getCommon($this_url_params)
            . '" title="' . $tempTitle . '">'
            . '<img src="' . $GLOBALS['pmaThemeImage'] . 's_' . $tempImgMode
            . 'text.png" alt="' . $tempTitle . '" /></a>';

        // we do not now how much rows are in the binlog
        // so we can just force 'NEXT' button
        if ($num_rows >= $GLOBALS['cfg']['MaxRows']) {
            $this_url_params = $url_params;
            $this_url_params['pos'] = $pos + $GLOBALS['cfg']['MaxRows'];
            $html .= ' - <a href="server_binlog.php'
                . PMA_URL_getCommon($this_url_params)
                . '"';
            if (Util::showIcons('TableNavigationLinksMode')) {
                $html .= ' title="' . _pgettext('Next page', 'Next') . '">';
            } else {
                $html .= '>' . _pgettext('Next page', 'Next');
            } // end if... else...
            $html .= ' &gt; </a>';
        }

        return $html;
    }

    /**
     * Returns the html for all binary log items.
     *
     * @param resource $result         MySQL Query result
     * @param bool     $dontlimitchars Whether limit chars
     *
     * @return string
     */
    private function _getAllLogItemInfo($result, $dontlimitchars)
    {
        $html = "";
        $odd_row = true;
        while ($value = $this->dbi->fetchAssoc($result)) {
            $html .= Template::get('server/binlog/log_row')->render(
                array(
                    'odd_row' => $odd_row,
                    'value' => $value,
                    'dontlimitchars' => $dontlimitchars,
                )
            );

            $odd_row = !$odd_row;
        }
        return $html;
    }
}
