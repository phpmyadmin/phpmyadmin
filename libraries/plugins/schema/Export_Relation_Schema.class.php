<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Contains PMA_Export_Relation_Schema class which is inherited
 * by all schema classes.
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * This class is inherited by all schema classes
 * It contains those methods which are common in them
 * it works like factory pattern
 *
 * @package PhpMyAdmin
 */
class PMA_Export_Relation_Schema
{
    /**
     * Constructor.
     *
     * @see PMA_SVG
     */
    function __construct()
    {
        $this->setPageNumber($_REQUEST['page_number']);
        $this->setOffline(isset($_REQUEST['offline_export']));
    }

    protected $showColor;
    protected $tableDimension;
    protected $sameWide;
    protected $showKeys;
    protected $orientation;
    protected $paper;

    protected $pageNumber;
    protected $offline;

    /**
     * Set Page Number
     *
     * @param integer $value Page Number of the document to be created
     *
     * @return void
     *
     * @access public
     */
    public function setPageNumber($value)
    {
        $this->pageNumber = $value;
    }

    /**
     * Returns the schema page number
     *
     * @return integer schema page number
     */
    public function getPageNumber()
    {
        return $this->pageNumber;
    }

    /**
     * Sets showColor
     *
     * @param boolean $value whether to show colors
     *
     * @return void
     */
    public function setShowColor($value)
    {
        $this->showColor = $value;
    }

    /**
     * Returns whether to show colors
     *
     * @return boolean whether to show colors
     */
    public function isShowColor()
    {
        return $this->showColor;
    }

    /**
     * Set Table Dimension
     *
     * @param boolean $value show table co-ordinates or not
     *
     * @return void
     *
     * @access public
     */
    public function setTableDimension($value)
    {
        $this->tableDimension = $value;
    }

    /**
     * Returns whether to show table dimensions
     *
     * @return boolean whether to show table dimensions
     */
    public function isTableDimension()
    {
        return $this->tableDimension;
    }

    /**
     * Set same width of All Tables
     *
     * @param boolean $value set same width of all tables or not
     *
     * @return void
     *
     * @access public
     */
    public function setAllTablesSameWidth($value)
    {
        $this->sameWide = $value;
    }

    /**
     * Returns whether to use same width for all tables or not
     *
     * @return boolean whether to use same width for all tables or not
     */
    public function isAllTableSameWidth()
    {
        return $this->sameWide;
    }

    /**
     * Set Show only keys
     *
     * @param boolean $value show only keys or not
     *
     * @return void
     *
     * @access public
     */
    public function setShowKeys($value)
    {
        $this->showKeys = $value;
    }

    /**
     * Returns whether to show keys
     *
     * @return boolean whether to show keys
     */
    public function isShowKeys()
    {
        return $this->showKeys;
    }

    /**
     * Set Orientation
     *
     * @param string $value Orientation will be portrait or landscape
     *
     * @return void
     *
     * @access public
     */
    public function setOrientation($value)
    {
        $this->orientation = ($value == 'P') ? 'P' : 'L';
    }

    /**
     * Returns orientation
     *
     * @return string orientation
     */
    public function getOrientation()
    {
        return $this->orientation;
    }

    /**
     * Set type of paper
     *
     * @param string $value paper type can be A4 etc
     *
     * @return void
     *
     * @access public
     */
    public function setPaper($value)
    {
        $this->paper = $value;
    }

    /**
     * Returns the paper size
     *
     * @return string paper size
     */
    public function getPaper()
    {
        return $this->paper;
    }

    /**
     * Set whether the document is generated from client side DB
     *
     * @param boolean $value offline or not
     *
     * @return void
     *
     * @access public
     */
    public function setOffline($value)
    {
        $this->offline = $value;
    }

    /**
     * Returns whether the client side database is used
     *
     * @return boolean
     *
     * @access public
     */
    public function isOffline()
    {
        return $this->offline;
    }

    /**
     * get all tables involved or included in page
     *
     * @param string  $db         name of the database
     * @param integer $pageNumber page no. whose tables will be fetched in an array
     *
     * @return Array an array of tables
     *
     * @access public
     */
    public function getAllTables($db, $pageNumber)
    {
        // Get All tables
        $tab_sql = 'SELECT table_name FROM '
            . PMA_Util::backquote($GLOBALS['cfgRelation']['db']) . '.'
            . PMA_Util::backquote($GLOBALS['cfgRelation']['table_coords'])
            . ' WHERE db_name = \'' . PMA_Util::sqlAddSlashes($db) . '\''
            . ' AND pdf_page_number = ' . $pageNumber;

        $tab_rs = PMA_queryAsControlUser(
            $tab_sql, null, PMA_DatabaseInterface::QUERY_STORE
        );
        if (! $tab_rs || ! $GLOBALS['dbi']->numRows($tab_rs) > 0) {
            $this->dieSchema('', __('This page does not contain any tables!'));
        }
        //Fix undefined error
        $alltables = array();
        while ($curr_table = @$GLOBALS['dbi']->fetchAssoc($tab_rs)) {
            $alltables[] = PMA_Util::sqlAddSlashes($curr_table['table_name']);
        }
        return $alltables;
    }

    /**
     * Displays an error message
     *
     * @param integer $pageNumber    ID of the chosen page
     * @param string  $type          Schema Type
     * @param string  $error_message The error mesage
     *
     * @access public
     *
     * @return void
     */
    function dieSchema($pageNumber, $type = '', $error_message = '')
    {
        echo "<p><strong>" . __("SCHEMA ERROR: ") .  $type . "</strong></p>" . "\n";
        if (!empty($error_message)) {
            $error_message = htmlspecialchars($error_message);
        }
        echo '<p>' . "\n";
        echo '    ' . $error_message . "\n";
        echo '</p>' . "\n";
        echo '<a href="schema_edit.php?' . PMA_URL_getCommon($GLOBALS['db'])
            . '&do=selectpage&chpage=' . htmlspecialchars($pageNumber)
            . '&action_choose=0'
            . '">' . __('Back') . '</a>';
        echo "\n";
        exit;
    }
}
?>
