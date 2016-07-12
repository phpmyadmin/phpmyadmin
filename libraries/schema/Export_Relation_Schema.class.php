<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
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
 */
class PMA_Export_Relation_Schema
{
    private $_pageTitle;
    public $showGrid;
    public $showColor;
    public $tableDimension;
    public $sameWide;
    public $withDoc;
    public $showKeys;
    public $orientation;
    public $paper;
    public $pageNumber;

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
        $this->pageNumber = isset($value) ? intval($value) : 1;
    }

    /**
     * Set Show Grid
     *
     * @param boolean $value show grid of the document or not
     *
     * @return void
     *
     * @access public
     */
    public function setShowGrid($value)
    {
        $this->showGrid = (isset($value) && $value == 'on') ? 1 : 0;
    }

    /**
     * Sets showColor
     *
     * @param string $value 'on' to set the the variable
     *
     * @return void
     */
    public function setShowColor($value)
    {
        $this->showColor = (isset($value) && $value == 'on') ? 1 : 0;
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
        $this->tableDimension = (isset($value) && $value == 'on') ? 1 : 0;
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
        $this->sameWide = (isset($value) && $value == 'on') ? 1 : 0;
    }

    /**
     * Set Data Dictionary
     *
     * @param boolean $value show selected database data dictionary or not
     *
     * @return void
     *
     * @access public
     */
    public function setWithDataDictionary($value)
    {
        $this->withDoc = (isset($value) && $value == 'on') ? 1 : 0;
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
        $this->showKeys = (isset($value) && $value == 'on') ? 1 : 0;
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
        $this->orientation = (isset($value) && $value == 'P') ? 'P' : 'L';
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
        $this->paper = isset($value) ? $value : 'A4';
    }

    /**
     * Set title of the page
     *
     * @param string $title title of the page displayed at top of the document
     *
     * @return void
     *
     * @access public
     */
    public function setPageTitle($title)
    {
        $this->_pageTitle=$title;
    }

    /**
     * Set type of export relational schema
     *
     * @param string $value can be pdf,svg,dia,eps etc
     *
     * @return void
     *
     * @access public
     */
    public function setExportType($value)
    {
        $this->exportType=$value;
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
        global $cfgRelation;

        // Get All tables
        $tab_sql = 'SELECT table_name FROM '
            . PMA_Util::backquote($GLOBALS['cfgRelation']['db']) . '.'
            . PMA_Util::backquote($cfgRelation['table_coords'])
            . ' WHERE db_name = \'' . PMA_Util::sqlAddSlashes($db) . '\''
            . ' AND pdf_page_number = ' . $pageNumber;

        $tab_rs = PMA_queryAsControlUser($tab_sql, null, PMA_DBI_QUERY_STORE);
        if (!$tab_rs || !PMA_DBI_num_rows($tab_rs) > 0) {
            $this->dieSchema('', __('This page does not contain any tables!'));
        }
        while ($curr_table = @PMA_DBI_fetch_assoc($tab_rs)) {
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
     * @global array    the PMA configuration array
     * @global string   the current database name
     *
     * @access public
     *
     * @return void
     */
    function dieSchema($pageNumber, $type = '', $error_message = '')
    {
        global $db;

        echo "<p><strong>" . __("SCHEMA ERROR: ") .  $type . "</strong></p>" . "\n";
        if (!empty($error_message)) {
            $error_message = htmlspecialchars($error_message);
        }
        echo '<p>' . "\n";
        echo '    ' . $error_message . "\n";
        echo '</p>' . "\n";
        echo '<a href="schema_edit.php?' . PMA_generate_common_url($db)
            . '&do=selectpage&chpage=' . htmlspecialchars($pageNumber)
            . '&action_choose=0'
            . '">' . __('Back') . '</a>';
        echo "\n";
        exit;
    }
}
?>
