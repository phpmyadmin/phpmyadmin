<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for relation_cleanup.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/Tracker.class.php';
require_once 'libraries/relation.lib.php';
require_once 'libraries/relation_cleanup.lib.php';


class PMA_Relation_Cleanup_Test extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $_SESSION['relation'] = array();
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['user'] = "user";
        $GLOBALS['cfg']['Server']['pmadb'] = "pmadb";
        $GLOBALS['cfg']['Server']['bookmarktable'] = 'bookmark'; 
        $GLOBALS['cfg']['Server']['relation'] = 'relation'; 
        $GLOBALS['cfg']['Server']['table_info'] = 'table_info'; 
        $GLOBALS['cfg']['Server']['table_coords'] = 'table_coords'; 
        $GLOBALS['cfg']['Server']['designer_coords'] = 'designer_coords'; 
        $GLOBALS['cfg']['Server']['column_info'] = 'column_info'; 
        $GLOBALS['cfg']['Server']['pdf_pages'] = 'pdf_pages'; 
        $GLOBALS['cfg']['Server']['history'] = 'history'; 
        $GLOBALS['cfg']['Server']['recent'] = 'recent'; 
        $GLOBALS['cfg']['Server']['table_uiprefs'] = 'table_uiprefs'; 
        $GLOBALS['cfg']['Server']['tracking'] = 'tracking';  
        $GLOBALS['cfg']['Server']['userconfig'] = 'userconfig';  
        $GLOBALS['cfg']['Server']['users'] = 'users';  
        $GLOBALS['cfg']['Server']['usergroups'] = 'usergroups'; 
        
        $this->redefineRelation();
    }
    
    public function redefineRelation()
    {     
        $GLOBALS['dbi'] = new DBI_PMA_Relation_Cleanup();
        unset($_SESSION['relation'][$GLOBALS['server']]);
    }

    /**
     * Test for PMA_relationsCleanupColumn
     *
     * @return void
     */
    public function testPMA_relationsCleanupColumn()
    {
        $db = "PMA";
        $table = "PMA_bookmark";
        $column = "name";
        $this->redefineRelation();
        
        //the $cfgRelation value before cleanup column
        $cfgRelation = PMA_checkRelationsParam();
        $this->assertEquals(
            true,
            $cfgRelation['commwork']
        );
        $this->assertEquals(
            true,
            $cfgRelation['displaywork']
        );
        $this->assertEquals(
            true,
            $cfgRelation['relwork']
        );
        $this->assertEquals(
            'column_info',
            $cfgRelation['column_info']
        );
        $this->assertEquals(
            'table_info',
            $cfgRelation['table_info']
        );
        $this->assertEquals(
            'relation',
            $cfgRelation['relation']
        );
        
        //cleanup
        PMA_relationsCleanupColumn($db, $table, $column);
        
        //the $cfgRelation value after cleanup column
        $cfgRelation = PMA_checkRelationsParam();
        
        $is_defined_column_info 
            = isset($cfgRelation['column_info'])? $cfgRelation['column_info'] : null;
        $is_defined_table_info 
            = isset($cfgRelation['table_info'])? $cfgRelation['table_info'] : null;
        $is_defined_relation
            = isset($cfgRelation['relation'])? $cfgRelation['relation'] : null;
        
        $this->assertEquals(
            null,
            $is_defined_column_info
        );
        $this->assertEquals(
            null,
            $is_defined_table_info
        );
        $this->assertEquals(
            null,
            $is_defined_relation
        );
        
    }

    /**
     * Test for PMA_relationsCleanupTable
     *
     * @return void
     */
    public function testPMA_relationsCleanupTable()
    {
        $db = "PMA";
        $table = "PMA_bookmark";
        $this->redefineRelation();
        
        //the $cfgRelation value before cleanup column
        $cfgRelation = PMA_checkRelationsParam();
        $this->assertEquals(
            'column_info',
            $cfgRelation['column_info']
        );
        $this->assertEquals(
            'table_info',
            $cfgRelation['table_info']
        );
        $this->assertEquals(
            'table_coords',
            $cfgRelation['table_coords']
        );
        $this->assertEquals(
            'designer_coords',
            $cfgRelation['designer_coords']
        );
        $this->assertEquals(
            'relation',
            $cfgRelation['relation']
        );
        
        //PMA_relationsCleanupTable
        PMA_relationsCleanupTable($db, $table);
        
        //the $cfgRelation value after cleanup column
        $cfgRelation = PMA_checkRelationsParam();
        
        $is_defined_column_info 
            = isset($cfgRelation['column_info'])? $cfgRelation['column_info'] : null;
        $is_defined_table_info 
            = isset($cfgRelation['table_info'])? $cfgRelation['table_info'] : null;
        $is_defined_relation
            = isset($cfgRelation['relation'])? $cfgRelation['relation'] : null;
        $is_defined_table_coords
            = isset($cfgRelation['table_coords'])
            ? $cfgRelation['table_coords'] 
            : null;
        $is_defined_designer_coords
            = isset($cfgRelation['designer_coords'])
            ? $cfgRelation['designer_coords'] 
            : null;
        
        $this->assertEquals(
            null,
            $is_defined_column_info
        );
        $this->assertEquals(
            null,
            $is_defined_table_info
        );
        $this->assertEquals(
            null,
            $is_defined_relation
        );
        $this->assertEquals(
            null,
            $is_defined_table_coords
        );
        $this->assertEquals(
            null,
            $is_defined_designer_coords
        );
        
    }

    /**
     * Test for PMA_relationsCleanupDatabase
     *
     * @return void
     */
    public function testPMA_relationsCleanupDatabase()
    {
        $db = "PMA";
        $this->redefineRelation();
        
        //the $cfgRelation value before cleanup column
        $cfgRelation = PMA_checkRelationsParam();
        $this->assertEquals(
            'column_info',
            $cfgRelation['column_info']
        );
        $this->assertEquals(
            'bookmark',
            $cfgRelation['bookmark']
        );
        $this->assertEquals(
            'table_info',
            $cfgRelation['table_info']
        );
        $this->assertEquals(
            'pdf_pages',
            $cfgRelation['pdf_pages']
        );
        $this->assertEquals(
            'table_coords',
            $cfgRelation['table_coords']
        );
        $this->assertEquals(
            'designer_coords',
            $cfgRelation['designer_coords']
        );
        $this->assertEquals(
            'relation',
            $cfgRelation['relation']
        );
        
        //cleanup
        PMA_relationsCleanupDatabase($db);
        
        //the value after cleanup column
        $cfgRelation = PMA_checkRelationsParam();
        
        $is_defined_column_info 
            = isset($cfgRelation['column_info'])? $cfgRelation['column_info'] : null;
        $is_defined_table_info 
            = isset($cfgRelation['table_info'])? $cfgRelation['table_info'] : null;
        $is_defined_relation
            = isset($cfgRelation['relation'])? $cfgRelation['relation'] : null;
        $is_defined_table_coords
            = isset($cfgRelation['table_coords'])
            ? $cfgRelation['table_coords'] 
            : null;
        $is_defined_designer_coords
            = isset($cfgRelation['designer_coords'])
            ? $cfgRelation['designer_coords'] 
            : null;
        
        $this->assertEquals(
            null,
            $is_defined_column_info
        );
        $this->assertEquals(
            null,
            $is_defined_table_info
        );
        $this->assertEquals(
            null,
            $is_defined_relation
        );
        $this->assertEquals(
            null,
            $is_defined_table_coords
        );
        $this->assertEquals(
            null,
            $is_defined_designer_coords
        );       
    }
}

//Mock DBI
class DBI_PMA_Relation_Cleanup extends PMA_DatabaseInterface
{
    var $index;
    var $values = array();
    var $indexs = array();
    
    public function __construct() 
    {
         $this->index = 0;
         $this->values = array(
             'bookmark',
             'relation',
             'table_info',
             'table_coords',
             'designer_coords',
             'column_info',
             'pdf_pages',
             'history',
             'recent',
             'table_uiprefs',
             'tracking',
             'userconfig',
             'users',
             'usergroups',
         );
         $this->indexs = array(
             'bookmark' => 0,
             'relation' => 1,
             'table_info' => 2,
             'table_coords' => 3,
             'designer_coords' => 4,
             'column_info' => 5,
             'pdf_pages' => 6,
             'history' => 7,
             'recent' => 8,
             'table_uiprefs' => 9,
             'tracking' => 10,
             'userconfig' => 11,
             'users' => 12,
             'usergroups' => 13,
         );
    }
    
    function fetchRow($result) 
    {
        if ($this->index < count($this->values)) {
            $curr_table[0] = $this->values[$this->index];
            $this->index++;
            return $curr_table;
        }
        
        $this->index = 0;
        return false;
    }
    
    function query($sql, $link = null, $options = 0, $cache_affected_rows = true) 
    {
        if (stripos($sql, "column_info") !== false) {
            unset($this->values[$this->indexs['column_info']]);
        }
        
        if (stripos($sql, "table_info") !== false) {
            unset ($this->values[$this->indexs['table_info']]);
        }
        
        if (stripos($sql, "table_coords") !== false) {
            unset($this->values[$this->indexs['table_coords']]);
        }
        
        if (stripos($sql, "designer_coords") !== false) {
            unset($this->values[$this->indexs['designer_coords']]);
        }
        
        if (stripos($sql, "relation") !== false) {
            unset($this->values[$this->indexs['relation']]);
        }
        
        if (stripos($sql, "pdf_pages") !== false) {
            unset($GLOBALS [$this->indexs['pdf_pages']]);
        }
        
        if (stripos($sql, "bookmark") !== false) {
            unset($GLOBALS [$this->indexs['bookmark']]);
        }
    }
    
    public function tryQuery(
        $query, $link = null, $options = 0, $cache_affected_rows = true
    ) {
        return true;
    }
    
    public function selectDb($dbname, $link = null) 
    {
        return true;
    }
    
    public function freeResult($result)
    {
        return true;
    }
}
