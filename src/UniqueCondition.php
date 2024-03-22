<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\SqlParser\Components\Expression;

use function bin2hex;
use function count;
use function mb_strlen;
use function preg_replace;
use function strcasecmp;
use function trim;

class UniqueCondition
{
    private string $whereClause;
    private bool $clauseIsUnique = true;
    /** @var array<string, string> */
    private array $conditionArray = [];

    /**
     * Function to generate unique condition for specified row.
     *
     * @param FieldMetadata[] $fieldsMeta      meta information about fields
     * @param array           $row             current row
     * @param bool            $forceUnique     generate condition only on pk or unique
     * @param string|bool     $restrictToTable restrict the unique condition to this table or false if none
     * @param Expression[]    $expressions     An array of Expression instances.
     * @psalm-param array<int, mixed> $row
     */
    public function __construct(
        array $fieldsMeta,
        array $row,
        bool $forceUnique = false,
        string|bool $restrictToTable = false,
        array $expressions = [],
    ) {
        $fieldsCount = count($fieldsMeta);
        $primaryKey = '';
        $uniqueKey = '';
        $nonPrimaryCondition = '';
        $preferredCondition = '';
        $primaryKeyArray = [];
        $uniqueKeyArray = [];
        $nonPrimaryConditionArray = [];

        foreach ($fieldsMeta as $i => $meta) {
            // do not use a column alias in a condition
            if ($meta->orgname === '') {
                $meta->orgname = $meta->name;

                foreach ($expressions as $expression) {
                    if (
                        $expression->alias === null || $expression->alias === ''
                        || $expression->column === null || $expression->column === ''
                    ) {
                        continue;
                    }

                    if (strcasecmp($meta->name, $expression->alias) == 0) {
                        $meta->orgname = $expression->column;
                        break;
                    }
                }
            }

            // Do not use a table alias in a condition.
            // Test case is:
            // select * from galerie x WHERE
            //(select count(*) from galerie y where y.datum=x.datum)>1
            //
            // Also, do not use the original table name if we are dealing with
            // a view because this view might be updatable.
            // (The isView() verification should not be costly in most cases
            // because there is some caching in the function).
            if (
                $meta->table !== $meta->orgtable
                && ! DatabaseInterface::getInstance()->getTable(Current::$database, $meta->table)->isView()
            ) {
                $meta->table = $meta->orgtable;
            }

            // If this field is not from the table which the unique clause needs
            // to be restricted to.
            if ($restrictToTable && $restrictToTable != $meta->table) {
                continue;
            }

            // to fix the bug where float fields (primary or not)
            // can't be matched because of the imprecision of
            // floating comparison, use CONCAT
            // (also, the syntax "CONCAT(field) IS NULL"
            // that we need on the next "if" will work)
            if ($meta->isType(FieldMetadata::TYPE_REAL)) {
                $conKey = 'CONCAT(' . Util::backquote($meta->table) . '.'
                    . Util::backquote($meta->orgname) . ')';
            } else {
                $conKey = Util::backquote($meta->table) . '.'
                    . Util::backquote($meta->orgname);
            }

            $condition = ' ' . $conKey . ' ';

            [$conVal, $condition] = $this->getConditionValue(
                $row[$i] ?? null,
                $meta,
                $fieldsCount,
                $conKey,
                $condition,
            );

            if ($conVal === null) {
                continue;
            }

            $condition .= $conVal . ' AND';

            if ($meta->isPrimaryKey()) {
                $primaryKey .= $condition;
                $primaryKeyArray[$conKey] = $conVal;
            } elseif ($meta->isUniqueKey()) {
                $uniqueKey .= $condition;
                $uniqueKeyArray[$conKey] = $conVal;
            }

            $nonPrimaryCondition .= $condition;
            $nonPrimaryConditionArray[$conKey] = $conVal;
        }

        // Correction University of Virginia 19991216:
        // prefer primary or unique keys for condition,
        // but use conjunction of all values if no primary key
        if ($primaryKey !== '') {
            $preferredCondition = $primaryKey;
            $this->conditionArray = $primaryKeyArray;
        } elseif ($uniqueKey !== '') {
            $preferredCondition = $uniqueKey;
            $this->conditionArray = $uniqueKeyArray;
        } elseif (! $forceUnique) {
            $preferredCondition = $nonPrimaryCondition;
            $this->conditionArray = $nonPrimaryConditionArray;
            $this->clauseIsUnique = false;
        }

        $this->whereClause = trim((string) preg_replace('|\s?AND$|', '', $preferredCondition));
    }

    public function getWhereClause(): string
    {
        return $this->whereClause;
    }

    public function isClauseUnique(): bool
    {
        return $this->clauseIsUnique;
    }

    /** @return array<string, string> */
    public function getConditionArray(): array
    {
        return $this->conditionArray;
    }

    /**
     * Build a condition and with a value
     *
     * @param string|int|float|null $row          The row value
     * @param FieldMetadata         $meta         The field metadata
     * @param int                   $fieldsCount  A number of fields
     * @param string                $conditionKey A key used for BINARY fields functions
     * @param string                $condition    The condition
     *
     * @return array<int,string|null>
     * @psalm-return array{string|null, string}
     */
    private function getConditionValue(
        string|int|float|null $row,
        FieldMetadata $meta,
        int $fieldsCount,
        string $conditionKey,
        string $condition,
    ): array {
        if ($row === null) {
            return ['IS NULL', $condition];
        }

        $conditionValue = '';
        $isBinaryString = $meta->isType(FieldMetadata::TYPE_STRING) && $meta->isBinary();
        // 63 is the binary charset, see: https://dev.mysql.com/doc/internals/en/charsets.html
        $isBlobAndIsBinaryCharset = $meta->isType(FieldMetadata::TYPE_BLOB) && $meta->charsetnr === 63;
        if ($meta->isNumeric) {
            $conditionValue = '= ' . $row;
        } elseif ($isBlobAndIsBinaryCharset || ($row != 0 && $isBinaryString)) {
            // hexify only if this is a true not empty BLOB or a BINARY

            // do not waste memory building a too big condition
            $rowLength = mb_strlen((string) $row);
            if ($rowLength > 0 && $rowLength < 1000) {
                // use a CAST if possible, to avoid problems
                // if the field contains wildcard characters % or _
                $conditionValue = '= CAST(0x' . bin2hex((string) $row) . ' AS BINARY)';
            } elseif ($fieldsCount === 1) {
                // when this blob is the only field present
                // try settling with length comparison
                $condition = ' CHAR_LENGTH(' . $conditionKey . ') ';
                $conditionValue = ' = ' . $rowLength;
            } else {
                // this blob won't be part of the final condition
                $conditionValue = null;
            }
        } elseif ($meta->isMappedTypeGeometry && $row != 0) {
            // do not build a too big condition
            if (mb_strlen((string) $row) < 5000) {
                $condition .= '= CAST(0x' . bin2hex((string) $row) . ' AS BINARY)';
            } else {
                $condition = '';
            }
        } elseif ($meta->isMappedTypeBit) {
            $conditionValue = "= b'" . Util::printableBitValue((int) $row, $meta->length) . "'";
        } else {
            $conditionValue = '= ' . DatabaseInterface::getInstance()->quoteString((string) $row);
        }

        return [$conditionValue, $condition];
    }
}
