<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

        // note: grab_globals has extracted the fields from _FILES
        //       or HTTP_POST_FILES

        // f i e l d    u p l o a d e d    f r o m    a    f i l e

        if (isset(${"fields_upload_" . $key}) && !empty(${"fields_upload_" . $key})) {
            $data_file = ${"fields_upload_" . $key};
            $val = fread(fopen($data_file, "rb"), filesize($data_file));
            if (isset(${"fields_upload_binary_" . $key})) {
                // nijel: This is probably the best way how to put binary data
                // into MySQL and it also allow not to care about charset
                // conversion that would otherwise corrupt the data.
                $val = '0x' . bin2hex($val);
            } else {
                // must always add slashes for an uploaded file:
                //  - do not use PMA_sqlAddslashes()
                //  - do not check get_magic_quotes_gpc()
                $val = "'" . addslashes($val) . "'";
            }
        } else {

        // f i e l d    v a l u e    i n    t h e    f o r m
            switch (strtolower($val)) {
                case 'null':
                    break;
                case '$enum$':
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
                case '$set$':
                    // if we have a set, then construct the value
                    $f = 'field_' . md5($key);
                    if (!empty($$f)) {
                        $val = implode(',', $$f);
                        $val = "'" . PMA_sqlAddslashes(urldecode($val)) . "'";
                    } else {
                        $val = "''";
                    }
                    break;
                case '$foreign$':
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
                case '$protected$':
                    // here we are in protected mode (asked in the config)
                    // so tbl_change has put this special value in the
                    // fields array, so we do not change the field value
                    // but we can still handle field upload

                    $val = "''";
                    break;
                default:
                    if (get_magic_quotes_gpc()) {
                        $val = "'" . str_replace('\\"', '"', $val) . "'";
                    } else {
                        $val = "'" . PMA_sqlAddslashes($val) . "'";
                    }
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
