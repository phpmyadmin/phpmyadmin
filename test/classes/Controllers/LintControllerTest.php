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
    public function testInvoke(): void
    {
        $_POST['sql_query'] = 'SELECT * FROM `actor` WHEREE `actor_id` = 1;';

        (new LintController(new ResponseRenderer(), new Template()))();

        $expectedJson = [
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
        ];

        $output = $this->getActualOutputForAssertion();
        $this->assertJson($output);
        $this->assertJsonStringEqualsJsonString(json_encode($expectedJson), $output);
    }
}
