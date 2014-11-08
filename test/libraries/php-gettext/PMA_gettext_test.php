<?php
/**
 * Tests for gettext_reader
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

require_once 'libraries/php-gettext/gettext.php';
require_once 'libraries/php-gettext/streams.php';

class PMA_Gettext_Test extends PHPUnit_Framework_TestCase
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
        $reader = new StringReader("cchars/nint");
        $this->object = new gettext_reader($reader);

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
     * Test for readint
     *
     * @return void
     */
    public function testReadint()
    {
        $this->assertEquals(
            $this->object->readint(),
            1848603506
        );
    }

    /**
     * Test for readintarray
     *
     * @return void
     */
    public function testReadintarray()
    {
        $this->assertEquals(
            $this->object->readintarray(1),
            array(
                1 => 1848603506
            )
        );
    }

    /**
     * Test for get_original_string
     *
     * @return void
     */
    public function testGet_original_string()
    {
        $this->assertEquals(
            $this->object->get_original_string(1),
            ''
        );
    }

    /**
     * Test for get_translation_string
     *
     * @return void
     */
    public function testGet_translation_string()
    {
        $this->assertEquals(
            $this->object->get_translation_string(1),
            ''
        );
    }

    /**
     * Test for find_string
     *
     * @param string $string string
     * @param int    $start  start (internally used in recursive function)
     * @param int    $end    end (internally used in recursive function)
     * @param string $output Expected output
     *
     * @return void
     *
     * @dataProvider providerForTestFind_string
     */
    public function testFind_string($string, $start, $end, $output)
    {
        $this->assertEquals(
            $this->object->find_string($string, $start, $end),
            $output
        );
    }

    /**
     * Data provider for testFind_string
     *
     * @return array
     */
    public function providerForTestFind_string()
    {
        return array(
            array(
                'sample_string/string',
                2,
                5,
                -1
            ),
            array(
                'sample_string/string',
                -1,
                -1,
                -1
            )
        );
    }

    /**
     * Test for translate
     *
     * @return void
     */
    public function testTranslate()
    {
        $this->assertEquals(
            $this->object->translate('transferable_string'),
            'transferable_string'
        );
    }

    /**
     * Test for sanitize_plural_expression
     *
     * @param string $expr   Expression to sanitize
     * @param string $output Expected output
     *
     * @return void
     *
     * @dataProvider providerForTestSanitize_plural_expression
     */
    public function testSanitize_plural_expression($expr, $output)
    {
        $this->assertEquals(
            $this->object->sanitize_plural_expression($expr),
            $output
        );
    }

    /**
     * Data provider for testSanitize_plural_expression
     *
     * @return array
     */
    public function providerForTestSanitize_plural_expression()
    {
        return array(
            array(
                'employeeId = 1',
                'employeeId=1;'
            ),
            array(
                'id = 1 ? true : false',
                'id=1 ? (true) : (false);'
            )
        );
    }

    /**
     * Test for extract_plural_forms_header_from_po_header
     *
     * @return void
     */
    public function testExtract_plural_forms_header_from_po_header()
    {
        $this->assertEquals(
            $this->object->extract_plural_forms_header_from_po_header(
                'id = 1 ? true : false'
            ),
            'nplurals=2; plural=n == 1 ? 0 : 1;'
        );
    }

    /**
     * Test for pgettext
     *
     * @return void
     */
    public function testPgettext()
    {
        $this->assertEquals(
            $this->object->pgettext('context', 'msgId'),
            'msgId'
        );
    }

}
