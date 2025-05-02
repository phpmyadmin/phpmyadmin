<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Operations;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\InvocableController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Export\Export;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\Response;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\InvalidDatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\MessageType;
use PhpMyAdmin\Operations;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\ExportType;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Url;
use PhpMyAdmin\UrlParams;
use PhpMyAdmin\UserPrivilegesFactory;
use PhpMyAdmin\Util;

use function __;
use function mb_strtolower;

/**
 * Handles miscellaneous database operations.
 */
final readonly class DatabaseController implements InvocableController
{
    public function __construct(
        private ResponseRenderer $response,
        private Operations $operations,
        private UserPrivilegesFactory $userPrivilegesFactory,
        private Relation $relation,
        private RelationCleanup $relationCleanup,
        private DatabaseInterface $dbi,
        private DbTableExists $dbTableExists,
        private Config $config,
    ) {
    }

    public function __invoke(ServerRequest $request): Response
    {
        $userPrivileges = $this->userPrivilegesFactory->getPrivileges();

        $this->response->addScriptFiles(['database/operations.js']);

        Current::$sqlQuery = '';

        /**
         * Rename/move or copy database
         */
        if (
            Current::$database !== '' && ($request->hasBodyParam('db_rename') || $request->hasBodyParam('db_copy'))
        ) {
            $move = $request->hasBodyParam('db_rename');

            try {
                $newDatabaseName = DatabaseName::from($request->getParsedBodyParam('newname'));
                if ($this->dbi->getLowerCaseNames() === 1) {
                    $newDatabaseName = DatabaseName::from(mb_strtolower($newDatabaseName->getName()));
                }
            } catch (InvalidDatabaseName $exception) {
                $newDatabaseName = null;
                Current::$message = Message::error($exception->getMessage());
            }

            if ($newDatabaseName !== null) {
                if ($newDatabaseName->getName() === $_REQUEST['db']) {
                    Current::$message = Message::error(
                        __('Cannot copy database to the same name. Change the name and try again.'),
                    );
                } else {
                    if ($move || $request->hasBodyParam('create_database_before_copying')) {
                        $this->operations->createDbBeforeCopy($userPrivileges, $newDatabaseName);
                    }

                    // here I don't use DELIMITER because it's not part of the
                    // language; I have to send each statement one by one

                    // to avoid selecting alternatively the current and new db
                    // we would need to modify the CREATE definitions to qualify
                    // the db name
                    $this->operations->runProcedureAndFunctionDefinitions(Current::$database, $newDatabaseName);

                    // go back to current db, just in case
                    $this->dbi->selectDb(Current::$database);

                    $tableNames = $this->dbi->getTables(Current::$database);

                    // remove all foreign key constraints, otherwise we can get errors
                    $exportSqlPlugin = Plugins::getPlugin('export', 'sql', ExportType::Database, Export::$singleTable);

                    // create stand-in tables for views
                    $views = $this->operations->getViewsAndCreateSqlViewStandIn(
                        $tableNames,
                        $exportSqlPlugin,
                        Current::$database,
                        $newDatabaseName,
                    );

                    // copy tables
                    $sqlConstraints = $this->operations->copyTables(
                        $tableNames,
                        $move,
                        Current::$database,
                        $newDatabaseName,
                    );

                    // handle the views
                    $this->operations->handleTheViews($views, $move, Current::$database, $newDatabaseName);

                    // now that all tables exist, create all the accumulated constraints
                    if ($sqlConstraints !== []) {
                        $this->operations->createAllAccumulatedConstraints($sqlConstraints, $newDatabaseName);
                    }

                    if ($this->dbi->getVersion() >= 50100) {
                        // here DELIMITER is not used because it's not part of the
                        // language; each statement is sent one by one

                        $this->operations->runEventDefinitionsForDb(Current::$database, $newDatabaseName);
                    }

                    // go back to current db, just in case
                    $this->dbi->selectDb(Current::$database);

                    // Duplicate the bookmarks for this db (done once for each db)
                    $this->operations->duplicateBookmarks(false, Current::$database, $newDatabaseName);

                    if ($move) {
                        if ($request->hasBodyParam('adjust_privileges')) {
                            $this->operations->adjustPrivilegesMoveDb(
                                $userPrivileges,
                                Current::$database,
                                $newDatabaseName,
                            );
                        }

                        /**
                         * cleanup pmadb stuff for this db
                         */
                        $this->relationCleanup->database(Current::$database);

                        // if someday the RENAME DATABASE reappears, do not DROP
                        $localQuery = 'DROP DATABASE ' . Util::backquote(Current::$database) . ';';
                        Current::$sqlQuery .= "\n" . $localQuery;
                        $this->dbi->query($localQuery);

                        Current::$message = Message::success(
                            __('Database %1$s has been renamed to %2$s.'),
                        );
                        Current::$message->addParam(Current::$database);
                        Current::$message->addParam($newDatabaseName->getName());
                    } else {
                        if ($request->hasBodyParam('adjust_privileges')) {
                            $this->operations->adjustPrivilegesCopyDb(
                                $userPrivileges,
                                Current::$database,
                                $newDatabaseName,
                            );
                        }

                        Current::$message = Message::success(
                            __('Database %1$s has been copied to %2$s.'),
                        );
                        Current::$message->addParam(Current::$database);
                        Current::$message->addParam($newDatabaseName->getName());
                    }

                    ResponseRenderer::$reload = true;

                    /* Change database to be used */
                    if ($move) {
                        Current::$database = $newDatabaseName->getName();
                    } elseif ($request->getParsedBodyParam('switch_to_new') === 'true') {
                        $_SESSION['pma_switch_to_new'] = true;
                        Current::$database = $newDatabaseName->getName();
                    } else {
                        $_SESSION['pma_switch_to_new'] = false;
                    }
                }
            }

            /**
             * Database has been successfully renamed/moved.  If in an Ajax request,
             * generate the output with {@link ResponseRenderer} and exit
             */
            if ($request->isAjax()) {
                $this->response->setRequestStatus(Current::$message->isSuccess());
                $this->response->addJSON('message', Current::$message);
                $this->response->addJSON('newname', $newDatabaseName?->getName() ?? '');
                $this->response->addJSON(
                    'sql_query',
                    Generator::getMessage('', Current::$sqlQuery),
                );
                $this->response->addJSON('db', Current::$database);

                return $this->response->response();
            }
        }

        $relationParameters = $this->relation->getRelationParameters();

        /**
         * Check if comments were updated
         * (must be done before displaying the menu tabs)
         */
        if ($request->hasBodyParam('comment')) {
            $this->relation->setDbComment(Current::$database, $request->getParsedBodyParamAsString('comment'));
        }

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

            $this->response->redirectToRoute('/', ['reload' => true, 'message' => __('No databases selected.')]);

            return $this->response->response();
        }

        UrlParams::$params['goto'] = Url::getFromRoute('/database/operations');

        $oldMessage = '';
        if (Current::$message !== null) {
            $oldMessage = Generator::getMessage(Current::$message, Current::$sqlQuery);
            Current::$message = null;
        }

        $dbCollation = $this->dbi->getDbCollation(Current::$database);

        if (Utilities::isSystemSchema(Current::$database)) {
            return $this->response->response();
        }

        $databaseComment = '';
        if ($relationParameters->columnCommentsFeature !== null) {
            $databaseComment = $this->relation->getDbComment(Current::$database);
        }

        $hasAdjustPrivileges = $userPrivileges->database && $userPrivileges->table
            && $userPrivileges->column && $userPrivileges->routines && $userPrivileges->isReload;

        $isDropDatabaseAllowed = ($this->dbi->isSuperUser() || $this->config->settings['AllowUserDropDatabase'])
            && Current::$database !== 'mysql';

        $switchToNew = isset($_SESSION['pma_switch_to_new']) && $_SESSION['pma_switch_to_new'];

        $charsets = Charsets::getCharsets($this->dbi, $this->config->selectedServer['DisableIS']);
        $collations = Charsets::getCollations($this->dbi, $this->config->selectedServer['DisableIS']);

        if (
            ! $relationParameters->hasAllFeatures() && $this->config->settings['PmaNoRelation_DisableWarning'] == false
        ) {
            Current::$message = Message::notice(
                __(
                    'The phpMyAdmin configuration storage has been deactivated. %sFind out why%s.',
                ),
            );
            Current::$message->addParamHtml(
                '<a href="' . Url::getFromRoute('/check-relations')
                . '" data-post="' . Url::getCommon(['db' => Current::$database]) . '">',
            );
            Current::$message->addParamHtml('</a>');
            /* Show error if user has configured something, notice elsewhere */
            if (! empty($this->config->settings['Servers'][Current::$server]['pmadb'])) {
                Current::$message->setType(MessageType::Error);
            }
        }

        $this->response->render('database/operations/index', [
            'message' => $oldMessage,
            'db' => Current::$database,
            'has_comment' => $relationParameters->columnCommentsFeature !== null,
            'db_comment' => $databaseComment,
            'db_collation' => $dbCollation,
            'has_adjust_privileges' => $hasAdjustPrivileges,
            'is_drop_database_allowed' => $isDropDatabaseAllowed,
            'switch_to_new' => $switchToNew,
            'charsets' => $charsets,
            'collations' => $collations,
        ]);

        return $this->response->response();
    }
}
