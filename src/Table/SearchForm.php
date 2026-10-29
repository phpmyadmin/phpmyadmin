<?php

declare(strict_types=1);

namespace PhpMyAdmin\Table;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Template;
use PhpMyAdmin\Util;
use PhpMyAdmin\Utils\Gis;

use function in_array;
use function is_array;
use function is_string;
use function mb_strtolower;
use function md5;
use function preg_match;
use function preg_replace;
use function str_ireplace;
use function str_replace;
use function strncasecmp;
use function strtoupper;

/**
 * Builds the column data shared by the table search and zoom search forms.
 */
final readonly class SearchForm
{
    public function __construct(
        private DatabaseInterface $dbi,
        private Relation $relation,
        private Template $template,
        private Config $config,
    ) {
    }

    /**
     * Gets all the columns of a table along with their types, collations
     * and whether null or not.
     */
    public function getColumns(string $database, string $table): SearchColumns
    {
        $names = [];
        $types = [];
        $originalTypes = [];
        $collations = [];
        $nullFlags = [];
        $hasGeometry = false;

        $geomTypes = Gis::getDataTypes();

        foreach ($this->dbi->getColumns($database, $table) as $row) {
            $names[] = $row->field;

            $type = $row->type;
            $originalTypes[] = mb_strtolower($type);
            if (in_array($type, $geomTypes, true)) {
                $hasGeometry = true;
            }

            // reformat mysql query output
            if (strncasecmp($type, 'set', 3) === 0 || strncasecmp($type, 'enum', 4) === 0) {
                $type = str_replace(',', ', ', $type);
            } else {
                // strip the "BINARY" attribute, except if we find "BINARY(" because
                // this would be a BINARY or VARBINARY column type
                if (preg_match('@BINARY[\(]@i', $type) !== 1) {
                    $type = str_ireplace('BINARY', '', $type);
                }

                $type = str_ireplace('ZEROFILL', '', $type);
                $type = str_ireplace('UNSIGNED', '', $type);
                $type = mb_strtolower($type);
            }

            if ($type === '') {
                $type = '&nbsp;';
            }

            $types[] = $type;
            $nullFlags[] = $row->isNull;
            $collations[] = $row->collation !== null && $row->collation !== '' && $row->collation !== 'NULL'
                ? $row->collation
                : '';
        }

        return new SearchColumns(
            $names,
            $types,
            $originalTypes,
            $collations,
            $nullFlags,
            $hasGeometry,
            $this->relation->getForeigners($database, $table),
        );
    }

    /**
     * Provides a column's type, collation, operators list, and criteria value
     * to display in table search form
     *
     * @param int    $searchIndex Row number in table search form
     * @param int    $columnIndex Column index in the column names list
     * @param string $formId      ID of the search form, used by the criteria input box
     *
     * @return array<string, string> Array containing column's properties
     */
    public function getColumnProperties(
        SearchColumns $columns,
        int $searchIndex,
        int $columnIndex,
        string $formId,
    ): array {
        $operators = $_POST['criteriaColumnOperators'] ?? null;
        $selectedOperator = is_array($operators) ? $operators[$searchIndex] ?? '' : '';
        $enteredValue = $_POST['criteriaValues'] ?? '';
        //Gets column's type and collation
        $type = $columns->types[$columnIndex];
        $collation = $columns->collations[$columnIndex];
        $columnName = $columns->names[$columnIndex];
        $cleanType = (string) preg_replace('@\(.*@s', '', $type);
        //Gets column's comparison operators depending on column type
        $typeOperators = $this->dbi->types->getTypeOperatorsHtml(
            $cleanType,
            $columns->nullFlags[$columnIndex],
            is_string($selectedOperator) ? $selectedOperator : '',
        );
        $func = $this->template->render('table/search/column_comparison_operators', [
            'search_index' => $searchIndex,
            'type_operators' => $typeOperators,
        ]);
        //Gets link to browse foreign data(if any) and criteria inputbox
        $foreignData = $this->relation->getForeignData($columns->foreigners, $columnName, false, '', '');
        $htmlAttributes = '';
        $isInteger = in_array($cleanType, $this->dbi->types->getIntegerTypes(), true);
        $isFloat = in_array($cleanType, $this->dbi->types->getFloatTypes(), true);
        if ($isInteger) {
            $extractedColumnspec = Util::extractColumnSpec($columns->originalTypes[$columnIndex]);
            $isUnsigned = $extractedColumnspec['unsigned'];
            $minMaxValues = $this->dbi->types->getIntegerRange($cleanType, ! $isUnsigned);
            $htmlAttributes = 'data-min="' . $minMaxValues[0] . '" '
                            . 'data-max="' . $minMaxValues[1] . '"';
        }

        $htmlAttributes .= ' onfocus="return '
                        . 'verifyAfterSearchFieldChange(' . $searchIndex . ', \'#' . $formId . '\')"';

        $foreignDropdown = '';

        $searchColumnInForeigners = $this->relation->searchColumnInForeigners($columns->foreigners, $columnName);

        $hasForeigner = $searchColumnInForeigners !== false && $searchColumnInForeigners !== [];

        if ($hasForeigner && $foreignData->dispRow !== null) {
            $foreignDropdown = $this->relation->foreignDropdown(
                $foreignData->dispRow,
                $foreignData->foreignField ?? '',
                $foreignData->foreignDisplay,
                '',
                $this->config->config->ForeignKeyMaxLimit,
            );
        }

        $value = $this->template->render('table/search/input_box', [
            'str' => '',
            'column_type' => $type,
            'column_data_type' => $isInteger ? 'INT' : ($isFloat ? 'FLOAT' : strtoupper($cleanType)),
            'html_attributes' => $htmlAttributes,
            'column_id' => 'fieldID_',
            'in_zoom_search_edit' => false,
            'column_name' => $columnName,
            'column_name_hash' => md5($columnName),
            'foreign_data' => $foreignData,
            'table' => Current::$table,
            'column_index' => $searchIndex,
            'criteria_values' => $enteredValue,
            'db' => Current::$database,
            'in_fbs' => true,
            'foreign_dropdown' => $foreignDropdown,
            'has_foreigner' => $hasForeigner,
            'is_integer' => $isInteger,
            'is_float' => $isFloat,
        ]);

        return ['type' => $type, 'collation' => $collation, 'func' => $func, 'value' => $value];
    }
}
