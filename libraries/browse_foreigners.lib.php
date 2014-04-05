<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Contains functions used by browse_foreigners.php
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Function to get html for relational field selection
 *
 * @param string $db          current database
 * @param string $table       current table
 * @param string $field       field
 * @param array  $foreignData foreign column data
 * @param string $fieldkey    field key
 * @param array  $data        data
 *
 * @return string
 */
function PMA_getHtmlForRelationalFieldSelection($db, $table, $field, $foreignData,
    $fieldkey, $data
) {
    $gotopage = PMA_getHtmlForGotoPage($foreignData);
    $showall = PMA_getHtmlForShowAll($foreignData);

    $output = '<form action="browse_foreigners.php" method="post">'
        . '<fieldset>'
        . PMA_URL_getHiddenInputs($db, $table)
        . '<input type="hidden" name="field" value="' . htmlspecialchars($field)
        . '" />'
        . '<input type="hidden" name="fieldkey" value="'
        . (isset($fieldkey) ? htmlspecialchars($fieldkey) : '') . '" />';

    if (isset($_REQUEST['rownumber'])) {
        $output .= '<input type="hidden" name="rownumber" value="'
            . htmlspecialchars($_REQUEST['rownumber']) . '" />';
    }
    $output .= '<span class="formelement">'
        . '<label for="input_foreign_filter">' . __('Search:') . '</label>'
        . '<input type="text" name="foreign_filter" '
        . 'id="input_foreign_filter" value="'
        . (isset($_REQUEST['foreign_filter'])
        ? htmlspecialchars($_REQUEST['foreign_filter'])
        : '')
        . '" />'
        . '<input type="submit" name="submit_foreign_filter" value="'
        .  __('Go') . '" />'
        . '</span>'
        . '<span class="formelement">' . $gotopage . '</span>'
        . '<span class="formelement">' . $showall . '</span>'
        . '</fieldset>'
        . '</form>';

    $output .= '<table width="100%">';

    if (is_array($foreignData['disp_row'])) {
        $header = '<tr>
            <th>' . __('Keyname') . '</th>
            <th>' . __('Description') . '</th>
            <td width="20%"></td>
            <th>' . __('Description') . '</th>
            <th>' . __('Keyname') . '</th>
        </tr>';

        $output .= '<thead>' . $header . '</thead>' . "\n"
            . '<tfoot>' . $header . '</tfoot>' . "\n"
            . '<tbody>' . "\n";

        $values = array();
        $keys   = array();
        foreach ($foreignData['disp_row'] as $relrow) {
            if ($foreignData['foreign_display'] != false) {
                $values[] = $relrow[$foreignData['foreign_display']];
            } else {
                $values[] = '';
            }

            $keys[] = $relrow[$foreignData['foreign_field']];
        }

        asort($keys);

        $hcount = 0;
        $odd_row = true;
        $val_ordered_current_row = 0;
        $val_ordered_current_equals_data = false;
        $key_ordered_current_equals_data = false;
        foreach ($keys as $key_ordered_current_row => $value) {
            $hcount++;

            if ($GLOBALS['cfg']['RepeatCells'] > 0
                && $hcount > $GLOBALS['cfg']['RepeatCells']
            ) {
                $output .= $header;
                $hcount = 0;
                $odd_row = true;
            }

            $key_ordered_current_key = $keys[$key_ordered_current_row];
            $key_ordered_current_val = $values[$key_ordered_current_row];

            $val_ordered_current_key = $keys[$val_ordered_current_row];
            $val_ordered_current_val = $values[$val_ordered_current_row];

            $val_ordered_current_row++;

            $pmaString = $GLOBALS['PMA_String'];
            $limitChars = $GLOBALS['cfg']['LimitChars'];
            if ($pmaString->strlen($val_ordered_current_val) <= $limitChars) {
                $val_ordered_current_val = htmlspecialchars(
                    $val_ordered_current_val
                );
                $val_ordered_current_val_title = '';
            } else {
                $val_ordered_current_val_title = htmlspecialchars(
                    $val_ordered_current_val
                );
                $val_ordered_current_val = htmlspecialchars(
                    $pmaString->substr(
                        $val_ordered_current_val, 0, $limitChars
                    )
                    . '...'
                );
            }
            if ($pmaString->strlen($key_ordered_current_val) <= $limitChars) {
                $key_ordered_current_val = htmlspecialchars(
                    $key_ordered_current_val
                );
                $key_ordered_current_val_title = '';
            } else {
                $key_ordered_current_val_title = htmlspecialchars(
                    $key_ordered_current_val
                );
                $key_ordered_current_val = htmlspecialchars(
                    $pmaString->substr(
                        $key_ordered_current_val, 0, $limitChars
                    ) . '...'
                );
            }

            if (! empty($data)) {
                $val_ordered_current_equals_data
                    = $val_ordered_current_key == $data;
                $key_ordered_current_equals_data
                    = $key_ordered_current_key == $data;
            }

            $output .= '<tr class="noclick ' . ($odd_row ? 'odd' : 'even') . '">';
            $odd_row = ! $odd_row;

            $output .= PMA_getHtmlForColumnElement(
                'class="nowrap"', $key_ordered_current_equals_data,
                $key_ordered_current_key, $key_ordered_current_val,
                $key_ordered_current_val_title, $field
            );

            $output .= PMA_getHtmlForColumnElement(
                '', $key_ordered_current_equals_data, $key_ordered_current_key,
                $key_ordered_current_val, $key_ordered_current_val_title, $field
            );

            $output .= '<td width="20%">'
                . '<img src="' . $GLOBALS['pmaThemeImage'] . 'spacer.png" alt=""'
                . ' width="1" height="1" /></td>';

            $output .= PMA_getHtmlForColumnElement(
                '', $val_ordered_current_equals_data, $key_ordered_current_key,
                $val_ordered_current_val, $val_ordered_current_val_title, $field
            );

            $output .= PMA_getHtmlForColumnElement(
                'class="nowrap"', $val_ordered_current_equals_data,
                $val_ordered_current_key, $val_ordered_current_val,
                $val_ordered_current_val_title, $field
            );
            $output .= '</tr>';
        } // end while
    }
    $output .= '</tbody>'
        . '</table>';

    return $output;
}

/**
 * Function to get html for each column element
 *
 * @param string $cssClass          class="nowrap" or ''
 * @param bool   $currentEqualsData whether current equals data
 * @param string $currentKey        current key
 * @param string $currentVal        current value
 * @param string $currentTitle      current title
 * @param string $field             field
 *
 * @return string
 */
function PMA_getHtmlForColumnElement($cssClass, $currentEqualsData, $currentKey,
    $currentVal, $currentTitle, $field
) {
    $output = '<td ' . $cssClass . '>'
        . ($currentEqualsData ? '<strong>' : '')
        . '<a href="#" title="' . __('Use this value')
        . ($currentTitle != ''
            ? ': ' . $currentTitle
            : '')
        . '" onclick="formupdate(\'' . md5($field) . '\', \''
        . PMA_jsFormat($currentKey, false)
        . '\'); return false;">';
    if ($cssClass !== '') {
        $output .= htmlspecialchars($currentKey);
    } else {
        $output .= $currentVal;
    }

    $output .=  '</a>' . ($currentEqualsData ? '</strong>' : '') . '</td>';

    return $output;
}

/**
 * Function to get javascript code to handle display selection for relational
 * field values
 *
 * @return string
 */
function PMA_getJsScriptToHandleSelectRelationalFields()
{
    $element_name = PMA_getElementName();
    $fieldkey = PMA_getFieldKey();
    $error = PMA_getJsError();
    $code = <<<EOC
self.focus();
function formupdate(fieldmd5, key) {
    var \$inline = window.opener.jQuery('.browse_foreign_clicked');
    if (\$inline.length != 0) {
        \$inline.removeClass('browse_foreign_clicked')
            // for grid editing,
            // puts new value in the previous element which is
            // a span with class curr_value, and trigger .change()
            .prev('.curr_value').text(key).change();
        // for zoom-search editing, puts new value in the previous
        // element which is an input field
        \$inline.prev('input[type=text]').val(key);
        self.close();
        return false;
    }

    if (opener && opener.document && opener.document.insertForm) {
        var field = 'fields';
        var field_null = 'fields_null';

        $element_name

        var element_name_alt = field + '[$fieldkey]';

        if (opener.document.insertForm.elements[element_name]) {
            // Edit/Insert form
            opener.document.insertForm.elements[element_name].value = key;
            if (opener.document.insertForm.elements[null_name]) {
                opener.document.insertForm.elements[null_name].checked = false;
            }
            self.close();
            return false;
        } else if (opener.document.insertForm.elements[element_name_alt]) {
            // Search form
            opener.document.insertForm.elements[element_name_alt].value = key;
            self.close();
            return false;
        }
    }

    alert('$error');
}
EOC;

    return $code;
}

/**
 * Function to get formatted error message for javascript
 *
 * @return string
 */
function PMA_getJsError()
{
    return PMA_jsFormat(
        __(
            'The target browser window could not be updated. '
            . 'Maybe you have closed the parent window, or '
            . 'your browser\'s security settings are '
            . 'configured to block cross-window updates.'
        )
    );
}

/**
 * Function to get the field key
 *
 * @return string
 */
function PMA_getFieldKey()
{
    if (! isset($_REQUEST['fieldkey']) || ! is_numeric($_REQUEST['fieldkey'])) {
        $fieldkey = 0;
    } else {
        $fieldkey = $_REQUEST['fieldkey'];
    }

    return $fieldkey;
}

/**
 * Function to get the element name
 *
 * @return string
 */
function PMA_getElementName()
{
    // When coming from Table/Zoom search
    if (isset($_REQUEST['fromsearch'])) {
        // In table or zoom search, input fields are named "criteriaValues"
        $element_name = " var field = 'criteriaValues';\n";
    } else {
        // In insert/edit, input fields are named "fields"
        $element_name = " var field = 'fields';\n";
    }

    if (isset($_REQUEST['rownumber'])) {
        $element_name  .= "        var element_name = field + '[multi_edit]["
            . htmlspecialchars($_REQUEST['rownumber']) . "][' + fieldmd5 + ']';\n"
            . "        var null_name = field_null + '[multi_edit]["
            . htmlspecialchars($_REQUEST['rownumber']) . "][' + fieldmd5 + ']';\n";
    } else {
        $element_name .= "var element_name = field + '[]'";
    }

    return $element_name;
}

/**
 * Function to get html for show all case
 *
 * @param array $foreignData foreign data
 *
 * @return string
 */
function PMA_getHtmlForShowAll($foreignData)
{
    $showall = '';
    if (is_array($foreignData['disp_row'])) {
        if ($GLOBALS['cfg']['ShowAll']
            && ($foreignData['the_total'] > $GLOBALS['cfg']['MaxRows'])
        ) {
            $showall = '<input type="submit" name="foreign_navig" value="'
                     . __('Show all') . '" />';
        }
    }

    return $showall;
}

/**
 * Function to get html for the goto page option
 *
 * @param array $foreignData foreign data
 *
 * @return string
 */
function PMA_getHtmlForGotoPage($foreignData)
{
    $gotopage = '';
    isset($_REQUEST['pos']) ? $pos = $_REQUEST['pos'] : $pos = 0;
    if (is_array($foreignData['disp_row'])) {
        $session_max_rows = $GLOBALS['cfg']['MaxRows'];
        $pageNow = @floor($pos / $session_max_rows) + 1;
        $nbTotalPage = @ceil($foreignData['the_total'] / $session_max_rows);

        if ($foreignData['the_total'] > $GLOBALS['cfg']['MaxRows']) {
            $gotopage = PMA_Util::pageselector(
                'pos',
                $session_max_rows,
                $pageNow,
                $nbTotalPage,
                200,
                5,
                5,
                20,
                10,
                __('Page number:')
            );
        }
    }
    return $gotopage;
}

/**
 * Function to get foreign limit
 *
 * @param string $foreign_navig foreign navigation
 *
 * @return string
 */
function PMA_getForeignLimit($foreign_navig)
{
    if (isset($foreign_navig) && $foreign_navig == __('Show all')) {
        return null;
    }
    isset($_REQUEST['pos']) ? $pos = $_REQUEST['pos'] : $pos = 0;
    return 'LIMIT ' . $pos . ', ' . $GLOBALS['cfg']['MaxRows'] . ' ';
}
?>
