<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions related to table indexes
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

require_once 'libraries/Template.class.php';

/**
 * Function to get the name and type of the columns of a table
 *
 * @param string $db    current database
 * @param string $table current table
 *
 * @return array
 */
function PMA_getNameAndTypeOfTheColumns($db, $table)
{
    $columns = array();
    foreach ($GLOBALS['dbi']->getColumnsFull($db, $table) as $row) {
        if (preg_match('@^(set|enum)\((.+)\)$@i', $row['Type'], $tmp)) {
            $tmp[2] = /*overload*/mb_substr(
                preg_replace('@([^,])\'\'@', '\\1\\\'', ',' . $tmp[2]), 1
            );
            $columns[$row['Field']] = $tmp[1] . '('
                . str_replace(',', ', ', $tmp[2]) . ')';
        } else {
            $columns[$row['Field']] = $row['Type'];
        }
    } // end while

    return $columns;
}

/**
 * Function to handle the creation or edit of an index
 *
 * @param string    $db    current db
 * @param string    $table current table
 * @param PMA_Index $index current index
 *
 * @return void
 */
function PMA_handleCreateOrEditIndex($db, $table, $index)
{
    $error = false;

    $sql_query = PMA_getSqlQueryForIndexCreateOrEdit($db, $table, $index, $error);

    // If there is a request for SQL previewing.
    if (isset($_REQUEST['preview_sql'])) {
        PMA_previewSQL($sql_query);
    }

    if (! $error) {
        $GLOBALS['dbi']->query($sql_query);
        $message = PMA_Message::success(
            __('Table %1$s has been altered successfully.')
        );
        $message->addParam($table);

        if ($GLOBALS['is_ajax_request'] == true) {
            $response = PMA_Response::getInstance();
            $response->addJSON(
                'message', PMA_Util::getMessage($message, $sql_query, 'success')
            );
            $response->addJSON('index_table', PMA_Index::getHtmlForIndexes($table, $db));
        } else {
            include 'tbl_structure.php';
        }
        exit;
    } else {
        $response = PMA_Response::getInstance();
        $response->isSuccess(false);
        $response->addJSON('message', $error);
        exit;
    }
}

/**
 * Function to get the sql query for index creation or edit
 *
 * @param string    $db     current db
 * @param string    $table  current table
 * @param PMA_Index $index  current index
 * @param bool      &$error whether error occurred or not
 *
 * @return string
 */
function PMA_getSqlQueryForIndexCreateOrEdit($db, $table, $index, &$error)
{
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
    switch ($index->getChoice()) {
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
        $sql_query .= ' ADD ' . $index->getChoice() . ' '
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

    $keyBlockSizes = $index->getKeyBlockSize();
    if (! empty($keyBlockSizes)) {
        $sql_query .= " KEY_BLOCK_SIZE = "
             . PMA_Util::sqlAddSlashes($keyBlockSizes);
    }

    // specifying index type is allowed only for primary, unique and index only
    $type = $index->getType();
    if ($index->getChoice() != 'SPATIAL'
        && $index->getChoice() != 'FULLTEXT'
        && in_array($type, PMA_Index::getIndexTypes())
    ) {
        $sql_query .= ' USING ' . $type;
    }

    $parser = $index->getParser();
    if ($index->getChoice() == 'FULLTEXT' && ! empty($parser)) {
        $sql_query .= " WITH PARSER " . PMA_Util::sqlAddSlashes($parser);
    }

    $comment = $index->getComment();
    if (! empty($comment)) {
        $sql_query .= " COMMENT '" . PMA_Util::sqlAddSlashes($comment) . "'";
    }

    $sql_query .= ';';

    return $sql_query;
}

/**
 * Function to prepare the form values for index
 *
 * @param string $db    current database
 * @param string $table current table
 *
 * @return PMA_Index
 */
function PMA_prepareFormValues($db, $table)
{
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

    return $index;
}

/**
 * Function to get the number of fields for the form
 *
 * @param PMA_Index $index index
 *
 * @return int
 */
function PMA_getNumberOfFieldsForForm($index)
{
    if (isset($_REQUEST['index']) && is_array($_REQUEST['index'])) {
        // coming already from form
        $add_fields
            = isset($_REQUEST['index']['columns']['names'])?
            count($_REQUEST['index']['columns']['names'])
            - $index->getColumnCount():0;
        if (isset($_REQUEST['add_fields'])) {
            $add_fields += $_REQUEST['added_fields'];
        }
    } elseif (isset($_REQUEST['create_index'])) {
        $add_fields = $_REQUEST['added_fields'];
    } else {
        $add_fields = 0;
    }// end preparing form values

    return $add_fields;
}

/**
 * Function to get form parameters
 *
 * @param string $db    current db
 * @param string $table current table
 *
 * @return array
 */
function PMA_getFormParameters($db, $table)
{
    $form_params = array(
        'db'    => $db,
        'table' => $table,
    );

    if (isset($_REQUEST['create_index'])) {
        $form_params['create_index'] = 1;
    } elseif (isset($_REQUEST['old_index'])) {
        $form_params['old_index'] = $_REQUEST['old_index'];
    } elseif (isset($_REQUEST['index'])) {
        $form_params['old_index'] = $_REQUEST['index'];
    }

    return $form_params;
}

/**
 * Function to get html for displaying the index form
 *
 * @param array     $fields      fields
 * @param PMA_Index $index       index
 * @param array     $form_params form parameters
 * @param int       $add_fields  number of fields in the form
 *
 * @return string
 */
function PMA_getHtmlForIndexForm($fields, $index, $form_params, $add_fields)
{
    return PMA\Template::get('index_form')->render(array(
        'fields' => $fields,
        'index' => $index,
        'form_params' => $form_params,
        'add_fields' => $add_fields
    ));
}
?>
