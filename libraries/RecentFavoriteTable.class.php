<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Recent and Favorite table list handling
 *
 * @package PhpMyAdmin
 */

if (! defined('PHPMYADMIN')) {
    exit;
}

require_once './libraries/Message.class.php';

/**
 * Handles the recently used and favorite tables.
 *
 * @TODO Change the release version in table pma_recent
 * (#recent in documentation)
 *
 * @package PhpMyAdmin
 */
class PMA_RecentFavoriteTable
{
    /**
     * Defines the internal PMA table which contains recent/favorite tables.
     *
     * @access  private
     * @var string
     */
    private $_pmaTable;

    /**
     * Reference to session variable containing recently used or favorite tables.
     *
     * @access public
     * @var array
     */
    public $tables;

    /**
     * Defines type of action, Favorite or Recent table.
     *
     * @access public
     * @var string
     */
    public $table_type;

    /**
     * PMA_RecentFavoriteTable instance.
     *
     * @var PMA_RecentFavoriteTable
     */
    private static $_instance;

    /**
     * Creates a new instance of PMA_RecentFavoriteTable
     *
     * @param string $type the table type
     */
    public function __construct($type)
    {
        $this->table_type = $type;
        if (strlen($GLOBALS['cfg']['Server']['pmadb'])
            && strlen($GLOBALS['cfg']['Server'][$this->table_type])
        ) {
            $this->_pmaTable
                = PMA_Util::backquote($GLOBALS['cfg']['Server']['pmadb']) . "."
                . PMA_Util::backquote($GLOBALS['cfg']['Server'][$this->table_type]);
        }
        $server_id = $GLOBALS['server'];
        if (! isset($_SESSION['tmpval'][$this->table_type . '_tables'][$server_id])) {
            $_SESSION['tmpval'][$this->table_type . '_tables'][$server_id]
                = isset($this->_pmaTable) ? $this->getFromDb() : array();
        }
        $this->tables =& $_SESSION['tmpval'][$this->table_type . '_tables'][$server_id];
    }

    /**
     * Returns class instance.
     *
     * @param string $type the table type
     *
     * @return PMA_RecentFavoriteTable
     */
    public static function getInstance($type)
    {
        if (is_null(self::$_instance)) {
            self::$_instance = new PMA_RecentFavoriteTable($type);
        } else {
            if (self::$_instance->table_type != $type) {
                self::$_instance = new PMA_RecentFavoriteTable($type);
            }
        }
        return self::$_instance;
    }

    /**
     * Returns recently used tables or favorite from phpMyAdmin database.
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
     * Save recent/favorite tables into phpMyAdmin database.
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
            $error_msg = '';
            switch ($this->table_type) {
            case 'recent':
                $error_msg = __('Could not save recent table!');
                break;

            case 'favorite':
                $error_msg = __('Could not save favorite table!');
                break;
            }
            $message = PMA_Message::error($error_msg);
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
     * Trim recent.favorite table according to the
     * NumRecentTables/NumFavoriteTables configuration.
     *
     * @return boolean True if trimming occurred
     */
    public function trim()
    {
        $max = max(
            $GLOBALS['cfg']['Num' . ucfirst($this->table_type) . 'Tables'], 0
        );
        $trimming_occurred = count($this->tables) > $max;
        while (count($this->tables) > $max) {
            array_pop($this->tables);
        }
        return $trimming_occurred;
    }

    /**
     * Return HTML ul.
     *
     * @return string
     */
    public function getHtmlList()
    {
        // Remove Recent/Favorite tables that don't exist.
        foreach ($this->tables as $tbl) {
            if (! $GLOBALS['dbi']->getColumns($tbl['db'], $tbl['table'])) {
                $this->remove($tbl['db'], $tbl['table']);
            }
        }

        $html = '';
        if (count($this->tables)) {
            if ($this->table_type == 'recent') {
                foreach ($this->tables as $table) {
                    $html .= '<li>';
                    $html .= '<a href="sql.php?server=' . $GLOBALS['server']
                          . '&db=' . $table['db']
                          . '&table=' . $table['table']
                          . '&token=' . $_SESSION[' PMA_token ']
                          . '">`' . $table['db'] . '`.`' . $table['table'] . '`</a>';
                    $html .= '</li>';
                }
            } else {
                foreach ($this->tables as $table) {
                    $html .= '<li>';

                    $html .= '<a class="ajax favorite_table_anchor"';
                    $fav_params = array('db' => $table['db'],
                        'ajax_request' => true,
                        'favorite_table' => $table['table'],
                        'remove_favorite' => true);
                    $fav_rm_url = 'db_structure.php'
                        . PMA_URL_getCommon($fav_params);
                    $html .= 'href="' . $fav_rm_url
                        . '" title="' . __("Remove from Favorites")
                        . '" favtargetn="' . $table['db'] . "." . $table['table']
                        . '" >'
                        . PMA_Util::getIcon('b_favorite.png')
                        . '</a>';

                    $html .= '<a href="sql.php?server=' . $GLOBALS['server']
                          . '&db=' . $table['db']
                          . '&table=' . $table['table']
                          . '&token=' . $_SESSION[' PMA_token ']
                          . '">`' . $table['db'] . '`.`' . $table['table'] . '`</a>';
                    $html .= '</li>';
                }
            }
        } else {
            $html .= '<li >'
                  . ($this->table_type == 'recent'
                    ?__('There are no recent tables.')
                    :__('There are no favorite tables.'))
                  . '</li>';
        }
        return $html;
    }

    /**
     * Return HTML.
     *
     * @return string
     */
    public function getHtml()
    {
        $html  = '<div class="drop_list">';
        if ($this->table_type == 'recent') {
            $html .= '<span title="' . __('Recent tables')
                . '" class="drop_button">'
                . __('Recent') . '</span><ul id="pma_recent_list">';
        } else {
            $html .= '<span title="' . __('Favorite tables')
                . '" class="drop_button">'
                . __('Favorites') . '</span><ul id="pma_favorite_list">';
        }
        $html .= $this->getHtmlList();
        $html .= '</ul></div>';
        return $html;
    }

    /**
     * Add recently used or favorite tables.
     *
     * @param string $db    database name where the table is located
     * @param string $table table name
     *
     * @return true|PMA_Message True if success, PMA_Message if not
     */
    public function add($db, $table)
    {
        // If table doesnot exist, do not add.
        if (! $GLOBALS['dbi']->getColumns($db, $table)) {
            return true;
        }

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

    /**
     * Remove favorite tables.
     *
     * @param string $db    database name where the table is located
     * @param string $table table name
     *
     * @return true|PMA_Message True if success, PMA_Message if not
     */
    public function remove($db, $table)
    {
        $table_arr = array();
        $table_arr['db'] = $db;
        $table_arr['table'] = $table;
        foreach ($this->tables as $key => $value) {
            if ($value['db'] == $db && $value['table'] == $table) {
                unset($this->tables[$key]);
            }
        }
        if (isset($this->_pmaTable)) {
            return $this->saveToDb();
        }
        return true;
    }

    /**
     * Generate Html for sync Favorite tables anchor. (from localStorage to pmadb)
     *
     * @return string
     */
    public function getHtmlSyncFavoriteTables()
    {
        $retval = '';
        $server_id = $GLOBALS['server'];
        // Not to show this once list is synchronized.
        $is_synced = isset($_SESSION['tmpval']['favorites_synced'][$server_id]) ?
            true : false;
        if (!$is_synced) {
            $params  = array('ajax_request' => true, 'favorite_table' => true,
                'sync_favorite_tables' => true);
            $url     = 'db_structure.php' . PMA_URL_getCommon($params);
            $retval  = '<a class="hide" id="sync_favorite_tables"';
            $retval .= ' href="' . $url . '"></a>';
        }
        return $retval;
    }
}
?>
