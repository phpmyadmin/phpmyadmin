<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Indexes\Index;
use PhpMyAdmin\Query\Compatibility;

use function __;
use function _pgettext;
use function array_merge;
use function array_pop;
use function array_unique;
use function count;
use function explode;
use function htmlspecialchars;
use function implode;
use function in_array;
use function is_array;
use function json_encode;
use function mb_strtoupper;
use function sort;
use function sprintf;
use function str_replace;
use function trim;

/**
 * Set of functions used for normalization
 */
class Normalization
{
    private readonly Config $config;

    public function __construct(
        private DatabaseInterface $dbi,
        private Relation $relation,
        private Transformations $transformations,
        public Template $template,
    ) {
        $this->config = Config::getInstance();
    }

    /**
     * build the html for columns of $colTypeCategory category
     * in form of given $listType in a table
     *
     * @param string $db              current database
     * @param string $table           current table
     * @param string $colTypeCategory supported all|Numeric|String|Spatial
     *                                |Date and time using the _pgettext() format
     * @param string $listType        type of list to build, supported dropdown|checkbox
     *
     * @return string HTML for list of columns in form of given list types
     */
    public function getHtmlForColumnsList(
        string $db,
        string $table,
        string $colTypeCategory = 'all',
        string $listType = 'dropdown',
    ): string {
        $columnTypeList = [];
        if ($colTypeCategory !== 'all') {
            $types = $this->dbi->types->getColumns();
            $columnTypeList = $types[$colTypeCategory];
            if (! is_array($columnTypeList)) {
                $columnTypeList = [];
            }
        }

        $this->dbi->selectDb($db);
        $columns = $this->dbi->getColumns($db, $table);
        $selectColHtml = '';
        foreach ($columns as $def) {
            $column = $def->field;
            $extractedColumnSpec = Util::extractColumnSpec($def->type);

            if (
                $columnTypeList !== [] && ! in_array(mb_strtoupper(
                    $extractedColumnSpec['type'],
                ), $columnTypeList, true)
            ) {
                continue;
            }

            if ($listType === 'checkbox') {
                $selectColHtml .= '<input type="checkbox" value="'
                    . htmlspecialchars($column) . '">'
                    . htmlspecialchars($column) . ' [ '
                    . htmlspecialchars($def->type) . ' ]<br>';
            } else {
                $selectColHtml .= '<option value="' . htmlspecialchars($column)
                . '">' . htmlspecialchars($column)
                . ' [ ' . htmlspecialchars($def->type) . ' ]'
                . '</option>';
            }
        }

        return $selectColHtml;
    }

    /**
     * get the html of the form to add the new column to given table
     *
     * @param int     $numFields  number of columns to add
     * @param string  $db         current database
     * @param string  $table      current table
     * @param mixed[] $columnMeta array containing default values for the fields
     *
     * @return string HTML
     */
    public function getHtmlForCreateNewColumn(
        UserPrivileges $userPrivileges,
        int $numFields,
        string $db,
        string $table,
        array $columnMeta = [],
    ): string {
        $relationParameters = $this->relation->getRelationParameters();
        $contentCells = [];
        $availableMime = [];
        $mimeMap = [];
        if ($relationParameters->browserTransformationFeature !== null && $this->config->settings['BrowseMIME']) {
            $mimeMap = $this->transformations->getMime($db, $table);
            $availableMime = $this->transformations->getAvailableMimeTypes();
        }

        $commentsMap = $this->relation->getComments($db, $table);
        /** @infection-ignore-all */
        for ($columnNumber = 0; $columnNumber < $numFields; $columnNumber++) {
            $contentCells[$columnNumber] = [
                'column_number' => $columnNumber,
                'column_meta' => $columnMeta,
                'type_upper' => '',
                'length_values_input_size' => 8,
                'length' => '',
                'extracted_columnspec' => [],
                'submit_attribute' => null,
                'comments_map' => $commentsMap,
                'fields_meta' => null,
                'is_backup' => true,
                'move_columns' => [],
                'available_mime' => $availableMime,
                'mime_map' => $mimeMap,
            ];
        }

        $charsets = Charsets::getCharsets($this->dbi, $this->config->selectedServer['DisableIS']);
        $collations = Charsets::getCollations($this->dbi, $this->config->selectedServer['DisableIS']);
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

        return $this->template->render('columns_definitions/table_fields_definitions', [
            'is_backup' => true,
            'fields_meta' => null,
            'relation_parameters' => $relationParameters,
            'content_cells' => $contentCells,
            'change_column' => $_POST['change_column'] ?? $_GET['change_column'] ?? null,
            'is_virtual_columns_supported' => Compatibility::isVirtualColumnsSupported($this->dbi->getVersion()),
            'browse_mime' => $this->config->settings['BrowseMIME'],
            'supports_stored_keyword' => Compatibility::supportsStoredKeywordForVirtualColumns(
                $this->dbi->getVersion(),
            ),
            'server_version' => $this->dbi->getVersion(),
            'max_rows' => (int) $this->config->settings['MaxRows'],
            'char_editing' => $this->config->settings['CharEditing'],
            'attribute_types' => $this->dbi->types->getAttributes(),
            'privs_available' => $userPrivileges->column && $userPrivileges->isReload,
            'max_length' => $this->dbi->getVersion() >= 50503 ? 1024 : 255,
            'charsets' => $charsetsList,
        ]);
    }

    /**
     * build the html for step 1.1 of normalization
     *
     * @param string $db           current database
     * @param string $table        current table
     * @param string $normalizedTo up to which step normalization will go,
     *                             possible values 1nf|2nf|3nf
     *
     * @return string HTML for step 1.1
     */
    public function getHtmlFor1NFStep1(string $db, string $table, string $normalizedTo): string
    {
        $step = 1;
        $stepTxt = __('Make all columns atomic');
        $html = '<h3>' . __('First step of normalization (1NF)') . '</h3>';
        $html .= '<div class="card" id="mainContent" data-normalizeto="' . $normalizedTo . '">'
            . '<div class="card-header">' . __('Step 1.') . $step . ' ' . $stepTxt . '</div>'
            . '<div class="card-body">'
            . '<h4>' . __(
                'Do you have any column which can be split into more than one column?'
                . ' For example: address can be split into street, city, country and zip.',
            )
            . "<br>(<a class='central_columns_dialog' data-maxrows='25' "
            . "data-pick=false href='#'> "
            . __('Show me the central list of columns that are not already in this table') . ' </a>)</h4>'
            . "<p class='cm-em'>" . __(
                'Select a column which can be split into more '
                . 'than one (on select of \'no such column\', it\'ll move to next step).',
            )
            . '</p>'
            . "<div id='extra'>"
            . "<select id='selectNonAtomicCol' name='makeAtomic'>"
            . '<option selected disabled>'
            . __('Select one…') . '</option>'
            . "<option value='no_such_col'>" . __('No such column') . '</option>'
            . $this->getHtmlForColumnsList(
                $db,
                $table,
                _pgettext('string types', 'String'),
            )
            . '</select>'
            . '<span>' . __('split into ')
            . "</span><input id='numField' type='number' value='2'>"
            . '<input type="submit" class="btn btn-primary" id="splitGo" value="' . __('Go') . '"></div>'
            . "<div id='newCols'></div>"
            . '</div><div class="card-footer"></div>'
            . '</div>';

        return $html;
    }

    /**
     * build the html contents of various html elements in step 1.2
     *
     * @param string $db    current database
     * @param string $table current table
     *
     * @return array{legendText: string, headText: string, subText: string, hasPrimaryKey: string, extra: string}
     */
    public function getHtmlContentsFor1NFStep2(string $db, string $table): array
    {
        $step = 2;
        $stepTxt = __('Have a primary key');
        $primary = Index::getPrimary($this->dbi, $table, $db);
        $hasPrimaryKey = '0';
        $legendText = __('Step 1.') . $step . ' ' . $stepTxt;
        $extra = '';
        if ($primary !== null) {
            $headText = __('Primary key already exists.');
            $subText = __('Taking you to next step…');
            $hasPrimaryKey = '1';
        } else {
            $headText = __(
                'There is no primary key; please add one.<br>'
                . 'Hint: A primary key is a column '
                . '(or combination of columns) that uniquely identify all rows.',
            );
            $subText = '<a href="#" id="createPrimaryKey">'
                . Generator::getIcon(
                    'b_index_add',
                    __(
                        'Add a primary key on existing column(s)',
                    ),
                )
                . '</a>';
            $extra = __('If it\'s not possible to make existing column combinations as primary key') . '<br>'
                . '<a href="#" id="addNewPrimary">'
                . __('+ Add a new primary key column') . '</a>';
        }

        return [
            'legendText' => $legendText,
            'headText' => $headText,
            'subText' => $subText,
            'hasPrimaryKey' => $hasPrimaryKey,
            'extra' => $extra,
        ];
    }

    /**
     * build the html contents of various html elements in step 1.4
     *
     * @param string $db    current database
     * @param string $table current table
     *
     * @return array{legendText: string, headText: string, subText: string, extra: string} HTML contents for step 1.4
     */
    public function getHtmlContentsFor1NFStep4(string $db, string $table): array
    {
        $step = 4;
        $stepTxt = __('Remove redundant columns');
        $legendText = __('Step 1.') . $step . ' ' . $stepTxt;
        $headText = __(
            'Do you have a group of columns which on combining gives an existing'
            . ' column? For example, if you have first_name, last_name and'
            . ' full_name then combining first_name and last_name gives full_name'
            . ' which is redundant.',
        );
        $subText = __(
            'Check the columns which are redundant and click on remove. '
            . "If no redundant column, click on 'No redundant column'",
        );
        $extra = $this->getHtmlForColumnsList($db, $table, 'all', 'checkbox') . '<br>'
            . '<input class="btn btn-secondary" type="submit" id="removeRedundant" value="'
            . __('Remove selected') . '">'
            . '<input class="btn btn-secondary" type="submit" id="noRedundantColumn" value="'
            . __('No redundant column') . '">';

        return ['legendText' => $legendText, 'headText' => $headText, 'subText' => $subText, 'extra' => $extra];
    }

    /**
     * build the html contents of various html elements in step 1.3
     *
     * @param string $db    current database
     * @param string $table current table
     *
     * @return array{legendText: string, headText: string, subText: string, extra: string, primary_key: false|string}
     */
    public function getHtmlContentsFor1NFStep3(string $db, string $table): array
    {
        $step = 3;
        $stepTxt = __('Move repeating groups');
        $legendText = __('Step 1.') . $step . ' ' . $stepTxt;
        $headText = __(
            'Do you have a group of two or more columns that are closely '
            . 'related and are all repeating the same attribute? For example, '
            . 'a table that holds data on books might have columns such as book_id, '
            . 'author1, author2, author3 and so on which form a '
            . 'repeating group. In this case a new table (book_id, author) should '
            . 'be created.',
        );
        $subText = __(
            'Check the columns which form a repeating group. If no such group, click on \'No repeating group\'',
        );
        $extra = $this->getHtmlForColumnsList($db, $table, 'all', 'checkbox') . '<br>'
            . '<input class="btn btn-secondary" type="submit" id="moveRepeatingGroup" value="'
            . __('Done') . '">'
            . '<input class="btn btn-secondary" type="submit" value="' . __('No repeating group')
            . '" id="noRepeatingGroup">';
        $primary = Index::getPrimary($this->dbi, $table, $db);
        $primarycols = $primary === null ? [] : $primary->getColumns();
        $pk = [];
        foreach ($primarycols as $col) {
            $pk[] = $col->getName();
        }

        return [
            'legendText' => $legendText,
            'headText' => $headText,
            'subText' => $subText,
            'extra' => $extra,
            'primary_key' => json_encode($pk),
        ];
    }

    /**
     * build html contents for 2NF step 2.1
     *
     * @param string $db    current database
     * @param string $table current table
     *
     * @return array{legendText: string, headText: string, subText: string, extra: string, primary_key: string}
     */
    public function getHtmlFor2NFstep1(string $db, string $table): array
    {
        $legendText = __('Step 2.') . '1 ' . __('Find partial dependencies');
        $primary = Index::getPrimary($this->dbi, $table, $db);
        $primarycols = $primary === null ? [] : $primary->getColumns();
        $pk = [];
        $subText = '';
        $selectPkForm = '';
        $extra = '';
        foreach ($primarycols as $col) {
            $pk[] = $col->getName();
            $selectPkForm .= '<input type="checkbox" name="pd" value="'
                . htmlspecialchars($col->getName()) . '">'
                . htmlspecialchars($col->getName());
        }

        $key = implode(', ', $pk);
        if (count($primarycols) > 1) {
            $this->dbi->selectDb($db);
            $columns = $this->dbi->getColumnNames($db, $table);
            if (count($pk) === count($columns)) {
                $headText = sprintf(
                    __(
                        'No partial dependencies possible as '
                        . 'no non-primary column exists since primary key ( %1$s ) '
                        . 'is composed of all the columns in the table.',
                    ),
                    htmlspecialchars($key),
                ) . '<br>';
                $extra = '<h3>' . __('Table is already in second normal form.')
                    . '</h3>';
            } else {
                $headText = sprintf(
                    __(
                        'The primary key ( %1$s ) consists of more than one column '
                        . 'so we need to find the partial dependencies.',
                    ),
                    htmlspecialchars($key),
                ) . '<br>' . __('Please answer the following question(s) carefully to obtain a correct normalization.')
                    . '<br><a href="#" id="showPossiblePd">' . __(
                        '+ Show me the possible partial dependencies based on data in the table',
                    ) . '</a>';
                $subText = __(
                    'For each column below, '
                    . 'please select the <b>minimal set</b> of columns among given set '
                    . 'whose values combined together are sufficient'
                    . ' to determine the value of the column.',
                );
                $cnt = 0;
                foreach ($columns as $column) {
                    if (in_array($column, $pk, true)) {
                        continue;
                    }

                    $cnt++;
                    $extra .= '<b>' . sprintf(
                        __('\'%1$s\' depends on:'),
                        htmlspecialchars($column),
                    ) . '</b><br>';
                    $extra .= '<form id="pk_' . $cnt . '" data-colname="'
                        . htmlspecialchars($column) . '" class="smallIndent">'
                        . $selectPkForm . '</form><br><br>';
                }
            }
        } else {
            $headText = sprintf(
                __(
                    'No partial dependencies possible as the primary key ( %1$s ) has just one column.',
                ),
                htmlspecialchars($key),
            ) . '<br>';
            $extra = '<h3>' . __('Table is already in second normal form.') . '</h3>';
        }

        return [
            'legendText' => $legendText,
            'headText' => $headText,
            'subText' => $subText,
            'extra' => $extra,
            'primary_key' => $key,
        ];
    }

    /**
     * build the html for showing the tables to have in order to put current table in 2NF
     *
     * @param mixed[] $partialDependencies array containing all the dependencies
     * @param string  $table               current table
     *
     * @return string HTML
     */
    public function getHtmlForNewTables2NF(array $partialDependencies, string $table): string
    {
        $html = '<p><b>' . sprintf(
            __(
                'In order to put the '
                . 'original table \'%1$s\' into Second normal form we need '
                . 'to create the following tables:',
            ),
            htmlspecialchars($table),
        ) . '</b></p>';
        $tableName = $table;
        $i = 1;
        foreach ($partialDependencies as $key => $dependents) {
            $html .= '<p><input type="text" name="' . htmlspecialchars($key)
                . '" value="' . htmlspecialchars($tableName) . '">'
                . '( <u>' . htmlspecialchars($key) . '</u>'
                . (count($dependents) > 0 ? ', ' : '')
                . htmlspecialchars(implode(', ', $dependents)) . ' )';
            $i++;
            $tableName = 'table' . $i;
        }

        return $html;
    }

    /**
     * create/alter the tables needed for 2NF
     *
     * @param mixed[] $partialDependencies array containing all the partial dependencies
     * @param object  $tablesName          name of new tables
     * @param string  $table               current table
     * @param string  $db                  current database
     *
     * @return array{legendText: string, headText: string, queryError: bool, extra: Message}
     */
    public function createNewTablesFor2NF(
        array $partialDependencies,
        object $tablesName,
        string $table,
        string $db,
    ): array {
        $dropCols = false;
        $nonPKCols = [];
        $queries = [];
        $error = false;
        $headText = '<h3>' . sprintf(
            __('The second step of normalization is complete for table \'%1$s\'.'),
            htmlspecialchars($table),
        ) . '</h3>';
        if (count($partialDependencies) === 1) {
            return ['legendText' => __('End of step'), 'headText' => $headText, 'queryError' => false];
        }

        $message = '';
        $this->dbi->selectDb($db);
        foreach ($partialDependencies as $key => $dependents) {
            if ($tablesName->$key != $table) {
                $keys = explode(', ', $key);
                $quotedKeys = [];
                foreach ($keys as $eachKey) {
                    $quotedKeys[] = Util::backquote($eachKey);
                }

                $backquotedKey = implode(', ', $quotedKeys);

                $quotedDependents = [];
                foreach ($dependents as $dependent) {
                    $quotedDependents[] = Util::backquote($dependent);
                }

                $queries[] = 'CREATE TABLE ' . Util::backquote($tablesName->$key)
                    . ' SELECT DISTINCT ' . $backquotedKey
                    . (count($dependents) > 0 ? ', ' : '')
                    . implode(',', $quotedDependents)
                    . ' FROM ' . Util::backquote($table) . ';';
                $queries[] = 'ALTER TABLE ' . Util::backquote($tablesName->$key)
                    . ' ADD PRIMARY KEY(' . $backquotedKey . ');';
                $nonPKCols = array_merge($nonPKCols, $dependents);
            } else {
                $dropCols = true;
            }
        }

        if ($dropCols) {
            $query = 'ALTER TABLE ' . Util::backquote($table);
            foreach ($nonPKCols as $col) {
                $query .= ' DROP ' . Util::backquote($col) . ',';
            }

            $query = trim($query, ', ');
            $query .= ';';
            $queries[] = $query;
        } else {
            $queries[] = 'DROP TABLE ' . Util::backquote($table);
        }

        foreach ($queries as $query) {
            if (! $this->dbi->tryQuery($query)) {
                $message = Message::error(__('Error in processing!'));
                $message->addMessage(
                    Message::rawError($this->dbi->getError()),
                    '<br><br>',
                );
                $error = true;
                break;
            }
        }

        return [
            'legendText' => __('End of step'),
            'headText' => $headText,
            'queryError' => $error,
            'extra' => $message,
        ];
    }

    /**
     * build the html for showing the new tables to have in order
     * to put given tables in 3NF
     *
     * @param object  $dependencies containing all the dependencies
     * @param mixed[] $tables       tables formed after 2NF and need to convert to 3NF
     * @param string  $db           current database
     *
     * @return array{html:string, newTables:mixed[], success:true} containing html and the list of new tables
     */
    public function getHtmlForNewTables3NF(object $dependencies, array $tables, string $db): array
    {
        $html = '';
        $i = 1;
        $newTables = [];
        foreach ($tables as $table => $arrDependson) {
            if (count(array_unique($arrDependson)) === 1) {
                continue;
            }

            $primary = Index::getPrimary($this->dbi, $table, $db);
            $primarycols = $primary === null ? [] : $primary->getColumns();
            $pk = [];
            foreach ($primarycols as $col) {
                $pk[] = $col->getName();
            }

            $html .= '<p><b>' . sprintf(
                __(
                    'In order to put the '
                    . 'original table \'%1$s\' into Third normal form we need '
                    . 'to create the following tables:',
                ),
                htmlspecialchars($table),
            ) . '</b></p>';
            $tableName = $table;
            $columnList = [];
            foreach ($arrDependson as $key) {
                $dependents = $dependencies->$key;
                if ($key == $table) {
                    $key = implode(', ', $pk);
                }

                $tmpTableCols = array_merge(explode(', ', $key), $dependents);
                sort($tmpTableCols);
                if (in_array($tmpTableCols, $columnList)) {
                    continue;
                }

                $columnList[] = $tmpTableCols;
                $html .= '<p><input type="text" name="'
                    . htmlspecialchars($tableName)
                    . '" value="' . htmlspecialchars($tableName) . '">'
                    . '( <u>' . htmlspecialchars($key) . '</u>'
                    . (count($dependents) > 0 ? ', ' : '')
                    . htmlspecialchars(implode(', ', $dependents)) . ' )';
                $newTables[$table][$tableName] = ['pk' => $key, 'nonpk' => implode(', ', $dependents)];
                $i++;
                $tableName = 'table' . $i;
            }
        }

        return ['html' => $html, 'newTables' => $newTables, 'success' => true];
    }

    /**
     * create new tables or alter existing to get 3NF
     *
     * @param mixed[] $newTables list of new tables to be created
     * @param string  $db        current database
     *
     * @return array{legendText: string, headText: string, queryError: string|false, extra?: string}
     */
    public function createNewTablesFor3NF(array $newTables, string $db): array
    {
        $queries = [];
        $dropCols = false;
        $error = false;
        $headText = '<h3>' . __('The third step of normalization is complete.') . '</h3>';
        if ($newTables === []) {
            return ['legendText' => __('End of step'), 'headText' => $headText, 'queryError' => false];
        }

        $message = '';
        $this->dbi->selectDb($db);
        foreach ($newTables as $originalTable => $tablesList) {
            foreach ($tablesList as $table => $cols) {
                if ($table != $originalTable) {
                    $pkArray = explode(', ', $cols['pk']);
                    $quotedPkArray = [];
                    foreach ($pkArray as $pk) {
                        $quotedPkArray[] = Util::backquote($pk);
                    }

                    $quotedPk = implode(', ', $quotedPkArray);

                    $nonpkArray = explode(', ', $cols['nonpk']);
                    $quotedNonpkArray = [];
                    foreach ($nonpkArray as $nonpk) {
                        $quotedNonpkArray[] = Util::backquote($nonpk);
                    }

                    $quotedNonpk = implode(', ', $quotedNonpkArray);

                    $queries[] = 'CREATE TABLE ' . Util::backquote($table)
                        . ' SELECT DISTINCT ' . $quotedPk
                        . ', ' . $quotedNonpk
                        . ' FROM ' . Util::backquote($originalTable) . ';';
                    $queries[] = 'ALTER TABLE ' . Util::backquote($table)
                        . ' ADD PRIMARY KEY(' . $quotedPk . ');';
                } else {
                    $dropCols = $cols;
                }
            }

            if ($dropCols) {
                $columns = $this->dbi->getColumnNames($db, $originalTable);
                $colPresent = array_merge(
                    explode(', ', $dropCols['pk']),
                    explode(', ', $dropCols['nonpk']),
                );
                $query = 'ALTER TABLE ' . Util::backquote($originalTable);
                foreach ($columns as $col) {
                    if (in_array($col, $colPresent, true)) {
                        continue;
                    }

                    $query .= ' DROP ' . Util::backquote($col) . ',';
                }

                $query = trim($query, ', ');
                $query .= ';';
                $queries[] = $query;
            } else {
                $queries[] = 'DROP TABLE ' . Util::backquote($originalTable);
            }

            $dropCols = false;
        }

        foreach ($queries as $query) {
            if (! $this->dbi->tryQuery($query)) {
                $message = Message::error(__('Error in processing!'));
                $message->addMessage(
                    Message::rawError($this->dbi->getError()),
                    '<br><br>',
                );
                $error = true;
                break;
            }
        }

        return [
            'legendText' => __('End of step'),
            'headText' => $headText,
            'queryError' => $error,
            'extra' => $message,
        ];
    }

    /**
     * move the repeating group of columns to a new table
     *
     * @param string $repeatingColumns comma separated list of repeating group columns
     * @param string $primaryColumns   comma separated list of column in primary key
     *                                 of $table
     * @param string $newTable         name of the new table to be created
     * @param string $newColumn        name of the new column in the new table
     * @param string $table            current table
     * @param string $db               current database
     *
     * @return array{queryError: bool, message: Message}
     */
    public function moveRepeatingGroup(
        string $repeatingColumns,
        string $primaryColumns,
        string $newTable,
        string $newColumn,
        string $table,
        string $db,
    ): array {
        $repeatingColumnsArr = explode(', ', $repeatingColumns);
        $primaryColumnsArray = explode(',', $primaryColumns);
        $columns = [];
        foreach ($primaryColumnsArray as $column) {
            $columns[] = Util::backquote($column);
        }

        $primaryColumns = implode(',', $columns);
        $query1 = 'CREATE TABLE ' . Util::backquote($newTable);
        $query2 = 'ALTER TABLE ' . Util::backquote($table);
        $message = Message::success(
            sprintf(
                __('Selected repeating group has been moved to the table \'%s\''),
                htmlspecialchars($table),
            ),
        );
        $first = true;
        $error = false;
        foreach ($repeatingColumnsArr as $repeatingColumn) {
            if (! $first) {
                $query1 .= ' UNION ';
            }

            $first = false;
            $quotedRepeatingColumn = Util::backquote($repeatingColumn);
            $query1 .= ' SELECT ' . $primaryColumns . ',' . $quotedRepeatingColumn
                . ' as ' . Util::backquote($newColumn)
                . ' FROM ' . Util::backquote($table);
            $query2 .= ' DROP ' . $quotedRepeatingColumn . ',';
        }

        $query2 = trim($query2, ',');
        $queries = [$query1, $query2];
        $this->dbi->selectDb($db);
        foreach ($queries as $query) {
            if (! $this->dbi->tryQuery($query)) {
                $message = Message::error(__('Error in processing!'));
                $message->addMessage(
                    Message::rawError($this->dbi->getError()),
                    '<br><br>',
                );
                $error = true;
                break;
            }
        }

        return ['queryError' => $error, 'message' => $message];
    }

    /**
     * build html for 3NF step 1 to find the transitive dependencies
     *
     * @param string  $db     current database
     * @param mixed[] $tables tables formed after 2NF and need to process for 3NF
     *
     * @return array{legendText: string, headText: string, subText: string, extra: string}
     */
    public function getHtmlFor3NFstep1(string $db, array $tables): array
    {
        $legendText = __('Step 3.') . '1 ' . __('Find transitive dependencies');
        $extra = '';
        $headText = __('Please answer the following question(s) carefully to obtain a correct normalization.');
        $subText = __(
            'For each column below, '
            . 'please select the <b>minimal set</b> of columns among given set '
            . 'whose values combined together are sufficient'
            . ' to determine the value of the column.<br>'
            . 'Note: A column may have no transitive dependency, '
            . 'in that case you don\'t have to select any.',
        );
        $cnt = 0;
        $this->dbi->selectDb($db);
        foreach ($tables as $table) {
            $primary = Index::getPrimary($this->dbi, $table, $db);
            $primarycols = $primary === null ? [] : $primary->getColumns();
            $selectTdForm = '';
            $pk = [];
            foreach ($primarycols as $col) {
                $pk[] = $col->getName();
            }

            $columns = $this->dbi->getColumnNames($db, $table);
            if (count($columns) - count($pk) <= 1) {
                continue;
            }

            foreach ($columns as $column) {
                if (in_array($column, $pk, true)) {
                    continue;
                }

                $selectTdForm .= '<input type="checkbox" name="pd" value="'
                . htmlspecialchars($column) . '">'
                . '<span>' . htmlspecialchars($column) . '</span>';
            }

            foreach ($columns as $column) {
                if (in_array($column, $pk, true)) {
                    continue;
                }

                $cnt++;
                $extra .= '<b>' . sprintf(
                    __('\'%1$s\' depends on:'),
                    htmlspecialchars($column),
                )
                    . '</b><br>';
                $extra .= '<form id="td_' . $cnt . '" data-colname="'
                    . htmlspecialchars($column) . '" data-tablename="'
                    . htmlspecialchars($table) . '" class="smallIndent">'
                    . $selectTdForm
                    . '</form><br><br>';
            }
        }

        if ($extra === '') {
            $headText = __(
                'No Transitive dependencies possible as the table doesn\'t have any non primary key columns',
            );
            $subText = '';
            $extra = '<h3>' . __('Table is already in Third normal form!') . '</h3>';
        }

        return ['legendText' => $legendText, 'headText' => $headText, 'subText' => $subText, 'extra' => $extra];
    }

    /**
     * find all the possible partial dependencies based on data in the table.
     *
     * @param string $table current table
     * @param string $db    current database
     *
     * @return string HTML containing the list of all the possible partial dependencies
     */
    public function findPartialDependencies(string $table, string $db): string
    {
        $dependencyList = [];
        $this->dbi->selectDb($db);
        $columnNames = $this->dbi->getColumnNames($db, $table);
        $columns = [];
        foreach ($columnNames as $column) {
            $columns[] = Util::backquote($column);
        }

        $totalRows = (int) $this->dbi->fetchValue(
            'SELECT COUNT(*) FROM (SELECT * FROM '
            . Util::backquote($table) . ' LIMIT 500) as dt;',
        );
        $primary = Index::getPrimary($this->dbi, $table, $db);
        $primarycols = $primary === null ? [] : $primary->getColumns();
        $pk = [];
        foreach ($primarycols as $col) {
            $pk[] = Util::backquote($col->getName());
        }

        $partialKeys = $this->getAllCombinationPartialKeys($pk);
        $distinctValCount = $this->findDistinctValuesCount(
            array_unique(
                array_merge($columns, $partialKeys),
            ),
            $table,
        );
        foreach ($columns as $column) {
            if (in_array($column, $pk, true)) {
                continue;
            }

            foreach ($partialKeys as $partialKey) {
                if (
                    ! $partialKey
                    || ! $this->checkPartialDependency(
                        $partialKey,
                        $column,
                        $table,
                        $distinctValCount[$partialKey],
                        $distinctValCount[$column],
                        $totalRows,
                    )
                ) {
                    continue;
                }

                $dependencyList[$partialKey][] = $column;
            }
        }

        $html = __('This list is based on a subset of the table\'s data and is not necessarily accurate. ')
            . '<div class="dependencies_box">';
        foreach ($dependencyList as $dependon => $colList) {
            $html .= '<span class="d-block">'
                . '<input type="button" class="btn btn-secondary pickPd" value="' . __('Pick') . '">'
                . '<span class="determinants">'
                . htmlspecialchars(str_replace('`', '', (string) $dependon)) . '</span> -> '
                . '<span class="dependents">'
                . htmlspecialchars(str_replace('`', '', implode(', ', $colList)))
                . '</span>'
                . '</span>';
        }

        if ($dependencyList === []) {
            $html .= '<p class="d-block m-1">'
                . __('No partial dependencies found!') . '</p>';
        }

        $html .= '</div>';

        return $html;
    }

    /**
     * check whether a particular column is dependent on given subset of primary key
     *
     * @param string $partialKey the partial key, subset of primary key,
     *                           each column in key supposed to be backquoted
     * @param string $column     backquoted column on whose dependency being checked
     * @param string $table      current table
     * @param int    $pkCnt      distinct value count for given partial key
     * @param int    $colCnt     distinct value count for given column
     * @param int    $totalRows  total distinct rows count of the table
     */
    private function checkPartialDependency(
        string $partialKey,
        string $column,
        string $table,
        int $pkCnt,
        int $colCnt,
        int $totalRows,
    ): bool {
        $query = 'SELECT '
            . 'COUNT(DISTINCT ' . $partialKey . ',' . $column . ') as pkColCnt '
            . 'FROM (SELECT * FROM ' . Util::backquote($table)
            . ' LIMIT 500) as dt;';
        $pkColCnt = (int) $this->dbi->fetchValue($query);
        if ($pkCnt !== 0 && $pkCnt === $colCnt && $colCnt === $pkColCnt) {
            return true;
        }

        return $totalRows !== 0 && $totalRows === $pkCnt;
    }

    /**
     * function to get distinct values count of all the column in the array $columns
     *
     * @param string[] $columns array of backquoted columns whose distinct values
     *                       need to be counted.
     * @param string   $table   table to which these columns belong
     *
     * @return int[] associative array containing the count
     */
    private function findDistinctValuesCount(array $columns, string $table): array
    {
        $query = 'SELECT ';
        foreach ($columns as $column) {
            if ($column === '') {
                continue;
            }

            //each column is already backquoted
            $query .= 'COUNT(DISTINCT ' . $column . ') as \'' . $column . '_cnt\', ';
        }

        $query = trim($query, ', ');
        $query .= ' FROM (SELECT * FROM ' . Util::backquote($table)
            . ' LIMIT 500) as dt;';
        $res = $this->dbi->fetchSingleRow($query);
        if ($res === []) {
            return [];
        }

        $result = [];
        foreach ($columns as $column) {
            if ($column === '') {
                continue;
            }

            $result[$column] = (int) $res[$column . '_cnt'];
        }

        return $result;
    }

    /**
     * find all the possible partial keys
     *
     * @param list<string> $primaryKey array containing all the column present in primary key
     *
     * @return string[] containing all the possible partial keys(subset of primary key)
     */
    private function getAllCombinationPartialKeys(array $primaryKey): array
    {
        $results = [''];
        foreach ($primaryKey as $element) {
            foreach ($results as $combination) {
                $results[] = trim($element . ',' . $combination, ',');
            }
        }

        array_pop($results); //remove key which consist of all primary key columns

        return $results;
    }
}
