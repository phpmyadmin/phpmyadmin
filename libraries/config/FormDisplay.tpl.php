<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Form templates
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\Template;
use PMA\libraries\Util;
use PMA\libraries\URL;
use PMA\libraries\Sanitize;

/**
 * Displays top part of the form
 *
 * @param string $action        default: $_SERVER['REQUEST_URI']
 * @param string $method        'post' or 'get'
 * @param array  $hidden_fields array of form hidden fields (key: field name)
 *
 * @return string
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
    $htmlOutput = '<form method="' . $method . '" action="'
        . htmlspecialchars($action) . '" class="config-form disableAjax">';
    $htmlOutput .= '<input type="hidden" name="tab_hash" value="" />';
    // we do validation on page refresh when browser remembers field values,
    // add a field with known value which will be used for checks
    if (! $has_check_page_refresh) {
        $has_check_page_refresh = true;
        $htmlOutput .= '<input type="hidden" name="check_page_refresh" '
            . ' id="check_page_refresh" value="" />' . "\n";
    }
    $htmlOutput .= URL::getHiddenInputs('', '', 0, 'server') . "\n";
    $htmlOutput .= URL::getHiddenFields((array)$hidden_fields);
    return $htmlOutput;
}

/**
 * Displays form tabs which are given by an array indexed by fieldset id
 * ({@link PMA_displayFieldsetTop}), with values being tab titles.
 *
 * @param array $tabs tab names
 *
 * @return string
 */
function PMA_displayTabsTop($tabs)
{
    $items = array();
    foreach ($tabs as $tab_id => $tab_name) {
        $items[] = array(
            'content' => htmlspecialchars($tab_name),
            'url' => array(
                'href' => '#' . $tab_id,
            ),
        );
    }

    $htmlOutput = Template::get('list/unordered')->render(
        array(
            'class' => 'tabs',
            'items' => $items,
        )
    );
    $htmlOutput .= '<br />';
    $htmlOutput .= '<div class="tabs_contents">';
    return $htmlOutput;
}


/**
 * Displays top part of a fieldset
 *
 * @param string $title       title of fieldset
 * @param string $description description shown on top of fieldset
 * @param array  $errors      error messages to display
 * @param array  $attributes  optional extra attributes of fieldset
 *
 * @return string
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

    $htmlOutput = '<fieldset ' . implode(' ', $attributes) . '>';
    $htmlOutput .= '<legend>' . $title . '</legend>';
    if (!empty($description)) {
        $htmlOutput .= '<p>' . $description . '</p>';
    }
    // this must match with displayErrors() in scripts.js
    if (is_array($errors) && count($errors) > 0) {
        $htmlOutput .= '<dl class="errors">';
        foreach ($errors as $error) {
            $htmlOutput .= '<dd>' . $error . '</dd>';
        }
        $htmlOutput .= '</dl>';
    }
    $htmlOutput .= '<table width="100%" cellspacing="0">';
    return $htmlOutput;
}

/**
 * Displays input field
 *
 * $opts keys:
 * o doc - (string) documentation link
 * o errors - error array
 * o setvalue - (string) shows button allowing to set predefined value
 * o show_restore_default - (boolean) whether show "restore default" button
 * o userprefs_allow - whether user preferences are enabled for this field
 *                    (null - no support, true/false - enabled/disabled)
 * o userprefs_comment - (string) field comment
 * o values - key - value pairs for <select> fields
 * o values_escaped - (boolean) tells whether values array is already escaped
 *                    (defaults to false)
 * o values_disabled -  (array)list of disabled values (keys from values)
 * o comment - (string) tooltip comment
 * o comment_warning - (bool) whether this comments warns about something
 *
 * @param string $path             config option path
 * @param string $name             config option name
 * @param string $type             type of config option
 * @param mixed  $value            current value
 * @param string $description      verbose description
 * @param bool   $value_is_default whether value is default
 * @param array  $opts             see above description
 *
 * @return string
 */
function PMA_displayInput($path, $name, $type, $value, $description = '',
    $value_is_default = true, $opts = null
) {
    global $_FormDisplayGroup;
    static $icons;    // An array of IMG tags used further below in the function

    if (defined('TESTSUITE')) {
        $icons = null;
    }

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
                    "../themes/original/img/{$v[0]}",
                    $title
                );
            }
        } else {
            // In this case we just use getImage() because it's available
            foreach ($icon_init as $k => $v) {
                $icons[$k] = Util::getImage(
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

    $htmlOutput = '<tr' . $tr_class . '>';
    $htmlOutput .= '<th>';
    $htmlOutput .= '<label for="' . htmlspecialchars($path) . '">' . $name
        . '</label>';

    if (! empty($opts['doc'])) {
        $htmlOutput .= '<span class="doc">';
        $htmlOutput .= '<a href="' . $opts['doc']
            . '" target="documentation">' . $icons['help'] . '</a>';
        $htmlOutput .= "\n";
        $htmlOutput .= '</span>';
    }

    if ($option_is_disabled) {
        $htmlOutput .= '<span class="disabled-notice" title="';
        $htmlOutput .= __(
            'This setting is disabled, it will not be applied to your configuration.'
        );
        $htmlOutput .= '">' . __('Disabled') . "</span>";
    }

    if (!empty($description)) {
        $htmlOutput .= '<small>' . $description . '</small>';
    }

    $htmlOutput .= '</th>';
    $htmlOutput .= '<td>';

    switch ($type) {
    case 'text':
        $htmlOutput .= '<input type="text" size="40" ' . $name_id . $field_class
            . ' value="' . htmlspecialchars($value) . '" />';
        break;
    case 'password':
        $htmlOutput .= '<input type="password" size="40" ' . $name_id . $field_class
            . ' value="' . htmlspecialchars($value) . '" />';
        break;
    case 'short_text':
        // As seen in the reporting server (#15042) we sometimes receive
        // an array here. No clue about its origin nor content, so let's avoid
        // a notice on htmlspecialchars().
        if (! is_array($value)) {
            $htmlOutput .= '<input type="text" size="25" ' . $name_id
                . $field_class . ' value="' . htmlspecialchars($value)
                . '" />';
        }
        break;
    case 'number_text':
        $htmlOutput .= '<input type="number" ' . $name_id . $field_class
            . ' value="' . htmlspecialchars($value) . '" />';
        break;
    case 'checkbox':
        $htmlOutput .= '<span' . $field_class . '><input type="checkbox" ' . $name_id
          . ($value ? ' checked="checked"' : '') . ' /></span>';
        break;
    case 'select':
        $htmlOutput .= '<select ' . $name_id . $field_class . '>';
        $escape = !(isset($opts['values_escaped']) && $opts['values_escaped']);
        $values_disabled = isset($opts['values_disabled'])
            ? array_flip($opts['values_disabled']) : array();
        foreach ($opts['values'] as $opt_value_key => $opt_value) {
            // set names for boolean values
            if (is_bool($opt_value)) {
                $opt_value = mb_strtolower(
                    $opt_value ? __('Yes') : __('No')
                );
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
            $htmlOutput .= '<option value="' . $display_value . '"';
            if ($selected) {
                $htmlOutput .= ' selected="selected"';
            }
            if (isset($values_disabled[$opt_value_key])) {
                $htmlOutput .= ' disabled="disabled"';
            }
            $htmlOutput .= '>' . $display . '</option>';
        }
        $htmlOutput .= '</select>';
        break;
    case 'list':
        $htmlOutput .= '<textarea cols="40" rows="5" ' . $name_id . $field_class
            . '>' . htmlspecialchars(implode("\n", $value)) . '</textarea>';
        break;
    }
    if (isset($opts['comment']) && $opts['comment']) {
        $class = 'field-comment-mark';
        if (isset($opts['comment_warning']) && $opts['comment_warning']) {
            $class .= ' field-comment-warning';
        }
        $htmlOutput .= '<span class="' . $class . '" title="'
            . htmlspecialchars($opts['comment']) . '">i</span>';
    }
    if ($is_setup_script
        && isset($opts['userprefs_comment'])
        && $opts['userprefs_comment']
    ) {
        $htmlOutput .= '<a class="userprefs-comment" title="'
            . htmlspecialchars($opts['userprefs_comment']) . '">'
            . $icons['tblops'] . '</a>';
    }
    if (isset($opts['setvalue']) && $opts['setvalue']) {
        $htmlOutput .= '<a class="set-value" href="#'
            . htmlspecialchars("$path={$opts['setvalue']}") . '" title="'
            . sprintf(__('Set value: %s'), htmlspecialchars($opts['setvalue']))
            . '" style="display:none">' . $icons['edit'] . '</a>';
    }
    if (isset($opts['show_restore_default']) && $opts['show_restore_default']) {
        $htmlOutput .= '<a class="restore-default" href="#' . $path . '" title="'
            .  __('Restore default value') . '" style="display:none">'
            . $icons['reload'] . '</a>';
    }
    // this must match with displayErrors() in scripts/config.js
    if ($has_errors) {
        $htmlOutput .= "\n        <dl class=\"inline_errors\">";
        foreach ($opts['errors'] as $error) {
            $htmlOutput .= '<dd>' . htmlspecialchars($error) . '</dd>';
        }
        $htmlOutput .= '</dl>';
    }
    $htmlOutput .= '</td>';
    if ($is_setup_script && isset($opts['userprefs_allow'])) {
        $htmlOutput .= '<td class="userprefs-allow" title="' .
            __('Allow users to customize this value') . '">';
        $htmlOutput .= '<input type="checkbox" name="' . $path
            . '-userprefs-allow" ';
        if ($opts['userprefs_allow']) {
            $htmlOutput .= 'checked="checked"';
        };
        $htmlOutput .= '/>';
        $htmlOutput .= '</td>';
    } else if ($is_setup_script) {
        $htmlOutput .= '<td>&nbsp;</td>';
    }
    $htmlOutput .= '</tr>';
    return $htmlOutput;
}

/**
 * Display group header
 *
 * @param string $header_text Text of header
 *
 * @return string|void
 */
function PMA_displayGroupHeader($header_text)
{
    global $_FormDisplayGroup;

    $_FormDisplayGroup++;
    if (! $header_text) {
        return null;
    }
    $colspan = defined('PMA_SETUP')
        ? 3
        : 2;
    $htmlOutput = '<tr class="group-header group-header-' . $_FormDisplayGroup
        . '">';
    $htmlOutput .= '<th colspan="' . $colspan . '">';
    $htmlOutput .= $header_text;
    $htmlOutput .= '</th>';
    $htmlOutput .= '</tr>';
    return $htmlOutput;
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
 * @param bool $show_buttons whether show submit and reset button
 *
 * @return string
 */
function PMA_displayFieldsetBottom($show_buttons = true)
{
    $colspan = 2;
    if (defined('PMA_SETUP')) {
        $colspan++;
    }
    $htmlOutput = '';
    if ($show_buttons) {
        $htmlOutput .= '<tr>';
        $htmlOutput .= '<td colspan="' . $colspan . '" class="lastrow">';
        $htmlOutput .= '<input type="submit" name="submit_save" value="'
            . __('Apply') . '" class="green" />';
        $htmlOutput .= '<input type="button" name="submit_reset" value="'
            . __('Reset') . '" />';
        $htmlOutput .= '</td>';
        $htmlOutput .= '</tr>';
    }
    $htmlOutput .= '</table>';
    $htmlOutput .= '</fieldset>';
    return $htmlOutput;
}

/**
 * Displays simple bottom part of a fieldset (without submit buttons)
 *
 * @return string
 */
function PMA_displayFieldsetBottomSimple()
{
    $htmlOutput = '</table>';
    $htmlOutput .= '</fieldset>';
    return $htmlOutput;
}

/**
 * Closes form tabs
 *
 * @return string
 */
function PMA_displayTabsBottom()
{
    $htmlOutput = "</div>\n";
    return $htmlOutput;
}

/**
 * Displays bottom part of the form
 *
 * @return string
 */
function PMA_displayFormBottom()
{
    $htmlOutput = "</form>\n";
    return $htmlOutput;
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
        $v_name = "PMA_" . $v_name;
        $v_args = array();
        foreach ($validator as $arg) {
            $v_args[] = Sanitize::escapeJsString($arg);
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
 * @return string
 */
function PMA_displayJavascript($js_array)
{
    if (empty($js_array)) {
        return null;
    }

    return Template::get('javascript/display')->render(
        array('js_array' => $js_array,)
    );
}

/**
 * Displays error list
 *
 * @param string $name       name of item with errors
 * @param array  $error_list list of errors to show
 *
 * @return string HTML for errors
 */
function PMA_displayErrors($name, $error_list)
{
    $htmlOutput = '<dl>';
    $htmlOutput .= '<dt>' . htmlspecialchars($name) . '</dt>';
    foreach ($error_list as $error) {
        $htmlOutput .= '<dd>' . htmlspecialchars($error) . '</dd>';
    }
    $htmlOutput .= '</dl>';
    return $htmlOutput;
}
