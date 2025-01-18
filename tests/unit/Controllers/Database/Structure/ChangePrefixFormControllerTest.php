<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database\Structure;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Controllers\Database\Structure\ChangePrefixFormController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\Factory\ResponseFactory;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(ChangePrefixFormController::class)]
final class ChangePrefixFormControllerTest extends AbstractTestCase
{
    public function testChangePrefixModal(): void
    {
        Current::$database = 'test_db';

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withQueryParams(['db' => 'test_db'])
            ->withParsedBody(['selected_tbl' => ['test_table']]);

        $template = new Template();
        $controller = new ChangePrefixFormController(new ResponseRenderer(), ResponseFactory::create(), $template);
        $response = $controller($request);

        $expected = $template->render('database/structure/change_prefix_form', [
            'route' => '/database/structure/replace-prefix',
            'url_params' => ['db' => 'test_db', 'selected' => ['test_table']],
        ]);

        self::assertSame(StatusCodeInterface::STATUS_OK, $response->getStatusCode());
        self::assertSame(['text/html; charset=utf-8'], $response->getHeader('Content-Type'));
        self::assertSame($expected, (string) $response->getBody());
    }
}
