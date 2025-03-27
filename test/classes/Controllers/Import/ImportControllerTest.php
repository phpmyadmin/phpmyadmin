<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Import;

use PhpMyAdmin\Controllers\Import\ImportController;
use PhpMyAdmin\Tests\AbstractTestCase;

/**
 * @covers \PhpMyAdmin\Controllers\Import\ImportController
 */
class ImportControllerTest extends AbstractTestCase
{
    public function testIndexParametrized(): void
    {
        global $containerBuilder, $db, $table, $sql_query;

        parent::loadContainerBuilder();
        parent::loadDbiIntoContainerBuilder();
        parent::setLanguage();
        parent::setTheme();

        $GLOBALS['server'] = 1;
        $GLOBALS['cfg']['Server']['user'] = 'user';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        parent::loadResponseIntoContainerBuilder();

        // Some params where not added as they where not required for this test
        $_POST['db'] = 'pma_test';
        $_POST['table'] = 'table1';
        $db = $_POST['db'];
        $table = $_POST['table'];
        $_POST['parameterized'] = 'on';
        $_POST['parameters'] = [':nomEta' => 'Saint-Louis - Châteaulin', ':1' => '4'];
        $_POST['sql_query'] = 'SELECT A.*' . "\n"
            . 'FROM table1 A' . "\n"
            . 'WHERE A.nomEtablissement = :nomEta AND foo = :1 AND `:a` IS NULL';
        $sql_query = $_POST['sql_query'];

        $this->dummyDbi->addResult(
            'SELECT A.* FROM table1 A WHERE A.nomEtablissement = \'Saint-Louis - Châteaulin\''
            . ' AND foo = 4 AND `:a` IS NULL LIMIT 0, 25',
            [],
            ['nomEtablissement', 'foo']
        );

        $this->dummyDbi->addResult(
            'SHOW CREATE TABLE `pma_test`.`table1`',
            []
        );

        $this->dummyDbi->addResult(
            'SHOW FULL COLUMNS FROM `pma_test`.`table1`',
            []
        );

        /** @var ImportController $importController */
        $importController = $containerBuilder->get(ImportController::class);
        $this->dummyDbi->addSelectDb('pma_test');
        $this->dummyDbi->addSelectDb('pma_test');
        $importController();
        $this->assertAllSelectsConsumed();
        $this->assertResponseWasSuccessfull();

        self::assertStringContainsString(
            'MySQL returned an empty result set (i.e. zero rows).',
            $this->getResponseHtmlResult()
        );

        self::assertStringContainsString(
            'SELECT A.*' . "\n" . 'FROM table1 A' . "\n"
                . 'WHERE A.nomEtablissement = \'Saint-Louis - Châteaulin\' AND foo = 4 AND `:a` IS NULL',
            $this->getResponseHtmlResult()
        );

        $this->assertAllQueriesConsumed();
    }
}
