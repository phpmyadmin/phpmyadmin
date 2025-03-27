<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\ListDatabase;

/**
 * @covers \PhpMyAdmin\ListDatabase<extended>
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
     */
    public function testEmpty(): void
    {
        $arr = new ListDatabase();
        self::assertSame('', $arr->getEmpty());
    }

    /**
     * Test for ListDatabase::exists
     */
    public function testExists(): void
    {
        $arr = new ListDatabase();
        self::assertTrue($arr->exists('single_db'));
    }

    public function testGetList(): void
    {
        $arr = new ListDatabase();

        $GLOBALS['db'] = 'db';
        self::assertSame([
            [
                'name' => 'single_db',
                'is_selected' => false,
            ],
        ], $arr->getList());

        $GLOBALS['db'] = 'single_db';
        self::assertSame([
            [
                'name' => 'single_db',
                'is_selected' => true,
            ],
        ], $arr->getList());
    }

    /**
     * Test for checkHideDatabase
     */
    public function testCheckHideDatabase(): void
    {
        $GLOBALS['cfg']['Server']['hide_db'] = 'single\\_db';
        self::assertEquals($this->callFunction(
            $this->object,
            ListDatabase::class,
            'checkHideDatabase',
            []
        ), '');
    }

    /**
     * Test for getDefault
     */
    public function testGetDefault(): void
    {
        $GLOBALS['db'] = '';
        self::assertSame($this->object->getDefault(), '');

        $GLOBALS['db'] = 'mysql';
        self::assertSame($this->object->getDefault(), 'mysql');
    }
}
