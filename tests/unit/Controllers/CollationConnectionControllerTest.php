<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Config\UserPreferencesHandler;
use PhpMyAdmin\Controllers\CollationConnectionController;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Url;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(CollationConnectionController::class)]
class CollationConnectionControllerTest extends AbstractTestCase
{
    public function testInvoke(): void
    {
        $request = self::createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['collation_connection', null, 'utf8mb4_general_ci']]);

        $response = self::createMock(ResponseRenderer::class);
        $response->expects(self::once())->method('redirect')
            ->with('index.php?route=/' . Url::getCommonRaw([], '&'));
        $response->expects(self::once())->method('response')->willReturn(ResponseFactory::create()->createResponse());

        $userPreferencesHandler = self::createMock(UserPreferencesHandler::class);
        $userPreferencesHandler->expects(self::once())->method('setUserValue')
            ->with(null, 'DefaultConnectionCollation', 'utf8mb4_general_ci', 'utf8mb4_unicode_ci');

        (new CollationConnectionController($response, $userPreferencesHandler))($request);
    }
}
