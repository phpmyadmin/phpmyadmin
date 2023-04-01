<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Dbal;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Query\Utilities;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\DummyResult;

/** @coversNothing */
class DbiDummyTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    /**
     * Configures test parameters.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        $GLOBALS['dbi'] = $this->dbi;
        $GLOBALS['cfg']['DBG']['sql'] = false;
        $GLOBALS['cfg']['IconvExtraParams'] = '';
        $GLOBALS['server'] = 1;
    }

    public function testGetClientInfo(): void
    {
        $this->assertNotEmpty($this->dummyDbi->getClientInfo());
        // Call the DatabaseInterface
        $this->assertSame($this->dbi->getClientInfo(), $this->dummyDbi->getClientInfo());
    }

    /**
     * Simple test for basic query
     *
     * This relies on dummy driver internals
     */
    public function testQuery(): void
    {
        $this->assertInstanceOf(DummyResult::class, $this->dbi->tryQuery('SELECT 1'));
    }

    /**
     * Simple test for fetching results of query
     *
     * This relies on dummy driver internals
     */
    public function testFetch(): void
    {
        $result = $this->dbi->tryQuery('SELECT 1');
        $this->assertNotFalse($result);
        $this->assertSame(['1'], $result->fetchRow());
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

    /** @return array<array{string, bool}> */
    public static function schemaData(): array
    {
        return [['information_schema', true], ['pma_test', false]];
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
            Utilities::formatError($number, $message),
        );
    }

    /** @return array<array{int, string, string}> */
    public static function errorData(): array
    {
        return [
            [1234, '', '#1234 - '],
            [1234, 'foobar', '#1234 - foobar'],
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
            $this->dbi->escapeString('a'),
        );
        $this->assertEquals(
            'a\\\'',
            $this->dbi->escapeString('a\''),
        );
    }
}
