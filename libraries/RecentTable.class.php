<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Recent table list handling
 *
 * @package PhpMyAdmin
 */

if (! defined('PHPMYADMIN')) {
    exit;
}

require_once './libraries/Message.class.php';

/**
 * Handles the recently used tables.
 *
 * @TODO Change the release version in table pma_recent
 * (#recent in documentation)
 *
 * @package PhpMyAdmin
 */
class PMA_RecentTable
{
    /**
     * Defines the internal PMA table which contains recent tables.
     *
     * @access  private
     * @var string
     */
    private $_pmaTable;

    /**
     * Reference to session variable containing recently used tables.
     *
     * @access public
     * @var array
     */
    public $tables;

    /**
     * PMA_RecentTable instance.
     *
     * @var PMA_RecentTable
     */
    private static $_instance;

    /**
     * Creates a new instance of PMA_RecentTable
     */
    public function __construct()
    {
        if (strlen($GLOBALS['cfg']['Server']['pmadb'])
            && strlen($GLOBALS['cfg']['Server']['recent'])
        ) {
            $this->_pmaTable
                = PMA_Util::backquote($GLOBALS['cfg']['Server']['pmadb']) . "."
                . PMA_Util::backquote($GLOBALS['cfg']['Server']['recent']);
        }
        $server_id = $GLOBALS['server'];
        if (! isset($_SESSION['tmpval']['recent_tables'][$server_id])) {
            $_SESSION['tmpval']['recent_tables'][$server_id]
                = isset($this->_pmaTable) ? $this->getFromDb() : array();
        }
        $this->tables =& $_SESSION['tmpval']['recent_tables'][$server_id];
    }

    /**
     * Returns class instance.
     *
     * @return PMA_RecentTable
     */
    public static function getInstance()
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new PMA_RecentTable();
        }
        return self::$_instance;
    }

    /**
     * Returns recently used tables from phpMyAdmin database.
     *
     * @return array
     */
    public function getFromDb()
    {
        // Read from phpMyAdmin database, if recent tables is not in session
        $sql_query
            = " SELECT `tables` FROM " . $this->_pmaTable .
            " WHERE `username` = '" . $GLOBALS['cfg']['Server']['user'] . "'";

        $return = array();
        $result = PMA_queryAsControlUser($sql_query, false);
        if ($result) {
            $row = $GLOBALS['dbi']->fetchArray($result);
            if (isset($row[0])) {
                $return = json_decode($row[0], true);
            }
        }
        return $return;
    }

    /**
     * Save recent tables into phpMyAdmin database.
     *
     * @return true|PMA_Message
     */
    public function saveToDb()
    {
        $username = $GLOBALS['cfg']['Server']['user'];
        $sql_query
            = " REPLACE INTO " . $this->_pmaTable . " (`username`, `tables`)" .
                " VALUES ('" . $username . "', '"
                . PMA_Util::sqlAddSlashes(
                    json_encode($this->tables)
                ) . "')";

        $success = $GLOBALS['dbi']->tryQuery($sql_query, $GLOBALS['controllink']);

        if (! $success) {
            $message = PMA_Message::error(__('Could not save recent table'));
            $message->addMessage('<br /><br />');
            $message->addMessage(
                PMA_Message::rawError(
                    $GLOBALS['dbi']->getError($GLOBALS['controllink'])
                )
            );
            return $message;
        }
        return true;
    }

    /**
     * Trim recent table according to the NumRecentTables configuration.
     *
     * @return boolean True if trimming occurred
     */
    public function trim()
    {
        $max = max($GLOBALS['cfg']['NumRecentTables'], 0);
        $trimming_occurred = count($this->tables) > $max;
        while (count($this->tables) > $max) {
            array_pop($this->tables);
        }
        return $trimming_occurred;
    }

    /**
     * Return options for HTML select.
     *
     * @return string
     */
    public function getHtmlSelectOption()
    {
        // trim and save, in case where the configuration is changed
        if ($this->trim() && isset($this->_pmaTable)) {
            $this->saveToDb();
        }

        $html = '<option value="">(' . __('Recent tables') . ') ...</option>';
        if (count($this->tables)) {
            foreach ($this->tables as $table) {
                $html .= '<option value="'
                    . htmlspecialchars(json_encode($table)) . '">'
                    . htmlspecialchars(
                        '`' . $table['db'] . '`.`' . $table['table'] . '`'
                    )
                    . '</option>';
            }
        } else {
            $html .= '<option value="">'
                . __('There are no recent tables')
                . '</option>';
        }
        return $html;
    }

    /**
     * Return HTML select.
     *
     * @return string
     */
    public function getHtmlSelect()
    {
        $html  = '<select name="selected_recent_table" id="recentTable">';
        $html .= $this->getHtmlSelectOption();
        $html .= '</select>';

        return $html;
    }

    /**
     * Add recently used tables.
     *
     * @param string $db    database name where the table is located
     * @param string $table table name
     *
     * @return true|PMA_Message True if success, PMA_Message if not
     */
    public function add($db, $table)
    {
        $table_arr = array();
        $table_arr['db'] = $db;
        $table_arr['table'] = $table;

        // add only if this is new table
        if (! isset($this->tables[0]) || $this->tables[0] != $table_arr) {
            array_unshift($this->tables, $table_arr);
            $this->tables = array_merge(array_unique($this->tables, SORT_REGULAR));
            $this->trim();
            if (isset($this->_pmaTable)) {
                return $this->saveToDb();
            }
        }
        return true;
    }

}
?>
