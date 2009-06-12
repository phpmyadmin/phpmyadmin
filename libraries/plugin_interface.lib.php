<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Generic plugin interface.
 *
 * @version $Id$
 * @package phpMyAdmin
 */

/**
 * array PMA_getPlugins(string $plugins_dir, mixed $plugin_param)
 *
 * Reads all plugin information from directory $plugins_dir.
 *
 * @uses    ksort()
 * @uses    opendir()
 * @uses    readdir()
 * @uses    is_file()
 * @uses    preg_match()
 * @param   string  $plugins_dir    directrory with plugins
 * @param   mixed   $plugin_param   parameter to plugin by which they can decide whether they can work
 * @return  array                   list of plugins
 */
function PMA_getPlugins($plugins_dir, $plugin_param)
{
    /* Scan for plugins */
    $plugin_list = array();
    if ($handle = @opendir($plugins_dir)) {
        $is_first = 0;
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
 * @uses    $GLOBALS
 * @param   string  $name   for local string
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
 * @uses    $_REQUEST
 * @uses    $GLOBALS['cfg']
 * @uses    $GLOBALS['timeout_passed']
 * @param   string  $section    name of config section in
 *                              $GLOBALS['cfg'][$section] for plugin
 * @param   string  $opt        name of option
 * @return  string              hmtl input tag option 'checked'
 */
function PMA_pluginCheckboxCheck($section, $opt)
{
    if ((isset($GLOBALS['timeout_passed']) && $GLOBALS['timeout_passed'] && isset($_REQUEST[$opt])) ||
        (isset($GLOBALS['cfg'][$section][$opt]) && $GLOBALS['cfg'][$section][$opt])) {
        return ' checked="checked"';
    }
    return '';
}

/**
 * string PMA_pluginGetDefault(string $section, string $opt)
 *
 * returns default value for option $opt
 *
 * @uses    htmlspecialchars()
 * @uses    $_REQUEST
 * @uses    $GLOBALS['cfg']
 * @uses    $GLOBALS['timeout_passed']
 * @param   string  $section    name of config section in
 *                              $GLOBALS['cfg'][$section] for plugin
 * @param   string  $opt        name of option
 * @return  string              default value for option $opt
 */
function PMA_pluginGetDefault($section, $opt)
{
    if (isset($GLOBALS['timeout_passed']) && $GLOBALS['timeout_passed'] && isset($_REQUEST[$opt])) {
        return htmlspecialchars($_REQUEST[$opt]);
    } elseif (isset($GLOBALS['cfg'][$section][$opt])) {
        $matches = array();
        /* Possibly replace localised texts */
        if (preg_match_all('/(str[A-Z][A-Za-z0-9]*)/', $GLOBALS['cfg'][$section][$opt], $matches)) {
            $val = $GLOBALS['cfg'][$section][$opt];
            foreach($matches[0] as $match) {
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
 * @uses    $_REQUEST
 * @uses    $GLOBALS['cfg']
 * @uses    $GLOBALS['timeout_passed']
 * @param   string  $section    name of config section in
 *                              $GLOBALS['cfg'][$section] for plugin
 * @param   string  $opt        name of option
 * @param   string  $val        value of option to check against
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
 * returns html radio form element for plugin choice
 *
 * @uses    PMA_pluginIsActive()
 * @uses    PMA_getString()
 * @param   string  $section    name of config section in
 *                              $GLOBALS['cfg'][$section] for plugin
 * @param   string  $name       name of radio element
 * @param   array   &$list      array with plugin configuration defined in plugin file
 * @param   string  $cfgname    name of config value, if none same as $name
 * @return  string              html input radio tag
 */
function PMA_pluginGetChoice($section, $name, &$list, $cfgname = NULL)
{
    if (!isset($cfgname)) {
        $cfgname = $name;
    }
    $ret = '';
    foreach ($list as $plugin_name => $val) {
        $ret .= '<!-- ' . $plugin_name . ' -->' . "\n";
        $ret .= '<input type="radio" name="' . $name . '" value="' . $plugin_name . '"'
            . ' id="radio_plugin_' . $plugin_name . '"'
            . ' onclick="if(this.checked) { hide_them_all();';
        if (isset($val['force_file'])) {
            $ret .= 'document.getElementById(\'checkbox_dump_asfile\').checked = true;';
        }
        $ret .= ' document.getElementById(\'' . $plugin_name . '_options\').style.display = \'block\'; };'
                .' return true"'
            . PMA_pluginIsActive($section, $cfgname, $plugin_name) . '/>' . "\n";
        $ret .= '<label for="radio_plugin_' . $plugin_name . '">'
            . PMA_getString($val['text']) . '</label>' . "\n";
        $ret .= '<br />' . "\n";
    }
    return $ret;
}

/**
 * string PMA_pluginGetOneOption(string $section, string $plugin_name, string $id, array &$opt)
 *
 * returns single option in a table row
 *
 * @uses    PMA_getString()
 * @uses    PMA_pluginCheckboxCheck()
 * @uses    PMA_pluginGetDefault()
 * @param   string  $section        name of config section in
 *                                  $GLOBALS['cfg'][$section] for plugin
 * @param   string  $plugin_name    unique plugin name
 * @param   string  $id             option id
 * @param   array   &$opt           plugin option details
 * @return  string                  table row with option
 */
function PMA_pluginGetOneOption($section, $plugin_name, $id, &$opt)
{
    $ret = "\n";
    if ($opt['type'] == 'bool') {
        $ret .= '<div class="formelementrow">' . "\n";
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
        $ret .= '</div>' . "\n";
    } elseif ($opt['type'] == 'text') {
        $ret .= '<div class="formelementrow">' . "\n";
        $ret .= '<label for="text_' . $plugin_name . '_' . $opt['name'] . '" class="desc">'
            . PMA_getString($opt['text']) . '</label>';
        $ret .= '<input type="text" name="' . $plugin_name . '_' . $opt['name'] . '"'
            . ' value="' . PMA_pluginGetDefault($section, $plugin_name . '_' . $opt['name']) . '"'
            . ' id="text_' . $plugin_name . '_' . $opt['name'] . '"'
            . (isset($opt['size']) ? ' size="' . $opt['size'] . '"' : '')
            . (isset($opt['len']) ? ' maxlength="' . $opt['len'] . '"' : '') . ' />';
        $ret .= '</div>' . "\n";
    } elseif ($opt['type'] == 'message_only') {
        $ret .= '<div class="formelementrow">' . "\n";
        $ret .= '<p class="desc">' . PMA_getString($opt['text']) . '</p>';
        $ret .= '</div>' . "\n";
    } elseif ($opt['type'] == 'select') {
        $ret .= '<div class="formelementrow">' . "\n";
        $ret .= '<label for="select_' . $plugin_name . '_' . $opt['name'] . '" class="desc">'
            . PMA_getString($opt['text']) . '</label>';
        $ret .= '<select name="' . $plugin_name . '_' . $opt['name'] . '"'
            . ' id="select_' . $plugin_name . '_' . $opt['name'] . '">';
        $default = PMA_pluginGetDefault($section, $plugin_name . '_' . $opt['name']);
        foreach($opt['values'] as $key => $val) {
            $ret .= '<option value="' . $key . '"';
            if ($key == $default) {
                $ret .= ' selected="selected"';
            }
            $ret .= '>' . PMA_getString($val) . '</option>';
        }
        $ret .= '</select>';
        $ret .= '</div>' . "\n";
    } elseif ($opt['type'] == 'hidden') {
        $ret .= '<input type="hidden" name="' . $plugin_name . '_' . $opt['name'] . '"'
            . ' value="' . PMA_pluginGetDefault($section, $plugin_name . '_' . $opt['name']) . '"' . ' />';
    } elseif ($opt['type'] == 'bgroup') {
        $ret .= '<fieldset><legend>';
        /* No checkbox without name */
        if (!empty($opt['name'])) {
            $ret .= '<input type="checkbox" name="' . $plugin_name . '_' . $opt['name'] . '"'
                . ' value="something" id="checkbox_' . $plugin_name . '_' . $opt['name'] . '"'
                . ' ' . PMA_pluginCheckboxCheck($section, $plugin_name . '_' . $opt['name']);
            if (isset($opt['force'])) {
                /* Same code is also few lines higher, update both if needed */
                $ret .= ' onclick="if (!this.checked &amp;&amp; '
                    . '(!document.getElementById(\'checkbox_' . $plugin_name . '_' .$opt['force'] . '\') '
                    . '|| !document.getElementById(\'checkbox_' . $plugin_name . '_' .$opt['force'] . '\').checked)) '
                    . 'return false; else return true;"';
            }
            $ret .= ' />';
            $ret .= '<label for="checkbox_' . $plugin_name . '_' . $opt['name'] . '">'
                . PMA_getString($opt['text']) . '</label>';
        } else {
            $ret .= PMA_getString($opt['text']);
        }
        $ret .= '</legend>';
    } elseif ($opt['type'] == 'egroup') {
        $ret .= '</fieldset>';
    } else {
        /* This should be seen only by plugin writers, so I do not thing this
         * needs translation. */
        $ret .= 'UNKNOWN OPTION ' . $opt['type'] . ' IN IMPORT PLUGIN ' . $plugin_name . '!';
    }
    if (isset($opt['doc'])) {
        if (count($opt['doc']) == 3) {
            $ret .= PMA_showMySQLDocu($opt['doc'][0], $opt['doc'][1], false, $opt['doc'][2]);
        } else {
            $ret .= PMA_showMySQLDocu($opt['doc'][0], $opt['doc'][1]);
        }
    }
    $ret .= "\n";
    return $ret;
}

/**
 * string PMA_pluginGetOptions(string $section, array &$list)
 *
 * return html fieldset with editable options for plugin
 *
 * @uses    PMA_getString()
 * @uses    PMA_pluginGetOneOption()
 * @param   string  $section    name of config section in $GLOBALS['cfg'][$section]
 * @param   array   &$list      array with plugin configuration defined in plugin file
 * @return  string              html fieldset with plugin options
 */
function PMA_pluginGetOptions($section, &$list)
{
    $ret = '';
    // Options for plugins that support them
    foreach ($list as $plugin_name => $val) {
        $ret .= '<fieldset id="' . $plugin_name . '_options" class="options">';
        $ret .= '<legend>' . PMA_getString($val['options_text']) . '</legend>';
        $count = 0;
        if (isset($val['options']) && count($val['options']) > 0) {
            foreach ($val['options'] as $id => $opt) {
                if ($opt['type'] != 'hidden') $count++;
                $ret .= PMA_pluginGetOneOption($section, $plugin_name, $id, $opt);
            }
        }
        if ($count == 0) {
            $ret .= $GLOBALS['strNoOptions'];
        }
        $ret .= '</fieldset>';
    }
    return $ret;
}

/**
 * string PMA_pluginGetJavascript(array &$list)
 *
 * return html/javascript code which is needed for handling plugin stuff
 *
 * @param   array   &$list      array with plugin configuration defined in plugin file
 * @return  string              html fieldset with plugin options
 */
function PMA_pluginGetJavascript(&$list) {
    $ret = '
    <script type="text/javascript">
    //<![CDATA[
    function hide_them_all() {
        ';
    foreach ($list as $plugin_name => $val) {
        $ret .= 'document.getElementById("' . $plugin_name . '_options").style.display = "none";' . "\n";
    }
    $ret .= '
    }

    function init_options() {
        hide_them_all();
        ';
    foreach ($list as $plugin_name => $val) {
        $ret .= 'if (document.getElementById("radio_plugin_' . $plugin_name . '").checked) {' . "\n";
        if (isset($val['force_file'])) {
            $ret .= 'document.getElementById(\'checkbox_dump_asfile\').checked = true;' . "\n";
        }
        $ret .= 'document.getElementById("' . $plugin_name . '_options").style.display = "block";' . "\n";
        $ret .= ' } else ' . "\n";
    }
    $ret .= '
        {
            ;
        }
    }

    function match_file(fname) {
        farr = fname.toLowerCase().split(".");
        if (farr.length != 0) {
            len = farr.length
            if (farr[len - 1] == "gz" || farr[len - 1] == "bz2" || farr[len -1] == "zip") len--;
            switch (farr[len - 1]) {
                ';
    foreach ($list as $plugin_name => $val) {
        $ret .= 'case "' . $val['extension'] . '" :';
        $ret .= 'document.getElementById("radio_plugin_' . $plugin_name . '").checked = true;';
        $ret .= 'init_options();';
        $ret .= 'break;' . "\n";
    }
    $ret .='
            }
        }
    }
    //]]>
    </script>
    ';
    return $ret;
}
?>
