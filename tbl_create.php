<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Displays table create form and handles it
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\PMA_String;

/**
 * Get some core libraries
 */
require_once 'libraries/common.inc.php';
require_once 'libraries/create_addfield.lib.php';

// Check parameters
PMA\libraries\Util::checkParameters(array('db'));

/** @var String $pmaString */
$pmaString = $GLOBALS['PMA_String'];

/* Check if database name is empty */
if (/*overload*/mb_strlen($db) == 0) {
    PMA\libraries\Util::mysqlDie(
        __('The database name is empty!'), '', false, 'index.php'
    );
}

/**
 * Selects the database to work with
 */
if (!$GLOBALS['dbi']->selectDb($db)) {
    PMA\libraries\Util::mysqlDie(
        sprintf(__('\'%s\' database does not exist.'), htmlspecialchars($db)),
        '',
        false,
        'index.php'
    );
}

if ($GLOBALS['dbi']->getColumns($db, $table)) {
    // table exists already
    PMA\libraries\Util::mysqlDie(
        sprintf(__('Table %s already exists!'), htmlspecialchars($table)),
        '',
        false,
        'db_structure.php' . PMA_URL_getCommon(array('db' => $db))
    );
}

// for libraries/tbl_columns_definition_form.inc.php
// check number of fields to be created
$num_fields = PMA_getNumberOfFieldsFromRequest();

$action = 'tbl_create.php';

/**
 * The form used to define the structure of the table has been submitted
 */
if (isset($_REQUEST['do_save_data'])) {
    $sql_query = PMA_getTableCreationQuery($db, $table);

    // If there is a request for SQL previewing.
    if (isset($_REQUEST['preview_sql'])) {
        PMA_previewSQL($sql_query);
    }
    // Executes the query
    $result = $GLOBALS['dbi']->tryQuery($sql_query);

    if ($result) {
        // If comments were sent, enable relation stuff
        include_once 'libraries/transformations.lib.php';
        // Update comment table for mime types [MIME]
        if (isset($_REQUEST['field_mimetype'])
            && is_array($_REQUEST['field_mimetype'])
            && $cfg['BrowseMIME']
        ) {
            foreach ($_REQUEST['field_mimetype'] as $fieldindex => $mimetype) {
                if (isset($_REQUEST['field_name'][$fieldindex])
                    && /*overload*/mb_strlen($_REQUEST['field_name'][$fieldindex])
                ) {
                    PMA_setMIME(
                        $db, $table,
                        $_REQUEST['field_name'][$fieldindex], $mimetype,
                        $_REQUEST['field_transformation'][$fieldindex],
                        $_REQUEST['field_transformation_options'][$fieldindex],
                        $_REQUEST['field_input_transformation'][$fieldindex],
                        $_REQUEST['field_input_transformation_options'][$fieldindex]
                    );
                }
            }
        }
    } else {
        $response = PMA\libraries\Response::getInstance();
        $response->isSuccess(false);
        $response->addJSON('message', $GLOBALS['dbi']->getError());
    }
    exit;
} // end do create table

//This global variable needs to be reset for the headerclass to function properly
$GLOBAL['table'] = '';

$partitionDetails = array();

// Extract some partitioning and subpartitioning parameters from the request
$partitionParams = array(
    'partition_by', 'partition_expr', 'partition_count',
    'subpartition_by', 'subpartition_expr', 'subpartition_count'
);
foreach ($partitionParams as $partitionParam) {
    $partitionDetails[$partitionParam] = isset($_REQUEST[$partitionParam])
        ? $_REQUEST[$partitionParam] : '';
}

// Only LIST and RANGE type parameters allow subpartitioning
$partitionDetails['can_have_subpartitions'] = isset($_REQUEST['partition_count'])
    && $_REQUEST['partition_count'] > 1
    && isset($_REQUEST['partition_by'])
    && ($_REQUEST['partition_by'] == 'RANGE' || $_REQUEST['partition_by'] == 'LIST');

if (PMA_isValid($_REQUEST['partition_count'], 'numeric')
    && $_REQUEST['partition_count'] > 1
) { // Has partitions
    $partitions = isset($_REQUEST['partitions']) ? $_REQUEST['partitions'] : array();

    // Remove details of the additional partitions
    // when number of partitions have been reduced
    array_splice($partitions, $_REQUEST['partition_count']);

    for ($i = 0; $i < $_REQUEST['partition_count']; $i++) {
        if (! isset($partitions[$i])) { // Newly added partition
            $partitions[$i] = array(
                'value_type' => '',
                'value' => '',
                'engine' => '',
                'comment' => '',
                'data_directory' => '',
                'index_directory' => '',
                'max_rows' => '',
                'min_rows' => '',
                'tablespace' => '',
                'node_group' => '',
            );
        }

        $partition =& $partitions[$i];
        $partition['name'] = 'p' . $i;
        $partition['prefix'] = 'partitions[' . $i . ']';

        // Values are specified only for LIST and RANGE type partitions
        $partition['value_enabled'] = isset($_REQUEST['partition_by'])
            && ($_REQUEST['partition_by'] == 'RANGE'
            || $_REQUEST['partition_by'] == 'LIST');
        if (! $partition['value_enabled']) {
            $partition['value_type'] = '';
            $partition['value'] = '';
        }

        if (PMA_isValid($_REQUEST['subpartition_count'], 'numeric')
            && $_REQUEST['subpartition_count'] > 1
            && $partitionDetails['can_have_subpartitions'] == true
        ) { // Has subpartitions
            $partition['subpartition_count'] = $_REQUEST['subpartition_count'];

            if (! isset($partition['subpartitions'])) {
                $partition['subpartitions'] = array();
            }
            $subpartitions =& $partition['subpartitions'];

            // Remove details of the additional subpartitions
            // when number of subpartitions have been reduced
            array_splice($subpartitions, $_REQUEST['subpartition_count']);

            for ($j = 0; $j < $_REQUEST['subpartition_count']; $j++) {
                if (! isset($subpartitions[$j])) { // Newly added subpartition
                    $subpartitions[$j] = array(
                        'engine' => '',
                        'comment' => '',
                        'data_directory' => '',
                        'index_directory' => '',
                        'max_rows' => '',
                        'min_rows' => '',
                        'tablespace' => '',
                        'node_group' => '',
                    );
                }

                $subpartition =& $subpartitions[$j];
                $subpartition['name'] = 'p' . $i . 's' . $j;
                $subpartition['prefix'] = 'partitions[' . $i . ']'
                    . '[subpartitions][' . $j . ']';
            }
        } else { // No subpartitions
            unset($partition['subpartitions']);
            unset($partition['subpartition_count']);
        }
    }
    $partitionDetails['partitions'] = $partitions;
}

/**
 * Displays the form used to define the structure of the table
 */
require 'libraries/tbl_columns_definition_form.inc.php';
