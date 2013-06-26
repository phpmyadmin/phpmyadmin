<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functions for the table relation page
 *
 * @package PhpMyAdmin
 */

/**
 * Generate dropdown choices
 *
 * @param string $dropdown_question Message to display
 * @param string $select_name       Name of the <select> field
 * @param array  $choices           Choices for dropdown
 * @param string $selected_value    Selected value
 *
 * @return string The html code for existing value (for selected)
 *
 * @access public
 */
function PMA_generateDropdown(
    $dropdown_question, $select_name, $choices, $selected_value
) {
    $html_output = htmlspecialchars($dropdown_question) . '&nbsp;&nbsp;'
        . '<select name="' . htmlspecialchars($select_name) . '">' . "\n";

    foreach ($choices as $one_value => $one_label) {
        $html_output .= '<option value="' . htmlspecialchars($one_value) . '"';
        if ($selected_value == $one_value) {
            $html_output .= ' selected="selected" ';
        }
        $html_output .= '>' . htmlspecialchars($one_label) . '</option>' . "\n";
    }
    $html_output .= '</select>' . "\n";

    return $html_output;
}

/**
 * Split a string on backquote pairs
 *
 * @param string $text original string
 *
 * @return array containing the elements (and their surrounding backquotes)
 *
 * @access public
 */
function PMA_backquoteSplit($text)
{
    $elements = array();
    $final_pos = strlen($text) - 1;
    $pos = 0;
    while ($pos <= $final_pos) {
        $first_backquote = strpos($text, '`', $pos);
        $second_backquote = strpos($text, '`', $first_backquote + 1);
        // after the second one, there might be another one which means
        // this is an escaped backquote
        if ($second_backquote < $final_pos && '`' == $text[$second_backquote + 1]) {
            $second_backquote = strpos($text, '`', $second_backquote + 2);
        }
        if (false === $first_backquote || false === $second_backquote) {
            break;
        }
        $elements[] = substr(
            $text, $first_backquote, $second_backquote - $first_backquote + 1
        );
        $pos = $second_backquote + 1;
    }
    return($elements);
}

/**
 * Returns the DROP query for a foreign key constraint
 *
 * @param string $table table of the foreign key
 * @param string $fk    foreign key name
 *
 * @return string DROP query for the foreign key constraint
 */
function PMA_getSQLToDropForeignKey($table, $fk)
{
    return 'ALTER TABLE ' . PMA_Util::backquote($table)
        . ' DROP FOREIGN KEY ' . PMA_Util::backquote($fk) . ';';
}

/**
 * Returns the SQL query for foreign key constraint creation
 *
 * @param string $table        table name
 * @param string $field        field name
 * @param string $foreignDb    foreign database name
 * @param string $foreignTable foreign table name
 * @param string $foreignField foreign field name
 * @param string $name         name of the constraint
 * @param string $onDelete     on delete action
 * @param string $onUpdate     on update action
 *
 * @return string SQL query for foreign key constraint creation
 */
function PMA_getSQLToCreateForeignKey($table, $field, $foreignDb, $foreignTable,
    $foreignField, $name = null, $onDelete = null, $onUpdate = null
) {
    $sql_query  = 'ALTER TABLE ' . PMA_Util::backquote($table) . ' ADD ';
    // if user entered a constraint name
    if (! empty($name)) {
        $sql_query .= ' CONSTRAINT ' . PMA_Util::backquote($name);
    }

    $sql_query .= ' FOREIGN KEY (' . PMA_Util::backquote($field) . ')'
        . ' REFERENCES ' . PMA_Util::backquote($foreignDb)
        . '.' . PMA_Util::backquote($foreignTable)
        . '(' . PMA_Util::backquote($foreignField) . ')';

    if (! empty($onDelete)) {
        $sql_query .= ' ON DELETE ' . $onDelete;
    }
    if (! empty($onUpdate)) {
        $sql_query .= ' ON UPDATE ' . $onUpdate;
    }
    $sql_query .= ';';

    return $sql_query;
}

/**
 * Creates and populates dropdowns to select foreign db/table/column
 *
 * @param string $name    name of the dropdowns
 * @param array  $values  dropdown values
 * @param string $foreign value of the item to be selected
 * @param string $title   title to show on hovering the dropdown 
 *
 * @return string HTML for the dropdown
 */
function PMA_generateRelationalDropdown(
    $name, $values = array(), $foreign = false, $title = ''
) {
    $html_output = '<select name="' . $name . '" title="' . $title . '">';
    $html_output .= '<option value=""></option>';

    $seen_key = false;
    foreach ($values as $value) {
        $html_output .= '<option value="' . htmlspecialchars($value) . '"';
        if ($foreign && $value == $foreign) {
            $html_output .= ' selected="selected"';
            $seen_key = true;
        }
        $html_output .= '>' . htmlspecialchars($value) . '</option>';
    }

    if ($foreign && ! $seen_key) {
        $html_output .= '<option value="' . htmlspecialchars($foreign) . '"'
            . ' selected="selected">' . htmlspecialchars($foreign) . '</option>';
    }
    $html_output .= '</select>';
    return $html_output;
}
?>
