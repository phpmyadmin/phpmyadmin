<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use Generator;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Table\RelationController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseStub;

/** @covers \PhpMyAdmin\Controllers\Table\RelationController */
class RelationControllerTest extends AbstractTestCase
{
    private ResponseStub $response;

    private Template $template;

    /**
     * Configures environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        parent::setTheme();

        $GLOBALS['server'] = 0;
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        //$_SESSION

        $_POST['foreignDb'] = 'db';
        $_POST['foreignTable'] = 'table';

        $dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;

        $this->response = new ResponseStub();
        $this->template = new Template();
    }

    /**
     * Tests for getDropdownValueForTableAction()
     *
     * Case one: this case is for the situation when the target
     *           table is a view.
     */
    public function testGetDropdownValueForTableActionIsView(): void
    {
        $viewColumns = ['viewCol', 'viewCol2', 'viewCol3'];
        $tableMock = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        // Test the situation when the table is a view
        $tableMock->expects($this->any())->method('isView')
            ->will($this->returnValue(true));
        $tableMock->expects($this->any())->method('getColumns')
            ->will($this->returnValue($viewColumns));

        $GLOBALS['dbi']->expects($this->any())->method('getTable')
            ->will($this->returnValue($tableMock));

        $ctrl = new RelationController(
            $this->response,
            $this->template,
            new Relation($GLOBALS['dbi']),
            $GLOBALS['dbi'],
        );

        $ctrl->getDropdownValueForTable();
        $json = $this->response->getJSONResult();
        $this->assertEquals($viewColumns, $json['columns']);
    }

    /**
     * Tests for getDropdownValueForTableAction()
     *
     * Case one: this case is for the situation when the target
     *           table is not a view (real tabletable).
     */
    public function testGetDropdownValueForTableActionNotView(): void
    {
        $indexedColumns = ['primaryTableCol'];
        $tableMock = $this->getMockBuilder(Table::class)
            ->disableOriginalConstructor()
            ->getMock();
        // Test the situation when the table is a view
        $tableMock->expects($this->any())->method('isView')
            ->will($this->returnValue(false));
        $tableMock->expects($this->any())->method('getIndexedColumns')
            ->will($this->returnValue($indexedColumns));

        $GLOBALS['dbi']->expects($this->any())->method('getTable')
            ->will($this->returnValue($tableMock));

        $ctrl = new RelationController(
            $this->response,
            $this->template,
            new Relation($GLOBALS['dbi']),
            $GLOBALS['dbi'],
        );

        $ctrl->getDropdownValueForTable();
        $json = $this->response->getJSONResult();
        $this->assertEquals($indexedColumns, $json['columns']);
    }

    /**
     * Tests for getDropdownValueForDbAction()
     *
     * Case one: foreign
     */
    public function testGetDropdownValueForDbActionOne(): void
    {
        $resultStub = $this->createMock(DummyResult::class);

        $GLOBALS['dbi']->expects($this->exactly(1))
            ->method('query')
            ->will($this->returnValue($resultStub));

        $resultStub->expects($this->any())
            ->method('getIterator')
            ->will($this->returnCallback(static function (): Generator {
                yield from [['Engine' => 'InnoDB', 'Name' => 'table']];
            }));

        $ctrl = new RelationController(
            $this->response,
            $this->template,
            new Relation($GLOBALS['dbi']),
            $GLOBALS['dbi'],
        );

        $_POST['foreign'] = 'true';
        $ctrl->getDropdownValueForDatabase('INNODB');
        $json = $this->response->getJSONResult();
        $this->assertEquals(
            ['table'],
            $json['tables'],
        );
    }

    /**
     * Tests for getDropdownValueForDbAction()
     *
     * Case two: not foreign
     */
    public function testGetDropdownValueForDbActionTwo(): void
    {
        $resultStub = $this->createMock(DummyResult::class);

        $GLOBALS['dbi']->expects($this->exactly(1))
            ->method('query')
            ->will($this->returnValue($resultStub));

        $resultStub->expects($this->any())
            ->method('fetchAllColumn')
            ->will($this->returnValue(['table']));

        $ctrl = new RelationController(
            $this->response,
            $this->template,
            new Relation($GLOBALS['dbi']),
            $GLOBALS['dbi'],
        );

        $_POST['foreign'] = 'false';
        $ctrl->getDropdownValueForDatabase('INNODB');
        $json = $this->response->getJSONResult();
        $this->assertEquals(
            ['table'],
            $json['tables'],
        );
    }
}
