<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for TableProperty class
 *
 * @package PhpMyAdmin-test
 */
require_once 'libraries/plugins/export/TableProperty.class.php';
require_once 'libraries/plugins/export/ExportCodegen.class.php';
require_once 'libraries/Util.class.php';
require_once 'libraries/Theme.class.php';
require_once 'libraries/Config.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/config.default.php';

/**
 * tests for TableProperty class
 *
 * @package PhpMyAdmin-test
 */
class PMA_TableProperty_Test extends PHPUnit_Framework_TestCase
{
    protected $object;

    /**
     * Configures global environment.
     *
     * @return void
     */
    function setup()
    {
        $GLOBALS['server'] = 0;
        $row = array(' name ', 'int ', true, ' PRI', '0', 'mysql');
        $this->object = new TableProperty($row);
    }

    /**
     * tearDown for test cases
     *
     * @return void
     */
    public function tearDown()
    {
        unset($this->object);
    }

    /**
     * Test for TableProperty::__construct
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
     * Test for TableProperty::getPureType
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
     * Test for TableProperty::isNotNull
     *
     * @param string $nullable nullable value
     * @param string $expected expected output
     *
     * @return void
     * @dataProvider isNotNullProvider
     */
    public function testIsNotNull($nullable, $expected)
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
        return array(
            array("NO", "true"),
            array("", "false"),
            array("no", "false")
        );
    }

    /**
     * Test for TableProperty::isUnique
     *
     * @param string $key      key value
     * @param string $expected expected output
     *
     * @return void
     * @dataProvider isUniqueProvider
     */
    public function testIsUnique($key, $expected)
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
        return array(
            array("PRI", "true"),
            array("UNI", "true"),
            array("", "false"),
            array("pri", "false"),
            array("uni", "false"),
        );
    }

    /**
     * Test for TableProperty::getDotNetPrimitiveType
     *
     * @param string $type     type value
     * @param string $expected expected output
     *
     * @return void
     * @dataProvider getDotNetPrimitiveTypeProvider
     */
    public function testGetDotNetPrimitiveType($type, $expected)
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
        return array(
            array("int", "int"),
            array("long", "long"),
            array("char", "string"),
            array("varchar", "string"),
            array("text", "string"),
            array("longtext", "string"),
            array("tinyint", "bool"),
            array("datetime", "DateTime"),
            array("", "unknown"),
            array("dummy", "unknown"),
            array("INT", "unknown")
        );
    }

    /**
     * Test for TableProperty::getDotNetObjectType
     *
     * @param string $type     type value
     * @param string $expected expected output
     *
     * @return void
     * @dataProvider getDotNetObjectTypeProvider
     */
    public function testGetDotNetObjectType($type, $expected)
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
        return array(
            array("int", "Int32"),
            array("long", "Long"),
            array("char", "String"),
            array("varchar", "String"),
            array("text", "String"),
            array("longtext", "String"),
            array("tinyint", "Boolean"),
            array("datetime", "DateTime"),
            array("", "Unknown"),
            array("dummy", "Unknown"),
            array("INT", "Unknown")
        );
    }

    /**
     * Test for TableProperty::getIndexName
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
     * Test for TableProperty::isPK
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
     * Test for TableProperty::formatCs
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
     * Test for TableProperty::formatXml
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
     * Test for TableProperty::format
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
?>
