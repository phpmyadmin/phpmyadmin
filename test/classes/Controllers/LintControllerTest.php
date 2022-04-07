<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Controllers\LintController;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;

use function json_encode;

/**
 * @covers \PhpMyAdmin\Controllers\LintController
 */
class LintControllerTest extends AbstractTestCase
{
    public function testWithoutParams(): void
    {
        $_POST = [];

        $this->getLintController()();

        $output = $this->getActualOutputForAssertion();
        $this->assertJson($output);
        $this->assertJsonStringEqualsJsonString('[]', $output);
    }

    public function testWithoutSqlErrors(): void
    {
        $_POST['sql_query'] = 'SELECT * FROM `actor` WHERE `actor_id` = 1;';

        $this->getLintController()();

        $output = $this->getActualOutputForAssertion();
        $this->assertJson($output);
        $this->assertJsonStringEqualsJsonString('[]', $output);
    }

    public function testWithSqlErrors(): void
    {
        $_POST['sql_query'] = 'SELECT * FROM `actor` WHEREE `actor_id` = 1;';

        $expectedJson = json_encode([
            [
                'message' => 'An alias was previously found. (near <code>`actor_id`</code>)',
                'fromLine' => 0,
                'fromColumn' => 29,
                'toLine' => 0,
                'toColumn' => 39,
                'severity' => 'error',
            ],
            [
                'message' => 'Unexpected token. (near <code>`actor_id`</code>)',
                'fromLine' => 0,
                'fromColumn' => 29,
                'toLine' => 0,
                'toColumn' => 39,
                'severity' => 'error',
            ],
            [
                'message' => 'Unexpected token. (near <code>=</code>)',
                'fromLine' => 0,
                'fromColumn' => 40,
                'toLine' => 0,
                'toColumn' => 41,
                'severity' => 'error',
            ],
            [
                'message' => 'Unexpected token. (near <code>1</code>)',
                'fromLine' => 0,
                'fromColumn' => 42,
                'toLine' => 0,
                'toColumn' => 43,
                'severity' => 'error',
            ],
        ]);
        $this->assertNotFalse($expectedJson);

        $this->getLintController()();

        $output = $this->getActualOutputForAssertion();
        $this->assertJson($output);
        $this->assertJsonStringEqualsJsonString($expectedJson, $output);
    }

    private function getLintController(): LintController
    {
        return new LintController(new ResponseRenderer(), new Template());
    }
}
