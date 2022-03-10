<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationCleanup;
use PhpMyAdmin\Controllers\AbstractController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

use function __;
use function count;
use function mb_strtolower;
use function strlen;

/**
 * Handles miscellaneous database operations.
 */
class OperationsController extends AbstractController
{
    /** @var Operations */
    private $operations;

    /** @var CheckUserPrivileges */
    private $checkUserPrivileges;

    /** @var Relation */
    private $relation;

    /** @var RelationCleanup */
    private $relationCleanup;

    /** @var DatabaseInterface */
    private $dbi;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        Operations $operations,
        CheckUserPrivileges $checkUserPrivileges,
        Relation $relation,
        RelationCleanup $relationCleanup,
        DatabaseInterface $dbi
    ) {
        parent::__construct($response, $template);
        $this->operations = $operations;
        $this->checkUserPrivileges = $checkUserPrivileges;
        $this->relation = $relation;
        $this->relationCleanup = $relationCleanup;
        $this->dbi = $dbi;
    }

    public function __invoke(): void
    {
        $GLOBALS['server'] = $GLOBALS['server'] ?? null;
        $GLOBALS['move'] = $GLOBALS['move'] ?? null;
        $GLOBALS['message'] = $GLOBALS['message'] ?? null;
        $GLOBALS['tables_full'] = $GLOBALS['tables_full'] ?? null;
        $GLOBALS['errorUrl'] = $GLOBALS['errorUrl'] ?? null;
        $GLOBALS['export_sql_plugin'] = $GLOBALS['export_sql_plugin'] ?? null;
        $GLOBALS['views'] = $GLOBALS['views'] ?? null;
        $GLOBALS['sqlConstratints'] = $GLOBALS['sqlConstratints'] ?? null;
        $GLOBALS['local_query'] = $GLOBALS['local_query'] ?? null;
        $GLOBALS['reload'] = $GLOBALS['reload'] ?? null;
        $GLOBALS['urlParams'] = $GLOBALS['urlParams'] ?? null;
        $GLOBALS['tables'] = $GLOBALS['tables'] ?? null;
        $GLOBALS['total_num_tables'] = $GLOBALS['total_num_tables'] ?? null;
        $GLOBALS['sub_part'] = $GLOBALS['sub_part'] ?? null;
        $GLOBALS['tooltip_truename'] = $GLOBALS['tooltip_truename'] ?? null;
        $GLOBALS['db_collation'] = $GLOBALS['db_collation'] ?? null;
        $GLOBALS['tooltip_aliasname'] = $GLOBALS['tooltip_aliasname'] ?? null;
        $GLOBALS['pos'] = $GLOBALS['pos'] ?? null;
        $GLOBALS['is_information_schema'] = $GLOBALS['is_information_schema'] ?? null;
        $GLOBALS['single_table'] = $GLOBALS['single_table'] ?? null;
        $GLOBALS['num_tables'] = $GLOBALS['num_tables'] ?? null;

        $this->checkUserPrivileges->getPrivileges();

        $this->addScriptFiles(['database/operations.js']);

        $GLOBALS['sql_query'] = '';

        /**
         * Rename/move or copy database
         */
        if (strlen($GLOBALS['db']) > 0 && (! empty($_POST['db_rename']) || ! empty($_POST['db_copy']))) {
            if (! empty($_POST['db_rename'])) {
                $GLOBALS['move'] = true;
            } else {
                $GLOBALS['move'] = false;
            }

            if (! isset($_POST['newname']) || strlen($_POST['newname']) === 0) {
                $GLOBALS['message'] = Message::error(__('The database name is empty!'));
            } else {
                // lower_case_table_names=1 `DB` becomes `db`
                if ($this->dbi->getLowerCaseNames() === '1') {
                    $_POST['newname'] = mb_strtolower($_POST['newname']);
                }

                if ($_POST['newname'] === $_REQUEST['db']) {
                    $GLOBALS['message'] = Message::error(
                        __('Cannot copy database to the same name. Change the name and try again.')
                    );
                } else {
                    $_error = false;
                    if ($GLOBALS['move'] || ! empty($_POST['create_database_before_copying'])) {
                        $this->operations->createDbBeforeCopy();
                    }

                    // here I don't use DELIMITER because it's not part of the
                    // language; I have to send each statement one by one

                    // to avoid selecting alternatively the current and new db
                    // we would need to modify the CREATE definitions to qualify
                    // the db name
                    $this->operations->runProcedureAndFunctionDefinitions($GLOBALS['db']);

                    // go back to current db, just in case
                    $this->dbi->selectDb($GLOBALS['db']);

                    $GLOBALS['tables_full'] = $this->dbi->getTablesFull($GLOBALS['db']);

                    // remove all foreign key constraints, otherwise we can get errors
                    $GLOBALS['export_sql_plugin'] = Plugins::getPlugin('export', 'sql', [
                        'export_type' => 'database',
                        'single_table' => isset($GLOBALS['single_table']),
                    ]);

                    // create stand-in tables for views
                    $GLOBALS['views'] = $this->operations->getViewsAndCreateSqlViewStandIn(
                        $GLOBALS['tables_full'],
                        $GLOBALS['export_sql_plugin'],
                        $GLOBALS['db']
                    );

                    // copy tables
                    $GLOBALS['sqlConstratints'] = $this->operations->copyTables(
                        $GLOBALS['tables_full'],
                        $GLOBALS['move'],
                        $GLOBALS['db']
                    );

                    // handle the views
                    if (! $_error) {
                        $this->operations->handleTheViews($GLOBALS['views'], $GLOBALS['move'], $GLOBALS['db']);
                    }

                    unset($GLOBALS['views']);

                    // now that all tables exist, create all the accumulated constraints
                    if (! $_error && count($GLOBALS['sqlConstratints']) > 0) {
                        $this->operations->createAllAccumulatedConstraints($GLOBALS['sqlConstratints']);
                    }

                    unset($GLOBALS['sqlConstratints']);

                    if ($this->dbi->getVersion() >= 50100) {
                        // here DELIMITER is not used because it's not part of the
                        // language; each statement is sent one by one

                        $this->operations->runEventDefinitionsForDb($GLOBALS['db']);
                    }

                    // go back to current db, just in case
                    $this->dbi->selectDb($GLOBALS['db']);

                    // Duplicate the bookmarks for this db (done once for each db)
                    $this->operations->duplicateBookmarks($_error, $GLOBALS['db']);

                    if (! $_error && $GLOBALS['move']) {
                        if (isset($_POST['adjust_privileges']) && ! empty($_POST['adjust_privileges'])) {
                            $this->operations->adjustPrivilegesMoveDb($GLOBALS['db'], $_POST['newname']);
                        }

                        /**
                         * cleanup pmadb stuff for this db
                         */
                        $this->relationCleanup->database($GLOBALS['db']);

                        // if someday the RENAME DATABASE reappears, do not DROP
                        $GLOBALS['local_query'] = 'DROP DATABASE '
                            . Util::backquote($GLOBALS['db']) . ';';
                        $GLOBALS['sql_query'] .= "\n" . $GLOBALS['local_query'];
                        $this->dbi->query($GLOBALS['local_query']);

                        $GLOBALS['message'] = Message::success(
                            __('Database %1$s has been renamed to %2$s.')
                        );
                        $GLOBALS['message']->addParam($GLOBALS['db']);
                        $GLOBALS['message']->addParam($_POST['newname']);
                    } elseif (! $_error) {
                        if (isset($_POST['adjust_privileges']) && ! empty($_POST['adjust_privileges'])) {
                            $this->operations->adjustPrivilegesCopyDb($GLOBALS['db'], $_POST['newname']);
                        }

                        $GLOBALS['message'] = Message::success(
                            __('Database %1$s has been copied to %2$s.')
                        );
                        $GLOBALS['message']->addParam($GLOBALS['db']);
                        $GLOBALS['message']->addParam($_POST['newname']);
                    } else {
                        $GLOBALS['message'] = Message::error();
                    }

                    $GLOBALS['reload'] = true;

                    /* Change database to be used */
                    if (! $_error && $GLOBALS['move']) {
                        $GLOBALS['db'] = $_POST['newname'];
                    } elseif (! $_error) {
                        if (isset($_POST['switch_to_new']) && $_POST['switch_to_new'] === 'true') {
                            $_SESSION['pma_switch_to_new'] = true;
                            $GLOBALS['db'] = $_POST['newname'];
                        } else {
                            $_SESSION['pma_switch_to_new'] = false;
                        }
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
                $this->response->addJSON('newname', $_POST['newname']);
                $this->response->addJSON(
                    'sql_query',
                    Generator::getMessage('', $GLOBALS['sql_query'])
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
        if (isset($_POST['comment'])) {
            $this->relation->setDbComment($GLOBALS['db'], $_POST['comment']);
        }

        $this->checkParameters(['db']);

        $GLOBALS['errorUrl'] = Util::getScriptNameForOption($GLOBALS['cfg']['DefaultTabDatabase'], 'database');
        $GLOBALS['errorUrl'] .= Url::getCommon(['db' => $GLOBALS['db']], '&');

        if (! $this->hasDatabase()) {
            return;
        }

        $GLOBALS['urlParams']['goto'] = Url::getFromRoute('/database/operations');

        // Gets the database structure
        $GLOBALS['sub_part'] = '_structure';

        [
            $GLOBALS['tables'],
            $GLOBALS['num_tables'],
            $GLOBALS['total_num_tables'],
            $GLOBALS['sub_part'],,
            $isSystemSchema,
            $GLOBALS['tooltip_truename'],
            $GLOBALS['tooltip_aliasname'],
            $GLOBALS['pos'],
        ] = Util::getDbInfo($GLOBALS['db'], $GLOBALS['sub_part']);

        $oldMessage = '';
        if (isset($GLOBALS['message'])) {
            $oldMessage = Generator::getMessage($GLOBALS['message'], $GLOBALS['sql_query']);
            unset($GLOBALS['message']);
        }

        $GLOBALS['db_collation'] = $this->dbi->getDbCollation($GLOBALS['db']);
        $GLOBALS['is_information_schema'] = Utilities::isSystemSchema($GLOBALS['db']);

        if ($GLOBALS['is_information_schema']) {
            return;
        }

        $databaseComment = '';
        if ($relationParameters->columnCommentsFeature !== null) {
            $databaseComment = $this->relation->getDbComment($GLOBALS['db']);
        }

        $hasAdjustPrivileges = $GLOBALS['db_priv'] && $GLOBALS['table_priv']
            && $GLOBALS['col_priv'] && $GLOBALS['proc_priv'] && $GLOBALS['is_reload_priv'];

        $isDropDatabaseAllowed = ($this->dbi->isSuperUser() || $GLOBALS['cfg']['AllowUserDropDatabase'])
            && ! $isSystemSchema && $GLOBALS['db'] !== 'mysql';

        $switchToNew = isset($_SESSION['pma_switch_to_new']) && $_SESSION['pma_switch_to_new'];

        $charsets = Charsets::getCharsets($this->dbi, $GLOBALS['cfg']['Server']['DisableIS']);
        $collations = Charsets::getCollations($this->dbi, $GLOBALS['cfg']['Server']['DisableIS']);

        if (! $relationParameters->hasAllFeatures() && $GLOBALS['cfg']['PmaNoRelation_DisableWarning'] == false) {
            $GLOBALS['message'] = Message::notice(
                __(
                    'The phpMyAdmin configuration storage has been deactivated. %sFind out why%s.'
                )
            );
            $GLOBALS['message']->addParamHtml(
                '<a href="' . Url::getFromRoute('/check-relations')
                . '" data-post="' . Url::getCommon(['db' => $GLOBALS['db']]) . '">'
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
            'db_collation' => $GLOBALS['db_collation'],
            'has_adjust_privileges' => $hasAdjustPrivileges,
            'is_drop_database_allowed' => $isDropDatabaseAllowed,
            'switch_to_new' => $switchToNew,
            'charsets' => $charsets,
            'collations' => $collations,
        ]);
    }
}
