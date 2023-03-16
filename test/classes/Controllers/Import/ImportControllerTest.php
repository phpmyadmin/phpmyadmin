<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Import;

use PhpMyAdmin\Controllers\Import\ImportController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;

/** @covers \PhpMyAdmin\Controllers\Import\ImportController */
class ImportControllerTest extends AbstractTestCase
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

    public function testIndexParametrized(): void
    {
        parent::loadContainerBuilder();

        parent::loadDbiIntoContainerBuilder();

        parent::setLanguage();

        parent::setTheme();

        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['user'] = 'user';

        parent::loadResponseIntoContainerBuilder();

        // Some params were not added as they are not required for this test
        $GLOBALS['db'] = 'pma_test';
        $GLOBALS['table'] = 'table1';
        $GLOBALS['sql_query'] = 'SELECT A.*' . "\n"
            . 'FROM table1 A' . "\n"
            . 'WHERE A.nomEtablissement = :nomEta AND foo = :1 AND `:a` IS NULL';

        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['db', null, $GLOBALS['db']],
            ['table', null, $GLOBALS['table']],
            ['parameters', null, [':nomEta' => 'Saint-Louis - Châteaulin', ':1' => '4']],
            ['sql_query', null, $GLOBALS['sql_query']],
        ]);
        $request->method('hasBodyParam')->willReturnMap([
            ['parameterized', true],
            ['rollback_query', false],
            ['allow_interrupt', false],
            ['skip', false],
        ]);

        $this->dummyDbi->addResult(
            'SELECT A.* FROM table1 A WHERE A.nomEtablissement = \'Saint-Louis - Châteaulin\''
            . ' AND foo = 4 AND `:a` IS NULL LIMIT 0, 25',
            [],
        );

        $this->dummyDbi->addResult(
            'SHOW CREATE TABLE `pma_test`.`table1`',
            [],
        );

        $this->dummyDbi->addResult(
            'SHOW FULL COLUMNS FROM `pma_test`.`table1`',
            [],
        );

        /** @var ImportController $importController */
        $importController = $GLOBALS['containerBuilder']->get(ImportController::class);
        $this->dummyDbi->addSelectDb('pma_test');
        $this->dummyDbi->addSelectDb('pma_test');
        $importController($request);
        $this->dummyDbi->assertAllSelectsConsumed();
        $this->assertResponseWasSuccessfull();

        $this->assertStringContainsString(
            'MySQL returned an empty result set (i.e. zero rows).',
            $this->getResponseHtmlResult(),
        );

        $this->assertStringContainsString(
            'SELECT A.*' . "\n" . 'FROM table1 A' . "\n"
                . 'WHERE A.nomEtablissement = \'Saint-Louis - Châteaulin\' AND foo = 4 AND `:a` IS NULL',
            $this->getResponseHtmlResult(),
        );

        $this->dummyDbi->assertAllQueriesConsumed();
    }
}
