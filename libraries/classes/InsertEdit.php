<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\ResultInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Plugins\IOTransformationsPlugin;
use PhpMyAdmin\Plugins\TransformationsPlugin;
use PhpMyAdmin\Utils\Gis;

use function __;
use function array_fill;
use function array_key_exists;
use function array_merge;
use function array_values;
use function bin2hex;
use function class_exists;
use function count;
use function current;
use function date;
use function explode;
use function htmlspecialchars;
use function implode;
use function in_array;
use function is_array;
use function is_file;
use function is_string;
use function json_encode;
use function max;
use function mb_stripos;
use function mb_strlen;
use function mb_strstr;
use function md5;
use function min;
use function password_hash;
use function preg_match;
use function preg_replace;
use function str_contains;
use function str_ends_with;
use function str_replace;
use function str_starts_with;
use function stripcslashes;
use function stripslashes;
use function strlen;
use function substr;
use function time;
use function trim;

use const ENT_COMPAT;
use const PASSWORD_DEFAULT;

class InsertEdit
{
    private const FUNC_OPTIONAL_PARAM = ['RAND', 'UNIX_TIMESTAMP'];

    private const FUNC_NO_PARAM = [
        'CONNECTION_ID',
        'CURRENT_USER',
        'CURDATE',
        'CURTIME',
        'CURRENT_DATE',
        'CURRENT_TIME',
        'DATABASE',
        'LAST_INSERT_ID',
        'NOW',
        'PI',
        'RAND',
        'SYSDATE',
        'UNIX_TIMESTAMP',
        'USER',
        'UTC_DATE',
        'UTC_TIME',
        'UTC_TIMESTAMP',
        'UUID',
        'UUID_SHORT',
        'VERSION',
    ];

    private int $rowOffset = 0;
    private int $fieldIndex = 0;

    public function __construct(
        private DatabaseInterface $dbi,
        private Relation $relation,
        private Transformations $transformations,
        private FileListing $fileListing,
        private Template $template,
    ) {
    }

    /**
     * Retrieve form parameters for insert/edit form
     *
     * @param string  $db               name of the database
     * @param string  $table            name of the table
     * @param mixed[] $whereClauseArray
     *
     * @return array<string, string> array of insert/edit form parameters
     */
    public function getFormParametersForInsertForm(
        string $db,
        string $table,
        array|null $whereClauses,
        array $whereClauseArray,
        string $errorUrl,
    ): array {
        $formParams = [
            'db' => $db,
            'table' => $table,
            'goto' => $GLOBALS['goto'],
            'err_url' => $errorUrl,
            'sql_query' => $_POST['sql_query'] ?? '',
        ];

        if ($formParams['sql_query'] === '' && isset($_GET['sql_query'], $_GET['sql_signature'])) {
            if (Core::checkSqlQuerySignature($_GET['sql_query'], $_GET['sql_signature'])) {
                $formParams['sql_query'] = $_GET['sql_query'];
            }
        }

        if (isset($whereClauses)) {
            foreach ($whereClauseArray as $keyId => $whereClause) {
                $formParams['where_clause[' . $keyId . ']'] = trim($whereClause);
            }
        }

        if (isset($_POST['clause_is_unique'])) {
            $formParams['clause_is_unique'] = $_POST['clause_is_unique'];
        } elseif (isset($_GET['clause_is_unique'])) {
            $formParams['clause_is_unique'] = $_GET['clause_is_unique'];
        }

        return $formParams;
    }

    /**
     * Analysing where clauses array
     *
     * @param string[] $whereClauseArray array of where clauses
     * @param string   $table            name of the table
     * @param string   $db               name of the database
     *
     * @return array<int, string[]|ResultInterface[]|array<string, string|null>[]|bool>
     * @phpstan-return array{string[], ResultInterface[], array<string|null>[], bool}
     */
    private function analyzeWhereClauses(
        array $whereClauseArray,
        string $table,
        string $db,
    ): array {
        $rows = [];
        $result = [];
        $whereClauses = [];
        $foundUniqueKey = false;
        foreach ($whereClauseArray as $keyId => $whereClause) {
            $localQuery = 'SELECT * FROM '
                . Util::backquote($db) . '.'
                . Util::backquote($table)
                . ' WHERE ' . $whereClause . ';';
            $result[$keyId] = $this->dbi->query($localQuery);
            $rows[$keyId] = $result[$keyId]->fetchAssoc();

            $whereClauses[$keyId] = str_replace('\\', '\\\\', $whereClause);
            $hasUniqueCondition = $this->showEmptyResultMessageOrSetUniqueCondition(
                $rows,
                $keyId,
                $whereClauseArray,
                $localQuery,
                $result,
            );
            if (! $hasUniqueCondition) {
                continue;
            }

            $foundUniqueKey = true;
        }

        return [$whereClauses, $result, $rows, $foundUniqueKey];
    }

    /**
     * Show message for empty result or set the unique_condition
     *
     * @param mixed[]           $rows             MySQL returned rows
     * @param string|int        $keyId            ID in current key
     * @param mixed[]           $whereClauseArray array of where clauses
     * @param string            $localQuery       query performed
     * @param ResultInterface[] $result           MySQL result handle
     */
    private function showEmptyResultMessageOrSetUniqueCondition(
        array $rows,
        string|int $keyId,
        array $whereClauseArray,
        string $localQuery,
        array $result,
    ): bool {
        // No row returned
        if (! $rows[$keyId]) {
            unset($rows[$keyId], $whereClauseArray[$keyId]);
            ResponseRenderer::getInstance()->addHTML(
                Generator::getMessage(
                    __('MySQL returned an empty result set (i.e. zero rows).'),
                    $localQuery,
                ),
            );
            /**
             * @todo not sure what should be done at this point, but we must not
             * exit if we want the message to be displayed
             */

            return false;
        }

        $meta = $this->dbi->getFieldsMeta($result[$keyId]);

        [$uniqueCondition] = Util::getUniqueCondition(
            count($meta),
            $meta,
            $rows[$keyId],
            true,
        );

        return (bool) $uniqueCondition;
    }

    /**
     * No primary key given, just load first row
     */
    private function loadFirstRow(string $table, string $db): ResultInterface
    {
        return $this->dbi->query(
            'SELECT * FROM ' . Util::backquote($db)
            . '.' . Util::backquote($table) . ' LIMIT 1;',
        );
    }

    /** @return false[] */
    private function getInsertRows(): array
    {
        // Can be a string on some old configuration storage settings
        return array_fill(0, (int) $GLOBALS['cfg']['InsertRows'], false);
    }

    /**
     * Show type information or function selectors in Insert/Edit
     *
     * @param string  $which     function|type
     * @param mixed[] $urlParams containing url parameters
     * @param bool    $isShow    whether to show the element in $which
     *
     * @return string an HTML snippet
     */
    public function showTypeOrFunction(string $which, array $urlParams, bool $isShow): string
    {
        $params = [];

        switch ($which) {
            case 'function':
                $params['ShowFunctionFields'] = ($isShow ? 0 : 1);
                $params['ShowFieldTypesInDataEditView'] = $GLOBALS['cfg']['ShowFieldTypesInDataEditView'];
                break;
            case 'type':
                $params['ShowFieldTypesInDataEditView'] = ($isShow ? 0 : 1);
                $params['ShowFunctionFields'] = $GLOBALS['cfg']['ShowFunctionFields'];
                break;
        }

        $params['goto'] = Url::getFromRoute('/sql');
        $thisUrlParams = array_merge($urlParams, $params);

        if (! $isShow) {
            return ' : <a href="' . Url::getFromRoute('/table/change') . '" data-post="'
                . Url::getCommon($thisUrlParams, '', false) . '">'
                . $this->showTypeOrFunctionLabel($which)
                . '</a>';
        }

        return '<th><a href="' . Url::getFromRoute('/table/change') . '" data-post="'
            . Url::getCommon($thisUrlParams, '', false)
            . '" title="' . __('Hide') . '">'
            . $this->showTypeOrFunctionLabel($which)
            . '</a></th>';
    }

    /**
     * Show type information or function selectors labels in Insert/Edit
     *
     * @param string $which function|type
     *
     * @return string an HTML snippet
     */
    private function showTypeOrFunctionLabel(string $which): string
    {
        return match ($which) {
            'function' => __('Function'),
            'type' => __('Type'),
            default => '',
        };
    }

    /**
     * Analyze the table column array
     *
     * @param mixed[] $column      description of column in given table
     * @param mixed[] $commentsMap comments for every column that has a comment
     *
     * @return mixed[]                   description of column in given table
     */
    private function analyzeTableColumnsArray(
        array $column,
        array $commentsMap,
    ): array {
        $column['Field_md5'] = md5($column['Field']);
        // True_Type contains only the type (stops at first bracket)
        $column['True_Type'] = preg_replace('@\(.*@s', '', $column['Type']);
        $column['len'] = preg_match('@float|double@', $column['Type']) ? 100 : -1;
        $column['Field_title'] = $this->getColumnTitle($column, $commentsMap);
        $column['is_binary'] = $this->isColumn(
            $column,
            ['binary', 'varbinary'],
        );
        $column['is_blob'] = $this->isColumn(
            $column,
            ['blob', 'tinyblob', 'mediumblob', 'longblob'],
        );
        $column['is_char'] = $this->isColumn(
            $column,
            ['char', 'varchar'],
        );

        $column['pma_type'] = match ($column['True_Type']) {
            'set', 'enum' => $column['True_Type'],
            default => $column['Type']
        };

        $column['wrap'] = match ($column['True_Type']) {
            'set', 'enum' => '',
            default => ' text-nowrap'
        };

        // can only occur once per table
        $column['first_timestamp'] = $column['True_Type'] === 'timestamp';

        return $column;
    }

    /**
     * Retrieve the column title
     *
     * @param mixed[] $column      description of column in given table
     * @param mixed[] $commentsMap comments for every column that has a comment
     *
     * @return string              column title
     */
    private function getColumnTitle(array $column, array $commentsMap): string
    {
        if (isset($commentsMap[$column['Field']])) {
            return '<span style="border-bottom: 1px dashed black;" title="'
                . htmlspecialchars($commentsMap[$column['Field']]) . '">'
                . htmlspecialchars($column['Field']) . '</span>';
        }

        return htmlspecialchars($column['Field']);
    }

    /**
     * check whether the column is of a certain type
     * the goal is to ensure that types such as "enum('one','two','binary',..)"
     * or "enum('one','two','varbinary',..)" are not categorized as binary
     *
     * @param mixed[]  $column description of column in given table
     * @param string[] $types  the types to verify
     */
    public function isColumn(array $column, array $types): bool
    {
        foreach ($types as $oneType) {
            if (mb_stripos($column['Type'], $oneType) === 0) {
                return true;
            }
        }

        return false;
    }

    /**
     * Retrieve the nullify code for the null column
     *
     * @param mixed[] $column      description of column in given table
     * @param mixed[] $foreigners  keys into foreign fields
     * @param mixed[] $foreignData data about the foreign keys
     */
    private function getNullifyCodeForNullColumn(
        array $column,
        array $foreigners,
        array $foreignData,
    ): string {
        $foreigner = $this->relation->searchColumnInForeigners($foreigners, $column['Field']);
        if (mb_strstr($column['True_Type'], 'enum')) {
            if (mb_strlen((string) $column['Type']) > 20) {
                $nullifyCode = '1';
            } else {
                $nullifyCode = '2';
            }
        } elseif (mb_strstr($column['True_Type'], 'set')) {
            $nullifyCode = '3';
        } elseif ($foreigner && $foreignData['foreign_link'] == false) {
            // foreign key in a drop-down
            $nullifyCode = '4';
        } elseif ($foreigner && $foreignData['foreign_link'] == true) {
            // foreign key with a browsing icon
            $nullifyCode = '6';
        } else {
            $nullifyCode = '5';
        }

        return $nullifyCode;
    }

    /**
     * Get HTML textarea for insert form
     *
     * @param mixed[] $column              column information
     * @param string  $backupField         hidden input field
     * @param string  $columnNameAppendix  the name attribute
     * @param string  $onChangeClause      onchange clause for fields
     * @param string  $textDir             text direction
     * @param string  $specialCharsEncoded replaced char if the string starts
     *                                       with a \r\n pair (0x0d0a) add an extra \n
     * @param string  $dataType            the html5 data-* attribute type
     *
     * @return string                       an html snippet
     */
    private function getTextarea(
        array $column,
        string $backupField,
        string $columnNameAppendix,
        string $onChangeClause,
        string $textDir,
        string $specialCharsEncoded,
        string $dataType,
    ): string {
        $theClass = '';
        $textAreaRows = $GLOBALS['cfg']['TextareaRows'];
        $textareaCols = $GLOBALS['cfg']['TextareaCols'];

        if ($column['is_char']) {
            /**
             * @todo clarify the meaning of the "textfield" class and explain
             *       why character columns have the "char" class instead
             */
            $theClass = 'char charField';
            $textAreaRows = $GLOBALS['cfg']['CharTextareaRows'];
            $textareaCols = $GLOBALS['cfg']['CharTextareaCols'];
            $extractedColumnspec = Util::extractColumnSpec($column['Type']);
            $maxlength = $extractedColumnspec['spec_in_brackets'];
        } elseif ($GLOBALS['cfg']['LongtextDoubleTextarea'] && mb_strstr($column['pma_type'], 'longtext')) {
            $textAreaRows = $GLOBALS['cfg']['TextareaRows'] * 2;
            $textareaCols = $GLOBALS['cfg']['TextareaCols'] * 2;
        }

        return $backupField . "\n"
            . '<textarea name="fields' . $columnNameAppendix . '"'
            . ' class="' . $theClass . '"'
            . (isset($maxlength) ? ' data-maxlength="' . $maxlength . '"' : '')
            . ' rows="' . $textAreaRows . '"'
            . ' cols="' . $textareaCols . '"'
            . ' dir="' . $textDir . '"'
            . ' id="field_' . $this->fieldIndex . '_3"'
            . ($onChangeClause ? ' onchange="' . htmlspecialchars($onChangeClause, ENT_COMPAT) . '"' : '')
            . ' tabindex="' . $this->fieldIndex . '"'
            . ' data-type="' . $dataType . '">'
            . $specialCharsEncoded
            . '</textarea>';
    }

    /**
     * Get HTML input type
     *
     * @param mixed[] $column             description of column in given table
     * @param string  $columnNameAppendix the name attribute
     * @param string  $specialChars       special characters
     * @param int     $fieldsize          html field size
     * @param string  $onChangeClause     onchange clause for fields
     * @param string  $dataType           the html5 data-* attribute type
     *
     * @return string                       an html snippet
     */
    private function getHtmlInput(
        array $column,
        string $columnNameAppendix,
        string $specialChars,
        int $fieldsize,
        string $onChangeClause,
        string $dataType,
    ): string {
        $theClass = 'textfield';
        // verify True_Type which does not contain the parentheses and length
        if ($column['True_Type'] === 'date') {
            $theClass .= ' datefield';
        } elseif ($column['True_Type'] === 'time') {
            $theClass .= ' timefield';
        } elseif ($column['True_Type'] === 'datetime' || $column['True_Type'] === 'timestamp') {
            $theClass .= ' datetimefield';
        }

        $inputMinMax = '';
        $isInteger = in_array($column['True_Type'], $this->dbi->types->getIntegerTypes());
        if ($isInteger) {
            $extractedColumnspec = Util::extractColumnSpec($column['Type']);
            $isUnsigned = $extractedColumnspec['unsigned'];
            $minMaxValues = $this->dbi->types->getIntegerRange($column['True_Type'], ! $isUnsigned);
            $inputMinMax = 'min="' . $minMaxValues[0] . '" '
                . 'max="' . $minMaxValues[1] . '"';
            $dataType = 'INT';
        }

        // do not use the 'date' or 'time' types here; they have no effect on some
        // browsers and create side effects (see bug #4218)
        return '<input type="text"'
            . ' name="fields' . $columnNameAppendix . '"'
            . ' value="' . $specialChars . '" size="' . $fieldsize . '"'
            . (isset($column['is_char']) && $column['is_char']
                ? ' data-maxlength="' . $fieldsize . '"'
                : '')
            . ($inputMinMax ? ' ' . $inputMinMax : '')
            . ' data-type="' . $dataType . '"'
            . ' class="' . $theClass . '" onchange="' . htmlspecialchars($onChangeClause, ENT_COMPAT) . '"'
            . ' tabindex="' . $this->fieldIndex . '"'
            . ($isInteger ? ' inputmode="numeric"' : '')
            . ' id="field_' . $this->fieldIndex . '_3">';
    }

    /**
     * Get HTML select option for upload
     *
     * @param string $vkey         [multi_edit]['row_id']
     * @param string $fieldHashMd5 array index as an MD5 to avoid having special characters
     *
     * @return string an HTML snippet
     */
    private function getSelectOptionForUpload(string $vkey, string $fieldHashMd5): string
    {
        $files = $this->fileListing->getFileSelectOptions(
            Util::userDir((string) ($GLOBALS['cfg']['UploadDir'] ?? '')),
        );

        if ($files === false) {
            return '<span style="color:red">' . __('Error') . '</span><br>' . "\n"
                . __('The directory you set for upload work cannot be reached.') . "\n";
        }

        if ($files === '') {
            return '';
        }

        return "<br>\n"
            . '<i>' . __('Or') . '</i> '
            . __('web server upload directory:') . '<br>' . "\n"
            . '<select size="1" name="fields_uploadlocal'
            . $vkey . '[' . $fieldHashMd5 . ']">' . "\n"
            . '<option value="" selected="selected"></option>' . "\n"
            . $files
            . '</select>' . "\n";
    }

    /**
     * Retrieve the maximum upload file size
     */
    private function getMaxUploadSize(string $pmaType): string
    {
        // find maximum upload size, based on field type
        /**
         * @todo with functions this is not so easy, as you can basically
         * process any data with function like MD5
         */
        $maxFieldSize = match ($pmaType) {
            'tinyblob' => 256,
            'blob' => 65536,
            'mediumblob' => 16777216,
            'longblob' => 4294967296,// yeah, really
        };

        $thisFieldMaxSize = (int) $GLOBALS['config']->get('max_upload_size'); // from PHP max

        return Util::getFormattedMaximumUploadSize(min($thisFieldMaxSize, $maxFieldSize)) . "\n";
    }

    /**
     * Get HTML for the Value column of other datatypes
     * (here, "column" is used in the sense of HTML column in HTML table)
     *
     * @param mixed[] $column              description of column in given table
     * @param string  $defaultCharEditing  default char editing mode which is stored
     *                                        in the config.inc.php script
     * @param string  $backupField         hidden input field
     * @param string  $columnNameAppendix  the name attribute
     * @param string  $onChangeClause      onchange clause for fields
     * @param string  $specialChars        special characters
     * @param string  $textDir             text direction
     * @param string  $specialCharsEncoded replaced char if the string starts
     *                                       with a \r\n pair (0x0d0a) add an extra \n
     * @param string  $data                data to edit
     * @param mixed[] $extractedColumnspec associative array containing type,
     *                                     spec_in_brackets and possibly
     *                                     enum_set_values (another array)
     *
     * @return string an html snippet
     */
    private function getValueColumnForOtherDatatypes(
        array $column,
        string $defaultCharEditing,
        string $backupField,
        string $columnNameAppendix,
        string $onChangeClause,
        string $specialChars,
        string $textDir,
        string $specialCharsEncoded,
        string $data,
        array $extractedColumnspec,
    ): string {
        // HTML5 data-* attribute data-type
        $dataType = $this->dbi->types->getTypeClass($column['True_Type']);
        $fieldsize = $this->getColumnSize($column, $extractedColumnspec['spec_in_brackets']);

        $isTextareaRequired = $column['is_char']
            && ($GLOBALS['cfg']['CharEditing'] === 'textarea' || str_contains($data, "\n"));
        if ($isTextareaRequired) {
            $GLOBALS['cfg']['CharEditing'] = $defaultCharEditing;
            $htmlField = $this->getTextarea(
                $column,
                $backupField,
                $columnNameAppendix,
                $onChangeClause,
                $textDir,
                $specialCharsEncoded,
                $dataType,
            );
        } else {
            $htmlField = $this->getHtmlInput(
                $column,
                $columnNameAppendix,
                $specialChars,
                $fieldsize,
                $onChangeClause,
                $dataType,
            );
        }

        return $this->template->render('table/insert/value_column_for_other_datatype', [
            'html_field' => $htmlField,
            'backup_field' => $backupField,
            'is_textarea' => $isTextareaRequired,
            'columnNameAppendix' => $columnNameAppendix,
            'column' => $column,
        ]);
    }

    /**
     * Get the field size
     *
     * @param mixed[] $column         description of column in given table
     * @param string  $specInBrackets text in brackets inside column definition
     *
     * @return int field size
     */
    private function getColumnSize(array $column, string $specInBrackets): int
    {
        if ($column['is_char']) {
            $fieldsize = (int) $specInBrackets;
            if ($fieldsize > $GLOBALS['cfg']['MaxSizeForInputField']) {
                /**
                 * This case happens for CHAR or VARCHAR columns which have
                 * a size larger than the maximum size for input field.
                 */
                $GLOBALS['cfg']['CharEditing'] = 'textarea';
            }
        } else {
            /**
             * This case happens for example for INT or DATE columns;
             * in these situations, the value returned in $column['len']
             * seems appropriate.
             */
            $fieldsize = $column['len'];
        }

        return min(
            max($fieldsize, $GLOBALS['cfg']['MinSizeForInputField']),
            $GLOBALS['cfg']['MaxSizeForInputField'],
        );
    }

    /**
     * get html for continue insertion form
     *
     * @param string  $table            name of the table
     * @param string  $db               name of the database
     * @param mixed[] $whereClauseArray
     *
     * @return string                   an html snippet
     */
    public function getContinueInsertionForm(
        string $table,
        string $db,
        array $whereClauseArray,
        string $errorUrl,
    ): string {
        return $this->template->render('table/insert/continue_insertion_form', [
            'db' => $db,
            'table' => $table,
            'where_clause_array' => $whereClauseArray,
            'err_url' => $errorUrl,
            'goto' => $GLOBALS['goto'],
            'sql_query' => $_POST['sql_query'] ?? null,
            'has_where_clause' => isset($_POST['where_clause']),
            'insert_rows_default' => $GLOBALS['cfg']['InsertRows'],
        ]);
    }

    /**
     * @param string[]|string|null $whereClause
     *
     * @psalm-pure
     */
    public static function isWhereClauseNumeric(array|string|null $whereClause): bool
    {
        if ($whereClause === null) {
            return false;
        }

        if (! is_array($whereClause)) {
            $whereClause = [$whereClause];
        }

        // If we have just numeric primary key, we can also edit next
        // we are looking for `table_name`.`field_name` = numeric_value
        foreach ($whereClause as $clause) {
            // preg_match() returns 1 if there is a match
            $isNumeric = preg_match('@^[\s]*`[^`]*`[\.]`[^`]*` = [0-9]+@', $clause) === 1;
            if ($isNumeric) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get table head and table foot for insert row table
     *
     * @param mixed[] $urlParams url parameters
     *
     * @return string           an html snippet
     */
    private function getHeadAndFootOfInsertRowTable(array $urlParams): string
    {
        $type = '';
        $function = '';

        if ($GLOBALS['cfg']['ShowFieldTypesInDataEditView']) {
            $type = $this->showTypeOrFunction('type', $urlParams, true);
        }

        if ($GLOBALS['cfg']['ShowFunctionFields']) {
            $function = $this->showTypeOrFunction('function', $urlParams, true);
        }

        $template = new Template();

        return $template->render('table/insert/get_head_and_foot_of_insert_row_table', [
            'type' => $type,
            'function' => $function,
        ]);
    }

    /**
     * Prepares the field value and retrieve special chars, backup field and data array
     *
     * @param mixed[] $currentRow          a row of the table
     * @param mixed[] $column              description of column in given table
     * @param mixed[] $extractedColumnspec associative array containing type,
     *                                     spec_in_brackets and possibly
     *                                     enum_set_values (another array)
     * @param mixed[] $gisDataTypes        list of GIS data types
     * @param string  $columnNameAppendix  string to append to column name in input
     * @param bool    $asIs                use the data as is, used in repopulating
     *
     * @return mixed[] $real_null_value, $data, $special_chars, $backup_field,
     *               $special_chars_encoded
     * @psalm-return array{bool, string, string, string, string}
     */
    private function getSpecialCharsAndBackupFieldForExistingRow(
        array $currentRow,
        array $column,
        array $extractedColumnspec,
        array $gisDataTypes,
        string $columnNameAppendix,
        bool $asIs,
    ): array {
        $specialCharsEncoded = '';
        $data = null;
        $realNullValue = false;
        // (we are editing)
        if (! isset($currentRow[$column['Field']])) {
            $realNullValue = true;
            $currentRow[$column['Field']] = '';
            $specialChars = '';
            $data = '';
        } elseif ($column['True_Type'] === 'bit') {
            $specialChars = $asIs
                ? $currentRow[$column['Field']]
                : Util::printableBitValue(
                    (int) $currentRow[$column['Field']],
                    (int) $extractedColumnspec['spec_in_brackets'],
                );
        } elseif (
            (str_starts_with($column['True_Type'], 'timestamp')
                || $column['True_Type'] === 'datetime'
                || $column['True_Type'] === 'time')
            && (str_contains($currentRow[$column['Field']], '.'))
        ) {
            $currentRow[$column['Field']] = $asIs
                ? $currentRow[$column['Field']]
                : Util::addMicroseconds($currentRow[$column['Field']]);
            $specialChars = htmlspecialchars($currentRow[$column['Field']], ENT_COMPAT);
        } elseif (in_array($column['True_Type'], $gisDataTypes)) {
            // Convert gis data to Well Know Text format
            $currentRow[$column['Field']] = $asIs
                ? $currentRow[$column['Field']]
                : Gis::convertToWellKnownText($currentRow[$column['Field']], true);
            $specialChars = htmlspecialchars($currentRow[$column['Field']], ENT_COMPAT);
        } else {
            // special binary "characters"
            if ($column['is_binary'] || ($column['is_blob'] && $GLOBALS['cfg']['ProtectBinary'] !== 'all')) {
                $currentRow[$column['Field']] = $asIs
                    ? $currentRow[$column['Field']]
                    : bin2hex($currentRow[$column['Field']]);
            }

            $specialChars = htmlspecialchars($currentRow[$column['Field']], ENT_COMPAT);

            //We need to duplicate the first \n or otherwise we will lose
            //the first newline entered in a VARCHAR or TEXT column
            $specialCharsEncoded = Util::duplicateFirstNewline($specialChars);

            $data = $currentRow[$column['Field']];
        }

        /** @var string $defaultAction */
        $defaultAction = $_POST['default_action'] ?? $_GET['default_action'] ?? '';
        if (
            $defaultAction === 'insert'
            && $column['Key'] === 'PRI'
            && str_contains($column['Extra'], 'auto_increment')
        ) {
            // When copying row, it is useful to empty auto-increment column to prevent duplicate key error.
            $data = $specialCharsEncoded = $specialChars = null;
        }

        // If a timestamp field value is not included in an update
        // statement MySQL auto-update it to the current timestamp;
        // however, things have changed since MySQL 4.1, so
        // it's better to set a fields_prev in this situation
        $backupField = '<input type="hidden" name="fields_prev'
            . $columnNameAppendix . '" value="'
            . htmlspecialchars($currentRow[$column['Field']], ENT_COMPAT) . '">';

        return [$realNullValue, (string) $specialCharsEncoded, (string) $specialChars, (string) $data, $backupField];
    }

    /**
     * display default values
     */
    private function getSpecialCharsForInsertingMode(
        string|null $defaultValue,
        string $trueType,
    ): string {
        if ($defaultValue === null) {
            $defaultValue = '';
        }

        if ($trueType === 'bit') {
            $specialChars = Util::convertBitDefaultValue($defaultValue);
        } elseif (str_starts_with($trueType, 'timestamp') || $trueType === 'datetime' || $trueType === 'time') {
            $specialChars = Util::addMicroseconds($defaultValue);
        } elseif ($trueType === 'binary' || $trueType === 'varbinary') {
            $specialChars = bin2hex($defaultValue);
        } elseif (str_ends_with($trueType, 'text')) {
            $textDefault = substr($defaultValue, 1, -1);
            $specialChars = stripcslashes($textDefault !== '' ? $textDefault : $defaultValue);
        } else {
            $specialChars = htmlspecialchars($defaultValue);
        }

        return $specialChars;
    }

    /**
     * set $_SESSION for edit_next
     *
     * @param string $oneWhereClause one where clause from where clauses array
     */
    public function setSessionForEditNext(string $oneWhereClause): void
    {
        $localQuery = 'SELECT * FROM ' . Util::backquote($GLOBALS['db'])
            . '.' . Util::backquote($GLOBALS['table']) . ' WHERE '
            . str_replace('` =', '` >', $oneWhereClause) . ' LIMIT 1;';

        $res = $this->dbi->query($localQuery);
        $row = $res->fetchRow();
        $meta = $this->dbi->getFieldsMeta($res);
        // must find a unique condition based on unique key,
        // not a combination of all fields
        [$uniqueCondition] = Util::getUniqueCondition(
            count($meta),
            $meta,
            $row,
            true,
        );
        if (! $uniqueCondition) {
            return;
        }

        $_SESSION['edit_next'] = $uniqueCondition;
    }

    /**
     * set $goto_include variable for different cases and retrieve like,
     * if $GLOBALS['goto'] empty, if $goto_include previously not defined
     * and new_insert, same_insert, edit_next
     *
     * @param string|false $gotoInclude store some script for include, otherwise it is
     *                                   boolean false
     */
    public function getGotoInclude(string|false $gotoInclude): string
    {
        $validOptions = ['new_insert', 'same_insert', 'edit_next'];
        if (isset($_POST['after_insert']) && in_array($_POST['after_insert'], $validOptions)) {
            return '/table/change';
        }

        if (! empty($GLOBALS['goto'])) {
            if (! preg_match('@^[a-z_]+\.php$@', $GLOBALS['goto'])) {
                // this should NOT happen
                //$GLOBALS['goto'] = false;
                if ($GLOBALS['goto'] === 'index.php?route=/sql') {
                    $gotoInclude = '/sql';
                } else {
                    $gotoInclude = false;
                }
            } else {
                $gotoInclude = $GLOBALS['goto'];
            }

            if ($GLOBALS['goto'] === 'index.php?route=/database/sql' && strlen($GLOBALS['table']) > 0) {
                $GLOBALS['table'] = '';
            }
        }

        if (! $gotoInclude) {
            if ($GLOBALS['table'] === '') {
                $gotoInclude = '/database/sql';
            } else {
                $gotoInclude = '/table/sql';
            }
        }

        return $gotoInclude;
    }

    /**
     * Defines the url to return in case of failure of the query
     *
     * @param mixed[] $urlParams url parameters
     *
     * @return string           error url for query failure
     */
    public function getErrorUrl(array $urlParams): string
    {
        if (isset($_POST['err_url'])) {
            return $_POST['err_url'];
        }

        return Url::getFromRoute('/table/change', $urlParams);
    }

    /**
     * Builds the SQL insert query
     *
     * @param bool     $isInsertIgnore $_POST['submit_type'] === 'insertignore'
     * @param string[] $queryFields    column names array
     * @param string[] $valueSets      array of query values
     *
     * @todo move this to Query generator class
     */
    public function buildInsertSqlQuery(
        string $table,
        bool $isInsertIgnore,
        array $queryFields,
        array $valueSets,
    ): string {
        return ($isInsertIgnore ? 'INSERT IGNORE ' : 'INSERT ') . 'INTO '
            . Util::backquote($table)
            . ' (' . implode(', ', $queryFields) . ') VALUES ('
            . implode('), (', $valueSets) . ')';
    }

    /**
     * Executes the sql query and get the result, then move back to the calling page
     *
     * @param mixed[] $query built query from buildSqlQuery()
     *
     * @return mixed[] $total_affected_rows, $last_messages, $warning_messages, $error_messages
     */
    public function executeSqlQuery(array $query): array
    {
        $GLOBALS['sql_query'] = implode('; ', $query) . ';';
        // to ensure that the query is displayed in case of
        // "insert as new row" and then "insert another new row"
        $GLOBALS['display_query'] = $GLOBALS['sql_query'];

        $totalAffectedRows = 0;
        $lastMessages = [];
        $warningMessages = [];
        $errorMessages = [];

        foreach ($query as $singleQuery) {
            if (isset($_POST['submit_type']) && $_POST['submit_type'] === 'showinsert') {
                $lastMessages[] = Message::notice(__('Showing SQL query'));
                continue;
            }

            if ($GLOBALS['cfg']['IgnoreMultiSubmitErrors']) {
                $result = $this->dbi->tryQuery($singleQuery);
            } else {
                $result = $this->dbi->query($singleQuery);
            }

            if (! $result) {
                $errorMessages[] = $this->dbi->getError();
            } else {
                $totalAffectedRows += $this->dbi->affectedRows();

                $insertId = $this->dbi->insertId();
                if ($insertId) {
                    // insert_id is id of FIRST record inserted in one insert, so if we
                    // inserted multiple rows, we had to increment this

                    if ($totalAffectedRows > 0) {
                        $insertId += $totalAffectedRows - 1;
                    }

                    $lastMessage = Message::notice(__('Inserted row id: %1$d'));
                    $lastMessage->addParam($insertId);
                    $lastMessages[] = $lastMessage;
                }
            }

            $warningMessages = $this->getWarningMessages();
        }

        return [$totalAffectedRows, $lastMessages, $warningMessages, $errorMessages];
    }

    /**
     * get the warning messages array
     *
     * @return string[]
     */
    private function getWarningMessages(): array
    {
        $warningMessages = [];
        foreach ($this->dbi->getWarnings() as $warning) {
            $warningMessages[] = htmlspecialchars((string) $warning);
        }

        return $warningMessages;
    }

    /**
     * Column to display from the foreign table?
     *
     * @param string  $whereComparison string that contain relation field value
     * @param mixed[] $map             all Relations to foreign tables for a given
     *                                            table or optionally a given column in a table
     * @param string  $relationField   relation field
     *
     * @return string display value from the foreign table
     */
    public function getDisplayValueForForeignTableColumn(
        string $whereComparison,
        array $map,
        string $relationField,
    ): string {
        $foreigner = $this->relation->searchColumnInForeigners($map, $relationField);

        if (! is_array($foreigner)) {
            return '';
        }

        $displayField = $this->relation->getDisplayField($foreigner['foreign_db'], $foreigner['foreign_table']);
        // Field to display from the foreign table?
        if ($displayField !== '') {
            $dispsql = 'SELECT ' . Util::backquote($displayField)
                . ' FROM ' . Util::backquote($foreigner['foreign_db'])
                . '.' . Util::backquote($foreigner['foreign_table'])
                . ' WHERE ' . Util::backquote($foreigner['foreign_field'])
                . $whereComparison;
            $dispresult = $this->dbi->tryQuery($dispsql);
            if ($dispresult && $dispresult->numRows() > 0) {
                return (string) $dispresult->fetchValue();
            }
        }

        return '';
    }

    /**
     * Display option in the cell according to user choices
     *
     * @param mixed[] $map                all Relations to foreign tables for a given
     *                                                  table or optionally a given column in a table
     * @param string  $relationField      relation field
     * @param string  $whereComparison    string that contain relation field value
     * @param string  $dispval            display value from the foreign table
     * @param string  $relationFieldValue relation field value
     *
     * @return string HTML <a> tag
     */
    public function getLinkForRelationalDisplayField(
        array $map,
        string $relationField,
        string $whereComparison,
        string $dispval,
        string $relationFieldValue,
    ): string {
        $foreigner = $this->relation->searchColumnInForeigners($map, $relationField);

        if (! is_array($foreigner)) {
            return '';
        }

        if ($_SESSION['tmpval']['relational_display'] === 'K') {
            // user chose "relational key" in the display options, so
            // the title contains the display field
            $title = $dispval
                ? ' title="' . htmlspecialchars($dispval) . '"'
                : '';
        } else {
            $title = ' title="' . htmlspecialchars($relationFieldValue) . '"';
        }

        $sqlQuery = 'SELECT * FROM '
            . Util::backquote($foreigner['foreign_db'])
            . '.' . Util::backquote($foreigner['foreign_table'])
            . ' WHERE ' . Util::backquote($foreigner['foreign_field'])
            . $whereComparison;
        $urlParams = [
            'db' => $foreigner['foreign_db'],
            'table' => $foreigner['foreign_table'],
            'pos' => '0',
            'sql_signature' => Core::signSqlQuery($sqlQuery),
            'sql_query' => $sqlQuery,
        ];
        $output = '<a href="' . Url::getFromRoute('/sql', $urlParams) . '"' . $title . '>';

        if ($_SESSION['tmpval']['relational_display'] === 'D') {
            // user chose "relational display field" in the
            // display options, so show display field in the cell
            $output .= htmlspecialchars($dispval);
        } else {
            // otherwise display data in the cell
            $output .= htmlspecialchars($relationFieldValue);
        }

        $output .= '</a>';

        return $output;
    }

    /**
     * Transform edited values
     *
     * @param string  $db             db name
     * @param string  $table          table name
     * @param mixed[] $transformation mimetypes for all columns of a table
     *                               [field_name][field_key]
     * @param mixed[] $editedValues   transform columns list and new values
     * @param string  $file           file containing the transformation plugin
     * @param string  $columnName     column name
     * @param mixed[] $extraData      extra data array
     * @param string  $type           the type of transformation
     *
     * @return mixed[]
     */
    public function transformEditedValues(
        string $db,
        string $table,
        array $transformation,
        array &$editedValues,
        string $file,
        string $columnName,
        array $extraData,
        string $type,
    ): array {
        $includeFile = 'libraries/classes/Plugins/Transformations/' . $file;
        if (is_file(ROOT_PATH . $includeFile)) {
            // $cfg['SaveCellsAtOnce'] = true; JS code sends an array
            $whereClause = is_array($_POST['where_clause']) ? $_POST['where_clause'][0] : $_POST['where_clause'];
            $urlParams = [
                'db' => $db,
                'table' => $table,
                'where_clause_sign' => Core::signSqlQuery($whereClause),
                'where_clause' => $whereClause,
                'transform_key' => $columnName,
            ];
            $transformOptions = $this->transformations->getOptions($transformation[$type . '_options'] ?? '');
            $transformOptions['wrapper_link'] = Url::getCommon($urlParams);
            $transformOptions['wrapper_params'] = $urlParams;
            $className = $this->transformations->getClassName($includeFile);
            if (class_exists($className)) {
                /** @var TransformationsPlugin $transformationPlugin */
                $transformationPlugin = new $className();

                foreach ($editedValues as $cellIndex => $currCellEditedValues) {
                    if (! isset($currCellEditedValues[$columnName])) {
                        continue;
                    }

                    $extraData['transformations'][$cellIndex] = $transformationPlugin->applyTransformation(
                        $currCellEditedValues[$columnName],
                        $transformOptions,
                    );
                    $editedValues[$cellIndex][$columnName] = $extraData['transformations'][$cellIndex];
                }
            }
        }

        return $extraData;
    }

    /**
     * Get value part if a function was specified
     */
    private function formatAsSqlFunction(
        EditField $editField,
    ): string {
        if ($editField->function === 'PHP_PASSWORD_HASH') {
            $hash = password_hash($editField->value, PASSWORD_DEFAULT);

            return $this->dbi->quoteString($hash);
        }

        if ($editField->function === 'UUID') {
            /* This way user will know what UUID new row has */
            $uuid = (string) $this->dbi->fetchValue('SELECT UUID()');

            return $this->dbi->quoteString($uuid);
        }

        if (
            in_array($editField->function, $this->getGisFromTextFunctions())
            || in_array($editField->function, $this->getGisFromWKBFunctions())
        ) {
            preg_match('/^(\'?)(.*?)\1(?:,(\d+))?$/', $editField->value, $matches);
            $escapedParams = $this->dbi->quoteString($matches[2]) . (isset($matches[3]) ? ',' . $matches[3] : '');

            return $editField->function . '(' . $escapedParams . ')';
        }

        if (
            ! in_array($editField->function, self::FUNC_NO_PARAM)
            || ($editField->value !== '' && in_array($editField->function, self::FUNC_OPTIONAL_PARAM))
        ) {
            if (
                ($editField->salt !== null
                    && ($editField->function === 'AES_ENCRYPT'
                        || $editField->function === 'AES_DECRYPT'
                        || $editField->function === 'SHA2'))
                || ($editField->salt
                    && ($editField->function === 'DES_ENCRYPT'
                        || $editField->function === 'DES_DECRYPT'
                        || $editField->function === 'ENCRYPT'))
            ) {
                return $editField->function . '(' . $this->dbi->quoteString($editField->value) . ','
                    . $this->dbi->quoteString($editField->salt) . ')';
            }

            return $editField->function . '(' . $this->dbi->quoteString($editField->value) . ')';
        }

        return $editField->function . '()';
    }

    /**
     * Get the field value formatted for use in a SQL statement.
     * Used in both INSERT and UPDATE statements.
     */
    private function getValueFormattedAsSql(
        EditField $editField,
        string $protectedValue = '',
    ): string {
        if ($editField->isUploaded) {
            return $editField->value;
        }

        if ($editField->function !== '') {
            return $this->formatAsSqlFunction($editField);
        }

        return $this->formatAsSqlValueBasedOnType($editField, $protectedValue);
    }

    /**
     * Get query values array and query fields array for insert and update in multi edit
     *
     * @param string|int $whereClause Either a positional index or string representing selected row
     */
    public function getQueryValueForInsert(
        EditField $editField,
        bool $usingKey,
        string|int $whereClause,
    ): string {
        $protectedValue = '';
        if ($editField->type === 'protected' && $usingKey && $whereClause !== '') {
            // Fetch the current values of a row to use in case we have a protected field
            $protectedValue = $this->dbi->fetchValue(
                'SELECT ' . Util::backquote($editField->columnName)
                . ' FROM ' . Util::backquote($GLOBALS['table'])
                . ' WHERE ' . $whereClause,
            );
            $protectedValue = is_string($protectedValue) ? $protectedValue : '';
        }

        return $this->getValueFormattedAsSql($editField, $protectedValue);
    }

    /**
     * Get field-value pairs for update SQL.
     * During update, we build the SQL only with the fields that should be updated.
     */
    public function getQueryValueForUpdate(EditField $editField): string
    {
        $currentValueFormattedAsSql = $this->getValueFormattedAsSql($editField);

        // avoid setting a field to NULL when it's already NULL
        // (field had the null checkbox before the update; field still has the null checkbox)
        if ($editField->wasPreviouslyNull && $editField->isNull) {
            return '';
        }

        // A blob field that hasn't been changed will have no value
        if ($currentValueFormattedAsSql === '') {
            return '';
        }

        if (
            // Field had the null checkbox before the update; field no longer has the null checkbox
            $editField->wasPreviouslyNull ||
            // Field was marked as NULL (the value will be unchanged if it was an empty string)
            $editField->isNull ||
            // A function was applied to the field
            $editField->function !== '' ||
            // The value was changed
            $editField->value !== $editField->previousValue
        ) {
            return Util::backquote($editField->columnName) . ' = ' . $currentValueFormattedAsSql;
        }

        return '';
    }

    /**
     * Get the current column value in the form for different data types
     */
    private function formatAsSqlValueBasedOnType(
        EditField $editField,
        string $protectedValue,
    ): string {
        if ($editField->type === 'protected') {
            // here we are in protected mode (asked in the config)
            // so tbl_change has put this special value in the
            // columns array, so we do not change the column value
            // but we can still handle column upload

            // when in UPDATE mode, do not alter field's contents. When in INSERT
            // mode, insert empty field because no values were submitted.
            // If protected blobs were set, insert original field's content.
            if ($protectedValue !== '') {
                return '0x' . bin2hex($protectedValue);
            }

            if ($editField->isNull) {
                return 'NULL';
            }

            // The Null checkbox was unchecked for this field
            if ($editField->wasPreviouslyNull) {
                return "''";
            }

            return '';
        }

        if ($editField->value === '') {
            // When the field is autoIncrement, the best way to avoid problems
            // in strict mode is to set the value to null (works also in non-strict mode)

            // If the value is empty and the null checkbox is checked, set it to null
            return $editField->autoIncrement || $editField->isNull ? 'NULL' : "''";
        }

        if ($editField->type === 'hex') {
            if (! str_starts_with($editField->value, '0x')) {
                return '0x' . $editField->value;
            }

            return $editField->value;
        }

        if ($editField->type === 'bit') {
            $currentValue = (string) preg_replace('/[^01]/', '0', $editField->value);

            return 'b' . $this->dbi->quoteString($currentValue);
        }

        // For uuid type, generate uuid value
        // if empty value but not set null or value is uuid() function
        if (
            $editField->type === 'uuid'
                && ! $editField->isNull
                && in_array($editField->value, ["''", '', "'uuid()'", 'uuid()'], true)
        ) {
            return 'uuid()';
        }

        if (
            ($editField->type !== 'datetime' && $editField->type !== 'timestamp' && $editField->type !== 'date')
            || ($editField->value !== 'CURRENT_TIMESTAMP' && $editField->value !== 'current_timestamp()')
        ) {
            return $this->dbi->quoteString($editField->value);
        }

        // If there is a value, we ignore the Null checkbox;
        // this could be possible if Javascript is disabled in the browser
        return $editField->value;
    }

    /**
     * Check whether inline edited value can be truncated or not,
     * and add additional parameters for extra_data array  if needed
     *
     * @param string  $db         Database name
     * @param string  $table      Table name
     * @param string  $columnName Column name
     * @param mixed[] $extraData  Extra data for ajax response
     */
    public function verifyWhetherValueCanBeTruncatedAndAppendExtraData(
        string $db,
        string $table,
        string $columnName,
        array &$extraData,
    ): void {
        $extraData['isNeedToRecheck'] = false;

        $sqlForRealValue = 'SELECT ' . Util::backquote($table) . '.'
            . Util::backquote($columnName)
            . ' FROM ' . Util::backquote($db) . '.'
            . Util::backquote($table)
            . ' WHERE ' . $_POST['where_clause'][0];

        $result = $this->dbi->tryQuery($sqlForRealValue);

        if (! $result) {
            return;
        }

        $fieldsMeta = $this->dbi->getFieldsMeta($result);
        $meta = $fieldsMeta[0];
        $newValue = $result->fetchValue();

        if ($newValue === false) {
            return;
        }

        if ($newValue !== null) {
            if ($meta->isTimeType()) {
                $newValue = Util::addMicroseconds($newValue);
            } elseif ($meta->isBinary()) {
                $newValue = '0x' . bin2hex($newValue);
            }
        }

        $extraData['isNeedToRecheck'] = true;
        $extraData['truncatableFieldValue'] = $newValue;
    }

    /**
     * Function to get the columns of a table
     *
     * @param string $db    current db
     * @param string $table current table
     *
     * @return mixed[][]
     */
    public function getTableColumns(string $db, string $table): array
    {
        $this->dbi->selectDb($db);

        return array_values($this->dbi->getColumns($db, $table, true));
    }

    /**
     * Function to determine Insert/Edit rows
     *
     * @param string[]|string|null $whereClause where clause
     * @param string               $db          current database
     * @param string               $table       current table
     *
     * @return array<int, bool|string[]|string|ResultInterface|ResultInterface[]|null>
     * @phpstan-return array{
     *     bool,
     *     string[]|string|null,
     *     string[],
     *     string[]|null,
     *     ResultInterface[]|ResultInterface,
     *     array<string, string|null>[]|false[],
     *     bool,
     *     string|null
     * }
     */
    public function determineInsertOrEdit(array|string|null $whereClause, string $db, string $table): array
    {
        if (isset($_POST['where_clause'])) {
            $whereClause = $_POST['where_clause'];
        }

        if (isset($_SESSION['edit_next'])) {
            $whereClause = $_SESSION['edit_next'];
            unset($_SESSION['edit_next']);
            $afterInsert = 'edit_next';
        }

        if (isset($_POST['ShowFunctionFields'])) {
            $GLOBALS['cfg']['ShowFunctionFields'] = $_POST['ShowFunctionFields'];
        }

        if (isset($_POST['ShowFieldTypesInDataEditView'])) {
            $GLOBALS['cfg']['ShowFieldTypesInDataEditView'] = $_POST['ShowFieldTypesInDataEditView'];
        }

        if (isset($_POST['after_insert'])) {
            $afterInsert = $_POST['after_insert'];
        }

        if (isset($whereClause)) {
            // we are editing
            $insertMode = false;
            $whereClauseArray = (array) $whereClause;
            [$whereClauses, $result, $rows, $foundUniqueKey] = $this->analyzeWhereClauses(
                $whereClauseArray,
                $table,
                $db,
            );
        } else {
            // we are inserting
            $insertMode = true;
            $whereClause = null;
            $result = $this->loadFirstRow($table, $db);
            $rows = $this->getInsertRows();
            $whereClauses = null;
            $whereClauseArray = [];
            $foundUniqueKey = false;
        }

        /** @var string $defaultAction */
        $defaultAction = $_POST['default_action'] ?? $_GET['default_action'] ?? '';
        if ($defaultAction === 'insert') {
            // Copying a row - fetched data will be inserted as a new row, therefore the where clause is needless.
            $whereClause = $whereClauses = null;
        }

        return [
            $insertMode,
            $whereClause,
            $whereClauseArray,
            $whereClauses,
            $result,
            $rows,
            $foundUniqueKey,
            $afterInsert ?? null,
        ];
    }

    /**
     * Function to get comments for the table columns
     *
     * @param string $db    current database
     * @param string $table current table
     *
     * @return mixed[] comments for columns
     */
    public function getCommentsMap(string $db, string $table): array
    {
        if ($GLOBALS['cfg']['ShowPropertyComments']) {
            return $this->relation->getComments($db, $table);
        }

        return [];
    }

    /**
     * Function to get html for the gis editor div
     */
    public function getHtmlForGisEditor(): string
    {
        return '<div id="gis_editor"></div><div id="popup_background"></div><br>';
    }

    /**
     * Function to get html for the ignore option in insert mode
     *
     * @param int  $rowId   row id
     * @param bool $checked ignore option is checked or not
     */
    public function getHtmlForIgnoreOption(int $rowId, bool $checked = true): string
    {
        return '<input type="checkbox"'
            . ($checked ? ' checked="checked"' : '')
            . ' name="insert_ignore_' . $rowId . '"'
            . ' id="insert_ignore_' . $rowId . '">'
            . '<label for="insert_ignore_' . $rowId . '">'
            . __('Ignore')
            . '</label><br>' . "\n";
    }

    /**
     * Function to get html for the insert edit form header
     *
     * @param bool $hasBlobField whether has blob field
     * @param bool $isUpload     whether is upload
     */
    public function getHtmlForInsertEditFormHeader(bool $hasBlobField, bool $isUpload): string
    {
        $template = new Template();

        return $template->render('table/insert/get_html_for_insert_edit_form_header', [
            'has_blob_field' => $hasBlobField,
            'is_upload' => $isUpload,
        ]);
    }

    /**
     * Function to get html for each insert/edit column
     *
     * @param mixed[]         $column             column
     * @param int             $columnNumber       column index in table_columns
     * @param mixed[]         $commentsMap        comments map
     * @param ResultInterface $currentResult      current result
     * @param bool            $insertMode         whether insert mode
     * @param mixed[]         $currentRow         current row
     * @param int             $columnsCnt         columns count
     * @param bool            $isUpload           whether upload
     * @param mixed[]         $foreigners         foreigners
     * @param string          $table              table
     * @param string          $db                 database
     * @param int             $rowId              row id
     * @param string          $defaultCharEditing default char editing mode which is stored in the config.inc.php script
     * @param string          $textDir            text direction
     * @param mixed[]         $repopulate         the data to be repopulated
     * @param mixed[]         $columnMime         the mime information of column
     * @param string          $whereClause        the where clause
     */
    private function getHtmlForInsertEditFormColumn(
        array $column,
        int $columnNumber,
        array $commentsMap,
        ResultInterface $currentResult,
        bool $insertMode,
        array $currentRow,
        int $columnsCnt,
        bool $isUpload,
        array $foreigners,
        string $table,
        string $db,
        int $rowId,
        string $defaultCharEditing,
        string $textDir,
        array $repopulate,
        array $columnMime,
        string $whereClause,
    ): string {
        if (! isset($column['processed'])) {
            $column = $this->analyzeTableColumnsArray($column, $commentsMap);
        }

        $asIs = false;
        /** @var string $fieldHashMd5 */
        $fieldHashMd5 = $column['Field_md5'];
        if ($repopulate !== [] && array_key_exists($fieldHashMd5, $currentRow)) {
            $currentRow[$column['Field']] = $repopulate[$fieldHashMd5];
            $asIs = true;
        }

        $extractedColumnspec = Util::extractColumnSpec($column['Type']);

        if ($column['len'] === -1) {
            $column['len'] = $this->dbi->getFieldsMeta($currentResult)[$columnNumber]->length;
            // length is unknown for geometry fields,
            // make enough space to edit very simple WKTs
            if ($column['len'] === -1) {
                $column['len'] = 30;
            }
        }

        //Call validation when the form submitted...
        $onChangeClause = 'return verificationsAfterFieldChange('
            . json_encode($fieldHashMd5) . ', '
            . json_encode((string) $rowId) . ',' . json_encode($column['pma_type']) . ')';

        $vkey = '[multi_edit][' . $rowId . ']';
        // Use an MD5 as an array index to avoid having special characters
        // in the name attribute (see bug #1746964 )
        $columnNameAppendix = $vkey . '[' . $fieldHashMd5 . ']';

        if ($column['Type'] === 'datetime' && $column['Null'] !== 'YES' && ! isset($column['Default']) && $insertMode) {
            $column['Default'] = date('Y-m-d H:i:s', time());
        }

        // Get a list of GIS data types.
        $gisDataTypes = Gis::getDataTypes();

        // Prepares the field value
        if ($currentRow) {
            // (we are editing)
            [
                $realNullValue,
                $specialCharsEncoded,
                $specialChars,
                $data,
                $backupField,
            ] = $this->getSpecialCharsAndBackupFieldForExistingRow(
                $currentRow,
                $column,
                $extractedColumnspec,
                $gisDataTypes,
                $columnNameAppendix,
                $asIs,
            );
        } else {
            // (we are inserting)
            // display default values
            $defaultValue = $column['Default'] ?? null;
            if (isset($repopulate[$fieldHashMd5])) {
                $defaultValue = $repopulate[$fieldHashMd5];
            }

            $realNullValue = $defaultValue === null;
            $data = (string) $defaultValue;
            $specialChars = $this->getSpecialCharsForInsertingMode($defaultValue, $column['True_Type']);
            $specialCharsEncoded = Util::duplicateFirstNewline($specialChars);
            $backupField = '';
        }

        $this->fieldIndex = ($this->rowOffset * $columnsCnt) + $columnNumber + 1;

        // The function column
        // -------------------
        $foreignData = $this->relation->getForeignData($foreigners, $column['Field'], false, '', '');
        $isColumnBinary = $this->isColumnBinary($column, $isUpload);
        $functionOptions = '';

        if ($GLOBALS['cfg']['ShowFunctionFields']) {
            $functionOptions = Generator::getFunctionsForField($column, $insertMode, $foreignData);
        }

        // nullify code is needed by the js nullify() function to be able to generate calls to nullify() in jQuery
        $nullifyCode = $this->getNullifyCodeForNullColumn($column, $foreigners, $foreignData);

        // The value column (depends on type)
        // ----------------
        // See bug #1667887 for the reason why we don't use the maxlength
        // HTML attribute

        //add data attributes "no of decimals" and "data type"
        $noDecimals = 0;
        $type = current(explode('(', $column['pma_type']));
        if (preg_match('/\(([^()]+)\)/', $column['pma_type'], $match)) {
            $match[0] = trim($match[0], '()');
            $noDecimals = $match[0];
        }

        // Check input transformation of column
        $transformedHtml = '';
        if (! empty($columnMime['input_transformation'])) {
            $file = $columnMime['input_transformation'];
            $includeFile = 'libraries/classes/Plugins/Transformations/' . $file;
            if (is_file(ROOT_PATH . $includeFile)) {
                $className = $this->transformations->getClassName($includeFile);
                if (class_exists($className)) {
                    $transformationPlugin = new $className();
                    $transformationOptions = $this->transformations->getOptions(
                        $columnMime['input_transformation_options'],
                    );
                    $urlParams = [
                        'db' => $db,
                        'table' => $table,
                        'transform_key' => $column['Field'],
                        'where_clause_sign' => Core::signSqlQuery($whereClause),
                        'where_clause' => $whereClause,
                    ];
                    $transformationOptions['wrapper_link'] = Url::getCommon($urlParams);
                    $transformationOptions['wrapper_params'] = $urlParams;
                    $currentValue = '';
                    if (isset($currentRow[$column['Field']])) {
                        $currentValue = $currentRow[$column['Field']];
                    }

                    if ($transformationPlugin instanceof IOTransformationsPlugin) {
                        $transformedHtml = $transformationPlugin->getInputHtml(
                            $column,
                            $rowId,
                            $columnNameAppendix,
                            $transformationOptions,
                            $currentValue,
                            $textDir,
                            $this->fieldIndex,
                        );

                        $GLOBALS['plugin_scripts'] = array_merge(
                            $GLOBALS['plugin_scripts'],
                            $transformationPlugin->getScripts(),
                        );
                    }
                }
            }
        }

        $columnValue = '';
        $foreignDropdown = '';
        $dataType = '';
        $textAreaRows = $GLOBALS['cfg']['TextareaRows'];
        $textareaCols = $GLOBALS['cfg']['TextareaCols'];
        $maxlength = '';
        $enumSelectedValue = '';
        $columnSetValues = [];
        $setSelectSize = 0;
        $isColumnProtectedBlob = false;
        $blobValue = '';
        $blobValueUnit = '';
        $maxUploadSize = 0;
        $selectOptionForUpload = '';
        $inputFieldHtml = '';
        if (empty($transformedHtml)) {
            if (is_array($foreignData['disp_row'])) {
                $foreignDropdown = $this->relation->foreignDropdown(
                    $foreignData['disp_row'],
                    $foreignData['foreign_field'],
                    $foreignData['foreign_display'],
                    $data,
                    $GLOBALS['cfg']['ForeignKeyMaxLimit'],
                );
            }

            $dataType = $this->dbi->types->getTypeClass($column['True_Type']);

            if ($column['is_char']) {
                $textAreaRows = max($GLOBALS['cfg']['CharTextareaRows'], 7);
                $textareaCols = $GLOBALS['cfg']['CharTextareaCols'];
                $maxlength = $extractedColumnspec['spec_in_brackets'];
            } elseif ($GLOBALS['cfg']['LongtextDoubleTextarea'] && mb_strstr($column['pma_type'], 'longtext')) {
                $textAreaRows = $GLOBALS['cfg']['TextareaRows'] * 2;
                $textareaCols = $GLOBALS['cfg']['TextareaCols'] * 2;
            }

            if ($column['pma_type'] === 'enum') {
                $column['values'] ??= $extractedColumnspec['enum_set_values'];

                foreach ($column['values'] as $enumValue) {
                    if (
                        $data == $enumValue || ($data == ''
                            && (! isset($_POST['where_clause']) || $column['Null'] !== 'YES')
                            && isset($column['Default']) && $enumValue == $column['Default'])
                    ) {
                        $enumSelectedValue = $enumValue;
                        break;
                    }
                }
            } elseif ($column['pma_type'] === 'set') {
                $columnSetValues = $column['values'] ?? $extractedColumnspec['enum_set_values'];
                $setSelectSize = ! isset($column['values'])
                    ? min(4, count($extractedColumnspec['enum_set_values']))
                    : $column['select_size'];
            } elseif ($column['is_binary'] || $column['is_blob']) {
                $isColumnProtectedBlob = ($GLOBALS['cfg']['ProtectBinary'] === 'blob' && $column['is_blob'])
                    || ($GLOBALS['cfg']['ProtectBinary'] === 'all')
                    || ($GLOBALS['cfg']['ProtectBinary'] === 'noblob' && ! $column['is_blob']);
                if ($isColumnProtectedBlob) {
                    $blobSize = Util::formatByteDown(mb_strlen(stripslashes($data)), 3, 1);
                    if ($blobSize !== null) {
                        [$blobValue, $blobValueUnit] = $blobSize;
                    }
                }

                if ($isUpload && $column['is_blob']) {
                    $maxUploadSize = $this->getMaxUploadSize($column['pma_type']);
                }

                if (! empty($GLOBALS['cfg']['UploadDir'])) {
                    $selectOptionForUpload = $this->getSelectOptionForUpload($vkey, $fieldHashMd5);
                }

                if (
                    ! $isColumnProtectedBlob
                    && ! ($column['is_blob'] || ($column['len'] > $GLOBALS['cfg']['LimitChars']))
                ) {
                    $inputFieldHtml = $this->getHtmlInput(
                        $column,
                        $columnNameAppendix,
                        $specialChars,
                        min(max($column['len'], 4), $GLOBALS['cfg']['LimitChars']),
                        $onChangeClause,
                        'HEX',
                    );
                }
            } else {
                $columnValue = $this->getValueColumnForOtherDatatypes(
                    $column,
                    $defaultCharEditing,
                    $backupField,
                    $columnNameAppendix,
                    $onChangeClause,
                    $specialChars,
                    $textDir,
                    $specialCharsEncoded,
                    $data,
                    $extractedColumnspec,
                );
            }
        }

        return $this->template->render('table/insert/column_row', [
            'db' => $db,
            'table' => $table,
            'column' => $column,
            'row_id' => $rowId,
            'show_field_types_in_data_edit_view' => $GLOBALS['cfg']['ShowFieldTypesInDataEditView'],
            'show_function_fields' => $GLOBALS['cfg']['ShowFunctionFields'],
            'is_column_binary' => $isColumnBinary,
            'function_options' => $functionOptions,
            'nullify_code' => $nullifyCode,
            'real_null_value' => $realNullValue,
            'id_index' => $this->fieldIndex,
            'type' => $type,
            'decimals' => $noDecimals,
            'special_chars' => $specialChars,
            'transformed_value' => $transformedHtml,
            'value' => $columnValue,
            'is_value_foreign_link' => $foreignData['foreign_link'] === true,
            'backup_field' => $backupField,
            'data' => $data,
            'gis_data_types' => $gisDataTypes,
            'foreign_dropdown' => $foreignDropdown,
            'data_type' => $dataType,
            'textarea_cols' => $textareaCols,
            'textarea_rows' => $textAreaRows,
            'text_dir' => $textDir,
            'max_length' => $maxlength,
            'longtext_double_textarea' => $GLOBALS['cfg']['LongtextDoubleTextarea'],
            'enum_selected_value' => $enumSelectedValue,
            'set_values' => $columnSetValues,
            'set_select_size' => $setSelectSize,
            'is_column_protected_blob' => $isColumnProtectedBlob,
            'blob_value' => $blobValue,
            'blob_value_unit' => $blobValueUnit,
            'is_upload' => $isUpload,
            'max_upload_size' => $maxUploadSize,
            'select_option_for_upload' => $selectOptionForUpload,
            'limit_chars' => $GLOBALS['cfg']['LimitChars'],
            'input_field_html' => $inputFieldHtml,
        ]);
    }

    /** @param mixed[] $column */
    private function isColumnBinary(array $column, bool $isUpload): bool
    {
        if (! $GLOBALS['cfg']['ShowFunctionFields']) {
            return false;
        }

        return ($GLOBALS['cfg']['ProtectBinary'] === 'blob' && $column['is_blob'] && ! $isUpload)
            || ($GLOBALS['cfg']['ProtectBinary'] === 'all' && $column['is_binary'])
            || ($GLOBALS['cfg']['ProtectBinary'] === 'noblob' && $column['is_binary']);
    }

    /**
     * Function to get html for each insert/edit row
     *
     * @param mixed[]         $urlParams        url parameters
     * @param mixed[][]       $tableColumns     table columns
     * @param mixed[]         $commentsMap      comments map
     * @param ResultInterface $currentResult    current result
     * @param bool            $insertMode       whether insert mode
     * @param mixed[]         $currentRow       current row
     * @param bool            $isUpload         whether upload
     * @param mixed[]         $foreigners       foreigners
     * @param string          $table            table
     * @param string          $db               database
     * @param int             $rowId            row id
     * @param string          $textDir          text direction
     * @param mixed[]         $repopulate       the data to be repopulated
     * @param mixed[]         $whereClauseArray the array of where clauses
     */
    public function getHtmlForInsertEditRow(
        array $urlParams,
        array $tableColumns,
        array $commentsMap,
        ResultInterface $currentResult,
        bool $insertMode,
        array $currentRow,
        bool $isUpload,
        array $foreigners,
        string $table,
        string $db,
        int $rowId,
        string $textDir,
        array $repopulate,
        array $whereClauseArray,
    ): string {
        $htmlOutput = $this->getHeadAndFootOfInsertRowTable($urlParams)
            . '<tbody>';

        //store the default value for CharEditing
        $defaultCharEditing = $GLOBALS['cfg']['CharEditing'];
        $mimeMap = $this->transformations->getMime($db, $table);
        $whereClause = '';
        if (isset($whereClauseArray[$rowId])) {
            $whereClause = $whereClauseArray[$rowId];
        }

        $columnCount = count($tableColumns);
        for ($columnNumber = 0; $columnNumber < $columnCount; $columnNumber++) {
            $tableColumn = $tableColumns[$columnNumber];
            $columnMime = [];
            if (isset($mimeMap[$tableColumn['Field']])) {
                $columnMime = $mimeMap[$tableColumn['Field']];
            }

            $virtual = ['VIRTUAL', 'PERSISTENT', 'VIRTUAL GENERATED', 'STORED GENERATED'];
            if (in_array($tableColumn['Extra'], $virtual)) {
                continue;
            }

            $htmlOutput .= $this->getHtmlForInsertEditFormColumn(
                $tableColumn,
                $columnNumber,
                $commentsMap,
                $currentResult,
                $insertMode,
                $currentRow,
                $columnCount,
                $isUpload,
                $foreigners,
                $table,
                $db,
                $rowId,
                $defaultCharEditing,
                $textDir,
                $repopulate,
                $columnMime,
                $whereClause,
            );
        }

        $this->rowOffset++;

        return $htmlOutput . '  </tbody>'
            . '</table></div><br>'
            . '<div class="clearfloat"></div>';
    }

    /**
     * Returns list of function names that accept WKB as text
     *
     * @return string[]
     */
    private function getGisFromTextFunctions(): array
    {
        return $this->dbi->getVersion() >= 50600 ?
        [
            'ST_GeomFromText',
            'ST_GeomCollFromText',
            'ST_LineFromText',
            'ST_MLineFromText',
            'ST_PointFromText',
            'ST_MPointFromText',
            'ST_PolyFromText',
            'ST_MPolyFromText',
        ] :
        [
            'GeomFromText',
            'GeomCollFromText',
            'LineFromText',
            'MLineFromText',
            'PointFromText',
            'MPointFromText',
            'PolyFromText',
            'MPolyFromText',
        ];
    }

    /**
     * Returns list of function names that accept WKB as binary
     *
     * @return string[]
     */
    private function getGisFromWKBFunctions(): array
    {
        return $this->dbi->getVersion() >= 50600 ?
        [
            'ST_GeomFromWKB',
            'ST_GeomCollFromWKB',
            'ST_LineFromWKB',
            'ST_MLineFromWKB',
            'ST_PointFromWKB',
            'ST_MPointFromWKB',
            'ST_PolyFromWKB',
            'ST_MPolyFromWKB',
        ] :
        [
            'GeomFromWKB',
            'GeomCollFromWKB',
            'LineFromWKB',
            'MLineFromWKB',
            'PointFromWKB',
            'MPointFromWKB',
            'PolyFromWKB',
            'MPolyFromWKB',
        ];
    }
}
