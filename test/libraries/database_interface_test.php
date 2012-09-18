<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for faked database access
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/php-gettext/gettext.inc';
require_once 'libraries/database_interface.lib.php';
require_once 'libraries/Tracker.class.php';

/**
 * Tests basic functionality of dummy dbi driver
 *
 * @package PhpMyAdmin-test
 */
class PMA_DBI_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Configures test parameters.
     *
     * @return void
     */
    function setup()
    {
        $GLOBALS['cfg']['IconvExtraParams'] = '';
    }

    /**
     * Simple test for basic query
     *
     * This relies on dummy driver internals
     *
     * @return void
     */
    function testQuery()
    {
        $this->assertEquals(0, PMA_DBI_real_query('SELECT 1'));
    }

    /**
     * Simple test for fetching results of query
     *
     * This relies on dummy driver internals
     *
     * @return void
     */
    function testFetch()
    {
        $this->assertEquals(array('1'), PMA_DBI_fetch_array(0));
    }

    /**
     * Test for system schema detection
     *
     * @param string $schema   schema name
     * @param bool   $expected expected result
     *
     * @return void
     *
     * @dataProvider schemaData
     */
    function testSystemSchema($schema, $expected)
    {
        $this->assertEquals($expected, PMA_is_system_schema($schema));
    }

    /**
     * Data provider for schema test
     *
     * @return array with test data
     */
    function schemaData()
    {
        return array(
            array('information_schema', true),
            array('pma_test', false),
        );
    }

    /**
     * Test for error formatting
     *
     * @param integer $number   error number
     * @param string  $message  error message
     * @param string  $expected expected result
     *
     * @return void
     *
     * @dataProvider errorData
     */
    function testFormatError($number, $message, $expected)
    {
        $this->assertEquals(
            $expected,
            PMA_DBI_formatError($number, $message)
        );
    }

    /**
     * Data provider for error formatting test
     *
     * @return array with test data
     */
    function errorData()
    {
        return array(
            array(1234, '', '#1234 - '),
            array(1234, 'foobar', '#1234 - foobar'),
            array(
                2002, 'foobar',
                '#2002 - foobar<br />The server is not responding (or the local '
                . 'server\'s socket is not correctly configured).'
            ),
        );
    }
}

