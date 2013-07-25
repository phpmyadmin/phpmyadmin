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
require_once 'libraries/relation_cleanup.lib.php';


class PMA_Relation_Cleanup_Test extends PHPUnit_Framework_TestCase
{
    public function setUp()
    {
        $this->initGlobalValue();
    }
    
    public function initGlobalValue()
    {
        $GLOBALS['column_info'] = 'column_info';
        $GLOBALS['table_info'] = 'table_info';
        $GLOBALS['table_coords'] = 'table_coords';
        $GLOBALS['designer_coords'] = 'designer_coords';
        $GLOBALS['bookmark'] = 'bookmark';
        $GLOBALS['pdf_pages'] = 'pdf_pages';
        $GLOBALS['relation'] = 'relation';
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
        $this->initGlobalValue();
        
        //the $cfgRelation value before cleanup column
        $cfgRelation = PMA_getRelationsParam();
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
        $cfgRelation = PMA_getRelationsParam();
        
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
        $this->initGlobalValue();
        
        //the $cfgRelation value before cleanup column
        $cfgRelation = PMA_getRelationsParam();
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
        $cfgRelation = PMA_getRelationsParam();
        
        $is_defined_column_info 
            = isset($cfgRelation['column_info'])? $cfgRelation['column_info'] : null;
        $is_defined_table_info 
            = isset($cfgRelation['table_info'])? $cfgRelation['table_info'] : null;
        $is_defined_relation
            = isset($cfgRelation['relation'])? $cfgRelation['relation'] : null;
        $is_defined_table_coords
            = isset($cfgRelation['table_coords'])? $cfgRelation['table_coords'] : null;
        $is_defined_designer_coords
            = isset($cfgRelation['designer_coords'])? $cfgRelation['designer_coords'] : null;
        
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
        $this->initGlobalValue();
        
        //the $cfgRelation value before cleanup column
        $cfgRelation = PMA_getRelationsParam();
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
        $cfgRelation = PMA_getRelationsParam();
        
        $is_defined_column_info 
            = isset($cfgRelation['column_info'])? $cfgRelation['column_info'] : null;
        $is_defined_table_info 
            = isset($cfgRelation['table_info'])? $cfgRelation['table_info'] : null;
        $is_defined_relation
            = isset($cfgRelation['relation'])? $cfgRelation['relation'] : null;
        $is_defined_table_coords
            = isset($cfgRelation['table_coords'])? $cfgRelation['table_coords'] : null;
        $is_defined_designer_coords
            = isset($cfgRelation['designer_coords'])? $cfgRelation['designer_coords'] : null;
        $is_defined_designer_bookmark
            = isset($cfgRelation['bookmark'])? $cfgRelation['bookmark'] : null;
        $is_defined_designer_pdf_pages
            = isset($cfgRelation['pdf_pages'])? $cfgRelation['pdf_pages'] : null;
        
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
        $this->assertEquals(
            null,
            $is_defined_designer_bookmark
        );
        $this->assertEquals(
            null,
            $is_defined_designer_pdf_pages
        );        
    }
}

//Mock global functions
function PMA_getRelationsParam()
{
    $cfgRelation = array();
    
    //Common value
    $cfgRelation['db'] = "PMA";
    $cfgRelation['commwork'] = true;
    $cfgRelation['displaywork'] = true;
    $cfgRelation['relwork'] = true;
    $cfgRelation['pdfwork'] = true;
    $cfgRelation['designerwork'] = true;
    $cfgRelation['bookmarkwork'] = true;

    if (isset($GLOBALS['column_info'])) {
        $cfgRelation['column_info'] = 'column_info';
    }
    
    if (isset($GLOBALS['table_info'])) {
        $cfgRelation['table_info'] = 'table_info';
    }
    
    if (isset($GLOBALS['table_coords'])) {
        $cfgRelation['table_coords'] = 'table_coords';
    }
    
    if (isset($GLOBALS['designer_coords'])) {
        $cfgRelation['designer_coords'] = 'designer_coords';
    }
    
    if (isset($GLOBALS['relation'])) {
        $cfgRelation['relation'] = 'relation';
    }
    
    if (isset($GLOBALS['pdf_pages'])) { 
        $cfgRelation['pdf_pages'] = 'pdf_pages';
    }
    
    if (isset($GLOBALS['bookmark'])) {
        $cfgRelation['bookmark'] = 'bookmark';
    }
    
    return $cfgRelation;
}

function PMA_queryAsControlUser($sql)
{
    if (stripos($sql, "column_info")!==false) {
        unset($GLOBALS['column_info']);
    }
    
    if (stripos($sql, "table_info")!==false) {
        unset($GLOBALS['table_info']);
    }
    
    if (stripos($sql, "table_coords")!==false) {
        unset($GLOBALS['table_coords']);
    }
    
    if (stripos($sql, "designer_coords")!==false) {
        unset($GLOBALS['designer_coords']);
    }
    
    if (stripos($sql, "relation")!==false) {
        unset($GLOBALS['relation']);
    }
    
    if (stripos($sql, "pdf_pages")!==false) {
        unset($GLOBALS['pdf_pages']);
    }
    
    if (stripos($sql, "bookmark")!==false) {
        unset($GLOBALS['bookmark']);
    }
}
