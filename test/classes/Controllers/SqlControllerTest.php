<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Controllers\SqlController;
use PhpMyAdmin\Tests\AbstractTestCase;

class SqlControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        parent::setGlobalDbi();
        parent::loadContainerBuilder();
        parent::loadDbiIntoContainerBuilder();
        parent::loadDefaultConfig();
        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        parent::defineVersionConstants();
        parent::loadResponseIntoContainerBuilder();
    }

    public function testGetSetValues(): void
    {
        global $containerBuilder, $_POST;

        $this->dummyDbi->addResult(
            'SHOW COLUMNS FROM `cvv`.`enums` LIKE \'set\'',
            false
        );

        $_POST = [
            'ajax_request' => true,
            'db' => 'cvv',
            'table' => 'enums',
            'column' => 'set',
            'curr_value' => 'b&c',
        ];
        $GLOBALS['db'] = $_POST['db'];
        $GLOBALS['table'] = $_POST['table'];

        $containerBuilder->setParameter('db', $GLOBALS['db']);
        $containerBuilder->setParameter('table', $GLOBALS['table']);
        /** @var SqlController $sqlController */
        $sqlController = $containerBuilder->get(SqlController::class);
        $sqlController->getSetValues();

        $this->assertResponseWasNotSuccessfull();

        $this->assertSame(
            ['message' => 'Error in processing request'],
            $this->getResponseJsonResult()
        );
    }

    public function testGetEnumValues(): void
    {
        global $containerBuilder, $_POST;

        $this->dummyDbi->addResult(
            'SHOW COLUMNS FROM `cvv`.`enums` LIKE \'set\'',
            false
        );

        $_POST = [
            'ajax_request' => true,
            'db' => 'cvv',
            'table' => 'enums',
            'column' => 'set',
            'curr_value' => 'b&c',
        ];
        $GLOBALS['db'] = $_POST['db'];
        $GLOBALS['table'] = $_POST['table'];

        $containerBuilder->setParameter('db', $GLOBALS['db']);
        $containerBuilder->setParameter('table', $GLOBALS['table']);
        /** @var SqlController $sqlController */
        $sqlController = $containerBuilder->get(SqlController::class);
        $sqlController->getEnumValues();

        $this->assertResponseWasNotSuccessfull();

        $this->assertSame(
            ['message' => 'Error in processing request'],
            $this->getResponseJsonResult()
        );
    }
}
