<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

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
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\MockObject\MockObject;

#[CoversClass(RelationController::class)]
#[AllowMockObjectsWithoutExpectations]
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
        $config = Config::getInstance();
        $config->selectedServer['DisableIS'] = false;
        //$_SESSION

        $_POST['foreignDb'] = 'db';
        $_POST['foreignTable'] = 'table';

        $this->dbi = $this->createMock(DatabaseInterface::class);

        $this->response = new ResponseStub();
        $this->template = new Template($config);
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
        $tableMock = $this->createMock(Table::class);
        // Test the situation when the table is a view
        $tableMock->method('isView')->willReturn(true);
        $tableMock->method('getColumns')->willReturn($viewColumns);

        $this->dbi->method('getTable')->willReturn($tableMock);

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
        $tableMock = $this->createMock(Table::class);
        // Test the situation when the table is a view
        $tableMock->method('isView')->willReturn(false);
        $tableMock->method('getIndexedColumns')->willReturn($indexedColumns);

        $this->dbi->method('getTable')->willReturn($tableMock);

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

        $resultStub->method('fetchAllColumn')->willReturn(['table']);

        $ctrl = new RelationController(
            $this->response,
            $this->template,
            new Relation($this->dbi),
            $this->dbi,
            Config::getInstance(),
        );

        $ctrl->getDropdownValueForDatabase('INNODB', 'db', 'true');
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
        $this->dbi->expects(self::exactly(1))
            ->method('getTables')
            ->willReturn(['table']);

        $ctrl = new RelationController(
            $this->response,
            $this->template,
            new Relation($this->dbi),
            $this->dbi,
            Config::getInstance(),
        );

        $ctrl->getDropdownValueForDatabase('INNODB', 'db', 'false');
        $json = $this->response->getJSONResult();
        self::assertSame(
            ['table'],
            $json['tables'],
        );
    }
}
