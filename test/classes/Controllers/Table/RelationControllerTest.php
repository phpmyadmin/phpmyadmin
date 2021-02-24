<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table;

use PhpMyAdmin\Controllers\Table\RelationController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Table;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\Response as ResponseStub;
use stdClass;

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
        parent::defineVersionConstants();
        parent::loadDefaultConfig();
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
        $viewColumns = [
            'viewCol',
            'viewCol2',
            'viewCol3',
        ];
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
            $GLOBALS['db'],
            $GLOBALS['table'],
            new Relation($GLOBALS['dbi'], $this->template),
            $GLOBALS['dbi']
        );

        $ctrl->getDropdownValueForTable();
        $json = $this->response->getJSONResult();
        $this->assertEquals(
            $viewColumns,
            $json['columns']
        );
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
            $GLOBALS['db'],
            $GLOBALS['table'],
            new Relation($GLOBALS['dbi'], $this->template),
            $GLOBALS['dbi']
        );

        $ctrl->getDropdownValueForTable();
        $json = $this->response->getJSONResult();
        $this->assertEquals(
            $indexedColumns,
            $json['columns']
        );
    }

    /**
     * Tests for getDropdownValueForDbAction()
     *
     * Case one: foreign
     */
    public function testGetDropdownValueForDbActionOne(): void
    {
        $GLOBALS['dbi']->expects($this->any())
            ->method('fetchArray')
            ->will(
                $this->returnCallback(
                    static function () {
                        static $count = 0;
                        if ($count == 0) {
                            $count++;

                            return [
                                'Engine' => 'InnoDB',
                                'Name' => 'table',
                            ];
                        }

                        return null;
                    }
                )
            );

        $ctrl = new RelationController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $GLOBALS['table'],
            new Relation($GLOBALS['dbi'], $this->template),
            $GLOBALS['dbi']
        );

        $_POST['foreign'] = 'true';
        $ctrl->getDropdownValueForDatabase('INNODB');
        $json = $this->response->getJSONResult();
        $this->assertEquals(
            ['table'],
            $json['tables']
        );
    }

    /**
     * Tests for getDropdownValueForDbAction()
     *
     * Case two: not foreign
     */
    public function testGetDropdownValueForDbActionTwo(): void
    {
        $GLOBALS['dbi']->expects($this->any())
            ->method('fetchArray')
            ->will(
                $this->returnCallback(
                    static function () {
                        static $count = 0;
                        if ($count == 0) {
                            $count++;

                            return ['table'];
                        }

                        return null;
                    }
                )
            );

        $ctrl = new RelationController(
            $this->response,
            $this->template,
            $GLOBALS['db'],
            $GLOBALS['table'],
            new Relation($GLOBALS['dbi'], $this->template),
            $GLOBALS['dbi']
        );

        $_POST['foreign'] = 'false';
        $ctrl->getDropdownValueForDatabase('INNODB');
        $json = $this->response->getJSONResult();
        $this->assertEquals(
            ['table'],
            $json['tables']
        );
    }
}
