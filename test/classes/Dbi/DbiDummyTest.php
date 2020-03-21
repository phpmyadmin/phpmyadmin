<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for faked database access
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Dbi;

use PHPUnit\Framework\TestCase;

/**
 * Tests basic functionality of dummy dbi driver
 *
 * @package PhpMyAdmin-test
 */
class DbiDummyTest extends TestCase
{
    /**
     * Configures test parameters.
     *
     * @return void
     */
    protected function setUp(): void
    {
        $GLOBALS['cfg']['DBG']['sql'] = false;
        $GLOBALS['cfg']['IconvExtraParams'] = '';
        $GLOBALS['server'] = 1;
    }

    /**
     * Simple test for basic query
     *
     * This relies on dummy driver internals
     *
     * @return void
     */
    public function testQuery()
    {
        $this->assertEquals(1000, $GLOBALS['dbi']->tryQuery('SELECT 1'));
    }

    /**
     * Simple test for fetching results of query
     *
     * This relies on dummy driver internals
     *
     * @return void
     */
    public function testFetch()
    {
        $result = $GLOBALS['dbi']->tryQuery('SELECT 1');
        $this->assertEquals(['1'], $GLOBALS['dbi']->fetchArray($result));
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
    public function testSystemSchema($schema, $expected): void
    {
        $this->assertEquals($expected, $GLOBALS['dbi']->isSystemSchema($schema));
    }

    /**
     * Data provider for schema test
     *
     * @return array with test data
     */
    public function schemaData()
    {
        return [
            [
                'information_schema',
                true,
            ],
            [
                'pma_test',
                false,
            ],
        ];
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
    public function testFormatError($number, $message, $expected): void
    {
        $GLOBALS['server'] = 1;
        $this->assertEquals(
            $expected,
            $GLOBALS['dbi']->formatError($number, $message)
        );
    }

    /**
     * Data provider for error formatting test
     *
     * @return array with test data
     */
    public function errorData()
    {
        return [
            [
                1234,
                '',
                '#1234 - ',
            ],
            [
                1234,
                'foobar',
                '#1234 - foobar',
            ],
            [
                2002,
                'foobar',
                '#2002 - foobar &mdash; The server is not responding (or the local '
                . 'server\'s socket is not correctly configured).',
            ],
        ];
    }

    /**
     * Test for string escaping
     *
     * @return void
     */
    public function testEscapeString(): void
    {
        $this->assertEquals(
            'a',
            $GLOBALS['dbi']->escapeString('a')
        );
        $this->assertEquals(
            'a\\\'',
            $GLOBALS['dbi']->escapeString('a\'')
        );
    }
}
