<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\Controllers\Server\SqlController
 * @package PhpMyAdmin\Controllers\Server
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Server;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\SqlQueryForm;

/**
 * Server SQL executor
 * @package PhpMyAdmin\Controllers\Server
 */
class SqlController extends AbstractController
{
    /**
     * @param SqlQueryForm $sqlQueryForm SqlQueryForm instance
     *
     * @return string HTML
     */
    public function index(SqlQueryForm $sqlQueryForm): string
    {
        PageSettings::showGroup('Sql');

        require_once ROOT_PATH . 'libraries/server_common.inc.php';

        return $sqlQueryForm->getHtml();
    }
}
