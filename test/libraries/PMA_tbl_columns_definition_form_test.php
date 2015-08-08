<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for libraries/tbl_columns_definition_form.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/Util.class.php';
require_once 'libraries/DatabaseInterface.class.php';
require_once 'libraries/Partition.class.php';
require_once 'libraries/Types.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/transformations.lib.php';
require_once 'libraries/mysql_charsets.inc.php';
require_once 'libraries/StorageEngine.class.php';

/**
 * Tests for libraries/tbl_columns_definition_form.lib.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_TblColumnsDefinitionFormTest extends PHPUnit_Framework_TestCase
{
    /**
     * SetUp function for test cases
     *
     * @return void
     */
    public function setUp()
    {
        /**
         * $GLOBALS['cfg']['ServerDefault'] = 1;
         * $GLOBALS['cfg']['DBG'] = null;
         * $GLOBALS['pmaThemeImage'] = 'image';
         *
         * $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
         * $_SESSION['PMA_Theme'] = new PMA_Theme();
         */
    }

    /**
     * Test for PMA_getHeaderCells
     *
     * @return void
     */
    public function testGetHeaderCells()
    {
        /**
         * @todo Test against table_fields_definition.phtml
         * $GLOBALS['cfg']['BrowseMIME'] = true;
         * $GLOBALS['cfg']['ShowHint'] = false;
         * $result = PMA_getHeaderCells(false, array(), true);
         *
         * $this->assertContains(
         *     'Index',
         *     $result
         * );
         *
         * $this->assertContains(
         *     'Move column',
         *     $result
         * );
         *
         * $this->assertContains(
         *     'MIME type',
         *     $result
         * );
         */
    }

    /**
     * Test for PMA_getHtmlForColumnName
     *
     * @return void
     */
    public function testGetHtmlForColumnName()
    {
        /**
         * @todo  Create test for page
         */
    }

    /**
     * Test for PMA_getHtmlForColumnType
     *
     * @return void
     */
    public function testGetHtmlForColumnType()
    {
        /**
         * @todo Find out a better method to test for HTML
         *
         * $GLOBALS['PMA_Types'] = new PMA_Types;
         * $result = PMA_getHtmlForColumnType(
         *     1, 4, 3, false, array('column_status' => array('isReferenced' => false,
         *     'isForeignKey' => false, 'isEditable' => true))
         * );
         *
         * $this->assertContains(
         *     '<select class="column_type" name="field_type[1]" id="field_1_1">',
         *     $result
         * );
         *
         * $this->assertContains(
         *     '<option title="">INT</option>',
         *     $result
         * );
         */
    }

    /**
     * Test for PMA_getHtmlForTransformationOption
     *
     * @return void
     */
    public function testGetHtmlForTransformationOption()
    {
        /**
         * @todo Find out a better method to test for HTML
         *
         * $cmeta = array(
         *     'Field' => 'fieldname'
         * );
         *
         * $mime = array(
         *     'fieldname' => array(
         *         'transformation_options' => 'transops'
         *     )
         * );
         *
         * $result = PMA_getHtmlForTransformationOption(
         *     2, 4, 4, $cmeta, $mime, ''
         * );
         *
         * $this->assertContains(
         *     '<input id="field_2_0" type="text" name="field_transformation_'
         *     . 'options[2]" size="16" class="textfield" value="transops" />',
         *     $result
         * );
         */
        $this->markTestIncomplete('Not yet implemented!');
    }

    /**
     * Test for PMA_getHtmlForTransformation
     *
     * @return void
     */
    public function testGetHtmlForTransformation()
    {
        /**
         * @todo Find out a better method to test for HTML
         *
         * $cmeta = array(
         *     'Field' => 'fieldname'
         * );
         *
         * $mime = array(
         *     'fieldname' => array(
         *         'transformation' => 'Text_Plain_Preappend.class.php',
         *         'transformation_options' => 'transops'
         *     )
         * );
         *
         * $avail_mime = array(
         *     'transformation' => array(
         *         'foo' => 'text/plain: bar'
         *     ),
         *     'transformation_file' => array(
         *         'foo' => 'Text_Plain_Preappend.class.php'
         *     )
         * );
         * $result = PMA_getHtmlForTransformation(
         * 2, 0, 0, $avail_mime, $cmeta, $mime, ''
         * );
         *
         * $this->assertContains(
         *     '<select id="field_2_0" size="1" name="field_transformation[2]">',
         *     $result
         * );
         *
         * $this->assertContains(
         *     'selected ',
         *     $result
         * );
         */
    }

    /**
     * Test for PMA_getHtmlForMoveColumn
     *
     * @return void
     */
    public function testGetHtmlForMoveColumn()
    {
        /**
         * @todo Find out a better method to test for HTML
         *
         * $cmeta = array(
         *     'Field' => 'fieldname'
         * );
         *
         * $moveColumns = array();
         *
         * $temp = new stdClass;
         * $temp->name = 'a';
         * $moveColumns[] = $temp;
         *
         * $temp = new stdClass;
         * $temp->name = 'fieldname';
         * $moveColumns[] = $temp;
         *
         * $result = PMA_getHtmlForMoveColumn(
         *     2, 0, 0, $moveColumns, $cmeta
         * );
         *
         * $this->assertContains(
         *     '<select id="field_2_0" name="field_move_to[2]" size="1" width="5em">',
         *     $result
         * );
         *
         * $this->assertContains(
         *     '<option value="" selected="selected">&nbsp;</option>',
         *     $result
         * );
         */

        /**
         * @todo Find out a better method to test for HTML
         *
         * $this->assertContains(
         *     '<option value="a" disabled="disabled">after `a`</option>',
         *     $result
         * );
         *
         * $this->assertContains(
         *     '<option value="fieldname" disabled="disabled">after `fieldname`</option>',
         *     $result
         * );
         */
    }

    /**
     * Test for PMA_getHtmlForColumnComment
     *
     * @return void
     */
    public function testGetHtmlForColumnComment()
    {
        /**
         * @todo Find out a better method to test for HTML
         *
         * $cmeta = array(
         *     'Field' => 'fieldname'
         * );
         *
         * $commentMeta = array(
         *     'fieldname' => 'fieldnamecomment<'
         * );
         *
         * $result = PMA_getHtmlForColumnComment(
         *     2, 1, 0, $cmeta, $commentMeta
         * );
         *
         * $this->assertContains(
         *     '<input id="field_2_1" type="text" name="field_comments[2]" '
         *     . 'size="12" maxlength="1024" value="fieldnamecomment&lt;" '
         *     . 'class="textfield" />',
         *     $result
         * );
         */
        $this->markTestIncomplete('Not yet implemented!');
    }

    /**
     * Test for PMA_getHtmlForColumnAutoIncrement
     *
     * @return void
     */
    public function testGetHtmlForColumnAutoIncrement()
    {
        /**
         * @todo Find out a better method to test for HTML
         *
         * $cmeta = array(
         *     'Extra' => 'auto_increment'
         * );
         *
         * $result = PMA_getHtmlForColumnAutoIncrement(
         *     2, 1, 0, $cmeta
         * );
         *
         * $this->assertContains(
         *     '<input name="field_extra[2]" id="field_2_1" checked="checked" '
         *     . 'type="checkbox" value="AUTO_INCREMENT" />',
         *     $result
         * );
         */
        $this->markTestIncomplete('Not yet implemented!');
    }

    /**
     * Test for PMA_getHtmlForColumnIndexes
     *
     * @return void
     */
    public function testGetHtmlForColumnIndexes()
    {
        /**
         * @todo Find out a better method to test for HTML
         *
         * $this->assertContains(
         *     '<select name="field_key[2]" id="field_2_1"',
         *     $result
         * );
         *
         *
         * $this->assertContains(
         *     '<option value="none_2">---</option>',
         *     $result
         * );
         *
         * $this->assertContains(
         *     '<option value="primary_2" title="Primary">PRIMARY</option>',
         *     $result
         * );
         *
         * $this->assertContains(
         *     '<option value="unique_2" title="Unique">UNIQUE</option>',
         *     $result
         * );
         *
         * $this->assertContains(
         *     '<option value="index_2" title="Index">INDEX</option>',
         *     $result
         * );
         */
    }

    /**
     * Test for PMA_getHtmlForColumnNull
     *
     * @return void
     */
    public function testGetHtmlForColumnNull()
    {
        /**
         * @todo Find out a better method to test for HTML
         *
         * $cmeta = array(
         *     'Null' => 'YES'
         * );
         *
         * $result = PMA_getHtmlForColumnNull(
         *     2, 3, 1, $cmeta
         * );
         *
         * $this->assertContains(
         *     '<input name="field_null[2]" id="field_2_2" checked="checked" '
         *     . 'type="checkbox" value="NULL" class="allow_null"/>',
         *     $result
         * );
         */
        $this->markTestIncomplete('Not yet implemented!');
    }

    /**
     * Test for PMA_getHtmlForColumnAttribute
     *
     * @return void
     */
    public function testGetHtmlForColumnAttribute()
    {
        /**
         * @todo Find out a better method to test for HTML
         *
         * $cmeta = array(
         *     'Null' => 'YES',
         *     'Extra' => 'on update CURRENT_TIMESTAMP',
         *     'Field' => 'f'
         * );
         *
         * $colspec = array('attribute' => 'attr');
         *
         * $analyzed_sql = array(
         *     array(
         *         'create_table_fields' => array(
         *             'f' => array(
         *                 'default_current_timestamp' => true,
         *             )
         *         )
         *     )
         * );
         *
         * $types = $this->getMockBuilder('PMA_Types')
         *     ->disableOriginalConstructor()
         *     ->setMethods(array('getAttributes'))
         *     ->getMock();
         *
         * $types->expects($this->once())
         *     ->method('getAttributes')
         *     ->will(
         *         $this->returnValue(
         *             array('on update CURRENT_TIMESTAMP')
         *         )
         *     );
         *
         * $GLOBALS['PMA_Types'] = $types;
         * $result = PMA_getHtmlForColumnAttribute(
         *     2, 3, 1, $colspec, $cmeta, true, $analyzed_sql
         * );
         *
         * $this->assertContains(
         *     '<select style="width: 7em;" name="field_attribute[2]" id="field_2_2">',
         *     $result
         * );
         *
         * $this->assertContains(
         *     '<option value="on update CURRENT_TIMESTAMP">',
         *     $result
         * );
         */
        $this->markTestIncomplete('Not yet implemented!');
    }

    /**
     * Test for PMA_getHtmlForColumnLength
     *
     * @return void
     */
    public function testGetHtmlForColumnLength()
    {
        /**
         * @todo Find out a better method to test for HTML
         * Template: columns_definitions/column_length
         *
         * $this->assertContains(
         *     '<input id="field_2_2" type="text" name="field_length[2]" size="10" '
         *     . 'value="8" class="textfield" />',
         *     $result
         * );
         *
         * $this->assertContains(
         *     '<p class="enum_notice" id="enum_notice_2_2">',
         *     $result
         * );
        */
    }

    /**
     * Test for PMA_getHtmlForColumnDefault
     *
     * @return void
     */
    public function testGetHtmlForColumnDefault()
    {
        /**
         * @todo Find out a better method to test for HTML
         *
         * $cmeta = array(
         *     'Default' => 'YES',
         *     'DefaultType' => 'NONE',
         *     'DefaultValue' => '2222'
         * );
         *
         * $result = PMA_getHtmlForColumnDefault(
         *     2, 3, 1, 'TIMESTAMP', true, $cmeta
         * );
         *
         * $this->assertContains(
         *     '<select name="field_default_type[2]" id="field_2_2" '
         *     . 'class="default_type">',
         *     $result
         * );
         *
         * $this->assertContains(
         *     '<option value="NONE" selected="selected" >',
         *     $result
         * );
         *
         * $this->assertContains(
         *     '<input type="text" name="field_default_value[2]" size="12" '
         *     . 'value="2222" class="textfield default_value" />',
         *     $result
         * );
         */
        $this->markTestIncomplete('Not yet implemented!');
    }

    /**
     * Test for PMA_getFormParamsForOldColumn
     *
     * @return void
     */
    public function testGetFormParamsForOldColumn()
    {
        // Function needs correction
        $this->markTestIncomplete('Not yet implemented!');
    }
}
