<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Operations;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\Current;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\InvalidDatabaseName;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\UserPrivilegesFactory;
use PhpMyAdmin\Util;

use function __;
use function mb_strtolower;

/**
 * Handles miscellaneous database operations.
 */
class DatabaseController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Operations $operations,
        private UserPrivilegesFactory $userPrivilegesFactory,
        private Relation $relation,
        private RelationCleanup $relationCleanup,
        private DatabaseInterface $dbi,
        private readonly DbTableExists $dbTableExists,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['message'] ??= null;
        $GLOBALS['errorUrl'] ??= null;
        $GLOBALS['urlParams'] ??= null;
        $GLOBALS['single_table'] ??= null;

        $userPrivileges = $this->userPrivilegesFactory->getPrivileges();

        $this->addScriptFiles(['database/operations.js']);

        $GLOBALS['sql_query'] = '';

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
                $GLOBALS['message'] = Message::error($exception->getMessage());
            }

            if ($newDatabaseName !== null) {
                if ($newDatabaseName->getName() === $_REQUEST['db']) {
                    $GLOBALS['message'] = Message::error(
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
                    $exportSqlPlugin = Plugins::getPlugin('export', 'sql', [
                        'export_type' => 'database',
                        'single_table' => isset($GLOBALS['single_table']),
                    ]);

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
                        $GLOBALS['sql_query'] .= "\n" . $localQuery;
                        $this->dbi->query($localQuery);

                        $GLOBALS['message'] = Message::success(
                            __('Database %1$s has been renamed to %2$s.'),
                        );
                        $GLOBALS['message']->addParam(Current::$database);
                        $GLOBALS['message']->addParam($newDatabaseName->getName());
                    } else {
                        if ($request->hasBodyParam('adjust_privileges')) {
                            $this->operations->adjustPrivilegesCopyDb(
                                $userPrivileges,
                                Current::$database,
                                $newDatabaseName,
                            );
                        }

                        $GLOBALS['message'] = Message::success(
                            __('Database %1$s has been copied to %2$s.'),
                        );
                        $GLOBALS['message']->addParam(Current::$database);
                        $GLOBALS['message']->addParam($newDatabaseName->getName());
                    }

                    $GLOBALS['reload'] = true;

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
                $this->response->setRequestStatus($GLOBALS['message']->isSuccess());
                $this->response->addJSON('message', $GLOBALS['message']);
                $this->response->addJSON('newname', $newDatabaseName?->getName() ?? '');
                $this->response->addJSON(
                    'sql_query',
                    Generator::getMessage('', $GLOBALS['sql_query']),
                );
                $this->response->addJSON('db', Current::$database);

                return;
            }
        }

        $relationParameters = $this->relation->getRelationParameters();

        /**
         * Check if comments were updated
         * (must be done before displaying the menu tabs)
         */
        if ($request->hasBodyParam('comment')) {
            $this->relation->setDbComment(Current::$database, $request->getParsedBodyParam('comment'));
        }

        if (! $this->checkParameters(['db'])) {
            return;
        }

        $config = Config::getInstance();
        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($config->settings['DefaultTabDatabase'], 'database');
        $GLOBALS['errorUrl'] .= Url::getCommon(['db' => Current::$database], '&');

        $databaseName = DatabaseName::tryFrom($request->getParam('db'));
        if ($databaseName === null || ! $this->dbTableExists->selectDatabase($databaseName)) {
            if ($request->isAjax()) {
                $this->response->setRequestStatus(false);
                $this->response->addJSON('message', Message::error(__('No databases selected.')));

                return;
            }

            $this->redirect('/', ['reload' => true, 'message' => __('No databases selected.')]);

            return;
        }

        $GLOBALS['urlParams']['goto'] = Url::getFromRoute('/database/operations');

        $oldMessage = '';
        if (isset($GLOBALS['message'])) {
            $oldMessage = Generator::getMessage($GLOBALS['message'], $GLOBALS['sql_query']);
            unset($GLOBALS['message']);
        }

        $dbCollation = $this->dbi->getDbCollation(Current::$database);

        if (Utilities::isSystemSchema(Current::$database)) {
            return;
        }

        $databaseComment = '';
        if ($relationParameters->columnCommentsFeature !== null) {
            $databaseComment = $this->relation->getDbComment(Current::$database);
        }

        $hasAdjustPrivileges = $userPrivileges->database && $userPrivileges->table
            && $userPrivileges->column && $userPrivileges->routines && $userPrivileges->isReload;

        $isDropDatabaseAllowed = ($this->dbi->isSuperUser() || $config->settings['AllowUserDropDatabase'])
            && Current::$database !== 'mysql';

        $switchToNew = isset($_SESSION['pma_switch_to_new']) && $_SESSION['pma_switch_to_new'];

        $charsets = Charsets::getCharsets($this->dbi, $config->selectedServer['DisableIS']);
        $collations = Charsets::getCollations($this->dbi, $config->selectedServer['DisableIS']);

        if (! $relationParameters->hasAllFeatures() && $config->settings['PmaNoRelation_DisableWarning'] == false) {
            $GLOBALS['message'] = Message::notice(
                __(
                    'The phpMyAdmin configuration storage has been deactivated. %sFind out why%s.',
                ),
            );
            $GLOBALS['message']->addParamHtml(
                '<a href="' . Url::getFromRoute('/check-relations')
                . '" data-post="' . Url::getCommon(['db' => Current::$database]) . '">',
            );
            $GLOBALS['message']->addParamHtml('</a>');
            /* Show error if user has configured something, notice elsewhere */
            if (! empty($config->settings['Servers'][Current::$server]['pmadb'])) {
                $GLOBALS['message']->setType(Message::ERROR);
            }
        }

        $this->render('database/operations/index', [
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
    }
}
