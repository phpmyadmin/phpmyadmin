<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\View;

use PhpMyAdmin\Container\ContainerBuilder;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Routing\Route;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\CreateStatement;
use PhpMyAdmin\SqlParser\TokensList;
use PhpMyAdmin\SystemDatabase;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;
use PhpMyAdmin\Util;

use function __;
use function array_merge;
use function explode;
use function htmlspecialchars;
use function in_array;
use function sprintf;
use function str_contains;
use function substr;

/**
 * Handles creation of VIEWs.
 */
#[Route('/view/create', ['GET', 'POST'])]
final class CreateController implements InvocableController
{
    /** @todo Move the whole view rebuilding logic to SQL parser */
    private const VIEW_SECURITY_OPTIONS = ['DEFINER', 'INVOKER'];

    private const VIEW_ALGORITHM_OPTIONS = ['UNDEFINED', 'MERGE', 'TEMPTABLE'];

    private const VIEW_WITH_OPTIONS = ['CASCADED', 'LOCAL'];

    public function __construct(
        private readonly ResponseRenderer $response,
        private readonly DatabaseInterface $dbi,
        private readonly DbTableExists $dbTableExists,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        if (Current::$database === '') {
            return $this->response->missingParameterError('db');
        }

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No databases selected.')));

                return $this->response->response();
            }

            return $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No databases selected.')]);
        }

        UrlParams::$params['goto'] = Url::getFromRoute('/table/structure');
        UrlParams::$params['back'] = Url::getFromRoute('/view/create');

        /** @var array|null $view */
        $view = $request->getParsedBodyParam('view');

        // View name is a compulsory field
        if (isset($view['name']) && $view['name'] === '') {
            Current::$message = Message::error(__('View name can not be empty!'));
            $this->response->addJSON('message', Current::$message);
            $this->response->setRequestStatus(false);

            return $this->response->response();
        }

        $createview = $request->hasBodyParam('createview');
        $alterview = $request->hasBodyParam('alterview');
        $ajaxdialog = $request->hasBodyParam('ajax_dialog');

        if (($createview || $alterview) && $view !== null) {
            Current::$sqlQuery = $this->getSqlQuery($createview, $view);

            if (! $this->dbi->tryQuery(Current::$sqlQuery)) {
                if (! $ajaxdialog) {
                    Current::$message = Message::rawError($this->dbi->getError());

                    return $this->response->response();
                }

                $this->response->addJSON(
                    'message',
                    Message::error(
                        '<i>' . htmlspecialchars(Current::$sqlQuery) . '</i><br><br>'
                        . $this->dbi->getError(),
                    ),
                );
                $this->response->setRequestStatus(false);

                return $this->response->response();
            }

            return $this->setSuccessResponse($view, $ajaxdialog, $request);
        }

        Current::$sqlQuery = $request->getParsedBodyParamAsString('sql_query', '');

        // prefill values if not already filled from former submission
        $viewData = [
            'operation' => 'create',
            'or_replace' => '',
            'algorithm' => '',
            'definer' => '',
            'sql_security' => '',
            'name' => '',
            'column_names' => '',
            'as' => Current::$sqlQuery,
            'with' => '',
        ];

        // Used to prefill the fields when editing a view
        if ($request->hasQueryParam('db') && $request->hasQueryParam('table')) {
            $db = $request->getQueryParam('db');
            $table = $request->getQueryParam('table');
            $item = $this->dbi->fetchSingleRow(
                sprintf(
                    'SELECT `VIEW_DEFINITION`, `CHECK_OPTION`, `DEFINER`, `SECURITY_TYPE`
                        FROM `INFORMATION_SCHEMA`.`VIEWS`
                        WHERE TABLE_SCHEMA=%s
                        AND TABLE_NAME=%s;',
                    $this->dbi->quoteString($db),
                    $this->dbi->quoteString($table),
                ),
            );
            $createView = $this->dbi->getTable($db, $table)
                ->showCreate();

            // CREATE ALGORITHM=<ALGORITHM> DE...
            $item['ALGORITHM'] = explode(' ', substr($createView, 17))[0];

            $viewData['operation'] = 'alter';
            $viewData['definer'] = $item['DEFINER'];
            $viewData['sql_security'] = $item['SECURITY_TYPE'];
            $viewData['name'] = $table;
            $viewData['as'] = $item['VIEW_DEFINITION'];
            $viewData['with'] = $item['CHECK_OPTION'];
            $viewData['algorithm'] = $item['ALGORITHM'];

            // MySQL 8.0+ - issue #16194
            if (empty($viewData['as'])) {
                $parser = new Parser($createView);
                /** @var CreateStatement $stmt */
                $stmt = $parser->statements[0];
                $viewData['as'] = TokensList::buildFromArray($stmt->body);
            }
        }

        if ($view !== null) {
            $viewData = array_merge($viewData, $view);
        }

        UrlParams::$params['db'] = Current::$database;
        UrlParams::$params['reload'] = 1;

        $this->response->addScriptFiles(['sql.js']);

        $this->response->render('view_create', [
            'ajax_dialog' => $ajaxdialog,
            'url_params' => UrlParams::$params,
            'view' => $viewData,
            'view_algorithm_options' => self::VIEW_ALGORITHM_OPTIONS,
            'view_with_options' => self::VIEW_WITH_OPTIONS,
            'view_security_options' => self::VIEW_SECURITY_OPTIONS,
        ]);

        return $this->response->response();
    }

    /** @param mixed[] $view */
    private function setSuccessResponse(array $view, bool $ajaxdialog, ServerRequest $request): Response
    {
        // If different column names defined for VIEW
        $viewColumns = [];
        if (isset($view['column_names']) && $view['column_names'] !== '') {
            $viewColumns = explode(',', $view['column_names']);
        }

        $systemDb = new SystemDatabase($this->dbi);
        $pmaTransformationData = $systemDb->getExistingTransformationData(Current::$database);

        if ($pmaTransformationData !== false) {
            $columnMap = $systemDb->getColumnMapFromSql($view['as'], $viewColumns);
            // SQL for store new transformation details of VIEW
            $newTransformationsSql = $systemDb->getNewTransformationDataSql(
                $pmaTransformationData,
                $columnMap,
                $view['name'],
                Current::$database,
            );

            // Store new transformations
            if ($newTransformationsSql !== '') {
                $this->dbi->tryQuery($newTransformationsSql);
            }
        }

        if ($ajaxdialog) {
            Current::$message = Message::success();
            $controller = ContainerBuilder::getContainer()->get(StructureController::class);

            return $controller($request);
        }

        $this->response->addJSON(
            'message',
            Generator::getMessage(
                Message::success(),
                Current::$sqlQuery,
            ),
        );
        $this->response->setRequestStatus(true);

        return $this->response->response();
    }

    /**
     * Creates the view
     *
     * @param mixed[] $view
     */
    private function getSqlQuery(bool $createview, array $view): string
    {
        $separator = "\r\n";

        if ($createview) {
            $sqlQuery = 'CREATE';
            if (isset($view['or_replace'])) {
                $sqlQuery .= ' OR REPLACE';
            }
        } else {
            $sqlQuery = 'ALTER';
        }

        if (
            isset($view['algorithm'])
            && in_array($view['algorithm'], self::VIEW_ALGORITHM_OPTIONS, true)
        ) {
            $sqlQuery .= $separator . ' ALGORITHM = ' . $view['algorithm'];
        }

        if (! empty($view['definer'])) {
            if (! str_contains($view['definer'], '@')) {
                $sqlQuery .= $separator . 'DEFINER='
                    . Util::backquote($view['definer']);
            } else {
                $definerArray = explode('@', $view['definer']);
                $sqlQuery .= $separator . 'DEFINER=' . Util::backquote($definerArray[0]);
                $sqlQuery .= '@' . Util::backquote($definerArray[1]) . ' ';
            }
        }

        if (
            isset($view['sql_security'])
            && in_array($view['sql_security'], self::VIEW_SECURITY_OPTIONS, true)
        ) {
            $sqlQuery .= $separator . ' SQL SECURITY '
                . $view['sql_security'];
        }

        $sqlQuery .= $separator . ' VIEW '
            . Util::backquote($view['name']);

        if (! empty($view['column_names'])) {
            $sqlQuery .= $separator . ' (' . $view['column_names'] . ')';
        }

        $sqlQuery .= $separator . ' AS ' . $view['as'];

        if (isset($view['with']) && in_array($view['with'], self::VIEW_WITH_OPTIONS, true)) {
            $sqlQuery .= $separator . ' WITH ' . $view['with'] . '  CHECK OPTION';
        }

        return $sqlQuery;
    }
}
