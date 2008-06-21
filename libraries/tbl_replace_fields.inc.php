<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * handle field values (possibly uploaded from a file)
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
 *
 * @uses $_REQUEST
 * @uses defined()
 * @uses define()
 * @uses bin2hex()
 * @uses strlen()
 * @uses md5()
 * @uses implode()
 * @uses PMA_NO_VARIABLES_IMPORT
 * @uses PMA_sqlAddslashes()
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * do not import request variable into global scope
 */
if (! defined('PMA_NO_VARIABLES_IMPORT')) {
    define('PMA_NO_VARIABLES_IMPORT', true);
}
/**
 * Gets some core libraries
 */
require_once './libraries/common.inc.php';
require_once './libraries/File.class.php';

$file_to_insert = new PMA_File();
$file_to_insert->checkTblChangeForm($key, $rowcount);

$possibly_uploaded_val = $file_to_insert->getContent();

if ($file_to_insert->isError()) {
    $message .= $file_to_insert->getError();
}
$file_to_insert->cleanUp();

if (false !== $possibly_uploaded_val) {
    $val = $possibly_uploaded_val;
} else {

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
                if (! empty($_REQUEST[$f]['multi_edit'][$rowcount])) {
                    $val = implode(',', $_REQUEST[$f]['multi_edit'][$rowcount]);
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
    } elseif ($type == 'bit') {
        $val = preg_replace('/[^01]/', '0', $val);
        $val = "b'" . PMA_sqlAddslashes($val) . "'";
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
unset($type, $f);
?>
