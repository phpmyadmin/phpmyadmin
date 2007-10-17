<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * phpMyAdmin setup script
 *
 * PHP versions 4 and 5
 *
 * @category   Setup
 * @package    phpMyAdmin-setup
 * @author     Michal Čihař <michal@cihar.com>
 * @copyright  2006 Michal Čihař <michal@cihar.com>
 * @license    http://www.gnu.org/licenses/gpl.html GNU GPL 2.0
 * @version    $Id$
 */

// Grab phpMyAdmin version and PMA_dl function
define('PMA_MINIMUM_COMMON', TRUE);
define('PMA_SETUP', TRUE);
chdir('..');
require_once './libraries/common.inc.php';

// Grab configuration defaults
// Do not use $PMA_Config, it interferes with the one in $_SESSION
// on servers with register_globals enabled
$PMA_Config_Setup = new PMA_Config();

// Script information
$script_info = 'phpMyAdmin ' . $PMA_Config_Setup->get('PMA_VERSION') . ' setup script by Michal Čihař <michal@cihar.com>';
$script_version = '$Id$';

// Grab action
if (isset($_POST['action'])) {
    $action = $_POST['action'];
} else {
    $action = '';
}

// Grab wanted CRLF type
if (isset($_POST['eoltype'])) {
    $eoltype = $_POST['eoltype'];
} else {
    if (PMA_USR_OS == 'Win') {
        $eoltype = 'dos';
    } else {
        $eoltype = 'unix';
    }
}

// Detect which CRLF to use
if ($eoltype == 'dos') {
    $crlf = "\r\n";
} elseif ($eoltype == 'mac') {
    $crlf = "\r";
} else {
    $crlf = "\n";
}

if (isset($_POST['configuration']) && $action != 'clear') {
    // Grab previous configuration, if it should not be cleared
    $configuration = unserialize($_POST['configuration']);
} else {
    // Start with empty configuration
    $configuration = array();
}

// We rely on Servers array to exist, so create it here
if (!isset($configuration['Servers']) || !is_array($configuration['Servers'])) {
    $configuration['Servers'] = array();
}

// Used later
$now = gmdate('D, d M Y H:i:s') . ' GMT';

// General header for no caching
header('Expires: ' . $now); // rfc2616 - Section 14.21
header('Last-Modified: ' . $now);
header('Cache-Control: no-store, no-cache, must-revalidate, pre-check=0, post-check=0, max-age=0'); // HTTP/1.1
header('Pragma: no-cache'); // HTTP/1.0

// whether to show html header?
if ($action != 'download') {

// Define the charset to be used
header('Content-Type: text/html; charset=utf-8');

// this needs to be echoed otherwise php with short tags complains
echo '<?xml version="1.0" encoding="utf-8"?>' . "\n";
?>
<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Transitional//EN"
    "http://www.w3.org/TR/xhtml1/DTD/xhtml1-transitional.dtd">
<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en" dir="ltr">
<head>
    <link rel="icon" href="../favicon.ico" type="image/x-icon" />
    <link rel="shortcut icon" href="../favicon.ico" type="image/x-icon" />
    <title>phpMyAdmin <?php echo $PMA_Config_Setup->get('PMA_VERSION'); ?> setup</title>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />

    <script type="text/javascript">
    //<![CDATA[
    // show this window in top frame
    if (top != self) {
        window.top.location.href=location;
    }
    //]]>
    </script>
    <style type="text/css">
    /* message boxes: warning, error, stolen from original theme */
    div.notice {
        color: #000000;
        background-color: #FFFFDD;
    }
    h1.notice,
    div.notice {
        margin: 0.5em 0 0.5em 0;
        border: 0.1em solid #FFD700;
        background-image: url(../<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/s_notice.png);
        background-repeat: no-repeat;
        background-position: 10px 50%;
        padding: 10px 10px 10px 36px;
    }
    div.notice h1 {
        border-bottom: 0.1em solid #FFD700;
        font-weight: bold;
        font-size: large;
        text-align: left;
        margin: 0 0 0.2em 0;
    }

    div.warning {
        color: #CC0000;
        background-color: #FFFFCC;
    }
    h1.warning,
    div.warning {
        margin: 0.5em 0 0.5em 0;
        border: 0.1em solid #CC0000;
        background-image: url(../<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/s_warn.png);
        background-repeat: no-repeat;
        background-position: 10px 50%;
        padding: 10px 10px 10px 36px;
    }
    div.warning h1 {
        border-bottom: 0.1em solid #cc0000;
        font-weight: bold;
        text-align: left;
        font-size: large;
        margin: 0 0 0.2em 0;
    }

    div.error {
        background-color: #FFFFCC;
        color: #ff0000;
    }
    h1.error,
    div.error {
        margin: 0.5em 0 0.5em 0;
        border: 0.1em solid #ff0000;
        background-image: url(../<?php echo $GLOBALS['cfg']['ThemePath']; ?>/original/img/s_error.png);
        background-repeat: no-repeat;
        background-position: 10px 50%;
        padding: 10px 10px 10px 36px;
    }
    div.error h1 {
        border-bottom: 0.1em solid #ff0000;
        font-weight: bold;
        text-align: left;
        font-size: large;
        margin: 0 0 0.2em 0;
    }

    fieldset.toolbar form.action {
        display: block;
        width: auto;
        clear: none;
        float: left;
        margin: 0;
        padding: 0;
        border-right: 1px solid black;
    }
    fieldset.toolbar form.action input, fieldset.toolbar form.action select {
        margin: 0.7em;
        padding: 0.1em;
    }

    fieldset.toolbar {
        display: block;
        width: 100%;
        background-color: #dddddd;
        padding: 0;
    }
    fieldset.optbox {
        padding: 0;
        background-color: #FFFFDD;
    }
    div.buttons, div.opts, fieldset.optbox p, fieldset.overview div.row {
        clear: both;
        padding: 0.5em;
        margin: 0;
        background-color: white;
    }
    div.opts, fieldset.optbox p, fieldset.overview div.row {
        border-bottom: 1px dotted black;
    }
    fieldset.overview {
        display: block;
        width: 100%;
        padding: 0;
    }
    fieldset.optbox p {
        background-color: #FFFFDD;
    }
    div.buttons {
        background-color: #dddddd;
    }
    div.buttons input {
        margin: 0 1em 0 1em;
    }
    div.buttons form {
        display: inline;
        margin: 0;
        padding: 0;
    }
    input.save {
        color: green;
        font-weight: bolder;
    }
    input.cancel {
        color: red;
        font-weight: bolder;
    }
    div.desc, label.desc, fieldset.overview div.desc {
        float: left;
        width: 27em;
        max-width: 60%;
    }
    code:before, code:after {
        content: '"';
    }
    span.doc {
        margin: 0 1em 0 1em;
    }
    span.doc a {
        margin: 0 0.1em 0 0.1em;
    }
    span.doc a img {
        border: none;
    }
    </style>
</head>

<body>
<h1>phpMyAdmin <?php echo $PMA_Config_Setup->get('PMA_VERSION'); ?> setup</h1>
<?php
} // end show html header

/**
 * Calculates numerical equivalent of phpMyAdmin version string
 *
 * @param   string  version
 *
 * @return  mixed   FALSE on failure, integer on success
 */
function version_to_int($version) {
    if (!preg_match('/^(\d+)\.(\d+)\.(\d+)((\.|-(pl|rc|dev|beta|alpha))(\d+)?)?$/', $version, $matches)) {
        return FALSE;
    }
    if (!empty($matches[6])) {
        switch ($matches[6]) {
            case 'pl':
                $added = 60;
                break;
            case 'rc':
                $added = 30;
                break;
            case 'beta':
                $added = 20;
                break;
            case 'alpha':
                $added = 10;
                break;
            case 'dev':
                $added = 0;
                break;
            default:
                message('notice', 'Unknown version part: ' . htmlspecialchars($matches[5]));
                $added = 0;
                break;
        }
    } else {
        $added = 50; // for final
    }
    if (!empty($matches[7])) {
        $added = $added + $matches[7];
    }
    return $matches[1] * 1000000 + $matches[2] * 10000 + $matches[3] * 100 + $added;
}

/**
 * Returns link to documentation of some configuration directive
 *
 * @param   string  confguration directive name
 *
 * @return  string  HTML link to documentation
 */
function get_cfg_doc($anchor) {
    /* Link for wiki */
    $wiki = $anchor;
    if (strncmp($anchor, 'Servers_', 8) == 0) {
        $wiki = substr($anchor, 8);
    }
    return
        '<span class="doc">' .
        '<a href="../Documentation.html#cfg_' . $anchor . '" target="pma_doc" class="doc">' .
        '<img class="icon" src="../' . $GLOBALS['cfg']['ThemePath'] . '/original/img/b_help.png" width="11" height="11" alt="Documentation" title="Documentation" />' .
        '</a>' .
        '<a href="http://wiki.cihar.com/pma/Config#' . $wiki . '" target="pma_doc" class="doc">' .
        '<img class="icon" src="../' . $GLOBALS['cfg']['ThemePath'] . '/original/img/b_info.png" width="11" height="11" alt="Wiki" title="Wiki" />' .
        '</a>' .
        '</span>'
        ;
}

/**
 * Displays message
 *
 * @param   string  type of message (notice/warning/error)
 * @param   string  text of message
 * @param   title   optional title of message
 *
 * @return  nothing
 */
function message($type, $text, $title = '') {
    echo '<div class="' . $type . '">' . "\n";
    if (!empty($title)) {
        echo '<h1>';
        echo $title;
        echo '</h1>' . "\n";
    }
    echo $text . "\n";
    echo '</div>' . "\n";
}

/**
 * Creates hidden input required for keeping current configuraion
 *
 * @return string   HTML with hidden inputs
 */
function get_hidden_cfg() {
    global $configuration, $eoltype;

    $ret = '<input type="hidden" name="configuration" value="' . htmlspecialchars(serialize($configuration)) . '" />' . "\n";
    $ret .= '<input type="hidden" name="eoltype" value="' . htmlspecialchars($eoltype) . '" />' . "\n";

    return $ret;
}

/**
 * Returns needed hidden input for forms.
 *
 * @return  string HTML with hidden inputs
 */
function get_hidden_inputs() {
    return '<input type="hidden" name="token" value="' . $_SESSION[' PMA_token '] . '" />';
}

/**
 * Creates form for some action
 *
 * @param   string  action name
 * @param   string  form title
 * @param   string  optional additional inputs
 *
 * @return  string  HTML with form
 */
function get_action($name, $title, $added = '', $enabled = TRUE) {
    $ret = '';
    $ret .= '<form class="action" method="post" action="">';
    $ret .= get_hidden_inputs();
    $ret .= '<input type="hidden" name="action" value="' . $name . '" />';
    $ret .= $added;
    $ret .= '<input type="submit" value="' . $title . '"';
    if (!$enabled) {
        $ret .= ' disabled="disabled"';
    }
    $ret .= ' />';
    $ret .= get_hidden_cfg();
    $ret .= '</form>';
    $ret .= "\n";
    return $ret;
}

/**
 * Creates form for going to some url
 *
 * @param   string  URL where to go
 * @param   string  form title
 * @param   string  optional array of parameters
 *
 * @return  string  HTML with form
 */
function get_url_action($url, $title, $params = array()) {
    $ret = '';
    $ret .= '<form class="action" method="get" action="' . $url . '" target="_blank">';
    $ret .= get_hidden_inputs();
    foreach ($params as $key => $val) {
        $ret .= '<input type="hidden" name="' . $key . '" value="' . $val . '" />';
    }
    $ret .= '<input type="submit" value="' . $title . '" />';
    $ret .= '</form>';
    $ret .= "\n";
    return $ret;
}

/**
 * Terminates script and ends HTML
 *
 * @return nothing
 */
function footer() {
    echo '</body>';
    echo '</html>';
    exit;
}

/**
 * Creates string describing server authentication method
 *
 * @param   array   server configuration
 *
 * @return  string  authentication method description
 */
function get_server_auth($val) {
    global $PMA_Config_Setup;

    if (isset($val['auth_type'])) {
        $auth = $val['auth_type'];
    } else {
        $auth = $PMA_Config_Setup->default_server['auth_type'];
    }
    $ret = $auth;
    if ($auth == 'config') {
        if (isset($val['user'])) {
            $ret .= ':' . $val['user'];
        } else {
            $ret .= ':' . $PMA_Config_Setup->default_server['user'];
        }
    }
    return $ret;
}

/**
 * Creates nice string with server name
 *
 * @param   array   server configuration
 * @param   int     optional server id
 *
 * @return  string  fancy server name
 */
function get_server_name($val, $id = FALSE, $escape = true) {
    if (!empty($val['verbose'])) {
        $ret = $val['verbose'];
    } else {
        $ret = $val['host'];
    }
    $ret .= ' (' . get_server_auth($val) . ')';
    if ($id !== FALSE) {
        $ret .= ' [' . ($id + 1) . ']' ;
    }
    if ($escape) {
        return htmlspecialchars($ret);
    } else {
        return $ret;
    }
}


/**
 * Exports variable to PHP code, very limited version of var_export
 *
 * @param   string  data to export
 *
 * @see var_export
 *
 * @return  string  PHP code containing variable value
 */
function PMA_var_export($input) {
    global $crlf;

    $output = '';
    if (is_null($input)) {
        $output .= 'NULL';
    } elseif (is_array($input)) {
        $output .= 'array (' . $crlf;
        foreach($input as $key => $value) {
            $output .= PMA_var_export($key) . ' => ' . PMA_var_export($value);
            $output .= ',' . $crlf;
        }
        $output .= ')';
    } elseif (is_string($input)) {
        $output .= '\'' . addslashes($input) . '\'';
    } elseif (is_int($input) || is_double($input)) {
        $output .= (string) $input;
    } elseif (is_bool($input)) {
        $output .= $input ? 'true' : 'false';
    } else {
        die('Unknown type for PMA_var_export: ' . $input);
    }
    return $output;
}

/**
 * Creates configuration code for one variable
 *
 * @param   string  variable name
 * @param   mixed   configuration
 *
 * @return  string  PHP code containing configuration
 */
function get_cfg_val($name, $val) {
    global $crlf;

    $ret = '';
    if (is_array($val)) {
        $ret .= $crlf;
        foreach ($val as $k => $v) {
            if (!isset($type)) {
                if (is_string($k)) {
                    $type = 'string';
                } elseif (is_int($k)) {
                    $type = 'int';
                    $ret .= $name . ' = array(' . $crlf;
                } else {
                    // Something unknown...
                    $ret .= $name. ' = ' . PMA_var_export($val) . ';' . $crlf;
                    break;
                }
            }
            if ($type == 'string') {
                $ret .= get_cfg_val($name . "['$k']", $v);
            } elseif ($type == 'int') {
                $ret .= '    ' . PMA_var_export($v) . ',' . $crlf;
            }
        }
        if (!isset($type)) {
            /* Empty array */
            $ret .= $name . ' = array();' . $crlf;
        } elseif ($type == 'int') {
            $ret .= ');' . $crlf;
        }
        $ret .= $crlf;
        unset($type);
    } else {
        $ret .= $name . ' = ' . PMA_var_export($val) . ';' . $crlf;
    }
    return $ret;
}

/**
 * Creates configuration PHP code
 *
 * @param   array   configuration
 *
 * @return  string  PHP code containing configuration
 */
function get_cfg_string($cfg) {
    global $script_info, $script_version, $now, $crlf;

    $c = $cfg;
    $ret = "<?php$crlf/*$crlf * Generated configuration file$crlf * Generated by: $script_info$crlf * Version: $script_version$crlf * Date: " . $now . $crlf . ' */' . $crlf . $crlf;

    if (count($c['Servers']) > 0) {
        $ret .= "/* Servers configuration */$crlf\$i = 0;" . $crlf;
        foreach ($c['Servers'] as $cnt => $srv) {
            $ret .= $crlf . '/* Server ' . strtr(get_server_name($srv, $cnt, false), '*', '-') . " */$crlf\$i++;" . $crlf;
            foreach ($srv as $key => $val) {
                $ret .= get_cfg_val("\$cfg['Servers'][\$i]['$key']", $val);
            }
        }
        $ret .= $crlf . '/* End of servers configuration */' . $crlf . $crlf;
    }
    unset($c['Servers']);

    foreach ($c as $key => $val) {
        $ret .= get_cfg_val("\$cfg['$key']", $val);
    }

    $ret .= '?>' . $crlf;
    return $ret;
}

/**
 * Compresses server configuration to be indexed from 0 and contain no gaps
 *
 * @param   array   configuration
 *
 * @return  nothing
 */
function compress_servers(&$cfg) {
    $ns = array();
    foreach ($cfg['Servers'] as $val) {
        if (!empty($val['host'])) {
            $ns[] = $val;
        }
    }
    $cfg['Servers'] = $ns;
}

/**
 * Grabs values from POST
 *
 * @param   string   list of values to grab, values are separated by ";",
 *                   each can have defined type separated by ":", if no type
 *                   is defined, string is assumed. Possible types: bool -
 *                   boolean value, serialized - serialized value, int -
 *                   integer, tristate - "TRUE"/"FALSE" converted to bool,
 *                   other strings are kept.
 *
 * @return  array   array with grabbed values
 */
function grab_values($list)
{
    $a = split(';', $list);
    $res = array();
    foreach ($a as $val) {
        $v = split(':', $val);
        if (!isset($v[1])) {
            $v[1] = '';
        }
        switch($v[1]) {
            case 'bool':
                $res[$v[0]] = isset($_POST[$v[0]]);
                break;
            case 'serialized':
                if (isset($_POST[$v[0]]) && strlen($_POST[$v[0]]) > 0) {
                    $res[$v[0]] = unserialize($_POST[$v[0]]);
                }
                break;
            case 'int':
                if (isset($_POST[$v[0]]) && strlen($_POST[$v[0]]) > 0) {
                    $res[$v[0]] = (int)$_POST[$v[0]];
                }
                break;
            case 'tristate':
                if (isset($_POST[$v[0]]) && strlen($_POST[$v[0]]) > 0) {
                    $cur = $_POST[$v[0]];
                    if ($cur == 'TRUE') {
                        $res[$v[0]] = TRUE;
                    } elseif ($cur == 'FALSE') {
                        $res[$v[0]] = FALSE;
                    } else {
                        $res[$v[0]] = $cur;
                    }
                }
                break;
            case 'string':
            default:
                if (isset($_POST[$v[0]]) && strlen($_POST[$v[0]]) > 0) {
                    $res[$v[0]] = $_POST[$v[0]];
                }
                break;
        }
    }
    return $res;
}

/**
 * Displays overview
 *
 * @param   string  title of oveview
 * @param   array   list of values to display (each element is array of two
 *                  values - name and value)
 * @param   string  optional buttons to be displayed
 *
 * @return  nothing
 */
function show_overview($title, $list, $buttons = '') {
    echo '<fieldset class="overview">' . "\n";
    echo '<legend>' . $title . '</legend>' . "\n";
    foreach ($list as $val) {
        echo '<div class="row">';
        echo '<div class="desc">';
        echo $val[0];
        echo '</div>';
        echo '<div class="data">';
        echo $val[1];
        echo '</div>';
        echo '</div>' . "\n";
    }
    if (!empty($buttons)) {
        echo '<div class="buttons">';
        echo '<div class="desc">Actions:</div>';
        echo $buttons;
        echo '</div>' . "\n";
    }
    echo '</fieldset>' . "\n";
    echo "\n";
}

/**
 * Displays configuration, fallback defaults are taken from global $PMA_Config_Setup
 *
 * @param   array   list of values to display (each element is array of two or
 *                  three values - desription, name and optional type
 *                  indicator). Type is determined by type of this parameter,
 *                  array means select and array elements are items,
 *                  'password' means password input.
 * @param   string  title of configuration
 * @param   string  help string for this configuration
 * @param   array   optional first level defaults
 * @param   string  optional title for save button
 * @param   string  optional prefix for documentation links
 *
 * @return  nothing
 */
function show_config_form($list, $legend, $help, $defaults = array(), $save = '', $prefix = '') {
    global $PMA_Config_Setup;

    if (empty($save)) {
        $save = 'Update';
    }

    echo '<fieldset class="optbox">' . "\n";
    echo '<legend>' . $legend . '</legend>' . "\n";
    echo '<p>' . $help . '</p>' . "\n";
    foreach ($list as $val) {
        echo '<div class="opts">';
        $type = 'text';
        if (isset($val[3])) {
            if (is_array($val[3])) {
                $type = 'select';
            } elseif (is_bool($val[3])) {
                $type = 'check';
            } elseif ($val[3] == 'password') {
                $type = 'password';
            }
        }
        switch ($type) {
            case 'text':
            case 'password':
                echo '<label for="text_' . $val[1] . '" class="desc" title="' . $val[2] . '">' . $val[0] . get_cfg_doc($prefix . $val[1]) . '</label>';
                echo '<input type="' . $type . '" name="' . $val[1] . '" id="text_' . $val[1] . '" title="' . $val[2] . '" size="50"';
                if (isset($defaults[$val[1]])) {
                    echo ' value="' . htmlspecialchars($defaults[$val[1]]) . '"';
                } else {
                    echo ' value="' . htmlspecialchars($PMA_Config_Setup->get($val[1])) . '"';
                }
                echo ' />';
                break;
            case 'check':
                echo '<input type="checkbox" name="' . $val[1] . '" value="something" id="checkbox_' . $val[1] . '" title="' . $val[2] . '"';
                if (isset($defaults[$val[1]])) {
                    if ($defaults[$val[1]]) {
                        echo ' checked="checked"';
                    }
                } else {
                    if ($PMA_Config_Setup->get($val[1])) {
                        echo ' checked="checked"';
                    }
                }
                echo ' />';
                echo '<label for="checkbox_' . $val[1] . '" title="' . $val[2] . '">' . $val[0] . get_cfg_doc($prefix . $val[1]) . '</label>';
                break;
            case 'select':
                echo '<label for="select_' . $val[1] . '" class="desc" title="' . $val[2] . '">' . $val[0] . get_cfg_doc($prefix . $val[1]) . '</label>';
                echo '<select name="' . $val[1] . '" id="select_' . $val[1] . '" ' . ' title="' . $val[2] . '">';
                foreach ($val[3] as $opt) {
                    echo '<option value="' . $opt . '"';
                    if (isset($defaults[$val[1]])) {
                        if (is_bool($defaults[$val[1]])) {
                            if (($defaults[$val[1]] && $opt == 'TRUE') || (!$defaults[$val[1]] && $opt == 'FALSE')) {
                                echo ' selected="selected"';
                            }
                        } else {
                            if ($defaults[$val[1]] == $opt) {
                                echo ' selected="selected"';
                            }
                        }
                    } else {
                        $def_val = $PMA_Config_Setup->get($val[1]);
                        if (is_bool($val)) {
                            if (($def_val && $opt == 'TRUE') || (!$def_val && $opt == 'FALSE')) {
                                echo ' selected="selected"';
                            }
                        } else {
                            if ($def_val == $opt) {
                                echo ' selected="selected"';
                            }
                        }
                        unset($def_val);
                    }
                    echo '>' . $opt . '</option>';
                }
                echo '</select>';
                break;
        }
        echo '</div>' . "\n";
    }
    echo '<div class="buttons">';
    echo '<div class="desc">Actions:</div>';
    echo '<input type="submit" name="submit_save" value="' . $save .'" class="save" />';
    echo '<input type="submit" name="submit_ignore" value="Cancel" class="cancel" />';
    echo '</div>' . "\n";
    echo '</fieldset>' . "\n";
    echo "\n";
}

/**
 * Shows security options configuration form
 *
 * @param   array   optional defaults
 *
 * @return  nothing
 */
function show_security_form($defaults = array()) {
    ?>
<form method="post" action="">
    <?php echo get_hidden_inputs();?>
    <input type="hidden" name="action" value="feat_security_real" />
    <?php
        echo get_hidden_cfg();
        show_config_form(array(
            array('Blowfish secret', 'blowfish_secret', 'Secret passphrase used for encrypting cookies'),
            array('Force SSL connection', 'ForceSSL', 'Whether to force using secured connection while using phpMyAdmin', FALSE),
            array('Show phpinfo output', 'ShowPhpInfo', 'Whether to allow users to see phpinfo() output', FALSE),
            array('Show password change form', 'ShowChgPassword', 'Whether to show form for changing password, this does not limit ability to execute the same command directly', FALSE),
            array('Allow login to any MySQL server', 'AllowArbitraryServer', 'If enabled user can enter any MySQL server in login form for cookie auth.', FALSE),
            array('Recall user name', 'LoginCookieRecall', 'Whether to recall user name on log in prompt while using cookie auth.', TRUE),
            array('Login cookie validity', 'LoginCookieValidity', 'How long is login valid without performing any action.'),
            ),
            'Configure security features',
            'Please note that phpMyAdmin is just a user interface and it\'s features do not limit MySQL.',
            $defaults);
    ?>
</form>
    <?php
}

/**
 * Shows MySQL manual configuration form
 *
 * @param   array   optional defaults
 *
 * @return  nothing
 */
function show_manual_form($defaults = array()) {
    ?>
<form method="post" action="">
    <?php echo get_hidden_inputs();?>
    <input type="hidden" name="action" value="feat_manual_real" />
    <?php
        echo get_hidden_cfg();
        show_config_form(array(
            array('Type of MySQL documentation', 'MySQLManualType', 'These types are same as listed on MySQL download page', array('viewable', 'chapters', 'big', 'none')),
            array('Base URL of MySQL documentation', 'MySQLManualBase', 'Where is MySQL documentation placed, this is usually top level directory.'),
            ),
            'Configure MySQL manual links',
            'If you have local copy of MySQL documentation, you might want to use it in documentation links. Otherwise use <code>viewable</code> type and <code>http://dev.mysql.com/doc/refman</code> as manual base URL.',
            $defaults);
    ?>
</form>
    <?php
}

/**
 * Shows charset options configuration form
 *
 * @param   array   optional defaults
 *
 * @return  nothing
 */
function show_charset_form($defaults = array()) {
    global $PMA_Config_Setup;
    ?>
<form method="post" action="">
    <?php echo get_hidden_inputs();?>
    <input type="hidden" name="action" value="feat_charset_real" />
    <?php
        echo get_hidden_cfg();
        show_config_form(array(
            array('Allow charset conversion', 'AllowAnywhereRecoding', 'If you want to use such functions.', FALSE),
            array('Default charset', 'DefaultCharset', 'Default charset for conversion.', $PMA_Config_Setup->get('AvailableCharsets')),
            array('Recoding engine', 'RecodingEngine', 'PHP can contain iconv and/or recode, select which one to use or keep autodetection.', array('auto', 'iconv', 'recode')),
            array('Extra params for iconv', 'IconvExtraParams', 'Iconv can get some extra parameters for conversion see man iconv_open.'),
            ),
            'Configure charset conversions',
            'phpMyAdmin can perform charset conversions so that you can import and export in any charset you want.',
            $defaults);
    ?>
</form>
    <?php
}

/**
 * Shows PHP extensions configuration form
 *
 * @param   array   optional defaults
 *
 * @return  nothing
 */
function show_extensions_form($defaults = array()) {
    ?>
<form method="post" action="">
    <?php echo get_hidden_inputs();?>
    <input type="hidden" name="action" value="feat_extensions_real" />
    <?php
        echo get_hidden_cfg();
        show_config_form(array(
            array('GD 2 is available', 'GD2Available', 'Whether you have GD 2 or newer installed', array('auto', 'yes', 'no')),
            ),
            'Configure extensions',
            'phpMyAdmin can use several extensions, however here are configured only those that didn\'t fit elsewhere. MySQL extension is configured within server, charset conversion one on separate charsets page.',
            $defaults);
    ?>
</form>
    <?php
}

/**
 * Shows MIME/relation/history configuration form
 *
 * @param   array   optional defaults
 *
 * @return  nothing
 */
function show_relation_form($defaults = array()) {
    global $PMA_Config_Setup;
    ?>
<form method="post" action="">
    <?php echo get_hidden_inputs();?>
    <input type="hidden" name="action" value="feat_relation_real" />
    <?php
        echo get_hidden_cfg();
        show_config_form(array(
            array('Permanent query history', 'QueryHistoryDB', 'Store history into database.', FALSE),
            array('Maximal history size', 'QueryHistoryMax', 'How many queries are kept in history.'),
            array('Use MIME transformations', 'BrowseMIME', 'Use MIME transformations while browsing.', TRUE),
            array('PDF default page size', 'PDFDefaultPageSize', 'Default page size for PDF, you can change this while creating page.', $PMA_Config_Setup->get('PDFPageSizes')),
            ),
            'Configure MIME/relation/history',
            'phpMyAdmin can provide additional features like MIME transformation, internal relations, permanent history and PDF pages generation. You have to configure the database and tables that will store this information on the server page. Behaviour of those functions is configured here.',
            $defaults);
    ?>
</form>
    <?php
}

/**
 * Shows upload/save configuration form
 *
 * @param   array   optional defaults
 *
 * @return  nothing
 */
function show_upload_form($defaults = array()) {
    ?>
<form method="post" action="">
    <?php echo get_hidden_inputs();?>
    <input type="hidden" name="action" value="feat_upload_real" />
    <?php
        echo get_hidden_cfg();
        show_config_form(array(
            array('Upload directory', 'UploadDir', 'Directory on server where you can upload files for import'),
            array('Save directory', 'SaveDir', 'Directory where exports can be saved on server'),
            ),
            'Configure upload/save directories',
            'Enter directories, either absolute path or relative to phpMyAdmin top level directory.',
            $defaults);
    ?>
</form>
    <?php
}

/**
 * Shows server configuration form
 *
 * @param   array   optional defaults
 *
 * @return  nothing
 */
function show_server_form($defaults = array(), $number = FALSE) {
    ?>
<form method="post" action="">
    <?php echo get_hidden_inputs();?>
    <input type="hidden" name="action" value="addserver_real" />
    <?php
        echo get_hidden_cfg();
        if (!($number === FALSE)) {
            echo '<input type="hidden" name="server" value="' . $number . '" />';
        }
        $hi = array ('bookmarktable', 'relation', 'table_info', 'table_coords', 'pdf_pages', 'column_info', 'designer_coords', 'history', 'AllowDeny');
        foreach ($hi as $k) {
            if (isset($defaults[$k]) && (!is_string($defaults[$k]) || strlen($defaults[$k]) > 0)) {
                echo '<input type="hidden" name="' . $k . '" value="' . htmlspecialchars(serialize($defaults[$k])) . '" />';
            }
        }
        show_config_form(array(
            array('Server hostname', 'host', 'Hostname where MySQL server is running'),
            array('Server port', 'port', 'Port on which MySQL server is listening, leave empty for default'),
            array('Server socket', 'socket', 'Socket on which MySQL server is listening, leave empty for default'),
            array('Connection type', 'connect_type', 'How to connect to server, keep tcp if unsure', array('tcp', 'socket')),
            array('PHP extension to use', 'extension', 'What PHP extension to use, use mysqli if supported', array('mysql', 'mysqli')),
            array('Compress connection', 'compress', 'Whether to compress connection to MySQL server', FALSE),
            array('Authentication type', 'auth_type', 'Authentication method to use', array('cookie', 'http', 'config', 'signon')),
            array('User for config auth', 'user', 'Leave empty if not using config auth'),
            array('Password for config auth', 'password', 'Leave empty if not using config auth', 'password'),
            array('Only database to show', 'only_db', 'Limit listing of databases in left frame to this one'),
            array('Verbose name of this server', 'verbose', 'Name to display in server selection'),
            array('phpMyAdmin control user', 'controluser', 'User which phpMyAdmin can use for various actions'),
            array('phpMyAdmin control user password', 'controlpass', 'Password for user which phpMyAdmin can use for various actions', 'password'),
            array('phpMyAdmin database for advanced features', 'pmadb', 'phpMyAdmin will allow much more when you enable this. Table names are filled in automatically.'),
            array('Session name for signon auth', 'SignonSession', 'Leave empty if not using signon auth'),
            array('Login URL for signon auth', 'SignonURL', 'Leave empty if not using signon auth'),
            array('Logout URL', 'LogoutURL', 'Where to redirect user after logout'),
            ),
            'Configure server',
            ($number === FALSE) ? 'Enter new server connection parameters.' : 'Editing server ' . get_server_name($defaults, $number),
            $defaults, $number === FALSE ? 'Add' : '', 'Servers_');
    ?>
</form>
    <?php
}

/**
 * Shows left frame configuration form
 *
 * @param   array   optional defaults
 *
 * @return  nothing
 */
function show_left_form($defaults = array()) {
    ?>
<form method="post" action="">
    <?php echo get_hidden_inputs();?>
    <input type="hidden" name="action" value="lay_navigation_real" />
    <?php
        echo get_hidden_cfg();
        show_config_form(array(
            array('Use light version', 'LeftFrameLight', 'Disable this if you want to see all databases at one time.', TRUE),
            array('Display databases in tree', 'LeftFrameDBTree', 'Whether to display databases in tree (determined by separator defined lower)', TRUE),
            array('Databases tree separator', 'LeftFrameDBSeparator', 'String that separates databases into different tree level'),
            array('Table tree separator', 'LeftFrameTableSeparator', 'String that separates tables into different tree level'),
            array('Maximum table tree nesting', 'LeftFrameTableLevel', 'Maximum number of children in table tree'),
            array('Show logo', 'LeftDisplayLogo', 'Whether to show logo in left frame', TRUE),
            array('Display servers selection', 'LeftDisplayServers', 'Whether to show server selection in left frame', FALSE),
            array('Display servers as list', 'DisplayServersList', 'Whether to show server listing as list instead of drop down', FALSE),
            array('Display databases as list', 'DisplayDatabasesList', 'Whether to show database listing in navigation as list instead of drop down', array('auto', 'yes', 'no')),
            array('Enable pointer highlighting', 'LeftPointerEnable', 'Whether you want to highlight server under mouse', TRUE),
            ),
            'Configure navigation frame',
            'Customize the appears of the navigation frame.',
            $defaults);
    ?>
</form>
    <?php
}

/**
 * Shows tabs configuration form
 *
 * @param   array   optional defaults
 *
 * @return  nothing
 */
function show_tabs_form($defaults = array()) {
    ?>
<form method="post" action="">
    <?php echo get_hidden_inputs();?>
    <input type="hidden" name="action" value="lay_tabs_real" />
    <?php
        echo get_hidden_cfg();
        show_config_form(array(
            array('Default tab for server', 'DefaultTabServer', 'Tab that is displayed when entering server', array('main.php', 'server_databases.php', 'server_status.php', 'server_variables.php', 'server_privileges.php', 'server_processlist.php')),
            array('Default tab for database', 'DefaultTabDatabase', 'Tab that is displayed when entering database', array('db_structure.php', 'db_sql.php', 'db_search.php', 'db_operations.php')),
            array('Default tab for table', 'DefaultTabTable', 'Tab that is displayed when entering table', array('tbl_structure.php', 'sql.php', 'tbl_sql.php', 'tbl_select.php', 'tbl_change.php')),
            array('Use lighter tabs', 'LightTabs', 'If you want simpler tabs enable this', FALSE),
            ),
            'Configure tabs',
            'Choose how you want tabs to work.',
            $defaults);
    ?>
</form>
    <?php
}

/**
 * Shows icons configuration form
 *
 * @param   array   optional defaults
 *
 * @return  nothing
 */
function show_icons_form($defaults = array()) {
    ?>
<form method="post" action="">
    <?php echo get_hidden_inputs();?>
    <input type="hidden" name="action" value="lay_icons_real" />
    <?php
        echo get_hidden_cfg();
        show_config_form(array(
            array('Icons on errors', 'ErrorIconic', 'Whether to use icons in error messages.', TRUE),
            array('Icons on main page', 'MainPageIconic', 'Whether to use icons on main page.', TRUE),
            array('Icons as help links', 'ReplaceHelpImg', 'Whether to use icons as help links.', TRUE),
            array('Navigation with icons', 'NavigationBarIconic', 'Whether to display navigation (eg. tabs) with icons.', array('TRUE', 'FALSE', 'both')),
            array('Properties pages with icons', 'PropertiesIconic', 'Whether to display properties (eg. table lists and structure) with icons.', array('TRUE', 'FALSE', 'both')),
            ),
            'Configure icons',
            'Select whether you prefer text or icons. Both means that text and icons will be displayed.',
            $defaults);
    ?>
</form>
    <?php
}

/**
 * Shows browsing  configuration form
 *
 * @param   array   optional defaults
 *
 * @return  nothing
 */
function show_browse_form($defaults = array()) {
    ?>
<form method="post" action="">
    <?php echo get_hidden_inputs();?>
    <input type="hidden" name="action" value="lay_browse_real" />
    <?php
        echo get_hidden_cfg();
        show_config_form(array(
            array('Display of values', 'DefaultDisplay', 'How to list values while browsing', array('horizontal', 'vertical', 'horizontalflipped')),
            array('Hightlight pointer', 'BrowsePointerEnable', 'Whether to highlight row under mouse.', TRUE),
            array('Use row marker', 'BrowseMarkerEnable', 'Whether to highlight selected row.', TRUE),
            array('Action buttons on left', 'ModifyDeleteAtLeft', 'Show action buttons on left side of listing?', TRUE),
            array('Action buttons on right', 'ModifyDeleteAtRight', 'Show action buttons on right side of listing?', FALSE),
            array('Repeat heading', 'RepeatCells', 'After how many rows heading should be repeated.'),
            ),
            'Configure browsing',
            'Select desired browsing look and feel.',
            $defaults);
    ?>
</form>
    <?php
}

/**
 * Shows editing options configuration form
 *
 * @param   array   optional defaults
 *
 * @return  nothing
 */
function show_edit_form($defaults = array()) {
    ?>
<form method="post" action="">
    <?php echo get_hidden_inputs();?>
    <input type="hidden" name="action" value="lay_edit_real" />
    <?php
        echo get_hidden_cfg();
        show_config_form(array(
            array('Display of properties while editing', 'DefaultPropDisplay', 'How to list properties (table structure or values) while editing', array('horizontal', 'vertical')),
            array('Number of inserted rows', 'InsertRows', 'How many rows can be inserted at once'),
            array('Move using Ctrl+arrows', 'CtrlArrowsMoving', 'Whether to enable moving using Ctrl+Arrows', TRUE),
            array('Autoselect text in textarea', 'TextareaAutoSelect', 'Whether to automatically select text in textarea on focus.', TRUE),
            array('Textarea columns', 'TextareaCols', 'Number of columns in textarea while editing TEXT fields'),
            array('Textarea rows', 'TextareaRows', 'Number of rows in textarea while editing TEXT fields'),
            array('Double textarea for LONGTEXT', 'LongtextDoubleTextarea', 'Whether to double textarea size for LONGTEXT fields', TRUE),
            array('Edit CHAR fields in textarea', 'CharEditing', 'Whether to edit CHAR fields in textarea', array('input', 'textarea')),
            array('CHAR textarea columns', 'CharTextareaCols', 'Number of columns in textarea while editing CHAR fields (must be enabled above)'),
            array('CHAR textarea rows', 'CharTextareaRows', 'Number of rows in textarea while editing CHAR fields (must be enabled above)'),
            ),
            'Configure editing',
            'Select desired editing look and feel.',
            $defaults);
    ?>
</form>
    <?php
}

/**
 * Shows query window configuration form
 *
 * @param   array   optional defaults
 *
 * @return  nothing
 */
function show_window_form($defaults = array()) {
    ?>
<form method="post" action="">
    <?php echo get_hidden_inputs();?>
    <input type="hidden" name="action" value="lay_window_real" />
    <?php
        echo get_hidden_cfg();
        show_config_form(array(
            array('Edit SQL in window', 'EditInWindow', 'Whether edit links will edit in query window.', TRUE),
            array('Query window height', 'QueryWindowHeight', 'Height of query window'),
            array('Query window width', 'QueryWindowWidth', 'Width of query window'),
            array('Default tab', 'QueryWindowDefTab', 'Default tab on query window', array('sql', 'files', 'history', 'full')),
            ),
            'Configure query window',
            'Select desired query window look and feel.',
            $defaults);
    ?>
</form>
    <?php
}

/**
 * Creates selection with servers
 *
 * @param   array   configuraion
 *
 * @return  string  HTML for server selection
 */
function get_server_selection($cfg) {
    if (count($cfg['Servers']) == 0) {
        return '';
    }
    $ret = '<select name="server">';
    foreach ($cfg['Servers'] as $key => $val) {
        $ret .= '<option value="' . $key . '">' . get_server_name($val, $key) . '</option>';
    }
    $ret .= '</select>';
    return $ret;
}

/**
 * Loads configuration from file
 *
 * @param   string  filename
 *
 * @return  mixed   FALSE on failure, new config array on success
 */
function load_config($config_file) {
    if (file_exists($config_file)) {
        $success_apply_user_config = FALSE;
        $old_error_reporting = error_reporting(0);
        if (function_exists('file_get_contents')) {
            $success_apply_user_config = eval('?>' . trim(file_get_contents($config_file)));
        } else {
            $success_apply_user_config =
                eval('?>' . trim(implode("\n", file($config_file))));
        }
        error_reporting($old_error_reporting);
        unset($old_error_reporting);
        if ($success_apply_user_config === FALSE) {
            message('error', 'Error while parsing configuration file!');
        } elseif (!isset($cfg) || count($cfg) == 0) {
            message('error', 'Config file seems to contain no configuration!');
        } else {
            // This must be set
            if (!isset($cfg['Servers'])) {
                $cfg['Servers'] = array();
            }
            message('notice', 'Configuration loaded');
            compress_servers($cfg);
            return $cfg;
        }
    } else {
        message('error', 'Configuration file not found!');
    }
    return FALSE;
}

if ($action != 'download') {
    // Check whether we can write to configuration
    $fail_dir = FALSE;
    $fail_dir = $fail_dir || !is_dir('./config/');
    $fail_dir = $fail_dir || !is_writable('./config/');
    $fail_dir = $fail_dir || (file_exists('./config/config.inc.php') && !is_writable('./config/config.inc.php'));
    $config = @fopen('./config/config.inc.php', 'a');
    $fail_dir = $fail_dir || ($config === FALSE);
    @fclose($config);
}

/**
 * @var boolean whether to show configuration overview
 */
$show_info = FALSE;

// Do the main work depending on selected action
switch ($action) {
    case 'download':
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="config.inc.php"');

        echo get_cfg_string($configuration);
        exit;
        break;
    case 'display':
        echo '<form method="none" action=""><textarea name="config" cols="50" rows="20" id="textconfig" wrap="off">' . "\n";
        echo htmlspecialchars(get_cfg_string($configuration));
        echo '</textarea></form>' . "\n";
        ?>
<script type="text/javascript">
//<![CDATA[
    var bodyWidth=null; var bodyHeight=null;
    if (document.getElementById('textconfig')) {
        bodyWidth  = self.innerWidth;
        bodyHeight = self.innerHeight;
        if(!bodyWidth && !bodyHeight){
            if (document.compatMode && document.compatMode == "BackCompat") {
                bodyWidth  = document.body.clientWidth;
                bodyHeight = document.body.clientHeight;
            } else if (document.compatMode && document.compatMode == "CSS1Compat") {
                bodyWidth  = document.documentElement.clientWidth;
                bodyHeight = document.documentElement.clientHeight;
            }
        }
        document.getElementById('textconfig').style.width=(bodyWidth-50) + 'px';
        document.getElementById('textconfig').style.height=(bodyHeight-100) + 'px';
    }
//]]>
</script>
        <?php
        break;
    case 'save':
        $config = @fopen('./config/config.inc.php', 'w');
        if ($config === FALSE) {
            message('error', 'Could not open config file for writing! Bad permissions?');
            break;
        }
        $s = get_cfg_string($configuration);
        $r = fwrite($config, $s);
        if (!$r || $r != strlen($s)) {
            message('error', 'Could not write to config file! Not enough space?');
            break;
        } else {
            message('notice', 'Configuration saved to file config/config.inc.php in phpMyAdmin top level directory, copy it to top level one and delete directory config to use it.', 'File saved');
        }
        unset($r, $s);
        fclose($config);
        break;
    case 'load':
        if ($fail_dir) {
            message('error', 'Reading of configuration disabled because of permissions.');
            break;
        }
        $new_cfg = load_config('./config/config.inc.php');
        if (!($new_cfg === FALSE)) {
            $configuration = $new_cfg;
        }
        $show_info = TRUE;
        break;

    case 'addserver_real':
        if (isset($_POST['submit_save'])) {
            $new_server = grab_values('host;extension;port;socket;connect_type;compress:bool;controluser;controlpass;auth_type;user;password;only_db;verbose;pmadb;bookmarktable:serialized;relation:serialized;table_info:serialized;table_coords:serialized;pdf_pages:serialized;column_info:serialized;designer_coords:serialized;history:serialized;AllowDeny:serialized;SignonSession;SignonURL;LogoutURL');
            $err = FALSE;
            if (empty($new_server['host'])) {
                message('error', 'Empty hostname!');
                $err = TRUE;
            }
            if ($new_server['auth_type'] == 'config' && empty($new_server['user'])) {
                message('error', 'Empty username while using config authentication method!');
                $err = TRUE;
            }
            if ($new_server['auth_type'] == 'signon' && empty($new_server['SignonSession'])) {
                message('error', 'Empty signon session name while using signon authentication method!');
                $err = TRUE;
            }
            if ($new_server['auth_type'] == 'signon' && empty($new_server['SignonURL'])) {
                message('error', 'Empty signon URL while using signon authentication method!');
                $err = TRUE;
            }
            if (isset($new_server['pmadb']) && strlen($new_server['pmadb'])) {
                // Just use defaults, should be okay for most users
                $pmadb = array();
                $pmadb['bookmarktable'] = 'pma_bookmark';
                $pmadb['relation']      = 'pma_relation';
                $pmadb['table_info']    = 'pma_table_info';
                $pmadb['table_coords']  = 'pma_table_coords';
                $pmadb['pdf_pages']     = 'pma_pdf_pages';
                $pmadb['column_info']   = 'pma_column_info';
                $pmadb['designer_coords'] = 'pma_designer_coords';
                $pmadb['history']       = 'pma_history';

                $new_server = array_merge($pmadb, $new_server);
                unset($pmadb);
                if (empty($new_server['controluser'])) {
                    message('error', 'Empty phpMyAdmin control user while using pmadb!');
                    $err = TRUE;
                }
                if (empty($new_server['controlpass'])) {
                    message('error', 'Empty phpMyAdmin control user password while using pmadb!');
                    $err = TRUE;
                }
                /* Check whether we can connect as control user */
                if (!empty($new_server['controluser']) && !empty($new_server['controlpass'])) {
                    if ($new_server['extension'] == 'mysql') {
                        $socket = empty($new_server['socket']) || $new_server['connect_type'] == 'tcp' ? '' : ':' . $new_server['socket'];
                        $port = empty($new_server['port']) || $new_server['connect_type'] == 'socket' ? '' : ':' . $new_server['port'];
                        $conn = @mysql_connect($new_server['host'] . $socket . $port, $new_server['controluser'], $new_server['controlpass']);
                        if ($conn === FALSE) {
                            message('error', 'Could not connect as control user!');
                            $err = TRUE;
                        } else {
                            mysql_close($conn);
                        }
                    } else {
                        $socket = empty($new_server['socket']) || $new_server['connect_type'] == 'tcp' ? NULL : $new_server['socket'];
                        $port = empty($new_server['port']) || $new_server['connect_type'] == 'socket' ? NULL : $new_server['port'];
                        $conn = @mysqli_connect($new_server['host'], $new_server['controluser'], $new_server['controlpass'], NULL, $port, $socket);
                        if ($conn === FALSE) {
                            message('error', 'Could not connect as control user!');
                            $err = TRUE;
                        } else {
                            mysqli_close($conn);
                        }
                    }
                }
            } else {
                message('warning', 'You didn\'t set phpMyAdmin database, so you can not use all phpMyAdmin features.');
            }
            if ($new_server['auth_type'] == 'config') {
                message('warning', 'Remember to protect your installation while using config authentication method!');
            } else {
                // Not needed:
                unset($new_server['user']);
                unset($new_server['password']);
            }
            if ($err) {
                show_server_form($new_server, isset($_POST['server']) ? $_POST['server'] : FALSE);
            } else {
                if (isset($_POST['server'])) {
                    $configuration['Servers'][$_POST['server']] = $new_server;
                    message('notice', 'Changed server ' . get_server_name($new_server, $_POST['server']));
                } else {
                    $configuration['Servers'][] = $new_server;
                    message('notice', 'New server added');
                }
                $show_info = TRUE;
                if ($new_server['auth_type'] == 'cookie' && empty($configuration['blowfish_secret'])) {
                    message('notice', 'You did not have configured blowfish secret and you want to use cookie authentication so I generated blowfish secret for you. It is used to encrypt cookies.', 'Blowfish secret generated');
                    $configuration['blowfish_secret'] = uniqid('', TRUE);
                }
            }
            unset($new_server);
        } else {
            $show_info = TRUE;
        }
        break;
    case 'addserver':
        if (count($configuration['Servers']) == 0) {
            // First server will use defaults as in config.default.php
            $defaults = $PMA_Config_Setup->default_server;
            unset($defaults['AllowDeny']); // Ignore this for now
        } else {
            $defaults = array();
        }

        // Guess MySQL extension to use, prefer mysqli
        if (!function_exists('mysql_get_client_info')) {
            PMA_dl('mysql');
        }
        if (!function_exists('mysqli_get_client_info')) {
            PMA_dl('mysqli');
        }
        if (function_exists('mysqli_get_client_info')) {
            $defaults['extension'] = 'mysqli';
        } elseif (function_exists('mysql_get_client_info')) {
            $defaults['extension'] = 'mysql';
        } else {
            message('warning', 'Could not load either mysql or mysqli extension, you might not be able to use phpMyAdmin! Check your PHP configuration.');
        }
        if (isset($defaults['extension'])) {
            message('notice', 'Autodetected MySQL extension to use: ' . $defaults['extension']);
        }

        // Display form
        show_server_form($defaults);
        break;
    case 'editserver':
        if (!isset($_POST['server'])) {
            footer();
        }
        show_server_form($configuration['Servers'][$_POST['server']], $_POST['server']);
        break;
    case 'deleteserver':
        if (!isset($_POST['server'])) {
            footer();
        }
        message('notice', 'Deleted server ' . get_server_name($configuration['Servers'][$_POST['server']], $_POST['server']));
        unset($configuration['Servers'][$_POST['server']]);
        compress_servers($configuration);
        $show_info = TRUE;
        break;
    case 'servers':
        if (count($configuration['Servers']) == 0) {
            message('notice', 'No servers defined, so none can be shown');
        } else {
            foreach ($configuration['Servers'] as $i => $srv) {
                $data = array();
                if (!empty($srv['verbose'])) {
                    $data[] = array('Verbose name', $srv['verbose']);
                }
                $data[] = array('Host', $srv['host']);
                $data[] = array('MySQL extension', isset($srv['extension']) ? $srv['extension'] : $PMA_Config_Setup->default_server['extension']);
                $data[] = array('Authentication type', get_server_auth($srv));
                $data[] = array('phpMyAdmin advanced features', empty($srv['pmadb']) || empty($srv['controluser']) || empty($srv['controlpass']) ? 'disabled' : 'enabled, db: ' . $srv['pmadb'] . ', user: ' . $srv['controluser']);
                $buttons =
                    get_action('deleteserver', 'Delete', '<input type="hidden" name="server" value="' . $i . '" />') .
                    get_action('editserver', 'Edit', '<input type="hidden" name="server" value="' . $i . '" />');
                show_overview('Server ' . get_server_name($srv, $i), $data, $buttons);
            }
        }
        break;

    case 'feat_upload_real':
        if (isset($_POST['submit_save'])) {
            $dirs = grab_values('UploadDir;SaveDir');
            $err = FALSE;
            if (!empty($dirs['UploadDir']) && !is_dir($dirs['UploadDir'])) {
                message('error', 'Upload directory ' . htmlspecialchars($dirs['UploadDir']) . ' does not exist!');
                $err = TRUE;
            }
            if (!empty($dirs['SaveDir']) && !is_dir($dirs['SaveDir'])) {
                message('error', 'Save directory ' . htmlspecialchars($dirs['SaveDir']) . ' does not exist!');
                $err = TRUE;
            }
            if ($err) {
                show_upload_form($dirs);
            } else {
                $configuration = array_merge($configuration, $dirs);
                message('notice', 'Configuration changed');
                $show_info = TRUE;
            }
        } else {
            $show_info = TRUE;
        }
        break;
    case 'feat_upload':
        show_upload_form($configuration);
        break;

    case 'feat_security_real':
        if (isset($_POST['submit_save'])) {
            $vals = grab_values('blowfish_secret;ForceSSL:bool;ShowPhpInfo:bool;ShowChgPassword:bool;AllowArbitraryServer:bool;LoginCookieRecall:book;LoginCookieValidity:int');
            $err = FALSE;
            if (empty($vals['blowfish_secret'])) {
                message('warning', 'Blowfish secret is empty, you will not be able to use cookie authentication.');
            }
            if ($vals['AllowArbitraryServer']) {
                message('warning', 'Arbitrary server connection might be dangerous as it might allow access to internal servers that are not reachable from outside.');
            }
            if (isset($vals['LoginCookieValidity']) && $vals['LoginCookieValidity'] < 1) {
                message('error', 'Invalid cookie validity time');
                $err = TRUE;
            }
            if ($err) {
                show_security_form($vals);
            } else {
                $configuration = array_merge($configuration, $vals);
                message('notice', 'Configuration changed');
                $show_info = TRUE;
            }
        } else {
            $show_info = TRUE;
        }
        break;
    case 'feat_security':
        show_security_form($configuration);
        break;

    case 'feat_manual_real':
        if (isset($_POST['submit_save'])) {
            $vals = grab_values('MySQLManualBase;MySQLManualType');
            $err = FALSE;
            if ($vals['MySQLManualType'] != 'none' && empty($vals['MySQLManualBase'])) {
                message('error', 'You need to set manual base URL or choose type \'none\'.');
                $err = TRUE;
            }
            if ($err) {
                show_manual_form($vals);
            } else {
                $configuration = array_merge($configuration, $vals);
                message('notice', 'Configuration changed');
                $show_info = TRUE;
            }
        } else {
            $show_info = TRUE;
        }
        break;
    case 'feat_manual':
        show_manual_form($configuration);
        break;

    case 'feat_charset_real':
        if (isset($_POST['submit_save'])) {
            $vals = grab_values('AllowAnywhereRecoding:bool;DefaultCharset;RecodingEngine;IconvExtraParams');
            $err = FALSE;
            if ($err) {
                show_charset_form($vals);
            } else {
                $configuration = array_merge($configuration, $vals);
                message('notice', 'Configuration changed');
                $show_info = TRUE;
            }
        } else {
            $show_info = TRUE;
        }
        break;
    case 'feat_charset':
        $d = $configuration;
        if (!isset($d['RecodingEngine'])) {
            if (@extension_loaded('iconv')) {
                $d['RecodingEngine']         = 'iconv';
            } elseif (@extension_loaded('recode')) {
                $d['RecodingEngine']         = 'recode';
            } else {
                PMA_dl('iconv');
                if (!@extension_loaded('iconv')) {
                    PMA_dl('recode');
                    if (!@extension_loaded('recode')) {
                        message('warning', 'Neither recode nor iconv could be loaded so charset conversion will most likely not work.');
                    } else {
                        $d['RecodingEngine'] = 'recode';
                    }
                } else {
                    $d['RecodingEngine']     = 'iconv';
                }
            }
            if (isset($d['RecodingEngine'])) {
                message('notice', 'Autodetected recoding engine: ' . $d['RecodingEngine']);
            }
        }
        show_charset_form($d);
        unset($d);
        break;

    case 'feat_extensions_real':
        if (isset($_POST['submit_save'])) {
            $vals = grab_values('GD2Available');
            $err = FALSE;
            if ($err) {
                show_extensions_form($vals);
            } else {
                $configuration = array_merge($configuration, $vals);
                message('notice', 'Configuration changed');
                $show_info = TRUE;
            }
        } else {
            $show_info = TRUE;
        }
        break;
    case 'feat_extensions':
        $d = $configuration;
        if (!@extension_loaded('mbstring')) {
            PMA_dl('mbstring');
        }
        if (!@extension_loaded('mbstring')) {
            message('warning', 'Could not load <code>mbstring</code> extension, which is required for work with multibyte strings like UTF-8 ones. Please consider installing it.');
        }
        if (!isset($d['GD2Available'])) {
            if (PMA_IS_GD2 == 1) {
                message('notice', 'GD 2 or newer found.');
                $d['GD2Available'] = 'yes';
            } else {
                message('warning', 'GD 2 or newer is not present.');
                $d['GD2Available'] = 'no';
            }
        }
        show_extensions_form($d);
        unset($d);
        break;

    case 'feat_relation_real':
        if (isset($_POST['submit_save'])) {
            $vals = grab_values('QueryHistoryDB:bool;QueryHistoryMax:int;BrowseMIME:bool;PDFDefaultPageSize');
            $err = FALSE;
            if (isset($vals['QueryHistoryMax']) && $vals['QueryHistoryMax'] < 1) {
                message('error', 'Invalid value for query maximum history size!');
                $err = TRUE;
            }
            if ($err) {
                show_relation_form($vals);
            } else {
                $configuration = array_merge($configuration, $vals);
                message('notice', 'Configuration changed');
                $show_info = TRUE;
            }
        } else {
            $show_info = TRUE;
        }
        break;
    case 'feat_relation':
        show_relation_form($configuration);
        break;

    case 'lay_navigation_real':
        if (isset($_POST['submit_save'])) {
            $vals = grab_values('LeftFrameLight:bool;LeftFrameDBTree:bool;LeftFrameDBSeparator;LeftFrameTableSeparator;LeftFrameTableLevel:int;LeftDisplayLogo:bool;LeftDisplayServers:bool;DisplayServersList:bool;DisplayDatabasesList;LeftPointerEnable:bool');
            $err = FALSE;
            if (isset($vals['DisplayDatabasesList'])) {
                if ($vals['DisplayDatabasesList'] == 'yes') {
                    $vals['DisplayDatabasesList'] = true;
                } elseif ($vals['DisplayDatabasesList'] == 'no') {
                    $vals['DisplayDatabasesList'] = false;
                }
            }
            if (isset($vals['LeftFrameTableLevel']) && $vals['LeftFrameTableLevel'] < 1) {
                message('error', 'Invalid value for maximum table nesting level!');
                $err = TRUE;
            }
            if ($err) {
                show_left_form($vals);
            } else {
                $configuration = array_merge($configuration, $vals);
                message('notice', 'Configuration changed');
                $show_info = TRUE;
            }
        } else {
            $show_info = TRUE;
        }
        break;
    case 'lay_navigation':
        show_left_form($configuration);
        break;

    case 'lay_tabs_real':
        if (isset($_POST['submit_save'])) {
            $vals = grab_values('DefaultTabServer;DefaultTabDatabase;DefaultTabTable;LightTabs:bool');
            $err = FALSE;
            if ($err) {
                show_tabs_form($vals);
            } else {
                $configuration = array_merge($configuration, $vals);
                message('notice', 'Configuration changed');
                $show_info = TRUE;
            }
        } else {
            $show_info = TRUE;
        }
        break;
    case 'lay_tabs':
        show_tabs_form($configuration);
        break;

    case 'lay_icons_real':
        if (isset($_POST['submit_save'])) {
            $vals = grab_values('ErrorIconic:bool;MainPageIconic:bool;ReplaceHelpImg:bool;NavigationBarIconic:tristate;PropertiesIconic:tristate');
            $err = FALSE;
            if ($err) {
                show_icons_form($vals);
            } else {
                $configuration = array_merge($configuration, $vals);
                message('notice', 'Configuration changed');
                $show_info = TRUE;
            }
        } else {
            $show_info = TRUE;
        }
        break;
    case 'lay_icons':
        show_icons_form($configuration);
        break;

    case 'lay_browse_real':
        if (isset($_POST['submit_save'])) {
            $vals = grab_values('BrowsePointerEnable:bool;BrowseMarkerEnable:bool;ModifyDeleteAtRight:bool;ModifyDeleteAtLeft:bool;RepeatCells:int;DefaultDisplay');
            $err = FALSE;
            if (isset($vals['RepeatCells']) && $vals['RepeatCells'] < 1) {
                message('error', 'Invalid value for header repeating!');
                $err = TRUE;
            }
            if (!$vals['ModifyDeleteAtLeft'] && !$vals['ModifyDeleteAtRight']) {
                message('error', 'No action buttons enabled!');
                $err = TRUE;
            }
            if ($err) {
                show_browse_form($vals);
            } else {
                $configuration = array_merge($configuration, $vals);
                message('notice', 'Configuration changed');
                $show_info = TRUE;
            }
        } else {
            $show_info = TRUE;
        }
        break;
    case 'lay_browse':
        show_browse_form($configuration);
        break;

    case 'lay_edit_real':
        if (isset($_POST['submit_save'])) {
            $vals = grab_values('TextareaCols:int;TextareaRows:int;LongtextDoubleTextarea:bool;TextareaAutoSelect:bool;CharEditing;CharTextareaCols:int;CharTextareaRows:int;CtrlArrowsMoving:bool;DefaultPropDisplay;InsertRows:int');
            $err = FALSE;
            if (isset($vals['TextareaCols']) && $vals['TextareaCols'] < 1) {
                message('error', 'Invalid value for textarea columns!');
                $err = TRUE;
            }
            if (isset($vals['TextareaRows']) && $vals['TextareaRows'] < 1) {
                message('error', 'Invalid value for textarea rows!');
                $err = TRUE;
            }
            if (isset($vals['CharTextareaCols']) && $vals['CharTextareaCols'] < 1) {
                message('error', 'Invalid value for CHAR textarea columns!');
                $err = TRUE;
            }
            if (isset($vals['CharTextareaRows']) && $vals['CharTextareaRows'] < 1) {
                message('error', 'Invalid value for CHAR textarea rows!');
                $err = TRUE;
            }
            if (isset($vals['InsertRows']) && $vals['InsertRows'] < 1) {
                message('error', 'Invalid value for inserted rows count!');
                $err = TRUE;
            }
            if ($err) {
                show_edit_form($vals);
            } else {
                $configuration = array_merge($configuration, $vals);
                message('notice', 'Configuration changed');
                $show_info = TRUE;
            }
        } else {
            $show_info = TRUE;
        }
        break;
    case 'lay_edit':
        show_edit_form($configuration);
        break;

    case 'lay_window_real':
        if (isset($_POST['submit_save'])) {
            $vals = grab_values('EditInWindow:bool;QueryWindowHeight:int;QueryWindowWidth:int;QueryWindowDefTab');
            $err = FALSE;
            if (isset($vals['QueryWindowWidth']) && $vals['QueryWindowWidth'] < 1) {
                message('error', 'Invalid value for query window width!');
                $err = TRUE;
            }
            if (isset($vals['QueryWindowHeight']) && $vals['QueryWindowHeight'] < 1) {
                message('error', 'Invalid value for query window height');
                $err = TRUE;
            }
            if ($err) {
                show_window_form($vals);
            } else {
                $configuration = array_merge($configuration, $vals);
                message('notice', 'Configuration changed');
                $show_info = TRUE;
            }
        } else {
            $show_info = TRUE;
        }
        break;
    case 'lay_window':
        show_window_form($configuration);
        break;

/* Template for new actions:
    case 'blah_real':
        if (isset($_POST['submit_save'])) {
            $vals = grab_values('value1:bool;value2');
            $err = FALSE;
            if (somechekcfails) {
                message('error', 'Invalid value for blah!');
                $err = TRUE;
            }
            if ($err) {
                show_blah_form($vals);
            } else {
                $configuration = array_merge($configuration, $vals);
                message('notice', 'Configuration changed');
                $show_info = TRUE;
            }
        } else {
            $show_info = TRUE;
        }
        break;
    case 'blah':
        show_blah_form($configuration);
        break;
*/
    case 'versioncheck': // Check for latest available version
        PMA_dl('curl');
        $url = 'http://phpmyadmin.net/home_page/version.php';
        $data = '';
        $f = @fopen($url, 'r');
        if ($f === FALSE) {
            if (!function_exists('curl_init')) {
                message('error', 'Neither URL wrappers nor CURL are available. Version check is not possible.');
                break;
            }
        } else {
            $data = fread($f, 20);
            fclose($f);
        }
        if (empty($data) && function_exists('curl_init')) {
            $ch = curl_init($url);
            curl_setopt($ch, CURLOPT_HEADER, FALSE);
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, TRUE);
            $data = curl_exec($ch);
            curl_close($ch);
        }
        if (empty($data)) {
            message('error', 'Reading of version failed. Maybe you\'re offline or the upgrade server does not respond.');
            break;
        }

        /* Format: version\ndate\n(download\n)* */
        $data_list = split("\n", $data);

        if (count($data_list) > 0) {
            $version = $data_list[0];
        } else {
            $version = '';
        }

        $version_upstream = version_to_int($version);
        if ($version_upstream === FALSE) {
            message('error', 'Got invalid version string from server.');
            break;
        }

        $version_local = version_to_int($PMA_Config_Setup->get('PMA_VERSION'));
        if ($version_local === FALSE) {
            message('error', 'Unparsable version string.');
            break;
        }

        if ($version_upstream > $version_local) {
            message('notice', 'New version of phpMyAdmin is available, you should consider upgrade. New version is ' . htmlspecialchars($version) . '.');
        } else {
            if ($version_local % 100 == 0) {
                message('notice', 'You are using subversion version, run <code>svn update</code> :-). However latest released version is ' . htmlspecialchars($version) . '.');
            } else {
                message('notice', 'No newer stable version is available.');
            }
        }
        break;

    case 'seteol':
        $eoltype = $_POST['neweol'];
        message('notice', 'End of line format changed.');
    case 'clear': // Actual clearing is done on beginning of this script
    case 'main':
        $show_info = TRUE;
        break;

    case '':
        message('notice', 'You want to configure phpMyAdmin using web interface. Please note that this only allows basic setup, please read <a href="../Documentation.html#config">documentation</a> to see full description of all configuration directives.', 'Welcome');

        if ($fail_dir) {
            message('warning', 'Please create web server writable folder config in phpMyAdmin toplevel directory as described in <a href="../Documentation.html#setup_script">documentation</a>. Otherwise you will be only able to download or display it.', 'Can not load or save configuration');
        }

        if (empty($_SERVER['HTTPS']) || strtolower($_SERVER['HTTPS']) == 'off') {
            if (empty($_SERVER['REQUEST_URI']) || empty($_SERVER['HTTP_HOST'])) {
                $redir = '';
            } else {
                $redir = ' If your server is also configured to accept HTTPS request'
                    . ' follow <a href="https://'
                    . htmlspecialchars($_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'])
                    . '">this link</a> to use secure connection.';
            }
            message('warning', 'You are not using secure connection, all data (including sensitive, like passwords) are transfered unencrypted!' . $redir, 'Not secure connection');
        }
        break;
}

// Should we show information?
if ($show_info) {
    $servers = 'none';
    $servers_text = 'Servers';
    if (count($configuration['Servers']) == 0) {
        message('warning', 'No servers defined, you probably want to add one.');
    } else {
        $servers = '';
        $servers_text = 'Servers (' . count($configuration['Servers']) . ')';

        $sep = '';
        foreach ($configuration['Servers'] as $key => $val) {
            $servers .= $sep;
            $sep = ', ';
            $servers .= get_server_name($val, $key);
        }
        unset($sep);
    }
    show_overview('Current configuration overview',
        array(
            array($servers_text, $servers),
            array('SQL files upload', empty($configuration['UploadDir']) ? 'disabled' : 'enabled'),
            array('Exported files on server', empty($configuration['SaveDir']) ? 'disabled' : 'enabled'),
            array('Charset conversion', isset($configuration['AllowAnywhereRecoding']) && $configuration['AllowAnywhereRecoding'] ? 'enabled' : 'disabled'),
        ));
    unset($servers_text, $servers);
}

// And finally display all actions:
echo '<p>Available global actions (please note that these will delete any changes you could have done above):</p>';

echo '<fieldset class="toolbar"><legend>Servers</legend>' . "\n";
echo get_action('addserver', 'Add');
$servers = get_server_selection($configuration);
if (!empty($servers)) {
    echo get_action('servers', 'List');
    echo get_action('deleteserver', 'Delete', $servers);
    echo get_action('editserver', 'Edit', $servers);
}
echo '</fieldset>' . "\n\n";

echo '<fieldset class="toolbar"><legend>Layout</legend>' . "\n";
echo get_action('lay_navigation', 'Navigation frame');
echo get_action('lay_tabs', 'Tabs');
echo get_action('lay_icons', 'Icons');
echo get_action('lay_browse', 'Browsing');
echo get_action('lay_edit', 'Editing');
echo get_action('lay_window', 'Query window');
echo '</fieldset>' . "\n\n";

echo '<fieldset class="toolbar"><legend>Features</legend>' . "\n";
echo get_action('feat_upload', 'Upload/Download');
echo get_action('feat_security', 'Security');
echo get_action('feat_manual', 'MySQL manual');
echo get_action('feat_charset', 'Charsets');
echo get_action('feat_extensions', 'Extensions');
echo get_action('feat_relation', 'MIME/Relation/History');
echo '</fieldset>' . "\n\n";

echo '<fieldset class="toolbar"><legend>Configuration</legend>' . "\n";
echo get_action('main', 'Overview');
echo get_action('display', 'Display');
echo get_action('download', 'Download');
echo get_action('save', 'Save', '', !$fail_dir);
echo get_action('load', 'Load', '', !$fail_dir);
echo get_action('clear', 'Clear');
echo get_action('seteol', 'Change end of line',
        '<select name="neweol">' .
        '<option value="unix" ' . ($eoltype == 'unix' ? ' selected="selected"' : '') . '>UNIX/Linux (\\n)</option>' .
        '<option value="dos" ' . ($eoltype == 'dos' ? ' selected="selected"' : '') . '>DOS/Windows (\\r\\n)</option>' .
        '<option value="mac" ' . ($eoltype == 'mac' ? ' selected="selected"' : '') . '>Macintosh (\\r)</option>' . '
        </select>');
echo '</fieldset>' . "\n\n";

echo '<fieldset class="toolbar"><legend>Other actions</legend>' . "\n";
echo get_action('versioncheck', 'Check for latest version');
echo get_url_action('http://www.phpmyadmin.net/', 'Go to homepage');
echo get_url_action('https://sourceforge.net/donate/index.php', 'Donate to phpMyAdmin', array('group_id' => 23067));
echo '</fieldset>' . "\n\n";

footer();
?>
