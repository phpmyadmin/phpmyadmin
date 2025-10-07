<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table\Structure;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Table\Structure\SaveController;
use PhpMyAdmin\Controllers\Table\StructureController;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseStub;
use PhpMyAdmin\Transformations;
use ReflectionClass;

use const PHP_VERSION_ID;

/**
 * @covers \PhpMyAdmin\Controllers\Table\Structure\SaveController
 */
class SaveControllerTest extends AbstractTestCase
{
    public function testSaveController(): void
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['db'] = 'db';
        $GLOBALS['table'] = 'table';

        $class = new ReflectionClass(SaveController::class);
        $method = $class->getMethod('adjustColumnPrivileges');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        $ctrl = new SaveController(
            new ResponseStub(),
            new Template(),
            $GLOBALS['db'],
            $GLOBALS['table'],
            new Relation($this->dbi),
            new Transformations(),
            $this->dbi,
            $this->createStub(StructureController::class)
        );

        self::assertFalse($method->invokeArgs($ctrl, [[]]));
    }
}
