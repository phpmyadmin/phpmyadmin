<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Table\Structure;

use PhpMyAdmin\Controllers\Table\Structure\ReservedWordCheckController;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseStub;
use PHPUnit\Framework\Attributes\CoversClass;

/**
 * @covers \PhpMyAdmin\Controllers\Table\Structure\ReservedWordCheckController
 */
#[CoversClass(ReservedWordCheckController::class)]
class ReservedWordCheckControllerTest extends AbstractTestCase
{
    public function testWithoutFieldName(): void
    {
        $_POST = [];

        $response = new ResponseStub();
        $controller = new ReservedWordCheckController($response, new Template(), 'test_db', 'test_table');
        $controller();

        self::assertFalse($response->hasSuccessState());
        self::assertSame(400, $response->getHttpResponseCode());
        self::assertSame(['message' => 'Missing parameter: field_name'], $response->getJSONResult());
    }
}
