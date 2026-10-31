<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Sql;

use PhpMyAdmin\CheckUserPrivileges;
use PhpMyAdmin\Controllers\Sql\SqlController;
use PhpMyAdmin\Sql;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PHPUnit\Framework\Attributes\CoversClass;

/** @covers \PhpMyAdmin\Controllers\Sql\SqlController */
#[CoversClass(SqlController::class)]
final class SqlControllerTest extends AbstractTestCase
{
    public function testWithMissingRequestParameters(): void
    {
        $GLOBALS['db'] = '';
        $GLOBALS['table'] = '';
        $GLOBALS['sql_query'] = '';
        $responseRenderer = new ResponseRenderer();
        $checkUserPrivileges = $this->createMock(CheckUserPrivileges::class);
        $checkUserPrivileges->expects(self::once())->method('getPrivileges');
        $sqlController = new SqlController(
            $responseRenderer,
            new Template(),
            $this->createStub(Sql::class),
            $checkUserPrivileges,
            $this->createDatabaseInterface()
        );
        // phpcs:disable Generic.Files.LineLength.TooLong
        $expected = <<<'HTML'
<div class="alert alert-danger" role="alert">
  <img src="themes/dot.gif" title="" alt="" class="icon ic_s_error"> Missing parameter: sql_query<a href="./url.php?url=https%3A%2F%2Fdocs.phpmyadmin.net%2Fen%2Flatest%2Ffaq.html%23faqmissingparameters" target="documentation"><img src="themes/dot.gif" title="Documentation" alt="Documentation" class="icon ic_b_help"></a><br>
</div>

HTML;
        //phpcs:enable

        $sqlController();

        self::assertSame(400, $responseRenderer->getHttpResponseCode());
        self::assertFalse($responseRenderer->hasSuccessState());
        self::assertStringContainsString($expected, $responseRenderer->getHTMLResult());
    }
}
