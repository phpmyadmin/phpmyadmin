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
declare(strict_types=1);

use PhpMyAdmin\CheckUserPrivileges;
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

if (! defined('ROOT_PATH')) {
    define('ROOT_PATH', __DIR__ . DIRECTORY_SEPARATOR);
}

global $cfg, $db, $server, $url_query;

require_once ROOT_PATH . 'libraries/common.inc.php';

/** @var Response $response */
$response = $containerBuilder->get(Response::class);

/** @var DatabaseInterface $dbi */
$dbi = $containerBuilder->get(DatabaseInterface::class);

$checkUserPrivileges = new CheckUserPrivileges($dbi);
$checkUserPrivileges->getPrivileges();

$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('database/operations.js');

$sql_query = '';

/** @var Relation $relation */
$relation = $containerBuilder->get('relation');
$operations = new Operations($dbi, $relation);
$relationCleanup = new RelationCleanup($dbi, $relation);

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
        if ($dbi->getLowerCaseNames() === '1') {
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
            $operations->runProcedureAndFunctionDefinitions($db);

            // go back to current db, just in case
            $dbi->selectDb($db);

            $tables_full = $dbi->getTablesFull($db);

            // remove all foreign key constraints, otherwise we can get errors
            /** @var ExportSql $export_sql_plugin */
            $export_sql_plugin = Plugins::getPlugin(
                "export",
                "sql",
                'libraries/classes/Plugins/Export/',
                [
                    'single_table' => isset($single_table),
                    'export_type'  => 'database',
                ]
            );

            // create stand-in tables for views
            $views = $operations->getViewsAndCreateSqlViewStandIn(
                $tables_full,
                $export_sql_plugin,
                $db
            );

            // copy tables
            $sqlConstratints = $operations->copyTables(
                $tables_full,
                $move,
                $db
            );

            // handle the views
            if (! $_error) {
                $operations->handleTheViews($views, $move, $db);
            }
            unset($views);

            // now that all tables exist, create all the accumulated constraints
            if (! $_error && count($sqlConstratints) > 0) {
                $operations->createAllAccumulatedConstraints($sqlConstratints);
            }
            unset($sqlConstratints);

            if ($dbi->getVersion() >= 50100) {
                // here DELIMITER is not used because it's not part of the
                // language; each statement is sent one by one

                $operations->runEventDefinitionsForDb($db);
            }

            // go back to current db, just in case
            $dbi->selectDb($db);

            // Duplicate the bookmarks for this db (done once for each db)
            $operations->duplicateBookmarks($_error, $db);

            if (! $_error && $move) {
                if (isset($_POST['adjust_privileges'])
                    && ! empty($_POST['adjust_privileges'])
                ) {
                    $operations->adjustPrivilegesMoveDb($db, $_POST['newname']);
                }

                /**
                 * cleanup pmadb stuff for this db
                 */
                $relationCleanup->database($db);

                // if someday the RENAME DATABASE reappears, do not DROP
                $local_query = 'DROP DATABASE '
                    . Util::backquote($db) . ';';
                $sql_query .= "\n" . $local_query;
                $dbi->query($local_query);

                $message = Message::success(
                    __('Database %1$s has been renamed to %2$s.')
                );
                $message->addParam($db);
                $message->addParam($_POST['newname']);
            } elseif (! $_error) {
                if (isset($_POST['adjust_privileges'])
                    && ! empty($_POST['adjust_privileges'])
                ) {
                    $operations->adjustPrivilegesCopyDb($db, $_POST['newname']);
                }

                $message = Message::success(
                    __('Database %1$s has been copied to %2$s.')
                );
                $message->addParam($db);
                $message->addParam($_POST['newname']);
            } else {
                $message = Message::error();
            }
            $reload     = true;

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
        $response->addJSON('db', $db);
        exit;
    }
}

/**
 * Settings for relations stuff
 */
$cfgRelation = $relation->getRelationsParam();

/**
 * Check if comments were updated
 * (must be done before displaying the menu tabs)
 */
if (isset($_POST['comment'])) {
    $relation->setDbComment($db, $_POST['comment']);
}

require ROOT_PATH . 'libraries/db_common.inc.php';
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
) = Util::getDbInfo($db, $sub_part === null ? '' : $sub_part);

echo "\n";

if (isset($message)) {
    echo Util::getMessage($message, $sql_query);
    unset($message);
}

$db_collation = $dbi->getDbCollation($db);
$is_information_schema = $dbi->isSystemSchema($db);

if (! $is_information_schema) {
    if ($cfgRelation['commwork']) {
        /**
         * database comment
         */
        $response->addHTML($operations->getHtmlForDatabaseComment($db));
    }

    $response->addHTML('<div>');
    $response->addHTML(CreateTable::getHtml($db));
    $response->addHTML('</div>');

    /**
     * rename database
     */
    if ($db != 'mysql') {
        $response->addHTML($operations->getHtmlForRenameDatabase($db, $db_collation));
    }

    // Drop link if allowed
    // Don't even try to drop information_schema.
    // You won't be able to. Believe me. You won't.
    // Don't allow to easily drop mysql database, RFE #1327514.
    if (($dbi->isSuperuser() || $cfg['AllowUserDropDatabase'])
        && ! $db_is_system_schema
        && $db != 'mysql'
    ) {
        $response->addHTML($operations->getHtmlForDropDatabaseLink($db));
    }
    /**
     * Copy database
     */
    $response->addHTML($operations->getHtmlForCopyDatabase($db, $db_collation));

    /**
     * Change database charset
     */
    $response->addHTML($operations->getHtmlForChangeDatabaseCharset($db, $db_collation));

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
        if (! empty($cfg['Servers'][$server]['pmadb'])) {
            $message->isError(true);
        }
    } // end if
} // end if (!$is_information_schema)
