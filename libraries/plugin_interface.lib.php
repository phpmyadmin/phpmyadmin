<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Generic plugin interface.
 */

/**
 * Reads all plugin information from directory.
 * 
 * @param   string  directrory with plugins
 * @param   mixed   parameter to plugin by which they can decide whether they can work
 *
 * @returns array   list of plugins
 */
function PMA_getPlugins($plugins_dir, $plugin_param) {
    /* Scan for plugins */
    $plugin_list = array();
    if ($handle = @opendir($plugins_dir)) {
        $is_first = 0;
        while ($file = @readdir($handle)) {
            if (is_file($plugins_dir . $file) && eregi('\.php$', $file)) {
                include($plugins_dir . $file);
            }
        }
    }
    ksort($plugin_list);
    return $plugin_list;
}

function PMA_getString($name) {
    return isset($GLOBALS[$name]) ? $GLOBALS[$name] : $name;
}

function PMA_pluginCheckboxCheck($section, $opt) {
    if ((isset($GLOBALS['timeout_passed']) && $GLOBALS['timeout_passed'] && isset($_REQUEST[$opt])) ||
        (isset($GLOBALS['cfg'][$section][$opt]) && $GLOBALS['cfg'][$section][$opt])) {
        return ' checked="checked"';
    }
    return '';
}

function PMA_pluginGetDefault($section, $opt) {
    if (isset($GLOBALS['timeout_passed']) && $GLOBALS['timeout_passed'] && isset($_REQUEST[$opt])) {
        return htmlspecialchars($_REQUEST[$opt]);
    } elseif (isset($GLOBALS['cfg'][$section][$opt])) {
        return htmlspecialchars($GLOBALS['cfg'][$section][$opt]);
    }
    return '';
}

function PMA_pluginIsActive($section, $opt, $val) {
    if (isset($GLOBALS['timeout_passed']) && $GLOBALS['timeout_passed'] && isset($_REQUEST[$opt])) {
        if ($_REQUEST[$opt] == $val) {
            return ' checked="checked"';
        }
    } elseif (isset($GLOBALS['cfg'][$section][$opt]) &&  $GLOBALS['cfg'][$section][$opt] == $val) {
        return ' checked="checked"';
    }
    return '';
}

function PMA_pluginGetChoice($section, $name, &$list) {
    $ret = '';
    foreach($list as $key => $val) {
        $ret .= '<!-- ' . $key . ' -->' . "\n";
        $ret .= '<input type="radio" name="' . $name . '" value="' . $key . '" id="radio_plugin_' . $key . '" onclick="if(this.checked) { hide_them_all(); document.getElementById(\'' . $key . '_options\').style.display = \'block\'; }; return true" ' . PMA_pluginIsActive($section, $name, $key) . '/>' . "\n";
        $ret .= '<label for="radio_plugin_' . $key . '">' . PMA_getString($val['text']) . '</label>' . "\n";
        $ret .= '<br /><br />' . "\n";
    }
    return $ret;
}

function PMA_pluginGetOneOption($section, $key, $id, &$opt) {
    $ret = '';
    if ($opt['type'] == 'bool') {
        $ret .= '<input type="checkbox" name="' . $key . '_' . $opt['name'] . '" value="something" id="checkbox_' . $key . '_' . $opt['name'] . '" ' . PMA_pluginCheckboxCheck($section, $key . '_' . $opt['name']) .'/>';
        $ret .= '<label for="checkbox_' . $key . '_' . $opt['name'] . '">' . PMA_getString($opt['text']) . '</label>';
    } elseif ($opt['type'] == 'text') {
        $ret .= '<label for="text_' . $key . '_' . $opt['name'] . '" style="float: left; width: 20%;">' . PMA_getString($opt['text']) . '</label>';
        $ret .= '<input type="text" name="' . $key . '_' . $opt['name'] . '" value="' . PMA_pluginGetDefault($section, $key . '_' . $opt['name']) . '" id="text_' . $key . '_' . $opt['name'] . '" ' . (isset($opt['size']) ? 'size="' . $opt['size'] . '"' : '' )  . (isset($opt['len']) ? 'maxlength="' . $opt['len'] . '"' : '' ) . '/>';
    } else {
        /* This should be seen only by plugin writers, so I do not thing this needs translation. */
        $ret .= 'UNKNOWN OPTION IN IMPORT PLUGIN ' . $key . '!';
    }
    $ret .= '<br />';
    return $ret;
}

function PMA_pluginGetOptions($section, &$list) {
    $ret = '';
    // Options for plugins that support them
    foreach($list as $key => $val) {
        $ret .= '<fieldset id="' . $key . '_options" class="options">';
        $ret .= '<legend>' . PMA_getString($val['options_text']) . '</legend>';
        if (isset($val['options'])) {
            foreach($val['options'] as $id => $opt) {
                $ret .= PMA_pluginGetOneOption($section, $key, $id, $opt);
            }
        } else {
            $ret .= $GLOBALS['strNoOptions'];
        }
        $ret .= '</fieldset>';
    }
    return $ret;
}

function PMA_pluginGetJavascript(&$list) {
    $ret = ' 
    <script type="text/javascript" language="javascript">
    //<![CDATA[
    function hide_them_all() {
        ';
    foreach($list as $key => $val) {
        $ret .= 'document.getElementById("' . $key . '_options").style.display = "none";' . "\n";
    }
    $ret .= '
    }

    function init_options() {
        hide_them_all();
        ';
    foreach($list as $key => $val) {
        $ret .= 'if (document.getElementById("radio_plugin_' . $key . '").checked) {' . "\n";
        $ret .= 'document.getElementById("' . $key . '_options").style.display = "block";' . "\n";
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
    foreach($list as $key => $val) {
        $ret .= 'case "' . $val['extension'] . '" :';
        $ret .= 'document.getElementById("radio_plugin_' . $key . '").checked = true;';
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
