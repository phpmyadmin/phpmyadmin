<?php
/**
 * f i e l d    u p l o a d e d    f r o m    a    f i l e
 *
 * garvin: original if-clause checked, whether input was stored in a possible
 * fields_upload_XX var. Now check, if the field is set. If it is empty or a
 * malicious file, do not alter fields contents. If an empty or invalid file is
 * specified, the binary data gets deleter. Maybe a nice new text-variable is
 * appropriate to document this behaviour.
 *
 * garvin: security cautions! You could trick the form and submit any file the
 * webserver has access to for upload to a binary field. Shouldn't be that easy! ;)
 *
 * garvin: default is to advance to the field-value parsing. Will only be set to
 * true when a binary file is uploaded, thus bypassing further manipulation of $val.
 *
 * note: grab_globals has extracted the fields from _FILES or HTTP_POST_FILES
 *
 * @version $Id$
 * vim: expandtab sw=4 ts=4 sts=4:
 *
 * @uses $GLOBALS['cfg']['UploadDir']
 * @uses $_FILES
 * @uses $_REQUEST
 * @uses defined()
 * @uses define()
 * @uses is_uploaded_file()
 * @uses ini_get()
 * @uses is_dir()
 * @uses mkdir()
 * @uses chmod()
 * @uses is_writable()
 * @uses is_readable()
 * @uses move_uploaded_file()
 * @uses basename()
 * @uses preg_replace()
 * @uses bin2hex()
 * @uses fread()
 * @uses fopen()
 * @uses filesize()
 * @uses unlink()
 * @uses strlen()
 * @uses md5()
 * @uses implode()
 * @uses PMA_IS_WINDOWS
 * @uses PMA_NO_VARIABLES_IMPORT
 * @uses PMA_checkParameters()
 * @uses PMA_sqlAddslashes()
 * @uses PMA_userDir()
 */

/**
 * do not import request variable into global scope
 */
if (! defined('PMA_NO_VARIABLES_IMPORT')) {
    define('PMA_NO_VARIABLES_IMPORT', true);
}
/**
 * Gets some core libraries
 */
require_once './libraries/common.lib.php';

$valid_file_was_uploaded = false;

// Check if a multi-edit row was found

$me_fields_upload =
    (isset($_FILES['fields_upload_' . $key]['tmp_name']['multi_edit'][$primary_key])
    ? $_FILES['fields_upload_' . $key]['tmp_name']['multi_edit'][$primary_key]
    : (isset($_FILES['fields_upload_' . $key]['tmp_name'])
        ? $_FILES['fields_upload_' . $key]['tmp_name']
        : 'none'));

$me_fields_uploadlocal =
    (isset($_REQUEST['fields_uploadlocal_' . $key]['multi_edit'])
    ? $_REQUEST['fields_uploadlocal_' . $key]['multi_edit'][$primary_key]
    : (isset($_REQUEST['fields_uploadlocal_' . $key])
        ? $_REQUEST['fields_uploadlocal_' . $key]
        : null));

if ($me_fields_upload != 'none') {
    // garvin: This fields content is a blob-file upload.

    $file_to_insert = false;
    $unlink = false;

    if (is_uploaded_file($me_fields_upload)) {
        // whether we insert form uploaded file ...

        $file_to_insert = $me_fields_upload;

        // If we are on a server with open_basedir, we must move the file
        // before opening it. The FAQ 1.11 explains how to create the "./tmp"
        // directory - if needed
        if ('' != ini_get('open_basedir')) {
            $tmp_subdir = (PMA_IS_WINDOWS ? '.\\tmp\\' : './tmp/');

            if (! is_dir($tmp_subdir)) {
                // try to create the tmp directory if not exists
                if (mkdir($tmp_subdir, 0777)) {
                    chmod($tmp_subdir, 0777);
                }
            }

            if (! is_writable($tmp_subdir)) {
                // if we cannot move the file don't change blob fields
                $file_to_insert = false;
            } else {
                $new_file_to_upload = $tmp_subdir . basename($file_to_insert);

                move_uploaded_file($file_to_insert, $new_file_to_upload);

                $file_to_insert = $new_file_to_upload;
                $unlink = true;
                unset($new_file_to_upload);
            }
            unset($tmp_subdir);
        }
    } elseif (! empty($me_fields_uploadlocal)) {
        // ... or selected file from $cfg['UploadDir']

        $file_to_insert = PMA_userDir($GLOBALS['cfg']['UploadDir']) . preg_replace('@\.\.*@', '.', $me_fields_uploadlocal);

        if (! is_readable($file_to_insert)) {
            $file_to_insert = false;
        }
    }
    // garvin: else: Post-field contains no data. Blob-fields are preserved, see below. ($protected$)

    if ($file_to_insert) {
        $val = '';
        // check if file is not empty
        if (function_exists('file_get_contents')) {
            $val = file_get_contents($file_to_insert);
        } elseif ($file_to_insert_size = filesize($file_to_insert)) {
            $val = fread(fopen($file_to_insert, 'rb'), $file_to_insert_size);
        }

        if (! empty($val)) {
            $val = '0x' . bin2hex($val);
            $seen_binary = true;
            $valid_file_was_uploaded = true;
        }

        if ($unlink == true) {
            unlink($file_to_insert);
        }
    }

    unset($file_to_insert, $file_to_insert_size, $unlink);
}

if (false === $valid_file_was_uploaded) {

    // f i e l d    v a l u e    i n    t h e    f o r m

    if (isset($me_fields_type[$key])) {
        $type = $me_fields_type[$key];
    } else {
        $type = '';
    }

    $f = 'field_' . md5($key);

    if (0 === strlen($val)) {
        // default
        $val = "''";

        switch ($type) {
            case 'enum':
                // if we have an enum, then construct the value
            case 'set':
                // if we have a set, then construct the value
            case 'foreign':
                // if we have a foreign key, then construct the value
                if (! empty($_REQUEST[$f]['multi_edit'][$primary_key])) {
                    $val = implode(',', $_REQUEST[$f]['multi_edit'][$primary_key]);
                    $val = "'" . PMA_sqlAddslashes($val) . "'";
                }
                break;
            case 'protected':
                // here we are in protected mode (asked in the config)
                // so tbl_change has put this special value in the
                // fields array, so we do not change the field value
                // but we can still handle field upload

                // garvin: when in UPDATE mode, do not alter field's contents. When in INSERT
                // mode, insert empty field because no values were submitted. If protected
                // blobs where set, insert original fields content.
                if (! empty($prot_row[$key])) {
                    $val = '0x' . bin2hex($prot_row[$key]);
                    $seen_binary = true;
                } else {
                    $val = '';
                }

                break;
            default:
                // best way to avoid problems in strict mode (works also in non-strict mode)
                if (isset($me_auto_increment)  && isset($me_auto_increment[$key])) {
                    $val = 'NULL';
                }
                break;
        }
    } elseif (! ($type == 'timestamp' && $val == 'CURRENT_TIMESTAMP')) {
        $val = "'" . PMA_sqlAddslashes($val) . "'";
    }

    // Was the Null checkbox checked for this field?
    // (if there is a value, we ignore the Null checkbox: this could
    // be possible if Javascript is disabled in the browser)
    if (isset($me_fields_null[$key])
     && $val == "''") {
        $val = 'NULL';
    }

    // The Null checkbox was unchecked for this field
    if (empty($val) && isset($me_fields_null_prev[$key]) && ! isset($me_fields_null[$key])) {
        $val = "''";
    }
}  // end else (field value in the form)
unset($valid_file_was_uploaded, $me_fields_upload, $me_fields_uploadlocal, $type, $f);
?>
