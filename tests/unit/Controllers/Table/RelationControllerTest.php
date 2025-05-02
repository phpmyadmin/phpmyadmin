<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use Generator;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Table\RelationController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Table\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DummyResult;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseStub;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(RelationController::class)]
class RelationControllerTest extends AbstractTestCase
{
    private ResponseStub $response;

    private Template $template;

    private DatabaseInterface&MockObject $dbi;

    /**
     * Configures environment
     */
    protected function setUp(): void
    {
        parent::setUp();

        Current::$database = 'db';
        Current::$table = 'table';
        Config::getInstance()->selectedServer['DisableIS'] = false;
        //$_SESSION

        $_POST['foreignDb'] = 'db';
        $_POST['foreignTable'] = 'table';

        $this->dbi = $this->getMockBuilder(DatabaseInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

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
        $tableMock->expects(self::any())->method('isView')
            ->willReturn(true);
        $tableMock->expects(self::any())->method('getColumns')
            ->willReturn($viewColumns);

        $this->dbi->expects(self::any())->method('getTable')
            ->willReturn($tableMock);

        $ctrl = new RelationController(
            $this->response,
            $this->template,
            new Relation($this->dbi),
            $this->dbi,
            Config::getInstance(),
        );

        $ctrl->getDropdownValueForTable();
        $json = $this->response->getJSONResult();
        self::assertSame($viewColumns, $json['columns']);
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
        $tableMock->expects(self::any())->method('isView')
            ->willReturn(false);
        $tableMock->expects(self::any())->method('getIndexedColumns')
            ->willReturn($indexedColumns);

        $this->dbi->expects(self::any())->method('getTable')
            ->willReturn($tableMock);

        $ctrl = new RelationController(
            $this->response,
            $this->template,
            new Relation($this->dbi),
            $this->dbi,
            Config::getInstance(),
        );

        $ctrl->getDropdownValueForTable();
        $json = $this->response->getJSONResult();
        self::assertSame($indexedColumns, $json['columns']);
    }

    /**
     * Tests for getDropdownValueForDbAction()
     *
     * Case one: foreign
     */
    public function testGetDropdownValueForDbActionOne(): void
    {
        $resultStub = $this->createMock(DummyResult::class);

        $this->dbi->expects(self::exactly(1))
            ->method('query')
            ->willReturn($resultStub);

        $resultStub->expects(self::any())
            ->method('getIterator')
            ->willReturnCallback(static function (): Generator {
                yield from [['Engine' => 'InnoDB', 'Name' => 'table']];
            });

        $ctrl = new RelationController(
            $this->response,
            $this->template,
            new Relation($this->dbi),
            $this->dbi,
            Config::getInstance(),
        );

        $_POST['foreign'] = 'true';
        $ctrl->getDropdownValueForDatabase('INNODB');
        $json = $this->response->getJSONResult();
        self::assertSame(
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

        $this->dbi->expects(self::exactly(1))
            ->method('query')
            ->willReturn($resultStub);

        $resultStub->expects(self::any())
            ->method('fetchAllColumn')
            ->willReturn(['table']);

        $ctrl = new RelationController(
            $this->response,
            $this->template,
            new Relation($this->dbi),
            $this->dbi,
            Config::getInstance(),
        );

        $_POST['foreign'] = 'false';
        $ctrl->getDropdownValueForDatabase('INNODB');
        $json = $this->response->getJSONResult();
        self::assertSame(
            ['table'],
            $json['tables'],
        );
    }
}
