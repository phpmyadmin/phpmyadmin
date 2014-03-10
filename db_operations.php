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

/**
 * requirements
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/mysql_charsets.inc.php';

/**
 * functions implementation for this script
 */
require_once 'libraries/operations.lib.php';

// add a javascript file for jQuery functions to handle Ajax actions
$response = PMA_Response::getInstance();
$header = $response->getHeader();
$scripts = $header->getScripts();
$scripts->addFile('db_operations.js');

/**
 * Rename/move or copy database
 */
if (strlen($db)
    && (! empty($_REQUEST['db_rename']) || ! empty($_REQUEST['db_copy']))
) {
    if (! empty($_REQUEST['db_rename'])) {
        $move = true;
    } else {
        $move = false;
    }

    if (! isset($_REQUEST['newname']) || ! strlen($_REQUEST['newname'])) {
        $message = PMA_Message::error(__('The database name is empty!'));
    } else {
        $sql_query = ''; // in case target db exists
        $_error = false;
        if ($move
            || (isset($_REQUEST['create_database_before_copying'])
            && $_REQUEST['create_database_before_copying'])
        ) {
            $sql_query = PMA_getSqlQueryAndCreateDbBeforeCopy();
        }

        // here I don't use DELIMITER because it's not part of the
        // language; I have to send each statement one by one

        // to avoid selecting alternatively the current and new db
        // we would need to modify the CREATE definitions to qualify
        // the db name
        PMA_runProcedureAndFunctionDefinitions($db);

        // go back to current db, just in case
        $GLOBALS['dbi']->selectDb($db);

        $tables_full = $GLOBALS['dbi']->getTablesFull($db);

        include_once "libraries/plugin_interface.lib.php";
        // remove all foreign key constraints, otherwise we can get errors
        $export_sql_plugin = PMA_getPlugin(
            "export",
            "sql",
            'libraries/plugins/export/',
            array(
                'single_table' => isset($single_table),
                'export_type'  => 'database'
            )
        );
        $GLOBALS['sql_constraints_query_full_db']
            = PMA_getSqlConstraintsQueryForFullDb(
                $tables_full, $export_sql_plugin, $move, $db
            );

        $views = PMA_getViewsAndCreateSqlViewStandIn(
            $tables_full, $export_sql_plugin, $db
        );

        list($sql_query, $_error) = PMA_getSqlQueryForCopyTable(
            $tables_full, $sql_query, $move, $db
        );

        // handle the views
        if (! $_error) {
            $_error = PMA_handleTheViews($views, $move, $db);
        }
        unset($views);

        // now that all tables exist, create all the accumulated constraints
        if (! $_error && count($GLOBALS['sql_constraints_query_full_db']) > 0) {
            PMA_createAllAccumulatedConstraints();
        }

        if (! PMA_DRIZZLE && PMA_MYSQL_INT_VERSION >= 50100) {
            // here DELIMITER is not used because it's not part of the
            // language; each statement is sent one by one

            PMA_runEventDefinitionsForDb($db);
        }

        // go back to current db, just in case
        $GLOBALS['dbi']->selectDb($db);

        // Duplicate the bookmarks for this db (done once for each db)
        PMA_duplicateBookmarks($_error, $db);

        if (! $_error && $move) {
            /**
             * cleanup pmadb stuff for this db
             */
            include_once 'libraries/relation_cleanup.lib.php';
            PMA_relationsCleanupDatabase($db);

            // if someday the RENAME DATABASE reappears, do not DROP
            $local_query = 'DROP DATABASE ' . PMA_Util::backquote($db) . ';';
            $sql_query .= "\n" . $local_query;
            $GLOBALS['dbi']->query($local_query);

            $message = PMA_Message::success(
                __('Database %1$s has been renamed to %2$s.')
            );
            $message->addParam($db);
            $message->addParam($_REQUEST['newname']);
        } elseif (! $_error) {
            $message = PMA_Message::success(
                __('Database %1$s has been copied to %2$s.')
            );
            $message->addParam($db);
            $message->addParam($_REQUEST['newname']);
        }
        $reload     = true;

        /* Change database to be used */
        if (! $_error && $move) {
            $db = $_REQUEST['newname'];
        } elseif (! $_error) {
            if (isset($_REQUEST['switch_to_new'])
                && $_REQUEST['switch_to_new'] == 'true'
            ) {
                $GLOBALS['PMA_Config']->setCookie('pma_switch_to_new', 'true');
                $db = $_REQUEST['newname'];
            } else {
                $GLOBALS['PMA_Config']->setCookie('pma_switch_to_new', '');
            }
        }

        if ($_error && ! isset($message)) {
            $message = PMA_Message::error();
        }
    }

    /**
     * Database has been successfully renamed/moved.  If in an Ajax request,
     * generate the output with {@link PMA_Response} and exit
     */
    if ($GLOBALS['is_ajax_request'] == true) {
        $response = PMA_Response::getInstance();
        $response->isSuccess($message->isSuccess());
        $response->addJSON('message', $message);
        $response->addJSON('newname', $_REQUEST['newname']);
        $response->addJSON(
            'sql_query',
            PMA_Util::getMessage(null, $sql_query)
        );
        $response->addJSON('db', $db);
        exit;
    }
}

/**
 * Settings for relations stuff
 */

$cfgRelation = PMA_getRelationsParam();

/**
 * Check if comments were updated
 * (must be done before displaying the menu tabs)
 */
if (isset($_REQUEST['comment'])) {
    PMA_setDbComment($db, $_REQUEST['comment']);
}

require 'libraries/db_common.inc.php';
$url_query .= '&amp;goto=db_operations.php';

// Gets the database structure
$sub_part = '_structure';
require 'libraries/db_info.inc.php';
echo "\n";

if (isset($message)) {
    echo PMA_Util::getMessage($message, $sql_query);
    unset($message);
}

$_REQUEST['db_collation'] = PMA_getDbCollation($db);
$is_information_schema = $GLOBALS['dbi']->isSystemSchema($db);

$response->addHTML('<div id="boxContainer" data-box-width="300">');

if (!$is_information_schema) {
    if ($cfgRelation['commwork']) {
        /**
         * database comment
         */
        $response->addHTML(PMA_getHtmlForDatabaseComment($db));
    }

    $response->addHTML('<div class="operations_half_width">');
    ob_start();
    include 'libraries/display_create_table.lib.php';
    $content = ob_get_contents();
    ob_end_clean();
    $response->addHTML($content);
    $response->addHTML('</div>');

    /**
     * rename database
     */
    if ($db != 'mysql') {
        $response->addHTML(PMA_getHtmlForRenameDatabase($db));
    }

    // Drop link if allowed
    // Don't even try to drop information_schema.
    // You won't be able to. Believe me. You won't.
    // Don't allow to easily drop mysql database, RFE #1327514.
    if (($is_superuser || $GLOBALS['cfg']['AllowUserDropDatabase'])
        && ! $db_is_system_schema
        && (PMA_DRIZZLE || $db != 'mysql')
    ) {
        $response->addHTML(PMA_getHtmlForDropDatabaseLink($db));
    }
    /**
     * Copy database
     */
    $response->addHTML(PMA_getHtmlForCopyDatabase($db));

    /**
     * Change database charset
     */
    $response->addHTML(PMA_getHtmlForChangeDatabaseCharset($db, $table));

    if ($num_tables > 0
        && ! $cfgRelation['allworks']
        && $cfg['PmaNoRelation_DisableWarning'] == false
    ) {
        $message = PMA_Message::notice(
            __('The phpMyAdmin configuration storage has been deactivated. To find out why click %shere%s.')
        );
        $message->addParam(
            '<a href="' . $cfg['PmaAbsoluteUri']
            . 'chk_rel.php?' . $url_query . '">',
            false
        );
        $message->addParam('</a>', false);
        /* Show error if user has configured something, notice elsewhere */
        if (!empty($cfg['Servers'][$server]['pmadb'])) {
            $message->isError(true);
        }
        $response->addHTML('<div class="operations_full_width">');
        $response->addHTML($message->getDisplay());
        $response->addHTML('</div>');
    } // end if
} // end if (!$is_information_schema)

$response->addHTML('</div>');

// not sure about displaying the PDF dialog in case db is information_schema
if ($cfgRelation['pdfwork'] && $num_tables > 0) {
    // We only show this if we find something in the new pdf_pages table
    $test_query = '
         SELECT *
           FROM ' . PMA_Util::backquote($GLOBALS['cfgRelation']['db'])
            . '.' . PMA_Util::backquote($cfgRelation['pdf_pages']) . '
          WHERE db_name = \'' . PMA_Util::sqlAddSlashes($db) . '\'';
    $test_rs = PMA_queryAsControlUser(
        $test_query,
        null,
        PMA_DatabaseInterface::QUERY_STORE
    );

    /*
     * Export Relational Schema View
     */
    $response->addHTML(PMA_getHtmlForExportRelationalSchemaView($url_query));
} // end if

?>
