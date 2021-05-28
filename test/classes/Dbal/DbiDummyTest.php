<?php
/**
 * Test for faked database access
 */

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Dbal;

use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;

/**
 * Tests basic functionality of dummy dbi driver
 */
class DbiDummyTest extends AbstractTestCase
{
    /** @var DbiDummy */
    protected $object;

    /**
     * Configures test parameters.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $this->object = new DbiDummy();
        $GLOBALS['cfg']['DBG']['sql'] = false;
        $GLOBALS['cfg']['IconvExtraParams'] = '';
        $GLOBALS['server'] = 1;
    }

    public function testGetClientInfo(): void
    {
        $obj = (object) [];
        $this->assertNotEmpty($this->object->getClientInfo($obj));
        // Call the DatabaseInterface
        $this->assertSame($GLOBALS['dbi']->getClientInfo(), $this->object->getClientInfo($obj));
    }

    /**
     * Simple test for basic query
     *
     * This relies on dummy driver internals
     */
    public function testQuery(): void
    {
        $this->assertEquals(1000, $GLOBALS['dbi']->tryQuery('SELECT 1'));
    }

    /**
     * Simple test for fetching results of query
     *
     * This relies on dummy driver internals
     */
    public function testFetch(): void
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
     * @dataProvider schemaData
     */
    public function testSystemSchema(string $schema, bool $expected): void
    {
        $this->assertEquals($expected, Utilities::isSystemSchema($schema));
    }

    /**
     * Data provider for schema test
     */
    public function schemaData(): array
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
     * @param int    $number   error number
     * @param string $message  error message
     * @param string $expected expected result
     *
     * @dataProvider errorData
     */
    public function testFormatError(int $number, string $message, string $expected): void
    {
        $GLOBALS['server'] = 1;
        $this->assertEquals(
            $expected,
            Utilities::formatError($number, $message)
        );
    }

    /**
     * Data provider for error formatting test
     */
    public function errorData(): array
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
