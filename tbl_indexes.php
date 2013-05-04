<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays index edit/creation form and handles it
 *
 * @package PhpMyAdmin
 */

/**
 * Gets some core libraries
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/Index.class.php';
require_once 'libraries/tbl_common.inc.php';

// Get fields and stores their name/type
$fields = array();
foreach (PMA_DBI_getColumnsFull($db, $table) as $row) {
    if (preg_match('@^(set|enum)\((.+)\)$@i', $row['Type'], $tmp)) {
        $tmp[2] = substr(
            preg_replace('@([^,])\'\'@', '\\1\\\'', ',' . $tmp[2]), 1
        );
        $fields[$row['Field']] = $tmp[1] . '('
            . str_replace(',', ', ', $tmp[2]) . ')';
    } else {
        $fields[$row['Field']] = $row['Type'];
    }
} // end while

// Prepares the form values
if (isset($_REQUEST['index'])) {
    if (is_array($_REQUEST['index'])) {
        // coming already from form
        $index = new PMA_Index($_REQUEST['index']);
    } else {
        $index = PMA_Index::singleton($db, $table, $_REQUEST['index']);
    }
} else {
    $index = new PMA_Index;
}

/**
 * Process the data from the edit/create index form,
 * run the query to build the new index
 * and moves back to "tbl_sql.php"
 */
if (isset($_REQUEST['do_save_data'])) {
    $error = false;

    // $sql_query is the one displayed in the query box
    $sql_query = 'ALTER TABLE ' . PMA_Util::backquote($db)
        . '.' . PMA_Util::backquote($table);

    // Drops the old index
    if (! empty($_REQUEST['old_index'])) {
        if ($_REQUEST['old_index'] == 'PRIMARY') {
            $sql_query .= ' DROP PRIMARY KEY,';
        } else {
            $sql_query .= ' DROP INDEX '
                . PMA_Util::backquote($_REQUEST['old_index']) . ',';
        }
    } // end if

    // Builds the new one
    switch ($index->getType()) {
    case 'PRIMARY':
        if ($index->getName() == '') {
            $index->setName('PRIMARY');
        } elseif ($index->getName() != 'PRIMARY') {
            $error = PMA_Message::error(
                __('The name of the primary key must be "PRIMARY"!')
            );
        }
        $sql_query .= ' ADD PRIMARY KEY';
        break;
    case 'FULLTEXT':
    case 'UNIQUE':
    case 'INDEX':
    case 'SPATIAL':
        if ($index->getName() == 'PRIMARY') {
            $error = PMA_Message::error(__('Can\'t rename index to PRIMARY!'));
        }
        $sql_query .= ' ADD ' . $index->getType() . ' '
            . ($index->getName() ? PMA_Util::backquote($index->getName()) : '');
        break;
    } // end switch

    $index_fields = array();
    foreach ($index->getColumns() as $key => $column) {
        $index_fields[$key] = PMA_Util::backquote($column->getName());
        if ($column->getSubPart()) {
            $index_fields[$key] .= '(' . $column->getSubPart() . ')';
        }
    } // end while

    if (empty($index_fields)) {
        $error = PMA_Message::error(__('No index parts defined!'));
    } else {
        $sql_query .= ' (' . implode(', ', $index_fields) . ')';
    }

    if (PMA_MYSQL_INT_VERSION > 50500) {
        $sql_query .= "COMMENT '" 
            . PMA_Util::sqlAddSlashes($index->getComment()) 
            . "'";
    }
    $sql_query .= ';';

    if (! $error) {
        PMA_DBI_query($sql_query);
        $message = PMA_Message::success(
            __('Table %1$s has been altered successfully')
        );
        $message->addParam($table);

        if ($GLOBALS['is_ajax_request'] == true) {
            $response = PMA_Response::getInstance();
            $response->addJSON('message', $message);
            $response->addJSON('index_table', PMA_Index::getView($table, $db));
            $response->addJSON(
                'sql_query',
                PMA_Util::getMessage(null, $sql_query)
            );
        } else {
            $active_page = 'tbl_structure.php';
            include 'tbl_structure.php';
        }
        exit;
    } else {
        if ($GLOBALS['is_ajax_request'] == true) {
            $response = PMA_Response::getInstance();
            $response->isSuccess(false);
            $response->addJSON('message', $error);
            exit;
        } else {
            $error->display();
        }
    }
} // end builds the new index


/**
 * Display the form to edit/create an index
 */
require_once 'libraries/tbl_info.inc.php';
require_once 'libraries/display_indexes.lib.php';
?>
