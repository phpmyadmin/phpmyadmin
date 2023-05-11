<?php

declare(strict_types=1);

namespace PhpMyAdmin\Table;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\Gis;

use function count;
use function explode;
use function implode;
use function in_array;
use function is_array;
use function mb_strpos;
use function preg_match;
use function str_contains;
use function str_replace;
use function strlen;
use function strncasecmp;
use function trim;

final class Search
{
    public function __construct(private DatabaseInterface $dbi)
    {
    }

    /**
     * Builds the sql search query from the post parameters
     *
     * @return string the generated SQL query
     */
    public function buildSqlQuery(): string
    {
        $sqlQuery = 'SELECT ';

        // If only distinct values are needed
        $isDistinct = isset($_POST['distinct']) ? 'true' : 'false';
        if ($isDistinct === 'true') {
            $sqlQuery .= 'DISTINCT ';
        }

        // if all column names were selected to display, we do a 'SELECT *'
        // (more efficient and this helps prevent a problem in IE
        // if one of the rows is edited and we come back to the Select results)
        if (isset($_POST['zoom_submit']) || ! empty($_POST['displayAllColumns'])) {
            $sqlQuery .= '* ';
        } else {
            $columnsToDisplay = $_POST['columnsToDisplay'];
            $quotedColumns = [];
            foreach ($columnsToDisplay as $column) {
                $quotedColumns[] = Util::backquote($column);
            }

            $sqlQuery .= implode(', ', $quotedColumns);
        }

        $sqlQuery .= ' FROM '
            . Util::backquote($_POST['table']);
        $whereClause = $this->generateWhereClause();
        $sqlQuery .= $whereClause;

        // if the search results are to be ordered
        if (isset($_POST['orderByColumn']) && $_POST['orderByColumn'] !== '--nil--') {
            $sqlQuery .= ' ORDER BY '
                . Util::backquote($_POST['orderByColumn'])
                . ' ' . $_POST['order'];
        }

        return $sqlQuery;
    }

    /**
     * Generates the where clause for the SQL search query to be executed
     *
     * @return string the generated where clause
     */
    private function generateWhereClause(): string
    {
        if (isset($_POST['customWhereClause']) && trim($_POST['customWhereClause']) != '') {
            return ' WHERE ' . $_POST['customWhereClause'];
        }

        // If there are no search criteria set or no unary criteria operators,
        // return
        if (
            ! isset($_POST['criteriaValues'])
            && ! isset($_POST['criteriaColumnOperators'])
            && ! isset($_POST['geom_func'])
        ) {
            return '';
        }

        // else continue to form the where clause from column criteria values
        $fullWhereClause = [];
        foreach ($_POST['criteriaColumnOperators'] as $columnIndex => $operator) {
            $unaryFlag = $this->dbi->types->isUnaryOperator($operator);
            $tmpGeomFunc = $_POST['geom_func'][$columnIndex] ?? null;

            $whereClause = $this->getWhereClause(
                $_POST['criteriaValues'][$columnIndex],
                $_POST['criteriaColumnNames'][$columnIndex],
                $_POST['criteriaColumnTypes'][$columnIndex],
                $operator,
                $unaryFlag,
                $tmpGeomFunc,
            );

            if (! $whereClause) {
                continue;
            }

            $fullWhereClause[] = $whereClause;
        }

        if ($fullWhereClause !== []) {
            return ' WHERE ' . implode(' AND ', $fullWhereClause);
        }

        return '';
    }

    /**
     * Return the where clause for query generation based on the inputs provided.
     *
     * @param mixed       $criteriaValues Search criteria input
     * @param string      $names          Name of the column on which search is submitted
     * @param string      $types          Type of the field
     * @param string      $funcType       Search function/operator
     * @param bool        $unaryFlag      Whether operator unary or not
     * @param string|null $geomFunc       Whether geometry functions should be applied
     *
     * @return string generated where clause.
     */
    private function getWhereClause(
        mixed $criteriaValues,
        string $names,
        string $types,
        string $funcType,
        bool $unaryFlag,
        string|null $geomFunc = null,
    ): string {
        // If geometry function is set
        if (! empty($geomFunc)) {
            return $this->getGeomWhereClause($criteriaValues, $names, $funcType, $types, $geomFunc);
        }

        $backquotedName = Util::backquote($names);
        $where = '';
        if ($unaryFlag) {
            $where = $backquotedName . ' ' . $funcType;
        } elseif (strncasecmp($types, 'enum', 4) == 0 && ! empty($criteriaValues)) {
            $where = $backquotedName;
            $where .= $this->getEnumWhereClause($criteriaValues, $funcType);
        } elseif ($criteriaValues != '') {
            // For these types we quote the value. Even if it's another type
            // (like INT), for a LIKE we always quote the value. MySQL converts
            // strings to numbers and numbers to strings as necessary
            // during the comparison
            if (
                preg_match('@char|binary|blob|text|set|date|time|year|uuid@i', $types)
                || mb_strpos(' ' . $funcType, 'LIKE')
            ) {
                $quot = '\'';
            } else {
                $quot = '';
            }

            // LIKE %...%
            if ($funcType === 'LIKE %...%') {
                $funcType = 'LIKE';
                $criteriaValues = '%' . $criteriaValues . '%';
            }

            if ($funcType === 'NOT LIKE %...%') {
                $funcType = 'NOT LIKE';
                $criteriaValues = '%' . $criteriaValues . '%';
            }

            if ($funcType === 'REGEXP ^...$') {
                $funcType = 'REGEXP';
                $criteriaValues = '^' . $criteriaValues . '$';
            }

            if (
                $funcType !== 'IN (...)'
                && $funcType !== 'NOT IN (...)'
                && $funcType !== 'BETWEEN'
                && $funcType !== 'NOT BETWEEN'
            ) {
                return $backquotedName . ' ' . $funcType . ' ' . $quot
                    . $this->dbi->escapeString($criteriaValues) . $quot;
            }

            $funcType = str_replace(' (...)', '', $funcType);

            //Don't explode if this is already an array
            //(Case for (NOT) IN/BETWEEN.)
            if (is_array($criteriaValues)) {
                $values = $criteriaValues;
            } else {
                $values = explode(',', $criteriaValues);
            }

            // quote values one by one
            $emptyKey = false;
            foreach ($values as $key => &$value) {
                if ($value === '') {
                    $emptyKey = $key;
                    $value = 'NULL';
                    continue;
                }

                $value = $quot . $this->dbi->escapeString(trim($value))
                    . $quot;
            }

            if ($funcType === 'BETWEEN' || $funcType === 'NOT BETWEEN') {
                $where = $backquotedName . ' ' . $funcType . ' '
                    . ($values[0] ?? '')
                    . ' AND ' . ($values[1] ?? '');
            } else { //[NOT] IN
                if ($emptyKey !== false) {
                    unset($values[$emptyKey]);
                }

                $wheres = [];
                if ($values !== []) {
                    $wheres[] = $backquotedName . ' ' . $funcType
                        . ' (' . implode(',', $values) . ')';
                }

                if ($emptyKey !== false) {
                    $wheres[] = $backquotedName . ' IS NULL';
                }

                $where = implode(' OR ', $wheres);
                if (1 < count($wheres)) {
                    $where = '(' . $where . ')';
                }
            }
        }

        return $where;
    }

    /**
     * Return the where clause for a geometrical column.
     *
     * @param mixed       $criteriaValues Search criteria input
     * @param string      $names          Name of the column on which search is submitted
     * @param string      $funcType       Search function/operator
     * @param string      $types          Type of the field
     * @param string|null $geomFunc       Whether geometry functions should be applied
     *
     * @return string part of where clause.
     */
    private function getGeomWhereClause(
        mixed $criteriaValues,
        string $names,
        string $funcType,
        string $types,
        string|null $geomFunc = null,
    ): string {
        $geomUnaryFunctions = ['IsEmpty' => 1, 'IsSimple' => 1, 'IsRing' => 1, 'IsClosed' => 1];
        $where = '';

        // Get details about the geometry functions
        $geomFuncs = Gis::getFunctions($types, true, false);

        // If the function takes multiple parameters
        if (str_contains($funcType, 'IS NULL') || str_contains($funcType, 'IS NOT NULL')) {
            return Util::backquote($names) . ' ' . $funcType;
        }

        if ($geomFuncs[$geomFunc]['params'] > 1) {
            // create gis data from the criteria input
            $gisData = Gis::createData($criteriaValues, $this->dbi->getVersion());

            return $geomFunc . '(' . Util::backquote($names)
                . ', ' . $gisData . ')';
        }

        // New output type is the output type of the function being applied
        $type = $geomFuncs[$geomFunc]['type'];
        $geomFunctionApplied = $geomFunc
            . '(' . Util::backquote($names) . ')';

        // If the where clause is something like 'IsEmpty(`spatial_col_name`)'
        if (isset($geomUnaryFunctions[$geomFunc]) && trim($criteriaValues) == '') {
            $where = $geomFunctionApplied;
        } elseif (in_array($type, Gis::getDataTypes()) && ! empty($criteriaValues)) {
            // create gis data from the criteria input
            $gisData = Gis::createData($criteriaValues, $this->dbi->getVersion());
            $where = $geomFunctionApplied . ' ' . $funcType . ' ' . $gisData;
        } elseif (strlen($criteriaValues) > 0) {
            $where = $geomFunctionApplied . ' '
                . $funcType . " '" . $criteriaValues . "'";
        }

        return $where;
    }

    /**
     * Return the where clause in case column's type is ENUM.
     *
     * @param mixed  $criteriaValues Search criteria input
     * @param string $funcType       Search function/operator
     *
     * @return string part of where clause.
     */
    private function getEnumWhereClause(mixed $criteriaValues, string $funcType): string
    {
        if (! is_array($criteriaValues)) {
            $criteriaValues = explode(',', $criteriaValues);
        }

        $enumSelectedCount = count($criteriaValues);
        if ($funcType === '=' && $enumSelectedCount > 1) {
            $funcType = 'IN';
            $parensOpen = '(';
            $parensClose = ')';
        } elseif ($funcType === '!=' && $enumSelectedCount > 1) {
            $funcType = 'NOT IN';
            $parensOpen = '(';
            $parensClose = ')';
        } else {
            $parensOpen = '';
            $parensClose = '';
        }

        $enumWhere = '\''
            . $this->dbi->escapeString($criteriaValues[0]) . '\'';
        for ($e = 1; $e < $enumSelectedCount; $e++) {
            $enumWhere .= ', \''
                . $this->dbi->escapeString($criteriaValues[$e]) . '\'';
        }

        return ' ' . $funcType . ' ' . $parensOpen
            . $enumWhere . $parensClose;
    }
}
