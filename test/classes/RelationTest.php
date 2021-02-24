<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;

/**
 * @group medium
 */
class RelationTest extends AbstractTestCase
{
    /** @var Relation */
    private $relation;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::defineVersionConstants();
        parent::setTheme();
        $GLOBALS['server'] = 1;
        $GLOBALS['db'] = 'db';
        $GLOBALS['cfg']['Server']['user'] = 'root';
        $GLOBALS['cfg']['Server']['pmadb'] = 'phpmyadmin';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['ZeroConf'] = true;
        $_SESSION['relation'][$GLOBALS['server']] = 'PMA_relation';
        $_SESSION['relation'] = [];

        $GLOBALS['cfg']['ServerDefault'] = 0;

        $this->relation = new Relation($GLOBALS['dbi']);
    }

    /**
     * Test for queryAsControlUser
     */
    public function testPMAQueryAsControlUser(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $dbi->expects($this->once())
            ->method('query')
            ->will($this->returnValue('executeResult1'));

        $dbi->expects($this->once())
            ->method('tryQuery')
            ->will($this->returnValue('executeResult2'));

        $GLOBALS['dbi'] = $dbi;
        $this->relation->dbi = $GLOBALS['dbi'];

        $sql = 'insert into PMA_bookmark A,B values(1, 2)';
        $this->assertEquals(
            'executeResult1',
            $this->relation->queryAsControlUser($sql)
        );
        $this->assertEquals(
            'executeResult2',
            $this->relation->queryAsControlUser($sql, false)
        );
    }

    /**
     * Test for getRelationsParam & getRelationsParamDiagnostic
     */
    public function testPMAGetRelationsParam(): void
    {
        $relationsPara = $this->relation->getRelationsParam();
        $this->assertFalse(
            $relationsPara['relwork']
        );
        $this->assertFalse(
            $relationsPara['bookmarkwork']
        );
        $this->assertEquals(
            'root',
            $relationsPara['user']
        );
        $this->assertEquals(
            'phpmyadmin',
            $relationsPara['db']
        );

        $retval = $this->relation->getRelationsParamDiagnostic($relationsPara);
        //check $cfg['Servers'][$i]['pmadb']
        $this->assertStringContainsString(
            "\$cfg['Servers'][\$i]['pmadb']",
            $retval
        );
        $this->assertStringContainsString(
            '<strong>OK</strong>',
            $retval
        );

        //$cfg['Servers'][$i]['relation']
        $result = "\$cfg['Servers'][\$i]['pmadb']  ... </th><td class=\"right\">"
            . '<span class="success"><strong>OK</strong></span>';
        $this->assertStringContainsString(
            $result,
            $retval
        );
        // $cfg['Servers'][$i]['relation']
        $result = "\$cfg['Servers'][\$i]['relation']  ... </th><td class=\"right\">"
            . '<span class="caution"><strong>not OK</strong></span>';
        $this->assertStringContainsString(
            $result,
            $retval
        );
        // General relation features
        $result = 'General relation features: <span class="caution">Disabled</span>';
        $this->assertStringContainsString(
            $result,
            $retval
        );
        // $cfg['Servers'][$i]['table_info']
        $result = "\$cfg['Servers'][\$i]['table_info']  ... </th>"
            . '<td class="right">'
            . '<span class="caution"><strong>not OK</strong></span>';
        $this->assertStringContainsString(
            $result,
            $retval
        );
        // Display Features:
        $result = 'Display Features: <span class="caution">Disabled</span>';
        $this->assertStringContainsString(
            $result,
            $retval
        );

        $relationsPara['db'] = false;
        $retval = $this->relation->getRelationsParamDiagnostic($relationsPara);

        $result = __('General relation features');
        $this->assertStringContainsString(
            $result,
            $retval
        );
        $result = 'Configuration of pmadb… ';
        $this->assertStringContainsString(
            $result,
            $retval
        );
        $result = '<strong>not OK</strong>';
        $this->assertStringContainsString(
            $result,
            $retval
        );
    }

    /**
     * Test for getDisplayField
     */
    public function testPMAGetDisplayField(): void
    {
        $db = 'information_schema';
        $table = 'CHARACTER_SETS';
        $this->assertEquals(
            'DESCRIPTION',
            $this->relation->getDisplayField($db, $table)
        );

        $db = 'information_schema';
        $table = 'TABLES';
        $this->assertEquals(
            'TABLE_COMMENT',
            $this->relation->getDisplayField($db, $table)
        );

        $db = 'information_schema';
        $table = 'PMA';
        $this->assertFalse(
            $this->relation->getDisplayField($db, $table)
        );
    }

    /**
     * Test for getComments
     */
    public function testPMAGetComments(): void
    {
        $GLOBALS['cfg']['ServerDefault'] = 0;
        $_SESSION['relation'] = [];

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $getColumnsResult = [
            [
                'Field' => 'field1',
                'Type' => 'int(11)',
                'Comment' => 'Comment1',
            ],
            [
                'Field' => 'field2',
                'Type' => 'text',
                'Comment' => 'Comment1',
            ],
        ];
        $dbi->expects($this->any())->method('getColumns')
            ->will($this->returnValue($getColumnsResult));

        $GLOBALS['dbi'] = $dbi;
        $this->relation->dbi = $GLOBALS['dbi'];

        $db = 'information_schema';
        $this->assertEquals(
            [''],
            $this->relation->getComments($db)
        );

        $db = 'information_schema';
        $table = 'TABLES';
        $this->assertEquals(
            [
                'field1' => 'Comment1',
                'field2' => 'Comment1',
            ],
            $this->relation->getComments($db, $table)
        );
    }

    /**
     * Test for tryUpgradeTransformations
     */
    public function testPMATryUpgradeTransformations(): void
    {
        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();
        $dbi->expects($this->any())
            ->method('tryQuery')
            ->will($this->returnValue(true));
        $dbi->expects($this->any())
            ->method('numRows')
            ->will($this->returnValue(0));
        $dbi->expects($this->any())
            ->method('getError')
            ->will($this->onConsecutiveCalls(true, false));
        $GLOBALS['dbi'] = $dbi;
        $this->relation->dbi = $GLOBALS['dbi'];

        $GLOBALS['cfg']['Server']['pmadb'] = 'pmadb';
        $GLOBALS['cfg']['Server']['column_info'] = 'column_info';

        // Case 1
        $actual = $this->relation->tryUpgradeTransformations();
        $this->assertFalse(
            $actual
        );

        // Case 2
        $actual = $this->relation->tryUpgradeTransformations();
        $this->assertTrue(
            $actual
        );
    }

    /**
     * Test for searchColumnInForeigners
     */
    public function testPMASearchColumnInForeigners(): void
    {
        $foreigners = [
            'value' => [
                'master_field' => 'value',
                'foreign_db' => 'GSoC14',
                'foreign_table' => 'test',
                'foreign_field' => 'value',
            ],
            'foreign_keys_data' => [
                0 => [
                    'constraint' => 'ad',
                    'index_list' => [
                        'id',
                        'value',
                    ],
                    'ref_db_name' => 'GSoC14',
                    'ref_table_name' => 'table_1',
                    'ref_index_list' => [
                        'id',
                        'value',
                    ],
                    'on_delete' => 'CASCADE',
                    'on_update' => 'CASCADE',
                ],
            ],
        ];

        $foreigner = $this->relation->searchColumnInForeigners($foreigners, 'id');
        $expected = [];
        $expected['foreign_field'] = 'id';
        $expected['foreign_db'] = 'GSoC14';
        $expected['foreign_table'] = 'table_1';
        $expected['constraint'] = 'ad';
        $expected['on_delete'] = 'CASCADE';
        $expected['on_update'] = 'CASCADE';

        $this->assertEquals(
            $expected,
            $foreigner
        );
    }
}
