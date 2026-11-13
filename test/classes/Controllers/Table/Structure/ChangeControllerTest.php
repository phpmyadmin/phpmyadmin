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
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
    }

    public function testChangeController(): void
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
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

    public function testInheritedColumnCollationIsNotSelected(): void
    {
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['db'] = 'testdb';
        $GLOBALS['table'] = 'mytable';

        $this->dummyDbi->addResult(
            'SHOW CREATE TABLE `testdb`.`mytable`',
            [
                [
                    'mytable',
                    'CREATE TABLE `mytable` ('
                        . ' `inherited` varchar(20) NOT NULL,'
                        . ' `explicit` varchar(20) CHARACTER SET latin1 COLLATE latin1_swedish_ci NOT NULL'
                        . ') DEFAULT CHARSET=latin1 COLLATE=latin1_swedish_ci',
                ],
            ],
            ['Table', 'Create Table']
        );
        foreach (['inherited', 'explicit'] as $field) {
            $this->dummyDbi->addResult(
                'SHOW FULL COLUMNS FROM `testdb`.`mytable` LIKE \'' . $field . '\'',
                [[$field, 'varchar(20)', 'latin1_swedish_ci', 'NO', '', null, '', '', '']],
                ['Field', 'Type', 'Collation', 'Null', 'Key', 'Default', 'Extra', 'Privileges', 'Comment']
            );
        }

        $response = new ResponseStub();
        $controller = new ChangeController(
            $response,
            new Template(),
            'testdb',
            'mytable',
            new Relation($this->dbi),
            new Transformations(),
            $this->dbi
        );
        $method = (new ReflectionClass(ChangeController::class))->getMethod('displayHtmlForColumnChange');
        if (PHP_VERSION_ID < 80100) {
            $method->setAccessible(true);
        }

        $method->invokeArgs($controller, [['inherited', 'explicit']]);
        $html = $response->getHTMLResult();
        self::assertStringContainsString('name="field_collation_orig[0]" value=""', $html);
        self::assertStringContainsString(
            'name="field_collation_orig[1]" value="latin1_swedish_ci"',
            $html
        );
        $this->assertAllQueriesConsumed();
    }
}
