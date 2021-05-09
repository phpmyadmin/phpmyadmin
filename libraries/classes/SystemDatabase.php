<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use mysqli_result;

use function count;
use function sprintf;

class SystemDatabase
{
    /** @var DatabaseInterface */
    protected $dbi;

    /** @var Relation */
    private $relation;

    /**
     * Get instance of SystemDatabase
     *
     * @param DatabaseInterface $dbi Database interface for the system database
     */
    public function __construct(DatabaseInterface $dbi)
    {
        $this->dbi = $dbi;
        $this->relation = new Relation($this->dbi);
    }

    /**
     * Get existing data on transformations applied for
     * columns in a particular table
     *
     * @param string $db Database name looking for
     *
     * @return mysqli_result|false Result of executed SQL query
     */
    public function getExistingTransformationData($db)
    {
        $cfgRelation = $this->relation->getRelationsParam();

        if (! $cfgRelation['mimework']) {
            return false;
        }

        // Get the existing transformation details of the same database
        // from pma__column_info table
        $transformationSql = sprintf(
            "SELECT * FROM %s.%s WHERE `db_name` = '%s'",
            Util::backquote($cfgRelation['db']),
            Util::backquote($cfgRelation['column_info']),
            $this->dbi->escapeString($db)
        );

        return $this->dbi->tryQuery($transformationSql);
    }

    /**
     * Get SQL query for store new transformation details of a VIEW
     *
     * @param object $transformationData Result set of SQL execution
     * @param array  $columnMap          Details of VIEW columns
     * @param string $viewName           Name of the VIEW
     * @param string $db                 Database name of the VIEW
     *
     * @return string SQL query for new transformations
     */
    public function getNewTransformationDataSql(
        $transformationData,
        array $columnMap,
        $viewName,
        $db
    ) {
        $cfgRelation = $this->relation->getRelationsParam();

        // Need to store new transformation details for VIEW
        $newTransformationsSql = sprintf(
            'INSERT INTO %s.%s ('
            . '`db_name`, `table_name`, `column_name`, '
            . '`comment`, `mimetype`, `transformation`, '
            . '`transformation_options`) VALUES',
            Util::backquote($cfgRelation['db']),
            Util::backquote($cfgRelation['column_info'])
        );

        $columnCount = 0;
        $addComma = false;

        while ($dataRow = $this->dbi->fetchAssoc($transformationData)) {
            foreach ($columnMap as $column) {
                if (
                    $dataRow['table_name'] != $column['table_name']
                    || $dataRow['column_name'] != $column['refering_column']
                ) {
                    continue;
                }

                $newTransformationsSql .= sprintf(
                    "%s ('%s', '%s', '%s', '%s', '%s', '%s', '%s')",
                    $addComma ? ', ' : '',
                    $db,
                    $viewName,
                    $column['real_column'] ?? $column['refering_column'],
                    $dataRow['comment'],
                    $dataRow['mimetype'],
                    $dataRow['transformation'],
                    $GLOBALS['dbi']->escapeString(
                        $dataRow['transformation_options']
                    )
                );

                $addComma = true;
                $columnCount++;
                break;
            }

            if ($columnCount == count($columnMap)) {
                break;
            }
        }

        return $columnCount > 0 ? $newTransformationsSql : '';
    }
}
