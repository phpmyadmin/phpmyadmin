<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\InvalidDatabaseName;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function mb_strtolower;
use function strlen;

/**
 * Handles miscellaneous database operations.
 */
class OperationsController extends AbstractController
{
    public function __construct(
        ResponseRenderer $response,
        Template $template,
        private Operations $operations,
        private CheckUserPrivileges $checkUserPrivileges,
        private Relation $relation,
        private RelationCleanup $relationCleanup,
        private DatabaseInterface $dbi,
    ) {
        parent::__construct($response, $template);
    }

    public function __invoke(ServerRequest $request): void
    {
        $GLOBALS['server'] ??= null;
        $GLOBALS['message'] ??= null;
        $GLOBALS['errorUrl'] ??= null;
        $GLOBALS['reload'] ??= null;
        $GLOBALS['urlParams'] ??= null;
        $GLOBALS['single_table'] ??= null;

        $this->checkUserPrivileges->getPrivileges();

        $this->addScriptFiles(['database/operations.js']);

        $GLOBALS['sql_query'] = '';

        /**
         * Rename/move or copy database
         */
        if (strlen($GLOBALS['db']) > 0 && ($request->hasBodyParam('db_rename') || $request->hasBodyParam('db_copy'))) {
            $move = $request->hasBodyParam('db_rename');

            try {
                $newDatabaseName = DatabaseName::fromValue($request->getParsedBodyParam('newname'));
                if ($this->dbi->getLowerCaseNames() === 1) {
                    $newDatabaseName = DatabaseName::fromValue(mb_strtolower($newDatabaseName->getName()));
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
                        $this->operations->createDbBeforeCopy($newDatabaseName);
                    }

                    // here I don't use DELIMITER because it's not part of the
                    // language; I have to send each statement one by one

                    // to avoid selecting alternatively the current and new db
                    // we would need to modify the CREATE definitions to qualify
                    // the db name
                    $this->operations->runProcedureAndFunctionDefinitions($GLOBALS['db'], $newDatabaseName);

                    // go back to current db, just in case
                    $this->dbi->selectDb($GLOBALS['db']);

                    $tableNames = $this->dbi->getTables($GLOBALS['db']);

                    // remove all foreign key constraints, otherwise we can get errors
                    $exportSqlPlugin = Plugins::getPlugin('export', 'sql', [
                        'export_type' => 'database',
                        'single_table' => isset($GLOBALS['single_table']),
                    ]);

                    // create stand-in tables for views
                    $views = $this->operations->getViewsAndCreateSqlViewStandIn(
                        $tableNames,
                        $exportSqlPlugin,
                        $GLOBALS['db'],
                        $newDatabaseName,
                    );

                    // copy tables
                    $sqlConstraints = $this->operations->copyTables(
                        $tableNames,
                        $move,
                        $GLOBALS['db'],
                        $newDatabaseName,
                    );

                    // handle the views
                    $this->operations->handleTheViews($views, $move, $GLOBALS['db'], $newDatabaseName);

                    // now that all tables exist, create all the accumulated constraints
                    if ($sqlConstraints !== []) {
                        $this->operations->createAllAccumulatedConstraints($sqlConstraints, $newDatabaseName);
                    }

                    if ($this->dbi->getVersion() >= 50100) {
                        // here DELIMITER is not used because it's not part of the
                        // language; each statement is sent one by one

                        $this->operations->runEventDefinitionsForDb($GLOBALS['db'], $newDatabaseName);
                    }

                    // go back to current db, just in case
                    $this->dbi->selectDb($GLOBALS['db']);

                    // Duplicate the bookmarks for this db (done once for each db)
                    $this->operations->duplicateBookmarks(false, $GLOBALS['db'], $newDatabaseName);

                    if ($move) {
                        if ($request->hasBodyParam('adjust_privileges')) {
                            $this->operations->adjustPrivilegesMoveDb($GLOBALS['db'], $newDatabaseName);
                        }

                        /**
                         * cleanup pmadb stuff for this db
                         */
                        $this->relationCleanup->database($GLOBALS['db']);

                        // if someday the RENAME DATABASE reappears, do not DROP
                        $localQuery = 'DROP DATABASE ' . Util::backquote($GLOBALS['db']) . ';';
                        $GLOBALS['sql_query'] .= "\n" . $localQuery;
                        $this->dbi->query($localQuery);

                        $GLOBALS['message'] = Message::success(
                            __('Database %1$s has been renamed to %2$s.'),
                        );
                        $GLOBALS['message']->addParam($GLOBALS['db']);
                        $GLOBALS['message']->addParam($newDatabaseName->getName());
                    } else {
                        if ($request->hasBodyParam('adjust_privileges')) {
                            $this->operations->adjustPrivilegesCopyDb($GLOBALS['db'], $newDatabaseName);
                        }

                        $GLOBALS['message'] = Message::success(
                            __('Database %1$s has been copied to %2$s.'),
                        );
                        $GLOBALS['message']->addParam($GLOBALS['db']);
                        $GLOBALS['message']->addParam($newDatabaseName->getName());
                    }

                    $GLOBALS['reload'] = true;

                    /* Change database to be used */
                    if ($move) {
                        $GLOBALS['db'] = $newDatabaseName->getName();
                    } elseif ($request->getParsedBodyParam('switch_to_new') === 'true') {
                        $_SESSION['pma_switch_to_new'] = true;
                        $GLOBALS['db'] = $newDatabaseName->getName();
                    } else {
                        $_SESSION['pma_switch_to_new'] = false;
                    }
                }
            }

            /**
             * Database has been successfully renamed/moved.  If in an Ajax request,
             * generate the output with {@link ResponseRenderer} and exit
             */
            if ($this->response->isAjax()) {
                $this->response->setRequestStatus($GLOBALS['message']->isSuccess());
                $this->response->addJSON('message', $GLOBALS['message']);
                $this->response->addJSON('newname', $newDatabaseName?->getName() ?? '');
                $this->response->addJSON(
                    'sql_query',
                    Generator::getMessage('', $GLOBALS['sql_query']),
                );
                $this->response->addJSON('db', $GLOBALS['db']);

                return;
            }
        }

        $relationParameters = $this->relation->getRelationParameters();

        /**
         * Check if comments were updated
         * (must be done before displaying the menu tabs)
         */
        if ($request->hasBodyParam('comment')) {
            $this->relation->setDbComment($GLOBALS['db'], $request->getParsedBodyParam('comment'));
        }

        $this->checkParameters(['db']);

        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabDatabase'], 'database');
        $GLOBALS['errorUrl'] .= Url::getCommon(['db' => $GLOBALS['db']], '&');

        if (! $this->hasDatabase()) {
            return;
        }

        $GLOBALS['urlParams']['goto'] = Url::getFromRoute('/database/operations');

        $oldMessage = '';
        if (isset($GLOBALS['message'])) {
            $oldMessage = Generator::getMessage($GLOBALS['message'], $GLOBALS['sql_query']);
            unset($GLOBALS['message']);
        }

        $dbCollation = $this->dbi->getDbCollation($GLOBALS['db']);

        if (Utilities::isSystemSchema($GLOBALS['db'])) {
            return;
        }

        $databaseComment = '';
        if ($relationParameters->columnCommentsFeature !== null) {
            $databaseComment = $this->relation->getDbComment($GLOBALS['db']);
        }

        $hasAdjustPrivileges = $GLOBALS['db_priv'] && $GLOBALS['table_priv']
            && $GLOBALS['col_priv'] && $GLOBALS['proc_priv'] && $GLOBALS['is_reload_priv'];

        $isDropDatabaseAllowed = ($this->dbi->isSuperUser() || $GLOBALS['cfg']['AllowUserDropDatabase'])
            && $GLOBALS['db'] !== 'mysql';

        $switchToNew = isset($_SESSION['pma_switch_to_new']) && $_SESSION['pma_switch_to_new'];

        $charsets = Charsets::getCharsets($this->dbi, $GLOBALS['cfg']['Server']['DisableIS']);
        $collations = Charsets::getCollations($this->dbi, $GLOBALS['cfg']['Server']['DisableIS']);

        if (! $relationParameters->hasAllFeatures() && $GLOBALS['cfg']['PmaNoRelation_DisableWarning'] == false) {
            $GLOBALS['message'] = Message::notice(
                __(
                    'The phpMyAdmin configuration storage has been deactivated. %sFind out why%s.',
                ),
            );
            $GLOBALS['message']->addParamHtml(
                '<a href="' . Url::getFromRoute('/check-relations')
                . '" data-post="' . Url::getCommon(['db' => $GLOBALS['db']]) . '">',
            );
            $GLOBALS['message']->addParamHtml('</a>');
            /* Show error if user has configured something, notice elsewhere */
            if (! empty($GLOBALS['cfg']['Servers'][$GLOBALS['server']]['pmadb'])) {
                $GLOBALS['message']->isError(true);
            }
        }

        $this->render('database/operations/index', [
            'message' => $oldMessage,
            'db' => $GLOBALS['db'],
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
