<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\ResultInterface;

use function count;
use function sprintf;

class SystemDatabase
{
    private Relation $relation;

    /**
     * Get instance of SystemDatabase
     *
     * @param DatabaseInterface $dbi Database interface for the system database
     */
    public function __construct(protected DatabaseInterface $dbi)
    {
        $this->relation = new Relation($this->dbi);
    }

    /**
     * Get existing data on transformations applied for
     * columns in a particular table
     *
     * @param string $db Database name looking for
     *
     * @return ResultInterface|false Result of executed SQL query
     */
    public function getExistingTransformationData(string $db): ResultInterface|false
    {
        $browserTransformationFeature = $this->relation->getRelationParameters()->browserTransformationFeature;
        if ($browserTransformationFeature === null) {
            return false;
        }

        // Get the existing transformation details of the same database
        // from pma__column_info table
        $transformationSql = sprintf(
            'SELECT * FROM %s.%s WHERE `db_name` = %s',
            Util::backquote($browserTransformationFeature->database),
            Util::backquote($browserTransformationFeature->columnInfo),
            $this->dbi->quoteString($db),
        );

        return $this->dbi->tryQuery($transformationSql);
    }

    /**
     * Get SQL query for store new transformation details of a VIEW
     *
     * @param ResultInterface $transformationData Result set of SQL execution
     * @param SystemColumn[]  $columnMap          Details of VIEW columns
     * @param string          $viewName           Name of the VIEW
     * @param string          $db                 Database name of the VIEW
     *
     * @return string SQL query for new transformations
     */
    public function getNewTransformationDataSql(
        ResultInterface $transformationData,
        array $columnMap,
        string $viewName,
        string $db,
    ): string {
        $browserTransformationFeature = $this->relation->getRelationParameters()->browserTransformationFeature;
        if ($browserTransformationFeature === null) {
            return '';
        }

        // Need to store new transformation details for VIEW
        $newTransformationsSql = sprintf(
            'INSERT INTO %s.%s ('
            . '`db_name`, `table_name`, `column_name`, '
            . '`comment`, `mimetype`, `transformation`, '
            . '`transformation_options`) VALUES',
            Util::backquote($browserTransformationFeature->database),
            Util::backquote($browserTransformationFeature->columnInfo),
        );

        $columnCount = 0;
        $addComma = false;

        while ($dataRow = $transformationData->fetchAssoc()) {
            foreach ($columnMap as $column) {
                if (
                    $dataRow['table_name'] != $column->tableName
                    || $dataRow['column_name'] != $column->referringColumn
                ) {
                    continue;
                }

                $newTransformationsSql .= sprintf(
                    '%s (%s, %s, %s, %s, %s, %s, %s)',
                    $addComma ? ', ' : '',
                    $this->dbi->quoteString($db),
                    $this->dbi->quoteString($viewName),
                    $this->dbi->quoteString($column->realColumn ?? $column->referringColumn),
                    $this->dbi->quoteString($dataRow['comment']),
                    $this->dbi->quoteString($dataRow['mimetype']),
                    $this->dbi->quoteString($dataRow['transformation']),
                    $this->dbi->quoteString($dataRow['transformation_options']),
                );

                $addComma = true;
                $columnCount++;
                break;
            }

            if ($columnCount === count($columnMap)) {
                break;
            }
        }

        return $columnCount > 0 ? $newTransformationsSql : '';
    }

    /**
     * @param string[] $viewColumns
     *
     * @return SystemColumn[]
     * @psalm-return list<SystemColumn>
     */
    public function getColumnMapFromSql(string $sqlQuery, array $viewColumns): array
    {
        $result = $this->dbi->tryQuery($sqlQuery);

        if ($result === false) {
            return [];
        }

        $columnMap = [];

        foreach ($this->dbi->getFieldsMeta($result) as $i => $field) {
            $columnMap[] = new SystemColumn($field->table, $field->name, $viewColumns[$i] ?? null);
        }

        return $columnMap;
    }
}
