<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Contains functions used by browse_foreigners.php
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin;

use PhpMyAdmin\Template;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * PhpMyAdmin\BrowseForeigners class
 *
 * @package PhpMyAdmin
 */
class BrowseForeigners
{
    /**
     * Function to get html for one relational key
     *
     * @param integer $horizontal_count   the current horizontal count
     * @param string  $header             table header
     * @param array   $keys               all the keys
     * @param integer $indexByKeyname     index by keyname
     * @param array   $descriptions       descriptions
     * @param integer $indexByDescription index by description
     * @param string  $current_value      current value on the edit form
     *
     * @return string $html the generated html
     */
    public static function getHtmlForOneKey(
        $repeatCells,
        $pmaThemeImage,
        $limitChars,
        $horizontal_count,
        $header,
        array $keys,
        $indexByKeyname,
        array $descriptions,
        $indexByDescription,
        $current_value
    ) {
        $horizontal_count++;
        $output = '';

        // whether the key name corresponds to the selected value in the form
        $rightKeynameIsSelected = false;
        $leftKeynameIsSelected = false;

        if ($repeatCells > 0 && $horizontal_count > $repeatCells) {
            $output .= $header;
            $horizontal_count = 0;
        }

        // key names and descriptions for the left section,
        // sorted by key names
        $leftKeyname = $keys[$indexByKeyname];
        list(
            $leftDescription,
            $leftDescriptionTitle
        ) = self::getDescriptionAndTitle(
            $limitChars,
            $descriptions[$indexByKeyname]
        );

        // key names and descriptions for the right section,
        // sorted by descriptions
        $rightKeyname = $keys[$indexByDescription];
        list(
            $rightDescription,
            $rightDescriptionTitle
        ) = self::getDescriptionAndTitle(
            $limitChars,
            $descriptions[$indexByDescription]
        );

        $indexByDescription++;

        if (! empty($current_value)) {
            $rightKeynameIsSelected = $rightKeyname == $current_value;
            $leftKeynameIsSelected = $leftKeyname == $current_value;
        }

        $output .= '<tr class="noclick">';

        $output .= self::getHtmlForColumnElement(
            true, $leftKeynameIsSelected,
            $leftKeyname, $leftDescription,
            $leftDescriptionTitle
        );

        $output .= self::getHtmlForColumnElement(
            false, $leftKeynameIsSelected, $leftKeyname,
            $leftDescription, $leftDescriptionTitle
        );

        $output .= '<td width="20%">'
            . '<img src="' . $pmaThemeImage . 'spacer.png" alt=""'
            . ' width="1" height="1" /></td>';

        $output .= self::getHtmlForColumnElement(
            false, $rightKeynameIsSelected, $rightKeyname,
            $rightDescription, $rightDescriptionTitle
        );

        $output .= self::getHtmlForColumnElement(
            true, $rightKeynameIsSelected,
            $rightKeyname, $rightDescription,
            $rightDescriptionTitle
        );
        $output .= '</tr>';

        return array($output, $horizontal_count, $indexByDescription);
    }

    /**
     * Function to get html for relational field selection
     *
     * @param string $db            current database
     * @param string $table         current table
     * @param string $field         field
     * @param array  $foreignData   foreign column data
     * @param string $fieldkey      field key
     * @param string $current_value current columns's value
     *
     * @return string
     */
    public static function getHtmlForRelationalFieldSelection(
        $repeatCells,
        $pmaThemeImage,
        $maxRows,
        $showAll,
        $limitChars,
        $db,
        $table,
        $field,
        array $foreignData,
        $fieldkey,
        $current_value
    ) {
        $gotopage = self::getHtmlForGotoPage($maxRows, $foreignData);
        $foreignShowAll = self::getHtmlForShowAll($showAll, $maxRows, $foreignData);

        $output = '<form class="ajax" '
            . 'id="browse_foreign_form" name="browse_foreign_from" '
            . 'action="browse_foreigners.php" method="post">'
            . '<fieldset>'
            . Url::getHiddenInputs($db, $table)
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
            . '<span class="formelement">' . $foreignShowAll . '</span>'
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

        $horizontal_count = 0;
        $indexByDescription = 0;

        foreach ($keys as $indexByKeyname => $value) {
            list(
                $html,
                $horizontal_count,
                $indexByDescription
            ) = self::getHtmlForOneKey(
                $repeatCells,
                $pmaThemeImage,
                $limitChars,
                $horizontal_count,
                $header,
                $keys,
                $indexByKeyname,
                $descriptions,
                $indexByDescription,
                $current_value
            );
            $output .= $html;
        }

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
    public static function getDescriptionAndTitle($limitChars, $description)
    {
        if (mb_strlen($description) <= $limitChars) {
            $description = htmlspecialchars(
                $description
            );
            $descriptionTitle = '';
        } else {
            $descriptionTitle = htmlspecialchars(
                $description
            );
            $description = htmlspecialchars(
                mb_substr(
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
     * @param bool   $nowrap      if true add class="nowrap"
     * @param bool   $isSelected  whether current equals form's value
     * @param string $keyname     current key
     * @param string $description current value
     * @param string $title       current title
     *
     * @return string
     */
    public static function getHtmlForColumnElement(
        $nowrap,
        $isSelected,
        $keyname,
        $description,
        $title
    ) {
        return Template::get('table/browse_foreigners/column_element')->render([
            'keyname' => $keyname,
            'description' => $description,
            'title' => $title,
            'is_selected' => $isSelected,
            'nowrap' => $nowrap,
        ]);
    }

    /**
     * Function to get html for show all case
     *
     * @param array|null $foreignData foreign data
     *
     * @return string
     */
    public static function getHtmlForShowAll($showAll, $maxRows, $foreignData)
    {
        $return = '';
        if (is_array($foreignData['disp_row'])) {
            if ($showAll && ($foreignData['the_total'] > $maxRows)) {
                $return = '<input type="submit" id="foreign_showAll" '
                    . 'name="foreign_showAll" '
                    . 'value="' . __('Show all') . '" />';
            }
        }

        return $return;
    }

    /**
     * Function to get html for the goto page option
     *
     * @param array|null $foreignData foreign data
     *
     * @return string
     */
    public static function getHtmlForGotoPage($maxRows, $foreignData)
    {
        $gotopage = '';
        isset($_REQUEST['pos']) ? $pos = $_REQUEST['pos'] : $pos = 0;
        if (!is_array($foreignData['disp_row'])) {
            return $gotopage;
        }

        $pageNow = @floor($pos / $maxRows) + 1;
        $nbTotalPage = @ceil($foreignData['the_total'] / $maxRows);

        if ($foreignData['the_total'] > $maxRows) {
            $gotopage = Util::pageselector(
                'pos',
                $maxRows,
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
     * @param string $foreignShowAll foreign navigation
     *
     * @return string
     */
    public static function getForeignLimit($maxRows, $foreignShowAll)
    {
        if (isset($foreignShowAll) && $foreignShowAll == __('Show all')) {
            return null;
        }
        isset($_REQUEST['pos']) ? $pos = $_REQUEST['pos'] : $pos = 0;
        return 'LIMIT ' . $pos . ', ' . intval($maxRows) . ' ';
    }
}
