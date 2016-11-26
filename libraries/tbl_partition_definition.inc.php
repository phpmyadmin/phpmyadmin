<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Table partition definition
 *
 * @package PhpMyAdmin
 */

use PMA\libraries\Template;

if (!isset($partitionDetails)) {

    $partitionDetails = array();

    // Extract some partitioning and subpartitioning parameters from the request
    $partitionParams = array(
        'partition_by', 'partition_expr',
        'subpartition_by', 'subpartition_expr',
    );
    foreach ($partitionParams as $partitionParam) {
        $partitionDetails[$partitionParam] = isset($_REQUEST[$partitionParam])
            ? $_REQUEST[$partitionParam] : '';
    }

    if (PMA_isValid($_REQUEST['partition_count'], 'numeric')) {
        // MySQL's limit is 8192, so do not allow more
        $partition_count = min(intval($_REQUEST['partition_count']), 8192);
    } else {
        $partition_count = 0;
    }
    $partitionDetails['partition_count']
        = ($partition_count === 0) ? '' : $partition_count;
    if (PMA_isValid($_REQUEST['subpartition_count'], 'numeric')) {
        // MySQL's limit is 8192, so do not allow more
        $subpartition_count = min(intval($_REQUEST['subpartition_count']), 8192);
    } else {
        $subpartition_count = 0;
    }
    $partitionDetails['subpartition_count']
        = ($subpartition_count === 0) ? '' : $subpartition_count;

    // Only LIST and RANGE type parameters allow subpartitioning
    $partitionDetails['can_have_subpartitions'] = $partition_count > 1
        && isset($_REQUEST['partition_by'])
        && ($_REQUEST['partition_by'] == 'RANGE'
        || $_REQUEST['partition_by'] == 'RANGE COLUMNS'
        || $_REQUEST['partition_by'] == 'LIST'
        || $_REQUEST['partition_by'] == 'LIST COLUMNS');

    // Values are specified only for LIST and RANGE type partitions
    $partitionDetails['value_enabled'] = isset($_REQUEST['partition_by'])
        && ($_REQUEST['partition_by'] == 'RANGE'
        || $_REQUEST['partition_by'] == 'RANGE COLUMNS'
        || $_REQUEST['partition_by'] == 'LIST'
        || $_REQUEST['partition_by'] == 'LIST COLUMNS');

    // Has partitions
    if ($partition_count > 1) {
        $partitions = isset($_REQUEST['partitions'])
            ? $_REQUEST['partitions']
            : array();

        // Remove details of the additional partitions
        // when number of partitions have been reduced
        array_splice($partitions, $partition_count);

        for ($i = 0; $i < $partition_count; $i++) {
            if (! isset($partitions[$i])) { // Newly added partition
                $partitions[$i] = array(
                    'name' => 'p' . $i,
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
            $partition['prefix'] = 'partitions[' . $i . ']';

            // Changing from HASH/KEY to RANGE/LIST
            if (! isset($partition['value_type'])) {
                $partition['value_type'] = '';
                $partition['value'] = '';
            }
            if (! isset($partition['engine'])) { // When removing subpartitioning
                $partition['engine'] = '';
                $partition['comment'] = '';
                $partition['data_directory'] = '';
                $partition['index_directory'] = '';
                $partition['max_rows'] = '';
                $partition['min_rows'] = '';
                $partition['tablespace'] = '';
                $partition['node_group'] = '';
            }

            if ($subpartition_count > 1
                && $partitionDetails['can_have_subpartitions'] == true
            ) { // Has subpartitions
                $partition['subpartition_count'] = $subpartition_count;

                if (! isset($partition['subpartitions'])) {
                    $partition['subpartitions'] = array();
                }
                $subpartitions =& $partition['subpartitions'];

                // Remove details of the additional subpartitions
                // when number of subpartitions have been reduced
                array_splice($subpartitions, $subpartition_count);

                for ($j = 0; $j < $subpartition_count; $j++) {
                    if (! isset($subpartitions[$j])) { // Newly added subpartition
                        $subpartitions[$j] = array(
                            'name' => $partition['name'] . '_s' . $j,
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
}

echo Template::get('columns_definitions/partitions')
    ->render(array('partitionDetails' => $partitionDetails));
