<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Holds the PMA\libraries\controllers\server\ServerCollationsController
 *
 * @package PMA\libraries\controllers\server
 */

namespace PMA\libraries\controllers\server;

use PMA\libraries\controllers\Controller;
use PMA\libraries\Charsets;
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

        $this->response->addHTML(PMA_getHtmlForSubPageHeader('collations'));
        $this->response->addHTML(
            $this->_getHtmlForCharsets(
                Charsets::getMySQLCharsets(),
                Charsets::getMySQLCollations(),
                Charsets::getMySQLCharsetsDescriptions(),
                Charsets::getMySQLCollationsDefault()
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
     *
     * @return string
     */
    function _getHtmlForCharsets($mysqlCharsets, $mysqlCollations,
        $mysqlCharsetsDesc, $mysqlDftCollations
    ) {
        return Template::get('server/collations/charsets')->render(
            array(
                'mysqlCharsets' => $mysqlCharsets,
                'mysqlCollations' => $mysqlCollations,
                'mysqlCharsetsDesc' => $mysqlCharsetsDesc,
                'mysqlDftCollations' => $mysqlDftCollations,
            )
        );
    }
}
