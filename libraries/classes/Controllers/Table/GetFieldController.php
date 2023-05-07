<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
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
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $this->response->disable();

        $this->checkParameters(['db', 'table']);

        /* Select database */
        if (! $this->dbi->selectDb($GLOBALS['db'])) {
            Generator::mysqlDie(
                sprintf(__('\'%s\' database does not exist.'), htmlspecialchars($GLOBALS['db'])),
                '',
                false,
            );
        }

        /* Check if table exists */
        if (! $this->dbi->getColumns($GLOBALS['db'], $GLOBALS['table'])) {
            Generator::mysqlDie(__('Invalid table name'));
        }

        $whereClause = (string) $request->getQueryParam('where_clause', '');
        $whereClauseSign = (string) $request->getQueryParam('where_clause_sign', '');
        if (
            $whereClause === '' || $whereClauseSign === ''
            || ! Core::checkSqlQuerySignature($whereClause, $whereClauseSign)
        ) {
            $this->response->setRequestStatus(false);
            /* l10n: In case a SQL query did not pass a security check  */
            $this->response->addHTML(Message::error(__('There is an issue with your request.'))->getDisplay());

            return;
        }

        $transformKey = (string) $request->getQueryParam('transform_key', '');
        /* Grab data */
        $sql = 'SELECT ' . Util::backquote($transformKey)
            . ' FROM ' . Util::backquote($GLOBALS['table'])
            . ' WHERE ' . $whereClause . ';';
        $result = $this->dbi->fetchValue($sql);

        /* Check return code */
        if ($result === false) {
            Generator::mysqlDie(
                __('MySQL returned an empty result set (i.e. zero rows).'),
                $sql,
            );

            return;
        }

        /* Avoid corrupting data */
        ini_set('url_rewriter.tags', '');

        Core::downloadHeader(
            $GLOBALS['table'] . '-' . $transformKey . '.bin',
            Mime::detect($result),
            mb_strlen($result, '8bit'),
        );
        echo $result;
    }
}
