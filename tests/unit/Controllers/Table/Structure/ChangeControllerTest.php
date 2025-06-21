<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table\Structure;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Table\Structure\ChangeController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Table\ColumnsDefinition;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseStub;
use PhpMyAdmin\Transformations;
use PhpMyAdmin\UserPrivilegesFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionClass;

#[CoversClass(ChangeController::class)]
class ChangeControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        DatabaseInterface::$instance = $this->dbi;
    }

    public function testChangeController(): void
    {
        Config::getInstance()->selectedServer['DisableIS'] = false;
        Current::$database = 'testdb';
        Current::$table = 'mytable';
        $_REQUEST['field'] = '_id';

        $response = new ResponseStub();

        $class = new ReflectionClass(ChangeController::class);
        $method = $class->getMethod('displayHtmlForColumnChange');

        $relation = new Relation($this->dbi);
        $ctrl = new ChangeController(
            $response,
            $this->dbi,
            new ColumnsDefinition($this->dbi, $relation, new Transformations($this->dbi, $relation)),
            new UserPrivilegesFactory($this->dbi),
        );

        $method->invokeArgs($ctrl, [[$_REQUEST['field']]]);
        $actual = $response->getHTMLResult();
        self::assertStringContainsString(
            '<input id="field_0_1"' . "\n"
            . '        type="text"' . "\n"
            . '    name="field_name[0]"' . "\n"
            . '    maxlength="64"' . "\n"
            . '    class="textfield form-control"' . "\n"
            . '    title="Column"' . "\n"
            . '    size="10"' . "\n"
            . '    value="_id">' . "\n",
            $actual,
        );
        self::assertStringContainsString('id="enumEditorModal"', $actual);
    }
}
