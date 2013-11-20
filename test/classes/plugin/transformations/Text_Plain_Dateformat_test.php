<?php
/**
 * Tests for Text_Plain_Dateformat class
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

/* Each PluginObserver instance contains a PluginManager instance */
require_once 'libraries/Util.class.php';
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/plugins/PluginManager.class.php';
require_once 'libraries/plugins/transformations/Text_Plain_Dateformat.class.php';

/**
 * Tests for Text_Plain_Dateformat class
 *
 * @package PhpMyAdmin-test
 */
class Text_Plain_Dateformat_Test extends PHPUnit_Framework_TestCase
{
    /**
     * @access protected
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function setUp()
    {
        $this->object = new Text_Plain_Dateformat(new PluginManager());
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function tearDown()
    {
        unset($this->object);
    }

    /**
     * Test for getInfo
     *
     * @return void
     *
     * @group medium
     */
    public function testGetInfo()
    {
        $info = 'Displays a TIME, TIMESTAMP, DATETIME or numeric unix timestamp'
            . ' column as formatted date. The first option is the offset (in'
            . ' hours) which will be added to the timestamp (Default: 0). Use'
            . ' second option to specify a different date/time format string.'
            . ' Third option determines whether you want to see local date or'
            . ' UTC one (use "local" or "utc" strings) for that. According to'
            . ' that, date format has different value - for "local" see the'
            . ' documentation for PHP\'s strftime() function and for "utc" it'
            . ' is done using gmdate() function.';

        $this->assertEquals(
            $info,
            Text_Plain_Dateformat::getInfo()
        );

    }

    /**
     * Test for getName
     *
     * @return void
     *
     * @group medium
     */
    public function testGetName()
    {
        $this->assertEquals(
            "Date Format",
            Text_Plain_Dateformat::getName()
        );
    }

    /**
     * Test for getMIMEType
     *
     * @return void
     *
     * @group medium
     */
    public function testGetMIMEType()
    {
        $this->assertEquals(
            "Text",
            Text_Plain_Dateformat::getMIMEType()
        );
    }

    /**
     * Test for getMIMESubtype
     *
     * @return void
     *
     * @group medium
     */
    public function testGetMIMESubtype()
    {
        $this->assertEquals(
            "Plain",
            Text_Plain_Dateformat::getMIMESubtype()
        );
    }

    /**
     * Test for applyTransformation
     *
     * @return void
     *
     * @group medium
     */
    public function testApplyTransformation()
    {
        //add timezone setting before time transformation
        date_default_timezone_set('UTC');
        $timestamp = 12345;
        $options = array(0);
        $meta = new Text_Plain_Dateformat_Meta();
        $meta->type = 'int';
        $result = '<dfn onclick="alert(\'12345\');" title="12345">'
             . 'Jan 01, 1970 at 03:25 AM</dfn>';
        $this->assertEquals(
            $result,
            $this->object->applyTransformation($timestamp, $options, $meta)
        );

        //other format timestamp, Detect TIMESTAMP(6 | 8 | 10 | 12 | 14)
        $meta->type = 'string';
        $timestamp = 12345678;
        $result = '<dfn onclick="alert(\'12345678\');" title="12345678">'
             . 'May 23, 1970 at 09:21 PM</dfn>';
        $this->assertEquals(
            $result,
            $this->object->applyTransformation($timestamp, $options, $meta)
        );

        //no MYSQL timestamp
        $timestamp = 123456789;
        $result = '<dfn onclick="alert(\'123456789\');" title="123456789">'
            . 'Nov 29, 1973 at 09:33 PM</dfn>';
        $this->assertEquals(
            $result,
            $this->object->applyTransformation($timestamp, $options, $meta)
        );

        //string
        $timestamp = "20100201";
        $result = '<dfn onclick="alert(\'20100201\');" title="20100201">'
            . 'Feb 01, 2010 at 12:00 AM</dfn>';
        $this->assertEquals(
            $result,
            $this->object->applyTransformation($timestamp, $options, $meta)
        );


    }
}

/**
 * Mock Class Text_Plain_Dateformat_Meta
 *
 * @package PhpMyAdmin-test
 */
class Text_Plain_Dateformat_Meta
{
    var $blob = null;
    var $max_length = null;
    var $type = null;
}