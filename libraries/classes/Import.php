<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Library that provides common import functions that are used by import plugins
 *
 * @package PhpMyAdmin-Import
 */
namespace PhpMyAdmin;

use PhpMyAdmin\Encoding;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statements\DeleteStatement;
use PhpMyAdmin\SqlParser\Statements\InsertStatement;
use PhpMyAdmin\SqlParser\Statements\ReplaceStatement;
use PhpMyAdmin\SqlParser\Statements\UpdateStatement;
use PhpMyAdmin\SqlParser\Utils\Query;
use PhpMyAdmin\Table;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * We need to know something about user
 */
$GLOBALS['cfg']['Server']['DisableIS'] = false;
require_once './libraries/check_user_privileges.inc.php';

/**
 * PhpMyAdmin\Import class
 *
 * @package PhpMyAdmin
 */
class Import
{
    /* MySQL type defs */
    const NONE = 0;
    const VARCHAR = 1;
    const INT = 2;
    const DECIMAL = 3;
    const BIGINT = 4;
    const GEOMETRY = 5;
    /* Decimal size defs */
    const M = 0;
    const D = 1;
    const FULL = 2;
    /* Table array defs */
    const TBL_NAME = 0;
    const COL_NAMES = 1;
    const ROWS = 2;
    /* Analysis array defs */
    const TYPES = 0;
    const SIZES = 1;
    const FORMATTEDSQL = 2;

    /**
     * Checks whether timeout is getting close
     *
     * @return boolean true if timeout is close
     * @access public
     */
    public static function checkTimeout()
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
        }

        return false;
    }

    /**
     * Runs query inside import buffer. This is needed to allow displaying
     * of last SELECT, SHOW or HANDLER results and similar nice stuff.
     *
     * @param string $sql       query to run
     * @param string $full      query to display, this might be commented
     * @param array  &$sql_data SQL parse data storage
     *
     * @return void
     * @access public
     */
    public static function executeQuery($sql, $full, array &$sql_data)
    {
        global $go_sql,
            $sql_query, $my_die, $error, $reload,
            $result, $msg,
            $cfg, $sql_query_disabled, $db;

        $result = $GLOBALS['dbi']->tryQuery($sql);

        // USE query changes the database, son need to track
        // while running multiple queries
        $is_use_query = mb_stripos($sql, "use ") !== false;

        $msg = '# ';
        if ($result === false) { // execution failed
            if (! isset($my_die)) {
                $my_die = array();
            }
            $my_die[] = array(
                'sql' => $full,
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
            } elseif ($a_aff_rows > 0) {
                $message = Message::getMessageForAffectedRows(
                    $a_aff_rows
                );
                $msg .= $message->getMessage();
            } else {
                $msg .= __(
                    'MySQL returned an empty result set (i.e. zero '
                    . 'rows).'
                );
            }

            if (($a_num_rows > 0) || $is_use_query) {
                $sql_data['valid_sql'][] = $sql;
                if (!isset($sql_data['valid_queries'])) {
                    $sql_data['valid_queries'] = 0;
                }
                $sql_data['valid_queries']++;
            }
        }
        if (! $sql_query_disabled) {
            $sql_query .= $msg . "\n";
        }

        // If a 'USE <db>' SQL-clause was found and the query
        // succeeded, set our current $db to the new one
        if ($result != false) {
            list($db, $reload) = self::lookForUse(
                $sql,
                $db,
                $reload
            );
        }

        $pattern = '@^[\s]*(DROP|CREATE)[\s]+(IF EXISTS[[:space:]]+)'
            . '?(TABLE|DATABASE)[[:space:]]+(.+)@im';
        if ($result != false
            && preg_match($pattern, $sql)
        ) {
            $reload = true;
        }
    }

    /**
     * Runs query inside import buffer. This is needed to allow displaying
     * of last SELECT, SHOW or HANDLER results and similar nice stuff.
     *
     * @param string $sql       query to run
     * @param string $full      query to display, this might be commented
     * @param array  &$sql_data SQL parse data storage
     *
     * @return void
     * @access public
     */
    public static function runQuery($sql = '', $full = '', array &$sql_data = array())
    {
        global $import_run_buffer, $go_sql, $complete_query, $display_query,
            $sql_query, $error, $reload, $result, $msg,
            $skip_queries, $executed_queries, $max_sql_len, $read_multiply,
            $cfg, $sql_query_disabled, $db, $run_query;
        $read_multiply = 1;
        if (!isset($import_run_buffer)) {
            // Do we have something to push into buffer?
            $import_run_buffer = self::runQueryPost(
                $import_run_buffer, $sql, $full
            );
            return;
        }

        // Should we skip something?
        if ($skip_queries > 0) {
            $skip_queries--;
            // Do we have something to push into buffer?
            $import_run_buffer = self::runQueryPost(
                $import_run_buffer, $sql, $full
            );
            return;
        }

        if (! empty($import_run_buffer['sql'])
            && trim($import_run_buffer['sql']) != ''
        ) {
            $max_sql_len = max(
                $max_sql_len,
                mb_strlen($import_run_buffer['sql'])
            );
            if (! $sql_query_disabled) {
                $sql_query .= $import_run_buffer['full'];
            }

            $executed_queries++;

            if ($run_query && $executed_queries < 50) {
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
                $sql_data['valid_full'][] = $import_run_buffer['full'];
                if (! isset($sql_data['valid_queries'])) {
                    $sql_data['valid_queries'] = 0;
                }
                $sql_data['valid_queries']++;
            } elseif ($run_query) {

                /* Handle rollback from go_sql */
                if ($go_sql && isset($sql_data['valid_full'])) {
                    $queries = $sql_data['valid_sql'];
                    $fulls = $sql_data['valid_full'];
                    $count = $sql_data['valid_queries'];
                    $go_sql = false;

                    $sql_data['valid_sql'] = array();
                    $sql_data['valid_queries'] = 0;
                    unset($sql_data['valid_full']);
                    for ($i = 0; $i < $count; $i++) {
                        self::executeQuery(
                            $queries[$i],
                            $fulls[$i],
                            $sql_data
                        );
                    }
                }

                self::executeQuery(
                    $import_run_buffer['sql'],
                    $import_run_buffer['full'],
                    $sql_data
                );
            } // end run query
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
                if (mb_strlen($sql_query) > 50000
                    || $executed_queries > 50
                    || $max_sql_len > 1000
                ) {
                    $sql_query = '';
                    $sql_query_disabled = true;
                }
            }
        }

        // Do we have something to push into buffer?
        $import_run_buffer = self::runQueryPost($import_run_buffer, $sql, $full);

        // In case of ROLLBACK, notify the user.
        if (isset($_POST['rollback_query'])) {
            $msg .= __('[ROLLBACK occurred.]');
        }
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
    public static function runQueryPost($import_run_buffer, $sql, $full)
    {
        if (!empty($sql) || !empty($full)) {
            $import_run_buffer = array('sql' => $sql, 'full' => $full);
            return $import_run_buffer;
        }

        unset($GLOBALS['import_run_buffer']);
        return $import_run_buffer;
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
    public static function lookForUse($buffer, $db, $reload)
    {
        if (preg_match('@^[\s]*USE[[:space:]]+([\S]+)@i', $buffer, $match)) {
            $db = trim($match[1]);
            $db = trim($db, ';'); // for example, USE abc;

            // $db must not contain the escape characters generated by backquote()
            // ( used in self::buildSql() as: backquote($db_name), and then called
            // in self::runQuery() which in turn calls self::lookForUse() )
            $db = Util::unQuote($db);

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
    public static function getNextChunk($size = 32768)
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

        if (self::checkTimeout()) {
            return false;
        }
        if ($GLOBALS['finished']) {
            return true;
        }

        if ($GLOBALS['import_file'] == 'none') {
            // Well this is not yet supported and tested,
            // but should return content of textarea
            if (mb_strlen($GLOBALS['import_text']) < $size) {
                $GLOBALS['finished'] = true;
                return $GLOBALS['import_text'];
            } else {
                $r = mb_substr($GLOBALS['import_text'], 0, $size);
                $GLOBALS['offset'] += $size;
                $GLOBALS['import_text'] = mb_substr($GLOBALS['import_text'], $size);
                return $r;
            }
        }

        $result = $import_handle->read($size);
        $GLOBALS['finished'] = $import_handle->eof();
        $GLOBALS['offset'] += $size;

        if ($charset_conversion) {
            return Encoding::convertString($charset_of_file, 'utf-8', $result);
        }

        /**
         * Skip possible byte order marks (I do not think we need more
         * charsets, but feel free to add more, you can use wikipedia for
         * reference: <https://en.wikipedia.org/wiki/Byte_Order_Mark>)
         *
         * @todo BOM could be used for charset autodetection
         */
        if ($GLOBALS['offset'] == $size) {
            // UTF-8
            if (strncmp($result, "\xEF\xBB\xBF", 3) == 0) {
                $result = mb_substr($result, 3);
                // UTF-16 BE, LE
            } elseif (strncmp($result, "\xFE\xFF", 2) == 0
                || strncmp($result, "\xFF\xFE", 2) == 0
            ) {
                $result = mb_substr($result, 2);
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
    public static function getColumnAlphaName($num)
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
            $col_name = self::getColumnAlphaName($div);
            // use modulus as new column number
            $num = $remain;
        }

        if ($num == 0) {
            // use 'Z' if column number is 0,
            // this is necessary because A-Z has no 'zero'
            $col_name .= mb_chr(($A + 26) - 1);
        } else {
            // convert column number to ASCII character
            $col_name .= mb_chr(($A + $num) - 1);
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
    public static function getColumnNumberFromName($name)
    {
        if (empty($name)) {
            return 0;
        }

        $name = mb_strtoupper($name);
        $num_chars = mb_strlen($name);
        $column_number = 0;
        for ($i = 0; $i < $num_chars; ++$i) {
            // read string from back to front
            $char_pos = ($num_chars - 1) - $i;

            // convert capital character to ASCII value
            // and subtract 64 to get corresponding decimal value
            // ASCII value of "A" is 65, "B" is 66, etc.
            // Decimal equivalent of "A" is 1, "B" is 2, etc.
            $number = (int)(mb_ord($name[$char_pos]) - 64);

            // base26 to base10 conversion : multiply each number
            // with corresponding value of the position, in this case
            // $i=0 : 1; $i=1 : 26; $i=2 : 676; ...
            $column_number += $number * pow(26, $i);
        }
        return $column_number;
    }

    /**
     * Obtains the precision (total # of digits) from a size of type decimal
     *
     * @param string $last_cumulative_size Size of type decimal
     *
     * @return int Precision of the given decimal size notation
     * @access  public
     */
    public static function getDecimalPrecision($last_cumulative_size)
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
    public static function getDecimalScale($last_cumulative_size)
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
    public static function getDecimalSize($cell)
    {
        $curr_size = mb_strlen((string)$cell);
        $decPos = mb_strpos($cell, ".");
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
    public static function detectSize($last_cumulative_size, $last_cumulative_type,
        $curr_type, $cell
    ) {
        $curr_size = mb_strlen((string)$cell);

        /**
         * If the cell is NULL, don't treat it as a varchar
         */
        if (! strcmp('NULL', $cell)) {
            return $last_cumulative_size;
        } elseif ($curr_type == self::VARCHAR) {
            /**
             * What to do if the current cell is of type VARCHAR
             */
            /**
             * The last cumulative type was VARCHAR
             */
            if ($last_cumulative_type == self::VARCHAR) {
                if ($curr_size >= $last_cumulative_size) {
                    return $curr_size;
                }

                return $last_cumulative_size;
            } elseif ($last_cumulative_type == self::DECIMAL) {
                /**
                 * The last cumulative type was DECIMAL
                 */
                $oldM = self::getDecimalPrecision($last_cumulative_size);

                if ($curr_size >= $oldM) {
                    return $curr_size;
                }

                return $oldM;
            } elseif ($last_cumulative_type == self::BIGINT || $last_cumulative_type == self::INT) {
                /**
                 * The last cumulative type was BIGINT or INT
                 */
                if ($curr_size >= $last_cumulative_size) {
                    return $curr_size;
                }

                return $last_cumulative_size;
            } elseif (! isset($last_cumulative_type) || $last_cumulative_type == self::NONE) {
                /**
                 * This is the first row to be analyzed
                 */
                return $curr_size;
            }

            /**
             * An error has DEFINITELY occurred
             */
            /**
             * TODO: Handle this MUCH more elegantly
             */

            return -1;
        } elseif ($curr_type == self::DECIMAL) {
            /**
             * What to do if the current cell is of type DECIMAL
             */
            /**
             * The last cumulative type was VARCHAR
             */
            if ($last_cumulative_type == self::VARCHAR) {
                /* Convert $last_cumulative_size from varchar to decimal format */
                $size = self::getDecimalSize($cell);

                if ($size[self::M] >= $last_cumulative_size) {
                    return $size[self::M];
                }

                return $last_cumulative_size;
            } elseif ($last_cumulative_type == self::DECIMAL) {
                /**
                 * The last cumulative type was DECIMAL
                 */
                $size = self::getDecimalSize($cell);

                $oldM = self::getDecimalPrecision($last_cumulative_size);
                $oldD = self::getDecimalScale($last_cumulative_size);

                /* New val if M or D is greater than current largest */
                if ($size[self::M] > $oldM || $size[self::D] > $oldD) {
                    /* Take the largest of both types */
                    return (string) ((($size[self::M] > $oldM) ? $size[self::M] : $oldM)
                        . "," . (($size[self::D] > $oldD) ? $size[self::D] : $oldD));
                }

                return $last_cumulative_size;
            } elseif ($last_cumulative_type == self::BIGINT || $last_cumulative_type == self::INT) {
                /**
                 * The last cumulative type was BIGINT or INT
                 */
                /* Convert $last_cumulative_size from int to decimal format */
                $size = self::getDecimalSize($cell);

                if ($size[self::M] >= $last_cumulative_size) {
                    return $size[self::FULL];
                }

                return ($last_cumulative_size . "," . $size[self::D]);
            } elseif (! isset($last_cumulative_type) || $last_cumulative_type == self::NONE) {
                /**
                 * This is the first row to be analyzed
                 */
                /* First row of the column */
                $size = self::getDecimalSize($cell);

                return $size[self::FULL];
            }

            /**
             * An error has DEFINITELY occurred
             */
            /**
             * TODO: Handle this MUCH more elegantly
             */

            return -1;
        } elseif ($curr_type == self::BIGINT || $curr_type == self::INT) {
            /**
             * What to do if the current cell is of type BIGINT or INT
             */
            /**
             * The last cumulative type was VARCHAR
             */
            if ($last_cumulative_type == self::VARCHAR) {
                if ($curr_size >= $last_cumulative_size) {
                    return $curr_size;
                }

                return $last_cumulative_size;
            } elseif ($last_cumulative_type == self::DECIMAL) {
                /**
                 * The last cumulative type was DECIMAL
                 */
                $oldM = self::getDecimalPrecision($last_cumulative_size);
                $oldD = self::getDecimalScale($last_cumulative_size);
                $oldInt = $oldM - $oldD;
                $newInt = mb_strlen((string)$cell);

                /* See which has the larger integer length */
                if ($oldInt >= $newInt) {
                    /* Use old decimal size */
                    return $last_cumulative_size;
                }

                /* Use $newInt + $oldD as new M */
                return (($newInt + $oldD) . "," . $oldD);
            } elseif ($last_cumulative_type == self::BIGINT || $last_cumulative_type == self::INT) {
                /**
                 * The last cumulative type was BIGINT or INT
                 */
                if ($curr_size >= $last_cumulative_size) {
                    return $curr_size;
                }

                return $last_cumulative_size;
            } elseif (! isset($last_cumulative_type) || $last_cumulative_type == self::NONE) {
                /**
                 * This is the first row to be analyzed
                 */
                return $curr_size;
            }

            /**
             * An error has DEFINITELY occurred
             */
            /**
             * TODO: Handle this MUCH more elegantly
             */

            return -1;
        }

        /**
         * An error has DEFINITELY occurred
         */
        /**
         * TODO: Handle this MUCH more elegantly
         */

        return -1;
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
    public static function detectType($last_cumulative_type, $cell)
    {
        /**
         * If numeric, determine if decimal, int or bigint
         * Else, we call it varchar for simplicity
         */

        if (! strcmp('NULL', $cell)) {
            if ($last_cumulative_type === null || $last_cumulative_type == self::NONE) {
                return self::NONE;
            }

            return $last_cumulative_type;
        }

        if (!is_numeric($cell)) {
            return self::VARCHAR;
        }

        if ($cell == (string)(float)$cell
            && mb_strpos($cell, ".") !== false
            && mb_substr_count($cell, ".") == 1
        ) {
            return self::DECIMAL;
        }

        if (abs($cell) > 2147483647) {
            return self::BIGINT;
        }

        if ($cell !== (string)(int)$cell) {
            return self::VARCHAR;
        }

        return self::INT;
    }

    /**
     * Determines if the column types are int, decimal, or string
     *
     * @param array &$table array(string $table_name, array $col_names, array $rows)
     *
     * @return array    array(array $types, array $sizes)
     * @access  public
     *
     * @link https://wiki.phpmyadmin.net/pma/Import
     *
     * @todo    Handle the error case more elegantly
     */
    public static function analyzeTable(array &$table)
    {
        /* Get number of rows in table */
        $numRows = count($table[self::ROWS]);
        /* Get number of columns */
        $numCols = count($table[self::COL_NAMES]);
        /* Current type for each column */
        $types = array();
        $sizes = array();

        /* Initialize $sizes to all 0's */
        for ($i = 0; $i < $numCols; ++$i) {
            $sizes[$i] = 0;
        }

        /* Initialize $types to NONE */
        for ($i = 0; $i < $numCols; ++$i) {
            $types[$i] = self::NONE;
        }

        /* If the passed array is not of the correct form, do not process it */
        if (!is_array($table)
            || is_array($table[self::TBL_NAME])
            || !is_array($table[self::COL_NAMES])
            || !is_array($table[self::ROWS])
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
                $curr_type = self::detectType($types[$i], $table[self::ROWS][$j][$i]);
                /* Determine size of the current cell */
                $sizes[$i] = self::detectSize(
                    $sizes[$i],
                    $types[$i],
                    $curr_type,
                    $table[self::ROWS][$j][$i]
                );

                /**
                 * If a type for this column has already been declared,
                 * only alter it if it was a number and a varchar was found
                 */
                if ($curr_type != self::NONE) {
                    if ($curr_type == self::VARCHAR) {
                        $types[$i] = self::VARCHAR;
                    } elseif ($curr_type == self::DECIMAL) {
                        if ($types[$i] != self::VARCHAR) {
                            $types[$i] = self::DECIMAL;
                        }
                    } elseif ($curr_type == self::BIGINT) {
                        if ($types[$i] != self::VARCHAR && $types[$i] != self::DECIMAL) {
                            $types[$i] = self::BIGINT;
                        }
                    } elseif ($curr_type == self::INT) {
                        if ($types[$i] != self::VARCHAR
                            && $types[$i] != self::DECIMAL
                            && $types[$i] != self::BIGINT
                        ) {
                            $types[$i] = self::INT;
                        }
                    }
                }
            }
        }

        /* Check to ensure that all types are valid */
        $len = count($types);
        for ($n = 0; $n < $len; ++$n) {
            if (! strcmp(self::NONE, $types[$n])) {
                $types[$n] = self::VARCHAR;
                $sizes[$n] = '10';
            }
        }

        return array($types, $sizes);
    }

    /**
     * Builds and executes SQL statements to create the database and tables
     * as necessary, as well as insert all the data.
     *
     * @param string     $db_name         Name of the database
     * @param array      &$tables         Array of tables for the specified database
     * @param array|null &$analyses       Analyses of the tables
     * @param array|null &$additional_sql Additional SQL statements to be executed
     * @param array|null $options         Associative array of options
     * @param array      &$sql_data       2-element array with sql data
     *
     * @return void
     * @access  public
     *
     * @link https://wiki.phpmyadmin.net/pma/Import
     */
    public static function buildSql($db_name, array &$tables, &$analyses = null,
        &$additional_sql = null, $options = null, array &$sql_data
    ) {
        /* Needed to quell the beast that is Message */
        $import_notice = null;

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
            $sql[] = "CREATE DATABASE IF NOT EXISTS " . Util::backquote($db_name)
                . " DEFAULT CHARACTER SET " . $charset . " COLLATE " . $collation
                . ";";
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
            self::runQuery($sql[$i], $sql[$i], $sql_data);
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
             * See: https://bugs.mysql.com/bug.php?id=15287
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
                self::runQuery($additional_sql[$i], $additional_sql[$i], $sql_data);
            }
        }

        if ($analyses != null) {
            $type_array = array(
                self::NONE => "NULL",
                self::VARCHAR => "varchar",
                self::INT => "int",
                self::DECIMAL => "decimal",
                self::BIGINT => "bigint",
                self::GEOMETRY => 'geometry'
            );

            /* TODO: Do more checking here to make sure they really are matched */
            if (count($tables) != count($analyses)) {
                exit();
            }

            /* Create SQL code to create the tables */
            $num_tables = count($tables);
            for ($i = 0; $i < $num_tables; ++$i) {
                $num_cols = count($tables[$i][self::COL_NAMES]);
                $tempSQLStr = "CREATE TABLE IF NOT EXISTS "
                . Util::backquote($db_name)
                . '.' . Util::backquote($tables[$i][self::TBL_NAME]) . " (";
                for ($j = 0; $j < $num_cols; ++$j) {
                    $size = $analyses[$i][self::SIZES][$j];
                    if ((int)$size == 0) {
                        $size = 10;
                    }

                    $tempSQLStr .= Util::backquote(
                        $tables[$i][self::COL_NAMES][$j]
                    ) . " "
                    . $type_array[$analyses[$i][self::TYPES][$j]];
                    if ($analyses[$i][self::TYPES][$j] != self::GEOMETRY) {
                        $tempSQLStr .= "(" . $size . ")";
                    }

                    if ($j != (count($tables[$i][self::COL_NAMES]) - 1)) {
                        $tempSQLStr .= ", ";
                    }
                }
                $tempSQLStr .= ") DEFAULT CHARACTER SET " . $charset
                    . " COLLATE " . $collation . ";";

                /**
                 * Each SQL statement is executed immediately
                 * after it is formed so that we don't have
                 * to store them in a (possibly large) buffer
                 */
                self::runQuery($tempSQLStr, $tempSQLStr, $sql_data);
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
            $num_cols = count($tables[$i][self::COL_NAMES]);
            $num_rows = count($tables[$i][self::ROWS]);

            $tempSQLStr = "INSERT INTO " . Util::backquote($db_name) . '.'
                . Util::backquote($tables[$i][self::TBL_NAME]) . " (";

            for ($m = 0; $m < $num_cols; ++$m) {
                $tempSQLStr .= Util::backquote($tables[$i][self::COL_NAMES][$m]);

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
                        && isset($analyses[$i][self::FORMATTEDSQL][$col_count])
                        && $analyses[$i][self::FORMATTEDSQL][$col_count] == true
                    ) {
                        $tempSQLStr .= (string) $tables[$i][self::ROWS][$j][$k];
                    } else {
                        if ($analyses != null) {
                            $is_varchar = ($analyses[$i][self::TYPES][$col_count] === self::VARCHAR);
                        } else {
                            $is_varchar = ! is_numeric($tables[$i][self::ROWS][$j][$k]);
                        }

                        /* Don't put quotes around NULL fields */
                        if (! strcmp($tables[$i][self::ROWS][$j][$k], 'NULL')) {
                            $is_varchar = false;
                        }

                        $tempSQLStr .= (($is_varchar) ? "'" : "");
                        $tempSQLStr .= $GLOBALS['dbi']->escapeString(
                            (string) $tables[$i][self::ROWS][$j][$k]
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
                    unset($tables[$i][self::ROWS][$j][$k]);
                }

                $tempSQLStr .= ")";

                if ($j != ($num_rows - 1)) {
                    $tempSQLStr .= ",\n ";
                }

                $col_count = 0;
                /* Delete the row after we are done with it */
                unset($tables[$i][self::ROWS][$j]);
            }

            $tempSQLStr .= ";";

            /**
             * Each SQL statement is executed immediately
             * after it is formed so that we don't have
             * to store them in a (possibly large) buffer
             */
            self::runQuery($tempSQLStr, $tempSQLStr, $sql_data);
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

        $additional_sql_len = is_null($additional_sql) ? 0 : count($additional_sql);
        for ($i = 0; $i < $additional_sql_len; ++$i) {
            preg_match($view_pattern, $additional_sql[$i], $regs);

            if (count($regs) == 0) {
                preg_match($table_pattern, $additional_sql[$i], $regs);
            }

            if (count($regs)) {
                for ($n = 0; $n < $num_tables; ++$n) {
                    if (! strcmp($regs[1], $tables[$n][self::TBL_NAME])) {
                        $inTables = true;
                        break;
                    }
                }

                if (! $inTables) {
                    $tables[] = array(self::TBL_NAME => $regs[1]);
                }
            }

            /* Reset the array */
            $regs = array();
            $inTables = false;
        }

        $params = array('db' => (string)$db_name);
        $db_url = 'db_structure.php' . Url::getCommon($params);
        $db_ops_url = 'db_operations.php' . Url::getCommon($params);

        $message = '<br /><br />';
        $message .= '<strong>' . __(
            'The following structures have either been created or altered. Here you can:'
        ) . '</strong><br />';
        $message .= '<ul><li>' . __(
            "View a structure's contents by clicking on its name."
        ) . '</li>';
        $message .= '<li>' . __(
            'Change any of its settings by clicking the corresponding "Options" link.'
        ) . '</li>';
        $message .= '<li>' . __('Edit structure by following the "Structure" link.')
            . '</li>';
        $message .= sprintf(
            '<br /><li><a href="%s" title="%s">%s</a> (<a href="%s" title="%s">'
            . __('Options') . '</a>)</li>',
            $db_url,
            sprintf(
                __('Go to database: %s'),
                htmlspecialchars(Util::backquote($db_name))
            ),
            htmlspecialchars($db_name),
            $db_ops_url,
            sprintf(
                __('Edit settings for %s'),
                htmlspecialchars(Util::backquote($db_name))
            )
        );

        $message .= '<ul>';

        unset($params);

        $num_tables = count($tables);
        for ($i = 0; $i < $num_tables; ++$i) {
            $params = array(
                 'db' => (string) $db_name,
                 'table' => (string) $tables[$i][self::TBL_NAME]
            );
            $tbl_url = 'sql.php' . Url::getCommon($params);
            $tbl_struct_url = 'tbl_structure.php' . Url::getCommon($params);
            $tbl_ops_url = 'tbl_operations.php' . Url::getCommon($params);

            unset($params);

            $_table = new Table($tables[$i][self::TBL_NAME], $db_name);
            if (! $_table->isView()) {
                $message .= sprintf(
                    '<li><a href="%s" title="%s">%s</a> (<a href="%s" title="%s">' . __(
                        'Structure'
                    ) . '</a>) (<a href="%s" title="%s">' . __('Options') . '</a>)</li>',
                    $tbl_url,
                    sprintf(
                        __('Go to table: %s'),
                        htmlspecialchars(
                            Util::backquote($tables[$i][self::TBL_NAME])
                        )
                    ),
                    htmlspecialchars($tables[$i][self::TBL_NAME]),
                    $tbl_struct_url,
                    sprintf(
                        __('Structure of %s'),
                        htmlspecialchars(
                            Util::backquote($tables[$i][self::TBL_NAME])
                        )
                    ),
                    $tbl_ops_url,
                    sprintf(
                        __('Edit settings for %s'),
                        htmlspecialchars(
                            Util::backquote($tables[$i][self::TBL_NAME])
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
                            Util::backquote($tables[$i][self::TBL_NAME])
                        )
                    ),
                    htmlspecialchars($tables[$i][self::TBL_NAME])
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
     * @param Message $error_message The error message
     *
     * @return void
     * @access  public
     *
     */
    public static function stop(Message $error_message)
    {
        global $import_handle, $file_to_unlink;

        // Close open handles
        if ($import_handle !== false && $import_handle !== null) {
            $import_handle->close();
        }

        // Delete temporary file
        if ($file_to_unlink != '') {
            unlink($file_to_unlink);
        }
        $msg = $error_message->getDisplay();
        $_SESSION['Import_message']['message'] = $msg;

        $response = Response::getInstance();
        $response->setRequestStatus(false);
        $response->addJSON('message', Message::error($msg));

        exit;
    }

    /**
     * Handles request for Simulation of UPDATE/DELETE queries.
     *
     * @return void
     */
    public static function handleSimulateDmlRequest()
    {
        $response = Response::getInstance();
        $error = false;
        $error_msg = __('Only single-table UPDATE and DELETE queries can be simulated.');
        $sql_delimiter = $_POST['sql_delimiter'];
        $sql_data = array();
        $queries = explode($sql_delimiter, $GLOBALS['sql_query']);
        foreach ($queries as $sql_query) {
            if (empty($sql_query)) {
                continue;
            }

            // Parsing the query.
            $parser = new Parser($sql_query);

            if (empty($parser->statements[0])) {
                continue;
            }

            $statement = $parser->statements[0];

            $analyzed_sql_results = array(
                'query' => $sql_query,
                'parser' => $parser,
                'statement' => $statement,
            );

            if ((!(($statement instanceof UpdateStatement)
                || ($statement instanceof DeleteStatement)))
                || (!empty($statement->join))
            ) {
                $error = $error_msg;
                break;
            }

            $tables = Query::getTables($statement);
            if (count($tables) > 1) {
                $error = $error_msg;
                break;
            }

            // Get the matched rows for the query.
            $result = self::getMatchedRows($analyzed_sql_results);
            if (! $error = $GLOBALS['dbi']->getError()) {
                $sql_data[] = $result;
            } else {
                break;
            }
        }

        if ($error) {
            $message = Message::rawError($error);
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
    public static function getMatchedRows(array $analyzed_sql_results = array())
    {
        $statement = $analyzed_sql_results['statement'];

        $matched_row_query = '';
        if ($statement instanceof DeleteStatement) {
            $matched_row_query = self::getSimulatedDeleteQuery($analyzed_sql_results);
        } elseif ($statement instanceof UpdateStatement) {
            $matched_row_query = self::getSimulatedUpdateQuery($analyzed_sql_results);
        }

        // Execute the query and get the number of matched rows.
        $matched_rows = self::executeMatchedRowQuery($matched_row_query);

        // URL to matched rows.
        $_url_params = array(
            'db'        => $GLOBALS['db'],
            'sql_query' => $matched_row_query
        );
        $matched_rows_url  = 'sql.php' . Url::getCommon($_url_params);

        return array(
            'sql_query' => Util::formatSql($analyzed_sql_results['query']),
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
    public static function getSimulatedUpdateQuery(array $analyzed_sql_results)
    {
        $table_references = Query::getTables(
            $analyzed_sql_results['statement']
        );

        $where = Query::getClause(
            $analyzed_sql_results['statement'],
            $analyzed_sql_results['parser']->list,
            'WHERE'
        );

        if (empty($where)) {
            $where = '1';
        }

        $columns = array();
        $diff = array();
        foreach ($analyzed_sql_results['statement']->set as $set) {
            $columns[] = $set->column;
            $not_equal_operator = ' <> ';
            if(strtoupper($set->value) == 'NULL'){
                $not_equal_operator = ' IS NOT ';
            }
            $diff[] = $set->column . $not_equal_operator . $set->value;
        }
        if (!empty($diff)) {
            $where .= ' AND (' . implode(' OR ', $diff) . ')';
        }

        $order_and_limit = '';

        if (!empty($analyzed_sql_results['statement']->order)) {
            $order_and_limit .= ' ORDER BY ' . Query::getClause(
                $analyzed_sql_results['statement'],
                $analyzed_sql_results['parser']->list,
                'ORDER BY'
            );
        }

        if (!empty($analyzed_sql_results['statement']->limit)) {
            $order_and_limit .= ' LIMIT ' . Query::getClause(
                $analyzed_sql_results['statement'],
                $analyzed_sql_results['parser']->list,
                'LIMIT'
            );
        }

        return 'SELECT '  . implode(', ', $columns) .
            ' FROM ' . implode(', ', $table_references) .
            ' WHERE ' . $where . $order_and_limit;
    }

    /**
     * Transforms a DELETE query into SELECT statement.
     *
     * @param array $analyzed_sql_results Analyzed SQL results from parser.
     *
     * @return string SQL query
     */
    public static function getSimulatedDeleteQuery(array $analyzed_sql_results)
    {
        $table_references = Query::getTables(
            $analyzed_sql_results['statement']
        );

        $where = Query::getClause(
            $analyzed_sql_results['statement'],
            $analyzed_sql_results['parser']->list,
            'WHERE'
        );

        if (empty($where)) {
            $where = '1';
        }

        $order_and_limit = '';

        if (!empty($analyzed_sql_results['statement']->order)) {
            $order_and_limit .= ' ORDER BY ' . Query::getClause(
                $analyzed_sql_results['statement'],
                $analyzed_sql_results['parser']->list,
                'ORDER BY'
            );
        }

        if (!empty($analyzed_sql_results['statement']->limit)) {
            $order_and_limit .= ' LIMIT ' . Query::getClause(
                $analyzed_sql_results['statement'],
                $analyzed_sql_results['parser']->list,
                'LIMIT'
            );
        }

        return 'SELECT * FROM ' . implode(', ', $table_references) .
            ' WHERE ' . $where . $order_and_limit;
    }

    /**
     * Executes the matched_row_query and returns the resultant row count.
     *
     * @param string $matched_row_query SQL query
     *
     * @return integer Number of rows returned
     */
    public static function executeMatchedRowQuery($matched_row_query)
    {
        $GLOBALS['dbi']->selectDb($GLOBALS['db']);
        // Execute the query.
        $result = $GLOBALS['dbi']->tryQuery($matched_row_query);
        // Count the number of rows in the result set.
        $result = $GLOBALS['dbi']->numRows($result);

        return $result;
    }

    /**
     * Handles request for ROLLBACK.
     *
     * @param string $sql_query SQL query(s)
     *
     * @return void
     */
    public static function handleRollbackRequest($sql_query)
    {
        $sql_delimiter = $_POST['sql_delimiter'];
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
            if (! self::checkIfRollbackPossible($sql_query)) {
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
            unset($_POST['rollback_query']);
            $response = Response::getInstance();
            $message = Message::rawError($error);
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
    public static function checkIfRollbackPossible($sql_query)
    {
        $parser = new Parser($sql_query);

        if (empty($parser->statements[0])) {
            return false;
        }

        $statement = $parser->statements[0];

        // Check if query is supported.
        if (!(($statement instanceof InsertStatement)
            || ($statement instanceof UpdateStatement)
            || ($statement instanceof DeleteStatement)
            || ($statement instanceof ReplaceStatement))
        ) {
            return false;
        }

        // Get table_references from the query.
        $tables = Query::getTables($statement);

        // Check if each table is 'InnoDB'.
        foreach ($tables as $table) {
            if (! self::isTableTransactional($table)) {
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
    public static function isTableTransactional($table)
    {
        $table = explode('.', $table);
        if (count($table) == 2) {
            $db = Util::unQuote($table[0]);
            $table = Util::unQuote($table[1]);
        } else {
            $db = $GLOBALS['db'];
            $table = Util::unQuote($table[0]);
        }

        // Query to check if table exists.
        $check_table_query = 'SELECT * FROM ' . Util::backquote($db)
            . '.' . Util::backquote($table) . ' '
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

        return $GLOBALS['dbi']->numRows($result) == 1;
    }
}
