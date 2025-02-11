<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Charsets;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

#[CoversClass(Charsets::class)]
#[PreserveGlobalState(false)]
#[RunTestsInSeparateProcesses]
class CharsetsTest extends AbstractTestCase
{
    public function testGetServerCharset(): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);
        $dummyDbi->addResult(
            'SHOW SESSION VARIABLES LIKE \'character_set_server\';',
            [['character_set_server', 'utf8mb3']],
            ['Variable_name', 'Value'],
        );
        $dummyDbi->addResult('SHOW SESSION VARIABLES LIKE \'character_set_server\';', false);
        $dummyDbi->addResult('SELECT @@character_set_server;', false);
        $dummyDbi->addResult('SHOW SESSION VARIABLES LIKE \'character_set_server\';', false);
        $dummyDbi->addResult('SELECT @@character_set_server;', [['utf8mb3']]);
        $dummyDbi->addResult(
            'SHOW SESSION VARIABLES LIKE \'character_set_server\';',
            [['character_set_server', 'utf8mb4']],
            ['Variable_name', 'Value'],
        );

        $charset = Charsets::getServerCharset($dbi, false);
        self::assertSame('utf8', $charset->getName());

        $charset = Charsets::getServerCharset($dbi, false);
        self::assertSame('Unknown', $charset->getName());

        $charset = Charsets::getServerCharset($dbi, false);
        self::assertSame('utf8', $charset->getName());

        $charset = Charsets::getServerCharset($dbi, false);
        self::assertSame('utf8mb4', $charset->getName());

        $charset = Charsets::getServerCharset($dbi, false);
        self::assertSame('utf8mb4', $charset->getName());

        $dummyDbi->assertAllQueriesConsumed();
    }

    public function testFindCollationByName(): void
    {
        $dbi = $this->createDatabaseInterface();
        self::assertNull(Charsets::findCollationByName($dbi, false, ''));
        self::assertNull(Charsets::findCollationByName($dbi, false, 'invalid'));
        $actual = Charsets::findCollationByName($dbi, false, 'utf8_general_ci');
        self::assertInstanceOf(Charsets\Collation::class, $actual);
        self::assertSame('utf8_general_ci', $actual->getName());
    }

    public function testGetCharsetsWithIS(): void
    {
        $dbi = $this->createDatabaseInterface();
        $charsets = Charsets::getCharsets($dbi, false);
        self::assertCount(4, $charsets);
        self::assertContainsOnlyInstancesOf(Charsets\Charset::class, $charsets);
    }

    public function testGetCharsetsWithoutIS(): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);
        $dummyDbi->addResult(
            'SHOW CHARACTER SET',
            [
                ['armscii8', 'ARMSCII-8 Armenian', 'armscii8_general_ci', '1'],
                ['utf8', 'utf8_general_ci', 'UTF-8 Unicode', '3'],
                ['utf8mb4', 'UTF-8 Unicode', 'utf8mb4_0900_ai_ci', '4'],
                ['latin1', 'latin1_swedish_ci', 'cp1252 West European', '1'],
            ],
            ['Charset', 'Default collation', 'Description', 'Maxlen'],
        );

        $charsets = Charsets::getCharsets($dbi, true);
        self::assertCount(4, $charsets);
        self::assertContainsOnlyInstancesOf(Charsets\Charset::class, $charsets);
    }

    public function testGetCollationsWithIS(): void
    {
        $dbi = $this->createDatabaseInterface();
        $collations = Charsets::getCollations($dbi, false);
        self::assertCount(4, $collations);
        self::assertContainsOnlyArray($collations);
        foreach ($collations as $collation) {
            self::assertContainsOnlyInstancesOf(Charsets\Collation::class, $collation);
        }
    }

    public function testGetCollationsMariaDB(): void
    {
        $dbi = $this->createDatabaseInterface();
        $dbi->setVersion(['@@version' => '10.10.0-MariaDB']);
        $collations = Charsets::getCollations($dbi, false);
        self::assertCount(4, $collations);
        self::assertContainsOnlyArray($collations);
        foreach ($collations as $collation) {
            self::assertContainsOnlyInstancesOf(Charsets\Collation::class, $collation);
        }
    }

    public function testGetCollationsWithoutIS(): void
    {
        $dummyDbi = $this->createDbiDummy();
        $dbi = $this->createDatabaseInterface($dummyDbi);
        $dummyDbi->addResult(
            'SHOW COLLATION',
            [
                ['utf8mb4_general_ci', 'utf8mb4', '45', 'Yes', 'Yes', '1'],
                ['armscii8_general_ci', 'armscii8', '32', 'Yes', 'Yes', '1'],
                ['utf8_general_ci', 'utf8', '33', 'Yes', 'Yes', '1'],
                ['utf8_bin', 'utf8', '83', '', 'Yes', '1'],
                ['latin1_swedish_ci', 'latin1', '8', 'Yes', 'Yes', '1'],
            ],
            ['Collation', 'Charset', 'Id', 'Default', 'Compiled', 'Sortlen'],
        );

        $collations = Charsets::getCollations($dbi, true);
        self::assertCount(4, $collations);
        self::assertContainsOnlyArray($collations);
        foreach ($collations as $collation) {
            self::assertContainsOnlyInstancesOf(Charsets\Collation::class, $collation);
        }
    }
}
