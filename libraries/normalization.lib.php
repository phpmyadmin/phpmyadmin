<?php

/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * set of functions used for normalization
 *
 * @package PhpMyAdmin
 */

if (! defined('PHPMYADMIN')) {
    exit;
}
/**
 * build the html for columns of $colTypeCategory category
 * in form of given $listType in a table
 *
 * @param string $db              current databse
 * @param string $table           current table
 * @param string $colTypeCategory supported all|Numeric|String|Spatial
 *                                |Date and time using the _pgettext() format
 * @param string $listType        type of list to build, supported dropdown|checkbox
 *
 * @return HTML for list of columns in form of given list types
 */
function PMA_getHtmlForColumnsList(
    $db, $table, $colTypeCategory='all', $listType='dropdown'
) {
    $columnTypeList = array();
    if ($colTypeCategory != 'all') {
        $types = $GLOBALS['PMA_Types']->getColumns();
        $columnTypeList = $types[$colTypeCategory];
    }
    $GLOBALS['dbi']->selectDb($db, $GLOBALS['userlink']);
    $columns = (array) $GLOBALS['dbi']->getColumns(
        $db, $table, null,
        true, $GLOBALS['userlink']
    );
    $type = "";
    $selectColHtml = "";
    foreach ($columns as $column=>$def) {
        if (isset($def['Type'])) {
            $extracted_columnspec = PMA_Util::extractColumnSpec($def['Type']);
            $type = $extracted_columnspec['type'];
        }
        if (empty($columnTypeList) || in_array(strtoupper($type), $columnTypeList)) {
            if ($listType == 'checkbox') {
                $selectColHtml .= '<input type="checkbox" value="'
                    . htmlspecialchars($column) . '"/>'
                    . htmlspecialchars($column) . ' [ ' . $def['Type'] . ' ]</br>';
            } else {
                $selectColHtml .= '<option value="' . htmlspecialchars($column) . ''
                . '">' . htmlspecialchars($column) . ' [ ' . $def['Type'] . ' ]'
                . '</option>';
            }
        }
    }
    return $selectColHtml;
}

/**
 * get the html of the form to add the new column to given table
 *
 * @param integer $num_fields number of columns to add
 * @param string  $db         current database
 * @param string  $table      current table
 * @param array   $columnMeta array containing default values for the fields
 *
 * @return HTML
 */
function PMA_getHtmlForCreateNewColumn(
    $num_fields, $db, $table, $columnMeta=array()
) {
    $cfgRelation = PMA_getRelationsParam();
    $content_cells = array();
    $available_mime = array();
    $mime_map = array();
    $header_cells = PMA_getHeaderCells(
        true, null,
        $cfgRelation['mimework'], $db, $table
    );
    if ($cfgRelation['mimework'] && $GLOBALS['cfg']['BrowseMIME']) {
        $mime_map = PMA_getMIME($db, $table);
        $available_mime = PMA_getAvailableMIMEtypes();
    }
    $comments_map = PMA_getComments($db, $table);
    for ($columnNumber = 0; $columnNumber < $num_fields; $columnNumber++) {
        $content_cells[$columnNumber] = PMA_getHtmlForColumnAttributes(
            $columnNumber, $columnMeta, '',
            8, '', null, array(), null, null, null,
            $comments_map, null, true,
            array(), $cfgRelation,
            $available_mime, $mime_map
        );
    }
    return PMA_getHtmlForTableFieldDefinitions($header_cells, $content_cells);
}
/**
 * build the html for step 1.1 of normalization
 *
 * @param string $db           current database
 * @param string $table        current table
 * @param string $normalizedTo upto which step normaliztio will go,
 * possible values 1nf|2nf|3nf
 *
 * @return HTML for step 1.1
 */
function PMA_getHtmlFor1NFStep1($db, $table, $normalizedTo)
{
    $step = 1;
    $stepTxt = __('Make all columns atomic');
    $html = "<h3 class='center'>"
        . __('First step of normalization (1NF)') . "</h3>";
    $html .= "<div id='mainContent' data-normalizeto='" . $normalizedTo . "'>" .
        "<fieldset>" .
        "<legend>" . __('Step 1.') . $step . " " . $stepTxt . "</legend>" .
        "<h4>" . __(
            'Do you have any column which can be split into more than'
            . ' one column? '
            . 'For example: address can be split into street, city, country and zip.'
        )
        . "</br>(<a class='central_columns_dialog' data-maxrows='25' "
        . "data-pick=false href='#'> "
        . __(
            'Show me the central list of columns that are not already in this table'
        ) . " </a>)</h4>"
        . "<p class='cm-em'>" . __(
            'Select a column which can be split into more '
            . 'than one. (on select of \'no such column\', it\'ll move to next step)'
        )
        . "</p>"
        . "<div id='extra'>"
        . "<select id='selectNonAtomicCol' name='makeAtomic'>"
        . '<option selected="selected" disabled="disabled">'
        . __('Select one…') . "</option>"
        . "<option value='no_such_col'>" . __('No such column') . "</option>"
        . PMA_getHtmlForColumnsList(
            $db,
            $table,
            _pgettext('string types', 'String')
        )
        . "</select>"
        . "<span>" . __('split into ')
        . "</span><input id='numField' type='number' value='2'>"
        . "<input type='submit' id='splitGo' value='" . __('Go') . "'/></div>"
        . "<div id='newCols'></div>"
        . "</fieldset><fieldset class='tblFooters'>"
        . "</fieldset>"
        . "</div>";
    return $html;
}

/**
 * build the html contents of various html elements in step 1.2
 *
 * @param string $db    current database
 * @param string $table current table
 *
 * @return HTML contents for step 1.2
 */
function PMA_getHtmlContentsFor1NFStep2($db, $table)
{
    $step = 2;
    $stepTxt = __('Have a primary key');
    $primary = PMA_Index::getPrimary($table, $db);
    $hasPrimaryKey = "0";
    $legendText = __('Step 1.') . $step . " " . $stepTxt;
    $extra = '';
    if ($primary) {
        $headText = __("Primary key already exists.");
        $subText = __("Taking you to next step…");
        $hasPrimaryKey = "1";
    } else {
        $headText = __(
            "There is no primary key; please add one.<br/>"
            . "Hint: A primary key is a column "
            . "(or combination of columns) that uniquely identify all rows."
        );
        $subText = '<a href="#" id="createPrimaryKey">'
            . PMA_Util::getIcon(
                'b_index_add.png', __(
                    'Add a primary key on existing column(s)'
                )
            )
            . '</a>';
        $extra = __(
            "If it's not possible to make existing "
            . "column combinations as primary key"
        ) . "<br/>"
            . '<a href="#" id="addNewPrimary">'
            . __('+ Add a new primary key column') . '</a>';
    }
    $res = array('legendText'=>$legendText, 'headText'=>$headText,
        'subText'=>$subText, 'hasPrimaryKey'=>$hasPrimaryKey, 'extra'=>$extra);
    return $res;
}

/**
 * build the html contents of various html elements in step 1.3
 *
 * @param string $db    current database
 * @param string $table current table
 *
 * @return HTML contents for step 1.3
 */
function PMA_getHtmlContentsFor1NFStep3($db, $table)
{
    $step = 3;
    $stepTxt = __('Remove redundant columns');
    $legendText = __('Step 1.') . $step . " " . $stepTxt;
    $headText = __(
        "Do you have a group of columns which on combining gives an existing"
        . " column? For example, if you have first_name, last_name and"
        . " full_name then combining first_name and last_name gives full_name"
        . " which is redundant."
    );
    $subText = __(
        "Check the columns which are redundant and click on remove. "
        . "If no redundant column, click on 'No redundant column'"
    );
    $extra = PMA_getHtmlForColumnsList($db, $table, 'all', "checkbox") . "</br>"
        . '<input type="submit" id="removeRedundant" value="'
        . __('Remove selected') . '"/>'
        . '<input type="submit" value="' . __('No redundant column')
        . '" onclick="goToFinish1NF();"'
        . '/>';
    $res = array(
            'legendText'=>$legendText, 'headText'=>$headText,
            'subText'=>$subText, 'extra'=>$extra
        );
    return $res;
}
/**
 * build html contents for 2NF step 2.1
 *
 * @param string $db    current database
 * @param string $table current table
 *
 * @return HTML contents for 2NF step 2.1
 */
function PMA_getHtmlFor2NFstep1($db, $table)
{
    $legendText = __('Step 2.') . "1 " . __('Find partial dependencies');
    $primary = PMA_Index::getPrimary($table, $db);
    $primarycols = $primary->getColumns();
    $pk = array();
    $subText = '';
    $selectPkForm = "";
    $extra = "";
    foreach ($primarycols as $col) {
        $pk[] = $col->getName();
        $selectPkForm .= '<input type="checkbox" name="pd" value="'
            . htmlspecialchars($col->getName()) . '">'
            . htmlspecialchars($col->getName());
    }
    $key = implode(', ', $pk);
    if (count($primarycols) > 1) {
        $GLOBALS['dbi']->selectDb($db, $GLOBALS['userlink']);
        $columns = (array) $GLOBALS['dbi']->getColumnNames(
            $db, $table, $GLOBALS['userlink']
        );
        if (count($pk) == count($columns)) {
            $headText = sprintf(
                __(
                    'No partial dependencies possible as '
                    . 'no non-primary coulmn exists since primary key ( %1$s ) '
                    . 'is composed of all the columns in the table '
                ), htmlspecialchars($key)
            ) . '<br/>';
            $extra = '<h3>' . __('Table is already in second normal form.')
                . '</h3>';
        } else {
            $headText = sprintf(
                __(
                    'The primary key ( %1$s ) consists of more than one column '
                    . 'so we need to find the partial dependencies.'
                ), htmlspecialchars($key)
            ) . '<br/>' . __(
                'Please read and answer the following question(s) '
                . 'carefully to get correct normalization.'
            )
                . '<br/><a href="#" id="showPossiblePd">' . __(
                    '+ Show me the possible partial dependencies '
                    . 'based on data in the table'
                ) . '</a>';
            $subText = __(
                'For each of the column below, '
                . 'please select the <b>minimal set</b> of columns among given set '
                . 'whose values combined together are sufficient'
                . ' to determine the value of the column.'
            );
            $cnt=0;
            foreach ($columns as $column) {
                if (!in_array($column, $pk)) {
                    $cnt++;
                    $extra .= "<b>'" . htmlspecialchars($column)
                        . "' depends on:</b><br>";
                    $extra .= '<form id="pk_' . $cnt . '" data-colname="'
                        . htmlspecialchars($column) . '" class="smallIndent">'
                        . $selectPkForm . '</form><br/><br/>';
                }
            }
        }
    } else {
        $headText = sprintf(
            __(
                'No partial dependencies possible as the primary key'
                . ' ( %1$s ) has just one column '
            ), htmlspecialchars($key)
        ) . '<br/>';
        $extra = '<h3>' . __('Table is already in second normal form') . '</h3>';
    }
    $res = array(
        'legendText'=>$legendText, 'headText'=>$headText,
        'subText'=>$subText,'extra'=>$extra, 'primary_key'=> $key
    );
    return $res;
}

/**
 * build the html for showing the tables to have in order to put current table in 2NF
 *
 * @param array  $partialDependencies array containing all the dependencies
 * @param string $table               current table
 *
 * @return HTML
 */
function PMA_getHtmlForNewTables2NF($partialDependencies,$table)
{
    $html = '<p><b>' . sprintf(
        __(
            'As per above partial dependencies, in order to put the '
            . 'original table \'%1$s\' into Second normal form we need '
            . 'to create the following tables:'
        ), $table
    ) . '</b></p>';
    $tableName = $table;
    $i=1;
    foreach ($partialDependencies as $key=>$dependents) {
        $html .= '<p><input type="text" name="' . htmlspecialchars($key)
            . '" value="' . htmlspecialchars($tableName) . '"/>'
            . '( <u>' . htmlspecialchars($key) . '</u>'
            .  (count($dependents)>0?', ':'')
            . htmlspecialchars(implode(', ', $dependents)) . ' )';
        $i++;
        $tableName = 'table' . $i;
    }
    return $html;
}

/**
 * create/alter the tables needed for 2NF
 *
 * @param array  $partialDependencies array containing all the partial dependencies
 * @param array  $tablesName          array containing name of new tables
 * @param string $table               current table
 * @param string $db                  current database
 *
 * @return array
 */
function PMA_creatNewTablesFor2NF($partialDependencies, $tablesName, $table, $db)
{
    $dropCols = false;
    $nonPKCols = array();
    $queries = array();
    $error = false;
    $headText = '<h3>' . sprintf(
        __('The second step of normalization is complete for table \'%1$s\'.'),
        htmlspecialchars($table)
    ) . '</h3>';
    if (count((array)$partialDependencies) == 1) {
        return array(
            'legendText'=>__('End of step'), 'headText'=>$headText,
            'queryError'=>$error
        );
    }
    $message = '';
    $GLOBALS['dbi']->selectDb($db, $GLOBALS['userlink']);
    foreach ($partialDependencies as $key=>$dependents) {
        if ($tablesName->$key != $table) {
            $keyBackqoeted = implode(', ', PMA_Util::backquote(explode(', ', $key)));
            $queries[] = 'CREATE TABLE ' . PMA_Util::backquote($tablesName->$key)
                . ' SELECT DISTINCT ' . $keyBackqoeted
                . (count($dependents)>0?', ':'')
                . implode(',', PMA_Util::backquote($dependents))
                . ' FROM ' . PMA_Util::backquote($table) . ';';
            $queries[] = 'ALTER TABLE ' . PMA_Util::backquote($tablesName->$key)
                . ' ADD PRIMARY KEY(' . $keyBackqoeted . ');';
            $nonPKCols = array_merge($nonPKCols, $dependents);
        } else {
            $dropCols = true;
        }
    }

    if ($dropCols) {
        $query = 'ALTER TABLE ' . PMA_Util::backquote($table);
        foreach ($nonPKCols as $col) {
            $query .= ' DROP ' . PMA_Util::backquote($col) . ',';
        }
        $query = trim($query, ', ');
        $query .= ';';
        $queries[] = $query;
    } else {
        $queries[] = 'DROP TABLE ' . PMA_Util::backquote($table);
    }
    foreach ($queries as $query) {
        if (!$GLOBALS['dbi']->tryQuery($query, $GLOBALS['userlink'])) {
            $message = PMA_Message::error(__('Error in processing!'));
            $message->addMessage('<br /><br />');
            $message->addMessage(
                PMA_Message::rawError(
                    $GLOBALS['dbi']->getError($GLOBALS['userlink'])
                )
            );
            $error = true;
            break;
        }
    }
    return array(
        'legendText'=>__('End of step'), 'headText'=>$headText,
        'queryError'=>$error, 'extra'=>$message
    );
}
/**
 * get html for options to normalize table
 *
 * @return HTML
 */
function PMA_getHtmlForNormalizetable()
{
    $html_output = '<form method="post" action="normalization.php" '
        . 'name="normalize" '
        . 'id="normalizeTable" '
        . '>'
        . PMA_URL_getHiddenInputs($GLOBALS['db'], $GLOBALS['table'])
        . '<input type="hidden" name="step1" value="1">';
    $html_output .= '<fieldset>';
    $html_output .= '<legend>'
        . __('Improve table structure (Normalization):') . '</legend>';
    $html_output .= '<h3>' . __('Select up to what step you want to normalize') . '</h3>';
    $choices = array(
            '1nf' => __('First step of normalization (1NF)'),
            '2nf'      => __('Second step of normalization (1NF+2NF)'),
            '3nf'  => __('Third step of normalization (1NF+2NF+3NF)'));

    $html_output .= PMA_Util::getRadioFields(
        'normalizeTo', $choices, '1nf', true
    );
    $html_output .= '</fieldset><fieldset class="tblFooters">'
        . "<span class='floatleft'>" . __(
            'Hint: Please follow the procedure carefully in order '
            . 'to obtain correct normalization'
        ) . "</span>"
        . '<input type="submit" name="submit_normalize" value="' . __('Go') . '" />'
        . '</fieldset>'
        . '</form>'
        . '</div>';

    return $html_output;
}

/**
 * find all the possible partial dependencies based on data in the table.
 *
 * @param string $table current table
 * @param string $db    current database
 *
 * @return HTML containing the list of all the possible partial dependencies
 */
function PMA_findPartialDependencies($table, $db)
{
    $dependencyList = array();
    $GLOBALS['dbi']->selectDb($db, $GLOBALS['userlink']);
    $columns = (array) $GLOBALS['dbi']->getColumnNames(
        $db, $table, $GLOBALS['userlink']
    );
    $columns = (array)PMA_Util::backquote($columns);
    $totalRowsRes = $GLOBALS['dbi']->fetchResult(
        'SELECT COUNT(*) FROM (SELECT * FROM '
        . PMA_Util::backquote($table) . ' LIMIT 500) as dt;'
    );
    $totalRows = $totalRowsRes[0];
    $primary = PMA_Index::getPrimary($table, $db);
    $primarycols = $primary->getColumns();
    $pk = array();
    foreach ($primarycols as $col) {
        $pk[] = PMA_Util::backquote($col->getName());
    }
    $partialKeys = PMA_getAllCombinationPartialKeys($pk);
    $distinctValCount = PMA_findDistinctValuesCount(
        array_unique(
            array_merge($columns, $partialKeys)
        ), $table
    );
    foreach ($columns as $column) {
        if (!in_array($column, $pk)) {
            foreach ($partialKeys as $partialKey) {
                if ($partialKey
                    && PMA_checkPartialDependency(
                        $partialKey, $column, $table,
                        $distinctValCount[$partialKey],
                        $distinctValCount[$column], $totalRows
                    )
                ) {
                    $dependencyList[$partialKey][] = $column;
                }
            }
        }
    }

    $html = __(
        'This list is based on a subset of the table\'s data '
        . 'and is not necessarily accurate. '
    )
        . '<div class="dependencies_box">';
    foreach ($dependencyList as $dependon=>$colList) {
        $html .= '<span class="displayblock">'
            . '<input type="button" class="pickPd" value="' . __('Pick') . '"/>'
            . '<span class="determinants">'
            . htmlspecialchars(str_replace('`', '', $dependon)) . '</span> -> '
            . '<span class="dependents">'
            . htmlspecialchars(str_replace('`', '', implode(', ', $colList)))
            . '</span>'
            . '</span>';
    }
    if (empty($dependencyList)) {
        $html .= '<p class="displayblock desc">'
            . __('No Partial dependencies found!') . '</p>';
    }
    $html .= '</div>';
    return $html;
}
/**
 * check whether a particular column is dependent on given subset of primary key
 *
 * @param string  $partialKey the partial key, subset of primary key,
 * each column in key supposed to be backquoted
 * @param string  $column     backquoted column on whose dependency being checked
 * @param string  $table      current table
 * @param integer $pkCnt      distinct value count for given partial key
 * @param integer $colCnt     distinct value count for given column
 * @param integer $totalRows  total distinct rows count of the table
 *
 * @return boolean TRUE if $column is dependent on $partialKey, False otherwise
 */
function PMA_checkPartialDependency(
    $partialKey, $column, $table, $pkCnt, $colCnt, $totalRows
) {
    $query = 'SELECT '
        . 'COUNT(DISTINCT ' . $partialKey . ',' . $column . ') as pkColCnt '
        . 'FROM (SELECT * FROM ' . PMA_Util::backquote($table)
        . ' LIMIT 500) as dt'  . ';';
    $res = $GLOBALS['dbi']->fetchResult($query, null, null, $GLOBALS['userlink']);
    $pkColCnt = $res[0];
    if ($pkCnt && $pkCnt == $colCnt && $colCnt == $pkColCnt) {
        return true;
    }
    if ($totalRows && $totalRows == $pkCnt) {
        return true;
    }
    return false;
}

/**
 * function to get distinct values count of all the column in the array $columns
 *
 * @param array  $columns array of backquoted columns whose distinct values
 * need to be counted.
 * @param string $table   table to which these columns belong
 *
 * @return array associative array containing the count
 */
function PMA_findDistinctValuesCount($columns, $table)
{
    $result = array();
    $query = 'SELECT ';
    foreach ($columns as $column) {
        if ($column) { //each column is already backquoted
            $query .= 'COUNT(DISTINCT ' .  $column . ') as \''
                . $column . '_cnt\', ';
        }
    }
    $query = trim($query, ', ');
    $query .= ' FROM (SELECT * FROM ' . PMA_Util::backquote($table)
        . ' LIMIT 500) as dt' . ';';
    $res = $GLOBALS['dbi']->fetchResult($query, null, null, $GLOBALS['userlink']);
    foreach ($columns as $column) {
        if ($column) {
            $result[$column] = $res[0][$column . '_cnt'];
        }
    }
    return $result;
}

/**
 * find all the possible partial keys
 *
 * @param array $primaryKey array containing all the column present in primary key
 *
 * @return array containing all the possible partial keys(subset of primary key)
 */
function PMA_getAllCombinationPartialKeys($primaryKey)
{
    $results = array('');
    foreach ($primaryKey as $element) {
        foreach ($results as $combination) {
            array_push(
                $results, trim($element . ',' . $combination, ',')
            );
        }
    }
    array_pop($results); //remove key which consist of all primary key columns
    return $results;
}
