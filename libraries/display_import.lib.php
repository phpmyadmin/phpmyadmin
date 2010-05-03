<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 *
 */
require_once './libraries/file_listing.php';
require_once './libraries/plugin_interface.lib.php';
require_once './libraries/display_import_ajax.lib.php';

/* Scan for plugins */
$import_list = PMA_getPlugins('./libraries/import/', $import_type);

/* Fail if we didn't find any plugin */
if (empty($import_list)) {
    PMA_Message::error('strCanNotLoadImportPlugins')->display();
    require './libraries/footer.inc.php';
}
?>

<iframe id="import_upload_iframe" name="import_upload_iframe" width="1" height="1" style="display: none"></iframe>
<div id="import_form_status" style="display: none;"></div>
<div id="importmain">
    <img src="<?php echo $GLOBALS['pmaThemeImage'];?>ajax_clock_small.gif" alt="ajax clock" style="display: none;" />
    <script type="text/javascript">
        //<![CDATA[
        $(document).ready( function() {
            // add event when user click on "Go" button
            $('#buttonGo').bind('click', function() {
                $('#upload_form_form').css("display", "none"); // hide form
                $('#upload_form_status').css("display", "inline"); // show progress bar
                $('#upload_form_status_info').css("display", "inline"); // - || -
<?php
if ($_SESSION[$SESSION_KEY]["handler"]!="noplugin") {
    ?>
                $('#upload_form_status').html('<div class="upload_progress_bar_outer"><div id="status" class="upload_progress_bar_inner"></div></div>'); // add the progress bar

                var finished = false;
                var percent  = 0.0;
                var total    = 0;
                var complete = 0;

                var perform_upload;
                var periodical_upload;

                var request_upload = [];

                perform_upload = function () {
                    new $.getJSON(
                    'import_status.php?id=<?php echo $upload_id ; ?>&<?php echo PMA_generate_common_url(); ?>',
                    {},
                    function(response) {
                        finished = response.finished;
                        percent = response.percent;
                        total = response.total;
                        complete = response.complete;

                          if (total==0 && complete==0 && percent==0) {
                              $('#upload_form_status_info').html('<img src="<?php echo $GLOBALS['pmaThemeImage'];?>ajax_clock_small.gif" alt="ajax clock" /> <?php echo PMA_jsFormat(__('The file being uploaded is probably larger than the maximum allowed size or this is a known bug in webkit based (Safari, Google Chrome, Arora etc.) browsers.')); ?>');
                              $('#upload_form_status').css("display", "none");
                          } else {
                              $('#upload_form_status_info').html(' '+Math.round(percent)+'%, '+complete+'/'+total);
                              $('#status').animate({width: Math.round(percent)*2+'px'},150);
                          } // else

                          if (finished==true) {
                                    $('#importmain').css('display', 'none');
                                    $('#import_form_status').css('display', 'inline');
                                    $('#import_form_status').html('<img src="<?php echo $GLOBALS['pmaThemeImage'];?>ajax_clock_small.gif" alt="ajax clock" /> <?php echo __('The file is being processed, please be patient.'); ?> ');
                                    $('#import_form_status').load('import_status.php?message=true&<?php echo PMA_generate_common_url(); ?>'); // loads the message, either success or mysql error
                                    <?php
                                    // reload the left sidebar when the import is finished
                                    $GLOBALS['reload']=true;
                                    PMA_reloadNavigation(true);
                                    ?>

                          } // if finished
                          else {
                                window.setTimeout(perform_upload, 1000);
                          }

                      }
                  );

              }
                window.setTimeout(perform_upload, 1000);

    <?php
} else { // no plugin avaliable
    ?>
                        $('#upload_form_status_info').html('<img src="<?php echo $GLOBALS['pmaThemeImage'];?>ajax_clock_small.gif" alt="ajax clock" /> <?php echo __('Please be patient, the file is being uploaded. Details about the upload are not available.') . PMA_showDocu('faq2_9'); ?>');
                        $('#upload_form_status').css("display", "none");
    <?php
} // else
?>
                    }); // onclick
                }); // domready

                document.write('<form action="import.php" method="post" enctype="multipart/form-data" name="import"<?php if ($_SESSION[$SESSION_KEY]["handler"]!="noplugin") echo ' target="import_upload_iframe"'; ?>>');
                //]]>
    </script>
    <noscript>
        <form action="import.php" method="post" enctype="multipart/form-data" name="import">
    </noscript>
    <input type="hidden" name="<?php echo $ID_KEY; ?>" value="<?php echo $upload_id ; ?>" />
    <?php
    if ($import_type == 'server') {
        echo PMA_generate_common_hidden_inputs('', '', 1);
    } elseif ($import_type == 'database') {
        echo PMA_generate_common_hidden_inputs($db, '', 1);
    } else {
        echo PMA_generate_common_hidden_inputs($db, $table, 1);
    }
    echo '    <input type="hidden" name="import_type" value="' . $import_type . '" />'."\n";
    echo PMA_pluginGetJavascript($import_list);
    ?>
    <fieldset class="options">
        <legend><?php echo __('File to import'); ?></legend>

        <?php

        if ($GLOBALS['is_upload']) {
            $uid = uniqid("");
            ?>
        <div class="formelementrow" id="upload_form">
            <div id="upload_form_status" style="display: none;"></div>
            <div id="upload_form_status_info" style="display: none;"></div>
            <div id="upload_form_form">
                <label for="input_import_file"><?php echo __('Location of the text file'); ?></label>
                <input style="margin: 5px" type="file" name="import_file" id="input_import_file" onchange="match_file(this.value);" />
                    <?php
                    echo PMA_displayMaximumUploadSize($max_upload_size) . "\n";
                    // some browsers should respect this :)
                    echo PMA_generateHiddenMaxFileSize($max_upload_size) . "\n";
                    ?>
            </div>
        </div>
            <?php
        } else {
            PMA_Message::warning('strUploadsNotAllowed')->display();
        }
        if (!empty($cfg['UploadDir'])) {
            $extensions = '';
            foreach ($import_list as $key => $val) {
                if (!empty($extensions)) {
                    $extensions .= '|';
                }
                $extensions .= $val['extension'];
            }
            $matcher = '@\.(' . $extensions . ')(\.(' . PMA_supportedDecompressions() . '))?$@';

            $files = PMA_getFileSelectOptions(PMA_userDir($cfg['UploadDir']), $matcher, (isset($timeout_passed) && $timeout_passed && isset($local_import_file)) ? $local_import_file : '');
            echo '<div class="formelementrow">' . "\n";
            if ($files === FALSE) {
                PMA_Message::error('strWebServerUploadDirectoryError')->display();
            } elseif (!empty($files)) {
                echo "\n";
                echo '    <i>' . __('Or') . '</i><br/><label for="select_local_import_file">' . __('web server upload directory') . '</label>&nbsp;: ' . "\n";
                echo '    <select style="margin: 5px" size="1" name="local_import_file" onchange="match_file(this.value)" id="select_local_import_file">' . "\n";
                echo '        <option value="">&nbsp;</option>' . "\n";
                echo $files;
                echo '    </select>' . "\n";
            }
            echo '</div>' . "\n";
        } // end if (web-server upload directory)

// charset of file
        echo '<div class="formelementrow">' . "\n";
        if ($cfg['AllowAnywhereRecoding']) {
            echo '<label for="charset_of_file">' . __('Character set of the file:') . '</label>';
            reset($cfg['AvailableCharsets']);
            echo '<select id="charset_of_file" name="charset_of_file" size="1">';
            foreach ($cfg['AvailableCharsets'] as $temp_charset) {
                echo '<option value="' . htmlentities($temp_charset) .  '"';
                if ((empty($cfg['Import']['charset']) && $temp_charset == $charset)
                        || $temp_charset == $cfg['Import']['charset']) {
                    echo ' selected="selected"';
                }
                echo '>' . htmlentities($temp_charset) . '</option>';
            }
            echo ' </select><br />';
        } else {
            echo '<label for="charset_of_file">' . __('Character set of the file:') . '</label>' . "\n";
            echo PMA_generateCharsetDropdownBox(PMA_CSDROPDOWN_CHARSET, 'charset_of_file', 'charset_of_file', 'utf8', FALSE);
        } // end if (recoding)
        echo '</div>' . "\n";

// zip, gzip and bzip2 encode features
        $compressions = __('None');

        if ($cfg['GZipDump'] && @function_exists('gzopen')) {
            $compressions .= ', gzip';
        }
        if ($cfg['BZipDump'] && @function_exists('bzopen')) {
            $compressions .= ', bzip2';
        }
        if ($cfg['ZipDump'] && @function_exists('zip_open')) {
            $compressions .= ', zip';
        }

// We don't have show anything about compression, when no supported
        if ($compressions != __('None')) {
            echo '<div class="formelementrow">' . "\n";
            printf(__('Imported file compression will be automatically detected from: %s'), $compressions);
            echo '</div>' . "\n";
        }
        echo "\n";
        ?>
    </fieldset>
    <fieldset class="options">
        <legend><?php echo __('Partial import'); ?></legend>

        <?php
        if (isset($timeout_passed) && $timeout_passed) {
            echo '<div class="formelementrow">' . "\n";
            echo '<input type="hidden" name="skip" value="' . $offset . '" />';
            echo sprintf(__('Previous import timed out, after resubmitting will continue from position %d.'), $offset) . '';
            echo '</div>' . "\n";
        }
        ?>
        <div class="formelementrow">
            <input type="checkbox" name="allow_interrupt" value="yes"
                   id="checkbox_allow_interrupt" <?php echo PMA_pluginCheckboxCheck('Import', 'allow_interrupt'); ?>/>
            <label for="checkbox_allow_interrupt"><?php echo __('Allow the interruption of an import in case the script detects it is close to the PHP timeout limit. This might be good way to import large files, however it can break transactions.'); ?></label><br />
        </div>

        <?php
        if (! (isset($timeout_passed) && $timeout_passed)) {
            ?>
        <div class="formelementrow">
            <label for="text_skip_queries"><?php echo __('Number of records (queries) to skip from start'); ?></label>
            <input type="text" name="skip_queries" value="<?php echo PMA_pluginGetDefault('Import', 'skip_queries');?>" id="text_skip_queries" />
        </div>
            <?php
        } else {
            // If timeout has passed,
            // do not show the Skip dialog to avoid the risk of someone
            // entering a value here that would interfere with "skip"
            ?>
        <input type="hidden" name="skip_queries" value="<?php echo PMA_pluginGetDefault('Import', 'skip_queries');?>" id="text_skip_queries" />
            <?php
        }
        ?>
    </fieldset>

    <fieldset class="options">
        <legend><?php echo __('Format of imported file'); ?></legend>
        <?php
// Let's show format options now
        echo '<div style="float: left;">';
        echo PMA_pluginGetChoice('Import', 'format', $import_list);
        echo '</div>';

        echo '<div style="float: left;">';
        echo PMA_pluginGetOptions('Import', $import_list);
        echo '</div>';
        ?>
        <div class="clearfloat"></div>
    </fieldset>
    <?php
// Encoding setting form appended by Y.Kawada
    if (function_exists('PMA_set_enc_form')) {
        echo PMA_set_enc_form('            ');
    }
    echo "\n";
    ?>
    <fieldset class="tblFooters">
        <input type="submit" value="<?php echo __('Go'); ?>" id="buttonGo" />
    </fieldset>
</form>
</div>
