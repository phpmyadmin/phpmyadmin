<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * set of functions for tbl_create.php and tbl_addfield.php
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\Table;
use PMA\libraries\Util;

/**
 * Transforms the radio button field_key into 4 arrays
 *
 * @return array An array of arrays which represents column keys for each index type
 */
function PMA_getIndexedColumns()
{
    $field_cnt      = count($_REQUEST['field_name']);
    $field_primary  = json_decode($_REQUEST['primary_indexes'], true);
    $field_index    = json_decode($_REQUEST['indexes'], true);
    $field_unique   = json_decode($_REQUEST['unique_indexes'], true);
    $field_fulltext = json_decode($_REQUEST['fulltext_indexes'], true);
    $field_spatial = json_decode($_REQUEST['spatial_indexes'], true);

    return array(
        $field_cnt, $field_primary, $field_index, $field_unique,
        $field_fulltext, $field_spatial
    );
}

/**
 * Initiate the column creation statement according to the table creation or
 * add columns to a existing table
 *
 * @param int     $field_cnt     number of columns
 * @param boolean $is_create_tbl true if requirement is to get the statement
 *                               for table creation
 *
 * @return array  $definitions An array of initial sql statements
 *                             according to the request
 */
function PMA_buildColumnCreationStatement(
    $field_cnt, $is_create_tbl = true
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
                Table::generateFieldSpec(
                    trim($_REQUEST['field_name'][$i]),
                    $_REQUEST['field_type'][$i],
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
                    isset($_REQUEST['field_virtuality'][$i])
                    ? $_REQUEST['field_virtuality'][$i]
                    : '',
                    isset($_REQUEST['field_expression'][$i])
                    ? $_REQUEST['field_expression'][$i]
                    : ''
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
    $sql_suffix = ' ';
    if ($is_create_tbl) {
        return $sql_suffix;
    }

    if ((string) $_REQUEST['field_where'] === 'last') {
        return $sql_suffix;
    }

    // Only the first field can be added somewhere other than at the end
    if ((int) $current_field_num === 0) {
        if ((string) $_REQUEST['field_where'] === 'first') {
            $sql_suffix .= ' FIRST';
        } else {
            $sql_suffix .= ' AFTER '
                    . PMA\libraries\Util::backquote($_REQUEST['after_field']);
        }
    } else {
        $sql_suffix .= ' AFTER '
                . PMA\libraries\Util::backquote(
                    $_REQUEST['field_name'][$current_field_num - 1]
                );
    }

    return $sql_suffix;
}

/**
 * Create relevant index statements
 *
 * @param array   $index         an array of index columns
 * @param string  $index_choice  index choice that which represents
 *                               the index type of $indexed_fields
 * @param boolean $is_create_tbl true if requirement is to get the statement
 *                               for table creation
 *
 * @return array an array of sql statements for indexes
 */
function PMA_buildIndexStatements($index, $index_choice,
    $is_create_tbl = true
) {
    $statement = array();
    if (!count($index)) {
        return $statement;
    }

    $sql_query = PMA_getStatementPrefix($is_create_tbl)
        . ' ' . $index_choice;

    if (! empty($index['Key_name']) && $index['Key_name'] != 'PRIMARY') {
        $sql_query .= ' ' . PMA\libraries\Util::backquote($index['Key_name']);
    }

    $index_fields = array();
    foreach ($index['columns'] as $key => $column) {
        $index_fields[$key] = PMA\libraries\Util::backquote(
            $_REQUEST['field_name'][$column['col_index']]
        );
        if ($column['size']) {
            $index_fields[$key] .= '(' . $column['size'] . ')';
        }
    } // end while

    $sql_query .= ' (' . implode(', ', $index_fields) . ')';

    $keyBlockSizes = $index['Key_block_size'];
    if (! empty($keyBlockSizes)) {
        $sql_query .= " KEY_BLOCK_SIZE = "
             . $GLOBALS['dbi']->escapeString($keyBlockSizes);
    }

    // specifying index type is allowed only for primary, unique and index only
    $type = $index['Index_type'];
    if ($index['Index_choice'] != 'SPATIAL'
        && $index['Index_choice'] != 'FULLTEXT'
        && in_array($type, PMA\libraries\Index::getIndexTypes())
    ) {
        $sql_query .= ' USING ' . $type;
    }

    $parser = $index['Parser'];
    if ($index['Index_choice'] == 'FULLTEXT' && ! empty($parser)) {
        $sql_query .= " WITH PARSER " . $GLOBALS['dbi']->escapeString($parser);
    }

    $comment = $index['Index_comment'];
    if (! empty($comment)) {
        $sql_query .= " COMMENT '" . $GLOBALS['dbi']->escapeString($comment)
            . "'";
    }

    $statement[] = $sql_query;

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
 * Merge index definitions for one type of index
 *
 * @param array   $definitions     the index definitions to merge to
 * @param boolean $is_create_tbl   true if requirement is to get the statement
 *                                 for table creation
 * @param array   $indexed_columns the columns for one type of index
 * @param string  $index_keyword   the index keyword to use in the definition
 *
 * @return array $index_definitions
 */
function PMA_mergeIndexStatements(
    $definitions, $is_create_tbl, $indexed_columns, $index_keyword
) {
    foreach ($indexed_columns as $index) {
        $statements = PMA_buildIndexStatements(
            $index, " " . $index_keyword . " ", $is_create_tbl
        );
        $definitions = array_merge($definitions, $statements);
    }
    return $definitions;
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
    $sql_statement = "";
    list($field_cnt, $field_primary, $field_index,
            $field_unique, $field_fulltext, $field_spatial
            ) = PMA_getIndexedColumns();
    $definitions = PMA_buildColumnCreationStatement(
        $field_cnt, $is_create_tbl
    );

    // Builds the PRIMARY KEY statements
    $primary_key_statements = PMA_buildIndexStatements(
        isset($field_primary[0]) ? $field_primary[0] : array(),
        " PRIMARY KEY ",
        $is_create_tbl
    );
    $definitions = array_merge($definitions, $primary_key_statements);

    // Builds the INDEX statements
    $definitions = PMA_mergeIndexStatements(
        $definitions, $is_create_tbl, $field_index, "INDEX"
    );

    // Builds the UNIQUE statements
    $definitions = PMA_mergeIndexStatements(
        $definitions, $is_create_tbl, $field_unique, "UNIQUE"
    );

    // Builds the FULLTEXT statements
    $definitions = PMA_mergeIndexStatements(
        $definitions, $is_create_tbl, $field_fulltext, "FULLTEXT"
    );

    // Builds the SPATIAL statements
    $definitions = PMA_mergeIndexStatements(
        $definitions, $is_create_tbl, $field_spatial, "SPATIAL"
    );

    if (count($definitions)) {
        $sql_statement = implode(', ', $definitions);
    }
    $sql_statement = preg_replace('@, $@', '', $sql_statement);

    return $sql_statement;

}

/**
 * Returns the partitioning clause
 *
 * @return string partitioning clause
 */
function PMA_getPartitionsDefinition()
{
    $sql_query = "";
    if (! empty($_REQUEST['partition_by'])
        && ! empty($_REQUEST['partition_expr'])
        && ! empty($_REQUEST['partition_count'])
        && $_REQUEST['partition_count'] > 1
    ) {
        $sql_query .= " PARTITION BY " . $_REQUEST['partition_by']
            . " (" . $_REQUEST['partition_expr'] . ")"
            . " PARTITIONS " . $_REQUEST['partition_count'];
    }

    if (! empty($_REQUEST['subpartition_by'])
        && ! empty($_REQUEST['subpartition_expr'])
        && ! empty($_REQUEST['subpartition_count'])
        && $_REQUEST['subpartition_count'] > 1
    ) {
        $sql_query .= " SUBPARTITION BY " . $_REQUEST['subpartition_by']
           . " (" . $_REQUEST['subpartition_expr'] . ")"
           . " SUBPARTITIONS " . $_REQUEST['subpartition_count'];
    }

    if (! empty($_REQUEST['partitions'])) {
        $i = 0;
        $partitions = array();
        foreach ($_REQUEST['partitions'] as $partition) {
            $partitions[] = PMA_getPartitionDefinition($partition);
            $i++;
        }
        $sql_query .= " (" . implode(", ", $partitions) . ")";
    }

    return $sql_query;
}

/**
 * Returns the definition of a partition/subpartition
 *
 * @param array   $partition      array of partition/subpartition detiails
 * @param boolean $isSubPartition whether a subpartition
 *
 * @return string partition/subpartition definition
 */
function PMA_getPartitionDefinition($partition, $isSubPartition = false)
{
    $sql_query = " " . ($isSubPartition ? "SUB" : "") . "PARTITION ";
    $sql_query .= $partition['name'];

    if (! empty($partition['value_type'])) {
        $sql_query .= " VALUES " . $partition['value_type'];

        if ($partition['value_type'] != 'LESS THAN MAXVALUE') {
            $sql_query .= " (" . $partition['value'] . ")";
        }
    }

    if (! empty($partition['engine'])) {
        $sql_query .= " ENGINE = " . $partition['engine'];
    }
    if (! empty($partition['comment'])) {
        $sql_query .= " COMMENT = '" . $partition['comment'] . "'";
    }
    if (! empty($partition['data_directory'])) {
        $sql_query .= " DATA DIRECTORY = '" . $partition['data_directory'] . "'";
    }
    if (! empty($partition['index_directory'])) {
        $sql_query .= " INDEX_DIRECTORY = '" . $partition['index_directory'] . "'";
    }
    if (! empty($partition['max_rows'])) {
        $sql_query .= " MAX_ROWS = " . $partition['max_rows'];
    }
    if (! empty($partition['min_rows'])) {
        $sql_query .= " MIN_ROWS = " . $partition['min_rows'];
    }
    if (! empty($partition['tablespace'])) {
        $sql_query .= " TABLESPACE = " . $partition['tablespace'];
    }
    if (! empty($partition['node_group'])) {
        $sql_query .= " NODEGROUP = " . $partition['node_group'];
    }

    if (! empty($partition['subpartitions'])) {
        $j = 0;
        $subpartitions = array();
        foreach ($partition['subpartitions'] as $subpartition) {
            $subpartitions[] = PMA_getPartitionDefinition(
                $subpartition,
                true
            );
            $j++;
        }
        $sql_query .= " (" . implode(", ", $subpartitions) . ")";
    }

    return $sql_query;
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
    $sql_query = 'CREATE TABLE ' . PMA\libraries\Util::backquote($db) . '.'
        . PMA\libraries\Util::backquote(trim($table)) . ' (' . $sql_statement . ')';

    // Adds table type, character set, comments and partition definition
    if (!empty($_REQUEST['tbl_storage_engine'])
        && ($_REQUEST['tbl_storage_engine'] != 'Default')
    ) {
        $sql_query .= ' ENGINE = ' . $_REQUEST['tbl_storage_engine'];
    }
    if (!empty($_REQUEST['tbl_collation'])) {
        $sql_query .= Util::getCharsetQueryPart($_REQUEST['tbl_collation']);
    }
    if (! empty($_REQUEST['connection'])
        && ! empty($_REQUEST['tbl_storage_engine'])
        && $_REQUEST['tbl_storage_engine'] == 'FEDERATED'
    ) {
        $sql_query .= " CONNECTION = '"
            . $GLOBALS['dbi']->escapeString($_REQUEST['connection']) . "'";
    }
    if (!empty($_REQUEST['comment'])) {
        $sql_query .= ' COMMENT = \''
            . $GLOBALS['dbi']->escapeString($_REQUEST['comment']) . '\'';
    }
    $sql_query .= PMA_getPartitionsDefinition();
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
    if (isset($_REQUEST['submit_num_fields'])) { // adding new fields
        $num_fields = intval($_REQUEST['orig_num_fields']) + intval($_REQUEST['added_fields']);
    } elseif (isset($_REQUEST['orig_num_fields'])) { // retaining existing fields
        $num_fields = intval($_REQUEST['orig_num_fields']);
    } elseif (isset($_REQUEST['num_fields'])
        && intval($_REQUEST['num_fields']) > 0
    ) { // new table with specified number of fields
        $num_fields = intval($_REQUEST['num_fields']);
    } else { // new table with unspecified number of fields
        $num_fields = 4;
    }

    // Limit to 4096 fields (MySQL maximal value)
    return min($num_fields, 4096);
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
    if (!($GLOBALS['dbi']->selectDb($db))) {
        PMA\libraries\Util::mysqlDie(
            $GLOBALS['dbi']->getError(),
            'USE ' . PMA\libraries\Util::backquote($db), false,
            $err_url
        );
    }
    $sql_query    = 'ALTER TABLE ' .
        PMA\libraries\Util::backquote($table) . ' ' . $sql_statement . ';';
    // If there is a request for SQL previewing.
    if (isset($_REQUEST['preview_sql'])) {
        PMA_previewSQL($sql_query);
    }
    return array($GLOBALS['dbi']->tryQuery($sql_query) , $sql_query);
}
