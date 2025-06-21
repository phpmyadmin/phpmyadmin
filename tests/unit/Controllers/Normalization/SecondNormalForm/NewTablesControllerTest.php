<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Normalization\SecondNormalForm;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Normalization\SecondNormalForm\NewTablesController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;

use function json_encode;

#[CoversClass(NewTablesController::class)]
class NewTablesControllerTest extends AbstractTestCase
{
    public function testDefault(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'test_table';

        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;
        $response = new ResponseRenderer();
        $template = new Template();
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'pd' => json_encode(['ID, task' => [], 'task' => ['timestamp']]),
            ]);
        $relation = new Relation($dbi);
        $controller = new NewTablesController(
            $response,
            new Normalization($dbi, $relation, new Transformations($dbi, $relation), $template),
        );
        $controller($request);

        // phpcs:disable Generic.Files.LineLength.TooLong
        self::assertSame(
            '<p><b>In order to put the original table \'test_table\' into Second normal form we need to create the following tables:</b></p><p><input type="text" name="ID, task" value="test_table">( <u>ID, task</u> )<p><input type="text" name="task" value="table2">( <u>task</u>, timestamp )',
            $response->getHTMLResult(),
        );
        // phpcs:enable
    }
}
