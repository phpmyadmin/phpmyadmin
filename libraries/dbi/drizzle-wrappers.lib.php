<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Wrappers for Drizzle extension classes
 *
 * Drizzle extension exposes libdrizzle functions and requires user to have it in mind while using them.
 * This wrapper is not complete and hides a lot of original functionality, but allows for easy usage
 * of the drizzle PHP extension.
 *
 * @package PhpMyAdmin-DBI-Drizzle
 */

// TODO: drizzle module segfaults while freeing resources, often. This allows at least for some development
function _drizzle_shutdown_flush() {
    flush();
}
register_shutdown_function('_drizzle_shutdown_flush');

function _dlog_argstr($args)
{
    $r = array();
    foreach ($args as $arg) {
        if (is_object($arg)) {
            $r[] = get_class($arg);
        } elseif (is_bool($arg)) {
            $r[] = $arg ? 'true' : 'false';
        } elseif (is_null($arg)) {
            $r[] = 'null';
        } else {
            $r[] = $arg;
        }
    }
    return implode(', ', $r);
}

function _dlog($end = false)
{
    /*
    static $fp = null;

    if (!$fp) {
        $fp = fopen('./drizzle_log.log', 'a');
        flock($fp, LOCK_EX);
        fwrite($fp, "\r\n[" . date('H:i:s') . "]\t" . $_SERVER['REQUEST_URI'] . "\r\n");
        register_shutdown_function(function() use ($fp) {
            fwrite($fp, '[' . date('H:i:s') . "]\tEND\r\n\r\n");
        });
    }
    if ($end) {
        fwrite($fp, '[' . date('H:i:s') . "]\tok\r\n");
    } else {
        $bt = debug_backtrace(true);
        $caller = (isset($bt[1]['class']) ? $bt[1]['class'] . '::' : '') . $bt[1]['function'];
        if ($bt[1]['function'] == '__call') {
            $caller .= '^' . $bt[1]['args'][0];
            $args = _dlog_argstr($bt[1]['args'][1]);
        } else {
            $args = _dlog_argstr($bt[1]['args']);
        }
        fwrite($fp, '[' . date('H:i:s') . "]\t" . $caller . "\t" . $args . "\r\n");
        for ($i = 2; $i <= count($bt)-1; $i++) {
            if (!isset($bt[$i])) {
                break;
            }
            $caller = (isset($bt[$i]['class']) ? $bt[$i]['class'] . '::' : '') . $bt[$i]['function'];
            $caller .= ' (' . $bt[$i]['file'] . ':' . $bt[$i]['line'] .  ')';
            fwrite($fp, str_repeat(' ', 20) . $caller . "\r\n");
        }
    }
    //*/
}

/**
 * Wrapper for Drizzle class
 */
class PMA_Drizzle extends Drizzle
{
    /**
     * Fetch mode: result rows contain column names
     */
    const FETCH_ASSOC = 1;
    /**
     * Fetch mode: result rows contain only numeric indices
     */
    const FETCH_NUM = 2;
    /**
     * Fetch mode: result rows have both column names and numeric indices
     */
    const FETCH_BOTH = 3;

    /**
     * Result buffering: entire result set is buffered upon execution
     */
    const BUFFER_RESULT = 1;
    /**
     * Result buffering: buffering occurs only on row level
     */
    const BUFFER_ROW = 2;

    /**
     * Constructor
     */
    public function __construct()
    {_dlog();
        parent::__construct();
    }

    /**
     * Creates a new database conection using TCP
     *
     * @param $host
     * @param $port
     * @param $user
     * @param $password
     * @param $db
     * @param $options
     * @return PMA_DrizzleCon
     */
    public function addTcp($host, $port, $user, $password, $db, $options)
    {_dlog();
        $dcon = parent::addTcp($host, $port, $user, $password, $db, $options);
        return $dcon instanceof DrizzleCon
            ? new PMA_DrizzleCon($dcon)
            : $dcon;
    }

    /**
     * Creates a new connection using unix domain socket
     * 
     * @param $uds
     * @param $user
     * @param $password
     * @param $db
     * @param $options
     * @return PMA_DrizzleCon
     */
    public function addUds($uds, $user, $password, $db, $options)
    {_dlog();
        $dcon = parent::addUds($uds, $user, $password, $db, $options);
        return $dcon instanceof DrizzleCon
            ? new PMA_DrizzleCon($dcon)
            : $dcon;
    }
}

/**
 * Wrapper around DrizzleCon class
 *
 * Its main task is to wrap results with PMA_DrizzleResult class
 */
class PMA_DrizzleCon
{
    /**
     * Instance of DrizzleCon class
     * @var DrizzleCon
     */
    private $dcon;

    /**
     * Result of the most recent query
     * @var PMA_DrizzleResult
     */
    private $lastResult;

    /**
     * Constructor
     *
     * @param DrizzleCon $dcon
     */
    public function __construct(DrizzleCon $dcon)
    {_dlog();
        $this->dcon = $dcon;
    }

    /**
     * Executes given query. Opens database connection if not already done.
     *
     * @param string $query
     * @param int    $bufferMode  PMA_Drizzle::BUFFER_RESULT, PMA_Drizzle::BUFFER_ROW
     * @param int    $fetchMode   PMA_Drizzle::FETCH_ASSOC, PMA_Drizzle::FETCH_NUM or PMA_Drizzle::FETCH_BOTH
     * @return PMA_DrizzleResult
     */
    public function query($query, $bufferMode = PMA_Drizzle::BUFFER_RESULT, $fetchMode = PMA_Drizzle::FETCH_ASSOC)
    {_dlog();
        $result = $this->dcon->query($query);
        if ($result instanceof DrizzleResult) {
    _dlog(true);
            $this->lastResult = new PMA_DrizzleResult($result, $bufferMode, $fetchMode);
            return $this->lastResult;
        }
        return $result;
    }

    /**
     * Returns the number of rows affected by last query
     *
     * @return int|false
     */
    public function affectedRows()
    {
        return $this->lastResult
            ? $this->lastResult->affectedRows()
            : false;
    }

    /**
     * Pass calls of undefined methods to DrizzleCon object
     * 
     * @param $method
     * @param $args
     * @return mixed
     */
    public function __call($method, $args)
    {_dlog();
        return call_user_func_array(array($this->dcon, $method), $args);
    }

    /**
     * Returns original Drizzle connection object
     *
     * @return DrizzleCon
     */
    public function getConnectionObject()
    {_dlog();
        return $this->dcon;
    }
}

/**
 * Wrapper around DrizzleResult. Allows for reading result rows as an associative array
 * and hides complexity behind buffering.
 */
class PMA_DrizzleResult
{
    /**
     * Instamce of DrizzleResult class
     * @var DrizzleResult
     */
    private $dresult;
    /**
     * Fetch mode
     * @var int
     */
    private $fetchMode;
    /**
     * Buffering mode
     * @var int
     */
    private $bufferMode;

    /**
     * Cached column data
     * @var DrizzleColumn[]
     */
    private $columns = null;
    /**
     * Cached column names
     * @var string[]
     */
    private $columnNames = null;

    /**
     * Constructor
     *
     * @param DrizzleResult $dresult
     * @param int           $bufferMode
     * @param int           $fetchMode
     */
    public function __construct(DrizzleResult $dresult, $bufferMode, $fetchMode)
    {_dlog();
        $this->dresult = $dresult;
        $this->bufferMode = $bufferMode;
        $this->fetchMode = $fetchMode;

        if ($this->bufferMode == PMA_Drizzle::BUFFER_RESULT) {
            $this->dresult->buffer();
        }
    }

    /**
     * Sets fetch mode
     *
     * @param int $fetchMode
     */
    public function setFetchMode($fetchMode)
    {_dlog();
        $this->fetchMode = $fetchMode;
    }

    /**
     * Reads information about columns contained in current result set into {@see $columns} and {@see $columnNames} arrays
     */
    private function _readColumns()
    {_dlog();
        $this->columns = array();
        $this->columnNames = array();
        if ($this->bufferMode == PMA_Drizzle::BUFFER_RESULT) {
            while (($column = $this->dresult->columnNext()) !== null) {
                $this->columns[] = $column;
                $this->columnNames[] = $column->name();
            }
        } else {
            while (($column = $this->dresult->columnRead()) !== null) {
                $this->columns[] = $column;
                $this->columnNames[] = $column->name();
            }
        }
    }

    /**
     * Returns columns in current result
     *
     * @return DrizzleColumn[]
     */
    public function getColumns()
    {_dlog();
        if (!$this->columns) {
            $this->_readColumns();
        }
        return $this->columns;
    }

    /**
     * Returns number if columns in result
     *
     * @return int
     */
    public function numColumns()
    {_dlog();
        return $this->dresult->columnCount();
    }

    /**
     * Transforms result row to conform to current fetch mode
     *
     * @param mixed &$row
     * @param int   $fetchMode
     */
    private function _transformResultRow(&$row, $fetchMode)
    {
        if (!$row) {
            return;
        }

        switch ($fetchMode) {
            case PMA_Drizzle::FETCH_ASSOC:
                $row = array_combine($this->columnNames, $row);
                break;
            case PMA_Drizzle::FETCH_BOTH:
                $length = count($row);
                for ($i = 0; $i < $length; $i++) {
                    $row[$this->columnNames[$i]] = $row[$i];
                }
                break;
            default:
                break;
        }
    }

    /**
     * Fetches next for from this result set
     *
     * @param int $fetchMode  fetch mode to use, if none given the default one is used
     * @return array|null
     */
    public function fetchRow($fetchMode = null)
    {_dlog();
        // read column names on first fetch, only buffered results allow for reading it later
        if (!$this->columns) {
            $this->_readColumns();
        }
        if ($fetchMode === null) {
            $fetchMode = $this->fetchMode;
        }
        $row = null;
        switch ($this->bufferMode) {
            case PMA_Drizzle::BUFFER_RESULT:
                $row = $this->dresult->rowNext();
                break;
            case PMA_Drizzle::BUFFER_ROW:
                $row = $this->dresult->rowBuffer();
                break;
        }
        $this->_transformResultRow($row, $fetchMode);
        return $row;
    }

    /**
     * Adjusts the result pointer to an arbitrary row in buffered result
     *
     * @param $row_index
     * @return bool
     */
    public function seek($row_index)
    {_dlog();
        if ($this->bufferMode != PMA_Drizzle::BUFFER_RESULT) {
            trigger_error("Can't seek in an unbuffered result set", E_USER_WARNING);
            return false;
        }
        // rowSeek always returns NULL (drizzle extension v.0.5, API v.7)
        if ($row_index >= 0 && $row_index < $this->dresult->rowCount()) {
            $this->dresult->rowSeek($row_index);
            return true;
        }
        return false;
    }

    /**
     * Returns the number of rows in buffered result set
     *
     * @return int|false
     */
    public function numRows()
    {_dlog();
        if ($this->bufferMode != PMA_Drizzle::BUFFER_RESULT) {
            trigger_error("Can't count rows in an unbuffered result set", E_USER_WARNING);
            return false;
        }
        return $this->dresult->rowCount();
    }

    /**
     * Returns the number of rows affected by query
     *
     * @return int|false
     */
    public function affectedRows()
    {_dlog();
        return $this->dresult->affectedRows();
    }

    /**
     * Frees resources taken by this result
     */
    public function free()
    {_dlog();
        unset($this->columns);
        unset($this->columnNames);
        drizzle_result_free($this->dresult);
        unset($this->dresult);
    }
}
