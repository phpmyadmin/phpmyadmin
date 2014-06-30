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
 * build the html for columns of $colTypeCategory catgory
 * in form of given $listType in a table
 *
 * @param string $db              current databse
 * @param string $table           current table
 * @param string $colTypeCategory supported all|numeric|string|spatial|date and time
 * @param string $listType        type of list to buld, supported dropdown|checkbox
 *
 * @return HTML for list of columns in form of given list types
 */
function PMA_getHtmlForColumnsList(
    $db, $table, $colTypeCategory='all', $listType='dropdown'
) {
    $columnTypeList = array();
    if ($colTypeCategory != 'all') {
        $types = $GLOBALS['PMA_Types']->getColumns();
        $columnTypeList = $types[ucfirst($colTypeCategory)];
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
 * function to check if any unique column or group of columns exist or not
 *
 * @param string $db    current database
 * @param string $table current table
 *
 * @return "1" if the unique columns exist, otherwise "0"
 */
function PMA_checkUniqueColumn($db, $table)
{
    $query = "SELECT EXISTS(
                SELECT 1
                FROM information_schema.columns
                WHERE table_schema = '" . $db . "'
                and table_name='" . $table . "'
                and column_key = 'PRI'
            ) As HasUniqueKey";
    $result = $GLOBALS['dbi']->fetchResult($query, null, null, $GLOBALS['userlink']);
    return $result[0];
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
 * buld the html for step 1.1 of normalization
 *
 * @param string $db    current database
 * @param string $table current table
 *
 * @return HTML for step 1.1
 */
function PMA_getHtmlFor1NFStep1($db, $table)
{
    $step = 1;
    $stepTxt = __('Make all columns atomic');
    $html = "<h3 style='text-align:center'>"
        . __('First step of normalization (1NF)') . "</h3>";
    $html .= "<div id='mainContent'>" .
        "<fieldset>" .
        "<legend>" . __('Step 1.') . $step . " " . $stepTxt . "</legend>" .
        "<h4>" . __(
            'Do you have any column which can be split into more than'
            . ' one column, '
            . 'For example: address can be split into street, city, country and zip.'
        )
        . "</br>(<a class='central_columns_dialog' data-maxrows='25' "
        . "data-pick=false href='#'> "
        . __('Show me the central list of columns that are not already in this table') . " </a>)</h4>"
        . "<p style='font-style:italic'>" . __(
            'Select a column which can be split into more '
            . 'than one. (on select of \'no such column\', it\'ll move to next step)'
        )
        . "</p>"
        . "<div id='extra'>"
        . "<select id='selectNonAtomicCol' name='makeAtomic'>"
        . "<option selected disabled>" . __('Select one ...') . "</option>" .
        "<option value='no_such_col'>" . __('No such column') . "</option>" .
        PMA_getHtmlForColumnsList($db, $table, 'string') .
        "</select>"
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
    $stepTxt = __('Have unique columns');
    $hasPrimaryKey = PMA_checkUniqueColumn($db, $table);
    $legendText = __('Step 1.') . $step . " " . $stepTxt;
    $extra = '';
    if ($hasPrimaryKey) {
        $headText = __("Unique column(s) already exist");
        $subText = __("Taking you to next step ...");
    } else {
        $headText = __(
            "There are no unique columns. Add an unique column "
            . "(or combination of columns) that uniquely identify all rows. "
        );
        $subText = '<a href="#" id="createUniqueColumns">'
            . PMA_Util::getIcon(
                'b_index_add.png', __(
                    'Add unique/primary index on existing column(s)'
                )
            )
            . '</a>';
        $extra = __(
            "If it's not possible to make existing "
            . "column combinations as unique then"
        ) . "<br/>"
            . '<a href="#" id="addNewPrimary">'
            . __('+ Add a new unique column (primary key)') . '</a>';
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
        "Do you have group of columns which on combining gives an existing
        column. for ex. if have first_name, last_name and full_name then
        combining first_name and last_name gives full_name which is redundant"
    );
    $subText = __(
        "Check the columns which are redundant and click on remove. "
        . "If no redundant column, click on 'No redundant column'"
    );
    $extra = PMA_getHtmlForColumnsList($db, $table, 'all', "checkbox") . "</br>"
        . '<input type="submit" id="removeRedundant" value="'
        . __('Remove selected') . '"/>'
        . '<input type="submit" value="' . __('No redundant column')
        . '" onclick="goToFinish();"'
        . '/>';
    $res = array(
            'legendText'=>$legendText, 'headText'=>$headText,
            'subText'=>$subText, 'extra'=>$extra
        );
    return $res;
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
        . ' class="ajax" >'
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
        . "<span style='float:left'>" . __(
            'Hint: Please follow the procedure carefully in order '
            . 'to obtain correct normalizarion'
        ) . "</span>"
        . '<input type="submit" name="submit_normalize" value="' . __('Go') . '" />'
        . '</fieldset>'
        . '</form>'
        . '</div>';

    return $html_output;
}