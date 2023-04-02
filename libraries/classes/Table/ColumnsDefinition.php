<?php

declare(strict_types=1);

namespace PhpMyAdmin\Table;

use PhpMyAdmin\Charsets;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Partitioning\Partition;
use PhpMyAdmin\Partitioning\TablePartitionDefinition;
use PhpMyAdmin\Query\Compatibility;
use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Table;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\Util;

use function array_keys;
use function array_merge;
use function bin2hex;
use function count;
use function explode;
use function in_array;
use function intval;
use function is_array;
use function mb_strtoupper;
use function preg_quote;
use function preg_replace;
use function rtrim;
use function stripcslashes;
use function substr;
use function trim;

/**
 * Displays the form used to define the structure of the table
 */
final class ColumnsDefinition
{
    public function __construct(
        private DatabaseInterface $dbi,
        private Relation $relation,
        private Transformations $transformations,
    ) {
    }

    /**
     * @param int          $numFields  The number of fields
     * @param string|null  $regenerate Use regeneration
     * @param mixed[]|null $selected   Selected
     * @param mixed[]|null $fieldsMeta Fields meta
     * @psalm-param '/table/create'|'/table/add-field'|'/table/structure/save' $action
     *
     * @return array<string, mixed>
     */
    public function displayForm(
        string $action,
        int $numFields = 0,
        string|null $regenerate = null,
        array|null $selected = null,
        array|null $fieldsMeta = null,
    ): array {
        $GLOBALS['col_priv'] ??= null;
        $GLOBALS['is_reload_priv'] ??= null;
        $GLOBALS['mime_map'] ??= null;

        $lengthValuesInputSize = 8;
        $contentCells = [];
        $formParams = ['db' => $GLOBALS['db']];

        if ($action === '/table/create') {
            $formParams['reload'] = 1;
        } else {
            if ($action === '/table/add-field') {
                $formParams = array_merge($formParams, ['field_where' => $_POST['field_where'] ?? null]);
                if (isset($_POST['field_where'])) {
                    $formParams['after_field'] = (string) $_POST['after_field'];
                }
            }

            $formParams['table'] = $GLOBALS['table'];
        }

        $formParams['orig_num_fields'] = $numFields;

        $formParams = array_merge(
            $formParams,
            ['orig_field_where' => $_POST['field_where'] ?? null, 'orig_after_field' => $_POST['after_field'] ?? null],
        );

        if (is_array($selected)) {
            foreach ($selected as $oFldNr => $oFldVal) {
                $formParams['selected[' . $oFldNr . ']'] = $oFldVal;
            }
        }

        $isBackup = $action !== '/table/create' && $action !== '/table/add-field';

        $relationParameters = $this->relation->getRelationParameters();

        $commentsMap = $this->relation->getComments($GLOBALS['db'], $GLOBALS['table']);

        $moveColumns = [];
        if (isset($fieldsMeta)) {
            $moveColumns = $this->dbi->getTable($GLOBALS['db'], $GLOBALS['table'])->getColumnsMeta();
        }

        $availableMime = [];
        if ($relationParameters->browserTransformationFeature !== null && $GLOBALS['cfg']['BrowseMIME']) {
            $GLOBALS['mime_map'] = $this->transformations->getMime($GLOBALS['db'], $GLOBALS['table']);
            $availableMime = $this->transformations->getAvailableMimeTypes();
        }

        $mimeTypes = ['input_transformation', 'transformation'];
        foreach ($mimeTypes as $mimeType) {
            if (! isset($availableMime[$mimeType]) || ! is_array($availableMime[$mimeType])) {
                continue;
            }

            foreach (array_keys($availableMime[$mimeType]) as $mimekey) {
                $availableMime[$mimeType . '_file_quoted'][$mimekey] = preg_quote(
                    $availableMime[$mimeType . '_file'][$mimekey],
                    '@',
                );
            }
        }

        if (isset($_POST['submit_num_fields']) || isset($_POST['submit_partition_change'])) {
            //if adding new fields, set regenerate to keep the original values
            $regenerate = 1;
        }

        $foreigners = $this->relation->getForeigners($GLOBALS['db'], $GLOBALS['table'], '', 'foreign');
        $childReferences = null;
        // From MySQL 5.6.6 onwards columns with foreign keys can be renamed.
        // Hence, no need to get child references
        if ($this->dbi->getVersion() < 50606) {
            $childReferences = $this->relation->getChildReferences($GLOBALS['db'], $GLOBALS['table']);
        }

        for ($columnNumber = 0; $columnNumber < $numFields; $columnNumber++) {
            $type = '';
            $length = '';
            $columnMeta = [];
            $submitAttribute = null;
            $extractedColumnSpec = [];

            if (! empty($regenerate)) {
                $columnMeta = array_merge(
                    $columnMeta,
                    [
                        'Field' => Util::getValueByKey(
                            $_POST,
                            'field_name.' . $columnNumber,
                            null,
                        ),
                        'Type' => Util::getValueByKey(
                            $_POST,
                            'field_type.' . $columnNumber,
                            null,
                        ),
                        'Collation' => Util::getValueByKey(
                            $_POST,
                            'field_collation.' . $columnNumber,
                            '',
                        ),
                        'Null' => Util::getValueByKey(
                            $_POST,
                            'field_null.' . $columnNumber,
                            '',
                        ),
                        'DefaultType' => Util::getValueByKey(
                            $_POST,
                            'field_default_type.' . $columnNumber,
                            'NONE',
                        ),
                        'DefaultValue' => Util::getValueByKey(
                            $_POST,
                            'field_default_value.' . $columnNumber,
                            '',
                        ),
                        'Extra' => Util::getValueByKey(
                            $_POST,
                            'field_extra.' . $columnNumber,
                            null,
                        ),
                        'Virtuality' => Util::getValueByKey(
                            $_POST,
                            'field_virtuality.' . $columnNumber,
                            '',
                        ),
                        'Expression' => Util::getValueByKey(
                            $_POST,
                            'field_expression.' . $columnNumber,
                            '',
                        ),
                    ],
                );

                $columnMeta['Key'] = '';
                $parts = explode(
                    '_',
                    Util::getValueByKey($_POST, 'field_key.' . $columnNumber, ''),
                    2,
                );
                if (count($parts) === 2 && $parts[1] == $columnNumber) {
                    $columnMeta['Key'] = Util::getValueByKey(
                        [
                            'primary' => 'PRI',
                            'index' => 'MUL',
                            'unique' => 'UNI',
                            'fulltext' => 'FULLTEXT',
                            'spatial' => 'SPATIAL',
                        ],
                        $parts[0],
                        '',
                    );
                }

                $columnMeta['Comment'] = false;

                switch ($columnMeta['DefaultType']) {
                    case 'NONE':
                        $columnMeta['Default'] = null;
                        break;
                    case 'USER_DEFINED':
                        $columnMeta['Default'] = $columnMeta['DefaultValue'];
                        break;
                    case 'NULL':
                    case 'CURRENT_TIMESTAMP':
                    case 'current_timestamp()':
                    case 'UUID':
                    case 'uuid()':
                        $columnMeta['Default'] = $columnMeta['DefaultType'];
                        break;
                }

                $length = Util::getValueByKey($_POST, 'field_length.' . $columnNumber, $length);
                $submitAttribute = Util::getValueByKey($_POST, 'field_attribute.' . $columnNumber, false);
                $commentsMap[$columnMeta['Field']] = Util::getValueByKey($_POST, 'field_comments.' . $columnNumber);

                $GLOBALS['mime_map'][$columnMeta['Field']] = array_merge(
                    $GLOBALS['mime_map'][$columnMeta['Field']] ?? [],
                    [
                        'mimetype' => Util::getValueByKey($_POST, 'field_mimetype.' . $columnNumber),
                        'transformation' => Util::getValueByKey(
                            $_POST,
                            'field_transformation.' . $columnNumber,
                        ),
                        'transformation_options' => Util::getValueByKey(
                            $_POST,
                            'field_transformation_options.' . $columnNumber,
                        ),
                    ],
                );
            } elseif (isset($fieldsMeta[$columnNumber])) {
                $columnMeta = $fieldsMeta[$columnNumber];
                $virtual = ['VIRTUAL', 'PERSISTENT', 'VIRTUAL GENERATED', 'STORED GENERATED'];
                if (in_array($columnMeta['Extra'], $virtual)) {
                    $tableObj = new Table($GLOBALS['table'], $GLOBALS['db'], $this->dbi);
                    $expressions = $tableObj->getColumnGenerationExpression($columnMeta['Field']);
                    $columnMeta['Expression'] = is_array($expressions) ? $expressions[$columnMeta['Field']] : null;
                }

                $columnMetaDefault = self::decorateColumnMetaDefault($columnMeta);
                $columnMeta = array_merge($columnMeta, $columnMetaDefault);
            }

            if (isset($columnMeta['Type'])) {
                $extractedColumnSpec = Util::extractColumnSpec($columnMeta['Type']);
                if ($extractedColumnSpec['type'] === 'bit') {
                    $columnMeta['Default'] = Util::convertBitDefaultValue($columnMeta['Default']);
                }

                $type = $extractedColumnSpec['type'];
                if ($length == '') {
                    $length = $extractedColumnSpec['spec_in_brackets'];
                }
            } else {
                // creating a column
                $columnMeta['Type'] = '';
            }

            // Variable tell if current column is bound in a foreign key constraint or not.
            // MySQL version from 5.6.6 allow renaming columns with foreign keys
            if (isset($columnMeta['Field'], $formParams['table']) && $this->dbi->getVersion() < 50606) {
                $columnMeta['column_status'] = $this->relation->checkChildForeignReferences(
                    $formParams['db'],
                    $formParams['table'],
                    $columnMeta['Field'],
                    $foreigners,
                    $childReferences,
                );
            }

            // some types, for example longtext, are reported as
            // "longtext character set latin7" when their charset and / or collation
            // differs from the ones of the corresponding database.
            // rtrim the type, for cases like "float unsigned"
            $type = rtrim(
                preg_replace('/[\s]character set[\s][\S]+/', '', $type),
            );

            /**
             * old column attributes
             */
            if ($isBackup) {
                // old column name
                if (isset($columnMeta['Field'])) {
                    $formParams['field_orig[' . $columnNumber . ']'] = $columnMeta['Field'];
                    if (isset($columnMeta['column_status']) && ! $columnMeta['column_status']['isEditable']) {
                        $formParams['field_name[' . $columnNumber . ']'] = $columnMeta['Field'];
                    }
                } else {
                    $formParams['field_orig[' . $columnNumber . ']'] = '';
                }

                // old column type
                // keep in uppercase because the new type will be in uppercase
                $formParams['field_type_orig[' . $columnNumber . ']'] = mb_strtoupper($type);
                if (isset($columnMeta['column_status']) && ! $columnMeta['column_status']['isEditable']) {
                    $formParams['field_type[' . $columnNumber . ']'] = mb_strtoupper($type);
                }

                // old column length
                $formParams['field_length_orig[' . $columnNumber . ']'] = $length;

                // old column default
                $formParams = array_merge(
                    $formParams,
                    [
                        'field_default_value_orig[' . $columnNumber . ']' => $columnMeta['Default'] ?? '',
                        'field_default_type_orig[' . $columnNumber . ']' => $columnMeta['DefaultType'] ?? '',
                        'field_collation_orig[' . $columnNumber . ']' => $columnMeta['Collation'] ?? '',
                        'field_attribute_orig[' . $columnNumber . ']' => trim($extractedColumnSpec['attribute'] ?? ''),
                        'field_null_orig[' . $columnNumber . ']' => $columnMeta['Null'] ?? '',
                        'field_extra_orig[' . $columnNumber . ']' => $columnMeta['Extra'] ?? '',
                        'field_comments_orig[' . $columnNumber . ']' => $columnMeta['Comment'] ?? '',
                        'field_virtuality_orig[' . $columnNumber . ']' => $columnMeta['Virtuality'] ?? '',
                        'field_expression_orig[' . $columnNumber . ']' => $columnMeta['Expression'] ?? '',
                    ],
                );
            }

            $defaultValue = '';
            $typeUpper = mb_strtoupper($type);

            // For a TIMESTAMP, do not show the string "CURRENT_TIMESTAMP" as a default value
            if (isset($columnMeta['DefaultValue'])) {
                $defaultValue = $columnMeta['DefaultValue'];
            }

            if ($typeUpper === 'BIT') {
                $defaultValue = ! empty($columnMeta['DefaultValue'])
                    ? Util::convertBitDefaultValue($columnMeta['DefaultValue'])
                    : '';
            } elseif ($typeUpper === 'BINARY' || $typeUpper === 'VARBINARY') {
                $defaultValue = bin2hex((string) $columnMeta['DefaultValue']);
            }

            $contentCells[$columnNumber] = [
                'column_number' => $columnNumber,
                'column_meta' => $columnMeta,
                'type_upper' => $typeUpper,
                'default_value' => $defaultValue,
                'length_values_input_size' => $lengthValuesInputSize,
                'length' => $length,
                'extracted_columnspec' => $extractedColumnSpec,
                'submit_attribute' => $submitAttribute,
                'comments_map' => $commentsMap,
                'fields_meta' => $fieldsMeta ?? null,
                'is_backup' => $isBackup,
                'move_columns' => $moveColumns,
                'available_mime' => $availableMime,
                'mime_map' => $GLOBALS['mime_map'] ?? [],
            ];
        }

        $partitionDetails = TablePartitionDefinition::getDetails();

        $charsets = Charsets::getCharsets($this->dbi, $GLOBALS['cfg']['Server']['DisableIS']);
        $collations = Charsets::getCollations($this->dbi, $GLOBALS['cfg']['Server']['DisableIS']);
        $charsetsList = [];
        foreach ($charsets as $charset) {
            $collationsList = [];
            foreach ($collations[$charset->getName()] as $collation) {
                $collationsList[] = ['name' => $collation->getName(), 'description' => $collation->getDescription()];
            }

            $charsetsList[] = [
                'name' => $charset->getName(),
                'description' => $charset->getDescription(),
                'collations' => $collationsList,
            ];
        }

        $storageEngines = StorageEngine::getArray();
        $isIntegersLengthRestricted = Compatibility::isIntegersLengthRestricted($this->dbi);

        return [
            'is_backup' => $isBackup,
            'fields_meta' => $fieldsMeta ?? null,
            'relation_parameters' => $relationParameters,
            'action' => $action,
            'form_params' => $formParams,
            'content_cells' => $contentCells,
            'partition_details' => $partitionDetails,
            'primary_indexes' => $_POST['primary_indexes'] ?? null,
            'unique_indexes' => $_POST['unique_indexes'] ?? null,
            'indexes' => $_POST['indexes'] ?? null,
            'fulltext_indexes' => $_POST['fulltext_indexes'] ?? null,
            'spatial_indexes' => $_POST['spatial_indexes'] ?? null,
            'table' => $_POST['table'] ?? null,
            'comment' => $_POST['comment'] ?? null,
            'tbl_collation' => $_POST['tbl_collation'] ?? null,
            'charsets' => $charsetsList,
            'tbl_storage_engine' => $_POST['tbl_storage_engine'] ?? null,
            'storage_engines' => $storageEngines,
            'connection' => $_POST['connection'] ?? null,
            'change_column' => $_POST['change_column'] ?? $_GET['change_column'] ?? null,
            'is_virtual_columns_supported' => Compatibility::isVirtualColumnsSupported($this->dbi->getVersion()),
            'is_integers_length_restricted' => $isIntegersLengthRestricted,
            'browse_mime' => $GLOBALS['cfg']['BrowseMIME'] ?? null,
            'supports_stored_keyword' => Compatibility::supportsStoredKeywordForVirtualColumns(
                $this->dbi->getVersion(),
            ),
            'server_version' => $this->dbi->getVersion(),
            'max_rows' => intval($GLOBALS['cfg']['MaxRows']),
            'char_editing' => $GLOBALS['cfg']['CharEditing'] ?? null,
            'attribute_types' => $this->dbi->types->getAttributes(),
            'privs_available' => ($GLOBALS['col_priv'] ?? false) && ($GLOBALS['is_reload_priv'] ?? false),
            'max_length' => $this->dbi->getVersion() >= 50503 ? 1024 : 255,
            'have_partitioning' => Partition::havePartitioning(),
            'disable_is' => $GLOBALS['cfg']['Server']['DisableIS'],
        ];
    }

    /**
     * Set default type and default value according to the column metadata
     *
     * @param array $columnMeta Column Metadata
     * @phpstan-param array<string, string|null> $columnMeta
     *
     * @return non-empty-array<array-key, mixed>
     */
    public static function decorateColumnMetaDefault(array $columnMeta): array
    {
        $metaDefault = ['DefaultType' => 'USER_DEFINED', 'DefaultValue' => ''];

        switch ($columnMeta['Default']) {
            case null:
                if ($columnMeta['Null'] === 'YES') {
                    $metaDefault['DefaultType'] = 'NULL';
                } else {
                    $metaDefault['DefaultType'] = 'NONE';
                }

                break;
            case 'CURRENT_TIMESTAMP':
            case 'current_timestamp()':
                $metaDefault['DefaultType'] = 'CURRENT_TIMESTAMP';

                break;
            case 'UUID':
            case 'uuid()':
                $metaDefault['DefaultType'] = 'UUID';

                break;
            default:
                $metaDefault['DefaultValue'] = $columnMeta['Default'];

                if (substr((string) $columnMeta['Type'], -4) === 'text') {
                    $textDefault = substr($columnMeta['Default'], 1, -1);
                    $metaDefault['Default'] = stripcslashes($textDefault);
                }

                break;
        }

        return $metaDefault;
    }
}
