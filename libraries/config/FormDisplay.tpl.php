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
<form method="<?php echo $method ?>" action="<?php echo htmlspecialchars($action) ?>" class="config-form">
<input type="hidden" name="tab_hash" value="" />
<?php
    // we do validation on page refresh when browser remembers field values,
    // add a field with known value which will be used for checks
    if (!$has_check_page_refresh) {
        $has_check_page_refresh = true;
        echo '<input type="hidden" name="check_page_refresh" id="check_page_refresh"'
            . ' value="" />' . "\n";
    }
    echo PMA_generate_common_hidden_inputs('', '', 0, 'server') . "\n";
    echo PMA_getHiddenFields((array)$hidden_fields);
}

/**
 * Displays form tabs which are given by an array indexed by fieldset id
 * ({@link display_fieldset_top}), with values being tab titles.
 *
 * @param array $tabs
 */
function display_tabs_top($tabs)
{
?>
<ul class="tabs">
<?php foreach ($tabs as $tab_id => $tab_name): ?>
    <li><a href="#<?php echo $tab_id ?>"><?php echo htmlspecialchars($tab_name); ?></a></li>
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
 * o userprefs_allow - whether user preferences are enabled for this field (null - no support,
 *                     true/false - enabled/disabled)
 * o userprefs_comment - (string) field comment
 * o values - key - value paris for <select> fields
 * o values_escaped - (boolean) tells whether values array is already escaped (defaults to false)
 * o values_disabled -  (array)list of disabled values (keys from values)
 * o comment - (string) tooltip comment
 * o comment_warning - (bool) whether this comments warns about something
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
    global $_FormDisplayGroup;
    static $base_dir; // Relative path to the root phpMyAdmin folder
    static $icons;    // An array of IMG tags used further below in the function

    $is_setup_script = defined('PMA_SETUP');
    if ($base_dir === null) { // if the static variables have not been initialised
        $base_dir = $is_setup_script ? '../' : '';
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
            // sprite-aware PMA_getImage() function because the PMA_theme class
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
            // In this case we just use PMA_getImage() because it's available
            foreach ($icon_init as $k => $v) {
                $icons[$k] = PMA_getImage($v[0], $v[1]);
            }
        }
    }
    $has_errors = isset($opts['errors']) && !empty($opts['errors']);
    $option_is_disabled = !$is_setup_script && isset($opts['userprefs_allow']) && !$opts['userprefs_allow'];
    $name_id = 'name="' . htmlspecialchars($path) . '" id="' . htmlspecialchars($path) . '"';
    $field_class = $type == 'checkbox' ? 'checkbox' : '';
    if (!$value_is_default) {
        $field_class .= ($field_class == '' ? '' : ' ') . ($has_errors ? 'custom field-error' : 'custom');
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
?>
<tr<?php echo $tr_class ?>>
    <th>
        <label for="<?php echo htmlspecialchars($path) ?>"><?php echo $name ?></label>
        <?php if (!empty($opts['doc']) || !empty($opts['wiki'])) { ?>
        <span class="doc">
            <?php if (!empty($opts['doc'])) { ?><a href="<?php echo $base_dir . $opts['doc']  ?>" target="documentation"><?php echo $icons['help']; ?></a><?php } ?>
            <?php if (!empty($opts['wiki'])){ ?><a href="<?php echo $opts['wiki'] ?>" target="wiki"><?php echo $icons['info']; ?></a><?php } ?>
        </span>
        <?php } ?>
        <?php if ($option_is_disabled) { ?>
            <span class="disabled-notice" title="<?php echo __('This setting is disabled, it will not be applied to your configuration') ?>"><?php echo __('Disabled') ?></span>
        <?php } ?>
        <?php if (!empty($description)) { ?><small><?php echo $description ?></small><?php } ?>
    </th>
    <td>
    <?php
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
                echo '<option value="' . $display_value . '"'
                    . ($selected ? ' selected="selected"' : '')
                    . (isset($values_disabled[$opt_value_key]) ? ' disabled="disabled"' : '')
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
    if (isset($opts['comment']) && $opts['comment']) {
        $class = 'field-comment-mark';
        if (isset($opts['comment_warning']) && $opts['comment_warning']) {
            $class .= ' field-comment-warning';
        }
        ?>
        <span class="<?php echo $class ?>" title="<?php echo htmlspecialchars($opts['comment']) ?>">i</span>
        <?php
    }
    if ($is_setup_script && isset($opts['userprefs_comment']) && $opts['userprefs_comment']) {
        ?>
        <a class="userprefs-comment" title="<?php echo htmlspecialchars($opts['userprefs_comment']) ?>"><?php echo $icons['tblops']; ?></a>
        <?php
    }
    if (isset($opts['setvalue']) && $opts['setvalue']) {
        ?>
        <a class="set-value" href="#<?php echo htmlspecialchars("$path={$opts['setvalue']}") ?>" title="<?php echo sprintf(__('Set value: %s'), htmlspecialchars($opts['setvalue'])) ?>" style="display:none"><?php echo $icons['edit']; ?></a>
        <?php
    }
    if (isset($opts['show_restore_default']) && $opts['show_restore_default']) {
        ?>
        <a class="restore-default" href="#<?php echo $path ?>" title="<?php echo __('Restore default value') ?>" style="display:none"><?php echo $icons['reload']; ?></a>
        <?php
    }
    // this must match with displayErrors() in scripts/config.js
    if ($has_errors) {
        echo "\n        <dl class=\"inline_errors\">";
        foreach ($opts['errors'] as $error) {
            echo '<dd>' . htmlspecialchars($error) . '</dd>';
        }
        echo '</dl>';
    }
    ?>
    </td>
    <?php
    if ($is_setup_script && isset($opts['userprefs_allow'])) {
    ?>
    <td class="userprefs-allow" title="<?php echo __('Allow users to customize this value') ?>">
        <input type="checkbox" name="<?php echo $path ?>-userprefs-allow" <?php if ($opts['userprefs_allow']) echo 'checked="checked"' ?> />
    </td>
    <?php
    } else if ($is_setup_script) {
        echo '<td>&nbsp;</td>';
    }
    ?>
</tr>
<?php
}

/**
 * Display group header
 *
 * @param string $header_text
 */
function display_group_header($header_text)
{
    global $_FormDisplayGroup;

    $_FormDisplayGroup++;
    if (!$header_text) {
        return;
    }
    $colspan = defined('PMA_SETUP')
        ? 3
        : 2;
?>
<tr class="group-header group-header-<?php echo $_FormDisplayGroup ?>">
    <th colspan="<?php echo $colspan ?>">
        <?php echo $header_text ?>
    </th>
</tr>
<?php
}

/**
 * Display group footer
 *
 */
function display_group_footer()
{
    global $_FormDisplayGroup;

    $_FormDisplayGroup--;
}

/**
 * Displays bottom part of a fieldset
 */
function display_fieldset_bottom()
{
    $colspan = 2;
    if (defined('PMA_SETUP')) {
        $colspan++;
    }
?>
<tr>
    <td colspan="<?php echo $colspan ?>" class="lastrow">
        <input type="submit" name="submit_save" value="<?php echo __('Save') ?>" class="green" />
        <input type="button" name="submit_reset" value="<?php echo __('Reset') ?>" />
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
function display_tabs_bottom()
{
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
 * @param string       $field_id
 * @param string|array $validator
 * @param array        $js_array
 */
function js_validate($field_id, $validators, &$js_array)
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
 * @param array $js_array
 */
function display_js($js_array)
{
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
function display_errors($name, $error_list)
{
    echo '<dl>';
    echo '<dt>' . htmlspecialchars($name) . '</dt>';
    foreach ($error_list as $error) {
        echo '<dd>' . htmlspecialchars($error) . '</dd>';
    }
    echo '</dl>';
}
?>
