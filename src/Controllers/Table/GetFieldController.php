<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Core;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Mime;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\Util;

use function __;
use function htmlspecialchars;
use function ini_set;
use function mb_strlen;
use function sprintf;

/**
 * Provides download to a given field defined in parameters.
 */
#[Route('/table/get-field', ['GET', 'POST'])]
final class GetFieldController implements InvocableController
{
    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly DatabaseInterface $dbi,
        private readonly ResponseFactory $responseFactory,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (Current::$database === '') {
            return $this->response->missingParameterError('db');
        }

        if (Current::$table === '') {
            return $this->response->missingParameterError('table');
        }

        /* Select database */
        if (! $this->dbi->selectDb(Current::$database)) {
            Generator::mysqlDie(
                sprintf(__('\'%s\' database does not exist.'), htmlspecialchars(Current::$database)),
                '',
                false,
            );
        }

        /* Check if table exists */
        if ($this->dbi->getColumns(Current::$database, Current::$table) === []) {
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

            return $this->response->response();
        }

        $transformKey = (string) $request->getQueryParam('transform_key', '');
        /* Grab data */
        $sql = 'SELECT ' . Util::backquote($transformKey)
            . ' FROM ' . Util::backquote(Current::$table)
            . ' WHERE ' . $whereClause . ';';
        $result = $this->dbi->fetchValue($sql);

        /* Check return code */
        if ($result === false) {
            Generator::mysqlDie(
                __('MySQL returned an empty result set (i.e. zero rows).'),
                $sql,
            );
        }

        $result ??= '';

        /* Avoid corrupting data */
        ini_set('url_rewriter.tags', '');

        $response = $this->responseFactory->createResponse();
        Core::downloadHeader(
            Current::$table . '-' . $transformKey . '.bin',
            Mime::detect($result),
            mb_strlen($result, '8bit'),
        );

        return $response->write($result);
    }
}
