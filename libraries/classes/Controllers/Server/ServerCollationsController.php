<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Holds the PhpMyAdmin\Controllers\Server\ServerCollationsController
 *
 * @package PhpMyAdmin\Controllers
 */

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Controllers\Controller;
use PhpMyAdmin\Charsets;
use PhpMyAdmin\Server\Common;
use PhpMyAdmin\Template;

/**
 * Handles viewing character sets and collations
 *
 * @package PhpMyAdmin\Controllers
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

        $this->response->addHTML(Common::getHtmlForSubPageHeader('collations'));
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
    function _getHtmlForCharsets(array $mysqlCharsets, array $mysqlCollations,
        array $mysqlCharsetsDesc, array $mysqlDftCollations
    ) {
        return Template::get('server/collations/charsets')->render(
            array(
                'mysql_charsets' => $mysqlCharsets,
                'mysql_collations' => $mysqlCollations,
                'mysql_charsets_desc' => $mysqlCharsetsDesc,
                'mysql_dft_collations' => $mysqlDftCollations,
            )
        );
    }
}
