<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Charsets;

/**
 * @covers \PhpMyAdmin\Charsets
 */
class CharsetsTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        parent::setGlobalDbi();
        $GLOBALS['server'] = 0;
        $GLOBALS['cfg']['DBG']['sql'] = false;
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
    }

    public function testGetServerCharset(): void
    {
        $this->dummyDbi->addResult(
            'SHOW SESSION VARIABLES LIKE \'character_set_server\';',
            [
                [
                    'character_set_server',
                    'utf8mb3',
                ],
            ],
            [
                'Variable_name',
                'Value',
            ]
        );
        $this->dummyDbi->addResult('SHOW SESSION VARIABLES LIKE \'character_set_server\';', false);
        $this->dummyDbi->addResult('SELECT @@character_set_server;', false);
        $this->dummyDbi->addResult('SHOW SESSION VARIABLES LIKE \'character_set_server\';', false);
        $this->dummyDbi->addResult(
            'SELECT @@character_set_server;',
            [
                ['utf8mb3'],
            ]
        );

        $charset = Charsets::getServerCharset($GLOBALS['dbi'], $GLOBALS['cfg']['Server']['DisableIS']);
        self::assertSame('utf8', $charset->getName());

        $charset = Charsets::getServerCharset($GLOBALS['dbi'], $GLOBALS['cfg']['Server']['DisableIS']);
        self::assertSame('Unknown', $charset->getName());

        $charset = Charsets::getServerCharset($GLOBALS['dbi'], $GLOBALS['cfg']['Server']['DisableIS']);
        self::assertSame('utf8', $charset->getName());

        $this->assertAllQueriesConsumed();
    }

    public function testFindCollationByName(): void
    {
        self::assertNull(Charsets::findCollationByName(
            $GLOBALS['dbi'],
            $GLOBALS['cfg']['Server']['DisableIS'],
            null
        ));

        self::assertNull(Charsets::findCollationByName(
            $GLOBALS['dbi'],
            $GLOBALS['cfg']['Server']['DisableIS'],
            ''
        ));

        self::assertNull(Charsets::findCollationByName(
            $GLOBALS['dbi'],
            $GLOBALS['cfg']['Server']['DisableIS'],
            'invalid'
        ));

        $actual = Charsets::findCollationByName(
            $GLOBALS['dbi'],
            $GLOBALS['cfg']['Server']['DisableIS'],
            'utf8_general_ci'
        );

        self::assertInstanceOf(Charsets\Collation::class, $actual);

        self::assertSame('utf8_general_ci', $actual->getName());
    }

    public function testGetCollationsMariaDB(): void
    {
        $this->dbi->setVersion(['@@version' => '10.10.0-MariaDB']);
        $collations = Charsets::getCollations($this->dbi, false);
        self::assertCount(4, $collations);
        self::assertContainsOnly('array', $collations);
        foreach ($collations as $collation) {
            self::assertContainsOnlyInstancesOf(Charsets\Collation::class, $collation);
        }
    }
}
