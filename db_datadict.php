<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Renders data dictionary
 *
 * @package PhpMyAdmin
 */

/**
 * Gets the variables sent or posted to this script, then displays headers
 */
require_once 'libraries/common.inc.php';

if (! isset($selected_tbl)) {
    include 'libraries/db_common.inc.php';
    include 'libraries/db_info.inc.php';
}

$response = PMA_Response::getInstance();
$header   = $response->getHeader();
$header->enablePrintView();

/**
 * Gets the relations settings
 */
$cfgRelation  = PMA_getRelationsParam();

require_once 'libraries/transformations.lib.php';
require_once 'libraries/Index.class.php';

/**
 * Check parameters
 */
PMA_Util::checkParameters(array('db'));

/**
 * Defines the url to return to in case of error in a sql statement
 */
if (strlen($table)) {
    $err_url = 'tbl_sql.php?' . PMA_URL_getCommon($db, $table);
} else {
    $err_url = 'db_sql.php?' . PMA_URL_getCommon($db);
}

if ($cfgRelation['commwork']) {
    $comment = PMA_getDbComment($db);

    /**
     * Displays DB comment
     */
    if ($comment) {
        echo '<p>' . __('Database comment:')
            . ' <i>' . htmlspecialchars($comment) . '</i></p>';
    } // end if
}

/**
 * Selects the database and gets tables names
 */
$GLOBALS['dbi']->selectDb($db);
$tables = $GLOBALS['dbi']->getTables($db);

$count  = 0;
foreach ($tables as $table) {
    $comments = PMA_getComments($db, $table);

    echo '<div>' . "\n";

    echo '<h2>' . htmlspecialchars($table) . '</h2>' . "\n";

    /**
     * Gets table informations
     */
    $show_comment = PMA_Table::sGetStatusInfo($db, $table, 'TABLE_COMMENT');

    /**
     * Gets table keys and retains them
     */

    $GLOBALS['dbi']->selectDb($db);
    $indexes      = $GLOBALS['dbi']->getTableIndexes($db, $table);
    $primary      = '';
    $indexes      = array();
    $lastIndex    = '';
    $indexes_info = array();
    $indexes_data = array();
    $pk_array     = array(); // will be use to emphasis prim. keys in the table
                             // view
    foreach ($indexes as $row) {
        // Backups the list of primary keys
        if ($row['Key_name'] == 'PRIMARY') {
            $primary   .= $row['Column_name'] . ', ';
            $pk_array[$row['Column_name']] = 1;
        }
        // Retains keys informations
        if ($row['Key_name'] != $lastIndex) {
            $indexes[] = $row['Key_name'];
            $lastIndex = $row['Key_name'];
        }
        $indexes_info[$row['Key_name']]['Sequences'][] = $row['Seq_in_index'];
        $indexes_info[$row['Key_name']]['Non_unique'] = $row['Non_unique'];
        if (isset($row['Cardinality'])) {
            $indexes_info[$row['Key_name']]['Cardinality'] = $row['Cardinality'];
        }
        // I don't know what does following column mean....
        // $indexes_info[$row['Key_name']]['Packed']          = $row['Packed'];

        $indexes_info[$row['Key_name']]['Comment'] = $row['Comment'];

        $indexes_data[$row['Key_name']][$row['Seq_in_index']]['Column_name']
            = $row['Column_name'];
        if (isset($row['Sub_part'])) {
            $indexes_data[$row['Key_name']][$row['Seq_in_index']]['Sub_part']
                = $row['Sub_part'];
        }

    } // end while

    /**
     * Gets columns properties
     */
    $columns = $GLOBALS['dbi']->getColumns($db, $table);

    if (PMA_MYSQL_INT_VERSION < 50025) {
        // We need this to correctly learn if a TIMESTAMP is NOT NULL, since
        // SHOW FULL COLUMNS or INFORMATION_SCHEMA incorrectly says NULL
        // and SHOW CREATE TABLE says NOT NULL
        // http://bugs.mysql.com/20910.

        $show_create_table_query = 'SHOW CREATE TABLE '
            . PMA_Util::backquote($db) . '.'
            . PMA_Util::backquote($table);
        $show_create_table = $GLOBALS['dbi']->fetchValue(
            $show_create_table_query, 0, 1
        );
        $analyzed_sql = PMA_SQP_analyze(PMA_SQP_parse($show_create_table));
    }

    // Check if we can use Relations
    if (!empty($cfgRelation['relation'])) {
        // Find which tables are related with the current one and write it in
        // an array
        $res_rel = PMA_getForeigners($db, $table);

        if (count($res_rel) > 0) {
            $have_rel = true;
        } else {
            $have_rel = false;
        }
    } else {
        $have_rel = false;
    } // end if


    /**
     * Displays the comments of the table if MySQL >= 3.23
     */
    if (!empty($show_comment)) {
        echo __('Table comments:') . ' ';
        echo htmlspecialchars($show_comment) . '<br /><br />';
    }

    /**
     * Displays the table structure
     */

    echo '<table width="100%" class="print">';
    echo '<tr><th width="50">' . __('Column') . '</th>';
    echo '<th width="80">' . __('Type') . '</th>';
    echo '<th width="40">' . __('Null') . '</th>';
    echo '<th width="70">' . __('Default') . '</th>';
    if ($have_rel) {
        echo '    <th>' . __('Links to') . '</th>' . "\n";
    }
    echo '    <th>' . __('Comments') . '</th>' . "\n";
    if ($cfgRelation['mimework']) {
        echo '    <th>MIME</th>' . "\n";
    }
    echo '</tr>';
    $odd_row = true;
    foreach ($columns as $row) {

        if ($row['Null'] == '') {
            $row['Null'] = 'NO';
        }
        $extracted_columnspec
            = PMA_Util::extractColumnSpec($row['Type']);

        // reformat mysql query output
        // set or enum types: slashes single quotes inside options
        if ('set' == $extracted_columnspec['type']
            || 'enum' == $extracted_columnspec['type']
        ) {
            $type_nowrap  = '';

        } else {
            $type_nowrap  = ' class="nowrap"';
        }
        $type = htmlspecialchars($extracted_columnspec['print_type']);
        $attribute     = $extracted_columnspec['attribute'];
        if (! isset($row['Default'])) {
            if ($row['Null'] != 'NO') {
                $row['Default'] = '<i>NULL</i>';
            }
        } else {
            $row['Default'] = htmlspecialchars($row['Default']);
        }
        $column_name = $row['Field'];

        $tmp_column = $analyzed_sql[0]['create_table_fields'][$column_name];
        if (PMA_MYSQL_INT_VERSION < 50025
            && ! empty($tmp_column['type'])
            && $tmp_column['type'] == 'TIMESTAMP'
            && $tmp_column['timestamp_not_null']
        ) {
            // here, we have a TIMESTAMP that SHOW FULL COLUMNS reports as
            // having the NULL attribute, but SHOW CREATE TABLE says the
            // contrary. Believe the latter.
            /**
             * @todo merge this logic with the one in tbl_structure.php
             * or move it in a function similar to $GLOBALS['dbi']->getColumnsFull()
             * but based on SHOW CREATE TABLE because information_schema
             * cannot be trusted in this case (MySQL bug)
             */
             $row['Null'] = 'NO';
        }
        echo '<tr class="';
        echo $odd_row ? 'odd' : 'even'; $odd_row = ! $odd_row;
        echo '">';
        echo '<td class="nowrap">';

        if (isset($pk_array[$row['Field']])) {
            echo '<u>' . htmlspecialchars($column_name) . '</u>';
        } else {
            echo htmlspecialchars($column_name);
        }
        echo '</td>';
        echo '<td' . $type_nowrap . ' lang="en" dir="ltr">' . $type . '</td>';
        echo '<td>';
        echo (($row['Null'] == 'NO') ? __('No') : __('Yes'));
        echo '</td>';
        echo '<td class="nowrap">';
        if (isset($row['Default'])) {
            echo $row['Default'];
        }
        echo '</td>';

        if ($have_rel) {
            echo '    <td>';
            if (isset($res_rel[$column_name])) {
                echo htmlspecialchars(
                    $res_rel[$column_name]['foreign_table']
                    . ' -> '
                    . $res_rel[$column_name]['foreign_field']
                );
            }
            echo '</td>' . "\n";
        }
        echo '    <td>';
        if (isset($comments[$column_name])) {
            echo htmlspecialchars($comments[$column_name]);
        }
        echo '</td>' . "\n";
        if ($cfgRelation['mimework']) {
            $mime_map = PMA_getMIME($db, $table, true);

            echo '    <td>';
            if (isset($mime_map[$column_name])) {
                echo htmlspecialchars(
                    str_replace('_', '/', $mime_map[$column_name]['mimetype'])
                );
            }
            echo '</td>' . "\n";
        }
        echo '</tr>';
    } // end foreach
    $count++;
    echo '</table>';
    // display indexes information
    if (count(PMA_Index::getFromTable($table, $db)) > 0) {
        echo PMA_Index::getView($table, $db, true);
    }
    echo '</div>';
} //ends main while

/**
 * Displays the footer
 */
echo PMA_Util::getButton();

?>
