<?php
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Database;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Common;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Display\CreateTable;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\Export\ExportSql;
use PhpMyAdmin\Relation;
use PhpMyAdmin\RelationCleanup;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;
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

    /**
     * @param Response            $response            Response object
     * @param DatabaseInterface   $dbi                 DatabaseInterface object
     * @param Template            $template            Template object
     * @param string              $db                  Database name
     * @param Operations          $operations          Operations object
     * @param CheckUserPrivileges $checkUserPrivileges CheckUserPrivileges object
     * @param Relation            $relation            Relation object
     * @param RelationCleanup     $relationCleanup     RelationCleanup object
     */
    public function __construct(
        $response,
        $dbi,
        Template $template,
        $db,
        Operations $operations,
        CheckUserPrivileges $checkUserPrivileges,
        Relation $relation,
        RelationCleanup $relationCleanup
    ) {
        parent::__construct($response, $dbi, $template, $db);
        $this->operations = $operations;
        $this->checkUserPrivileges = $checkUserPrivileges;
        $this->relation = $relation;
        $this->relationCleanup = $relationCleanup;
    }

    public function index(): void
    {
        global $cfg, $db, $server, $url_query, $sql_query, $move, $message, $tables_full;
        global $export_sql_plugin, $views, $sqlConstratints, $local_query, $reload, $url_params, $tables;
        global $total_num_tables, $sub_part, $is_show_stats, $db_is_system_schema, $tooltip_truename;
        global $db_collation, $tooltip_aliasname, $pos, $is_information_schema, $single_table, $num_tables;

        $this->checkUserPrivileges->getPrivileges();

        $header = $this->response->getHeader();
        $scripts = $header->getScripts();
        $scripts->addFile('database/operations.js');

        $sql_query = '';

        /**
         * Rename/move or copy database
         */
        if (strlen($db) > 0
            && (! empty($_POST['db_rename']) || ! empty($_POST['db_copy']))
        ) {
            if (! empty($_POST['db_rename'])) {
                $move = true;
            } else {
                $move = false;
            }

            if (! isset($_POST['newname']) || strlen($_POST['newname']) === 0) {
                $message = Message::error(__('The database name is empty!'));
            } else {
                // lower_case_table_names=1 `DB` becomes `db`
                if ($this->dbi->getLowerCaseNames() === '1') {
                    $_POST['newname'] = mb_strtolower(
                        $_POST['newname']
                    );
                }

                if ($_POST['newname'] === $_REQUEST['db']) {
                    $message = Message::error(
                        __('Cannot copy database to the same name. Change the name and try again.')
                    );
                } else {
                    $_error = false;
                    if ($move || ! empty($_POST['create_database_before_copying'])) {
                        $this->operations->createDbBeforeCopy();
                    }

                    // here I don't use DELIMITER because it's not part of the
                    // language; I have to send each statement one by one

                    // to avoid selecting alternatively the current and new db
                    // we would need to modify the CREATE definitions to qualify
                    // the db name
                    $this->operations->runProcedureAndFunctionDefinitions($db);

                    // go back to current db, just in case
                    $this->dbi->selectDb($db);

                    $tables_full = $this->dbi->getTablesFull($db);

                    // remove all foreign key constraints, otherwise we can get errors
                    /** @var ExportSql $export_sql_plugin */
                    $export_sql_plugin = Plugins::getPlugin(
                        'export',
                        'sql',
                        'libraries/classes/Plugins/Export/',
                        [
                            'single_table' => isset($single_table),
                            'export_type'  => 'database',
                        ]
                    );

                    // create stand-in tables for views
                    $views = $this->operations->getViewsAndCreateSqlViewStandIn(
                        $tables_full,
                        $export_sql_plugin,
                        $db
                    );

                    // copy tables
                    $sqlConstratints = $this->operations->copyTables(
                        $tables_full,
                        $move,
                        $db
                    );

                    // handle the views
                    if (! $_error) {
                        $this->operations->handleTheViews($views, $move, $db);
                    }
                    unset($views);

                    // now that all tables exist, create all the accumulated constraints
                    if (! $_error && count($sqlConstratints) > 0) {
                        $this->operations->createAllAccumulatedConstraints($sqlConstratints);
                    }
                    unset($sqlConstratints);

                    if ($this->dbi->getVersion() >= 50100) {
                        // here DELIMITER is not used because it's not part of the
                        // language; each statement is sent one by one

                        $this->operations->runEventDefinitionsForDb($db);
                    }

                    // go back to current db, just in case
                    $this->dbi->selectDb($db);

                    // Duplicate the bookmarks for this db (done once for each db)
                    $this->operations->duplicateBookmarks($_error, $db);

                    if (! $_error && $move) {
                        if (isset($_POST['adjust_privileges'])
                            && ! empty($_POST['adjust_privileges'])
                        ) {
                            $this->operations->adjustPrivilegesMoveDb($db, $_POST['newname']);
                        }

                        /**
                         * cleanup pmadb stuff for this db
                         */
                        $this->relationCleanup->database($db);

                        // if someday the RENAME DATABASE reappears, do not DROP
                        $local_query = 'DROP DATABASE '
                            . Util::backquote($db) . ';';
                        $sql_query .= "\n" . $local_query;
                        $this->dbi->query($local_query);

                        $message = Message::success(
                            __('Database %1$s has been renamed to %2$s.')
                        );
                        $message->addParam($db);
                        $message->addParam($_POST['newname']);
                    } elseif (! $_error) {
                        if (isset($_POST['adjust_privileges'])
                            && ! empty($_POST['adjust_privileges'])
                        ) {
                            $this->operations->adjustPrivilegesCopyDb($db, $_POST['newname']);
                        }

                        $message = Message::success(
                            __('Database %1$s has been copied to %2$s.')
                        );
                        $message->addParam($db);
                        $message->addParam($_POST['newname']);
                    } else {
                        $message = Message::error();
                    }
                    $reload = true;

                    /* Change database to be used */
                    if (! $_error && $move) {
                        $db = $_POST['newname'];
                    } elseif (! $_error) {
                        if (isset($_POST['switch_to_new'])
                            && $_POST['switch_to_new'] == 'true'
                        ) {
                            $_SESSION['pma_switch_to_new'] = true;
                            $db = $_POST['newname'];
                        } else {
                            $_SESSION['pma_switch_to_new'] = false;
                        }
                    }
                }
            }

            /**
             * Database has been successfully renamed/moved.  If in an Ajax request,
             * generate the output with {@link Response} and exit
             */
            if ($this->response->isAjax()) {
                $this->response->setRequestStatus($message->isSuccess());
                $this->response->addJSON('message', $message);
                $this->response->addJSON('newname', $_POST['newname']);
                $this->response->addJSON(
                    'sql_query',
                    Generator::getMessage(null, $sql_query)
                );
                $this->response->addJSON('db', $db);
                return;
            }
        }

        /**
         * Settings for relations stuff
         */
        $cfgRelation = $this->relation->getRelationsParam();

        /**
         * Check if comments were updated
         * (must be done before displaying the menu tabs)
         */
        if (isset($_POST['comment'])) {
            $this->relation->setDbComment($db, $_POST['comment']);
        }

        Common::database();

        $url_params['goto'] = Url::getFromRoute('/database/operations');
        $url_query .= Url::getCommon($url_params, '&');

        // Gets the database structure
        $sub_part = '_structure';

        [
            $tables,
            $num_tables,
            $total_num_tables,
            $sub_part,
            $is_show_stats,
            $db_is_system_schema,
            $tooltip_truename,
            $tooltip_aliasname,
            $pos,
        ] = Util::getDbInfo($db, $sub_part ?? '');

        $oldMessage = '';
        if (isset($message)) {
            $oldMessage = Generator::getMessage($message, $sql_query);
            unset($message);
        }

        $db_collation = $this->dbi->getDbCollation($db);
        $is_information_schema = $this->dbi->isSystemSchema($db);

        if ($is_information_schema) {
            return;
        }

        $databaseComment = '';
        if ($cfgRelation['commwork']) {
            $databaseComment = $this->relation->getDbComment($db);
        }

        $createTable = CreateTable::getHtml($db);

        $hasAdjustPrivileges = $GLOBALS['db_priv'] && $GLOBALS['table_priv']
            && $GLOBALS['col_priv'] && $GLOBALS['proc_priv'] && $GLOBALS['is_reload_priv'];

        $isDropDatabaseAllowed = ($this->dbi->isSuperuser() || $cfg['AllowUserDropDatabase'])
            && ! $db_is_system_schema && $db !== 'mysql';

        $switchToNew = isset($_SESSION['pma_switch_to_new']) && $_SESSION['pma_switch_to_new'];

        $charsets = Charsets::getCharsets($this->dbi, $GLOBALS['cfg']['Server']['DisableIS']);
        $collations = Charsets::getCollations($this->dbi, $GLOBALS['cfg']['Server']['DisableIS']);

        if (! $cfgRelation['allworks']
            && $cfg['PmaNoRelation_DisableWarning'] == false
        ) {
            $message = Message::notice(
                __(
                    'The phpMyAdmin configuration storage has been deactivated. ' .
                    '%sFind out why%s.'
                )
            );
            $message->addParamHtml('<a href="' . Url::getFromRoute('/check-relations') . '" data-post="' . $url_query . '">');
            $message->addParamHtml('</a>');
            /* Show error if user has configured something, notice elsewhere */
            if (! empty($cfg['Servers'][$server]['pmadb'])) {
                $message->isError(true);
            }
        }

        $this->response->addHTML($this->template->render('database/operations/index', [
            'message' => $oldMessage,
            'db' => $db,
            'has_comment' => $cfgRelation['commwork'],
            'db_comment' => $databaseComment,
            'create_table' => $createTable,
            'db_collation' => $db_collation,
            'has_adjust_privileges' => $hasAdjustPrivileges,
            'is_drop_database_allowed' => $isDropDatabaseAllowed,
            'switch_to_new' => $switchToNew,
            'charsets' => $charsets,
            'collations' => $collations,
        ]));
    }
}
