<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Controllers\LintController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

use function json_encode;

/**
 * @covers \PhpMyAdmin\Controllers\LintController
 * @runTestsInSeparateProcesses
 */
class LintControllerTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['dbi'] = $this->createDatabaseInterface();
    }

    public function testWithoutParams(): void
    {
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['sql_query', '', ''], ['options', null, null]]);

        $this->getLintController()($request);

        $output = $this->getActualOutputForAssertion();
        $this->assertJson($output);
        $this->assertJsonStringEqualsJsonString('[]', $output);
    }

    public function testWithoutSqlErrors(): void
    {
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['sql_query', '', 'SELECT * FROM `actor` WHERE `actor_id` = 1;'],
            ['options', null, null],
        ]);

        $this->getLintController()($request);

        $output = $this->getActualOutputForAssertion();
        $this->assertJson($output);
        $this->assertJsonStringEqualsJsonString('[]', $output);
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
        $this->assertNotFalse($expectedJson);

        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([
            ['sql_query', '', 'SELECT * FROM `actor` WHEREE `actor_id` = 1'],
            ['options', null, null],
        ]);

        $this->getLintController()($request);

        $output = $this->getActualOutputForAssertion();
        $this->assertJson($output);
        $this->assertJsonStringEqualsJsonString($expectedJson, $output);
    }

    private function getLintController(): LintController
    {
        return new LintController(new ResponseRenderer(), new Template());
    }
}
