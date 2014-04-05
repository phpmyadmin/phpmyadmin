<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Set of functions related to applying transformations for VIEWs
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}


/**
 * Get the column details of VIEW with its original references
 *
 * @param string $sql_query    SQL for original resource
 * @param array  $view_columns Columns of VIEW if defined new column names
 *
 * @return array $column_map Details of VIEW columns
 */
function PMA_getColumnMap($sql_query, $view_columns)
{

    $column_map = array();
    // Select query which give results for VIEW
    $real_source_result = $GLOBALS['dbi']->tryQuery($sql_query);

    if ($real_source_result !== false) {

        $real_source_fields_meta = $GLOBALS['dbi']->getFieldsMeta(
            $real_source_result
        );

        if (count($real_source_fields_meta) > 0) {

            for ($i=0; $i<count($real_source_fields_meta); $i++) {

                $map = array();
                $map['table_name'] = $real_source_fields_meta[$i]->table;
                $map['refering_column'] = $real_source_fields_meta[$i]->name;

                if (count($view_columns) > 1) {
                    $map['real_column'] = $view_columns[$i];
                }

                $column_map[] = $map;

            }

        }

    }
    unset($real_source_result);

    return $column_map;

}


/**
 * Get existing data on tranformations applyed for
 * columns in a particular table
 *
 * @param string $db Database name looking for
 *
 * @return mysqli_result Result of executed SQL query
 */
function PMA_getExistingTranformationData($db)
{
    $cfgRelation = PMA_getRelationsParam();

    // Get the existing transformation details of the same database
    // from pma__column_info table
    $pma_transformation_sql = 'SELECT * FROM '
        . PMA_Util::backquote($cfgRelation['db']) . '.'
        . PMA_Util::backquote($cfgRelation['column_info'])
        . ' WHERE `db_name` = \''
        . PMA_Util::sqlAddSlashes($db) . '\'';

    return $GLOBALS['dbi']->tryQuery($pma_transformation_sql);

}


/**
 * Get SQL query for store new transformation details of a VIEW
 *
 * @param mysqli_result $pma_tranformation_data Result set of SQL execution
 * @param array         $column_map             Details of VIEW columns
 * @param string        $view_name              Name of the VIEW
 * @param string        $db                     Database name of the VIEW
 *
 * @return string $new_transformations_sql SQL query for new tranformations
 */
function PMA_getNewTransformationDataSql(
    $pma_tranformation_data, $column_map, $view_name, $db
) {
    $cfgRelation = PMA_getRelationsParam();

    // Need to store new transformation details for VIEW
    $new_transformations_sql = 'INSERT INTO '
        . PMA_Util::backquote($cfgRelation['db']) . '.'
        . PMA_Util::backquote($cfgRelation['column_info'])
        . ' (`db_name`, `table_name`, `column_name`, `comment`, '
        . '`mimetype`, `transformation`, `transformation_options`)'
        . ' VALUES ';

    $column_count = 0;
    $add_comma = false;

    while ($data_row = $GLOBALS['dbi']->fetchAssoc($pma_tranformation_data)) {

        foreach ($column_map as $column) {

            if ($data_row['table_name'] == $column['table_name']
                && $data_row['column_name'] == $column['refering_column']
            ) {

                $new_transformations_sql .= $add_comma ? ', ' : '';

                $new_transformations_sql .= '('
                    . '\'' . $db . '\', '
                    . '\'' . $view_name . '\', '
                    . '\'';

                $new_transformations_sql .= (isset($column['real_column']))
                        ? $column['real_column']
                        : $column['refering_column'];

                $new_transformations_sql .= '\', '
                    . '\'' . $data_row['comment'] . '\', '
                    . '\'' . $data_row['mimetype'] . '\', '
                    . '\'' . $data_row['transformation'] . '\', '
                    . '\''
                    . PMA_Util::sqlAddSlashes(
                        $data_row['transformation_options']
                    )
                    . '\')';

                $add_comma = true;
                $column_count++;
                break;

            }

        }

        if ($column_count == count($column_map)) {
            break;
        }

    }

    return ($column_count > 0) ? $new_transformations_sql : '';

}


?>
