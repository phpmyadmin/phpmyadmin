<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Sql;

use Fig\Http\Message\StatusCodeInterface;
use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\PageSettings;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Sql\SqlController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SqlController::class)]
final class SqlControllerTest extends AbstractTestCase
{
    public function testWithMissingRequestParameters(): void
    {
        Current::$database = '';
        Current::$table = '';
        Current::$sqlQuery = '';
        $responseRenderer = new ResponseRenderer();
        $pageSettings = $this->createMock(PageSettings::class);
        $pageSettings->expects(self::once())->method('getErrorHTML')->willReturn('');
        $pageSettings->expects(self::once())->method('getHTML')->willReturn('');
        $config = new Config();
        $dbi = $this->createDatabaseInterface(config: $config);
        $relation = new Relation($dbi, $config);
        $bookmarkRepository = new BookmarkRepository($dbi, $relation, $config);
        $sqlController = new SqlController(
            $responseRenderer,
            self::createStub(Sql::class),
            $pageSettings,
            $bookmarkRepository,
            $config,
        );
        $request = ServerRequestFactory::create()->createServerRequest('GET', 'https://example.com/');
        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'HTML'
            <div class="alert alert-danger" role="alert">
              <img src="themes/dot.gif" title="" alt="" class="icon ic_s_error"> Missing parameter: sql_query<a href="index.php?route=/url&url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2Ffaq.html%23faqmissingparameters" target="documentation"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a><br>
            </div>

            HTML;
        //phpcs:enable

        $response = $sqlController($request);

        self::assertSame(StatusCodeInterface::STATUS_BAD_REQUEST, $response->getStatusCode());
        self::assertFalse($responseRenderer->hasSuccessState());
        self::assertStringContainsString($expected, (string) $response->getBody());
    }
}
