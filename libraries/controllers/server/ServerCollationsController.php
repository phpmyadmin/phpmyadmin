<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Holds the PMA\libraries\controllers\server\ServerCollationsController
 *
 * @package PMA\libraries\controllers\server
 */

namespace PMA\libraries\controllers\server;

use PMA\libraries\controllers\Controller;

/**
 * Handles viewing character sets and collations
 *
 * @package PMA\libraries\controllers\server
 */
class ServerCollationsController extends Controller
{
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
        /**
         * Includes the required charset library
         */
        include_once 'libraries/mysql_charsets.inc.php';

        $this->response->addHTML(PMA_getHtmlForSubPageHeader('collations'));
        $this->response->addHTML(
            $this->_getHtmlForCharsets(
                $GLOBALS['mysql_charsets'],
                $GLOBALS['mysql_collations'],
                $GLOBALS['mysql_charsets_descriptions'],
                $GLOBALS['mysql_default_collations'],
                $GLOBALS['mysql_collations_available']
            )
        );
    }

    /**
     * Returns the html for server Character Sets and Collations.
     *
     * @param array $mysqlCharsets      Mysql Charsets list
     * @param array $mysqlCollations    Mysql Collations list
     * @param array $mysqlCharsetsDesc  Charsets descriptions
     * @param array $mysqlDftCollations Default Collations list
     * @param array $mysqlCollAvailable Available Collations list
     *
     * @return string
     */
    function _getHtmlForCharsets($mysqlCharsets, $mysqlCollations,
        $mysqlCharsetsDesc, $mysqlDftCollations, $mysqlCollAvailable
    ) {
        /**
         * Outputs the result
         */
        $html = '<div id="div_mysql_charset_collations">' . "\n"
            . '<table class="data noclick">' . "\n"
            . '<tr><th id="collationHeader">' . __('Collation') . '</th>' . "\n"
            . '    <th>' . __('Description') . '</th>' . "\n"
            . '</tr>' . "\n";

        $table_row_count = count($mysqlCharsets) + count($mysqlCollations);

        foreach ($mysqlCharsets as $current_charset) {

            $html .= '<tr><th colspan="2" class="right">' . "\n"
                . '        ' . htmlspecialchars($current_charset) . "\n"
                . (empty($mysqlCharsetsDesc[$current_charset])
                    ? ''
                    : '        (<i>' . htmlspecialchars(
                        $mysqlCharsetsDesc[$current_charset]
                    ) . '</i>)' . "\n")
                . '    </th>' . "\n"
                . '</tr>' . "\n";

            $html .= $this->_getHtmlForCollationCurrentCharset(
                $current_charset,
                $mysqlCollations,
                $mysqlDftCollations,
                $mysqlCollAvailable
            );

        }

        $html .= '</table>' . "\n"
            . '</div>' . "\n";

        return $html;
    }

    /**
     * Returns the html for Collations of Current Charset.
     *
     * @param string $currCharset        Current Charset
     * @param array  $mysqlColl          Collations list
     * @param array  $mysqlDefaultColl   Default Collations list
     * @param array  $mysqlCollAvailable Available Collations list
     *
     * @return string
     */
    function _getHtmlForCollationCurrentCharset(
        $currCharset, $mysqlColl, $mysqlDefaultColl, $mysqlCollAvailable
    ) {
        $odd_row = true;
        $html = '';
        foreach ($mysqlColl[$currCharset] as $current_collation) {

            $html .= '<tr class="'
                . ($odd_row ? 'odd' : 'even')
                . ($mysqlDefaultColl[$currCharset] == $current_collation
                    ? ' marked'
                    : '')
                . ($mysqlCollAvailable[$current_collation] ? '' : ' disabled')
                . '">' . "\n"
                . '    <td>' . htmlspecialchars($current_collation) . '</td>' . "\n"
                . '    <td>' . PMA_getCollationDescr($current_collation) . '</td>' . "\n"
                . '</tr>' . "\n";
            $odd_row = !$odd_row;
        }
        return $html;
    }
}
