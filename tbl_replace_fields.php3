<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

// note: grab_globals has extracted the fields from _FILES
//       or HTTP_POST_FILES

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
if (isset(${"fields_upload_" . $key}) && ${"fields_upload_" . $key} != 'none'){
    // garvin: This fields content is a blob-file upload.

    if (!empty(${"fields_upload_" . $key})) {
        // garvin: The blob-field is not empty. Check what we have there.

        $data_file = ${"fields_upload_" . $key};

        if (is_uploaded_file($data_file)) {
            // garvin: A valid uploaded file is found. Look into the file...

            $val = fread(fopen($data_file, "rb"), filesize($data_file));
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

    }
    // garvin: else: Post-field contains no data. Blob-fields are preserved, see below. ($protected$)

}

if (!$check_stop) {
// f i e l d    v a l u e    i n    t h e    f o r m
    if (isset($fields_type[$key])) $type = $fields_type[$key];
    else $type = '';
    switch (strtolower($val)) {
        case 'null':
            break;
        case '':
            switch ($type) {
                case 'enum':
                    // if we have an enum, then construct the value
                        $f = 'field_' . md5($key);
                        if (!empty($$f)) {
                            $val     = implode(',', $$f);
                            if ($val == 'null') {
                                // void
                            } else {
                                $val = "'" . PMA_sqlAddslashes(urldecode($val)) . "'";
                            }
                        } else {
                            $val     = "''";
                        }
                        break;
                case 'set':
                    // if we have a set, then construct the value
                    $f = 'field_' . md5($key);
                    if (!empty($$f)) {
                        $val = implode(',', $$f);
                        $val = "'" . PMA_sqlAddslashes(urldecode($val)) . "'";
                    } else {
                        $val = "''";
                    }
                    break;
                case 'foreign':
                    // if we have a foreign key, then construct the value
                    $f = 'field_' . md5($key);
                    if (!empty($$f)) {
                        $val     = implode(',', $$f);
                        if ($val == 'null') {
                            // void
                        } else {
                            $val = "'" . PMA_sqlAddslashes(urldecode($val)) . "'";
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
                    // mode, insert empty field because no values were submitted.
                    if (isset($fieldlist)) {
                        $val = "''";
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
    if (isset($fields_null) && isset($fields_null[$encoded_key])
        && $val=="''") {
        $val = 'NULL';
    }
}  // end else (field value in the form)
?>
