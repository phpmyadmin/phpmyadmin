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
require_once 'libraries/tbl_columns_definition_form.lib.php';
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
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['cfg']['DBG'] = null;
        $GLOBALS['cfg']['TextareaCols'] = 10;
        $GLOBALS['cfg']['TextareaRows'] = 15;
        $GLOBALS['pmaThemeImage'] = 'image';

        $_SESSION['PMA_Theme'] = PMA_Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new PMA_Theme();
    }

    /**
     * Test for PMA_getFormsParameters
     *
     * @return void
     */
    public function testGetFormsParameters()
    {
        // case 1
        $_REQUEST['after_field'] = "affield";
        $_REQUEST['field_where'] = "fwhere";

        $result = PMA_getFormsParameters(
            "dbname", "tablename", "tbl_create.php", 22, array(12, 13)
        );

        $this->assertEquals(
            array(
                'db' => 'dbname',
                'reload' => 1,
                'orig_num_fields' => 22,
                'orig_field_where' => 'fwhere',
                'orig_after_field' => 'affield',
                'selected[0]' => 12,
                'selected[1]' => 13
            ),
            $result
        );

        // case 2
        $result = PMA_getFormsParameters(
            "dbname", "tablename", "tbl_addfield.php", null, 1
        );

        $this->assertEquals(
            array(
                'db' => 'dbname',
                'table' => 'tablename',
                'orig_field_where' => 'fwhere',
                'orig_after_field' => 'affield',
                'field_where' => 'fwhere',
                'after_field' => 'affield'
            ),
            $result
        );

        // case 3
        $_REQUEST['after_field'] = null;
        $_REQUEST['field_where'] = null;

        $result = PMA_getFormsParameters(
            "dbname", "tablename", null, 0, null
        );

        $this->assertEquals(
            array(
                'db' => 'dbname',
                'table' => 'tablename',
                'orig_num_fields' => 0
            ),
            $result
        );
    }

    /**
     * Test for PMA_getHtmlForTableConfigurations
     *
     * @return void
     */
    public function testGetHtmlForTableConfigurations()
    {
        $_REQUEST['comment'] = 'c&d';
        $_REQUEST['tbl_storage_engine'] = 'engine';
        $_REQUEST['tbl_collation'] = 'latin1_swedish_ci';
        $_REQUEST['partition_definition'] = "partition>";

        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())
            ->method('fetchResult')
            ->will(
                $this->returnValue(
                    array(
                        array(
                            'Name' => 'partition',
                            'Support' => 'NO'
                        )
                    )
                )
            );

        $GLOBALS['dbi'] = $dbi;

        $result = PMA_getHtmlForTableConfigurations();

        $this->assertContains(
            '<input type="text" name="comment" size="40" maxlength="2048" '
                . 'value="c&amp;d" class="textfield"',
            $result
        );

        $this->assertContains(
            '<select name="tbl_storage_engine">',
            $result
        );

        $this->assertContains(
            '<select lang="en" dir="ltr" name="tbl_collation">',
            $result
        );

        $this->assertContains(
            '<option value="utf8_bin" title="Unicode (multilingual), Binary">'
                . 'utf8_bin</option>',
            $result
        );

        $this->assertContains(
            '<textarea name="partition_definition" id="partitiondefinition" '
                . 'cols="10" rows="15" dir="text_dir">partition&gt;</textarea>',
            $result
        );
    }

    /**
     * Test for PMA_getHtmlForFooter
     *
     * @return void
     */
    public function testGetHtmlForFooter()
    {
        $result = PMA_getHtmlForFooter();

        $this->assertContains(
            '<input type="submit" name="do_save_data" value="Save" />',
            $result
        );

        $this->assertContains(
            '<div id="properties_message">',
            $result
        );

        $this->assertContains(
            '<div id="popup_background">',
            $result
        );
    }

    /**
     * Test for PMA_getHtmlForTableNameAndNoOfColumns
     *
     * @return void
     */
    public function testGetHtmlForTableNameAndNoOfColumns()
    {
        $_REQUEST['table'] = "tablename";

        $result = PMA_getHtmlForTableNameAndNoOfColumns();

        $this->assertContains(
            '<input type="text" name="table" size="40" maxlength="80" '
                . 'value="tablename" class="textfield" ',
            $result
        );

        $this->assertContains(
            '<input type="text" id="added_fields" name="added_fields" '
                . 'size="2" value="1" onfocus="this.select()"',
            $result
        );

        $this->assertContains(
            '<input type="button" name="submit_num_fields"value="Go"',
            $result
        );
    }

    /**
     * Test for PMA_getHtmlForTableFieldDefinitions
     *
     * @return void
     */
    public function testGetHtmlForTableFieldDefinitions()
    {
        $header_cells = array('h1', 'h2');
        $content_cells = array(
            array('a', 'b'),
            array('c'),
            2
        );
        $result = PMA_getHtmlForTableFieldDefinitions($header_cells, $content_cells);

        $this->assertContains(
            '<th>h1</th>',
            $result
        );

        $this->assertContains(
            '<th>h2</th>',
            $result
        );

        $this->assertContains(
            '<td class="center">a</td>',
            $result
        );

        $this->assertContains(
            '<td class="center">b</td>',
            $result
        );

        $this->assertContains(
            '<td class="center">c</td>',
            $result
        );
    }

    /**
     * Test for PMA_getHtmlForTableCreateOrAddField
     *
     * @return void
     */
    public function testGetHtmlForTableCreateOrAddField()
    {
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->any())
            ->method('fetchResult')
            ->will(
                $this->returnValue(
                    array()
                )
            );

        $GLOBALS['dbi'] = $dbi;
        $result = PMA_getHtmlForTableCreateOrAddField(
            "tbl_create.php",
            array('a' => 'b'),
            array(array('c1')),
            array('h1')
        );

        $this->assertContains(
            '<form method="post" action="tbl_create.php" '
                . 'class="create_table_form ajax lock-page">',
            $result
        );

        $this->assertContains(
            '<input type="hidden" name="a" value="b"',
            $result
        );

        $this->assertContains(
            '<select lang="en" dir="ltr" name="tbl_collation">',
            $result
        );

        $this->assertContains(
            '<input type="submit" name="do_save_data" value="Save"',
            $result
        );

        $this->assertContains(
            '<input type="text" name="table"',
            $result
        );
    }

    /**
     * Test for PMA_getHeaderCells
     *
     * @return void
     */
    public function testGetHeaderCells()
    {
        $GLOBALS['cfg']['BrowseMIME'] = true;
        $GLOBALS['cfg']['ShowHint'] = false;
        $result = PMA_getHeaderCells(false, array(), true, 'db', 'table');

        $this->assertContains(
            'Index',
            $result
        );

        $this->assertContains(
            'Move column',
            $result
        );

        $this->assertContains(
            'MIME type',
            $result
        );
    }

    /**
     * Test for PMA_getMoveColumns
     *
     * @return void
     */
    public function testGetMoveColumns()
    {
        $dbi = $this->getMockBuilder('PMA_DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('tryQuery')
            ->with("SELECT * FROM `db`.`table` LIMIT 1")
            ->will($this->returnValue('v1'));

        $dbi->expects($this->once())
            ->method('getFieldsMeta')
            ->with("v1")
            ->will($this->returnValue('movecols'));

        $GLOBALS['dbi'] = $dbi;

        $this->assertEquals(
            PMA_getMoveColumns('db', 'table'),
            'movecols'
        );
    }

    /**
     * Test for PMA_getRowDataForRegeneration
     *
     * @return void
     */
    public function testGetRowDataForRegeneration()
    {
        $_REQUEST = array(
            'field_name' => array(1 => 'name'),
            'field_type' => array(1 => 'type'),
            'field_collation' => array(1 => 'colltn'),
            'field_null' => array(1 => true),
            'field_key' => array(1 => "fulltext_1"),
            'field_default_type' => array(1 => 'USER_DEFINED'),
            'field_default_value' => array(1 => 'DEF'),
            'field_extra' => array(1 => 'extra')
        );

        $submit_fulltext = array(1 => 1);

        $result = PMA_getRowDataForRegeneration(1, $submit_fulltext);

        $this->assertEquals(
            array(
                'Field' => 'name',
                'Type' => 'type',
                'Collation' => 'colltn',
                'Null' => true,
                'Key' => 'FULLTEXT',
                'DefaultType' => 'USER_DEFINED',
                'DefaultValue' => 'DEF',
                'Default' => 'DEF',
                'Extra' => 'extra',
                'Comment' => 'FULLTEXT'
            ),
            $result
        );
    }

    /**
     * Test for PMA_getSubmitPropertiesForRegeneration
     *
     * @return void
     */
    public function testGetSubmitPropertiesForRegeneration()
    {
        $_REQUEST = array(
            'field_length' => array(1 => 22),
            'field_attribute' => array(1 => 'attr'),
            'field_default_current_timestamp' => array()
        );

        $result = PMA_getSubmitPropertiesForRegeneration(1);

        $this->assertEquals(
            array(22, 'attr', false),
            $result
        );

    }

    /**
     * Test for PMA_getColumnMetaForDefault
     *
     * @return void
     */
    public function testHandleRegeneration()
    {
        $_REQUEST = array(
            'field_comments' => array(1 => 'comm'),
            'field_mimetype' => array(1 => 'mime'),
            'field_transformation' => array(1 => 'trans'),
            'field_transformation_options' => array(1 => 'transops')
        );

        $result = PMA_handleRegeneration(1, 'FULLTEXT', array(), array());

        $this->assertEquals(
            array('comm'),
            $result[4]
        );

        $this->assertEquals(
            array(
                array(
                    'mimetype' => 'mime',
                    'transformation' => 'trans',
                    'transformation_options' => 'transops'
                )
            ),
            $result[5]
        );
    }

    /**
     * Test for PMA_getColumnMetaForDefault
     *
     * @return void
     */
    public function testGetColumnMetaForDefault()
    {
        $cmeta = array(
            'Default' => null,
            'Null' => 'YES',
            'DefaultType' => 'a',
            'DefaultValue' => 'b',
        );

        $result = PMA_getColumnMetaForDefault($cmeta, null);

        $this->assertEquals(
            'NULL',
            $result['DefaultType']
        );

        $this->assertEquals(
            '',
            $result['DefaultValue']
        );

        // case 2
        $cmeta = array(
            'Default' => null,
            'Null' => 'NO',
            'DefaultType' => 'a',
            'DefaultValue' => 'b',
        );

        $result = PMA_getColumnMetaForDefault($cmeta, true);

        $this->assertEquals(
            'USER_DEFINED',
            $result['DefaultType']
        );

        $this->assertEquals(
            null,
            $result['DefaultValue']
        );

        // case 3
        $cmeta = array(
            'Default' => null,
            'Null' => 'NO',
            'DefaultType' => 'a',
            'DefaultValue' => 'b',
        );

        $result = PMA_getColumnMetaForDefault($cmeta, false);

        $this->assertEquals(
            'NONE',
            $result['DefaultType']
        );

        $this->assertEquals(
            null,
            $result['DefaultValue']
        );

        // case 4
        $cmeta = array(
            'Default' => 'CURRENT_TIMESTAMP',
            'Null' => 'NO',
            'DefaultType' => 'a',
            'DefaultValue' => 'b',
        );

        $result = PMA_getColumnMetaForDefault($cmeta, false);

        $this->assertEquals(
            'CURRENT_TIMESTAMP',
            $result['DefaultType']
        );

        $this->assertEquals(
            null,
            $result['DefaultValue']
        );

        // case 5
        $cmeta = array(
            'Default' => 'SOMETHING_ELSE',
            'Null' => 'NO',
            'DefaultType' => 'a',
            'DefaultValue' => 'b',
        );

        $result = PMA_getColumnMetaForDefault($cmeta, false);

        $this->assertEquals(
            'USER_DEFINED',
            $result['DefaultType']
        );

        $this->assertEquals(
            'SOMETHING_ELSE',
            $result['DefaultValue']
        );
    }

    /**
     * Test for PMA_getHtmlForColumnName
     *
     * @return void
     */
    public function testGetHtmlForColumnName()
    {
        $cfgRelation = array('central_columnswork' => true);
        $result = PMA_getHtmlForColumnName(
            2, 4, 4, array('Field' => "fieldname",
            'column_status' => array('isReferenced' => false,
            'isForeignKey' => false, 'isEditable' => true)), $cfgRelation
        );

        $this->assertContains(
            '<input id="field_2_0" type="text" name="field_name[2]" '
                . 'maxlength="64" class="textfield" title="Column" size="10" '
                . 'value="fieldname" />',
            $result
        );
    }

    /**
     * Test for PMA_getHtmlForColumnType
     *
     * @return void
     */
    public function testGetHtmlForColumnType()
    {
        $GLOBALS['PMA_Types'] = new PMA_Types;
        $result = PMA_getHtmlForColumnType(
            1, 4, 3, false, array('column_status' => array('isReferenced' => false,
            'isForeignKey' => false, 'isEditable' => true))
        );

        $this->assertContains(
            '<select class="column_type" name="field_type[1]" id="field_1_1">',
            $result
        );

        $this->assertContains(
            '<option title="">INT</option>',
            $result
        );
    }

    /**
     * Test for PMA_getHtmlForTransformationOption
     *
     * @return void
     */
    public function testGetHtmlForTransformationOption()
    {
        $cmeta = array(
            'Field' => 'fieldname'
        );

        $mime = array(
            'fieldname' => array(
                'transformation_options' => 'transops'
            )
        );

        $result = PMA_getHtmlForTransformationOption(
            2, 4, 4, $cmeta, $mime, ''
        );

        $this->assertContains(
            '<input id="field_2_0" type="text" name="field_transformation_'
                . 'options[2]" size="16" class="textfield" value="transops" />',
            $result
        );
    }

    /**
     * Test for PMA_getHtmlForTransformation
     *
     * @return void
     */
    public function testGetHtmlForTransformation()
    {
        $cmeta = array(
            'Field' => 'fieldname'
        );

        $mime = array(
            'fieldname' => array(
                'transformation' => 'Text_Plain_Preappend.class.php',
                'transformation_options' => 'transops'
            )
        );

        $avail_mime = array(
            'transformation' => array(
                'foo' => 'bar'
            ),
            'transformation_file' => array(
                'foo' => 'Text_Plain_Preappend.class.php'
            )
        );
        $result = PMA_getHtmlForTransformation(
            2, 0, 0, $avail_mime, $cmeta, $mime, ''
        );

        $this->assertContains(
            '<select id="field_2_0" size="1" name="field_transformation[2]">',
            $result
        );

        $this->assertContains(
            'selected ',
            $result
        );
    }

    /**
     * Test for PMA_getHtmlForMoveColumn
     *
     * @return void
     */
    public function testGetHtmlForMoveColumn()
    {
        $cmeta = array(
            'Field' => 'fieldname'
        );

        $moveColumns = array();

        $temp = new stdClass;
        $temp->name = 'a';
        $moveColumns[] = $temp;

        $temp = new stdClass;
        $temp->name = 'fieldname';
        $moveColumns[] = $temp;

        $result = PMA_getHtmlForMoveColumn(
            2, 0, 0, $moveColumns, $cmeta
        );

        $this->assertContains(
            '<select id="field_2_0" name="field_move_to[2]" size="1" '
                . 'width="5em">',
            $result
        );

        $this->assertContains(
            '<option value="" selected="selected">&nbsp;</option>',
            $result
        );

        $this->assertContains(
            '<option value="a" disabled="disabled">after `a`</option>',
            $result
        );

        $this->assertContains(
            '<option value="fieldname" disabled="disabled">after `fieldname`</option>',
            $result
        );
    }

    /**
     * Test for PMA_getHtmlForColumnComment
     *
     * @return void
     */
    public function testGetHtmlForColumnComment()
    {
        $cmeta = array(
            'Field' => 'fieldname'
        );

        $commentMeta = array(
            'fieldname' => 'fieldnamecomment<'
        );

        $result = PMA_getHtmlForColumnComment(
            2, 1, 0, $cmeta, $commentMeta
        );

        $this->assertContains(
            '<input id="field_2_1" type="text" name="field_comments[2]" '
                . 'size="12" value="fieldnamecomment&lt;" class="textfield" />',
            $result
        );
    }

    /**
     * Test for PMA_getHtmlForColumnAutoIncrement
     *
     * @return void
     */
    public function testGetHtmlForColumnAutoIncrement()
    {
        $cmeta = array(
            'Extra' => 'auto_increment'
        );

        $result = PMA_getHtmlForColumnAutoIncrement(
            2, 1, 0, $cmeta
        );

        $this->assertContains(
            '<input name="field_extra[2]" id="field_2_1" checked="checked" '
                . 'type="checkbox" value="AUTO_INCREMENT" />',
            $result
        );
    }

    /**
     * Test for PMA_getHtmlForColumnIndexes
     *
     * @return void
     */
    public function testGetHtmlForColumnIndexes()
    {
        $cmeta = array(
            'Extra' => 'auto_increment',
            'Field' => 'fieldname'
        );

        $result = PMA_getHtmlForColumnIndexes(
            2, 1, 0, $cmeta
        );

        $this->assertContains(
            '<select name="field_key[2]" id="field_2_1"',
            $result
        );

        $this->assertContains(
            '<option value="none_2">---</option>',
            $result
        );

        $this->assertContains(
            '<option value="primary_2" title="Primary">PRIMARY</option>',
            $result
        );

        $this->assertContains(
            '<option value="unique_2" title="Unique">UNIQUE</option>',
            $result
        );

        $this->assertContains(
            '<option value="index_2" title="Index">INDEX</option>',
            $result
        );
    }

    /**
     * Test for PMA_getHtmlForIndexTypeOption
     *
     * @return void
     */
    public function testGetHtmlForIndexTypeOption()
    {
        $cmeta = array(
            'Key' => 'PRI'
        );

        $result = PMA_getHtmlForIndexTypeOption(
            2, $cmeta, 'INT', 'PRI'
        );

        $this->assertContains(
            '<option value="int_2" title="INT" selected="selected">INT</option>',
            $result
        );
    }

    /**
     * Test for PMA_getHtmlForColumnNull
     *
     * @return void
     */
    public function testGetHtmlForColumnNull()
    {
        $cmeta = array(
            'Null' => 'YES'
        );

        $result = PMA_getHtmlForColumnNull(
            2, 3, 1, $cmeta
        );

        $this->assertContains(
            '<input name="field_null[2]" id="field_2_2" checked="checked" '
                . 'type="checkbox" value="NULL" class="allow_null"/>',
            $result
        );
    }

    /**
     * Test for PMA_getHtmlForColumnAttribute
     *
     * @return void
     */
    public function testGetHtmlForColumnAttribute()
    {
        $cmeta = array(
            'Null' => 'YES',
            'Extra' => 'on update CURRENT_TIMESTAMP',
            'Field' => 'f'
        );

        $colspec = array('attribute' => 'attr');

        $analyzed_sql = array(
            array(
                'create_table_fields' => array(
                    'f' => array(
                        'default_current_timestamp' => true,
                    )
                )
            )
        );

        $types = $this->getMockBuilder('PMA_Types')
            ->disableOriginalConstructor()
            ->setMethods(array('getAttributes'))
            ->getMock();

        $types->expects($this->once())
            ->method('getAttributes')
            ->will(
                $this->returnValue(
                    array('on update CURRENT_TIMESTAMP')
                )
            );

        $GLOBALS['PMA_Types'] = $types;
        $result = PMA_getHtmlForColumnAttribute(
            2, 3, 1, $colspec, $cmeta, true, $analyzed_sql
        );

        $this->assertContains(
            '<select style="width: 7em;" name="field_attribute[2]" '
                . 'id="field_2_2">',
            $result
        );

        $this->assertContains(
            '<option value="on update CURRENT_TIMESTAMP">',
            $result
        );
    }

    /**
     * Test for PMA_getHtmlForColumnCollation
     *
     * @return void
     */
    public function testGetHtmlForColumnCollation()
    {
        $cmeta = array(
            'Collation' => 'utf8_general_ci'
        );

        $result = PMA_getHtmlForColumnCollation(
            2, 3, 1, $cmeta
        );

        $this->assertContains(
            '<select lang="en" dir="ltr" name="field_collation[2]" id="field_2_2">',
            $result
        );

        $this->assertContains(
            '<option value="utf8_bin" title="Unicode (multilingual), Binary">',
            $result
        );
    }

    /**
     * Test for PMA_getHtmlForColumnLength
     *
     * @return void
     */
    public function testGetHtmlForColumnLength()
    {
        $result = PMA_getHtmlForColumnLength(
            2, 3, 1, 10, 8
        );

        $this->assertContains(
            '<input id="field_2_2" type="text" name="field_length[2]" size="10" '
                . 'value="8" class="textfield" />',
            $result
        );

        $this->assertContains(
            '<p class="enum_notice" id="enum_notice_2_2">',
            $result
        );
    }

    /**
     * Test for PMA_getHtmlForColumnDefault
     *
     * @return void
     */
    public function testGetHtmlForColumnDefault()
    {
        $cmeta = array(
            'Default' => 'YES',
            'DefaultType' => 'NONE',
            'DefaultValue' => '2222'
        );

        $result = PMA_getHtmlForColumnDefault(
            2, 3, 1, 'TIMESTAMP', true, $cmeta
        );

        $this->assertContains(
            '<select name="field_default_type[2]" id="field_2_2" '
                . 'class="default_type">',
            $result
        );

        $this->assertContains(
            '<option value="NONE" selected="selected" >',
            $result
        );

        $this->assertContains(
            '<input type="text" name="field_default_value[2]" size="12" '
                . 'value="2222" class="textfield default_value" />',
            $result
        );
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
?>
