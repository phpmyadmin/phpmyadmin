<?php
/**
 * Form templates
 *
 * @package    phpMyAdmin-setup
 * @author     Piotr Przybylski <piotrprz@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl.html GNU GPL 2.0
 * @version    $Id$
 */

/**
 * Displays top part of the form
 *
 * @param string $action         default: $_SERVER['REQUEST_URI']
 * @param string $method         'post' or 'get'
 * @param array  $hidden_fields  array of form hidden fields (key: field name)
 */
function display_form_top($action = null, $method = 'post', $hidden_fields = null)
{
    static $has_check_page_refresh = false;

    if ($action === null) {
        $action = $_SERVER['REQUEST_URI'];
    }
    if ($method != 'post') {
        $method = 'get';
    }
?>
<form method="<?php echo $method ?>" action="<?php echo htmlspecialchars($action) ?>">
<?php
    // we do validation on page refresh when browser remembers field values,
    // add a field with known value which will be used for checks
    if (!$has_check_page_refresh) {
        $has_check_page_refresh = true;
        echo '<input type="hidden" name="check_page_refresh" id="check_page_refresh"'
            . ' value="" />' . "\n";
    }
    echo PMA_generate_common_hidden_inputs() . "\n";
    echo PMA_getHiddenFields((array)$hidden_fields);
}

/**
 * Displays form tabs which are given by an array indexed by fieldset id
 * ({@link display_fieldset_top}), with values being tab titles.
 *
 * @param array $tabs
 */
function display_tabs_top($tabs) {
?>
<ul class="tabs">
<?php foreach ($tabs as $tab_id => $tab_name): ?>
    <li><a href="#<?php echo $tab_id ?>"><?php echo $tab_name ?></a></li>
<?php endforeach; ?>
</ul>
<br clear="right" />
<div class="tabs_contents">
<?php
}


/**
 * Displays top part of a fieldset
 *
 * @param string $title
 * @param string $description
 * @param array  $errors
 * @param array  $attributes
 */
function display_fieldset_top($title = '', $description = '', $errors = null, $attributes = array())
{
    $attributes = array_merge(array('class' => 'optbox'), $attributes);
    foreach ($attributes as $k => &$attr) {
        $attr = $k . '="' . htmlspecialchars($attr) . '"';
    }

    echo '<fieldset ' . implode(' ', $attributes) . '>';
    echo '<legend>' . $title . '</legend>';
    if (!empty($description)) {
        echo '<p>' . $description . '</p>';
    }
    // this must match with displayErrors() in scripts.js
    if (is_array($errors) && count($errors) > 0) {
        echo '<dl class="errors">';
        foreach ($errors as $error) {
            echo '<dd>' . $error . '</dd>';
        }
        echo '</dl>';
    }
?>
<table width="100%" cellspacing="0">
<?php
}

/**
 * Displays input field
 *
 * $opts keys:
 * o doc - (string) documentation link
 * o errors - error array
 * o setvalue - (string) shows button allowing to set poredefined value
 * o show_restore_default - (boolean) whether show "restore default" button
 * o values - key - value paris for <select> fields
 * o values_escaped - (boolean) tells whether values array is already escaped (defaults to false)
 * o values_disabled -  (array)list of disabled values (keys from values)
 * o wiki - (string) wiki link
 *
 * @param string $path
 * @param string $name
 * @param string $description
 * @param string $type
 * @param mixed  $value
 * @param bool   $value_is_default
 * @param array  $opts
 */
function display_input($path, $name, $description = '', $type, $value, $value_is_default = true, $opts = null)
{
    $field_class = $value_is_default ? '' : ' class="custom"';
    $name_id = 'name="' . $path . '" id="' . $path . '"';
?>
<tr>
    <th>
        <label for="<?php echo htmlspecialchars($path) ?>"><?php echo $name ?></label>
        <?php if (!empty($opts['doc']) || !empty($opts['wiki'])): ?>
        <span class="doc">
            <?php if (!empty($opts['doc'])) { ?><a href="<?php echo $opts['doc']  ?>" target="documentation"><img class="icon" src="../<?php echo $GLOBALS['cfg']['ThemePath'] ?>/original/img/b_help.png" width="11" height="11" alt="Doc" title="<?php echo $GLOBALS['strDocu'] ?>" /></a><?php } ?>
            <?php if (!empty($opts['wiki'])){ ?><a href="<?php echo $opts['wiki'] ?>" target="wiki"><img class="icon" src="../<?php echo $GLOBALS['cfg']['ThemePath'] ?>/original/img/b_info.png" width="11" height="11" alt="Wiki" title="Wiki" /></a><?php } ?>
        </span>
        <?php endif; ?>
        <?php if (!empty($description)) { ?><small><?php echo $description ?></small><?php } ?>

    </th>
    <td>
    <?php
    switch ($type) {
        case 'text':
            echo '<input type="text" size="50" ' . $name_id . $field_class
                . ' value="' . htmlspecialchars($value) . '" />';
          break;
        case 'checkbox':
            echo '<span class="checkbox' . ($value_is_default ? '' : ' custom')
              . '"><input type="checkbox" ' . $name_id
              . ($value ? ' checked="checked"' : '') . ' /></span>';
          break;
        case 'select':
            echo '<select ' . $name_id . $field_class . '>';
            $escape = !(isset($opts['values_escaped']) && $opts['values_escaped']);
            $values_disabled = isset($opts['values_disabled'])
                ? array_flip($opts['values_disabled']) : array();
            foreach ($opts['values'] as $opt_value => $opt_name) {
                // set names for boolean values
                if (is_bool($opt_name)) {
                    $opt_name = $GLOBALS['strSetup' . ($opt_value ? 'True' : 'False')];
                }
                // cast boolean values to integers
                $display_value = is_bool($opt_value) ? (int) $opt_value : $opt_value;
                // escape if necessary
                if ($escape) {
                    $display = htmlspecialchars($opt_name);
                    $display_value = htmlspecialchars($display_value);
                } else {
                    $display = $opt_name;
                }
                // compare with selected value
                // boolean values are cast to integers when used as array keys
                $selected = is_bool($value)
                    ? (int) $value === $opt_value
                    : $opt_value === $value;
                echo '<option value="' . $display_value . '"'
                    . ($selected ? ' selected="selected"' : '')
                    . (isset($values_disabled[$opt_value]) ? ' disabled="disabled"' : '')
                    . '>' . $display . '</option>';
            }
            echo '</select>';
            break;
        case 'list':
            echo '<textarea cols="40" rows="5" ' . $name_id . $field_class . '>'
                . htmlspecialchars(implode("\n", $value))
                . '</textarea>';
            break;
    }
    if (isset($opts['setvalue']) && $opts['setvalue']) {
        ?>
        <a class="set-value" href="#<?php echo "$path={$opts['setvalue']}" ?>" title="<?php echo sprintf($GLOBALS['strSetupSetValue'], htmlspecialchars($opts['setvalue'])) ?>" style="display:none"><img alt="set-value" src="../<?php echo $GLOBALS['cfg']['ThemePath'] ?>/original/img/b_edit.png" width="16" height="16" /></a>
        <?php
    }
    if (isset($opts['show_restore_default']) && $opts['show_restore_default']) {
        ?>
        <a class="restore-default" href="#<?php echo $path ?>" title="<?php echo $GLOBALS['strSetupRestoreDefaultValue'] ?>" style="display:none"><img alt="restore-default" src="../<?php echo $GLOBALS['cfg']['ThemePath'] ?>/original/img/s_reload.png" width="16" height="16" /></a>
        <?php
    }
    // this must match with displayErrors() in scripts.js
    if (isset($opts['errors']) && !empty($opts['errors'])) {
        echo "\n        <dl class=\"inline_errors\">";
        foreach ($opts['errors'] as $error) {
            echo '<dd>' . htmlspecialchars($error) . '</dd>';
        }
        echo '</dl>';
    }
    ?>

    </td>
</tr>
<?php
}

/**
 * Displays bottom part of a fieldset
 *
 * @param array $js_array
 */
function display_fieldset_bottom()
{
?>
<tr>
    <td colspan="2" class="lastrow">
        <input type="submit" name="submit_save" value="<?php echo $GLOBALS['strSave'] ?>" class="green" />
        <input type="button" name="submit_reset" value="<?php echo $GLOBALS['strReset'] ?>" />
    </td>
</tr>
</table>
</fieldset>

<?php
}

/**
 * Displays simple bottom part of a fieldset (without submit buttons)
 */
function display_fieldset_bottom_simple()
{
?>
</table>
</fieldset>

<?php
}

/**
 * Closes form tabs
 */
function display_tabs_bottom() {
    echo "</div>\n";
}

/**
 * Displays bottom part of the form
 */
function display_form_bottom()
{
    echo "</form>\n";
}

/**
 * Appends JS validation code to $js_array
 *
 * @param string $field_id
 * @param string $validator
 * @param array  $js_array
 */
function js_validate($field_id, $validator, &$js_array) {
    $js_array[] = "validateField('$field_id', '$validator', true)";
}

/**
 * Displays JavaScript code
 *
 * @param array $js_array
 */
function display_js($js_array) {
    if (empty($js_array)) {
        return;
    }
?>
<script type="text/javascript">
<?php echo implode(";\n", $js_array) . ";\n" ?>
</script>
<?php
}

/**
 * Displays error list
 *
 * @param string $name
 * @param array  $error_list
 */
function display_errors($name, $error_list) {
    echo '<dl>';
    echo '<dt>' . htmlspecialchars($name) . '</dt>';
    foreach ($error_list as $error) {
        echo '<dd>' . htmlspecialchars($error) . '</dd>';
    }
    echo '</dl>';
}
?>