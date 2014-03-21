<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Contains abstract class to hold table preferences/statistics
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Table preferences/statistics
 *
 * This class preserves the table co-ordinates,fields
 * and helps in drawing/generating the tables.
 *
 * @package PhpMyAdmin
 * @abstract
 */
abstract class TableStats
{
    protected $diagram;
    protected $db;
    protected $pageNumber;
    protected $tableName;

    protected $showKeys;
    protected $showInfo;

    public $displayfield;
    public $fields = array();
    public $primary = array();
    public $x, $y;

    public $width = 0;
    public $heightCell = 0;

    /**
     * Constructor
     *
     * @param object  $diagram    schema diagram
     * @param string  $db         current db name
     * @param integer $pageNumber current page number (from the
     *                            $cfg['Servers'][$i]['table_coords'] table)
     * @param string  $tableName  table name
     * @param boolean $showKeys   whether to display keys or not
     * @param boolean $showInfo   whether to display table position or not
     */
    public function __construct(
        $diagram, $db, $pageNumber, $tableName, $showKeys, $showInfo
    ) {
        $this->diagram    = $diagram;
        $this->db         = $db;
        $this->pageNumber = $pageNumber;
        $this->tableName  = $tableName;

        $this->showKeys   = $showKeys;
        $this->showInfo   = $showInfo;

        // checks whether the table exists
        // and loads fields
        $this->validateTableAndLoadFields();
        // load table coordinates
        $this->loadCoordinates();
        // loads display field
        $this->loadDisplayField();
        // loads primary keys
        $this->loadPrimaryKey();
    }

    /**
     * Validate whether the table exists.
     *
     * @return void
     */
    protected function validateTableAndLoadFields()
    {
        $sql = 'DESCRIBE ' . PMA_Util::backquote($this->tableName);
        $result = $GLOBALS['dbi']->tryQuery(
            $sql, null, PMA_DatabaseInterface::QUERY_STORE
        );
        if (! $result || ! $GLOBALS['dbi']->numRows($result)) {
            $this->showMissingTableError();
        }

        if ($this->showKeys) {
            $indexes = PMA_Index::getFromTable($this->tableName, $this->db);
            $all_columns = array();
            foreach ($indexes as $index) {
                $all_columns = array_merge(
                    $all_columns,
                    array_flip(array_keys($index->getColumns()))
                );
            }
            $this->fields = array_keys($all_columns);
        } else {
            while ($row = $GLOBALS['dbi']->fetchRow($result)) {
                $this->fields[] = $row[0];
            }
        }
    }

    /**
     * Displays an error when the table cannot be found.
     *
     * @return void
     * @abstract
     */
    protected abstract function showMissingTableError();

    /**
     * Loads coordinates of a table
     *
     * @return void
     */
    protected function loadCoordinates()
    {
        global $cfgRelation;

        $sql = "SELECT x, y FROM "
            . PMA_Util::backquote($GLOBALS['cfgRelation']['db']) . "."
                . PMA_Util::backquote($cfgRelation['table_coords'])
                . " WHERE db_name = '" . PMA_Util::sqlAddSlashes($this->db) . "'"
                . " AND   table_name = '" . PMA_Util::sqlAddSlashes($this->tableName) . "'"
                . " AND   pdf_page_number = " . $this->pageNumber;
        $result = PMA_queryAsControlUser(
            $sql, false, PMA_DatabaseInterface::QUERY_STORE
        );
        if (! $result || ! $GLOBALS['dbi']->numRows($result)) {
            $this->showMissingCoordinatesError();
        }
        list($this->x, $this->y) = $GLOBALS['dbi']->fetchRow($result);
        $this->x = (double) $this->x;
        $this->y = (double) $this->y;
    }

    /**
     * Displays an error on missing coordinates
     *
     * @return void
     * @abstract
     */
    protected abstract function showMissingCoordinatesError();

    /**
     * Loads the table's display field
     *
     * @return void
     */
    protected function loadDisplayField()
    {
        $this->displayfield = PMA_getDisplayField($this->db, $this->tableName);
    }

    /**
     * Loads the PRIMARY key.
     *
     * @return void
     */
    protected function loadPrimaryKey()
    {
        $result = $GLOBALS['dbi']->query(
            'SHOW INDEX FROM ' . PMA_Util::backquote($this->tableName) . ';',
            null, PMA_DatabaseInterface::QUERY_STORE
        );
        if ($GLOBALS['dbi']->numRows($result) > 0) {
            while ($row = $GLOBALS['dbi']->fetchAssoc($result)) {
                if ($row['Key_name'] == 'PRIMARY') {
                    $this->primary[] = $row['Column_name'];
                }
            }
        }
    }

    /**
     * Returns title of the current table,
     * title can have the dimensions/co-ordinates of the table
     *
     * @return string title of the current table
     */
    protected function getTitle()
    {
        return ($this->showInfo
            ? sprintf('%.0fx%0.f', $this->width, $this->heightCell)
            : ''
        )
        . ' ' . $this->tableName;
    }
}
?>