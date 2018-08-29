<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * handles miscellaneous db operations:
 *  - move/rename
 *  - copy
 *  - changing collation
 *  - changing comment
 *  - adding tables
 *  - viewing PDF schemas
 *
 * @package PhpMyAdmin
 */
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Display\CreateTable;
use PhpMyAdmin\Message;
use PhpMyAdmin\Operations;
use PhpMyAdmin\Plugins;
use PhpMyAdmin\Plugins\Export\ExportSql;
use PhpMyAdmin\Relation;
use PhpMyAdmin\RelationCleanup;
use PhpMyAdmin\Response;
use PhpMyAdmin\Util;

/**
 * requirements
 */
require_once 'libraries/common.inc.php';

/**
 * functions implementation for this script
 */
require_once 'libraries/check_user_privileges.inc.php';

// add a javascript file for jQuery functions to handle Ajax actions
$response = Response::getInstance();
$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('db_operations.js');

$sql_query = '';

$operations = new Operations();

/**
 * Rename/move or copy database
 */
if (strlen($GLOBALS['db']) > 0
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
        if ($GLOBALS['dbi']->getLowerCaseNames() === '1') {
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
                $operations->createDbBeforeCopy();
            }

            // here I don't use DELIMITER because it's not part of the
            // language; I have to send each statement one by one

            // to avoid selecting alternatively the current and new db
            // we would need to modify the CREATE definitions to qualify
            // the db name
            $operations->runProcedureAndFunctionDefinitions($GLOBALS['db']);

            // go back to current db, just in case
            $GLOBALS['dbi']->selectDb($GLOBALS['db']);

            $tables_full = $GLOBALS['dbi']->getTablesFull($GLOBALS['db']);

            // remove all foreign key constraints, otherwise we can get errors
            /* @var $export_sql_plugin ExportSql */
            $export_sql_plugin = Plugins::getPlugin(
                "export",
                "sql",
                'libraries/classes/Plugins/Export/',
                array(
                    'single_table' => isset($single_table),
                    'export_type'  => 'database'
                )
            );

            // create stand-in tables for views
            $views = $operations->getViewsAndCreateSqlViewStandIn(
                $tables_full, $export_sql_plugin, $GLOBALS['db']
            );

            // copy tables
            $sqlConstratints = $operations->copyTables(
                $tables_full, $move, $GLOBALS['db']
            );

            // handle the views
            if (! $_error) {
                $operations->handleTheViews($views, $move, $GLOBALS['db']);
            }
            unset($views);

            // now that all tables exist, create all the accumulated constraints
            if (! $_error && count($sqlConstratints) > 0) {
                $operations->createAllAccumulatedConstraints($sqlConstratints);
            }
            unset($sqlConstratints);

            if ($GLOBALS['dbi']->getVersion() >= 50100) {
                // here DELIMITER is not used because it's not part of the
                // language; each statement is sent one by one

                $operations->runEventDefinitionsForDb($GLOBALS['db']);
            }

            // go back to current db, just in case
            $GLOBALS['dbi']->selectDb($GLOBALS['db']);

            // Duplicate the bookmarks for this db (done once for each db)
            $operations->duplicateBookmarks($_error, $GLOBALS['db']);

            if (! $_error && $move) {
                if (isset($_POST['adjust_privileges'])
                    && ! empty($_POST['adjust_privileges'])
                ) {
                    $operations->adjustPrivilegesMoveDb($GLOBALS['db'], $_POST['newname']);
                }

                /**
                 * cleanup pmadb stuff for this db
                 */
                RelationCleanup::database($GLOBALS['db']);

                // if someday the RENAME DATABASE reappears, do not DROP
                $local_query = 'DROP DATABASE '
                    . Util::backquote($GLOBALS['db']) . ';';
                $sql_query .= "\n" . $local_query;
                $GLOBALS['dbi']->query($local_query);

                $message = Message::success(
                    __('Database %1$s has been renamed to %2$s.')
                );
                $message->addParam($GLOBALS['db']);
                $message->addParam($_POST['newname']);
            } elseif (! $_error) {
                if (isset($_POST['adjust_privileges'])
                    && ! empty($_POST['adjust_privileges'])
                ) {
                    $operations->adjustPrivilegesCopyDb($GLOBALS['db'], $_POST['newname']);
                }

                $message = Message::success(
                    __('Database %1$s has been copied to %2$s.')
                );
                $message->addParam($GLOBALS['db']);
                $message->addParam($_POST['newname']);
            } else {
                $message = Message::error();
            }
            $reload     = true;

            /* Change database to be used */
            if (! $_error && $move) {
                $GLOBALS['db'] = $_POST['newname'];
            } elseif (! $_error) {
                if (isset($_POST['switch_to_new'])
                    && $_POST['switch_to_new'] == 'true'
                ) {
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
     * generate the output with {@link PhpMyAdmin\Response} and exit
     */
    if ($response->isAjax()) {
        $response->setRequestStatus($message->isSuccess());
        $response->addJSON('message', $message);
        $response->addJSON('newname', $_POST['newname']);
        $response->addJSON(
            'sql_query',
            Util::getMessage(null, $sql_query)
        );
        $response->addJSON('db', $GLOBALS['db']);
        exit;
    }
}

/**
 * Settings for relations stuff
 */
$relation = new Relation();

$cfgRelation = $relation->getRelationsParam();

/**
 * Check if comments were updated
 * (must be done before displaying the menu tabs)
 */
if (isset($_POST['comment'])) {
    $relation->setDbComment($GLOBALS['db'], $_POST['comment']);
}

require 'libraries/db_common.inc.php';
$url_query .= '&amp;goto=db_operations.php';

// Gets the database structure
$sub_part = '_structure';

list(
    $tables,
    $num_tables,
    $total_num_tables,
    $sub_part,
    $is_show_stats,
    $db_is_system_schema,
    $tooltip_truename,
    $tooltip_aliasname,
    $pos
) = Util::getDbInfo($db, isset($sub_part) ? $sub_part : '');

echo "\n";

if (isset($message)) {
    echo Util::getMessage($message, $sql_query);
    unset($message);
}

$db_collation = $GLOBALS['dbi']->getDbCollation($GLOBALS['db']);
$is_information_schema = $GLOBALS['dbi']->isSystemSchema($GLOBALS['db']);

if (!$is_information_schema) {
    if ($cfgRelation['commwork']) {
        /**
         * database comment
         */
        $response->addHTML($operations->getHtmlForDatabaseComment($GLOBALS['db']));
    }

    $response->addHTML('<div>');
    $response->addHTML(CreateTable::getHtml($db));
    $response->addHTML('</div>');

    /**
     * rename database
     */
    if ($GLOBALS['db'] != 'mysql') {
        $response->addHTML($operations->getHtmlForRenameDatabase($GLOBALS['db'], $db_collation));
    }

    // Drop link if allowed
    // Don't even try to drop information_schema.
    // You won't be able to. Believe me. You won't.
    // Don't allow to easily drop mysql database, RFE #1327514.
    if (($GLOBALS['dbi']->isSuperuser() || $GLOBALS['cfg']['AllowUserDropDatabase'])
        && ! $db_is_system_schema
        && $GLOBALS['db'] != 'mysql'
    ) {
        $response->addHTML($operations->getHtmlForDropDatabaseLink($GLOBALS['db']));
    }
    /**
     * Copy database
     */
    $response->addHTML($operations->getHtmlForCopyDatabase($GLOBALS['db'], $db_collation));

    /**
     * Change database charset
     */
    $response->addHTML($operations->getHtmlForChangeDatabaseCharset($GLOBALS['db'], $db_collation));

    if (! $cfgRelation['allworks']
        && $cfg['PmaNoRelation_DisableWarning'] == false
    ) {
        $message = Message::notice(
            __(
                'The phpMyAdmin configuration storage has been deactivated. ' .
                '%sFind out why%s.'
            )
        );
        $message->addParamHtml('<a href="./chk_rel.php" data-post="' . $url_query . '">');
        $message->addParamHtml('</a>');
        /* Show error if user has configured something, notice elsewhere */
        if (!empty($cfg['Servers'][$server]['pmadb'])) {
            $message->isError(true);
        }
    } // end if
} // end if (!$is_information_schema)
