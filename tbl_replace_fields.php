<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

// note: grab_globals has extracted the fields from _FILES
//       or HTTP_POST_FILES

// Check parameters

require_once('./libraries/common.lib.php');

PMA_checkParameters(array('db','encoded_key'));


// f i e l d    u p l o a d e d    f r o m    a    f i l e

// garvin: original if-clause checked, whether input was stored in a possible fields_upload_XX var.
// Now check, if the field is set. If it is empty or a malicious file, do not alter fields contents.
// If an empty or invalid file is specified, the binary data gets deleter. Maybe a nice
// new text-variable is appropriate to document this behaviour.

// garvin: security cautions! You could trick the form and submit any file the webserver has access to
// for upload to a binary field. Shouldn't be that easy! ;)

// garvin: default is to advance to the field-value parsing. Will only be set to true when a
// binary file is uploaded, thus bypassing further manipulation of $val.

$check_stop = false;

// Check if a multi-edit row was found
${'me_fields_upload_' . $encoded_key}      = (isset($enc_primary_key) && isset(${'fields_upload_' . $encoded_key}['multi_edit'])      ? ${'fields_upload_' . $encoded_key}['multi_edit'][$enc_primary_key]      : (isset(${'fields_upload_' . $encoded_key})      ? ${'fields_upload_' . $encoded_key}      : null));
${'me_fields_uploadlocal_' . $encoded_key} = (isset($enc_primary_key) && isset(${'fields_uploadlocal_' . $encoded_key}['multi_edit']) ? ${'fields_uploadlocal_' . $encoded_key}['multi_edit'][$enc_primary_key] : (isset(${'fields_uploadlocal_' . $encoded_key}) ? ${'fields_uploadlocal_' . $encoded_key} : null));

if (isset(${'me_fields_upload_' . $encoded_key}) && ${'me_fields_upload_' . $encoded_key} != 'none'){
    // garvin: This fields content is a blob-file upload.

    if (!empty(${'me_fields_upload_' . $encoded_key})) {
        // garvin: The blob-field is not empty. Check what we have there.

        $data_file = ${'me_fields_upload_' . $encoded_key};

        if (is_uploaded_file($data_file)) {
            // garvin: A valid uploaded file is found. Look into the file...

            $val = fread(fopen($data_file, 'rb'), filesize($data_file));
            // nijel: This is probably the best way how to put binary data
            // into MySQL and it also allow not to care about charset
            // conversion that would otherwise corrupt the data.

            if (!empty($val)) {
                // garvin: The upload was valid. Check in new blob-field's contents.
                $val = '0x' . bin2hex($val);
                $seen_binary = TRUE;
                $check_stop = TRUE;
            }
            // garvin: ELSE: an empty file was uploaded. Remove blob-field's contents.
            // Blob-fields are preserved, see below. ($protected$)

        } else {
            // garvin: Danger, will robinson. File is malicious. Blob-fields are preserved, see below. ($protected$)
            // void
        }

    } elseif (!empty(${'me_fields_uploadlocal_' . $encoded_key})) {
        if (substr($cfg['UploadDir'], -1) != '/') {
            $cfg['UploadDir'] .= '/';
        }
        $file_to_upload = $cfg['UploadDir'] . preg_replace('@\.\.*@', '.', ${'me_fields_uploadlocal_' . $encoded_key});

        // A local file will be uploaded.
        $open_basedir = @ini_get('open_basedir');

        // If we are on a server with open_basedir, we must move the file
        // before opening it. The doc explains how to create the "./tmp"
        // directory

        $unlink = false;
        if (!empty($open_basedir)) {

            $tmp_subdir = (PMA_IS_WINDOWS ? '.\\tmp\\' : './tmp/');

            // function is_writeable() is valid on PHP3 and 4
            if (!is_writeable($tmp_subdir)) {
                // if we cannot move the file don't change blob fields
                $file_to_upload = '';
            } else {
                $new_file_to_upload = $tmp_subdir . basename($file_to_upload);
                move_uploaded_file($file_to_upload, $new_file_to_upload);

                $file_to_upload = $new_file_to_upload;
                $unlink = true;
            }
        }

        if ($file_to_upload != '') {

            $val = fread(fopen($file_to_upload, 'rb'), filesize($file_to_upload));
            if (!empty($val)) {
                $val = '0x' . bin2hex($val);
                $seen_binary = TRUE;
                $check_stop = TRUE;
            }

            if ($unlink == TRUE) {
                unlink($file_to_upload);
            }
        }

    }
    // garvin: else: Post-field contains no data. Blob-fields are preserved, see below. ($protected$)

}

if (!$check_stop) {

// f i e l d    v a l u e    i n    t h e    f o r m

    if (isset($me_fields_type[$encoded_key])) $type = $me_fields_type[$encoded_key];
    else $type = '';
    
    $f = 'field_' . md5($key);
    $t_fval = (isset($$f) ? $$f : null);
    
    if (isset($t_fval['multi_edit']) && isset($t_fval['multi_edit'][$enc_primary_key])) {
        $fval = &$t_fval['multi_edit'][$enc_primary_key];
    } else {
        $fval = &$t_fval;
    }
    
    switch (strtolower($val)) {
        // let users type NULL or null to input this string and not a NULL value
        //case 'null':
        //    break;
        case '':
            switch ($type) {
                case 'enum':
                    // if we have an enum, then construct the value
                        if (!empty($fval)) {
                            $val     = implode(',', $fval);
                            if ($val == 'null') {
                                // void
                            } else {
                                // the data here is not urlencoded!
                                //$val = "'" . PMA_sqlAddslashes(urldecode($val)) . "'";
                                $val = "'" . PMA_sqlAddslashes($val) . "'";
                            }
                        } else {
                            $val     = "''";
                        }
                        break;
                case 'set':
                    // if we have a set, then construct the value
                    if (!empty($fval)) {
                        $val = implode(',', $fval);
                        // the data here is not urlencoded!
                        //$val = "'" . PMA_sqlAddslashes(urldecode($val)) . "'";
                        $val = "'" . PMA_sqlAddslashes($val) . "'";
                    } else {
                        $val = "''";
                    }
                    break;
                case 'foreign':
                    // if we have a foreign key, then construct the value
                    if (!empty($fval)) {
                        $val     = implode(',', $fval);
                        if ($val == 'null') {
                            // void
                        } else {
                            // the data here is not urlencoded!
                            //$val = "'" . PMA_sqlAddslashes(urldecode($val)) . "'";
                            $val = "'" . PMA_sqlAddslashes($val) . "'";
                        }
                    } else {
                        $val     = "''";
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
                    if (isset($fieldlist)) {
                        if (isset($prot_row) && isset($prot_row[$key]) && !empty($prot_row[$key])) {
                            $val = '0x' . bin2hex($prot_row[$key]);
                            $seen_binary = TRUE;
                        } else {
                            $val = "''";
                        }
                    } else {
                        unset($val);
                    }

                    break;
                default:
                    $val = "'" . PMA_sqlAddslashes($val) . "'";
                    break;
            }
            break;
        default:
            $val = "'" . PMA_sqlAddslashes($val) . "'";
            break;
    } // end switch

    // Was the Null checkbox checked for this field?
    // (if there is a value, we ignore the Null checkbox: this could
    // be possible if Javascript is disabled in the browser)
    if (isset($me_fields_null) && isset($me_fields_null[$encoded_key])
        && $val=="''") {
        $val = 'NULL';
    }
}  // end else (field value in the form)
?>
