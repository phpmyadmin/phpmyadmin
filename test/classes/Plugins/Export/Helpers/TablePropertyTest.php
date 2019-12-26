<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for PhpMyAdmin\Plugins\Export\Helpers\TableProperty class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export\Helpers;

use PhpMyAdmin\Plugins\Export\Helpers\TableProperty;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * tests for PhpMyAdmin\Plugins\Export\Helpers\TableProperty class
 *
 * @package PhpMyAdmin-test
 */
class TablePropertyTest extends PmaTestCase
{
    protected $object;

    /**
     * Configures global environment.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $GLOBALS['server'] = 0;
        $row = [
            ' name ',
            'int ',
            true,
            ' PRI',
            '0',
            'mysql',
        ];
        $this->object = new TableProperty($row);
    }

    /**
     * tearDown for test cases
     *
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->object);
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\Helpers\TableProperty::__construct
     *
     * @return void
     */
    public function testConstructor()
    {
        $this->assertEquals(
            'name',
            $this->object->name
        );

        $this->assertEquals(
            'int',
            $this->object->type
        );

        $this->assertEquals(
            1,
            $this->object->nullable
        );

        $this->assertEquals(
            'PRI',
            $this->object->key
        );

        $this->assertEquals(
            '0',
            $this->object->defaultValue
        );

        $this->assertEquals(
            'mysql',
            $this->object->ext
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\Helpers\TableProperty::getPureType
     *
     * @return void
     */
    public function testGetPureType()
    {
        $this->object->type = "int(10)";

        $this->assertEquals(
            "int",
            $this->object->getPureType()
        );

        $this->object->type = "char";

        $this->assertEquals(
            "char",
            $this->object->getPureType()
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\Helpers\TableProperty::isNotNull
     *
     * @param string $nullable nullable value
     * @param string $expected expected output
     *
     * @return void
     * @dataProvider isNotNullProvider
     */
    public function testIsNotNull($nullable, $expected): void
    {
        $this->object->nullable = $nullable;

        $this->assertEquals(
            $expected,
            $this->object->isNotNull()
        );
    }

    /**
     * Data provider for testIsNotNull
     *
     * @return array Test Data
     */
    public function isNotNullProvider()
    {
        return [
            [
                "NO",
                "true",
            ],
            [
                "",
                "false",
            ],
            [
                "no",
                "false",
            ],
        ];
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\Helpers\TableProperty::isUnique
     *
     * @param string $key      key value
     * @param string $expected expected output
     *
     * @return void
     * @dataProvider isUniqueProvider
     */
    public function testIsUnique($key, $expected): void
    {
        $this->object->key = $key;

        $this->assertEquals(
            $expected,
            $this->object->isUnique()
        );
    }

    /**
     * Data provider for testIsUnique
     *
     * @return array Test Data
     */
    public function isUniqueProvider()
    {
        return [
            [
                "PRI",
                "true",
            ],
            [
                "UNI",
                "true",
            ],
            [
                "",
                "false",
            ],
            [
                "pri",
                "false",
            ],
            [
                "uni",
                "false",
            ],
        ];
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\Helpers\TableProperty::getDotNetPrimitiveType
     *
     * @param string $type     type value
     * @param string $expected expected output
     *
     * @return void
     * @dataProvider getDotNetPrimitiveTypeProvider
     */
    public function testGetDotNetPrimitiveType($type, $expected): void
    {
        $this->object->type = $type;

        $this->assertEquals(
            $expected,
            $this->object->getDotNetPrimitiveType()
        );
    }

    /**
     * Data provider for testGetDotNetPrimitiveType
     *
     * @return array Test Data
     */
    public function getDotNetPrimitiveTypeProvider()
    {
        return [
            [
                "int",
                "int",
            ],
            [
                "long",
                "long",
            ],
            [
                "char",
                "string",
            ],
            [
                "varchar",
                "string",
            ],
            [
                "text",
                "string",
            ],
            [
                "longtext",
                "string",
            ],
            [
                "tinyint",
                "bool",
            ],
            [
                "datetime",
                "DateTime",
            ],
            [
                "",
                "unknown",
            ],
            [
                "dummy",
                "unknown",
            ],
            [
                "INT",
                "unknown",
            ],
        ];
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\Helpers\TableProperty::getDotNetObjectType
     *
     * @param string $type     type value
     * @param string $expected expected output
     *
     * @return void
     * @dataProvider getDotNetObjectTypeProvider
     */
    public function testGetDotNetObjectType($type, $expected): void
    {
        $this->object->type = $type;

        $this->assertEquals(
            $expected,
            $this->object->getDotNetObjectType()
        );
    }

    /**
     * Data provider for testGetDotNetObjectType
     *
     * @return array Test Data
     */
    public function getDotNetObjectTypeProvider()
    {
        return [
            [
                "int",
                "Int32",
            ],
            [
                "long",
                "Long",
            ],
            [
                "char",
                "String",
            ],
            [
                "varchar",
                "String",
            ],
            [
                "text",
                "String",
            ],
            [
                "longtext",
                "String",
            ],
            [
                "tinyint",
                "Boolean",
            ],
            [
                "datetime",
                "DateTime",
            ],
            [
                "",
                "Unknown",
            ],
            [
                "dummy",
                "Unknown",
            ],
            [
                "INT",
                "Unknown",
            ],
        ];
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\Helpers\TableProperty::getIndexName
     *
     * @return void
     */
    public function testGetIndexName()
    {
        $this->object->name = "ä'7<ab>";
        $this->object->key = "PRI";

        $this->assertEquals(
            "index=\"ä'7&lt;ab&gt;\"",
            $this->object->getIndexName()
        );

        $this->object->key = "";

        $this->assertEquals(
            "",
            $this->object->getIndexName()
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\Helpers\TableProperty::isPK
     *
     * @return void
     */
    public function testIsPK()
    {
        $this->object->key = "PRI";

        $this->assertTrue(
            $this->object->isPK()
        );

        $this->object->key = "";

        $this->assertFalse(
            $this->object->isPK()
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\Helpers\TableProperty::formatCs
     *
     * @return void
     */
    public function testFormatCs()
    {
        $this->object->name = 'Name#name#123';

        $this->assertEquals(
            'text123Namename',
            $this->object->formatCs("text123#name#")
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\Helpers\TableProperty::formatXml
     *
     * @return void
     */
    public function testFormatXml()
    {
        $this->object->name = '"a\'';

        $this->assertEquals(
            '&quot;a\'index="&quot;a\'"',
            $this->object->formatXml("#name##indexName#")
        );
    }

    /**
     * Test for PhpMyAdmin\Plugins\Export\Helpers\TableProperty::format
     *
     * @return void
     */
    public function testFormat()
    {
        $this->assertEquals(
            'NameintInt32intfalsetrue',
            $this->object->format(
                "#ucfirstName##dotNetPrimitiveType##dotNetObjectType##type#" .
                "#notNull##unique#"
            )
        );
    }
}
