<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Form;
use PhpMyAdmin\Tests\AbstractTestCase;
use ReflectionClass;
use ReflectionProperty;
use function array_keys;
use function preg_match;

class FormTest extends AbstractTestCase
{
    /** @var Form */
    protected $object;

    /**
     * Configures global environment.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::defineVersionConstants();
        parent::setTheme();
        parent::loadDefaultConfig();
        parent::setGlobalConfig();
        $GLOBALS['server'] = 0;
        $this->object = new Form(
            'pma_form_name',
            [
                'pma_form1',
                'pma_form2',
            ],
            new ConfigFile(),
            1
        );
    }

    /**
     * tearDown for test cases
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    /**
     * Test for Form::__constructor
     *
     * @group medium
     */
    public function testContructor(): void
    {
        $this->assertEquals(
            1,
            $this->object->index
        );
        $this->assertEquals(
            'pma_form_name',
            $this->object->name
        );
        $this->assertArrayHasKey(
            'pma_form1',
            $this->object->fields
        );
    }

    /**
     * Test for Form::getOptionType
     */
    public function testGetOptionType(): void
    {
        $attrFieldsTypes = new ReflectionProperty(Form::class, 'fieldsTypes');
        $attrFieldsTypes->setAccessible(true);
        $attrFieldsTypes->setValue(
            $this->object,
            ['7' => 'Seven']
        );

        $this->assertNull(
            $this->object->getOptionType('123/4/5/6')
        );

        $this->assertEquals(
            'Seven',
            $this->object->getOptionType('123/4/5/7')
        );
    }

    /**
     * Test for Form::getOptionValueList
     */
    public function testGetOptionValueList(): void
    {
        $this->assertEquals(
            [
                'NHibernate C# DO',
                'NHibernate XML',
            ],
            $this->object->getOptionValueList('Export/codegen_format')
        );

        $this->assertEquals(
            [
                'auto' => 'auto',
                '1' => 1,
                '0' => 0,
            ],
            $this->object->getOptionValueList('OBGzip')
        );

        $this->assertEquals(
            [
                'none' => 'Nowhere',
                'left' => 'Left',
                'right' => 'Right',
                'both' =>   'Both',
            ],
            $this->object->getOptionValueList('RowActionLinks')
        );
    }

    /**
     * Test for Form::readFormPathsCallback
     */
    public function testReadFormPathsCallBack(): void
    {
        $reflection = new ReflectionClass(Form::class);
        $method = $reflection->getMethod('readFormPathsCallback');
        $method->setAccessible(true);

        $array = [
            'foo' => [
                'bar' => [
                    'test' => 1,
                    1 => ':group:end',
                ],
            ],
        ];

        $method->invoke($this->object, $array, 'foo', 'pref');

        $result = $this->object->fields;

        $this->assertCount(
            4,
            $result
        );

        $this->assertEquals(
            'pma_form1',
            $result['pma_form1']
        );

        $this->assertEquals(
            'pma_form2',
            $result['pma_form2']
        );

        $this->assertEquals(
            'preffoo/foo/bar/test',
            $result[0]
        );

        // needs regexp because the counter is static

        // assertMatchesRegularExpression added in 9.1
        $this->assertRegExp(
            '/^preffoo\/foo\/bar\/\:group\:end\:\d+$/',
            $result[1]
        );
    }

    /**
     * Test for Form::readFormPaths
     */
    public function testReadFormPaths(): void
    {
        $reflection = new ReflectionClass(Form::class);
        $method = $reflection->getMethod('readFormPaths');
        $method->setAccessible(true);

        $array = [
            'foo' => [
                'bar' => [
                    'test' => 1,
                    1 => ':group:end',
                ],
            ],
        ];

        $method->invoke($this->object, $array);

        $result = $this->object->fields;

        $this->assertCount(
            2,
            $result
        );

        $this->assertEquals(
            'foo/bar/test',
            $result['test']
        );

        unset($result['test']);

        // needs regexp because the counter is static

        $keys = array_keys($result);
        $key = $keys[0];

        // assertMatchesRegularExpression added in 9.1
        $this->assertRegExp(
            '/^\:group\:end\:(\d+)$/',
            $key
        );

        preg_match('/^\:group\:end\:(\d+)$/', $key, $matches);
        $digit = $matches[1];

        $this->assertEquals(
            'foo/bar/:group:end:' . $digit,
            $result[':group:end:' . $digit]
        );
    }

    /**
     * Test for Form::readTypes
     */
    public function testReadTypes(): void
    {
        $reflection = new ReflectionClass(Form::class);
        $method = $reflection->getMethod('readTypes');
        $method->setAccessible(true);

        $this->object->fields = [
            'pma_form1' => 'Servers/1/port',
            'pma_form2' => 'Servers/1/auth_type',
            ':group:end:0' => 'preffoo/foo/bar/test',
            '1' => 'preffoo/foo/bar/:group:end:0',
        ];

        $attrFieldsTypes = $reflection->getProperty('fieldsTypes');
        $attrFieldsTypes->setAccessible(true);

        $method->invoke($this->object, null);

        $this->assertEquals(
            [
                'pma_form1' => 'integer',
                'pma_form2' => 'select',
                ':group:end:0' => 'group',
                '1' => 'NULL',
            ],
            $attrFieldsTypes->getValue($this->object)
        );
    }

    /**
     * Test for Form::loadForm
     */
    public function testLoadForm(): void
    {
        $this->object = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->setMethods(['readFormPaths', 'readTypes'])
            ->getMock();

        $this->object->expects($this->exactly(1))
            ->method('readFormPaths')
            ->with(['testForm']);

        $this->object->expects($this->exactly(1))
            ->method('readTypes');

        $this->object->loadForm('pmaform', ['testForm']);

        $this->assertEquals(
            'pmaform',
            $this->object->name
        );
    }

    /**
     * Test for Form::cleanGroupPaths
     */
    public function testCleanGroupPaths(): void
    {
        $this->object = $this->getMockBuilder(Form::class)
            ->disableOriginalConstructor()
            ->setMethods(['readFormPaths', 'readTypes'])
            ->getMock();

        $this->object->expects($this->exactly(1))->method('readFormPaths')->with([
            ':group:OpenDocument-OpenOffice 試算表',
            'group:test/data',
            'Export/ods_columns',
            'Export/ods_null',
            ':group:end',
        ]);

        $this->object->loadForm('pmaform', [
            ':group:OpenDocument/OpenOffice 試算表',
            'group:test/data',
            'Export/ods_columns',
            'Export/ods_null',
            ':group:end',
        ]);
    }
}
