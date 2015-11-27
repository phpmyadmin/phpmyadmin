<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Holds the PMA\libraries\controllers\server\ServerCollationsController
 *
 * @package PMA\libraries\controllers\server
 */

namespace PMA\libraries\controllers\server;

use PMA\libraries\controllers\Controller;
use PMA\libraries\Template;

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
        return Template::get('server/collations/charsets')->render(
            array(
                'mysqlCharsets' => $mysqlCharsets,
                'mysqlCollations' => $mysqlCollations,
                'mysqlCharsetsDesc' => $mysqlCharsetsDesc,
                'mysqlDftCollations' => $mysqlDftCollations,
                'mysqlCollAvailable' => $mysqlCollAvailable,
            )
        );
    }
}
