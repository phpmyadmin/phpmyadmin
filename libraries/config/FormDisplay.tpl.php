<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Form templates
 *
 * @package PhpMyAdmin
 */

/**
 * Displays top part of the form
 *
 * @param string $action        default: $_SERVER['REQUEST_URI']
 * @param string $method        'post' or 'get'
 * @param array  $hidden_fields array of form hidden fields (key: field name)
 *
 * @return void
 */
function PMA_displayFormTop($action = null, $method = 'post', $hidden_fields = null)
{
    static $has_check_page_refresh = false;

    if ($action === null) {
        $action = $_SERVER['REQUEST_URI'];
    }
    if ($method != 'post') {
        $method = 'get';
    }
    echo '<form method="' . $method . '" action="'
        . htmlspecialchars($action) . '" class="config-form disableAjax">';
    echo '<input type="hidden" name="tab_hash" value="" />';
    // we do validation on page refresh when browser remembers field values,
    // add a field with known value which will be used for checks
    if (!$has_check_page_refresh) {
        $has_check_page_refresh = true;
        echo '<input type="hidden" name="check_page_refresh" '
            . ' id="check_page_refresh" value="" />' . "\n";
    }
    echo PMA_generate_common_hidden_inputs('', '', 0, 'server') . "\n";
    echo PMA_getHiddenFields((array)$hidden_fields);
}

/**
 * Displays form tabs which are given by an array indexed by fieldset id
 * ({@link PMA_displayFieldsetTop}), with values being tab titles.
 *
 * @param array $tabs tab names
 *
 * @return void
 */
function PMA_displayTabsTop($tabs)
{
    echo '<ul class="tabs">';
    foreach ($tabs as $tab_id => $tab_name) {
        echo '<li><a href="#' . $tab_id . '">'
            . htmlspecialchars($tab_name) . '</a></li>';
    }
    echo '</ul>';
    echo '<br clear="right" />';
    echo '<div class="tabs_contents">';
}


/**
 * Displays top part of a fieldset
 *
 * @param string $title       title of fieldset
 * @param string $description description shown on top of fieldset
 * @param array  $errors      error messages to display
 * @param array  $attributes  optional extra attributes of fieldset
 *
 * @return void
 */
function PMA_displayFieldsetTop($title = '', $description = '', $errors = null,
    $attributes = array()
) {
    global $_FormDisplayGroup;

    $_FormDisplayGroup = 0;

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
    echo '<table width="100%" cellspacing="0">';
}

/**
 * Displays input field
 *
 * $opts keys:
 * o doc - (string) documentation link
 * o errors - error array
 * o setvalue - (string) shows button allowing to set poredefined value
 * o show_restore_default - (boolean) whether show "restore default" button
 * o userprefs_allow - whether user preferences are enabled for this field
 *                    (null - no support, true/false - enabled/disabled)
 * o userprefs_comment - (string) field comment
 * o values - key - value paris for <select> fields
 * o values_escaped - (boolean) tells whether values array is already escaped
 *                    (defaults to false)
 * o values_disabled -  (array)list of disabled values (keys from values)
 * o comment - (string) tooltip comment
 * o comment_warning - (bool) whether this comments warns about something
 * o wiki - (string) wiki link
 *
 * @param string $path             config option path
 * @param string $name             config option name
 * @param string $type             type of config option
 * @param mixed  $value            current value
 * @param string $description      verbose description
 * @param bool   $value_is_default whether value is default
 * @param array  $opts             see above description
 *
 * @return void
 */
function PMA_displayInput($path, $name, $type, $value, $description = '',
    $value_is_default = true, $opts = null
) {
    global $_FormDisplayGroup;
    static $icons;    // An array of IMG tags used further below in the function

    $is_setup_script = defined('PMA_SETUP');
    if ($icons === null) { // if the static variables have not been initialised
        $icons = array();
        // Icon definitions:
        // The same indexes will be used in the $icons array.
        // The first element contains the filename and the second
        // element is used for the "alt" and "title" attributes.
        $icon_init = array(
            'edit'   => array('b_edit.png',   ''),
            'help'   => array('b_help.png',   __('Documentation')),
            'info'   => array('b_info.png',   __('Wiki')),
            'reload' => array('s_reload.png', ''),
            'tblops' => array('b_tblops.png', '')
        );
        if ($is_setup_script) {
            // When called from the setup script, we don't have access to the
            // sprite-aware getImage() function because the PMA_theme class
            // has not been loaded, so we generate the img tags manually.
            foreach ($icon_init as $k => $v) {
                $title = '';
                if (! empty($v[1])) {
                    $title = ' title="' . $v[1] . '"';
                }
                $icons[$k] = sprintf(
                    '<img alt="%s" src="%s"%s />',
                    $v[1],
                    ".{$GLOBALS['cfg']['ThemePath']}/original/img/{$v[0]}",
                    $title
                );
            }
        } else {
            // In this case we just use getImage() because it's available
            foreach ($icon_init as $k => $v) {
                $icons[$k] = PMA_Util::getImage(
                    $v[0], $v[1]
                );
            }
        }
    }
    $has_errors = isset($opts['errors']) && !empty($opts['errors']);
    $option_is_disabled = ! $is_setup_script && isset($opts['userprefs_allow'])
        && ! $opts['userprefs_allow'];
    $name_id = 'name="' . htmlspecialchars($path) . '" id="'
        . htmlspecialchars($path) . '"';
    $field_class = $type == 'checkbox' ? 'checkbox' : '';
    if (! $value_is_default) {
        $field_class .= ($field_class == '' ? '' : ' ')
            . ($has_errors ? 'custom field-error' : 'custom');
    }
    $field_class = $field_class ? ' class="' . $field_class . '"' : '';
    $tr_class = $_FormDisplayGroup > 0
        ? 'group-field group-field-' . $_FormDisplayGroup
        : '';
    if (isset($opts['setvalue']) && $opts['setvalue'] == ':group') {
        unset($opts['setvalue']);
        $_FormDisplayGroup++;
        $tr_class = 'group-header-field group-header-' . $_FormDisplayGroup;
    }
    if ($option_is_disabled) {
        $tr_class .= ($tr_class ? ' ' : '') . 'disabled-field';
    }
    $tr_class = $tr_class ? ' class="' . $tr_class . '"' : '';

    echo '<tr' . $tr_class . '>';
    echo '<th>';
    echo '<label for="' . htmlspecialchars($path) . '">' . $name . '</label>';

    if (! empty($opts['doc']) || ! empty($opts['wiki'])) {
        echo '<span class="doc">';
        if (! empty($opts['doc'])) {
            echo '<a href="' . $opts['doc']
                . '" target="documentation">' . $icons['help'] . '</a>';
            echo "\n";
        }
        if (! empty($opts['wiki'])) {
            echo '<a href="' . $opts['wiki']
                . '" target="wiki">' . $icons['info'] . '</a>';
            echo "\n";
        }
        echo '</span>';
    }

    if ($option_is_disabled) {
        echo '<span class="disabled-notice" title="';
        echo __(
            'This setting is disabled, it will not be applied to your configuration'
        );
        echo '">' . __('Disabled') . "</span>";
    }

    if (!empty($description)) {
        echo '<small>' . $description . '</small>';
    }

    echo '</th>';
    echo '<td>';

    switch ($type) {
    case 'text':
        echo '<input type="text" size="60" ' . $name_id . $field_class
            . ' value="' . htmlspecialchars($value) . '" />';
        break;
    case 'short_text':
        echo '<input type="text" size="25" ' . $name_id . $field_class
            . ' value="' . htmlspecialchars($value) . '" />';
        break;
    case 'number_text':
        echo '<input type="text" size="15" ' . $name_id . $field_class
            . ' value="' . htmlspecialchars($value) . '" />';
        break;
    case 'checkbox':
        echo '<span' . $field_class . '><input type="checkbox" ' . $name_id
          . ($value ? ' checked="checked"' : '') . ' /></span>';
        break;
    case 'select':
        echo '<select ' . $name_id . $field_class . '>';
        $escape = !(isset($opts['values_escaped']) && $opts['values_escaped']);
        $values_disabled = isset($opts['values_disabled'])
            ? array_flip($opts['values_disabled']) : array();
        foreach ($opts['values'] as $opt_value_key => $opt_value) {
            // set names for boolean values
            if (is_bool($opt_value)) {
                $opt_value = strtolower($opt_value ? __('Yes') : __('No'));
            }
            // escape if necessary
            if ($escape) {
                $display = htmlspecialchars($opt_value);
                $display_value = htmlspecialchars($opt_value_key);
            } else {
                $display = $opt_value;
                $display_value = $opt_value_key;
            }
            // compare with selected value
            // boolean values are cast to integers when used as array keys
            $selected = is_bool($value)
                ? (int) $value === $opt_value_key
                : $opt_value_key === $value;
            echo '<option value="' . $display_value . '"';
            if ($selected) {
                echo ' selected="selected"';
            }
            if (isset($values_disabled[$opt_value_key])) {
                echo ' disabled="disabled"';
            }
            echo '>' . $display . '</option>';
        }
        echo '</select>';
        break;
    case 'list':
        echo '<textarea cols="40" rows="5" ' . $name_id . $field_class . '>'
            . htmlspecialchars(implode("\n", $value))
            . '</textarea>';
        break;
    }
    if (isset($opts['comment']) && $opts['comment']) {
        $class = 'field-comment-mark';
        if (isset($opts['comment_warning']) && $opts['comment_warning']) {
            $class .= ' field-comment-warning';
        }
        echo '<span class="' . $class . '" title="'
            . htmlspecialchars($opts['comment']) . '">i</span>';
    }
    if ($is_setup_script
        && isset($opts['userprefs_comment'])
        && $opts['userprefs_comment']
    ) {
        echo '<a class="userprefs-comment" title="'
            . htmlspecialchars($opts['userprefs_comment']) . '">'
            . $icons['tblops'] . '</a>';
    }
    if (isset($opts['setvalue']) && $opts['setvalue']) {
        echo '<a class="set-value" href="#'
            . htmlspecialchars("$path={$opts['setvalue']}") . '" title="'
            . sprintf(__('Set value: %s'), htmlspecialchars($opts['setvalue']))
            . '" style="display:none">' . $icons['edit'] . '</a>';
    }
    if (isset($opts['show_restore_default']) && $opts['show_restore_default']) {
        echo '<a class="restore-default" href="#' . $path . '" title="'
            .  __('Restore default value') . '" style="display:none">'
            . $icons['reload'] . '</a>';
    }
    // this must match with displayErrors() in scripts/config.js
    if ($has_errors) {
        echo "\n        <dl class=\"inline_errors\">";
        foreach ($opts['errors'] as $error) {
            echo '<dd>' . htmlspecialchars($error) . '</dd>';
        }
        echo '</dl>';
    }
    echo '</td>';
    if ($is_setup_script && isset($opts['userprefs_allow'])) {
        echo '<td class="userprefs-allow" title="' .
            __('Allow users to customize this value') . '">';
        echo '<input type="checkbox" name="' . $path . '-userprefs-allow" ';
        if ($opts['userprefs_allow']) {
            echo 'checked="checked"';
        };
        echo '/>';
        echo '</td>';
    } else if ($is_setup_script) {
        echo '<td>&nbsp;</td>';
    }
    echo '</tr>';
}

/**
 * Display group header
 *
 * @param string $header_text Text of header
 *
 * @return void
 */
function PMA_displayGroupHeader($header_text)
{
    global $_FormDisplayGroup;

    $_FormDisplayGroup++;
    if (!$header_text) {
        return;
    }
    $colspan = defined('PMA_SETUP')
        ? 3
        : 2;
    echo '<tr class="group-header group-header-' . $_FormDisplayGroup . '">';
    echo '<th colspan="' . $colspan . '">';
    echo $header_text;
    echo '</th>';
    echo '</tr>';
}

/**
 * Display group footer
 *
 * @return void
 */
function PMA_displayGroupFooter()
{
    global $_FormDisplayGroup;

    $_FormDisplayGroup--;
}

/**
 * Displays bottom part of a fieldset
 *
 * @return void
 */
function PMA_displayFieldsetBottom()
{
    $colspan = 2;
    if (defined('PMA_SETUP')) {
        $colspan++;
    }
    echo '<tr>';
    echo '<td colspan="' . $colspan . '" class="lastrow">';
    echo '<input type="submit" name="submit_save" value="'
        . __('Save') . '" class="green" />';
    echo '<input type="button" name="submit_reset" value="'
        . __('Reset') . '" />';
    echo '</td>';
    echo '</tr>';
    echo '</table>';
    echo '</fieldset>';
}

/**
 * Displays simple bottom part of a fieldset (without submit buttons)
 *
 * @return void
 */
function PMA_displayFieldsetBottomSimple()
{
    echo '</table>';
    echo '</fieldset>';
}

/**
 * Closes form tabs
 *
 * @return void
 */
function PMA_displayTabsBottom()
{
    echo "</div>\n";
}

/**
 * Displays bottom part of the form
 *
 * @return void
 */
function PMA_displayFormBottom()
{
    echo "</form>\n";
}

/**
 * Appends JS validation code to $js_array
 *
 * @param string       $field_id   ID of field to validate
 * @param string|array $validators validators callback
 * @param array        &$js_array  will be updated with javascript code
 *
 * @return void
 */
function PMA_addJsValidate($field_id, $validators, &$js_array)
{
    foreach ((array)$validators as $validator) {
        $validator = (array)$validator;
        $v_name = array_shift($validator);
        $v_args = array();
        foreach ($validator as $arg) {
            $v_args[] = PMA_escapeJsString($arg);
        }
        $v_args = $v_args ? ", ['" . implode("', '", $v_args) . "']" : '';
        $js_array[] = "validateField('$field_id', '$v_name', true$v_args)";
    }
}

/**
 * Displays JavaScript code
 *
 * @param array $js_array lines of javascript code
 *
 * @return void
 */
function PMA_displayJavascript($js_array)
{
    if (empty($js_array)) {
        return;
    }
    echo '<script type="text/javascript">' . "\n";
    echo implode(";\n", $js_array) . ";\n";
    echo '</script>' . "\n";
}

/**
 * Displays error list
 *
 * @param string $name       name of item with errors
 * @param array  $error_list list of errors to show
 *
 * @return void
 */
function PMA_displayErrors($name, $error_list)
{
    echo '<dl>';
    echo '<dt>' . htmlspecialchars($name) . '</dt>';
    foreach ($error_list as $error) {
        echo '<dd>' . htmlspecialchars($error) . '</dd>';
    }
    echo '</dl>';
}
?>
