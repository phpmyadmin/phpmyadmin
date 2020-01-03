<?php
/**
 * @package PhpMyAdmin\Controllers\Database
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Response;
use PhpMyAdmin\SqlQueryForm;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

/**
 * Database SQL executor
 *
 * @package PhpMyAdmin\Controllers\Database
 */
class SqlController extends AbstractController
{
    /** @var SqlQueryForm */
    private $sqlQueryForm;

    /**
     * @param Response          $response     Response instance
     * @param DatabaseInterface $dbi          DatabaseInterface instance
     * @param Template          $template     Template instance
     * @param string            $db           Database name
     * @param SqlQueryForm      $sqlQueryForm SqlQueryForm instance
     */
    public function __construct($response, $dbi, Template $template, $db, SqlQueryForm $sqlQueryForm)
    {
        parent::__construct($response, $dbi, $template, $db);
        $this->sqlQueryForm = $sqlQueryForm;
    }

    /**
     * @param array $params Request parameters
     *
     * @return string HTML
     */
    public function index(array $params): string
    {
        global $goto, $back;

        $header = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('makegrid.js');
        $scripts->addFile('vendor/jquery/jquery.uitablefilter.js');
        $scripts->addFile('sql.js');

        PageSettings::showGroup('Sql');

        require ROOT_PATH . 'libraries/db_common.inc.php';

        /**
         * After a syntax error, we return to this script
         * with the typed query in the textarea.
         */
        $goto = Url::getFromRoute('/database/sql');
        $back = $goto;

        return $this->sqlQueryForm->getHtml(
            true,
            false,
            isset($params['delimiter'])
                ? htmlspecialchars($params['delimiter'])
                : ';'
        );
    }
}
