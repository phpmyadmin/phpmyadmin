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
final readonly class GetFieldController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private DatabaseInterface $dbi,
        private ResponseFactory $responseFactory,
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
            $errorMessage = Generator::mysqlDie(
                sprintf(__('\'%s\' database does not exist.'), htmlspecialchars(Current::$database)),
                '',
                false,
            );

            if ($this->response->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $errorMessage);

                return $this->response->response();
            }

            $this->response->addHTML($errorMessage);

            return $this->response->response();
        }

        /* Check if table exists */
        if ($this->dbi->getColumns(Current::$database, Current::$table) === []) {
            $errorMessage = Generator::mysqlDie(__('Invalid table name'), '', true);

            if ($this->response->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $errorMessage);

                return $this->response->response();
            }

            $this->response->addHTML($errorMessage);

            return $this->response->response();
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
            $errorMessage = Generator::mysqlDie(__('MySQL returned an empty result set (i.e. zero rows).'), $sql, true);

            if ($this->response->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', $errorMessage);

                return $this->response->response();
            }

            $this->response->addHTML($errorMessage);

            return $this->response->response();
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
