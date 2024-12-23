<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Schema;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Plugins\Schema\Eps\EpsRelationSchema;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Medium;

#[CoversClass(EpsRelationSchema::class)]
#[Medium]
class EpsRelationSchemaTest extends AbstractTestCase
{
    protected EpsRelationSchema $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;
        $_REQUEST['page_number'] = 33;
        $_REQUEST['eps_show_color'] = true;
        $_REQUEST['eps_show_keys'] = true;
        $_REQUEST['eps_orientation'] = 'orientation';
        $_REQUEST['eps_show_table_dimension'] = true;
        $_REQUEST['eps_all_tables_same_width'] = true;
        $_REQUEST['t_v'] = [1 => '1'];
        $_REQUEST['t_h'] = [1 => '1'];
        $_REQUEST['t_x'] = [1 => '10'];
        $_REQUEST['t_y'] = [1 => '10'];
        $_POST['t_db'] = ['test_db'];
        $_POST['t_tbl'] = ['test_table'];

        Current::$database = 'test_db';
        Config::getInstance()->selectedServer['DisableIS'] = true;

        $this->object = new EpsRelationSchema(new Relation($dbi), DatabaseName::from('test_db'));
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        parent::tearDown();

        unset($this->object);
    }

    /**
     * Test for construct
     */
    public function testConstructor(): void
    {
        self::assertSame(33, $this->object->getPageNumber());
        self::assertTrue($this->object->isShowColor());
        self::assertTrue($this->object->isShowKeys());
        self::assertTrue($this->object->isTableDimension());
        self::assertTrue($this->object->isAllTableSameWidth());
        self::assertSame('L', $this->object->getOrientation());
    }

    /**
     * Test for setPageNumber
     */
    public function testSetPageNumber(): void
    {
        $this->object->setPageNumber(33);
        self::assertSame(33, $this->object->getPageNumber());
    }
}
