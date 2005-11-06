<?php
/* $Id$ */
// vim: expandtab sw=4 ts=4 sts=4:

/**
 * Common Option Constants For DBI Functions
 */
// PMA_DBI_try_query()
define('PMA_DBI_QUERY_STORE',       1);  // Force STORE_RESULT method, ignored by classic MySQL.
define('PMA_DBI_QUERY_UNBUFFERED',  2);  // Do not read whole query
// PMA_DBI_get_variable()
define('PMA_DBI_GETVAR_SESSION', 1);
define('PMA_DBI_GETVAR_GLOBAL', 2);

/**
 * Including The DBI Plugin
 */
require_once('./libraries/dbi/' . $cfg['Server']['extension'] . '.dbi.lib.php');

/**
 * Common Functions
 */
function PMA_DBI_query($query, $link = NULL, $options = 0) {
    $res = PMA_DBI_try_query($query, $link, $options)
        or PMA_mysqlDie(PMA_DBI_getError($link), $query);
    return $res;
}

/**
 * converts charset of a mysql message, usally coming from mysql_error(),
 * into PMA charset, usally UTF-8
 * uses language to charset mapping from mysql/share/errmsg.txt
 * and charset names to ISO charset from information_schema.CHARACTER_SETS
 * 
 * @uses    $GLOBALS['cfg']['IconvExtraParams']
 * @uses    $GLOBALS['charset']     as target charset
 * @uses    PMA_DBI_fetch_value()   to get server_language
 * @uses    preg_match()            to filter server_language
 * @uses    in_array()
 * @uses    function_exists()       to check for a convert function
 * @uses    iconv()                 to convert message
 * @uses    libiconv()              to convert message
 * @uses    recode_string()         to convert message
 * @uses    mb_convert_encoding()   to convert message
 * @param   string  $message
 * @return  string  $message
 */
function PMA_DBI_convert_message( $message ) {
    // latin always last!
    $encodings = array(
        'japanese'      => 'EUC-JP', //'ujis',
        'japanese-sjis' => 'Shift-JIS', //'sjis',
        'korean'        => 'EUC-KR', //'euckr',
        'russian'       => 'KOI8-R', //'koi8r',
        'ukrainian'     => 'KOI8-U', //'koi8u',
        'greek'         => 'ISO-8859-7', //'greek',
        'serbian'       => 'CP1250', //'cp1250',
        'estonian'      => 'ISO-8859-13', //'latin7',
        'slovak'        => 'ISO-8859-2', //'latin2',
        'czech'         => 'ISO-8859-2', //'latin2',
        'hungarian'     => 'ISO-8859-2', //'latin2',
        'polish'        => 'ISO-8859-2', //'latin2',
        'romanian'      => 'ISO-8859-2', //'latin2',
        'spanish'       => 'CP1252', //'latin1',
        'swedish'       => 'CP1252', //'latin1',
        'italian'       => 'CP1252', //'latin1',
        'norwegian-ny'  => 'CP1252', //'latin1',
        'norwegian'     => 'CP1252', //'latin1',
        'portuguese'    => 'CP1252', //'latin1',
        'danish'        => 'CP1252', //'latin1',
        'dutch'         => 'CP1252', //'latin1',
        'english'       => 'CP1252', //'latin1',
        'french'        => 'CP1252', //'latin1',
        'german'        => 'CP1252', //'latin1',
    );
    
    if ( $server_language = PMA_DBI_fetch_value( 'SHOW VARIABLES LIKE \'language\';', 0, 1 ) ) {
        if ( preg_match( '&(?:\\\|\\/)([^\\\\\/]*)(?:\\\|\\/)$&i', $server_language, $found = array() ) ) {
            $server_language = $found[1];
        }
    } 
    
    if ( ! empty( $server_language ) && isset( $encodings[$server_language] ) ) {
        if ( function_exists( 'iconv' ) ) {
            $message = iconv( $encodings[$server_language],
                $GLOBALS['charset'] . $GLOBALS['cfg']['IconvExtraParams'], $message);
        } elseif ( function_exists( 'recode_string' ) ) {
            $message = recode_string( $encodings[$server_language] . '..'  . $GLOBALS['charset'],
                $message );
        } elseif ( function_exists( 'libiconv' ) ) {
            $message = libiconv( $encodings[$server_language], $GLOBALS['charset'], $message );
        } elseif ( function_exists( 'mb_convert_encoding' ) ) {
            // do not try unsupported charsets
            if ( ! in_array( $server_language, array( 'ukrainian', 'greek', 'serbian' ) ) ) {
                $message = mb_convert_encoding( $message, $GLOBALS['charset'],
                    $encodings[$server_language] );
            }
        }
    } else {
        // lang not found, try all
        // what TODO ?
    }
    
    return $message;
}

/**
 * returns array with database names
 * 
 * @return  array   $databases
 */
function PMA_DBI_get_dblist( $link = NULL ) {

    $dbs_array = PMA_DBI_fetch_result( 'SHOW DATABASES;', $link );
    
    // Before MySQL 4.0.2, SHOW DATABASES could send the
    // whole list, so check if we really have access:
    if ( PMA_MYSQL_INT_VERSION < 40002 ) {
        foreach ( $dbs_array as $key => $db ) {
            if ( ! PMA_DBI_select_db( $db, $link ) ) {
                unset( $dbs_array[$key] );
            }
        }
        // re-index values
        $dbs_array = array_values( $dbs_array );
    }

    return $dbs_array;
}

function PMA_DBI_get_tables($database, $link = NULL) {
    $result       = PMA_DBI_query('SHOW TABLES FROM ' . PMA_backquote($database) . ';', NULL, PMA_DBI_QUERY_STORE);
    $tables       = array();
    while (list($current) = PMA_DBI_fetch_row($result)) {
        $tables[] = $current;
    }
    PMA_DBI_free_result($result);

    return $tables;
}

/**
 * returns array of all tables in given db or dbs
 * this function expects unqoted names:
 * RIGHT: my_database
 * WRONG: `my_database`
 * WRONG: my\_database
 * if $tbl_is_group is true, $table is used as filter for table names
 * if $tbl_is_group is 'comment, $table is used as filter for table comments
 * 
 * <code>
 * PMA_DBI_get_tables_full( 'my_database' );
 * PMA_DBI_get_tables_full( 'my_database', 'my_table' ) );
 * PMA_DBI_get_tables_full( 'my_database', 'my_tables_', true ) );
 * PMA_DBI_get_tables_full( 'my_database', 'my_tables_', 'comment' ) );
 * </code>
 * 
 * @uses    PMA_MYSQL_INT_VERSION
 * @uses    PMA_DBI_fetch_result()
 * @uses    PMA_escape_mysql_wildcards()
 * @uses    PMA_backquote()
 * @uses    is_array()
 * @uses    addslashes()
 * @uses    strpos()
 * @uses    strtoupper()
 * @param   string          $databases      database
 * @param   string          $table          table
 * @param   boolean|string  $tbl_is_group   $table is a table group
 * @param   resource        $link           mysql link
 * @return  array           list of tbales in given db(s)
 */
function PMA_DBI_get_tables_full( $database, $table = false,
    $tbl_is_group = false, $link = NULL )
{
    // prepare and check parameters
    if ( empty( $database ) ) {
        return false;
    } elseif ( false !== strpos( '`', $database ) ) {
        // found ` in name
        return false;
    } elseif ( false !== strpos( '\\', $database ) ) {
        // found \ in name
        return false;
    }
    
    if ( PMA_MYSQL_INT_VERSION >= 50002 ) {
        // get table information from information_schema
        if ( $table ) {
            if ( true === $tbl_is_group ) {
                $sql_where_table = 'AND `TABLE_NAME` LIKE \'' 
                    . PMA_escape_mysql_wildcards( addslashes( $table ) ) . '%\'';
            } elseif ( 'comment' === $tbl_is_group ) {
                $sql_where_table = 'AND `TABLE_COMMENT` LIKE \'' 
                    . PMA_escape_mysql_wildcards( addslashes( $table ) ) . '%\'';
            } else {
                $sql_where_table = 'AND `TABLE_NAME` = \'' . addslashes( $table ) . '\'';
            }
        } else {
            $sql_where_table = '';
        }
        
        // for PMA bc:
        // `SCHEMA_FIELD_NAME` AS `SHOW_TABLE_STATUS_FIELD_NAME`
        $sql = '
             SELECT *,
                    `TABLE_SCHEMA`       AS `Db`,
                    `TABLE_NAME`         AS `Name`,
                    `ENGINE`             AS `Engine`,
                    `ENGINE`             AS `Type`,
                    `VERSION`            AS `Version`,
                    `ROW_FORMAT`         AS `Row_format`,
                    `TABLE_ROWS`         AS `Rows`,
                    `AVG_ROW_LENGTH`     AS `Avg_row_length`,
                    `DATA_LENGTH`        AS `Data_length`,
                    `MAX_DATA_LENGTH`    AS `Max_data_length`,
                    `INDEX_LENGTH`       AS `Index_length`,
                    `DATA_FREE`          AS `Data_free`,
                    `AUTO_INCREMENT`     AS `Auto_increment`,
                    `CREATE_TIME`        AS `Create_time`,
                    `UPDATE_TIME`        AS `Update_time`,
                    `CHECK_TIME`         AS `Check_time`,
                    `TABLE_COLLATION`    AS `Collation`,
                    `CHECKSUM`           AS `Checksum`,
                    `CREATE_OPTIONS`     AS `Create_options`,
                    `TABLE_COMMENT`      AS `Comment`
               FROM `information_schema`.`TABLES`
              WHERE `TABLE_SCHEMA` = \'' . addslashes( $database ) . '\'
                ' . $sql_where_table;
        
        $tables = PMA_DBI_fetch_result( $sql, 'TABLE_NAME', NULL, $link );
        unset( $sql_where_table, $sql );
    } else {
        if ( true === $tbl_is_group ) {
            $sql = 'SHOW TABLE STATUS FROM ' 
                . PMA_backquote( addslashes( $database ) ) 
                .' LIKE \'' . PMA_escape_mysql_wildcards( addslashes( $table ) ) . '%\'';
        } else {
            $sql = 'SHOW TABLE STATUS FROM ' 
                . PMA_backquote( addslashes( $database ) ) . ';';
        }
        $tables = PMA_DBI_fetch_result( $sql, 'Name', NULL, $link );
        foreach ( $tables as $table_name => $each_table ) {
            
            
            if ( 'comment' === $tbl_is_group 
              && 0 === strpos( $each_table['Comment'], $table ) )
            {
                // remove table from list
                unset( $tables[$table_name] );
                continue;
            }
            
            if ( ! isset( $tables[$table_name]['Type'] )
              && isset( $tables[$table_name]['Engine'] ) ) {
                // pma BC, same parts of PMA still uses 'Type'
                $tables[$table_name]['Type'] =& $tables[$table_name]['Engine'];
            } elseif ( ! isset( $tables[$table_name]['Engine'] )
              && isset( $tables[$table_name]['Type'] ) ) {
                // old MySQL reports Type, newer MySQL reports Engine
                $tables[$table_name]['Engine'] =& $tables[$table_name]['Type'];
            }
            
            // MySQL forward compatibility
            // so pma could use this array as if every server is of version >5.0
            $tables[$table_name]['TABLE_SCHEMA']      = $database;
            $tables[$table_name]['TABLE_NAME']        =& $tables[$table_name]['Name'];
            $tables[$table_name]['ENGINE']            =& $tables[$table_name]['Engine'];
            $tables[$table_name]['VERSION']           =& $tables[$table_name]['Version'];
            $tables[$table_name]['ROW_FORMAT']        =& $tables[$table_name]['Row_format'];
            $tables[$table_name]['TABLE_ROWS']        =& $tables[$table_name]['Rows'];
            $tables[$table_name]['AVG_ROW_LENGTH']    =& $tables[$table_name]['Avg_row_length'];
            $tables[$table_name]['DATA_LENGTH']       =& $tables[$table_name]['Data_length'];
            $tables[$table_name]['MAX_DATA_LENGTH']   =& $tables[$table_name]['Max_data_length'];
            $tables[$table_name]['INDEX_LENGTH']      =& $tables[$table_name]['Index_length'];
            $tables[$table_name]['DATA_FREE']         =& $tables[$table_name]['Data_free'];
            $tables[$table_name]['AUTO_INCREMENT']    =& $tables[$table_name]['Auto_increment'];
            $tables[$table_name]['CREATE_TIME']       =& $tables[$table_name]['Create_time'];
            $tables[$table_name]['UPDATE_TIME']       =& $tables[$table_name]['Update_time'];
            $tables[$table_name]['CHECK_TIME']        =& $tables[$table_name]['Check_time'];
            $tables[$table_name]['TABLE_COLLATION']   =& $tables[$table_name]['Collation'];
            $tables[$table_name]['CHECKSUM']          =& $tables[$table_name]['Checksum'];
            $tables[$table_name]['CREATE_OPTIONS']    =& $tables[$table_name]['Create_options'];
            $tables[$table_name]['TABLE_COMMENT']     =& $tables[$table_name]['Comment'];
            
            if ( strtoupper( $tables[$table_name]['Comment'] ) === 'VIEW' ) {
                $tables[$table_name]['TABLE_TYPE'] = 'VIEW';
            } else {
                // TODO difference between 'TEMPORARY' and 'BASE TABLE'
                // but how to detect?
                $tables[$table_name]['TABLE_TYPE'] = 'BASE TABLE';
            }
        }
    }
    
    if ( $GLOBALS['cfg']['NaturalOrder'] ) {
        uksort( $tables, 'strnatcasecmp' );
    }
    
    return $tables;
}

/**
 * returns array with databases containing extended infos about them
 * 
 * @param   string          $databases      database
 * @param   boolean         $force_stats    retrieve stats also for MySQL < 5
 * @param   resource        $link           mysql link
 * @return  array       $databases
 */
function PMA_DBI_get_databases_full( $database = NULL, $force_stats = false, $link = NULL ) {
    if ( PMA_MYSQL_INT_VERSION >= 50002 ) {
        // get table information from information_schema
        if ( $database ) {
            $sql_where_schema = 'WHERE `SCHEMA_NAME` LIKE \'' 
                . addslashes( $database ) . '\'';
        } else {
            $sql_where_schema = '';
        }
        
        // for PMA bc:
        // `SCHEMA_FIELD_NAME` AS `SHOW_TABLE_STATUS_FIELD_NAME`
        $sql = '
             SELECT `information_schema`.`SCHEMATA`.*,
                    COUNT(`information_schema`.`TABLES`.`TABLE_SCHEMA`)
                        AS `SCHEMA_TABLES`,
                    SUM(`information_schema`.`TABLES`.`TABLE_ROWS`)
                        AS `SCHEMA_TABLE_ROWS`,
                    SUM(`information_schema`.`TABLES`.`DATA_LENGTH`)
                        AS `SCHEMA_DATA_LENGTH`,
                    SUM(`information_schema`.`TABLES`.`MAX_DATA_LENGTH`)
                        AS `SCHEMA_MAX_DATA_LENGTH`,
                    SUM(`information_schema`.`TABLES`.`INDEX_LENGTH`)
                        AS `SCHEMA_INDEX_LENGTH`,
                    SUM(`information_schema`.`TABLES`.`DATA_LENGTH`
                      + `information_schema`.`TABLES`.`INDEX_LENGTH`)
                        AS `SCHEMA_LENGTH`,
                    SUM(`information_schema`.`TABLES`.`DATA_FREE`)
                        AS `SCHEMA_DATA_FREE`
               FROM `information_schema`.`SCHEMATA`
          LEFT JOIN `information_schema`.`TABLES`
                 ON `information_schema`.`TABLES`.`TABLE_SCHEMA`
                  = `information_schema`.`SCHEMATA`.`SCHEMA_NAME`
              ' . $sql_where_schema . '
           GROUP BY `information_schema`.`SCHEMATA`.`SCHEMA_NAME`';
        $databases = PMA_DBI_fetch_result( $sql, 'SCHEMA_NAME', NULL, $link );
        unset( $sql_where_schema, $sql );
    } else {
        foreach ( PMA_DBI_get_dblist( $link ) as $database_name ) {
            // MySQL forward compatibility
            // so pma could use this array as if every server is of version >5.0
            $databases[$database_name]['SCHEMA_NAME']      = $database_name;
            
            if ( $force_stats ) {
                require_once 'mysql_charsets.lib.php';
                
                $databases[$database_name]['DEFAULT_COLLATION_NAME']
                    = PMA_getDbCollation( $database_name );
                
                // get additonal info about tables
                $databases[$database_name]['SCHEMA_TABLES']          = 0;
                $databases[$database_name]['SCHEMA_TABLE_ROWS']      = 0;
                $databases[$database_name]['SCHEMA_DATA_LENGTH']     = 0;
                $databases[$database_name]['SCHEMA_MAX_DATA_LENGTH'] = 0;
                $databases[$database_name]['SCHEMA_INDEX_LENGTH']    = 0;
                $databases[$database_name]['SCHEMA_LENGTH']          = 0;
                $databases[$database_name]['SCHEMA_DATA_FREE']       = 0;
                
                $res = PMA_DBI_query('SHOW TABLE STATUS FROM ' . PMA_backquote( $database_name ) . ';');
                while ( $row = PMA_DBI_fetch_assoc( $res ) ) {
                    $databases[$database_name]['SCHEMA_TABLES']++;
                    $databases[$database_name]['SCHEMA_TABLE_ROWS']
                        += $row['Rows'];
                    $databases[$database_name]['SCHEMA_DATA_LENGTH']
                        += $row['Data_length'];
                    $databases[$database_name]['SCHEMA_MAX_DATA_LENGTH']
                        += $row['Max_data_length'];
                    $databases[$database_name]['SCHEMA_INDEX_LENGTH']
                        += $row['Index_length'];
                    $databases[$database_name]['SCHEMA_DATA_FREE']
                        += $row['Data_free'];
                    $databases[$database_name]['SCHEMA_LENGTH']
                        += $row['Data_length'] + $row['Index_length'];
                }
                PMA_DBI_free_result( $res );
                unset( $res );
            }
        }
    }
    
    if ( $GLOBALS['cfg']['NaturalOrder'] ) {
        uksort( $databases, 'strnatcasecmp' );
    }
    
    return $databases;
}

function PMA_DBI_get_fields($database, $table, $link = NULL) {
    if (empty($link)) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
        } else {
            return FALSE;
        }
    }
    // here we use a try_query because when coming from 
    // tbl_create + tbl_properties.inc.php, the table does not exist
    $result = PMA_DBI_try_query('SHOW FULL FIELDS FROM ' . PMA_backquote($database) . '.' . PMA_backquote($table), $link);

    if (!$result) {
        return FALSE;
    }

    $fields = array();
    while ($row = PMA_DBI_fetch_assoc($result)) {
        $fields[] = $row;
    }

    return $fields;
}

function PMA_DBI_get_variable($var, $type = PMA_DBI_GETVAR_SESSION, $link = NULL) {
    if ($link === NULL) {
        if (isset($GLOBALS['userlink'])) {
            $link = $GLOBALS['userlink'];
        } else {
            return FALSE;
        }
    }
    if (PMA_MYSQL_INT_VERSION < 40002) {
        $type = 0;
    }
    switch ($type) {
        case PMA_DBI_GETVAR_SESSION:
            $modifier = ' SESSION';
            break;
        case PMA_DBI_GETVAR_GLOBAL:
            $modifier = ' GLOBAL';
            break;
        default:
            $modifier = '';
    }
    $res = PMA_DBI_query('SHOW' . $modifier . ' VARIABLES LIKE \'' . $var . '\';', $link);
    $row = PMA_DBI_fetch_row($res);
    PMA_DBI_free_result($res);
    if (empty($row)) {
        return FALSE;
    } else {
        return $row[0] == $var ? $row[1] : FALSE;
    }
}

function PMA_DBI_postConnect($link, $is_controluser = FALSE) {
    global $collation_connection, $charset_connection;
    if (!defined('PMA_MYSQL_INT_VERSION')) {
        $result = PMA_DBI_query('SELECT VERSION() AS version', $link, PMA_DBI_QUERY_STORE);
        if ($result != FALSE && @PMA_DBI_num_rows($result) > 0) {
            $row   = PMA_DBI_fetch_row($result);
            $match = explode('.', $row[0]);
            PMA_DBI_free_result($result);
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

        // If $lang is defined and we are on MySQL >= 4.1.x,
        // we auto-switch the lang to its UTF-8 version (if it exists and user didn't force language)
        if (!empty($GLOBALS['lang']) && (substr($GLOBALS['lang'], -5) != 'utf-8') && !isset($GLOBALS['cfg']['Lang'])) {
            $lang_utf_8_version = substr($GLOBALS['lang'], 0, strpos($GLOBALS['lang'], '-')) . '-utf-8';
            if (!empty($GLOBALS['available_languages'][$lang_utf_8_version])) {
                $GLOBALS['lang'] = $lang_utf_8_version;
                $GLOBALS['charset'] = $charset = 'utf-8';
            }
        }

        // and we remove the non-UTF-8 choices to avoid confusion
        if (!defined('PMA_REMOVED_NON_UTF_8')) {
            $tmp_available_languages        = $GLOBALS['available_languages']; 
            $GLOBALS['available_languages'] = array();
            foreach ($tmp_available_languages AS $tmp_lang => $tmp_lang_data) {
                if (substr($tmp_lang, -5) == 'utf-8') {
                    $GLOBALS['available_languages'][$tmp_lang] = $tmp_lang_data;
                }
            } // end foreach
            unset($tmp_lang, $tmp_lang_data, $tmp_available_languages);
            define('PMA_REMOVED_NON_UTF_8',1);
        }

        $mysql_charset = $GLOBALS['mysql_charset_map'][$GLOBALS['charset']];
        if ($is_controluser || empty($collation_connection) || (strpos($collation_connection, '_') ? substr($collation_connection, 0, strpos($collation_connection, '_')) : $collation_connection) == $mysql_charset) {
            PMA_DBI_query('SET NAMES ' . $mysql_charset . ';', $link, PMA_DBI_QUERY_STORE);
        } else {
            PMA_DBI_query('SET CHARACTER SET ' . $mysql_charset . ';', $link, PMA_DBI_QUERY_STORE);
        }
        if (!empty($collation_connection)) {
            PMA_DBI_query('SET collation_connection = \'' . $collation_connection . '\';', $link, PMA_DBI_QUERY_STORE);
        }
        if (!$is_controluser) {
            $collation_connection = PMA_DBI_get_variable('collation_connection',     PMA_DBI_GETVAR_SESSION, $link);
            $charset_connection   = PMA_DBI_get_variable('character_set_connection', PMA_DBI_GETVAR_SESSION, $link);
        }

        // Add some field types to the list
        // (we pass twice here; feel free to code something better :)
        if (!defined('PMA_ADDED_FIELD_TYPES')) {
            $GLOBALS['cfg']['ColumnTypes'][] = 'BINARY';
            $GLOBALS['cfg']['ColumnTypes'][] = 'VARBINARY';
            define('PMA_ADDED_FIELD_TYPES',1);
        }

    } else {
        require_once('./libraries/charset_conversion.lib.php');
    }
}

/**
 * returns a single value from the given result or query,
 * if the query or the result has more than one row or field
 * the first field of the first row is returned
 * 
 * <code>
 * $sql = 'SELECT `name` FROM `user` WHERE `id` = 123';
 * $user_name = PMA_DBI_fetch_value( $sql );
 * // produces
 * // $user_name = 'John Doe'
 * </code>
 * 
 * @uses    is_string()
 * @uses    is_int()
 * @uses    PMA_DBI_try_query()
 * @uses    PMA_DBI_num_rows()
 * @uses    PMA_DBI_fetch_row()
 * @uses    PMA_DBI_fetch_assoc()
 * @uses    PMA_DBI_free_result()
 * @param   string|mysql_result $result query or mysql result
 * @param   integer             $row_number row to fetch the value from,
 *                                      starting at 0, with 0 beeing default
 * @param   integer|string      $field  field to fetch the value from,
 *                                      starting at 0, with 0 beeing default
 * @param   resource            $link   mysql link
 * @param   mixed               $options    
 * @return  mixed               value of first field in first row from result
 *                              or false if not found
 */
function PMA_DBI_fetch_value( $result, $row_number = 0, $field = 0, $link = NULL, $options = 0 ) {
    $value = false;
    
    if ( is_string( $result ) ) {
        $result = PMA_DBI_try_query( $result, $link, $options | PMA_DBI_QUERY_STORE );
    }
    
    // return false if result is empty or false
    // or requested row is larger than rows in result
    if ( PMA_DBI_num_rows( $result ) < ( $row_number + 1 ) ) {
        return $value;
    }
    
    // if $field is an integer use non associative mysql fetch function    
    if ( is_int( $field ) ) {
        $fetch_function = 'PMA_DBI_fetch_row';
    } else {
        $fetch_function = 'PMA_DBI_fetch_assoc';
    }
    
    // get requested row
    for ( $i = 0; $i <= $row_number; $i++ ) {
        $row = $fetch_function( $result );
    }
    PMA_DBI_free_result( $result );
    
    // return requested field
    if ( isset( $row[$field] ) ) {
        $value = $row[$field];
    }
    unset( $row );
    
    return $value;
}

/**
 * returns only the first row from the result
 * 
 * <code>
 * $sql = 'SELECT * FROM `user` WHERE `id` = 123';
 * $user = PMA_DBI_fetch_single_row( $sql );
 * // produces
 * // $user = array( 'id' => 123, 'name' => 'John Doe' )
 * </code>
 * 
 * @uses    is_string()
 * @uses    PMA_DBI_try_query()
 * @uses    PMA_DBI_num_rows()
 * @uses    PMA_DBI_fetch_row()
 * @uses    PMA_DBI_fetch_assoc()
 * @uses    PMA_DBI_fetch_array()
 * @uses    PMA_DBI_free_result()
 * @param   string|mysql_result $result query or mysql result
 * @param   string              $type   NUM|ASSOC|BOTH
 *                                      returned array should either numeric
 *                                      associativ or booth
 * @param   resource            $link   mysql link
 * @param   mixed               $options    
 * @return  array|boolean       first row from result
 *                              or false if result is empty
 */
function PMA_DBI_fetch_single_row( $result, $type = 'ASSOC', $link = NULL, $options = 0 ) {
    if ( is_string( $result ) ) {
        $result = PMA_DBI_try_query( $result, $link, $options | PMA_DBI_QUERY_STORE );
    }
    
    // return NULL if result is empty or false
    if ( ! PMA_DBI_num_rows( $result ) ) {
        return false;
    }    
    
    switch ( $type ) {
        case 'NUM' :
            $fetch_function = 'PMA_DBI_fetch_row';
            break;
        case 'ASSOC' :
            $fetch_function = 'PMA_DBI_fetch_assoc';
            break;
        case 'BOTH' :
        default :
            $fetch_function = 'PMA_DBI_fetch_array';
            break;
    }
    
    $row = $fetch_function( $result );
    PMA_DBI_free_result( $result );
    return $row;
}

/**
 * returns all rows in the resultset in one array
 * 
 * <code>
 * $sql = 'SELECT * FROM `user`';
 * $users = PMA_DBI_fetch_result( $sql );
 * // produces
 * // $users[] = array( 'id' => 123, 'name' => 'John Doe' )
 * 
 * $sql = 'SELECT `id`, `name` FROM `user`';
 * $users = PMA_DBI_fetch_result( $sql, 'id' );
 * // produces
 * // $users['123'] = array( 'id' => 123, 'name' => 'John Doe' )
 *
 * $sql = 'SELECT `id`, `name` FROM `user`';
 * $users = PMA_DBI_fetch_result( $sql, 0 );
 * // produces
 * // $users['123'] = array( 0 => 123, 1 => 'John Doe' )
 *
 * $sql = 'SELECT `id`, `name` FROM `user`';
 * $users = PMA_DBI_fetch_result( $sql, 'id', 'name' );
 * // or
 * $users = PMA_DBI_fetch_result( $sql, 0, 1 );
 * // produces
 * // $users['123'] = 'John Doe'
 * 
 * $sql = 'SELECT `name` FROM `user`';
 * $users = PMA_DBI_fetch_result( $sql );
 * // produces
 * // $users[] = 'John Doe'
 * </code>
 *
 * @uses    is_string()
 * @uses    is_int()
 * @uses    PMA_DBI_try_query()
 * @uses    PMA_DBI_num_rows()
 * @uses    PMA_DBI_num_fields()
 * @uses    PMA_DBI_fetch_row()
 * @uses    PMA_DBI_fetch_assoc()
 * @uses    PMA_DBI_free_result()
 * @param   string|mysql_result $result query or mysql result
 * @param   string|integer      $key    field-name or offset
 *                                      used as key for array
 * @param   string|integer      $value  value-name or offset
 *                                      used as value for array
 * @param   resource            $link   mysql link
 * @param   mixed               $options    
 * @return  array               resultrows or values indexed by $key
 */
function PMA_DBI_fetch_result( $result, $key = NULL, $value = NULL, $link = NULL, $options = 0 )
{
    $resultrows = array();
    
    if ( is_string( $result ) ) {
        $result = PMA_DBI_try_query( $result, $link, $options );
    }
    
    // return empty array if result is empty or false
    if ( ! $result ) {
        return $resultrows;
    }
    
    $fetch_function = 'PMA_DBI_fetch_assoc';
    
    // no nested array if only one field is in result
    if ( NULL === $key && 1 === PMA_DBI_num_fields( $result ) ) {
        $value = 0;
        $fetch_function = 'PMA_DBI_fetch_row';
    }
    
    // if $key is an integer use non associative mysql fetch function    
    if ( is_int( $key ) ) {
        $fetch_function = 'PMA_DBI_fetch_row';
    }
    
    if ( NULL === $key && NULL === $value ) {
        while ( $row = $fetch_function( $result ) ) {
            $resultrows[] = $row;
        }
    } elseif ( NULL === $key ) {
        while ( $row = $fetch_function( $result ) ) {
            $resultrows[] = $row[$value];
        }
    } elseif ( NULL === $value ) {
        while ( $row = $fetch_function( $result ) ) {
            $resultrows[$row[$key]] = $row;
        }
    } else {
        while ( $row = $fetch_function( $result ) ) {
            $resultrows[$row[$key]] = $row[$value];
        }
    }
    
    PMA_DBI_free_result( $result );
    return $resultrows;
}
?>
