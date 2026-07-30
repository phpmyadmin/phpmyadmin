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
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\Attributes\CoversClass;
use stdClass;

/**
 * @covers \PhpMyAdmin\Controllers\Table\RelationController
 */
#[CoversClass(RelationController::class)]
#[AllowMockObjectsWithoutExpectations]
class RelationControllerTest extends AbstractTestCase
{
    /** @var ResponseStub */
    private $response;

    /** @var Template */
    private $template;

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
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        //$_SESSION

        $_POST['foreignDb'] = 'db';
        $_POST['foreignTable'] = 'table';

        $GLOBALS['dblist'] = new stdClass();
        $GLOBALS['dblist']->databases = new class
        {
            /**
             * @param mixed $name name
             */
            public function exists($name): bool
            {
                return true;
            }
        };

        $dbi = $this->createMock(DatabaseInterface::class);

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
        $viewColumns = [
            'viewCol',
            'viewCol2',
            'viewCol3',
        ];
        $tableMock = $this->createMock(Table::class);
        // Test the situation when the table is a view
        $tableMock->method('isView')->willReturn(true);
        $tableMock->method('getColumns')->willReturn($viewColumns);

        $GLOBALS['dbi']->method('getTable')->willReturn($tableMock);

        $ctrl = new RelationController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $GLOBALS['table'],
            new Relation($GLOBALS['dbi']),
            $GLOBALS['dbi']
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

        $GLOBALS['dbi']->method('getTable')->willReturn($tableMock);

        $ctrl = new RelationController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $GLOBALS['table'],
            new Relation($GLOBALS['dbi']),
            $GLOBALS['dbi']
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

        $GLOBALS['dbi']->expects($this->exactly(1))
            ->method('query')
            ->willReturn($resultStub);

        $resultStub->method('getIterator')
            ->willReturnCallback(static function (): Generator {
                yield from [
                    [
                        'Engine' => 'InnoDB',
                        'Name' => 'table',
                    ],
                ];
            });

        $ctrl = new RelationController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $GLOBALS['table'],
            new Relation($GLOBALS['dbi']),
            $GLOBALS['dbi']
        );

        $_POST['foreign'] = 'true';
        $ctrl->getDropdownValueForDatabase('INNODB');
        $json = $this->response->getJSONResult();
        self::assertSame(['table'], $json['tables']);
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
            ->willReturn($resultStub);

        $resultStub->method('fetchAllColumn')->willReturn(['table']);

        $ctrl = new RelationController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $GLOBALS['table'],
            new Relation($GLOBALS['dbi']),
            $GLOBALS['dbi']
        );

        $_POST['foreign'] = 'false';
        $ctrl->getDropdownValueForDatabase('INNODB');
        $json = $this->response->getJSONResult();
        self::assertSame(['table'], $json['tables']);
    }
}
