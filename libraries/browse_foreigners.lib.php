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

    $output = '<form class="ajax" '
        . 'id="browse_foreign_form" name="browse_foreign_from" '
        . 'action="browse_foreigners.php" method="post">'
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
    $filter_value = (isset($_REQUEST['foreign_filter'])
        ? htmlspecialchars($_REQUEST['foreign_filter'])
        : '');
    $output .= '<span class="formelement">'
        . '<label for="input_foreign_filter">' . __('Search:') . '</label>'
        . '<input type="text" name="foreign_filter" '
        . 'id="input_foreign_filter" '
        . 'value="' . $filter_value . '" data-old="' . $filter_value . '" '
        . '/>'
        . '<input type="submit" name="submit_foreign_filter" value="'
        .  __('Go') . '" />'
        . '</span>'
        . '<span class="formelement">' . $gotopage . '</span>'
        . '<span class="formelement">' . $showall . '</span>'
        . '</fieldset>'
        . '</form>';

    $output .= '<table width="100%" id="browse_foreign_table">';

    if (!is_array($foreignData['disp_row'])) {
        $output .= '</tbody>'
            . '</table>';

        return $output;
    }

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

    $descriptions = array();
    $keys   = array();
    foreach ($foreignData['disp_row'] as $relrow) {
        if ($foreignData['foreign_display'] != false) {
            $descriptions[] = $relrow[$foreignData['foreign_display']];
        } else {
            $descriptions[] = '';
        }

        $keys[] = $relrow[$foreignData['foreign_field']];
    }

    asort($keys);

    $hcount = 0;
    $odd_row = true;
    $indexByDescription = 0;

    // whether the key name corresponds to the selected value in the form
    $rightKeynameIsSelected = false;
    $leftKeynameIsSelected = false;

    foreach ($keys as $indexByKeyname => $value) {
        $hcount++;

        if ($GLOBALS['cfg']['RepeatCells'] > 0
            && $hcount > $GLOBALS['cfg']['RepeatCells']
        ) {
            $output .= $header;
            $hcount = 0;
            $odd_row = true;
        }

        // key names and descriptions for the left section,
        // sorted by key names
        $leftKeyname = $keys[$indexByKeyname];
        list(
            $leftDescription,
            $leftDescriptionTitle
        ) = PMA_getDescriptionAndTitle($descriptions[$indexByKeyname]);

        // key names and descriptions for the right section,
        // sorted by descriptions
        $rightKeyname = $keys[$indexByDescription];
        list(
            $rightDescription,
            $rightDescriptionTitle
        ) = PMA_getDescriptionAndTitle($descriptions[$indexByDescription]);

        $indexByDescription++;

        if (! empty($data)) {
            $rightKeynameIsSelected = $rightKeyname == $data;
            $leftKeynameIsSelected = $leftKeyname == $data;
        }

        $output .= '<tr class="noclick ' . ($odd_row ? 'odd' : 'even') . '">';
        $odd_row = ! $odd_row;

        $output .= PMA_getHtmlForColumnElement(
            'class="nowrap"', $leftKeynameIsSelected,
            $leftKeyname, $leftDescription,
            $leftDescriptionTitle
        );

        $output .= PMA_getHtmlForColumnElement(
            '', $leftKeynameIsSelected, $leftKeyname,
            $leftDescription, $leftDescriptionTitle
        );

        $output .= '<td width="20%">'
            . '<img src="' . $GLOBALS['pmaThemeImage'] . 'spacer.png" alt=""'
            . ' width="1" height="1" /></td>';

        $output .= PMA_getHtmlForColumnElement(
            '', $rightKeynameIsSelected, $rightKeyname,
            $rightDescription, $rightDescriptionTitle
        );

        $output .= PMA_getHtmlForColumnElement(
            'class="nowrap"', $rightKeynameIsSelected,
            $rightKeyname, $rightDescription,
            $rightDescriptionTitle
        );
        $output .= '</tr>';
    } // end while
    $output .= '</tbody>'
        . '</table>';

    return $output;
}

/**
 * Get the description (possibly truncated) and the title
 *
 * @param string $description the key name's description
 *
 * @return array the new description and title
 */
function PMA_getDescriptionAndTitle($description)
{
    $limitChars = $GLOBALS['cfg']['LimitChars'];
    if (/*overload*/mb_strlen($description) <= $limitChars) {
        $description = htmlspecialchars(
            $description
        );
        $descriptionTitle = '';
    } else {
        $descriptionTitle = htmlspecialchars(
            $description
        );
        $description = htmlspecialchars(
            /*overload*/mb_substr(
                $description, 0, $limitChars
            )
            . '...'
        );
    }
    return array($description, $descriptionTitle);
}

/**
 * Function to get html for each column element
 *
 * @param string $cssClass    class="nowrap" or ''
 * @param bool   $isSelected  whether current equals form's value
 * @param string $keyname     current key
 * @param string $description current value
 * @param string $title       current title
 *
 * @return string
 */
function PMA_getHtmlForColumnElement($cssClass, $isSelected, $keyname,
    $description, $title
) {
    $keyname = htmlspecialchars($keyname);
    $output = '<td';
    if (! empty($cssClass)) {
        $output .= ' ' . $cssClass;
    }
    $output .= '>'
        . ($isSelected ? '<strong>' : '')
        . '<a class="foreign_value" data-key="' . $keyname . '" '
        . 'href="#" title="' . __('Use this value')
        . ($title != ''
            ? ': ' . $title
            : '')
        . '">';
    if ($cssClass !== '') {
        $output .= $keyname;
    } else {
        $output .= $description;
    }

    $output .=  '</a>' . ($isSelected ? '</strong>' : '') . '</td>';

    return $output;
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
            $showall = '<input type="submit" id="foreign_showAll" '
                . 'name="foreign_showAll" '
                . 'value="' . __('Show all') . '" />';
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
    if (!is_array($foreignData['disp_row'])) {
        return $gotopage;
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

    return $gotopage;
}

/**
 * Function to get foreign limit
 *
 * @param string $foreign_showAll foreign navigation
 *
 * @return string
 */
function PMA_getForeignLimit($foreign_showAll)
{
    if (isset($foreign_showAll) && $foreign_showAll == __('Show all')) {
        return null;
    }
    isset($_REQUEST['pos']) ? $pos = $_REQUEST['pos'] : $pos = 0;
    return 'LIMIT ' . $pos . ', ' . $GLOBALS['cfg']['MaxRows'] . ' ';
}
?>
