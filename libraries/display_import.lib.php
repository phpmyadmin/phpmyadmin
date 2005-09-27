<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

require_once('./libraries/file_listing.php');

/* Scan for plugins */
$import_list = array();
$plugins_dir = './libraries/import/';
if ($handle = @opendir($plugins_dir)) {
    $is_first = 0;
    while ($file = @readdir($handle)) {
        if (is_file($plugins_dir . $file) && eregi('\.php$', $file)) {
            include($plugins_dir . $file);
        }
    }
}
ksort($import_list);

/* Fail if we didn't find any plugin */
if (empty($import_list)) {
    $GLOBALS['show_error_header'] = TRUE;
    PMA_showMessage($strCanNotLoadImportPlugins);
    unset($GLOBALS['show_error_header']);
    require('./footer.inc.php');
}

function PMA_getString($name) {
    return isset($GLOBALS[$name]) ? $GLOBALS[$name] : $name;
}

function PMA_importCheckboxCheck($opt) {
    if ((isset($GLOBALS['timeout_passed']) && $GLOBALS['timeout_passed'] && isset($GLOBALS[$opt])) ||
        (isset($GLOBALS['cfg']['Import'][$opt]) && $GLOBALS['cfg']['Import'][$opt])) {
        return ' checked="checked"';
    }
    return '';
}

function PMA_importGetDefault($opt) {
    if (isset($GLOBALS['timeout_passed']) && $GLOBALS['timeout_passed'] && isset($GLOBALS[$opt])) {
        return htmlspecialchars($GLOBALS[$opt]);
    } elseif (isset($GLOBALS['cfg']['Import'][$opt])) {
        return htmlspecialchars($GLOBALS['cfg']['Import'][$opt]);
    }
    return '';
}

function PMA_importIsActive($what, $val) {
    if (isset($GLOBALS['timeout_passed']) && $GLOBALS['timeout_passed'] && isset($GLOBALS['what'])) {
        if ($GLOBALS['what'] == $val) {
            return ' checked="checked"';
        }
    } elseif (isset($GLOBALS['cfg']['Import'][$what]) &&  $GLOBALS['cfg']['Import'][$what] == $val) {
        return ' checked="checked"';
    }
    return '';
}

?>

<form action="import.php" method="post" enctype="multipart/form-data" name="import">
<?php 
if ($import_type == 'server') {
    echo PMA_generate_common_hidden_inputs('', '', 1);
} elseif ($import_type == 'database') {
    echo PMA_generate_common_hidden_inputs($db, '', 1);
} else {
    echo PMA_generate_common_hidden_inputs($db, $table, 1);
}
echo '    <input type="hidden" name="import_type" value="' . $import_type . '" />';
?>

    <script type="text/javascript">
    <!--
    function hide_them_all() {
        <?php 
        foreach($import_list as $key => $val) {
            if (isset($val['options'])) {
                echo 'getElement("' . $key . '_options").style.display = "none";';
            }
        }?>
        getElement("none_options").style.display = 'none';
    }

    function init_options() {
        hide_them_all();
        <?php 
        foreach($import_list as $key => $val) {
            echo 'if (document.getElementById("radio_import_' . $key . '").checked) {';
            if (isset($val['options'])) {
                echo 'getElement("' . $key . '_options").style.display = "block";';
            } else {
                echo 'getElement("none_options").style.display = "block";';
            }
            echo ' } else ';
        }
        ?>
        {
            getElement('none_options').style.display = 'block';
        }
    }
    
    function match_file(fname) {
        farr = fname.toLowerCase().split('.');
        if (farr.length != 0) {
            len = farr.length
            if (farr[len - 1] == 'gz' || farr[len - 1] == 'bz2' || farr[len -1] == 'zip') len--;
            switch (farr[len - 1]) {
<?php
foreach($import_list as $key => $val) {
    echo 'case "' . $val['extension'] . '" :';
    echo 'document.getElementById("radio_import_' . $key . '").checked = true;';
    echo 'init_options();';
    echo 'break;';
}
?>
            }
        }
    }
    //-->
    </script>
    
    <h2><?php echo $strImport; ?></h2>

    <!-- File name, and some other common options -->
    <fieldset class="options">
        <legend>
            <?php echo $strFileToImport; ?>
        </legend>
        
        <label for="input_import_file"><?php echo $strLocationTextfile; ?></label>
        <input style="margin: 5px" type="file" name="import_file" id="input_import_file" onchange="match_file(this.value);" />
<?php                
echo PMA_displayMaximumUploadSize($max_upload_size);
// some browsers should respect this :)
echo PMA_generateHiddenMaxFileSize($max_upload_size) . "\n";

if (!empty($cfg['UploadDir'])) {
    $extensions = '';
    foreach($import_list as $key => $val) {
        if (!empty($extensions)) $extensions .= '|';
        $extensions .= $val['extension'];
    }
    $matcher = '@\.(' . $extensions . ')(\.(' . PMA_supportedDecompressions() . '))?$@';

    $files = PMA_getFileSelectOptions($cfg['UploadDir'], $matcher, (isset($timeout_passed) && $timeout_passed && isset($local_import_file)) ? $local_import_file : '');
    
    if ($files === FALSE) {
        echo '    <div style="margin-bottom: 5px">' . "\n";
        echo '        <font color="red">' . $strError . '</font><br />' . "\n";
        echo '        ' . $strWebServerUploadDirectoryError . "\n";
        echo '    </div>' . "\n";
    } elseif (!empty($files)) {
        echo "<br />\n";
        echo '    <i>' . $strOr . '</i><br/><label for="select_local_import_file">' . $strWebServerUploadDirectory . '</label>&nbsp;: ' . "\n";
        echo '    <select style="margin: 5px" size="1" name="local_import_file" onchange="match_file(this.value)" id="select_local_import_file">' . "\n";
        echo '        <option value=""></option>' . "\n";
        echo $files;
        echo '    </select>' . "\n";
    }
} // end if (web-server upload directory)

// charset of file
if (PMA_MYSQL_INT_VERSION < 40100 && $cfg['AllowAnywhereRecoding'] && $allow_recoding) {
    echo '<br /><br /><label for="charset_of_file">' . $strCharsetOfFile . '</label>';
    echo "\n";
    $temp_charset = reset($cfg['AvailableCharsets']);
    echo '&nbsp;' . $strCharsetOfFile . "\n"
         . '    <select name="charset_of_file" size="1">' . "\n"
         . '            <option value="' . $temp_charset . '"';
    if ($temp_charset == $charset) {
        echo ' selected="selected"';
    }
    echo '>' . $temp_charset . '</option>' . "\n";
    while ($temp_charset = next($cfg['AvailableCharsets'])) {
        echo '            <option value="' . $temp_charset . '"';
        if ($temp_charset == $charset) {
            echo ' selected="selected"';
        }
        echo '>' . $temp_charset . '</option>' . "\n";
    }
    echo '        </select><br />' . "\n" . '    ';
} elseif (PMA_MYSQL_INT_VERSION >= 40100) {
    echo '<br /><br /><label for="charset_of_file">' . $strCharsetOfFile . '</label>';
    echo "\n";
    echo PMA_generateCharsetDropdownBox(PMA_CSDROPDOWN_CHARSET, 'charset_of_file', NULL, 'utf8', FALSE);
} // end if (recoding)
echo "\n";

// zip, gzip and bzip2 encode features
$compressions = $strNone;

if ($cfg['GZipDump'] && @function_exists('gzopen')) $compressions .= ', gzip';
if ($cfg['BZipDump'] && @function_exists('bzopen')) $compressions .= ', bzip2';
if ($cfg['ZipDump'] && @function_exists('gzinflate')) $compressions .= ', zip';

// We don't have show anything about compression, when no supported
if ($compressions != $strNone) {
    printf('<br/>' . $strCompressionWillBeDetected, $compressions);
}
echo "\n";
?>
    </fieldset>
    <fieldset class="options">
        <legend>
            <?php echo $strPartialImport; ?>
        </legend>
        <?php 
        if (isset($timeout_passed) && $timeout_passed) {
            echo '<input type="hidden" name="skip" value="' . $offset . '" />';
            echo sprintf($strTimeoutInfo, $offset) . '<br />';
        }
        ?>
        <input type="checkbox" name="allow_interrupt" value="yes" id="checkbox_allow_interrupt" <?php echo PMA_importCheckboxCheck('allow_interrupt'); ?>/><label for="checkbox_allow_interrupt"><?php echo $strAllowInterrupt; ?></label><br />

        <label for="text_skip_queries" style="float: left; width: 20%;"><?php echo $strSkipQueries; ?></label>
        <input type="text" name="skip_queries" value="<?php echo PMA_importGetDefault('skip_queries');?>" id="text_skip_queries" />

    </fieldset>
    <fieldset style="float: left;" class="options">
        <legend><?php echo $strImportFormat; ?></legend>
<?php
// Let's show format options now

foreach($import_list as $key => $val) {
?>
            <!-- <?php echo $key; ?> -->
            <input type="radio" name="what" value="<?php echo $key; ?>" id="radio_import_<?php echo $key; ?>" onclick="if(this.checked) { hide_them_all(); getElement('<?php echo isset($val['options']) ? $key : 'none';?>_options').style.display = 'block'; }; return true" <?php echo PMA_importIsActive('format', $key); ?>/>
            <label for="radio_import_<?php echo $key; ?>"><?php echo PMA_getString($val['text']); ?></label>
            <br /><br />
<?php
}
?>
    </fieldset>
<?php

// Options for imports that support them
foreach($import_list as $key => $val) {
    if (isset($val['options'])) {
        echo '<fieldset id="' . $key . '_options" class="options">';
        echo '<legend>' . PMA_getString($val['options_text']) . '</legend>';

        foreach($val['options'] as $id => $opt) {
            if ($opt['type'] == 'bool') {
                echo '<input type="checkbox" name="' . $key . '_' . $opt['name'] . '" value="something" id="checkbox_' . $key . '_' . $opt['name'] . '" ' . PMA_importCheckboxCheck($key . '_' . $opt['name']) .'/>';
                echo '<label for="checkbox_' . $key . '_' . $opt['name'] . '">' . PMA_getString($opt['text']) . '</label>';
            } elseif ($opt['type'] == 'text') {
                echo '<label for="text_' . $key . '_' . $opt['name'] . '" style="float: left; width: 20%;">' . PMA_getString($opt['text']) . '</label>';
                echo '<input type="text" name="' . $key . '_' . $opt['name'] . '" value="' . PMA_importGetDefault($key . '_' . $opt['name']) . '" id="text_' . $key . '_' . $opt['name'] . '" ' . (isset($opt['size']) ? 'size="' . $opt['size'] . '"' : '' )  . (isset($opt['len']) ? 'maxlength="' . $opt['len'] . '"' : '' ) . '/>';
            } else {
                /* This should be seen only by plugin writers, so I do not thing this needs translation. */
                echo 'UNKNOWN OPTION IN IMPORT PLUGIN ' . $key . '!';
            }
            echo '<br />';
        }
        
        echo '</fieldset>';
    }
}

    
?>
    <fieldset id="none_options" class="options">
        <legend><?php echo $strNoOptions; ?></legend>
    </fieldset>
<?php
// Encoding setting form appended by Y.Kawada
if (function_exists('PMA_set_enc_form')) {
    echo PMA_set_enc_form('            ');
}
echo "\n";
?>
    <input type="submit" value="<?php echo $strGo; ?>" id="buttonGo" style="clear: both; display: block;"/>
</form>
<script type="text/javascript">
<!--
    init_options();
//-->
</script>
