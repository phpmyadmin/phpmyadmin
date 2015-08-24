<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Wrappers for Drizzle extension classes
 *
 * Drizzle extension exposes libdrizzle functions and requires user to have it in
 * mind while using them.
 * This wrapper is not complete and hides a lot of original functionality,
 * but allows for easy usage of the drizzle PHP extension.
 *
 * @package    PhpMyAdmin-DBI
 * @subpackage Drizzle
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Workaround for crashing module
 *
 * @return void
 *
 * @todo drizzle module segfaults while freeing resources, often.
 *       This allows at least for some development
 */
function PMA_drizzleShutdownFlush()
{
    flush();
}
register_shutdown_function('PMA_drizzleShutdownFlush');

/**
 * Wrapper for Drizzle class
 *
 * @package    PhpMyAdmin-DBI
 * @subpackage Drizzle
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
     * Creates a new database connection using TCP
     *
     * @param string  $host     Drizzle host
     * @param integer $port     Drizzle port
     * @param string  $user     username
     * @param string  $password password
     * @param string  $db       database name
     * @param integer $options  connection options
     *
     * @return PMA_DrizzleCon
     */
    public function addTcp($host, $port, $user, $password, $db, $options)
    {
        $dcon = parent::addTcp($host, $port, $user, $password, $db, $options);
        return $dcon instanceof DrizzleCon
            ? new PMA_DrizzleCon($dcon)
            : $dcon;
    }

    /**
     * Creates a new connection using unix domain socket
     *
     * @param string  $uds      socket
     * @param string  $user     username
     * @param string  $password password
     * @param string  $db       database name
     * @param integer $options  connection options
     *
     * @return PMA_DrizzleCon
     */
    public function addUds($uds, $user, $password, $db, $options)
    {
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
 *
 * @package    PhpMyAdmin-DBI
 * @subpackage Drizzle
 *
 * @method string host() Get host
 * @method int port() Get port
 * @method int protocolVersion() Get protocol version
 * @method resource selectDb(string $dbname) Select a DB
 */
class PMA_DrizzleCon
{
    /**
     * Instance of DrizzleCon class
     * @var DrizzleCon
     */
    private $_dcon;

    /**
     * Result of the most recent query
     * @var PMA_DrizzleResult
     */
    private $_lastResult;

    /**
     * Constructor
     *
     * @param DrizzleCon $dcon connection handle
     */
    public function __construct(DrizzleCon $dcon)
    {
        $this->_dcon = $dcon;
    }

    /**
     * Executes given query. Opens database connection if not already done.
     *
     * @param string $query      query to execute
     * @param int    $bufferMode PMA_Drizzle::BUFFER_RESULT,PMA_Drizzle::BUFFER_ROW
     * @param int    $fetchMode  PMA_Drizzle::FETCH_ASSOC, PMA_Drizzle::FETCH_NUM
     *                           or PMA_Drizzle::FETCH_BOTH
     *
     * @return PMA_DrizzleResult
     */
    public function query($query, $bufferMode = PMA_Drizzle::BUFFER_RESULT,
        $fetchMode = PMA_Drizzle::FETCH_ASSOC
    ) {
        $result = $this->_dcon->query($query);
        if ($result instanceof DrizzleResult) {
            $this->_lastResult = new PMA_DrizzleResult(
                $result, $bufferMode, $fetchMode
            );
            return $this->_lastResult;
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
        return $this->_lastResult
            ? $this->_lastResult->affectedRows()
            : false;
    }

    /**
     * Pass calls of undefined methods to DrizzleCon object
     *
     * @param string $method method name
     * @param mixed  $args   method parameters
     *
     * @return mixed
     */
    public function __call($method, $args)
    {
        return call_user_func_array(array($this->_dcon, $method), $args);
    }

    /**
     * Returns original Drizzle connection object
     *
     * @return DrizzleCon
     */
    public function getConnectionObject()
    {
        return $this->_dcon;
    }
}

/**
 * Wrapper around DrizzleResult.
 *
 * Allows for reading result rows as an associative array and hides complexity
 * behind buffering.
 *
 * @package    PhpMyAdmin-DBI
 * @subpackage Drizzle
 */
class PMA_DrizzleResult
{
    /**
     * Instance of DrizzleResult class
     * @var DrizzleResult
     */
    private $_dresult;
    /**
     * Fetch mode
     * @var int
     */
    private $_fetchMode;
    /**
     * Buffering mode
     * @var int
     */
    private $_bufferMode;

    /**
     * Cached column data
     * @var DrizzleColumn[]
     */
    private $_columns = null;
    /**
     * Cached column names
     * @var string[]
     */
    private $_columnNames = null;

    /**
     * Constructor
     *
     * @param DrizzleResult $dresult    result handler
     * @param int           $bufferMode buffering mode
     * @param int           $fetchMode  fetching mode
     */
    public function __construct(DrizzleResult $dresult, $bufferMode, $fetchMode)
    {
        $this->_dresult = $dresult;
        $this->_bufferMode = $bufferMode;
        $this->_fetchMode = $fetchMode;

        if ($this->_bufferMode == PMA_Drizzle::BUFFER_RESULT) {
            $this->_dresult->buffer();
        }
    }

    /**
     * Sets fetch mode
     *
     * @param int $fetchMode fetch mode
     *
     * @return void
     */
    public function setFetchMode($fetchMode)
    {
        $this->_fetchMode = $fetchMode;
    }

    /**
     * Reads information about columns contained in current result
     * set into {@see $_columns} and {@see $_columnNames} arrays
     *
     * @return void
     */
    private function _readColumns()
    {
        $this->_columns = array();
        $this->_columnNames = array();
        if ($this->_bufferMode == PMA_Drizzle::BUFFER_RESULT) {
            while (($column = $this->_dresult->columnNext()) !== null) {
                $this->_columns[] = $column;
                $this->_columnNames[] = $column->name();
            }
        } else {
            while (($column = $this->_dresult->columnRead()) !== null) {
                $this->_columns[] = $column;
                $this->_columnNames[] = $column->name();
            }
        }
    }

    /**
     * Returns columns in current result
     *
     * @return DrizzleColumn[]
     */
    public function getColumns()
    {
        if (! $this->_columns) {
            $this->_readColumns();
        }
        return $this->_columns;
    }

    /**
     * Returns number if columns in result
     *
     * @return int
     */
    public function numColumns()
    {
        return $this->_dresult->columnCount();
    }

    /**
     * Transforms result row to conform to current fetch mode
     *
     * @param mixed &$row      row to process
     * @param int   $fetchMode fetch mode
     *
     * @return void
     */
    private function _transformResultRow(&$row, $fetchMode)
    {
        if (! $row) {
            return;
        }

        switch ($fetchMode) {
        case PMA_Drizzle::FETCH_ASSOC:
            $row = array_combine($this->_columnNames, $row);
            break;
        case PMA_Drizzle::FETCH_BOTH:
            $length = count($row);
            for ($i = 0; $i < $length; $i++) {
                $row[$this->_columnNames[$i]] = $row[$i];
            }
            break;
        default:
            break;
        }
    }

    /**
     * Fetches next for from this result set
     *
     * @param int $fetchMode fetch mode to use, if not given the default one is used
     *
     * @return array|null
     */
    public function fetchRow($fetchMode = null)
    {
        // read column names on first fetch, only buffered results
        // allow for reading it later
        if (! $this->_columns) {
            $this->_readColumns();
        }
        if ($fetchMode === null) {
            $fetchMode = $this->_fetchMode;
        }
        $row = null;
        switch ($this->_bufferMode) {
        case PMA_Drizzle::BUFFER_RESULT:
            $row = $this->_dresult->rowNext();
            break;
        case PMA_Drizzle::BUFFER_ROW:
            $row = $this->_dresult->rowBuffer();
            break;
        }
        $this->_transformResultRow($row, $fetchMode);
        return $row;
    }

    /**
     * Adjusts the result pointer to an arbitrary row in buffered result
     *
     * @param integer $row_index where to seek
     *
     * @return bool
     */
    public function seek($row_index)
    {
        if ($this->_bufferMode != PMA_Drizzle::BUFFER_RESULT) {
            trigger_error(
                __("Can't seek in an unbuffered result set"), E_USER_WARNING
            );
            return false;
        }
        // rowSeek always returns NULL (drizzle extension v.0.5, API v.7)
        if ($row_index >= 0 && $row_index < $this->_dresult->rowCount()) {
            $this->_dresult->rowSeek($row_index);
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
    {
        if ($this->_bufferMode != PMA_Drizzle::BUFFER_RESULT) {
            trigger_error(
                __("Can't count rows in an unbuffered result set"), E_USER_WARNING
            );
            return false;
        }
        return $this->_dresult->rowCount();
    }

    /**
     * Returns the number of rows affected by query
     *
     * @return int|false
     */
    public function affectedRows()
    {
        return $this->_dresult->affectedRows();
    }

    /**
     * Frees resources taken by this result
     *
     * @return void
     */
    public function free()
    {
        unset($this->_columns);
        unset($this->_columnNames);
        drizzle_result_free($this->_dresult);
        unset($this->_dresult);
    }
}
