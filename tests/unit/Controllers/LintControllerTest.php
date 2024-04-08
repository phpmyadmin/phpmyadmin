<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Controllers\LintController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\RunTestsInSeparateProcesses;

use function json_encode;

#[CoversClass(LintController::class)]
#[RunTestsInSeparateProcesses]
class LintControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        DatabaseInterface::$instance = $this->createDatabaseInterface();
    }

    public function testWithoutParams(): void
    {
        $request = self::createStub(ServerRequest::class);
        $request->method('isAjax')->willReturn(true);
        $request->method('getParsedBodyParam')->willReturnMap([['sql_query', '', ''], ['options', null, null]]);

        $this->getLintController()($request);

        $output = $this->getActualOutputForAssertion();
        self::assertJson($output);
        self::assertJsonStringEqualsJsonString('[]', $output);
    }

    public function testWithoutSqlErrors(): void
    {
        $request = self::createStub(ServerRequest::class);
        $request->method('isAjax')->willReturn(true);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['sql_query', '', 'SELECT * FROM `actor` WHERE `actor_id` = 1;'],
            ['options', null, null],
        ]);

        $this->getLintController()($request);

        $output = $this->getActualOutputForAssertion();
        self::assertJson($output);
        self::assertJsonStringEqualsJsonString('[]', $output);
    }

    public function testWithSqlErrors(): void
    {
        $expectedJson = json_encode([
            [
                'message' => 'An alias was previously found. (near <code>`actor_id`</code>)',
                'fromLine' => 1,
                'fromColumn' => 29,
                'toLine' => 1,
                'toColumn' => 39,
                'severity' => 'error',
            ],
            [
                'message' => 'Unexpected token. (near <code>`actor_id`</code>)',
                'fromLine' => 1,
                'fromColumn' => 29,
                'toLine' => 1,
                'toColumn' => 39,
                'severity' => 'error',
            ],
            [
                'message' => 'Unexpected token. (near <code>=</code>)',
                'fromLine' => 1,
                'fromColumn' => 40,
                'toLine' => 1,
                'toColumn' => 41,
                'severity' => 'error',
            ],
            [
                'message' => 'Unexpected token. (near <code>1</code>)',
                'fromLine' => 1,
                'fromColumn' => 42,
                'toLine' => 1,
                'toColumn' => 43,
                'severity' => 'error',
            ],
        ]);
        self::assertNotFalse($expectedJson);

        $request = self::createStub(ServerRequest::class);
        $request->method('isAjax')->willReturn(true);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['sql_query', '', 'SELECT * FROM `actor` WHEREE `actor_id` = 1'],
            ['options', null, null],
        ]);

        $this->getLintController()($request);

        $output = $this->getActualOutputForAssertion();
        self::assertJson($output);
        self::assertJsonStringEqualsJsonString($expectedJson, $output);
    }

    private function getLintController(): LintController
    {
        return new LintController(new ResponseRenderer());
    }
}
