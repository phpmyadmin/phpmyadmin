<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Library that provides common import functions that are used by import plugins
 *
 * @package PhpMyAdmin-Import
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * We need to know something about user
 */
require_once './libraries/check_user_privileges.lib.php';

/**
 * We do this check, DROP DATABASE does not need to be confirmed elsewhere
 */
define('PMA_CHK_DROP', 1);

/**
 * Checks whether timeout is getting close
 *
 * @return boolean true if timeout is close
 * @access public
 */
function PMA_checkTimeout()
{
    global $timestamp, $maximum_time, $timeout_passed;
    if ($maximum_time == 0) {
        return false;
    } elseif ($timeout_passed) {
        return true;
        /* 5 in next row might be too much */
    } elseif ((time() - $timestamp) > ($maximum_time - 5)) {
        $timeout_passed = true;
        return true;
    } else {
        return false;
    }
}

/**
 * Detects what compression the file uses
 *
 * @param string $filepath filename to check
 *
 * @return string MIME type of compression, none for none
 * @access public
 */
function PMA_detectCompression($filepath)
{
    $file = @fopen($filepath, 'rb');
    if (! $file) {
        return false;
    }
    return PMA_Util::getCompressionMimeType($file);
}

/**
 * Runs query inside import buffer. This is needed to allow displaying
 * of last SELECT, SHOW or HANDLER results and similar nice stuff.
 *
 * @param string $sql         query to run
 * @param string $full        query to display, this might be commented
 * @param bool   $controluser whether to use control user for queries
 * @param array  &$sql_data   SQL parse data storage
 *
 * @return void
 * @access public
 */
function PMA_importRunQuery($sql = '', $full = '', $controluser = false,
    &$sql_data = array()
) {
    global $import_run_buffer, $go_sql, $complete_query, $display_query,
        $sql_query, $my_die, $error, $reload,
        $last_query_with_results, $result, $msg,
        $skip_queries, $executed_queries, $max_sql_len, $read_multiply,
        $cfg, $sql_query_disabled, $db, $run_query, $is_superuser;
    $read_multiply = 1;
    if (!isset($import_run_buffer)) {
        // Do we have something to push into buffer?
        $import_run_buffer = PMA_ImportRunQuery_post(
            $import_run_buffer, $sql, $full
        );
        return;
    }

    // Should we skip something?
    if ($skip_queries > 0) {
        $skip_queries--;
        // Do we have something to push into buffer?
        $import_run_buffer = PMA_ImportRunQuery_post(
            $import_run_buffer, $sql, $full
        );
        return;
    }

    if (! empty($import_run_buffer['sql'])
        && trim($import_run_buffer['sql']) != ''
    ) {

        // USE query changes the database, son need to track
        // while running multiple queries
        $is_use_query
            = (/*overload*/mb_stripos($import_run_buffer['sql'], "use ") !== false)
                ? true
                : false;

        $max_sql_len = max(
            $max_sql_len,
            /*overload*/mb_strlen($import_run_buffer['sql'])
        );
        if (! $sql_query_disabled) {
            $sql_query .= $import_run_buffer['full'];
        }
        $pattern = '@^[[:space:]]*DROP[[:space:]]+(IF EXISTS[[:space:]]+)?'
            . 'DATABASE @i';
        if (! $cfg['AllowUserDropDatabase']
            && ! $is_superuser
            && preg_match($pattern, $import_run_buffer['sql'])
        ) {
            $GLOBALS['message'] = PMA_Message::error(
                __('"DROP DATABASE" statements are disabled.')
            );
            $error = true;
        } else {
            $executed_queries++;

            $pattern = '/^[\s]*(SELECT|SHOW|HANDLER)/i';
            if ($run_query
                && $GLOBALS['finished']
                && empty($sql)
                && ! $error
                && ((! empty($import_run_buffer['sql'])
                && preg_match($pattern, $import_run_buffer['sql']))
                || ($executed_queries == 1))
            ) {
                $go_sql = true;
                if (! $sql_query_disabled) {
                    $complete_query = $sql_query;
                    $display_query = $sql_query;
                } else {
                    $complete_query = '';
                    $display_query = '';
                }
                $sql_query = $import_run_buffer['sql'];
                $sql_data['valid_sql'][] = $import_run_buffer['sql'];
                if (! isset($sql_data['valid_queries'])) {
                    $sql_data['valid_queries'] = 0;
                }
                $sql_data['valid_queries']++;

                // If a 'USE <db>' SQL-clause was found,
                // set our current $db to the new one
                list($db, $reload) = PMA_lookForUse(
                    $import_run_buffer['sql'],
                    $db,
                    $reload
                );
            } elseif ($run_query) {

                if ($controluser) {
                    $result = PMA_queryAsControlUser(
                        $import_run_buffer['sql']
                    );
                } else {
                    $result = $GLOBALS['dbi']
                        ->tryQuery($import_run_buffer['sql']);
                }

                $msg = '# ';
                if ($result === false) { // execution failed
                    if (! isset($my_die)) {
                        $my_die = array();
                    }
                    $my_die[] = array(
                        'sql' => $import_run_buffer['full'],
                        'error' => $GLOBALS['dbi']->getError()
                    );

                    $msg .= __('Error');

                    if (! $cfg['IgnoreMultiSubmitErrors']) {
                        $error = true;
                        return;
                    }
                } else {
                    $a_num_rows = (int)@$GLOBALS['dbi']->numRows($result);
                    $a_aff_rows = (int)@$GLOBALS['dbi']->affectedRows();
                    if ($a_num_rows > 0) {
                        $msg .= __('Rows') . ': ' . $a_num_rows;
                        $last_query_with_results = $import_run_buffer['sql'];
                    } elseif ($a_aff_rows > 0) {
                        $message = PMA_Message::getMessageForAffectedRows(
                            $a_aff_rows
                        );
                        $msg .= $message->getMessage();
                    } else {
                        $msg .= __(
                            'MySQL returned an empty result set (i.e. zero '
                            . 'rows).'
                        );
                    }

                    $sql_data = updateSqlData(
                        $sql_data, $a_num_rows, $is_use_query, $import_run_buffer
                    );
                }
                if (! $sql_query_disabled) {
                    $sql_query .= $msg . "\n";
                }

                // If a 'USE <db>' SQL-clause was found and the query
                // succeeded, set our current $db to the new one
                if ($result != false) {
                    list($db, $reload) = PMA_lookForUse(
                        $import_run_buffer['sql'],
                        $db,
                        $reload
                    );
                }

                $pattern = '@^[\s]*(DROP|CREATE)[\s]+(IF EXISTS[[:space:]]+)'
                    . '?(TABLE|DATABASE)[[:space:]]+(.+)@im';
                if ($result != false
                    && preg_match($pattern, $import_run_buffer['sql'])
                ) {
                    $reload = true;
                }
            } // end run query
        } // end if not DROP DATABASE
        // end non empty query
    } elseif (! empty($import_run_buffer['full'])) {
        if ($go_sql) {
            $complete_query .= $import_run_buffer['full'];
            $display_query .= $import_run_buffer['full'];
        } else {
            if (! $sql_query_disabled) {
                $sql_query .= $import_run_buffer['full'];
            }
        }
    }
    // check length of query unless we decided to pass it to sql.php
    // (if $run_query is false, we are just displaying so show
    // the complete query in the textarea)
    if (! $go_sql && $run_query) {
        if (! empty($sql_query)) {
            if (/*overload*/mb_strlen($sql_query) > 50000
                || $executed_queries > 50
                || $max_sql_len > 1000
            ) {
                $sql_query = '';
                $sql_query_disabled = true;
            }
        }
    }

    // Do we have something to push into buffer?
    $import_run_buffer = PMA_ImportRunQuery_post($import_run_buffer, $sql, $full);

    // In case of ROLLBACK, notify the user.
    if (isset($_REQUEST['rollback_query'])) {
        $msg .= __('[ROLLBACK occurred.]');
    }
}

/**
 * Update $sql_data
 *
 * @param array $sql_data          SQL data
 * @param int   $a_num_rows        Number of rows
 * @param bool  $is_use_query      Query is used
 * @param array $import_run_buffer Import buffer
 *
 * @return array
 */
function updateSqlData($sql_data, $a_num_rows, $is_use_query, $import_run_buffer)
{
    if (($a_num_rows > 0) || $is_use_query) {
        $sql_data['valid_sql'][] = $import_run_buffer['sql'];
        if (!isset($sql_data['valid_queries'])) {
            $sql_data['valid_queries'] = 0;
        }
        $sql_data['valid_queries']++;
    }
    return $sql_data;
}

/**
 * Return import run buffer
 *
 * @param array  $import_run_buffer Buffer of queries for import
 * @param string $sql               SQL query
 * @param string $full              Query to display
 *
 * @return array Buffer of queries for import
 */
function PMA_ImportRunQuery_post($import_run_buffer, $sql, $full)
{
    if (!empty($sql) || !empty($full)) {
        $import_run_buffer = array('sql' => $sql, 'full' => $full);
        return $import_run_buffer;
    } else {
        unset($GLOBALS['import_run_buffer']);
        return $import_run_buffer;
    }
}

/**
 * Looks for the presence of USE to possibly change current db
 *
 * @param string $buffer buffer to examine
 * @param string $db     current db
 * @param bool   $reload reload
 *
 * @return array (current or new db, whether to reload)
 * @access public
 */
function PMA_lookForUse($buffer, $db, $reload)
{
    if (preg_match('@^[\s]*USE[[:space:]]+([\S]+)@i', $buffer, $match)) {
        $db = trim($match[1]);
        $db = trim($db, ';'); // for example, USE abc;

        // $db must not contain the escape characters generated by backquote()
        // ( used in PMA_buildSQL() as: backquote($db_name), and then called
        // in PMA_importRunQuery() which in turn calls PMA_lookForUse() )
        $db = PMA_Util::unQuote($db);

        $reload = true;
    }
    return(array($db, $reload));
}


/**
 * Returns next part of imported file/buffer
 *
 * @param int $size size of buffer to read
 *                  (this is maximal size function will return)
 *
 * @return string part of file/buffer
 * @access public
 */
function PMA_importGetNextChunk($size = 32768)
{
    global $compression, $import_handle, $charset_conversion, $charset_of_file,
        $read_multiply;

    // Add some progression while reading large amount of data
    if ($read_multiply <= 8) {
        $size *= $read_multiply;
    } else {
        $size *= 8;
    }
    $read_multiply++;

    // We can not read too much
    if ($size > $GLOBALS['read_limit']) {
        $size = $GLOBALS['read_limit'];
    }

    if (PMA_checkTimeout()) {
        return false;
    }
    if ($GLOBALS['finished']) {
        return true;
    }

    if ($GLOBALS['import_file'] == 'none') {
        // Well this is not yet supported and tested,
        // but should return content of textarea
        if (/*overload*/mb_strlen($GLOBALS['import_text']) < $size) {
            $GLOBALS['finished'] = true;
            return $GLOBALS['import_text'];
        } else {
            $r = /*overload*/mb_substr($GLOBALS['import_text'], 0, $size);
            $GLOBALS['offset'] += $size;
            $GLOBALS['import_text'] = /*overload*/
                mb_substr($GLOBALS['import_text'], $size);
            return $r;
        }
    }

    switch ($compression) {
    case 'application/bzip2':
        $result = bzread($import_handle, $size);
        $GLOBALS['finished'] = feof($import_handle);
        break;
    case 'application/gzip':
        $result = gzread($import_handle, $size);
        $GLOBALS['finished'] = feof($import_handle);
        break;
    case 'application/zip':
        $result = /*overload*/mb_substr($GLOBALS['import_text'], 0, $size);
        $GLOBALS['import_text'] = /*overload*/mb_substr(
            $GLOBALS['import_text'],
            $size
        );
        $GLOBALS['finished'] = empty($GLOBALS['import_text']);
        break;
    case 'none':
        $result = fread($import_handle, $size);
        $GLOBALS['finished'] = feof($import_handle);
        break;
    }
    $GLOBALS['offset'] += $size;

    if ($charset_conversion) {
        return PMA_convertString($charset_of_file, 'utf-8', $result);
    }

    /**
     * Skip possible byte order marks (I do not think we need more
     * charsets, but feel free to add more, you can use wikipedia for
     * reference: <http://en.wikipedia.org/wiki/Byte_Order_Mark>)
     *
     * @todo BOM could be used for charset autodetection
     */
    if ($GLOBALS['offset'] == $size) {
        // UTF-8
        if (strncmp($result, "\xEF\xBB\xBF", 3) == 0) {
            $result = /*overload*/mb_substr($result, 3);
            // UTF-16 BE, LE
        } elseif (strncmp($result, "\xFE\xFF", 2) == 0
            || strncmp($result, "\xFF\xFE", 2) == 0
        ) {
            $result = /*overload*/mb_substr($result, 2);
        }
    }
    return $result;
}

/**
 * Returns the "Excel" column name (i.e. 1 = "A", 26 = "Z", 27 = "AA", etc.)
 *
 * This functions uses recursion to build the Excel column name.
 *
 * The column number (1-26) is converted to the responding
 * ASCII character (A-Z) and returned.
 *
 * If the column number is bigger than 26 (= num of letters in alphabet),
 * an extra character needs to be added. To find this extra character,
 * the number is divided by 26 and this value is passed to another instance
 * of the same function (hence recursion). In that new instance the number is
 * evaluated again, and if it is still bigger than 26, it is divided again
 * and passed to another instance of the same function. This continues until
 * the number is smaller than 26. Then the last called function returns
 * the corresponding ASCII character to the function that called it.
 * Each time a called function ends an extra character is added to the column name.
 * When the first function is reached, the last character is added and the complete
 * column name is returned.
 *
 * @param int $num the column number
 *
 * @return string The column's "Excel" name
 * @access  public
 */
function PMA_getColumnAlphaName($num)
{
    $A = 65; // ASCII value for capital "A"
    $col_name = "";

    if ($num > 26) {
        $div = (int)($num / 26);
        $remain = (int)($num % 26);

        // subtract 1 of divided value in case the modulus is 0,
        // this is necessary because A-Z has no 'zero'
        if ($remain == 0) {
            $div--;
        }

        // recursive function call
        $col_name = PMA_getColumnAlphaName($div);
        // use modulus as new column number
        $num = $remain;
    }

    if ($num == 0) {
        // use 'Z' if column number is 0,
        // this is necessary because A-Z has no 'zero'
        $col_name .= /*overload*/mb_chr(($A + 26) - 1);
    } else {
        // convert column number to ASCII character
        $col_name .= /*overload*/mb_chr(($A + $num) - 1);
    }

    return $col_name;
}

/**
 * Returns the column number based on the Excel name.
 * So "A" = 1, "Z" = 26, "AA" = 27, etc.
 *
 * Basically this is a base26 (A-Z) to base10 (0-9) conversion.
 * It iterates through all characters in the column name and
 * calculates the corresponding value, based on character value
 * (A = 1, ..., Z = 26) and position in the string.
 *
 * @param string $name column name(i.e. "A", or "BC", etc.)
 *
 * @return int The column number
 * @access  public
 */
function PMA_getColumnNumberFromName($name)
{
    if (empty($name)) {
        return 0;
    }

    $name = /*overload*/mb_strtoupper($name);
    $num_chars = /*overload*/mb_strlen($name);
    $column_number = 0;
    for ($i = 0; $i < $num_chars; ++$i) {
        // read string from back to front
        $char_pos = ($num_chars - 1) - $i;

        // convert capital character to ASCII value
        // and subtract 64 to get corresponding decimal value
        // ASCII value of "A" is 65, "B" is 66, etc.
        // Decimal equivalent of "A" is 1, "B" is 2, etc.
        $number = (int)(/*overload*/mb_ord($name[$char_pos]) - 64);

        // base26 to base10 conversion : multiply each number
        // with corresponding value of the position, in this case
        // $i=0 : 1; $i=1 : 26; $i=2 : 676; ...
        $column_number += $number * PMA_Util::pow(26, $i);
    }
    return $column_number;
}

/**
 * Constants definitions
 */

/* MySQL type defs */
define("NONE",      0);
define("VARCHAR",   1);
define("INT",       2);
define("DECIMAL",   3);
define("BIGINT",    4);
define("GEOMETRY",  5);

/* Decimal size defs */
define("M",         0);
define("D",         1);
define("FULL",      2);

/* Table array defs */
define("TBL_NAME",  0);
define("COL_NAMES", 1);
define("ROWS",      2);

/* Analysis array defs */
define("TYPES",        0);
define("SIZES",        1);
define("FORMATTEDSQL", 2);

/**
 * Obtains the precision (total # of digits) from a size of type decimal
 *
 * @param string $last_cumulative_size Size of type decimal
 *
 * @return int Precision of the given decimal size notation
 * @access  public
 */
function PMA_getDecimalPrecision($last_cumulative_size)
{
    return (int)substr(
        $last_cumulative_size,
        0,
        strpos($last_cumulative_size, ",")
    );
}

/**
 * Obtains the scale (# of digits to the right of the decimal point)
 * from a size of type decimal
 *
 * @param string $last_cumulative_size Size of type decimal
 *
 * @return int Scale of the given decimal size notation
 * @access  public
 */
function PMA_getDecimalScale($last_cumulative_size)
{
    return (int)substr(
        $last_cumulative_size,
        (strpos($last_cumulative_size, ",") + 1),
        (strlen($last_cumulative_size) - strpos($last_cumulative_size, ","))
    );
}

/**
 * Obtains the decimal size of a given cell
 *
 * @param string $cell cell content
 *
 * @return array Contains the precision, scale, and full size
 *                representation of the given decimal cell
 * @access  public
 */
function PMA_getDecimalSize($cell)
{
    $curr_size = /*overload*/mb_strlen((string)$cell);
    $decPos = /*overload*/mb_strpos($cell, ".");
    $decPrecision = ($curr_size - 1) - $decPos;

    $m = $curr_size - 1;
    $d = $decPrecision;

    return array($m, $d, ($m . "," . $d));
}

/**
 * Obtains the size of the given cell
 *
 * @param string $last_cumulative_size Last cumulative column size
 * @param int    $last_cumulative_type Last cumulative column type
 *                                     (NONE or VARCHAR or DECIMAL or INT or BIGINT)
 * @param int    $curr_type            Type of the current cell
 *                                     (NONE or VARCHAR or DECIMAL or INT or BIGINT)
 * @param string $cell                 The current cell
 *
 * @return string  Size of the given cell in the type-appropriate format
 * @access  public
 *
 * @todo    Handle the error cases more elegantly
 */
function PMA_detectSize($last_cumulative_size, $last_cumulative_type,
    $curr_type, $cell
) {
    $curr_size = /*overload*/mb_strlen((string)$cell);

    /**
     * If the cell is NULL, don't treat it as a varchar
     */
    if (! strcmp('NULL', $cell)) {
        return $last_cumulative_size;
    } elseif ($curr_type == VARCHAR) {
        /**
         * What to do if the current cell is of type VARCHAR
         */
        /**
         * The last cumulative type was VARCHAR
         */
        if ($last_cumulative_type == VARCHAR) {
            if ($curr_size >= $last_cumulative_size) {
                return $curr_size;
            } else {
                return $last_cumulative_size;
            }
        } elseif ($last_cumulative_type == DECIMAL) {
            /**
             * The last cumulative type was DECIMAL
             */
            $oldM = PMA_getDecimalPrecision($last_cumulative_size);

            if ($curr_size >= $oldM) {
                return $curr_size;
            } else {
                return $oldM;
            }
        } elseif ($last_cumulative_type == BIGINT || $last_cumulative_type == INT) {
            /**
             * The last cumulative type was BIGINT or INT
             */
            if ($curr_size >= $last_cumulative_size) {
                return $curr_size;
            } else {
                return $last_cumulative_size;
            }
        } elseif (! isset($last_cumulative_type) || $last_cumulative_type == NONE) {
            /**
             * This is the first row to be analyzed
             */
            return $curr_size;
        } else {
            /**
             * An error has DEFINITELY occurred
             */
            /**
             * TODO: Handle this MUCH more elegantly
             */

            return -1;
        }
    } elseif ($curr_type == DECIMAL) {
        /**
         * What to do if the current cell is of type DECIMAL
         */
        /**
         * The last cumulative type was VARCHAR
         */
        if ($last_cumulative_type == VARCHAR) {
            /* Convert $last_cumulative_size from varchar to decimal format */
            $size = PMA_getDecimalSize($cell);

            if ($size[M] >= $last_cumulative_size) {
                return $size[M];
            } else {
                return $last_cumulative_size;
            }
        } elseif ($last_cumulative_type == DECIMAL) {
            /**
             * The last cumulative type was DECIMAL
             */
            $size = PMA_getDecimalSize($cell);

            $oldM = PMA_getDecimalPrecision($last_cumulative_size);
            $oldD = PMA_getDecimalScale($last_cumulative_size);

            /* New val if M or D is greater than current largest */
            if ($size[M] > $oldM || $size[D] > $oldD) {
                /* Take the largest of both types */
                return (string) ((($size[M] > $oldM) ? $size[M] : $oldM)
                    . "," . (($size[D] > $oldD) ? $size[D] : $oldD));
            } else {
                return $last_cumulative_size;
            }
        } elseif ($last_cumulative_type == BIGINT || $last_cumulative_type == INT) {
            /**
             * The last cumulative type was BIGINT or INT
             */
            /* Convert $last_cumulative_size from int to decimal format */
            $size = PMA_getDecimalSize($cell);

            if ($size[M] >= $last_cumulative_size) {
                return $size[FULL];
            } else {
                return ($last_cumulative_size . "," . $size[D]);
            }
        } elseif (! isset($last_cumulative_type) || $last_cumulative_type == NONE) {
            /**
             * This is the first row to be analyzed
             */
            /* First row of the column */
            $size = PMA_getDecimalSize($cell);

            return $size[FULL];
        } else {
            /**
             * An error has DEFINITELY occurred
             */
            /**
             * TODO: Handle this MUCH more elegantly
             */

            return -1;
        }
    } elseif ($curr_type == BIGINT || $curr_type == INT) {
        /**
         * What to do if the current cell is of type BIGINT or INT
         */
        /**
         * The last cumulative type was VARCHAR
         */
        if ($last_cumulative_type == VARCHAR) {
            if ($curr_size >= $last_cumulative_size) {
                return $curr_size;
            } else {
                return $last_cumulative_size;
            }
        } elseif ($last_cumulative_type == DECIMAL) {
            /**
             * The last cumulative type was DECIMAL
             */
            $oldM = PMA_getDecimalPrecision($last_cumulative_size);
            $oldD = PMA_getDecimalScale($last_cumulative_size);
            $oldInt = $oldM - $oldD;
            $newInt = /*overload*/mb_strlen((string)$cell);

            /* See which has the larger integer length */
            if ($oldInt >= $newInt) {
                /* Use old decimal size */
                return $last_cumulative_size;
            } else {
                /* Use $newInt + $oldD as new M */
                return (($newInt + $oldD) . "," . $oldD);
            }
        } elseif ($last_cumulative_type == BIGINT || $last_cumulative_type == INT) {
            /**
             * The last cumulative type was BIGINT or INT
             */
            if ($curr_size >= $last_cumulative_size) {
                return $curr_size;
            } else {
                return $last_cumulative_size;
            }
        } elseif (! isset($last_cumulative_type) || $last_cumulative_type == NONE) {
            /**
             * This is the first row to be analyzed
             */
            return $curr_size;
        } else {
            /**
             * An error has DEFINITELY occurred
             */
            /**
             * TODO: Handle this MUCH more elegantly
             */

            return -1;
        }
    } else {
        /**
         * An error has DEFINITELY occurred
         */
        /**
         * TODO: Handle this MUCH more elegantly
         */

        return -1;
    }
}

/**
 * Determines what MySQL type a cell is
 *
 * @param int    $last_cumulative_type Last cumulative column type
 *                                     (VARCHAR or INT or BIGINT or DECIMAL or NONE)
 * @param string $cell                 String representation of the cell for which
 *                                     a best-fit type is to be determined
 *
 * @return int  The MySQL type representation
 *               (VARCHAR or INT or BIGINT or DECIMAL or NONE)
 * @access  public
 */
function PMA_detectType($last_cumulative_type, $cell)
{
    /**
     * If numeric, determine if decimal, int or bigint
     * Else, we call it varchar for simplicity
     */

    if (! strcmp('NULL', $cell)) {
        if ($last_cumulative_type === null || $last_cumulative_type == NONE) {
            return NONE;
        }

        return $last_cumulative_type;
    }

    if (!is_numeric($cell)) {
        return VARCHAR;
    }

    if ($cell == (string)(float)$cell
        && /*overload*/mb_strpos($cell, ".") !== false
        && /*overload*/mb_substr_count($cell, ".") == 1
    ) {
        return DECIMAL;
    }

    if (abs($cell) > 2147483647) {
        return BIGINT;
    }

    return INT;
}

/**
 * Determines if the column types are int, decimal, or string
 *
 * @param array &$table array(string $table_name, array $col_names, array $rows)
 *
 * @return array    array(array $types, array $sizes)
 * @access  public
 *
 * @link http://wiki.phpmyadmin.net/pma/Import
 *
 * @todo    Handle the error case more elegantly
 */
function PMA_analyzeTable(&$table)
{
    /* Get number of rows in table */
    $numRows = count($table[ROWS]);
    /* Get number of columns */
    $numCols = count($table[COL_NAMES]);
    /* Current type for each column */
    $types = array();
    $sizes = array();

    /* Initialize $sizes to all 0's */
    for ($i = 0; $i < $numCols; ++$i) {
        $sizes[$i] = 0;
    }

    /* Initialize $types to NONE */
    for ($i = 0; $i < $numCols; ++$i) {
        $types[$i] = NONE;
    }

    /* Temp vars */
    $curr_type = NONE;

    /* If the passed array is not of the correct form, do not process it */
    if (!is_array($table)
        || is_array($table[TBL_NAME])
        || !is_array($table[COL_NAMES])
        || !is_array($table[ROWS])
    ) {
        /**
         * TODO: Handle this better
         */

        return false;
    }

    /* Analyze each column */
    for ($i = 0; $i < $numCols; ++$i) {
        /* Analyze the column in each row */
        for ($j = 0; $j < $numRows; ++$j) {
            /* Determine type of the current cell */
            $curr_type = PMA_detectType($types[$i], $table[ROWS][$j][$i]);
            /* Determine size of the current cell */
            $sizes[$i] = PMA_detectSize(
                $sizes[$i],
                $types[$i],
                $curr_type,
                $table[ROWS][$j][$i]
            );

            /**
             * If a type for this column has already been declared,
             * only alter it if it was a number and a varchar was found
             */
            if ($curr_type != NONE) {
                if ($curr_type == VARCHAR) {
                    $types[$i] = VARCHAR;
                } else if ($curr_type == DECIMAL) {
                    if ($types[$i] != VARCHAR) {
                        $types[$i] = DECIMAL;
                    }
                } else if ($curr_type == BIGINT) {
                    if ($types[$i] != VARCHAR && $types[$i] != DECIMAL) {
                        $types[$i] = BIGINT;
                    }
                } else if ($curr_type == INT) {
                    if ($types[$i] != VARCHAR
                        && $types[$i] != DECIMAL
                        && $types[$i] != BIGINT
                    ) {
                        $types[$i] = INT;
                    }
                }
            }
        }
    }

    /* Check to ensure that all types are valid */
    $len = count($types);
    for ($n = 0; $n < $len; ++$n) {
        if (! strcmp(NONE, $types[$n])) {
            $types[$n] = VARCHAR;
            $sizes[$n] = '10';
        }
    }

    return array($types, $sizes);
}

/* Needed to quell the beast that is PMA_Message */
$import_notice = null;

/**
 * Builds and executes SQL statements to create the database and tables
 * as necessary, as well as insert all the data.
 *
 * @param string $db_name         Name of the database
 * @param array  &$tables         Array of tables for the specified database
 * @param array  &$analyses       Analyses of the tables
 * @param array  &$additional_sql Additional SQL statements to be executed
 * @param array  $options         Associative array of options
 *
 * @return void
 * @access  public
 *
 * @link http://wiki.phpmyadmin.net/pma/Import
 */
function PMA_buildSQL($db_name, &$tables, &$analyses = null,
    &$additional_sql = null, $options = null
) {
    /* Take care of the options */
    if (isset($options['db_collation'])&& ! is_null($options['db_collation'])) {
        $collation = $options['db_collation'];
    } else {
        $collation = "utf8_general_ci";
    }

    if (isset($options['db_charset']) && ! is_null($options['db_charset'])) {
        $charset = $options['db_charset'];
    } else {
        $charset = "utf8";
    }

    if (isset($options['create_db'])) {
        $create_db = $options['create_db'];
    } else {
        $create_db = true;
    }

    /* Create SQL code to handle the database */
    $sql = array();

    if ($create_db) {
        if (PMA_DRIZZLE) {
            $sql[] = "CREATE DATABASE IF NOT EXISTS " . PMA_Util::backquote($db_name)
                . " COLLATE " . $collation;
        } else {
            $sql[] = "CREATE DATABASE IF NOT EXISTS " . PMA_Util::backquote($db_name)
                . " DEFAULT CHARACTER SET " . $charset . " COLLATE " . $collation;
        }
    }

    /**
     * The calling plug-in should include this statement,
     * if necessary, in the $additional_sql parameter
     *
     * $sql[] = "USE " . backquote($db_name);
     */

    /* Execute the SQL statements create above */
    $sql_len = count($sql);
    for ($i = 0; $i < $sql_len; ++$i) {
        PMA_importRunQuery($sql[$i], $sql[$i]);
    }

    /* No longer needed */
    unset($sql);

    /* Run the $additional_sql statements supplied by the caller plug-in */
    if ($additional_sql != null) {
        /* Clean the SQL first */
        $additional_sql_len = count($additional_sql);

        /**
         * Only match tables for now, because CREATE IF NOT EXISTS
         * syntax is lacking or nonexisting for views, triggers,
         * functions, and procedures.
         *
         * See: http://bugs.mysql.com/bug.php?id=15287
         *
         * To the best of my knowledge this is still an issue.
         *
         * $pattern = 'CREATE (TABLE|VIEW|TRIGGER|FUNCTION|PROCEDURE)';
         */
        $pattern = '/CREATE [^`]*(TABLE)/';
        $replacement = 'CREATE \\1 IF NOT EXISTS';

        /* Change CREATE statements to CREATE IF NOT EXISTS to support
         * inserting into existing structures
         */
        for ($i = 0; $i < $additional_sql_len; ++$i) {
            $additional_sql[$i] = preg_replace(
                $pattern,
                $replacement,
                $additional_sql[$i]
            );
            /* Execute the resulting statements */
            PMA_importRunQuery($additional_sql[$i], $additional_sql[$i]);
        }
    }

    if ($analyses != null) {
        $type_array = array(
            NONE => "NULL",
            VARCHAR => "varchar",
            INT => "int",
            DECIMAL => "decimal",
            BIGINT => "bigint",
            GEOMETRY => 'geometry'
        );

        /* TODO: Do more checking here to make sure they really are matched */
        if (count($tables) != count($analyses)) {
            exit();
        }

        /* Create SQL code to create the tables */
        $num_tables = count($tables);
        for ($i = 0; $i < $num_tables; ++$i) {
            $num_cols = count($tables[$i][COL_NAMES]);
            $tempSQLStr = "CREATE TABLE IF NOT EXISTS "
            . PMA_Util::backquote($db_name)
            . '.' . PMA_Util::backquote($tables[$i][TBL_NAME]) . " (";
            for ($j = 0; $j < $num_cols; ++$j) {
                $size = $analyses[$i][SIZES][$j];
                if ((int)$size == 0) {
                    $size = 10;
                }

                $tempSQLStr .= PMA_Util::backquote($tables[$i][COL_NAMES][$j]) . " "
                    . $type_array[$analyses[$i][TYPES][$j]];
                if ($analyses[$i][TYPES][$j] != GEOMETRY) {
                    $tempSQLStr .= "(" . $size . ")";
                }

                if ($j != (count($tables[$i][COL_NAMES]) - 1)) {
                    $tempSQLStr .= ", ";
                }
            }
            $tempSQLStr .= ")"
                . (PMA_DRIZZLE ? "" : " DEFAULT CHARACTER SET " . $charset)
                . " COLLATE " . $collation . ";";

            /**
             * Each SQL statement is executed immediately
             * after it is formed so that we don't have
             * to store them in a (possibly large) buffer
             */
            PMA_importRunQuery($tempSQLStr, $tempSQLStr);
        }
    }

    /**
     * Create the SQL statements to insert all the data
     *
     * Only one insert query is formed for each table
     */
    $tempSQLStr = "";
    $col_count = 0;
    $num_tables = count($tables);
    for ($i = 0; $i < $num_tables; ++$i) {
        $num_cols = count($tables[$i][COL_NAMES]);
        $num_rows = count($tables[$i][ROWS]);

        $tempSQLStr = "INSERT INTO " . PMA_Util::backquote($db_name) . '.'
            . PMA_Util::backquote($tables[$i][TBL_NAME]) . " (";

        for ($m = 0; $m < $num_cols; ++$m) {
            $tempSQLStr .= PMA_Util::backquote($tables[$i][COL_NAMES][$m]);

            if ($m != ($num_cols - 1)) {
                $tempSQLStr .= ", ";
            }
        }

        $tempSQLStr .= ") VALUES ";

        for ($j = 0; $j < $num_rows; ++$j) {
            $tempSQLStr .= "(";

            for ($k = 0; $k < $num_cols; ++$k) {
                // If fully formatted SQL, no need to enclose
                // with apostrophes, add slashes etc.
                if ($analyses != null
                    && isset($analyses[$i][FORMATTEDSQL][$col_count])
                    && $analyses[$i][FORMATTEDSQL][$col_count] == true
                ) {
                    $tempSQLStr .= (string) $tables[$i][ROWS][$j][$k];
                } else {
                    if ($analyses != null) {
                        $is_varchar = ($analyses[$i][TYPES][$col_count] === VARCHAR);
                    } else {
                        $is_varchar = ! is_numeric($tables[$i][ROWS][$j][$k]);
                    }

                    /* Don't put quotes around NULL fields */
                    if (! strcmp($tables[$i][ROWS][$j][$k], 'NULL')) {
                        $is_varchar = false;
                    }

                    $tempSQLStr .= (($is_varchar) ? "'" : "");
                    $tempSQLStr .= PMA_Util::sqlAddSlashes(
                        (string) $tables[$i][ROWS][$j][$k]
                    );
                    $tempSQLStr .= (($is_varchar) ? "'" : "");
                }

                if ($k != ($num_cols - 1)) {
                    $tempSQLStr .= ", ";
                }

                if ($col_count == ($num_cols - 1)) {
                    $col_count = 0;
                } else {
                    $col_count++;
                }

                /* Delete the cell after we are done with it */
                unset($tables[$i][ROWS][$j][$k]);
            }

            $tempSQLStr .= ")";

            if ($j != ($num_rows - 1)) {
                $tempSQLStr .= ",\n ";
            }

            $col_count = 0;
            /* Delete the row after we are done with it */
            unset($tables[$i][ROWS][$j]);
        }

        $tempSQLStr .= ";";

        /**
         * Each SQL statement is executed immediately
         * after it is formed so that we don't have
         * to store them in a (possibly large) buffer
         */
        PMA_importRunQuery($tempSQLStr, $tempSQLStr);
    }

    /* No longer needed */
    unset($tempSQLStr);

    /**
     * A work in progress
     */

    /* Add the viewable structures from $additional_sql
     * to $tables so they are also displayed
     */
    $view_pattern = '@VIEW `[^`]+`\.`([^`]+)@';
    $table_pattern = '@CREATE TABLE IF NOT EXISTS `([^`]+)`@';
    /* Check a third pattern to make sure its not a "USE `db_name`;" statement */

    $regs = array();

    $inTables = false;

    $additional_sql_len = count($additional_sql);
    for ($i = 0; $i < $additional_sql_len; ++$i) {
        preg_match($view_pattern, $additional_sql[$i], $regs);

        if (count($regs) == 0) {
            preg_match($table_pattern, $additional_sql[$i], $regs);
        }

        if (count($regs)) {
            for ($n = 0; $n < $num_tables; ++$n) {
                if (! strcmp($regs[1], $tables[$n][TBL_NAME])) {
                    $inTables = true;
                    break;
                }
            }

            if (! $inTables) {
                $tables[] = array(TBL_NAME => $regs[1]);
            }
        }

        /* Reset the array */
        $regs = array();
        $inTables = false;
    }

    $params = array('db' => (string)$db_name);
    $db_url = 'db_structure.php' . PMA_URL_getCommon($params);
    $db_ops_url = 'db_operations.php' . PMA_URL_getCommon($params);

    $message = '<br /><br />';
    $message .= '<strong>' . __('The following structures have either been created or altered. Here you can:') . '</strong><br />';
    $message .= '<ul><li>' . __("View a structure's contents by clicking on its name.") . '</li>';
    $message .= '<li>' . __('Change any of its settings by clicking the corresponding "Options" link.') . '</li>';
    $message .= '<li>' . __('Edit structure by following the "Structure" link.') . '</li>';
    $message .= sprintf(
        '<br /><li><a href="%s" title="%s">%s</a> (<a href="%s" title="%s">'
        . __('Options') . '</a>)</li>',
        $db_url,
        sprintf(
            __('Go to database: %s'),
            htmlspecialchars(PMA_Util::backquote($db_name))
        ),
        htmlspecialchars($db_name),
        $db_ops_url,
        sprintf(
            __('Edit settings for %s'),
            htmlspecialchars(PMA_Util::backquote($db_name))
        )
    );

    $message .= '<ul>';

    unset($params);

    $num_tables = count($tables);
    for ($i = 0; $i < $num_tables; ++$i) {
        $params = array(
             'db' => (string) $db_name,
             'table' => (string) $tables[$i][TBL_NAME]
        );
        $tbl_url = 'sql.php' . PMA_URL_getCommon($params);
        $tbl_struct_url = 'tbl_structure.php' . PMA_URL_getCommon($params);
        $tbl_ops_url = 'tbl_operations.php' . PMA_URL_getCommon($params);

        unset($params);

        if (! PMA_Table::isView($db_name, $tables[$i][TBL_NAME])) {
            $message .= sprintf(
                '<li><a href="%s" title="%s">%s</a> (<a href="%s" title="%s">' . __('Structure') . '</a>) (<a href="%s" title="%s">' . __('Options') . '</a>)</li>',
                $tbl_url,
                sprintf(
                    __('Go to table: %s'),
                    htmlspecialchars(
                        PMA_Util::backquote($tables[$i][TBL_NAME])
                    )
                ),
                htmlspecialchars($tables[$i][TBL_NAME]),
                $tbl_struct_url,
                sprintf(
                    __('Structure of %s'),
                    htmlspecialchars(
                        PMA_Util::backquote($tables[$i][TBL_NAME])
                    )
                ),
                $tbl_ops_url,
                sprintf(
                    __('Edit settings for %s'),
                    htmlspecialchars(
                        PMA_Util::backquote($tables[$i][TBL_NAME])
                    )
                )
            );
        } else {
            $message .= sprintf(
                '<li><a href="%s" title="%s">%s</a></li>',
                $tbl_url,
                sprintf(
                    __('Go to view: %s'),
                    htmlspecialchars(
                        PMA_Util::backquote($tables[$i][TBL_NAME])
                    )
                ),
                htmlspecialchars($tables[$i][TBL_NAME])
            );
        }
    }

    $message .= '</ul></ul>';

    global $import_notice;
    $import_notice = $message;

    unset($tables);
}


/**
 * Stops the import on (mostly upload/file related) error
 *
 * @param PMA_Message $error_message The error message
 *
 * @return void
 * @access  public
 *
 */
function PMA_stopImport( PMA_Message $error_message )
{
    global $import_handle, $file_to_unlink;

    // Close open handles
    if ($import_handle !== false && $import_handle !== null) {
        fclose($import_handle);
    }

    // Delete temporary file
    if ($file_to_unlink != '') {
        unlink($file_to_unlink);
    }
    $msg = $error_message->getDisplay();
    $_SESSION['Import_message']['message'] = $msg;

    $response = PMA_Response::getInstance();
    $response->isSuccess(false);
    $response->addJSON('message', PMA_Message::error($msg));

    exit;
}

/**
 * Handles request for Simulation of UPDATE/DELETE queries.
 *
 * @return void
 */
function PMA_handleSimulateDMLRequest()
{
    $response = PMA_Response::getInstance();
    $error = false;
    $error_msg = __('Only single-table UPDATE and DELETE queries can be simulated.');
    $sql_delimiter = $_REQUEST['sql_delimiter'];
    $sql_data = array();
    $queries = explode($sql_delimiter, $GLOBALS['sql_query']);
    foreach ($queries as $sql_query) {
        if (empty($sql_query)) {
            continue;
        }

        // Parse and Analyze the query.
        $parsed_sql = PMA_SQP_parse($sql_query);
        $analyzed_sql = PMA_SQP_analyze($parsed_sql);
        $analyzed_sql_results = array(
            'parsed_sql' => $parsed_sql,
            'analyzed_sql' => $analyzed_sql
        );

        // Only UPDATE/DELETE queries accepted.
        $query_type = $analyzed_sql_results['analyzed_sql'][0]['querytype'];
        if ($query_type != 'UPDATE' && $query_type != 'DELETE') {
            $error = $error_msg;
            break;
        }

        // Only single-table queries accepted.
        $table_references = PMA_getTableReferences($analyzed_sql_results);
        $table_references = $table_references ? $table_references : '';
        if (preg_match('/JOIN/i', $table_references)) {
            $error = $error_msg;
            break;
        } else {
            $tables = explode(',', $table_references);
            if (count($tables) > 1) {
                $error = $error_msg;
                break;
            }
        }

        // Get the matched rows for the query.
        $result = PMA_getMatchedRows($analyzed_sql_results);
        if (! $error = $GLOBALS['dbi']->getError()) {
            $sql_data[] = $result;
        } else {
            break;
        }
    }

    if ($error) {
        $message = PMA_Message::rawError($error);
        $response->addJSON('message', $message);
        $response->addJSON('sql_data', false);
    } else {
        $response->addJSON('sql_data', $sql_data);
    }
}

/**
 * Find the matching rows for UPDATE/DELETE query.
 *
 * @param array $analyzed_sql_results Analyzed SQL results from parser.
 *
 * @return mixed
 */
function PMA_getMatchedRows($analyzed_sql_results = array())
{
    // Get the query type.
    $query_type = (isset($analyzed_sql_results['analyzed_sql'][0]['querytype']))
        ? $analyzed_sql_results['analyzed_sql'][0]['querytype']
        : '';

    $matched_row_query = '';
    if ($query_type == 'DELETE') {
        $matched_row_query = PMA_getSimulatedDeleteQuery($analyzed_sql_results);
    } else if ($query_type == 'UPDATE') {
        $matched_row_query = PMA_getSimulatedUpdateQuery($analyzed_sql_results);
    }

    // Execute the query and get the number of matched rows.
    $matched_rows = PMA_executeMatchedRowQuery($matched_row_query);
    // URL to matched rows.
    $_url_params = array(
        'db'        => $GLOBALS['db'],
        'sql_query' => $matched_row_query
    );
    $matched_rows_url  = 'sql.php' . PMA_URL_getCommon($_url_params);

    return array(
        'sql_query' => PMA_Util::formatSql(
            $analyzed_sql_results['parsed_sql']['raw']
        ),
        'matched_rows' => $matched_rows,
        'matched_rows_url' => $matched_rows_url
    );
}

/**
 * Transforms a UPDATE query into SELECT statement.
 *
 * @param array $analyzed_sql_results Analyzed SQL results from parser.
 *
 * @return string SQL query
 */
function PMA_getSimulatedUpdateQuery($analyzed_sql_results)
{
    $where_clause = '';
    $extra_where_clause = array();
    $target_cols = array();

    $prev_term = '';
    $i = 0;
    $in_function = 0;
    foreach ($analyzed_sql_results['parsed_sql'] as $key => $term) {
        if (! isset($get_set_expr)
            && preg_match(
                '/\bSET\b/i',
                isset($term['data']) ? $term['data'] : ''
            )
        ) {
            $get_set_expr = true;
            continue;
        }

        if (isset($get_set_expr)) {
            if (preg_match(
                '/\bWHERE\b|\bORDER BY\b|\bLIMIT\b/i',
                isset($term['data']) ? $term['data'] : ''
            )
            ) {
                break;
            }
            if(!$in_function){
                if ($term['type'] == 'punct_listsep') {
                    $extra_where_clause[] = ' OR ';
                } else if ($term['type'] == 'punct') {
                    $extra_where_clause[] = ' <> ';
                } else if($term['type'] == 'alpha_functionName') {
                    array_pop($extra_where_clause);
                    array_pop($extra_where_clause);
                } else {
                    $extra_where_clause[] = $term['data'];
                }
            }
            else if($term['type'] == 'punct_bracket_close_round') {
                $in_function--;
            }

            if($term['type'] == 'alpha_functionName') {
                $in_function++;
            }

            // Get columns in SET expression.
            if ($prev_term != 'punct') {
                if ($term['type'] != 'punct_listsep'
                    && $term['type'] != 'punct'
                    && $term['type'] != 'punct_bracket_open_round'
                    && $term['type'] != 'punct_bracket_close_round'
                    && !$in_function
                    && isset($term['data'])
                ) {
                    if (isset($target_cols[$i])) {
                        $target_cols[$i] .= $term['data'];
                    } else {
                        $target_cols[$i] = $term['data'];
                    }
                }
            } else {
                $i++;
            }

            $prev_term = $term['type'];
            continue;
        }
    }

    // Get table_references.
    $table_references = PMA_getTableReferences($analyzed_sql_results);
    $target_cols = implode(', ', $target_cols);

    // Get WHERE clause.
    $where_clause .= $analyzed_sql_results['analyzed_sql'][0]['where_clause'];
    if (empty($where_clause)) {
        $where_clause = (!empty($extra_where_clause) && $extra_where_clause[0]) ? implode(' ',$extra_where_clause) : '1';
    }

    $matched_row_query = 'SELECT '
        . $target_cols
        . ' FROM '
        . $table_references
        . ' WHERE '
        . $where_clause;

    return $matched_row_query;
}

/**
 * Transforms a DELETE query into SELECT statement.
 *
 * @param array $analyzed_sql_results Analyzed SQL results from parser.
 *
 * @return string SQL query
 */
function PMA_getSimulatedDeleteQuery($analyzed_sql_results)
{
    $where_clause = '';

    $where_clause .= $analyzed_sql_results['analyzed_sql'][0]['where_clause'];
    if (empty($where_clause) && empty($extra_where_clause)) {
        $where_clause = '1';
    }

    // Get the table_references.
    $table_references = PMA_getTableReferences($analyzed_sql_results);

    $matched_row_query = 'SELECT * '
        . ' FROM '
        . $table_references
        . ' WHERE '
        . $where_clause;

    return $matched_row_query;
}

/**
 * Finds table_references from a given query.
 * Queries Supported: INSERT, UPDATE, DELETE, REPLACE, ALTER, DROP, TRUNCATE
 *                    and RENAME.
 *
 * @param array $analyzed_sql_results Analyzed SQL results from parser
 *
 * @return string table_references
 */
function PMA_getTableReferences($analyzed_sql_results)
{
    $table_references = '';
    foreach ($analyzed_sql_results['parsed_sql'] as $key => $term) {
        // Skip first KeyWord and other invalid keys.
        if ($key == 0 || ! isset($term['data'])) {
            continue;
        }

        // Get the query type.
        $query_type = (isset($analyzed_sql_results['analyzed_sql'][0]['querytype']))
            ? $analyzed_sql_results['analyzed_sql'][0]['querytype']
            : '';

        // Terms to 'ignore' from query for table_references.
        $ignore_re = '/';
        // Terminating condition for table_references.
        $terminate_re = '/';

        // Create relevant Regular Expressions.
        switch ($query_type) {
        case 'REPLACE':
        case 'INSERT':
            $ignore_re .= '\bINSERT\b|\bREPLACE\b|\bLOW_PRIORITY\b|\bDELAYED\b'
                . '|\bHIGH_PRIORITY\b|\bIGNORE\b|\bINTO\b';
            $terminate_re .= '\bPARTITION\b|\(|\bVALUE\b|\bVALUES\b|\bSELECT\b';
            break;
        case 'UPDATE':
            $ignore_re .= '\bUPDATE\b|\bLOW_PRIORITY\b|\bIGNORE\b';
            $terminate_re .= '\bSET\b|\bUSING\b';
            break;
        case 'DELETE':
            $ignore_re .= '\bDELETE\b|\bLOW_PRIORITY\b|\bQUICK\b|\bIGNORE\b'
                . '|\bFROM\b';
            $terminate_re .= '\bPARTITION\b|\bWHERE\b|\bORDER\b|\bLIMIT\b|\bUSING\b';
            break;
        case 'ALTER':
            $ignore_re .= '\bALTER\b|\bONLINE\b|\bOFFLINE\b|\bIGNORE\b|\bTABLE\b';
            $terminate_re .= '\bADD\b|\bALTER\b|\bCHANGE\b|\bMODIFY\b|\bDROP\b'
                . '|\bDISABLE\b|\bENABLE\b|\bRENAME\b|\bORDER\b|\bCONVERT\b'
                . '|\bDEFAULT\b|\bDISCARD\b|\bIMPORT\b|\bCOALESCE\b|\bREORGANIZE\b'
                . '|\bANALYZE\b|\bCHECK\b|\bOPTIMIZE\b|\bREBUILD\b|\bREPAIR\b'
                . '|\bPARTITION\b|\bREMOVE\b|\bCHARACTER\b';
            break;
        case 'DROP':
            $ignore_re .= '\bDROP\b|\bTEMPORARY\b|\bTABLE\b|\bIF\b|\bEXISTS\b';
            $terminate_re .= '\bRESTRICT\b|\bCASCADE\b';
            break;
        case 'TRUNCATE':
            $ignore_re .= '\bTRUNCATE\b|\bTABLE\b';
            $terminate_re .= '';
            break;
        case 'RENAME':
            $ignore_re .= '\bRENAME\b|\bTABLE\b';
            $terminate_re .= '\bTO\b';
            break;
        default:
            return false;
        }

        // Ignore 'case' in RegEx.
        $ignore_re .= '/i';
        $terminate_re .= '/i';

        if ($query_type != 'TRUNCATE'
            && preg_match($terminate_re, $term['data'])
        ) {
            break;
        }

        if (preg_match($ignore_re, $term['data'])
            || ! is_numeric($key)
            || $key == 0
        ) {
            continue;
        }

        $table_references .= ' ' . $term['data'];
    }

    return $table_references;
}

/**
 * Executes the matched_row_query and returns the resultant row count.
 *
 * @param string $matched_row_query SQL query
 *
 * @return integer Number of rows returned
 */
function PMA_executeMatchedRowQuery($matched_row_query)
{
    $GLOBALS['dbi']->selectDb($GLOBALS['db']);
    // Execute the query.
    $result = $GLOBALS['dbi']->tryQuery($matched_row_query);
    // Count the number of rows in the result set.
    $result = $GLOBALS['dbi']->numRows($result);

    return $result;
}

/**
 * Extracts unique table names from table_references.
 *
 * @param string $table_references table_references
 *
 * @return array $table_names
 */
function PMA_getTableNamesFromTableReferences($table_references)
{
    $table_names = array();
    $parsed_data = PMA_SQP_parse($table_references);

    $prev_term = array(
        'data' => '',
        'type' => ''
    );
    $on_encountered = false;
    $qualifier_encountered = false;
    $i = 0;
    foreach ($parsed_data as $key => $term) {
        // To skip first 'raw' key and other invalid keys.
        if (! is_numeric($key)
            || ! isset($term['data'])
            || ! isset($term['type'])
        ) {
            continue;
        }

        $add_to_table_names = true;

        // Un-quote the data, if any.
        if ($term['type'] == 'quote_backtick') {
            $term['data'] = PMA_Util::unQuote($term['data']);
            $term['type'] = 'alpha_identifier';
        }

        // New table name expected after 'JOIN' keyword.
        if (preg_match('/\bJOIN\b/i', $term['data'])) {
            $on_encountered = false;
        }

        // If term is a qualifier, set flag.
        if ($term['type'] == 'punct_qualifier') {
            $qualifier_encountered = true;
        }

        // Skip the JOIN conditions after 'ON' keyword.
        if (preg_match('/\bON\b/i', $term['data'])) {
            $on_encountered = true;
        }

        // If the word is not an 'identifier', skip it.
        if ($term['type'] != 'alpha_identifier') {
            $add_to_table_names = false;
        }

        // Skip table 'alias'.
        if (preg_match('/\bAS\b/i', $prev_term['data'])
            || $prev_term['type'] == 'alpha_identifier'
        ) {
            $add_to_table_names = false;
        }

        // Everything fine up to now, add name to list if 'unique'.
        if ($add_to_table_names
            && ! $on_encountered
            && ! in_array($term['data'], $table_names)
        ) {
            if (! $qualifier_encountered) {
                $table_names[] = PMA_Util::backquote($term['data']);
                $i++;
            } else {
                // If qualifier encountered, concatenate DB name and table name.
                $table_names[$i-1] = $table_names[$i-1]
                    . '.'
                    . PMA_Util::backquote($term['data']);
                $qualifier_encountered = false;
            }
        }

        // Update previous term.
        $prev_term = $term;
    }

    return $table_names;
}

/**
 * Handles request for ROLLBACK.
 *
 * @param string $sql_query SQL query(s)
 *
 * @return void
 */
function PMA_handleRollbackRequest($sql_query)
{
    $sql_delimiter = $_REQUEST['sql_delimiter'];
    $queries = explode($sql_delimiter, $sql_query);
    $error = false;
    $error_msg = __(
        'Only INSERT, UPDATE, DELETE and REPLACE '
        . 'SQL queries containing transactional engine tables can be rolled back.'
    );
    foreach ($queries as $sql_query) {
        if (empty($sql_query)) {
            continue;
        }

        // Check each query for ROLLBACK support.
        if (! PMA_checkIfRollbackPossible($sql_query)) {
            $global_error = $GLOBALS['dbi']->getError();
            if ($global_error) {
                $error = $global_error;
            } else {
                $error = $error_msg;
            }
            break;
        }
    }

    if ($error) {
        unset($_REQUEST['rollback_query']);
        $response = PMA_Response::getInstance();
        $message = PMA_Message::rawError($error);
        $response->addJSON('message', $message);
        exit;
    } else {
        // If everything fine, START a transaction.
        $GLOBALS['dbi']->query('START TRANSACTION');
    }
}

/**
 * Checks if ROLLBACK is possible for a SQL query or not.
 *
 * @param string $sql_query SQL query
 *
 * @return bool
 */
function PMA_checkIfRollbackPossible($sql_query)
{
    // Supported queries.
    $supported_queries = array(
        'INSERT',
        'UPDATE',
        'DELETE',
        'REPLACE'
    );

    // Parse and Analyze the query.
    $parsed_sql = PMA_SQP_parse($sql_query);
    $analyzed_sql = PMA_SQP_analyze($parsed_sql);
    $analyzed_sql_results = array(
        'parsed_sql' => $parsed_sql,
        'analyzed_sql' => $analyzed_sql
    );

    // Get the query type.
    $query_type = (isset($analyzed_sql_results['analyzed_sql'][0]['querytype']))
        ? $analyzed_sql_results['analyzed_sql'][0]['querytype']
        : '';

    // Check if query is supported.
    if (! in_array($query_type, $supported_queries)) {
        return false;
    }

    // Get table_references from the query.
    $table_references = PMA_getTableReferences($analyzed_sql_results);
    $table_references = $table_references ? $table_references : '';
    // Get table names from table_references.
    $tables = PMA_getTableNamesFromTableReferences($table_references);

    // Check if each table is 'InnoDB'.
    foreach ($tables as $table) {
        if (! PMA_isTableTransactional($table)) {
            return false;
        }
    }

    return true;
}

/**
 * Checks if a table is 'InnoDB' or not.
 *
 * @param string $table Table details
 *
 * @return bool
 */
function PMA_isTableTransactional($table)
{
    $table = explode('.', $table);
    if (count($table) == 2) {
        $db = PMA_Util::unQuote($table[0]);
        $table = PMA_Util::unQuote($table[1]);
    } else {
        $db = $GLOBALS['db'];
        $table = PMA_Util::unQuote($table[0]);
    }

    // Query to check if table exists.
    $check_table_query = 'SELECT * FROM ' . PMA_Util::backquote($db)
        . '.' . PMA_Util::backquote($table) . ' '
        . 'LIMIT 1';

    $result = $GLOBALS['dbi']->tryQuery($check_table_query);

    if (! $result) {
        return false;
    }

    // List of Transactional Engines.
    $transactional_engines = array(
        'INNODB',
        'FALCON',
        'NDB',
        'INFINIDB',
        'TOKUDB',
        'XTRADB',
        'SEQUENCE',
        'BDB'
    );

    // Query to check if table is 'Transactional'.
    $check_query = 'SELECT `ENGINE` FROM `information_schema`.`tables` '
        . 'WHERE `table_name` = "' . $table . '" '
        . 'AND `table_schema` = "' . $db . '" '
        . 'AND UPPER(`engine`) IN ("'
        . implode('", "', $transactional_engines)
        . '")';

    $result = $GLOBALS['dbi']->tryQuery($check_query);

    if ($GLOBALS['dbi']->numRows($result) == 1) {
        return true;
    } else {
        return false;
    }
}
?>
