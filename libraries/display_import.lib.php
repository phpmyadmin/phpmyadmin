<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * functions for displaying import for: server, database and table
 *
 * @usedby display_import.inc.php
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\Charsets;
use PMA\libraries\Encoding;
use PMA\libraries\Message;
use PMA\libraries\plugins\ImportPlugin;
use PMA\libraries\URL;
use PMA\libraries\Sanitize;

/**
 * Prints Html For Display Import Hidden Input
 *
 * @param String $import_type Import type: server, database, table
 * @param String $db          Selected DB
 * @param String $table       Selected Table
 *
 * @return string
 */
function PMA_getHtmlForHiddenInputs($import_type, $db, $table)
{
    $html  = '';
    if ($import_type == 'server') {
        $html .= URL::getHiddenInputs('', '', 1);
    } elseif ($import_type == 'database') {
        $html .= URL::getHiddenInputs($db, '', 1);
    } else {
        $html .= URL::getHiddenInputs($db, $table, 1);
    }
    $html .= '    <input type="hidden" name="import_type" value="'
        . $import_type . '" />' . "\n";

    return $html;
}

/**
 * Prints Html For Import Javascript
 *
 * @param int $upload_id The selected upload id
 *
 * @return string
 */
function PMA_getHtmlForImportJS($upload_id)
{
    global $SESSION_KEY;
    $html  = '';
    $html .= '<script type="text/javascript">';
    $html .= '    //<![CDATA[';
    //with "\n", so that the following lines won't be commented out by //<![CDATA[
    $html .= "\n";
    $html .= '    $( function() {';
    // add event when user click on "Go" button
    $html .= '      $("#buttonGo").bind("click", function() {';
    // hide form
    $html .= '        $("#upload_form_form").css("display", "none");';

    if ($_SESSION[$SESSION_KEY]["handler"] != 'PMA\libraries\plugins\import\upload\UploadNoplugin') {

        $html .= PMA_getHtmlForImportWithPlugin($upload_id);

    } else { // no plugin available
        $image_tag = '<img src="' . $GLOBALS['pmaThemeImage']
            . 'ajax_clock_small.gif" width="16" height="16" alt="ajax clock" /> '
            . Sanitize::jsFormat(
                __(
                    'Please be patient, the file is being uploaded. '
                    . 'Details about the upload are not available.'
                ),
                false
            ) . PMA\libraries\Util::showDocu('faq', 'faq2-9');
        $html .= "   $('#upload_form_status_info').html('" . $image_tag . "');";
        $html .= '   $("#upload_form_status").css("display", "none");';
    } // else

    // onclick
    $html .= '      });';
    // domready
    $html .= '    });';
    $html .= '    //]]>';
    //with "\n", so that the following lines won't be commented out by //]]>
    $html .= "\n";
    $html .= '</script>';

    return $html;
}

/**
 * Prints Html For Display Export options
 *
 * @param String $import_type Import type: server, database, table
 * @param String $db          Selected DB
 * @param String $table       Selected Table
 *
 * @return string
 */
function PMA_getHtmlForImportOptions($import_type, $db, $table)
{
    $html  = '    <div class="exportoptions" id="header">';
    $html .= '        <h2>';
    $html .= PMA\libraries\Util::getImage('b_import.png', __('Import'));

    if ($import_type == 'server') {
        $html .= __('Importing into the current server');
    } elseif ($import_type == 'database') {
        $import_str = sprintf(
            __('Importing into the database "%s"'),
            htmlspecialchars($db)
        );
        $html .= $import_str;
    } else {
        $import_str = sprintf(
            __('Importing into the table "%s"'),
            htmlspecialchars($table)
        );
        $html .= $import_str;
    }
    $html .= '        </h2>';
    $html .= '    </div>';

    return $html;
}

/**
 * Prints Html For Display Import options : Compressions
 *
 * @return string
 */
function PMA_getHtmlForImportCompressions()
{
    global $cfg;
    $html = '';
    // zip, gzip and bzip2 encode features
    $compressions = array();

    if ($cfg['GZipDump'] && @function_exists('gzopen')) {
        $compressions[] = 'gzip';
    }
    if ($cfg['BZipDump'] && @function_exists('bzopen')) {
        $compressions[] = 'bzip2';
    }
    if ($cfg['ZipDump'] && @function_exists('zip_open')) {
        $compressions[] = 'zip';
    }
    // We don't have show anything about compression, when no supported
    if ($compressions != array()) {
        $html .= '<div class="formelementrow" id="compression_info">';
        $compress_str = sprintf(
            __('File may be compressed (%s) or uncompressed.'),
            implode(", ", $compressions)
        );
        $html .= $compress_str;
        $html .= '<br />';
        $html .= __(
            'A compressed file\'s name must end in <b>.[format].[compression]</b>. '
            . 'Example: <b>.sql.zip</b>'
        );
        $html .= '</div>';
    }

    return $html;
}

/**
 * Prints Html For Display Import charset
 *
 * @return string
 */
function PMA_getHtmlForImportCharset()
{
    global $cfg;
    $html = '       <div class="formelementrow" id="charaset_of_file">';
    // charset of file
    if (Encoding::isSupported()) {
        $html .= '<label for="charset_of_file">' . __('Character set of the file:')
            . '</label>';
        $html .= '<select id="charset_of_file" name="charset_of_file" size="1">';
        foreach ($cfg['AvailableCharsets'] as $temp_charset) {
            $html .= '<option value="' . htmlentities($temp_charset) .  '"';
            if ((empty($cfg['Import']['charset']) && $temp_charset == 'utf-8')
                || $temp_charset == $cfg['Import']['charset']
            ) {
                $html .= ' selected="selected"';
            }
            $html .= '>' . htmlentities($temp_charset) . '</option>';
        }
        $html .= ' </select><br />';
    } else {
        $html .= '<label for="charset_of_file">' . __('Character set of the file:')
            . '</label>' . "\n";
        $html .= Charsets::getCharsetDropdownBox(
            'charset_of_file',
            'charset_of_file',
            'utf8',
            false
        );
    } // end if (recoding)

    $html .= '        </div>';

    return $html;
}

/**
 * Prints Html For Display Import options : file property
 *
 * @param int            $max_upload_size   Max upload size
 * @param ImportPlugin[] $import_list       import list
 * @param String         $local_import_file from upload directory
 *
 * @return string
 */
function PMA_getHtmlForImportOptionsFile(
    $max_upload_size, $import_list, $local_import_file
) {
    global $cfg;
    $html  = '    <div class="importoptions">';
    $html .= '         <h3>'  . __('File to import:') . '</h3>';
    $html .= PMA_getHtmlForImportCompressions();
    $html .= '        <div class="formelementrow" id="upload_form">';

    if ($GLOBALS['is_upload'] && !empty($cfg['UploadDir'])) {
        $html .= '            <ul>';
        $html .= '            <li>';
        $html .= '                <input type="radio" name="file_location" '
            . 'id="radio_import_file" required="required" />';
        $html .= PMA\libraries\Util::getBrowseUploadFileBlock($max_upload_size);
        $html .= '<br />' . __('You may also drag and drop a file on any page.');
        $html .= '            </li>';
        $html .= '            <li>';
        $html .= '               <input type="radio" name="file_location" '
            . 'id="radio_local_import_file"';
        if (! empty($GLOBALS['timeout_passed'])
            && ! empty($local_import_file)
        ) {
            $html .= ' checked="checked"';
        }
        $html .= ' />';
        $html .= PMA\libraries\Util::getSelectUploadFileBlock(
            $import_list,
            $cfg['UploadDir']
        );
        $html .= '            </li>';
        $html .= '            </ul>';

    } elseif ($GLOBALS['is_upload']) {
        $html .= PMA\libraries\Util::getBrowseUploadFileBlock($max_upload_size);
        $html .= '<br />' . __('You may also drag and drop a file on any page.');
    } elseif (!$GLOBALS['is_upload']) {
        $html .= Message::notice(
            __('File uploads are not allowed on this server.')
        )->getDisplay();
    } elseif (!empty($cfg['UploadDir'])) {
        $html .= PMA\libraries\Util::getSelectUploadFileBlock(
            $import_list,
            $cfg['UploadDir']
        );
    } // end if (web-server upload directory)

    $html .= '        </div>';
    $html .= PMA_getHtmlForImportCharset();
    $html .= '   </div>';

    return $html;
}

/**
 * Prints Html For Display Import options : Partial Import
 *
 * @param String $timeout_passed timeout passed
 * @param String $offset         timeout offset
 *
 * @return string
 */
function PMA_getHtmlForImportOptionsPartialImport($timeout_passed, $offset)
{
    $html  = '    <div class="importoptions">';
    $html .= '        <h3>' . __('Partial import:') . '</h3>';

    if (isset($timeout_passed) && $timeout_passed) {
        $html .= '<div class="formelementrow">' . "\n";
        $html .= '<input type="hidden" name="skip" value="' . $offset . '" />';
        $html .= sprintf(
            __(
                'Previous import timed out, after resubmitting '
                . 'will continue from position %d.'
            ),
            $offset
        );
        $html .= '</div>' . "\n";
    }

    $html .= '        <div class="formelementrow">';
    $html .= '           <input type="checkbox" name="allow_interrupt" value="yes"';
    $html .= '                  id="checkbox_allow_interrupt" '
        . PMA_pluginCheckboxCheck('Import', 'allow_interrupt') . '/>';
    $html .= '            <label for="checkbox_allow_interrupt">'
        . __(
            'Allow the interruption of an import in case the script detects '
            . 'it is close to the PHP timeout limit. <i>(This might be a good way'
            . ' to import large files, however it can break transactions.)</i>'
        ) . '</label><br />';
    $html .= '        </div>';

    if (! (isset($timeout_passed) && $timeout_passed)) {
        $html .= '        <div class="formelementrow">';
        $html .= '            <label for="text_skip_queries">'
            .  __(
                'Skip this number of queries (for SQL) starting from the first one:'
            )
            . '</label>';
        $html .= '            <input type="number" name="skip_queries" value="'
            . PMA_pluginGetDefault('Import', 'skip_queries')
            . '" id="text_skip_queries" min="0" />';
        $html .= '        </div>';

    } else {
        // If timeout has passed,
        // do not show the Skip dialog to avoid the risk of someone
        // entering a value here that would interfere with "skip"
        $html .= '         <input type="hidden" name="skip_queries" value="'
            . PMA_pluginGetDefault('Import', 'skip_queries')
            . '" id="text_skip_queries" />';
    }

    $html .= '    </div>';

    return $html;
}

/**
 * Prints Html For Display Import options : Other
 *
 * @return string
 */
function PMA_getHtmlForImportOptionsOther()
{
    $html  = '   <div class="importoptions">';
    $html .= '       <h3>' . __('Other options:') . '</h3>';
    $html .= '       <div class="formelementrow">';
    $html .= PMA\libraries\Util::getFKCheckbox();
    $html .= '       </div>';
    $html .= '   </div>';

    return $html;
}

/**
 * Prints Html For Display Import options : Format
 *
 * @param ImportPlugin[] $import_list import list
 *
 * @return string
 */
function PMA_getHtmlForImportOptionsFormat($import_list)
{
    $html  = '   <div class="importoptions">';
    $html .= '       <h3>' . __('Format:') . '</h3>';
    $html .= PMA_pluginGetChoice('Import', 'format', $import_list);
    $html .= '       <div id="import_notification"></div>';
    $html .= '   </div>';

    $html .= '    <div class="importoptions" id="format_specific_opts">';
    $html .= '        <h3>' . __('Format-specific options:') . '</h3>';
    $html .= '        <p class="no_js_msg" id="scroll_to_options_msg">'
        . 'Scroll down to fill in the options for the selected format '
        . 'and ignore the options for other formats.</p>';
    $html .= PMA_pluginGetOptions('Import', $import_list);
    $html .= '    </div>';
    $html .= '        <div class="clearfloat"></div>';

    // Japanese encoding setting
    if (Encoding::canConvertKanji()) {
        $html .= '        <div class="importoptions" id="kanji_encoding">';
        $html .= '            <h3>' . __('Encoding Conversion:') . '</h3>';
        $html .= Encoding::kanjiEncodingForm();
        $html .= '        </div>';

    }
    $html .= "\n";

    return $html;
}

/**
 * Prints Html For Display Import options : submit
 *
 * @return string
 */
function PMA_getHtmlForImportOptionsSubmit()
{
    $html  = '    <div class="importoptions" id="submit">';
    $html .= '       <input type="submit" value="' . __('Go') . '" id="buttonGo" />';
    $html .= '   </div>';

    return $html;
}

/**
 * Prints Html For Display Import
 *
 * @param int            $upload_id         The selected upload id
 * @param String         $import_type       Import type: server, database, table
 * @param String         $db                Selected DB
 * @param String         $table             Selected Table
 * @param int            $max_upload_size   Max upload size
 * @param ImportPlugin[] $import_list       Import list
 * @param String         $timeout_passed    Timeout passed
 * @param String         $offset            Timeout offset
 * @param String         $local_import_file from upload directory
 *
 * @return string
 */
function PMA_getHtmlForImport(
    $upload_id, $import_type, $db, $table,
    $max_upload_size, $import_list, $timeout_passed, $offset, $local_import_file
) {
    global $SESSION_KEY;
    $html  = '';
    $html .= '<iframe id="import_upload_iframe" name="import_upload_iframe" '
        . 'width="1" height="1" style="display: none;"></iframe>';
    $html .= '<div id="import_form_status" style="display: none;"></div>';
    $html .= '<div id="importmain">';
    $html .= '    <img src="' . $GLOBALS['pmaThemeImage'] . 'ajax_clock_small.gif" '
        . 'width="16" height="16" alt="ajax clock" style="display: none;" />';

    $html .= PMA_getHtmlForImportJS($upload_id);

    $html .= '    <form id="import_file_form" action="import.php" method="post" '
        . 'enctype="multipart/form-data"';
    $html .= '        name="import"';
    if ($_SESSION[$SESSION_KEY]["handler"] != 'PMA\libraries\plugins\import\upload\UploadNoplugin') {
        $html .= ' target="import_upload_iframe"';
    }
    $html .= ' class="ajax"';
    $html .= '>';
    $html .= '    <input type="hidden" name="';
    $html .= $_SESSION[$SESSION_KEY]['handler']::getIdKey();
    $html .= '" value="' . $upload_id . '" />';

    $html .= PMA_getHtmlForHiddenInputs($import_type, $db, $table);

    $html .= PMA_getHtmlForImportOptions($import_type, $db, $table);

    $html .= PMA_getHtmlForImportOptionsFile(
        $max_upload_size, $import_list, $local_import_file
    );

    $html .= PMA_getHtmlForImportOptionsPartialImport($timeout_passed, $offset);

    $html .= PMA_getHtmlForImportOptionsOther();

    $html .= PMA_getHtmlForImportOptionsFormat($import_list);

    $html .= PMA_getHtmlForImportOptionsSubmit();

    $html .= '</form>';
    $html .= '</div>';

    return $html;
}

/**
 * Prints javascript for upload with plugin, upload process bar
 *
 * @param int $upload_id The selected upload id
 *
 * @return string
 */
function PMA_getHtmlForImportWithPlugin($upload_id)
{
    //some variable for javascript
    $ajax_url = "import_status.php?id=" . $upload_id . "&"
        . URL::getCommonRaw(array('import_status'=>1));
    $promot_str = Sanitize::jsFormat(
        __(
            'The file being uploaded is probably larger than '
            . 'the maximum allowed size or this is a known bug in webkit '
            . 'based (Safari, Google Chrome, Arora etc.) browsers.'
        ),
        false
    );
    $statustext_str = Sanitize::escapeJsString(__('%s of %s'));
    $upload_str = Sanitize::jsFormat(__('Uploading your import file…'), false);
    $second_str = Sanitize::jsFormat(__('%s/sec.'), false);
    $remaining_min = Sanitize::jsFormat(__('About %MIN min. %SEC sec. remaining.'), false);
    $remaining_second = Sanitize::jsFormat(__('About %SEC sec. remaining.'), false);
    $processed_str = Sanitize::jsFormat(
        __('The file is being processed, please be patient.'),
        false
    );
    $import_url = URL::getCommonRaw(array('import_status'=>1));

    //start output
    $html  = 'var finished = false; ';
    $html .= 'var percent  = 0.0; ';
    $html .= 'var total    = 0; ';
    $html .= 'var complete = 0; ';
    $html .= 'var original_title = '
        . 'parent && parent.document ? parent.document.title : false; ';
    $html .= 'var import_start; ';

    $html .= 'var perform_upload = function () { ';
    $html .= 'new $.getJSON( ';
    $html .= '        "' . $ajax_url . '", ';
    $html .= '        {}, ';
    $html .= '        function(response) { ';
    $html .= '            finished = response.finished; ';
    $html .= '            percent = response.percent; ';
    $html .= '            total = response.total; ';
    $html .= '            complete = response.complete; ';

    $html .= '            if (total==0 && complete==0 && percent==0) { ';
    $img_tag = '<img src="' . $GLOBALS['pmaThemeImage'] . 'ajax_clock_small.gif"';
    $html .= '                $("#upload_form_status_info").html(\''
        . $img_tag . ' width="16" height="16" alt="ajax clock" /> '
        . $promot_str . '\'); ';
    $html .= '                $("#upload_form_status").css("display", "none"); ';
    $html .= '            } else { ';
    $html .= '                var now = new Date(); ';
    $html .= '                now = Date.UTC( ';
    $html .= '                    now.getFullYear(), ';
    $html .= '                    now.getMonth(), ';
    $html .= '                    now.getDate(), ';
    $html .= '                    now.getHours(), ';
    $html .= '                    now.getMinutes(), ';
    $html .= '                    now.getSeconds()) ';
    $html .= '                    + now.getMilliseconds() - 1000; ';
    $html .= '                var statustext = PMA_sprintf(';
    $html .= '                    "' . $statustext_str . '", ';
    $html .= '                    formatBytes( ';
    $html .= '                        complete, 1, PMA_messages.strDecimalSeparator';
    $html .= '                    ), ';
    $html .= '                    formatBytes(';
    $html .= '                        total, 1, PMA_messages.strDecimalSeparator';
    $html .= '                    ) ';
    $html .= '                ); ';

    $html .= '                if ($("#importmain").is(":visible")) { ';
    // show progress UI
    $html .= '                    $("#importmain").hide(); ';
    $html .= '                    $("#import_form_status") ';
    $html .= '                    .html(\'<div class="upload_progress">'
        . '<div class="upload_progress_bar_outer"><div class="percentage">'
        . '</div><div id="status" class="upload_progress_bar_inner">'
        . '<div class="percentage"></div></div></div><div>'
        . '<img src="' . $GLOBALS['pmaThemeImage']
        . 'ajax_clock_small.gif" width="16" height="16" alt="ajax clock" /> '
        . $upload_str . '</div><div id="statustext"></div></div>\') ';
    $html .= '                    .show(); ';
    $html .= '                    import_start = now; ';
    $html .= '                } ';
    $html .= '                else if (percent > 9 || complete > 2000000) { ';
    // calculate estimated time
    $html .= '                    var used_time = now - import_start; ';
    $html .= '                    var seconds = '
        . 'parseInt(((total - complete) / complete) * used_time / 1000); ';
    $html .= '                    var speed = PMA_sprintf("' . $second_str . '"';
    $html .= '                       , formatBytes(complete / used_time * 1000, 1,'
        . ' PMA_messages.strDecimalSeparator)); ';

    $html .= '                    var minutes = parseInt(seconds / 60); ';
    $html .= '                    seconds %= 60; ';
    $html .= '                    var estimated_time; ';
    $html .= '                    if (minutes > 0) { ';
    $html .= '                        estimated_time = "' . $remaining_min . '"';
    $html .= '                            .replace("%MIN", minutes)';
    $html .= '                            .replace("%SEC", seconds); ';
    $html .= '                    } ';
    $html .= '                    else { ';
    $html .= '                        estimated_time = "' . $remaining_second . '"';
    $html .= '                        .replace("%SEC", seconds); ';
    $html .= '                    } ';

    $html .= '                    statustext += "<br />" + speed + "<br /><br />" '
        . '+ estimated_time; ';
    $html .= '                } ';

    $html .= '                var percent_str = Math.round(percent) + "%"; ';
    $html .= '                $("#status").animate({width: percent_str}, 150); ';
    $html .= '                $(".percentage").text(percent_str); ';

    // show percent in window title
    $html .= '                if (original_title !== false) { ';
    $html .= '                    parent.document.title ';
    $html .= '                        = percent_str + " - " + original_title; ';
    $html .= '                } ';
    $html .= '                else { ';
    $html .= '                    document.title ';
    $html .= '                        = percent_str + " - " + original_title; ';
    $html .= '                } ';
    $html .= '                $("#statustext").html(statustext); ';
    $html .= '            }  ';

    $html .= '            if (finished == true) { ';
    $html .= '                if (original_title !== false) { ';
    $html .= '                    parent.document.title = original_title; ';
    $html .= '                } ';
    $html .= '                else { ';
    $html .= '                    document.title = original_title; ';
    $html .= '                } ';
    $html .= '                $("#importmain").hide(); ';
    // loads the message, either success or mysql error
    $html .= '                $("#import_form_status") ';
    $html .= '                .html(\'<img src="' . $GLOBALS['pmaThemeImage']
        . 'ajax_clock_small.gif" width="16" height="16" alt="ajax clock" /> '
        . $processed_str . '\')';
    $html .= '                .show(); ';
    $html .= '                $("#import_form_status").load("import_status.php?'
        . 'message=true&' . $import_url . '"); ';
    $html .= '                PMA_reloadNavigation(); ';

    // if finished
    $html .= '            } ';
    $html .= '            else { ';
    $html .= '              setTimeout(perform_upload, 1000); ';
    $html .= '         } ';
    $html .= '}); ';
    $html .= '}; ';
    $html .= 'setTimeout(perform_upload, 1000); ';

    return $html;
}

/**
 * Gets HTML to display import dialogs
 *
 * @param String $import_type     Import type: server|database|table
 * @param String $db              Selected DB
 * @param String $table           Selected Table
 * @param int    $max_upload_size Max upload size
 *
 * @return string $html
 */
function PMA_getImportDisplay($import_type, $db, $table, $max_upload_size)
{
    global $SESSION_KEY;
    include_once './libraries/file_listing.lib.php';
    include_once './libraries/plugin_interface.lib.php';

    include_once './libraries/display_import_ajax.lib.php';
    list(
        $SESSION_KEY,
        $upload_id,
    ) = PMA_uploadProgressSetup();

    /* Scan for plugins */
    /* @var $import_list ImportPlugin[] */
    $import_list = PMA_getPlugins(
        "import",
        'libraries/plugins/import/',
        $import_type
    );

    /* Fail if we didn't find any plugin */
    if (empty($import_list)) {
        Message::error(
            __(
                'Could not load import plugins, please check your installation!'
            )
        )->display();
        exit;
    }

    if (PMA_isValid($_REQUEST['offset'], 'numeric')) {
        $offset = intval($_REQUEST['offset']);
    }
    if (isset($_REQUEST['timeout_passed'])) {
        $timeout_passed = $_REQUEST['timeout_passed'];
    }

    $local_import_file = '';
    if (isset($_REQUEST['local_import_file'])) {
        $local_import_file = $_REQUEST['local_import_file'];
    }

    $timeout_passed_str = isset($timeout_passed)? $timeout_passed : null;
    $offset_str = isset($offset)? $offset : null;
    return PMA_getHtmlForImport(
        $upload_id,
        $import_type,
        $db,
        $table,
        $max_upload_size,
        $import_list,
        $timeout_passed_str,
        $offset_str,
        $local_import_file
    );
}
