<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Generic plugin interface.
 *
 * @package PhpMyAdmin
 */

/**
 * array PMA_getPlugins(string $plugins_dir, mixed $plugin_param)
 *
 * Reads all plugin information from directory $plugins_dir.
 *
 * @param string  $plugins_dir    directrory with plugins
 * @param mixed   $plugin_param   parameter to plugin by which they can decide whether they can work
 * @return  array                   list of plugins
 */
function PMA_getPlugins($plugins_dir, $plugin_param)
{
    /* Scan for plugins */
    $plugin_list = array();
    if ($handle = @opendir($plugins_dir)) {
        while ($file = @readdir($handle)) {
            // In some situations, Mac OS creates a new file for each file
            // (for example ._csv.php) so the following regexp
            // matches a file which does not start with a dot but ends
            // with ".php"
            if (is_file($plugins_dir . $file) && preg_match('@^[^\.](.)*\.php$@i', $file)) {
                include $plugins_dir . $file;
            }
        }
    }
    ksort($plugin_list);
    return $plugin_list;
}

/**
 * string PMA_getString(string $name)
 *
 * returns locale string for $name or $name if no locale is found
 *
 * @param string  $name   for local string
 * @return  string          locale string for $name
 */
function PMA_getString($name)
{
    return isset($GLOBALS[$name]) ? $GLOBALS[$name] : $name;
}

/**
 * string PMA_pluginCheckboxCheck(string $section, string $opt)
 *
 * returns html input tag option 'checked' if plugin $opt should be set by config or request
 *
 * @param string  $section    name of config section in
 *                              $GLOBALS['cfg'][$section] for plugin
 * @param string  $opt        name of option
 * @return  string              hmtl input tag option 'checked'
 */
function PMA_pluginCheckboxCheck($section, $opt)
{
    // If the form is being repopulated using $_GET data, that is priority
    if (isset($_GET[$opt]) || ! isset($_GET['repopulate']) && ((isset($GLOBALS['timeout_passed']) && $GLOBALS['timeout_passed'] && isset($_REQUEST[$opt])) ||
        (isset($GLOBALS['cfg'][$section][$opt]) && $GLOBALS['cfg'][$section][$opt]))) {
        return ' checked="checked"';
    }
    return '';
}

/**
 * string PMA_pluginGetDefault(string $section, string $opt)
 *
 * returns default value for option $opt
 *
 * @param string  $section    name of config section in
 *                              $GLOBALS['cfg'][$section] for plugin
 * @param string  $opt        name of option
 * @return  string              default value for option $opt
 */
function PMA_pluginGetDefault($section, $opt)
{
    if (isset($_GET[$opt])) { // If the form is being repopulated using $_GET data, that is priority
        return htmlspecialchars($_GET[$opt]);
    } elseif (isset($GLOBALS['timeout_passed']) && $GLOBALS['timeout_passed'] && isset($_REQUEST[$opt])) {
        return htmlspecialchars($_REQUEST[$opt]);
    } elseif (isset($GLOBALS['cfg'][$section][$opt])) {
        $matches = array();
        /* Possibly replace localised texts */
        if (preg_match_all('/(str[A-Z][A-Za-z0-9]*)/', $GLOBALS['cfg'][$section][$opt], $matches)) {
            $val = $GLOBALS['cfg'][$section][$opt];
            foreach ($matches[0] as $match) {
                if (isset($GLOBALS[$match])) {
                    $val = str_replace($match, $GLOBALS[$match], $val);
                }
            }
            return htmlspecialchars($val);
        } else {
            return htmlspecialchars($GLOBALS['cfg'][$section][$opt]);
        }
    }
    return '';
}

/**
 * string PMA_pluginIsActive(string $section, string $opt, string $val)
 *
 * returns html input tag option 'checked' if option $opt should be set by config or request
 *
 * @param string  $section    name of config section in
 *                              $GLOBALS['cfg'][$section] for plugin
 * @param string  $opt        name of option
 * @param string  $val        value of option to check against
 * @return  string              html input tag option 'checked'
 */
function PMA_pluginIsActive($section, $opt, $val)
{
    if (! empty($GLOBALS['timeout_passed']) && isset($_REQUEST[$opt])) {
        if ($_REQUEST[$opt] == $val) {
            return ' checked="checked"';
        }
    } elseif (isset($GLOBALS['cfg'][$section][$opt]) &&  $GLOBALS['cfg'][$section][$opt] == $val) {
        return ' checked="checked"';
    }
    return '';
}

/**
 * string PMA_pluginGetChoice(string $section, string $name, array &$list, string $cfgname)
 *
 * returns html select form element for plugin choice
 * and hidden fields denoting whether each plugin must be exported as a file
 *
 * @param string  $section    name of config section in
 *                              $GLOBALS['cfg'][$section] for plugin
 * @param string  $name       name of select element
 * @param array   &$list      array with plugin configuration defined in plugin file
 * @param string  $cfgname    name of config value, if none same as $name
 * @return  string              html select tag
 */
function PMA_pluginGetChoice($section, $name, &$list, $cfgname = null)
{
    if (! isset($cfgname)) {
        $cfgname = $name;
    }
    $ret = '<select id="plugins" name="' . $name . '">';
    $default = PMA_pluginGetDefault($section, $cfgname);
    foreach ($list as $plugin_name => $val) {
        $ret .= '<option';
         // If the form is being repopulated using $_GET data, that is priority
        if (isset($_GET[$name]) && $plugin_name == $_GET[$name] || ! isset($_GET[$name]) && $plugin_name == $default) {
            $ret .= ' selected="selected"';
        }
         $ret .= ' value="' . $plugin_name . '">' . PMA_getString($val['text']) . '</option>' . "\n";
    }
    $ret .= '</select>' . "\n";

    // Whether each plugin has to be saved as a file
    foreach ($list as $plugin_name => $val) {
        $ret .= '<input type="hidden" id="force_file_' . $plugin_name . '" value="';
        if (isset($val['force_file'])) {
            $ret .= 'true';
        } else {
            $ret .= 'false';
        }
        $ret .= '" />'. "\n";
    }
    return $ret;
}

/**
 * string PMA_pluginGetOneOption(string $section, string $plugin_name, string $id, array &$opt)
 *
 * returns single option in a list element
 *
 * @param string  $section        name of config section in
 *                                  $GLOBALS['cfg'][$section] for plugin
 * @param string  $plugin_name    unique plugin name
 * @param string  $id             option id
 * @param array   &$opt           plugin option details
 * @return  string                  table row with option
 */
function PMA_pluginGetOneOption($section, $plugin_name, $id, &$opt)
{
    $ret = "\n";
    if ($opt['type'] == 'bool') {
        $ret .= '<li>' . "\n";
        $ret .= '<input type="checkbox" name="' . $plugin_name . '_' . $opt['name'] . '"'
            . ' value="something" id="checkbox_' . $plugin_name . '_' . $opt['name'] . '"'
            . ' ' . PMA_pluginCheckboxCheck($section, $plugin_name . '_' . $opt['name']);
        if (isset($opt['force'])) {
            /* Same code is also few lines lower, update both if needed */
            $ret .= ' onclick="if (!this.checked &amp;&amp; '
                . '(!document.getElementById(\'checkbox_' . $plugin_name . '_' .$opt['force'] . '\') '
                . '|| !document.getElementById(\'checkbox_' . $plugin_name . '_' .$opt['force'] . '\').checked)) '
                . 'return false; else return true;"';
        }
        $ret .= ' />';
        $ret .= '<label for="checkbox_' . $plugin_name . '_' . $opt['name'] . '">'
            . PMA_getString($opt['text']) . '</label>';
    } elseif ($opt['type'] == 'text') {
        $ret .= '<li>' . "\n";
        $ret .= '<label for="text_' . $plugin_name . '_' . $opt['name'] . '" class="desc">'
            . PMA_getString($opt['text']) . '</label>';
        $ret .= '<input type="text" name="' . $plugin_name . '_' . $opt['name'] . '"'
            . ' value="' . PMA_pluginGetDefault($section, $plugin_name . '_' . $opt['name']) . '"'
            . ' id="text_' . $plugin_name . '_' . $opt['name'] . '"'
            . (isset($opt['size']) ? ' size="' . $opt['size'] . '"' : '')
            . (isset($opt['len']) ? ' maxlength="' . $opt['len'] . '"' : '') . ' />';
    } elseif ($opt['type'] == 'message_only') {
        $ret .= '<li>' . "\n";
        $ret .= '<p>' . PMA_getString($opt['text']) . '</p>';
    } elseif ($opt['type'] == 'select') {
        $ret .= '<li>' . "\n";
        $ret .= '<label for="select_' . $plugin_name . '_' . $opt['name'] . '" class="desc">'
            . PMA_getString($opt['text']) . '</label>';
        $ret .= '<select name="' . $plugin_name . '_' . $opt['name'] . '"'
            . ' id="select_' . $plugin_name . '_' . $opt['name'] . '">';
        $default = PMA_pluginGetDefault($section, $plugin_name . '_' . $opt['name']);
        foreach ($opt['values'] as $key => $val) {
            $ret .= '<option value="' . $key . '"';
            if ($key == $default) {
                $ret .= ' selected="selected"';
            }
            $ret .= '>' . PMA_getString($val) . '</option>';
        }
        $ret .= '</select>';
    } elseif ($opt['type'] == 'radio') {
        $default = PMA_pluginGetDefault($section, $plugin_name . '_' . $opt['name']);
        foreach ($opt['values'] as $key => $val) {
            $ret .= '<li><input type="radio" name="' . $plugin_name . '_' . $opt['name'] . '" value="' . $key
            . '" id="radio_' . $plugin_name . '_' . $opt['name'] . '_' . $key . '"';
            if ($key == $default) {
                $ret .= 'checked="checked"';
            }
            $ret .= ' />' . '<label for="radio_' . $plugin_name . '_' . $opt['name'] . '_' . $key . '">'
            . PMA_getString($val) . '</label></li>';
        }
    } elseif ($opt['type'] == 'hidden') {
        $ret .= '<li><input type="hidden" name="' . $plugin_name . '_' . $opt['name'] . '"'
            . ' value="' . PMA_pluginGetDefault($section, $plugin_name . '_' . $opt['name']) . '"' . ' /></li>';
    } elseif ($opt['type'] == 'begin_group') {
        $ret .= '<div class="export_sub_options" id="' . $plugin_name . '_' . $opt['name'] . '">';
        if (isset($opt['text'])) {
            $ret .= '<h4>' . PMA_getString($opt['text']) . '</h4>';
        }
        $ret .= '<ul>';
    } elseif ($opt['type'] == 'end_group') {
        $ret .= '</ul></div>';
    } elseif ($opt['type'] == 'begin_subgroup') {
        /* each subgroup can have a header, which may also be a form element */
        $ret .=  PMA_pluginGetOneOption($section, $plugin_name, $id, $opt['subgroup_header']) . '<li class="subgroup"><ul';
        if (isset($opt['subgroup_header']['name'])) {
            $ret .= ' id="ul_' . $opt['subgroup_header']['name'] . '">';
        } else {
            $ret .= '>';
        }
    } elseif ($opt['type'] == 'end_subgroup') {
        $ret .= '</ul></li>';
    } else {
        /* This should be seen only by plugin writers, so I do not thing this
         * needs translation. */
        $ret .= 'UNKNOWN OPTION ' . $opt['type'] . ' IN IMPORT PLUGIN ' . $plugin_name . '!';
    }
    if (isset($opt['doc'])) {
        if (count($opt['doc']) == 3) {
            $ret .= PMA_showMySQLDocu($opt['doc'][0], $opt['doc'][1], false, $opt['doc'][2]);
        } elseif (count($opt['doc']) == 1) {
            $ret .= PMA_showDocu($opt['doc'][0]);
        } else {
            $ret .= PMA_showMySQLDocu($opt['doc'][0], $opt['doc'][1]);
        }
    }

    // Close the list element after $opt['doc'] link is displayed
    if ($opt['type'] == 'bool' || $opt['type'] == 'text' || $opt['type'] == 'message_only' || $opt['type'] == 'select') {
        $ret .= '</li>';
    }
    $ret .= "\n";
    return $ret;
}

/**
 * string PMA_pluginGetOptions(string $section, array &$list)
 *
 * return html div with editable options for plugin
 *
 * @param string  $section    name of config section in $GLOBALS['cfg'][$section]
 * @param array   &$list      array with plugin configuration defined in plugin file
 * @return  string              html fieldset with plugin options
 */
function PMA_pluginGetOptions($section, &$list)
{
    $ret = '';
    $default = PMA_pluginGetDefault('Export', 'format');
    // Options for plugins that support them
    foreach ($list as $plugin_name => $val) {
        $ret .= '<div id="' . $plugin_name . '_options" class="format_specific_options">';
        $count = 0;
            $ret .= '<h3>' . PMA_getString($val['text']) . '</h3>';
        if (isset($val['options']) && count($val['options']) > 0) {
            foreach ($val['options'] as $id => $opt) {
                if ($opt['type'] != 'hidden' && $opt['type'] != 'begin_group' && $opt['type'] != 'end_group' && $opt['type'] != 'begin_subgroup' && $opt['type'] != 'end_subgroup') {
                    $count++;
                }
                $ret .= PMA_pluginGetOneOption($section, $plugin_name, $id, $opt);
            }
        }
        if ($count == 0) {
            $ret .= '<p>' . __('This format has no options') . '</p>';
        }
        $ret .= '</div>';
    }
    return $ret;
}
