<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table\Structure;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Table\Structure\ChangeController;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseStub;
use PhpMyAdmin\Transformations;
use ReflectionClass;

use const PHP_VERSION_ID;

/**
 * @covers \PhpMyAdmin\Controllers\Table\Structure\ChangeController
 */
class ChangeControllerTest extends AbstractTestCase
{
    public function testChangeController(): void
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['db'] = 'testdb';
        $GLOBALS['table'] = 'mytable';
        $_REQUEST['field'] = '_id';

        $response = new ResponseStub();

        $class = new ReflectionClass(ChangeController::class);
        $method = $class->getMethod('displayHtmlForColumnChange');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        $ctrl = new ChangeController(
            $response,
            new Template(),
            $GLOBALS['db'],
            $GLOBALS['table'],
            new Relation($this->dbi),
            new Transformations(),
            $this->dbi
        );

        $method->invokeArgs($ctrl, [null]);
        $actual = $response->getHTMLResult();
        self::assertStringContainsString('<input id="field_0_1"' . "\n"
        . '        type="text"' . "\n"
        . '    name="field_name[0]"' . "\n"
        . '    maxlength="64"' . "\n"
        . '    class="textfield"' . "\n"
        . '    title="Column"' . "\n"
        . '    size="10"' . "\n"
        . '    value="_id">' . "\n", $actual);
        self::assertStringContainsString('id="enumEditorModal"', $actual);
    }
}
