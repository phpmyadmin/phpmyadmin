<?php
/**
 * tests for ListDatabase class
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\ListDatabase;

/**
 * tests for ListDatabase class
 */
class ListDatabaseTest extends AbstractTestCase
{
    /**
     * ListDatabase instance
     *
     * @var ListDatabase
     */
    private $object;

    /**
     * SetUp for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['Server']['only_db'] = ['single\\_db'];
        $this->object = new ListDatabase();
    }

    /**
     * Test for ListDatabase::getEmpty
     *
     * @return void
     */
    public function testEmpty()
    {
        $arr = new ListDatabase();
        $this->assertEquals('', $arr->getEmpty());
    }

    /**
     * Test for ListDatabase::exists
     *
     * @return void
     */
    public function testExists()
    {
        $arr = new ListDatabase();
        $this->assertEquals(true, $arr->exists('single_db'));
    }

    public function testGetList(): void
    {
        $arr = new ListDatabase();

        $GLOBALS['db'] = 'db';
        $this->assertEquals(
            [
                [
                    'name' => 'single_db',
                    'is_selected' => false,
                ],
            ],
            $arr->getList()
        );

        $GLOBALS['db'] = 'single_db';
        $this->assertEquals(
            [
                [
                    'name' => 'single_db',
                    'is_selected' => true,
                ],
            ],
            $arr->getList()
        );
    }

    /**
     * Test for checkHideDatabase
     *
     * @return void
     */
    public function testCheckHideDatabase()
    {
        $GLOBALS['cfg']['Server']['hide_db'] = 'single\\_db';
        $this->assertEquals(
            $this->callFunction(
                $this->object,
                ListDatabase::class,
                'checkHideDatabase',
                []
            ),
            ''
        );
    }

    /**
     * Test for getDefault
     *
     * @return void
     */
    public function testGetDefault()
    {
        $GLOBALS['db'] = '';
        $this->assertEquals(
            $this->object->getDefault(),
            ''
        );

        $GLOBALS['db'] = 'mysql';
        $this->assertEquals(
            $this->object->getDefault(),
            'mysql'
        );
    }
}
