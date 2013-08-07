<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * display selection for relational field values
 *
 * @package PhpMyAdmin
 */

require_once 'libraries/common.inc.php';
require_once 'libraries/transformations.lib.php';

/**
 * Sets globals from $_REQUEST
 */
$request_params = array(
    'field',
    'fieldkey',
    'foreign_filter',
    'pos',
    'rownumber'
);

foreach ($request_params as $one_request_param) {
    if (isset($_REQUEST[$one_request_param])) {
        $GLOBALS[$one_request_param] = $_REQUEST[$one_request_param];
    }
}

PMA_Util::checkParameters(array('db', 'table', 'field'));

$response = PMA_Response::getInstance();
$response->getFooter()->setMinimal();
$header = $response->getHeader();
$header->disableMenu();
$header->setBodyId('body_browse_foreigners');

/**
 * Displays the frame
 */

$cfgRelation = PMA_getRelationsParam();
$foreigners  = ($cfgRelation['relwork'] ? PMA_getForeigners($db, $table) : false);

$override_total = true;

if (! isset($pos)) {
    $pos = 0;
}

$foreign_limit = 'LIMIT ' . $pos . ', ' . $GLOBALS['cfg']['MaxRows'] . ' ';
if (isset($foreign_navig) && $foreign_navig == __('Show all')) {
    unset($foreign_limit);
}

$foreignData = PMA_getForeignData(
    $foreigners, $field, $override_total,
    isset($foreign_filter) ? $foreign_filter : '', $foreign_limit
);

if (isset($rownumber)) {
    $rownumber_param = '&amp;rownumber=' . urlencode($rownumber);
} else {
    $rownumber_param = '';
}

$gotopage = '';
$showall = '';

if (is_array($foreignData['disp_row'])) {

    if ($cfg['ShowAll']
        && ($foreignData['the_total'] > $GLOBALS['cfg']['MaxRows'])
    ) {
        $showall = '<input type="submit" name="foreign_navig" value="'
                 . __('Show all') . '" />';
    }

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

// When coming from Table/Zoom search
if (isset($_REQUEST['fromsearch'])) {
    // In table or zoom search, input fields are named "criteriaValues"
    $element_name = " var field = 'criteriaValues';\n";
} else {
    // In insert/edit, input fields are named "fields"
    $element_name = " var field = 'fields';\n";
}

if (isset($rownumber)) {
    $element_name  .= "        var element_name = field + '[multi_edit]["
        . htmlspecialchars($rownumber) . "][' + fieldmd5 + ']';\n"
        . "        var null_name = field_null + '[multi_edit]["
        . htmlspecialchars($rownumber) . "][' + fieldmd5 + ']';\n";
} else {
    $element_name .= "var element_name = field + '[]'";
}
$error = PMA_jsFormat(
    __(
        'The target browser window could not be updated. '
        . 'Maybe you have closed the parent window, or '
        . 'your browser\'s security settings are '
        . 'configured to block cross-window updates.'
    )
);


if (! isset($fieldkey) || ! is_numeric($fieldkey)) {
    $fieldkey = 0;
}

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

$header->getScripts()->addCode($code);

// HTML output
$output = '<form action="browse_foreigners.php" method="post">'
    . '<fieldset>'
    . PMA_generate_common_hidden_inputs($db, $table)
    . '<input type="hidden" name="field" value="' . htmlspecialchars($field) . '" />'
    . '<input type="hidden" name="fieldkey" value="'
    . (isset($fieldkey) ? htmlspecialchars($fieldkey) : '') . '" />';

if (isset($rownumber)) {
    $output .= '<input type="hidden" name="rownumber" value="'
        . htmlspecialchars($rownumber) . '" />';
}
$output .= '<span class="formelement">'
    . '<label for="input_foreign_filter">' . __('Search') . ':' . '</label>'
    . '<input type="text" name="foreign_filter" id="input_foreign_filter" value="'
    . (isset($foreign_filter) ? htmlspecialchars($foreign_filter) : '') . '" />'
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

        if ($cfg['RepeatCells'] > 0 && $hcount > $cfg['RepeatCells']) {
            $output .= $header;
            $hcount = 0;
            $odd_row = true;
        }

        $key_ordered_current_key = $keys[$key_ordered_current_row];
        $key_ordered_current_val = $values[$key_ordered_current_row];

        $val_ordered_current_key = $keys[$val_ordered_current_row];
        $val_ordered_current_val = $values[$val_ordered_current_row];

        $val_ordered_current_row++;

        if (PMA_strlen($val_ordered_current_val) <= $cfg['LimitChars']) {
            $val_ordered_current_val = htmlspecialchars(
                $val_ordered_current_val
            );
            $val_ordered_current_val_title = '';
        } else {
            $val_ordered_current_val_title = htmlspecialchars(
                $val_ordered_current_val
            );
            $val_ordered_current_val = htmlspecialchars(
                PMA_substr($val_ordered_current_val, 0, $cfg['LimitChars'])
                . '...'
            );
        }
        if (PMA_strlen($key_ordered_current_val) <= $cfg['LimitChars']) {
            $key_ordered_current_val = htmlspecialchars(
                $key_ordered_current_val
            );
            $key_ordered_current_val_title = '';
        } else {
            $key_ordered_current_val_title = htmlspecialchars(
                $key_ordered_current_val
            );
            $key_ordered_current_val = htmlspecialchars(
                PMA_substr(
                    $key_ordered_current_val, 0, $cfg['LimitChars']
                ) . '...'
            );
        }

        if (! empty($data)) {
            $val_ordered_current_equals_data = $val_ordered_current_key == $data;
            $key_ordered_current_equals_data = $key_ordered_current_key == $data;
        }

        $output .= '<tr class="noclick ' . ($odd_row ? 'odd' : 'even') . '">';
        $odd_row = ! $odd_row;

        $output .= '<td class="nowrap">'
            . ($key_ordered_current_equals_data ? '<strong>' : '')
            . '<a href="#" title="' . __('Use this value')
            . ($key_ordered_current_val_title != ''
                ? ': ' . $key_ordered_current_val_title
                : '') . '"'
            . ' onclick="formupdate(\'' . md5($field) . '\', \''
            . PMA_jsFormat($key_ordered_current_key, false) . '\'); return false;">'
            . htmlspecialchars($key_ordered_current_key)
            . '</a>' . ($key_ordered_current_equals_data ? '</strong>' : '')
            . '</td>';

        $output .= '<td>'
            . ($key_ordered_current_equals_data ? '<strong>' : '')
            . '<a href="#" title="' . __('Use this value')
            . ($key_ordered_current_val_title != '' ? ': '
            . $key_ordered_current_val_title : '') . '" onclick="formupdate(\''
            . md5($field) . '\', \''
            . PMA_jsFormat($key_ordered_current_key, false)
            . '\'); return false;">'
            . $key_ordered_current_val . '</a>'
            . ($key_ordered_current_equals_data ? '</strong>' : '')
            . '</td>';

        $output .= '<td width="20%">'
            . '<img src="' . $GLOBALS['pmaThemeImage'] . 'spacer.png" alt=""'
            . ' width="1" height="1" /></td>';

        $output .= '<td>'
            . ($val_ordered_current_equals_data ? '<strong>' : '')
            . '<a href="#" title="' . __('Use this value')
            .  ($val_ordered_current_val_title != '' ? ': '
            . $val_ordered_current_val_title : '') . '" onclick="formupdate(\''
            . md5($field) . '\', \''
            . PMA_jsFormat($val_ordered_current_key, false)
            . '\'); return false;">'
            . $val_ordered_current_val . '</a>'
            . ($val_ordered_current_equals_data ? '</strong>' : '')
            . '</td>';

        $output .= '<td class="nowrap">'
            . ($val_ordered_current_equals_data ? '<strong>' : '')
            . '<a href="#" title="' . __('Use this value')
            . ($val_ordered_current_val_title != ''
                ? ': ' . $val_ordered_current_val_title : '')
            . '" onclick="formupdate(\'' . md5($field) . '\', \''
            . PMA_jsFormat($val_ordered_current_key, false) . '\'); return false;">'
            . htmlspecialchars($val_ordered_current_key)
            . '</a>' . ($val_ordered_current_equals_data ? '</strong>' : '')
            . '</td>';
        $output .= '</tr>';
    } // end while
}

$output .= '</tbody>'
    . '</table>';

$response->addHtml($output);
?>
