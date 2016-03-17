<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for libraries/controllers/TableRelationController.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
use PMA\libraries\di\Container;
use PMA\libraries\Theme;

require_once 'libraries/url_generating.lib.php';
require_once 'libraries/database_interface.inc.php';
require_once 'libraries/relation.lib.php';
require_once 'test/libraries/stubs/ResponseStub.php';
require_once 'test/PMATestCase.php';

/**
 * Tests for libraries/controllers/TableRelationController.php
 *
 * @package PhpMyAdmin-test
 */
class TableRelationControllerTest extends PMATestCase
{
    /**
     * @var \PMA\Test\Stubs\Response
     */
    private $_response;

    /**
     * Configures environment
     *
     * @return void
     */
    protected function setUp()
    {
        $GLOBALS['server'] = 0;
        $GLOBALS['pmaThemeImage'] = 'theme/';
        //$_SESSION
        $_SESSION['PMA_Theme'] = Theme::load('./themes/pmahomme');
        $_SESSION['PMA_Theme'] = new Theme();

        $_REQUEST['foreignDb'] = 'db';
        $_REQUEST['foreignTable'] = 'table';

        $GLOBALS['dblist'] = new DataBasePMAMockForTblRelation();
        $GLOBALS['dblist']->databases = new DataBaseMockForTblRelation();

        $dbi = $this->getMockBuilder('PMA\libraries\DatabaseInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $GLOBALS['dbi'] = $dbi;

        $container = Container::getDefaultContainer();
        $container->set('db', 'db');
        $container->set('table', 'table');
        $container->set('dbi', $GLOBALS['dbi']);
        $this->_response = new \PMA\Test\Stubs\Response();
        $container->set('PMA\libraries\Response', $this->_response);
        $container->alias('response', 'PMA\libraries\Response');
    }

    /**
     * Tests for getDropdownValueForTableAction()
     *
     * Case one: this case is for the situation when the target
     *           table is a view.
     *
     * @return void
     * @test
     */
    public function testGetDropdownValueForTableActionIsView()
    {
        $viewColumns = array(
            'viewCol', 'viewCol2', 'viewCol3'
        );
        $tableMock = $this->getMockBuilder('PMA\libraries\Table')
            ->disableOriginalConstructor()
            ->getMock();
        // Test the situation when the table is a view
        $tableMock->expects($this->any())->method('isView')
            ->will($this->returnValue(true));
        $tableMock->expects($this->any())->method('getColumns')
            ->will($this->returnValue($viewColumns));

        $GLOBALS['dbi']->expects($this->any())->method('getTable')
            ->will($this->returnValue($tableMock));

        $container = Container::getDefaultContainer();
        $container->set('dbi', $GLOBALS['dbi']);
        $container->factory('PMA\libraries\controllers\table\TableRelationController');
        $container->alias(
            'TableRelationController',
            'PMA\libraries\controllers\table\TableRelationController'
        );
        /**
         * @var PMA\libraries\controllers\table\TableRelationController
         */
        $ctrl = $container->get('TableRelationController');

        $ctrl->getDropdownValueForTableAction();
        $json = $this->_response->getJSONResult();
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
     *
     * @return void
     * @test
     */
    public function testGetDropdownValueForTableActionNotView()
    {
        $indexedColumns = array(
            'primaryTableCol'
        );
        $tableMock = $this->getMockBuilder('PMA\libraries\Table')
            ->disableOriginalConstructor()
            ->getMock();
        // Test the situation when the table is a view
        $tableMock->expects($this->any())->method('isView')
            ->will($this->returnValue(false));
        $tableMock->expects($this->any())->method('getIndexedColumns')
            ->will($this->returnValue($indexedColumns));

        $GLOBALS['dbi']->expects($this->any())->method('getTable')
            ->will($this->returnValue($tableMock));

        $container = Container::getDefaultContainer();
        $container->set('dbi', $GLOBALS['dbi']);
        $container->factory('PMA\libraries\controllers\table\TableRelationController');
        $container->alias(
            'TableRelationController',
            'PMA\libraries\controllers\table\TableRelationController'
        );
        $ctrl = $container->get('TableRelationController');

        $ctrl->getDropdownValueForTableAction();
        $json = $this->_response->getJSONResult();
        $this->assertEquals(
            $indexedColumns,
            $json['columns']
        );
    }

    /**
     * Tests for getDropdownValueForDbAction()
     *
     * Case one: foreign
     *
     * @return void
     * @test
     */
    public function testGetDropdownValueForDbActionOne()
    {
        $GLOBALS['dbi']->expects($this->any())
            ->method('fetchArray')
            ->will(
                $this->returnCallback(
                    function () {
                        static $count = 0;
                        if ($count == 0) {
                            $count++;
                            return array('Engine' => 'InnoDB', 'Name'   => 'table',);
                        }
                        return null;
                    }
                )
            );

        $container = Container::getDefaultContainer();
        $container->set('dbi', $GLOBALS['dbi']);
        $container->factory('PMA\libraries\controllers\table\TableRelationController');
        $container->alias(
            'TableRelationController',
            'PMA\libraries\controllers\table\TableRelationController'
        );
        $ctrl = $container->get(
            'TableRelationController',
            array('tbl_storage_engine' => 'INNODB')
        );

        $_REQUEST['foreign'] = 'true';
        $ctrl->getDropdownValueForDbAction();
        $json = $this->_response->getJSONResult();
        $this->assertEquals(
            array('table'),
            $json['tables']
        );
    }

    /**
     * Tests for getDropdownValueForDbAction()
     *
     * Case two: not foreign
     *
     * @return void
     * @test
     */
    public function testGetDropdownValueForDbActionTwo()
    {
        $GLOBALS['dbi']->expects($this->any())
            ->method('fetchArray')
            ->will(
                $this->returnCallback(
                    function () {
                        static $count = 0;
                        if ($count == 0) {
                            $count++;
                            return array('table');
                        }
                        return null;
                    }
                )
            );

        $container = Container::getDefaultContainer();
        $container->set('dbi', $GLOBALS['dbi']);
        $container->factory('PMA\libraries\controllers\table\TableRelationController');
        $container->alias(
            'TableRelationController',
            'PMA\libraries\controllers\table\TableRelationController'
        );
        $ctrl = $container->get(
            'TableRelationController',
            array('tbl_storage_engine' => 'INNODB',)
        );

        $_REQUEST['foreign'] = 'false';
        $ctrl->getDropdownValueForDbAction();
        $json = $this->_response->getJSONResult();
        $this->assertEquals(
            array('table'),
            $json['tables']
        );
    }
}

/**
 * Mock class for DataBasePMAMock
 *
 * @package PhpMyAdmin-test
 */
Class DataBasePMAMockForTblRelation
{
    var $databases;
}

/**
 * Mock class for DataBaseMock
 *
 * @package PhpMyAdmin-test
 */
Class DataBaseMockForTblRelation
{
    /**
     * mock function to return table is existed
     *
     * @param string $name table name
     *
     * @return bool
     */
    function exists($name)
    {
        return true;
    }
}
