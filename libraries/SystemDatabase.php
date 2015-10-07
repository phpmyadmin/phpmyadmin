<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold PMA\libraries\SystemDatabase class
 *
 * @package PMA
 */
namespace PMA\libraries;

require_once 'libraries/database_interface.inc.php';

/**
 * Class SystemDatabase
 *
 * @package PMA
 */
class SystemDatabase
{
    /**
     * @var DatabaseInterface
     */
    protected $dbi;

    /**
     * Get instance of SystemDatabase
     *
     * @param DatabaseInterface $dbi Database interface for the system database
     *
     */
    function __construct(DatabaseInterface $dbi)
    {
        $this->dbi = $dbi;
    }

    /**
     * Get existing data on transformations applied for
     * columns in a particular table
     *
     * @param string $db Database name looking for
     *
     * @return \mysqli_result Result of executed SQL query
     */
    public function getExistingTransformationData($db)
    {
        $cfgRelation = \PMA_getRelationsParam();

        // Get the existing transformation details of the same database
        // from pma__column_info table
        $pma_transformation_sql = sprintf(
            "SELECT * FROM %s.%s WHERE `db_name` = '%s'",
            Util::backquote($cfgRelation['db']),
            Util::backquote($cfgRelation['column_info']),
            Util::sqlAddSlashes($db)
        );

        return $this->dbi->tryQuery($pma_transformation_sql);
    }

    /**
     * Get SQL query for store new transformation details of a VIEW
     *
     * @param object $pma_transformation_data Result set of SQL execution
     * @param array  $column_map              Details of VIEW columns
     * @param string $view_name               Name of the VIEW
     * @param string $db                      Database name of the VIEW
     *
     * @return string $new_transformations_sql SQL query for new transformations
     */
    function getNewTransformationDataSql(
        $pma_transformation_data, $column_map, $view_name, $db
    ) {
        $cfgRelation = \PMA_getRelationsParam();

        // Need to store new transformation details for VIEW
        $new_transformations_sql = sprintf(
            "INSERT INTO %s.%s ("
            . "`db_name`, `table_name`, `column_name`, "
            . "`comment`, `mimetype`, `transformation`, "
            . "`transformation_options`) VALUES",
            Util::backquote($cfgRelation['db']),
            Util::backquote($cfgRelation['column_info'])
        );

        $column_count = 0;
        $add_comma = false;

        while ($data_row = $this->dbi->fetchAssoc($pma_transformation_data)) {

            foreach ($column_map as $column) {

                if ($data_row['table_name'] != $column['table_name']
                    || $data_row['column_name'] != $column['refering_column']
                ) {
                    continue;
                }

                $new_transformations_sql .= sprintf(
                    "%s ('%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                    $add_comma ? ', ' : '',
                    $db,
                    $view_name,
                    isset($column['real_column'])
                    ? $column['real_column']
                    : $column['refering_column'],
                    $data_row['comment'],
                    $data_row['mimetype'],
                    $data_row['transformation'],
                    Util::sqlAddSlashes(
                        $data_row['transformation_options']
                    )
                );

                $add_comma = true;
                $column_count++;
                break;
            }

            if ($column_count == count($column_map)) {
                break;
            }
        }

        return ($column_count > 0) ? $new_transformations_sql : '';
    }
}
