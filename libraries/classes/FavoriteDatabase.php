<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Favorite database list handling
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin;

/**
 * Handles the favorite databases.
 *
 * @TODO Change the release version in table pma_recent
 * (#recent in documentation)
 *
 * @package PhpMyAdmin
 */
class FavoriteDatabase
{
    /**
     * Reference to session variable containing favorite databases.
     *
     * @access private
     * @var array
     */
    private $_databases;

    /**
     * Defines type of action, Favorite database.
     *
     * @access private
     * @var string
     */
    private $_tableType;

    /**
     * RecentFavoriteTable instances.
     *
     * @access private
     * @var array
     */
    private static $_instances = [];

    /**
     * @var Relation
     */
    private $relation;

    /**
     * Creates a new instance of FavoriteDatabase
     *
     * @param string $type the table type
     *
     * @access private
     */
    private function __construct($type)
    {
        $this->_tableType = $type;
        $this->relation = new Relation($GLOBALS['dbi']);
        $server_id = $GLOBALS['server'];
        if (! isset($_SESSION['tmpval'][$this->_tableType . 'Tables'][$server_id])
        ) {
            $_SESSION['tmpval'][$this->_tableType . 'Tables'][$server_id]
                = $this->_getPmaTable() ? $this->getFromDb() : [];

        }
        $this->_databases
            =& $_SESSION['tmpval'][$this->_tableType . 'Tables'][$server_id];

    }

    /**
     * Returns class instance.
     *
     * @param string $type the table type
     *
     * @return RecentFavoriteTable
     */
    public static function getInstance($type)
    {

        if (! array_key_exists($type, self::$_instances)) {
            self::$_instances[$type] = new FavoriteDatabase($type);
        }
        return self::$_instances[$type];
    }

    /**
     * Returns the favorite databases array
     *
     * @return array
     */
    public function getDatabases()
    {
        return $this->_databases;
    }

    /**
     * Returns favorite database from phpMyAdmin database.
     *
     * @return array
     */
    public function getFromDb()
    {
        // Read from phpMyAdmin database, if not in session
        $sql_query
            = " SELECT `tables` FROM " . $this->_getPmaTable() .
            " WHERE `username` = '" . $GLOBALS['dbi']->escapeString($GLOBALS['cfg']['Server']['user']) . "'";

        $return = [];
        $result = $this->relation->queryAsControlUser($sql_query, false);
        if ($result) {
            $row = $GLOBALS['dbi']->fetchArray($result);
            if (isset($row[0])) {
                $return = json_decode($row[0], true);
            }
        }
        return $return;
    }

    /**
     * Save favorite database into phpMyAdmin database.
     *
     * @return true|Message
     */
    public function saveToDb()
    {
        $username = $GLOBALS['cfg']['Server']['user'];
        $sql_query
            = " INSERT OR IGNORE INTO " . $this->_getPmaTable() . " (`username`, `tables`)" .
                " VALUES ('" . $GLOBALS['dbi']->escapeString($username) . "', '"
                . $GLOBALS['dbi']->escapeString(
                    json_encode($this->_databases)
                ) . "')";

        $success = $GLOBALS['dbi']->tryQuery($sql_query, DatabaseInterface::CONNECT_CONTROL);

        if (! $success) {
            $error_msg = '';
                    $error_msg = __('Could not save favorite database!');
            }
            $message = Message::error($error_msg);
            $message->addMessage(
                Message::rawError(
                    $GLOBALS['dbi']->getError(DatabaseInterface::CONNECT_CONTROL)
                ),
                '<br><br>'
            );
            return $message;

        return true;
    }

    /**
     * Return HTML ul.
     *
     * @return string
     */
    public function getHtmlList()
    {
        $html = '';
        if (count($this->_databases)) {
                foreach ($this->_databases as $db) {

                    $html .= '<li class="warp_link">';

                    $html .= '<a class="ajax favorite_database_anchor" ';
                    $fav_params = [
                        'ajax_request'    => true,
                        'favorite_db'  => $db['db'],
                        'remove_favorite' => true,
                    ];
                    $fav_rm_url = 'server_databases.php'
                        . Url::getCommon($fav_params);
                    $html .= 'href="' . $fav_rm_url
                        . '" title="' . __("Remove from Favorites")
                        . '" data-favtargetn="'
                        . md5($db['db'])
                        . '" >'
                        . Util::getIcon('b_favorite')
                        . '</a>' ;
                    $fav_params = [
                        'db'    => $db['db'],
                    ];
                    $db_url = 'db_structure.php'
                        . Url::getCommon($fav_params);
                    $html .= '<a href="' . $db_url . '">';
                    $html .= htmlspecialchars($db['db']) . '</a>';
                    $html .= '</li>';
            }
        } else {
                    $html = '<li class="warp_link">';
                        $html.= ('There are no favorite Databases.');
                  $html.= '</li>';
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
        if ($this->_tableType == 'favorite_db') {
            $html .= '<button title="' . __('Favorite Databases')
            . '" class="drop_button btn">'
            . __('Databases') . '</button><ul id="pma_favorite_db_list">';
        }
        $html .= $this->getHtmlList();
        $html .= '</ul></div>';
        return $html;
    }

    /**
     * Add recently used or favorite Databases.
     *
     * @param string $db database name
     *
     * @return true|Message True if success, Message if not
     */
    public function add($db)
    {

        $table_arr = [];
        $table_arr['db'] = $db;

        // add only if this is new table
        if (! isset($this->_databases[0]) || $this->_databases[0] != $table_arr) {
            array_unshift($this->_databases, $table_arr);
            $this->_databases = array_merge(array_unique($this->_databases, SORT_REGULAR));
            if ($this->_getPmaTable()) {
                return $this->saveToDb();
            }
        }
        return true;
    }

    /**
     * Remove favorite tables.
     *
     * @param string $db    database name
     *
     * @return true|Message True if success, Message if not
     */
    public function remove($current_database)
    {

        foreach ($this->_databases as $key => $value) {

            if ($value['db'] == $current_database) {
                unset($this->_databases[$key]);
            }
        }
        if ($this->_getPmaTable()) {
            return $this->saveToDb();
        }
        return true;
    }

    /**
     * Reutrn the name of the configuration storage table
     *
     * @return string|null pma table name
     */
    private function _getPmaTable()
    {
        $cfgRelation = $this->relation->getRelationsParam();
        if (! empty($cfgRelation['db'])
            && ! empty($cfgRelation[$this->_tableType])
        ) {
            return Util::backquote($cfgRelation['db']) . "."
                . Util::backquote($cfgRelation[$this->_tableType]);
        }
        return null;
    }
}