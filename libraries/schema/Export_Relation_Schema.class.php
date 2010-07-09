<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 *
 * @version $Id$
 * @package phpMyAdmin
 */

/**
 * This class is inherited by all schema classes
 * It contains those methods which are common in them
 * it works like factory pattern
 *
 * @name Export Relation Schema
 * @author Muhammad Adnan <hiddenpearls@gmail.com>
 * @copyright
 * @license
 */

class PMA_Export_Relation_Schema
{
    private $_pageTitle; // Title of the page
    //protected $sameWide;
    public $showGrid;
    public $showColor;
    public $tableDimension;
    public $sameWide;
    public $withDoc;
    public $showKeys;
    public $orientation;
    public $paper;
    public $pageNumber;

    public function setPageNumber($value)
    {
        $this->pageNumber = isset($value) ? $value : 1;
    }
    
    public function setShowGrid($value)
    {
        $this->showGrid = (isset($value) && $value == 'on') ? 1 : 0;
    }
    
    public function setShowColor($value)
    {
        $this->showColor = (isset($value) && $value == 'on') ? 1 : 0;
    }
    
    public function setTableDimension($value)
    {
        $this->tableDimension = (isset($value) && $value == 'on') ? 1 : 0;
    }
    
    public function setAllTableSameWidth($value)
    {
        $this->sameWide = (isset($value) && $value == 'on') ? 1 : 0;
    }
    
    public function setWithDataDictionary($value)
    {
        $this->withDoc = (isset($value) && $value == 'on') ? 1 : 0;
    }
    
    public function setShowKeys($value)
    {
        $this->showKeys = (isset($value) && $value == 'on') ? 1 : 0;
    }    
    
    public function setOrientation($value)
    {
        $this->orientation = (isset($value) && $value == 'P') ? 'P' : 'L';
    }
    
    public function setPaper($value)
    {
        $this->paper = isset($value) ? $value : 'A4';
    }
    
    public function setPageTitle($title)
    {
        $this->_pageTitle=$title;
    }
    
    public function setExportType($value)
    {
        $this->exportType=$value;
    }

    public function getAllTables($db,$pageNumber)
    {
        global $cfgRelation;
         // Get All tables
        $tab_sql = 'SELECT table_name FROM ' . PMA_backquote($GLOBALS['cfgRelation']['db']) . '.' . PMA_backquote($cfgRelation['table_coords'])
                . ' WHERE db_name = \'' . PMA_sqlAddslashes($db) . '\''
                . ' AND pdf_page_number = ' . $pageNumber;
        // echo $tab_sql;
        $tab_rs = PMA_query_as_controluser($tab_sql, null, PMA_DBI_QUERY_STORE);
        if (!$tab_rs || !PMA_DBI_num_rows($tab_rs) > 0) {
            $this->_die('',__('No tables'));
        }
        while ($curr_table = @PMA_DBI_fetch_assoc($tab_rs)) {
            $alltables[] = PMA_sqlAddslashes($curr_table['table_name']);
        }
        return $alltables;
    }

    /**
     * Displays an error message
     */
    function dieSchema($type = '',$error_message = '')
    {
        global $db;

        require_once './libraries/header.inc.php';

        echo "<p><strong> {$type} " . __("SCHEMA ERROR") . "</strong></p>" . "\n";
        if (!empty($error_message)) {
            $error_message = htmlspecialchars($error_message);
        }
        echo '<p>' . "\n";
        echo '    ' . $error_message . "\n";
        echo '</p>' . "\n";

        echo '<a href="export_relation_schema.php?' . PMA_generate_common_url($db)
         . '">' . __('Back') . '</a>';
        echo "\n";

        require_once './libraries/footer.inc.php';
    }
}
?>