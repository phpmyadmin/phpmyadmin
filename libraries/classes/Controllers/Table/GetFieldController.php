<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Mime;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;

use function __;
use function htmlspecialchars;
use function ini_set;
use function mb_strlen;
use function sprintf;

/**
 * Provides download to a given field defined in parameters.
 */
class GetFieldController extends AbstractController
{
    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template);
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        $this->response->disable();

        $this->checkParameters(['db', 'table']);

        /* Select database */
        if (! $this->dbi->selectDb($GLOBALS['db'])) {
            Generator::mysqlDie(
                sprintf(__('\'%s\' database does not exist.'), htmlspecialchars($GLOBALS['db'])),
                '',
                false
            );
        }

        /* Check if table exists */
        if (! $this->dbi->getColumns($GLOBALS['db'], $GLOBALS['table'])) {
            Generator::mysqlDie(__('Invalid table name'));
        }

        if (
            ! isset($_GET['where_clause'])
            || ! isset($_GET['where_clause_sign'])
            || ! Core::checkSqlQuerySignature($_GET['where_clause'], $_GET['where_clause_sign'])
        ) {
            /* l10n: In case a SQL query did not pass a security check  */
            Core::fatalError(__('There is an issue with your request.'));

            return;
        }

        /* Grab data */
        $sql = 'SELECT ' . Util::backquote($_GET['transform_key'])
            . ' FROM ' . Util::backquote($GLOBALS['table'])
            . ' WHERE ' . $_GET['where_clause'] . ';';
        $result = $this->dbi->fetchValue($sql);

        /* Check return code */
        if ($result === false) {
            Generator::mysqlDie(
                __('MySQL returned an empty result set (i.e. zero rows).'),
                $sql
            );

            return;
        }

        /* Avoid corrupting data */
        ini_set('url_rewriter.tags', '');

        Core::downloadHeader(
            $GLOBALS['table'] . '-' . $_GET['transform_key'] . '.bin',
            Mime::detect($result),
            mb_strlen($result, '8bit')
        );
        echo $result;
    }
}
