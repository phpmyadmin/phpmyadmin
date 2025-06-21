<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Normalization\SecondNormalForm;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Normalization\SecondNormalForm\CreateNewTablesController;
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

#[CoversClass(CreateNewTablesController::class)]
class CreateNewTablesControllerTest extends AbstractTestCase
{
    public function testDefault(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'test_table';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');
        $dbiDummy->addResult('CREATE TABLE `batch_log2` SELECT DISTINCT `ID`, `task` FROM `test_table`;', true);
        $dbiDummy->addResult('CREATE TABLE `table2` SELECT DISTINCT `task`, `timestamp` FROM `test_table`;', true);
        $dbiDummy->addResult('DROP TABLE `test_table`', true);

        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;
        $response = new ResponseRenderer();
        $template = new Template();
        $request = ServerRequestFactory::create()->createServerRequest('POST', 'https://example.com/')
            ->withParsedBody([
                'pd' => json_encode(['ID, task' => [], 'task' => ['timestamp']]),
                'newTablesName' => json_encode(['ID, task' => 'batch_log2', 'task' => 'table2']),
            ]);

        $relation = new Relation($dbi);
        $controller = new CreateNewTablesController(
            $response,
            new Normalization($dbi, $relation, new Transformations($dbi, $relation), $template),
        );
        $controller($request);

        self::assertSame([
            'legendText' => 'End of step',
            'headText' => '<h3>The second step of normalization is complete for table \'test_table\'.</h3>',
            'queryError' => false,
            'extra' => '',
        ], $response->getJSONResult());
    }
}
