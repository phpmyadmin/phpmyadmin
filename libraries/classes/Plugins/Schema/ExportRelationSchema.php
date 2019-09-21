<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Contains PhpMyAdmin\Plugins\Schema\ExportRelationSchema class which is
 * inherited by all schema classes.
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Plugins\Schema;

use PhpMyAdmin\Relation;
use PhpMyAdmin\Url;
use PhpMyAdmin\Util;

/**
 * This class is inherited by all schema classes
 * It contains those methods which are common in them
 * it works like factory pattern
 *
 * @package PhpMyAdmin
 */
class ExportRelationSchema
{
    protected $db;
    protected $diagram;
    protected $showColor;
    protected $tableDimension;
    protected $sameWide;
    protected $showKeys;
    protected $orientation;
    protected $paper;
    protected $pageNumber;
    protected $offline;

    /**
     * @var Relation $relation
     */
    protected $relation;

    /**
     * Constructor.
     *
     * @param string $db      database name
     * @param object $diagram schema diagram
     */
    public function __construct($db, $diagram)
    {
        $this->db = $db;
        $this->diagram = $diagram;
        $this->setPageNumber($_REQUEST['page_number']);
        $this->setOffline(isset($_REQUEST['offline_export']));
        $this->relation = new Relation();
    }

    /**
     * Set Page Number
     *
     * @param integer $value Page Number of the document to be created
     *
     * @return void
     */
    public function setPageNumber($value)
    {
        $this->pageNumber = intval($value);
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
     * Get the table names from the request
     *
     * @return array an array of table names
     */
    protected function getTablesFromRequest()
    {
        $tables = [];
        if (isset($_POST['t_tbl'])) {
            foreach($_POST['t_tbl'] as $table) {
                $tables[] = rawurldecode($table);
            }
        }
        return $tables;
    }

    /**
     * Returns the file name
     *
     * @param String $extension file extension
     *
     * @return string file name
     */
    protected function getFileName($extension)
    {
        $filename = $this->db . $extension;
        // Get the name of this page to use as filename
        if ($this->pageNumber != -1 && !$this->offline) {
            $_name_sql = 'SELECT page_descr FROM '
                . Util::backquote($GLOBALS['cfgRelation']['db']) . '.'
                . Util::backquote($GLOBALS['cfgRelation']['pdf_pages'])
                . ' WHERE page_nr = ' . $this->pageNumber;
            $_name_rs = $this->relation->queryAsControlUser($_name_sql);
            $_name_row = $GLOBALS['dbi']->fetchRow($_name_rs);
            $filename = $_name_row[0] . $extension;
        }

        return $filename;
    }

    /**
     * Displays an error message
     *
     * @param integer $pageNumber    ID of the chosen page
     * @param string  $type          Schema Type
     * @param string  $error_message The error message
     *
     * @access public
     *
     * @return void
     */
    public static function dieSchema($pageNumber, $type = '', $error_message = '')
    {
        echo "<p><strong>" , __("SCHEMA ERROR: ") , $type , "</strong></p>" , "\n";
        if (!empty($error_message)) {
            $error_message = htmlspecialchars($error_message);
        }
        echo '<p>' , "\n";
        echo '    ' , $error_message , "\n";
        echo '</p>' , "\n";
        echo '<a href="db_designer.php'
            , Url::getCommon(array('db' => $GLOBALS['db'], 'server' => $GLOBALS['server']))
            , '&page=' . htmlspecialchars($pageNumber) , '">' , __('Back') , '</a>';
        echo "\n";
        exit;
    }
}
