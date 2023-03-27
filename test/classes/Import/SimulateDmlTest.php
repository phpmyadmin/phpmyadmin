<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Import;

use PhpMyAdmin\Core;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Import\SimulateDml;
use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Url;

/** @covers \PhpMyAdmin\Import\SimulateDml */
class SimulateDmlTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        $GLOBALS['dbi'] = $this->dbi;
    }

    /** @dataProvider providerForTestGetMatchedRows */
    public function testGetMatchedRows(string $sqlQuery, string $simulatedQuery): void
    {
        $GLOBALS['db'] = 'PMA';
        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addSelectDb('PMA');
        $dbi = $this->createDatabaseInterface($dummyDbi);

        $object = new SimulateDml($dbi);
        $parser = new Parser($sqlQuery);

        $simulatedData = $object->getMatchedRows($sqlQuery, $parser, $parser->statements[0]);

        $matchedRowsUrl = Url::getFromRoute('/sql', [
            'db' => 'PMA',
            'sql_query' => $simulatedQuery,
            'sql_signature' => Core::signSqlQuery($simulatedQuery),
        ]);

        $this->dummyDbi->assertAllSelectsConsumed();
        $this->assertEquals([
            'sql_query' => Generator::formatSql($sqlQuery),
            'matched_rows' => 2,
            'matched_rows_url' => $matchedRowsUrl,
        ], $simulatedData);
    }

    /** @return string[][] */
    public static function providerForTestGetMatchedRows(): array
    {
        return [
            'update statement' => [
                'UPDATE `table_1` SET `id` = 20 WHERE `id` > 10',
                'SELECT `id` FROM `table_1` WHERE `id` > 10 AND (`id` <> 20)',
            ],
            'delete statement' => ['DELETE FROM `table_1` WHERE `id` > 10', 'SELECT * FROM `table_1` WHERE `id` > 10'],
        ];
    }
}
