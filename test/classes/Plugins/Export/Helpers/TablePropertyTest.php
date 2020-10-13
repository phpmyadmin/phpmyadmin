<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export\Helpers;

use PhpMyAdmin\Plugins\Export\Helpers\TableProperty;
use PhpMyAdmin\Tests\AbstractTestCase;

class TablePropertyTest extends AbstractTestCase
{
    /** @var TableProperty */
    protected $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
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
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    public function testConstructor(): void
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

    public function testGetPureType(): void
    {
        $this->object->type = 'int(10)';

        $this->assertEquals(
            'int',
            $this->object->getPureType()
        );

        $this->object->type = 'char';

        $this->assertEquals(
            'char',
            $this->object->getPureType()
        );
    }

    /**
     * @param string $nullable nullable value
     * @param string $expected expected output
     *
     * @dataProvider isNotNullProvider
     */
    public function testIsNotNull(string $nullable, string $expected): void
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
    public function isNotNullProvider(): array
    {
        return [
            [
                'NO',
                'true',
            ],
            [
                '',
                'false',
            ],
            [
                'no',
                'false',
            ],
        ];
    }

    /**
     * @param string $key      key value
     * @param string $expected expected output
     *
     * @dataProvider isUniqueProvider
     */
    public function testIsUnique(string $key, string $expected): void
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
    public function isUniqueProvider(): array
    {
        return [
            [
                'PRI',
                'true',
            ],
            [
                'UNI',
                'true',
            ],
            [
                '',
                'false',
            ],
            [
                'pri',
                'false',
            ],
            [
                'uni',
                'false',
            ],
        ];
    }

    /**
     * @param string $type     type value
     * @param string $expected expected output
     *
     * @dataProvider getDotNetPrimitiveTypeProvider
     */
    public function testGetDotNetPrimitiveType(string $type, string $expected): void
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
    public function getDotNetPrimitiveTypeProvider(): array
    {
        return [
            [
                'int',
                'int',
            ],
            [
                'long',
                'long',
            ],
            [
                'char',
                'string',
            ],
            [
                'varchar',
                'string',
            ],
            [
                'text',
                'string',
            ],
            [
                'longtext',
                'string',
            ],
            [
                'tinyint',
                'bool',
            ],
            [
                'datetime',
                'DateTime',
            ],
            [
                '',
                'unknown',
            ],
            [
                'dummy',
                'unknown',
            ],
            [
                'INT',
                'unknown',
            ],
        ];
    }

    /**
     * @param string $type     type value
     * @param string $expected expected output
     *
     * @dataProvider getDotNetObjectTypeProvider
     */
    public function testGetDotNetObjectType(string $type, string $expected): void
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
    public function getDotNetObjectTypeProvider(): array
    {
        return [
            [
                'int',
                'Int32',
            ],
            [
                'long',
                'Long',
            ],
            [
                'char',
                'String',
            ],
            [
                'varchar',
                'String',
            ],
            [
                'text',
                'String',
            ],
            [
                'longtext',
                'String',
            ],
            [
                'tinyint',
                'Boolean',
            ],
            [
                'datetime',
                'DateTime',
            ],
            [
                '',
                'Unknown',
            ],
            [
                'dummy',
                'Unknown',
            ],
            [
                'INT',
                'Unknown',
            ],
        ];
    }

    public function testGetIndexName(): void
    {
        $this->object->name = "ä'7<ab>";
        $this->object->key = 'PRI';

        $this->assertEquals(
            "index=\"ä'7&lt;ab&gt;\"",
            $this->object->getIndexName()
        );

        $this->object->key = '';

        $this->assertEquals(
            '',
            $this->object->getIndexName()
        );
    }

    public function testIsPK(): void
    {
        $this->object->key = 'PRI';

        $this->assertTrue(
            $this->object->isPK()
        );

        $this->object->key = '';

        $this->assertFalse(
            $this->object->isPK()
        );
    }

    public function testFormatCs(): void
    {
        $this->object->name = 'Name#name#123';

        $this->assertEquals(
            'text123Namename',
            $this->object->formatCs('text123#name#')
        );
    }

    public function testFormatXml(): void
    {
        $this->object->name = '"a\'';

        $this->assertEquals(
            '&quot;a\'index="&quot;a\'"',
            $this->object->formatXml('#name##indexName#')
        );
    }

    public function testFormat(): void
    {
        $this->assertEquals(
            'NameintInt32intfalsetrue',
            $this->object->format(
                '#ucfirstName##dotNetPrimitiveType##dotNetObjectType##type#' .
                '#notNull##unique#'
            )
        );
    }
}
