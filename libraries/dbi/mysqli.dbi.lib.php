<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Interface to the improved MySQL extension (MySQLi)
 */

/**
 * Loads the MySQLi extension if it is not loaded yet
 */
if (!@function_exists('mysqli_connect')) {
    PMA_dl('mysqli');
}

// check whether mysql is available
if (!@function_exists('mysqli_connect')) {
    require_once('./libraries/header_http.inc.php');
    echo sprintf($strCantLoad, 'mysqli') . '<br />' . "\n"
         . '<a href="./Documentation.html#faqmysql" target="documentation">' . $GLOBALS['strDocu'] . '</a>' . "\n";
    exit;
}

// MySQL client API
if (!defined('PMA_MYSQL_CLIENT_API')) {
    $client_api = explode('.', mysqli_get_client_info());
    define('PMA_MYSQL_CLIENT_API', (int)sprintf('%d%02d%02d', $client_api[0], $client_api[1], intval($client_api[2])));
    unset($client_api);
}

function PMA_DBI_connect($user, $password) {
    global $cfg, $php_errormsg;

    $server_port   = (empty($cfg['Server']['port']))
                   ? FALSE
                   : (int) $cfg['Server']['port'];

    if (strtolower($cfg['Server']['connect_type']) == 'tcp') {
        $cfg['Server']['socket'] = '';
    }

    $server_socket = (empty($cfg['Server']['socket']))
                   ? FALSE
                   : $cfg['Server']['socket'];

    if ($server_socket) {
        $link = @mysqli_connect($cfg['Server']['host'], $user, $password, FALSE, $server_port, $server_socket);
    } else {
        // Omit the last parameter to enable connection to the default socket
        $link = @mysqli_connect($cfg['Server']['host'], $user, $password, FALSE, $server_port);
    }

    if (empty($link)) {
        PMA_auth_fails();
    } // end if

    if (!defined('PMA_MYSQL_INT_VERSION')) {
        $result = mysqli_query($link, 'SELECT VERSION() AS version;', MYSQLI_STORE_RESULT);
        if ($result != FALSE && @mysqli_num_rows($result) > 0) {
            $row = mysqli_fetch_row($result);
            $match = explode('.', $row[0]);
            mysqli_free_result($result);
        }
        if (!isset($row)) {
            define('PMA_MYSQL_INT_VERSION', 32332);
            define('PMA_MYSQL_STR_VERSION', '3.23.32');
        } else{
            define('PMA_MYSQL_INT_VERSION', (int)sprintf('%d%02d%02d', $match[0], $match[1], intval($match[2])));
            define('PMA_MYSQL_STR_VERSION', $row[0]);
            unset($result, $row, $match);
        }
    }

    if (PMA_MYSQL_INT_VERSION >= 40100) {
        $mysql_charset = $GLOBALS['mysql_charset_map'][$GLOBALS['charset']];
        mysqli_query($link, 'SET CHARACTER SET ' . $mysql_charset . ';', MYSQLI_STORE_RESULT);
    } else {
        require_once('./libraries/charset_conversion.lib.php');
    }

    return $link;
}

function PMA_DBI_select_db($dbname, $link = NULL) {
    if (empty($link)) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
        } else {
            return FALSE;
        }
    }
    if (PMA_MYSQL_INT_VERSION < 40100) {
        $dbname = PMA_convert_charset($dbname);
    }
    return mysqli_select_db($link, $dbname);
}

function PMA_DBI_try_query($query, $link = NULL, $options = 0) {
    if ($options == ($options | PMA_DBI_QUERY_STORE)) {
        $method = MYSQLI_STORE_RESULT;
    } elseif ($options == ($options | PMA_DBI_QUERY_UNBUFFERED)) {
        $method = MYSQLI_USE_RESULT;
    } else {
        $method = MYSQLI_USE_RESULT;
    }

    if (empty($link)) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
        } else {
            return FALSE;
        }
    }
    if (PMA_MYSQL_INT_VERSION < 40100) {
        $query = PMA_convert_charset($query);
    }
    return mysqli_query($link, $query, $method);
}

// The following function is meant for internal use only.
// Do not call it from outside this library!
function PMA_mysqli_fetch_array($result, $type = FALSE) {
    global $cfg, $allow_recoding, $charset, $convcharset;

    if ($type != FALSE) {
        $data = mysqli_fetch_array($result, $type);
    } else {
        $data = mysqli_fetch_array($result);
    }

    /* No data returned => do not touch it */
    if (! $data) return $data;
    
    if (PMA_MYSQL_INT_VERSION >= 40100
        || !(isset($cfg['AllowAnywhereRecoding']) && $cfg['AllowAnywhereRecoding'] && $allow_recoding)) {
        /* No recoding -> return data as we got them */
        return $data;
    } else {
        $ret    = array();
        $num    = mysqli_num_fields($result);
        $fields = mysqli_fetch_fields($result);
        $i = 0;
        for ($i = 0; $i < $num; $i++) {
            if (!$meta) {
                /* No meta information available -> we guess that it should be converted */
                if (isset($data[$i])) $ret[$i] = PMA_convert_display_charset($data[$i]);
                if (isset($data[$name])) $ret[PMA_convert_display_charset($name)] = PMA_convert_display_charset($data[$name]);
            } else {
                /* Meta information available -> check type of field and convert it according to the type */
                if (stristr($fields[$i]->type, 'BLOB') || stristr($fields[$i]->type, 'BINARY')) {
                    if (isset($data[$i])) $ret[$i] = $data[$i];
                    if (isset($data[$fields[$i]->name])) $ret[PMA_convert_display_charset($fields[$i]->name)] = $data[$fields[$i]->name];
                } else {
                    if (isset($data[$i])) $ret[$i] = PMA_convert_display_charset($data[$i]);
                    if (isset($data[$fields[$i]->name])) $ret[PMA_convert_display_charset($fields[$i]->name)] = PMA_convert_display_charset($data[$fields[$i]->name]);
                }
            }
        }
        return $ret;
    }
}

function PMA_DBI_fetch_array($result) {
    return PMA_mysqli_fetch_array($result, MYSQLI_BOTH);
}

function PMA_DBI_fetch_assoc($result) {
    return PMA_mysqli_fetch_array($result, MYSQLI_ASSOC);
}

function PMA_DBI_fetch_row($result) {
    return PMA_mysqli_fetch_array($result, MYSQLI_NUM);
}

function PMA_DBI_free_result($result) {
    return @mysqli_free_result($result);
}

function PMA_DBI_getError($link = NULL) {
    unset($GLOBALS['errno']);
    if (empty($link)) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
            // Do not stop now. We still can get the error code
            // with mysqli_connect_errno()
//        } else {
//            return FALSE;
        }
    }

    if (mysqli_connect_errno()) {
        $error = mysqli_connect_errno();
        $error_message = mysqli_connect_error();
    } elseif (mysqli_errno($link)) {
        $error = mysqli_errno($link);
        $error_message = mysqli_error($link);
    } 

    // keep the error number for further check after the call to PMA_DBI_getError()
    if ($error) {
        $GLOBALS['errno'] = $error;
    } else {
        return FALSE;
    }


    if ($error && $error == 2002) {
        $error = '#' . ((string) $error) . ' - ' . $GLOBALS['strServerNotResponding'];
    } elseif ($error && defined('PMA_MYSQL_INT_VERSION') && PMA_MYSQL_INT_VERSION >= 40100) {
        $error = '#' . ((string) $error) . ' - ' . $error_message;
    } elseif ($error) {
        $error = '#' . ((string) $error) . ' - ' . PMA_convert_display_charset($error_message);
    }
    return $error;
}

function PMA_DBI_close($link = NULL) {
    if (empty($link)) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
        } else {
            return FALSE;
        }
    }
    return @mysqli_close($link);
}

function PMA_DBI_num_rows($result) {
    return @mysqli_num_rows($result);
}

function PMA_DBI_insert_id($link = '') {
    if (empty($link)) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
        } else {
            return FALSE;
        }
    }
    return mysqli_insert_id($link);
}

function PMA_DBI_affected_rows($link = NULL) {
    if (empty($link)) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
        } else {
            return FALSE;
        }
    }
    return mysqli_affected_rows($link);
}

function PMA_DBI_get_fields_meta($result) {
    // Build an associative array for a type look up
    $typeAr = Array();
    $typeAr[MYSQLI_TYPE_DECIMAL]     = 'real';
    $typeAr[MYSQLI_TYPE_TINY]        = 'int';
    $typeAr[MYSQLI_TYPE_SHORT]       = 'int';
    $typeAr[MYSQLI_TYPE_LONG]        = 'int';
    $typeAr[MYSQLI_TYPE_FLOAT]       = 'real';
    $typeAr[MYSQLI_TYPE_DOUBLE]      = 'real';
    $typeAr[MYSQLI_TYPE_NULL]        = 'null';
    $typeAr[MYSQLI_TYPE_TIMESTAMP]   = 'timestamp';
    $typeAr[MYSQLI_TYPE_LONGLONG]    = 'int';
    $typeAr[MYSQLI_TYPE_INT24]       = 'int';
    $typeAr[MYSQLI_TYPE_DATE]        = 'date';
    $typeAr[MYSQLI_TYPE_TIME]        = 'time';
    $typeAr[MYSQLI_TYPE_DATETIME]    = 'datetime';
    $typeAr[MYSQLI_TYPE_YEAR]        = 'year';
    $typeAr[MYSQLI_TYPE_NEWDATE]     = 'date';
    $typeAr[MYSQLI_TYPE_ENUM]        = 'unknown';
    $typeAr[MYSQLI_TYPE_SET]         = 'unknown';
    $typeAr[MYSQLI_TYPE_TINY_BLOB]   = 'blob';
    $typeAr[MYSQLI_TYPE_MEDIUM_BLOB] = 'blob';
    $typeAr[MYSQLI_TYPE_LONG_BLOB]   = 'blob';
    $typeAr[MYSQLI_TYPE_BLOB]        = 'blob';
    $typeAr[MYSQLI_TYPE_VAR_STRING]  = 'string';
    $typeAr[MYSQLI_TYPE_STRING]      = 'string';
    $typeAr[MYSQLI_TYPE_CHAR]        = 'string';
    $typeAr[MYSQLI_TYPE_GEOMETRY]    = 'unknown';

    $fields = mysqli_fetch_fields($result);
    foreach ($fields as $k => $field) {
        $fields[$k]->type = $typeAr[$fields[$k]->type];
        $fields[$k]->flags = PMA_DBI_field_flags($result, $k);
        
        // Enhance the field objects for mysql-extension compatibilty
        $flags = explode(' ', $fields[$k]->flags);
        array_unshift($flags, 'dummy');
        $fields[$k]->multiple_key = (int)(array_search('multiple_key', $flags, true) > 0);
        $fields[$k]->primary_key  = (int)(array_search('primary_key', $flags, true) > 0);
        $fields[$k]->unique_key   = (int)(array_search('unique_key', $flags, true) > 0);
        $fields[$k]->not_null     = (int)(array_search('not_null', $flags, true) > 0);
        $fields[$k]->unsigned     = (int)(array_search('unsigned', $flags, true) > 0);
        $fields[$k]->zerofill     = (int)(array_search('zerofill', $flags, true) > 0);
        $fields[$k]->numeric      = (int)(array_search('num', $flags, true) > 0);
        $fields[$k]->blob         = (int)(array_search('blob', $flags, true) > 0);
    }
    return $fields;
}

function PMA_DBI_num_fields($result) {
    return mysqli_num_fields($result);
}

function PMA_DBI_field_len($result, $i) {
    $info = mysqli_fetch_field_direct($result, $i);
    // stdClass::$length will be integrated in 
    // mysqli-ext when mysql4.1 has been released.
    return @$info->length;
}

function PMA_DBI_field_name($result, $i) {
    $info = mysqli_fetch_field_direct($result, $i);
    return $info->name;
}

function PMA_DBI_field_flags($result, $i) {
    $f = mysqli_fetch_field_direct($result, $i);
    $f = $f->flags;
    $flags = '';
    while ($f > 0) {
        if (floor($f / 65536)) {
            $flags .= 'unique ';
            $f -= 65536;
            continue;
        } elseif (floor($f / 32768)) {
            $flags .= 'num ';
            $f -= 32768;
            continue;
        } elseif (floor($f / 16384)) {
            $flags .= 'part_key ';
            $f -= 16384;
            continue;
        } elseif (floor($f / 2048)) {
            $flags .= 'set ';
            $f -= 2048;
            continue;
        } elseif (floor($f / 1024)) {
            $flags .= 'timestamp ';
            $f -= 1024;
            continue;
        } elseif (floor($f / 512)) {
            $flags .= 'auto_increment ';
            $f -= 512;
            continue;
        } elseif (floor($f / 256)) {
            $flags .= 'enum ';
            $f -= 256;
            continue;
        } elseif (floor($f / 128)) {
            $flags .= 'binary ';
            $f -= 128;
            continue;
        } elseif (floor($f / 64)) {
            $flags .= 'zerofill ';
            $f -= 64;
            continue;
        } elseif (floor($f / 32)) {
            $flags .= 'unsigned ';
            $f -= 32;
            continue;
        } elseif (floor($f / 16)) {
            $flags .= 'blob ';
            $f -= 16;
            continue;
        } elseif (floor($f / 8)) {
            $flags .= 'multiple_key ';
            $f -= 8;
            continue;
        } elseif (floor($f / 4)) {
            $flags .= 'unique_key ';
            $f -= 4;
            continue;
        } elseif (floor($f / 2)) {
            $flags .= 'primary_key ';
            $f -= 2;
            continue;
        } elseif (floor($f / 1)) {
            $flags .= 'not_null ';
            $f -= 1;
            continue;
        }
    }
    return PMA_convert_display_charset(trim($flags));
}


?>
