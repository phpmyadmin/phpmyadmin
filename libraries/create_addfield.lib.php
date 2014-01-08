<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * set of functions for tbl_create.php and tbl_addfield.php
 *
 * @package PhpMyAdmin
 */

if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Transforms the radio button field_key into 4 arrays
 *
 * @return array An array of arrays which represents column keys for each index type
 */
function PMA_getIndexedColumns()
{
    $field_cnt      = count($_REQUEST['field_name']);
    $field_primary  = array();
    $field_index    = array();
    $field_unique   = array();
    $field_fulltext = array();
    for ($i = 0; $i < $field_cnt; ++$i) {
        if (isset($_REQUEST['field_key'][$i])
            && strlen($_REQUEST['field_name'][$i])
        ) {
            if ($_REQUEST['field_key'][$i] == 'primary_' . $i) {
                $field_primary[] = $i;
            }
            if ($_REQUEST['field_key'][$i] == 'index_' . $i) {
                $field_index[]   = $i;
            }
            if ($_REQUEST['field_key'][$i] == 'unique_' . $i) {
                $field_unique[]  = $i;
            }
            if ($_REQUEST['field_key'][$i] == 'fulltext_' . $i) {
                $field_fulltext[]  = $i;
            }
        } // end if
    } // end for

    return array(
        $field_cnt, $field_primary, $field_index, $field_unique,
        $field_fulltext
    );
}

/**
 * Initiate the column creation statement according to the table creation or
 * add columns to a existing table
 *
 * @param int     $field_cnt      number of columns
 * @param int     &$field_primary primary index field
 * @param boolean $is_create_tbl  true if requirement is to get the statement
 *                                for table creation
 *
 * @return array  $definitions An array of initial sql statements
 *                             according to the request
 */
function PMA_buildColumnCreationStatement(
    $field_cnt, &$field_primary, $is_create_tbl = true
) {
    $definitions = array();
    for ($i = 0; $i < $field_cnt; ++$i) {
        // '0' is also empty for php :-(
        if (empty($_REQUEST['field_name'][$i])
            && $_REQUEST['field_name'][$i] != '0'
        ) {
            continue;
        }

        $definition = PMA_getStatementPrefix($is_create_tbl) .
                PMA_Table::generateFieldSpec(
                    $_REQUEST['field_name'][$i],
                    $_REQUEST['field_type'][$i],
                    $i,
                    $_REQUEST['field_length'][$i],
                    $_REQUEST['field_attribute'][$i],
                    isset($_REQUEST['field_collation'][$i])
                    ? $_REQUEST['field_collation'][$i]
                    : '',
                    isset($_REQUEST['field_null'][$i])
                    ? $_REQUEST['field_null'][$i]
                    : 'NOT NULL',
                    $_REQUEST['field_default_type'][$i],
                    $_REQUEST['field_default_value'][$i],
                    isset($_REQUEST['field_extra'][$i])
                    ? $_REQUEST['field_extra'][$i]
                    : false,
                    isset($_REQUEST['field_comments'][$i])
                    ? $_REQUEST['field_comments'][$i]
                    : '',
                    $field_primary
                );


        $definition .= PMA_setColumnCreationStatementSuffix($i, $is_create_tbl);
        $definitions[] = $definition;
    } // end for

    return $definitions;
}

/**
 * Set column creation suffix according to requested position of the new column
 *
 * @param int     $current_field_num current column number
 * @param boolean $is_create_tbl     true if requirement is to get the statement
 *                                   for table creation
 *
 * @return string $sql_suffix suffix
 */
function PMA_setColumnCreationStatementSuffix($current_field_num,
    $is_create_tbl = true
) {
    // no suffix is needed if request is a table creation
    $sql_suffix = " ";
    if ($is_create_tbl) {
        return $sql_suffix;
    }

    if ($_REQUEST['field_where'] == 'last') {
        return $sql_suffix;
    }

    // Only the first field can be added somewhere other than at the end
    if ($current_field_num == 0) {
        if ($_REQUEST['field_where'] == 'first') {
            $sql_suffix .= ' FIRST';
        } else {
            $sql_suffix .= ' AFTER '
                    . PMA_Util::backquote($_REQUEST['after_field']);
        }
    } else {
        $sql_suffix .= ' AFTER '
                . PMA_Util::backquote(
                    $_REQUEST['field_name'][$current_field_num - 1]
                );
    }

    return $sql_suffix;
}

/**
 * Create relevant index statements
 *
 * @param array   $indexed_fields an array of index columns
 * @param string  $index_type     index type that which represents
 *                                the index type of $indexed_fields
 * @param boolean $is_create_tbl  true if requirement is to get the statement
 *                                for table creation
 *
 * @return array an array of sql statements for indexes
 */
function PMA_buildIndexStatements($indexed_fields, $index_type,
    $is_create_tbl = true
) {
    $statement = array();
    if (!count($indexed_fields)) {
        return $statement;
    }

    $fields = array();
    foreach ($indexed_fields as $field_nr) {
        $fields[] = PMA_Util::backquote($_REQUEST['field_name'][$field_nr]);
    }
    $statement[] = PMA_getStatementPrefix($is_create_tbl)
        . ' ' . $index_type . ' (' . implode(', ', $fields) . ') ';
    unset($fields);

    return $statement;
}

/**
 * Statement prefix for the PMA_buildColumnCreationStatement()
 *
 * @param boolean $is_create_tbl true if requirement is to get the statement
 *                               for table creation
 *
 * @return string $sql_prefix prefix
 */
function PMA_getStatementPrefix($is_create_tbl = true)
{
    $sql_prefix = " ";
    if (! $is_create_tbl) {
        $sql_prefix = ' ADD ';
    }
    return $sql_prefix;
}

/**
 * Returns sql statement according to the column and index specifications as
 * requested
 *
 * @param boolean $is_create_tbl true if requirement is to get the statement
 *                               for table creation
 *
 * @return string sql statement
 */
function PMA_getColumnCreationStatements($is_create_tbl = true)
{
    $definitions = array();
    $sql_statement = "";
    list($field_cnt, $field_primary, $field_index,
            $field_unique, $field_fulltext
            ) = PMA_getIndexedColumns();
    $definitions = PMA_buildColumnCreationStatement(
        $field_cnt, $field_primary, $is_create_tbl
    );

    // Builds the primary keys statements
    $primary_key_statements = PMA_buildIndexStatements(
        $field_primary, " PRIMARY KEY ", $is_create_tbl
    );
    $definitions = array_merge($definitions, $primary_key_statements);

    // Builds the indexes statements
    $index_statements = PMA_buildIndexStatements(
        $field_index, " INDEX ", $is_create_tbl
    );
    $definitions = array_merge($definitions, $index_statements);

    // Builds the uniques statements
    $unique_statements = PMA_buildIndexStatements(
        $field_unique, " UNIQUE ", $is_create_tbl
    );
    $definitions = array_merge($definitions, $unique_statements);

    // Builds the fulltext statements
    $fulltext_statements = PMA_buildIndexStatements(
        $field_fulltext, " FULLTEXT ", $is_create_tbl
    );
    $definitions = array_merge($definitions, $fulltext_statements);

    if (count($definitions)) {
        $sql_statement = implode(', ', $definitions);
    }
    $sql_statement = preg_replace('@, $@', '', $sql_statement);

    return $sql_statement;

}

/**
 * Function to get table creation sql query
 *
 * @param string $db    database name
 * @param string $table table name
 *
 * @return string
 */
function PMA_getTableCreationQuery($db, $table)
{
    // get column addition statements
    $sql_statement = PMA_getColumnCreationStatements(true);

    // Builds the 'create table' statement
    $sql_query = 'CREATE TABLE ' . PMA_Util::backquote($db) . '.'
        . PMA_Util::backquote($table) . ' (' . $sql_statement . ')';

    // Adds table type, character set, comments and partition definition
    if (!empty($_REQUEST['tbl_storage_engine'])
        && ($_REQUEST['tbl_storage_engine'] != 'Default')
    ) {
        $sql_query .= ' ENGINE = ' . $_REQUEST['tbl_storage_engine'];
    }
    if (!empty($_REQUEST['tbl_collation'])) {
        $sql_query .= PMA_generateCharsetQueryPart($_REQUEST['tbl_collation']);
    }
    if (!empty($_REQUEST['comment'])) {
        $sql_query .= ' COMMENT = \''
            . PMA_Util::sqlAddSlashes($_REQUEST['comment']) . '\'';
    }
    if (!empty($_REQUEST['partition_definition'])) {
        $sql_query .= ' ' . PMA_Util::sqlAddSlashes(
            $_REQUEST['partition_definition']
        );
    }
    $sql_query .= ';';

    return $sql_query;
}

/**
 * Function to get the number of fields for the table creation form
 *
 * @return int
 */
function PMA_getNumberOfFieldsFromRequest()
{
    if (isset($_REQUEST['submit_num_fields'])) {
        $num_fields = $_REQUEST['orig_num_fields'] + $_REQUEST['added_fields'];
    } elseif (isset($_REQUEST['num_fields'])
        && intval($_REQUEST['num_fields']) > 0
    ) {
        $num_fields = (int) $_REQUEST['num_fields'];
    } else {
        $num_fields = 4;
    }

    return $num_fields;
}

/**
 * Function to execute the column creation statement
 *
 * @param string $db      current database
 * @param string $table   current table
 * @param string $err_url error page url
 *
 * @return array
 */
function PMA_tryColumnCreationQuery($db, $table, $err_url)
{
    // get column addition statements
    $sql_statement = PMA_getColumnCreationStatements(false);

    // To allow replication, we first select the db to use and then run queries
    // on this db.
    $GLOBALS['dbi']->selectDb($db)
        or PMA_Util::mysqlDie(
            $GLOBALS['dbi']->getError(),
            'USE ' . PMA_Util::backquote($db), '',
            $err_url
        );
    $sql_query    = 'ALTER TABLE ' .
        PMA_Util::backquote($table) . ' ' . $sql_statement . ';';
    return array($GLOBALS['dbi']->tryQuery($sql_query) , $sql_query);
}
?>
