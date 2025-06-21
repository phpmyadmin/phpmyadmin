<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Normalization\SecondNormalForm;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Normalization\SecondNormalForm\FirstStepController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FirstStepController::class)]
class FirstStepControllerTest extends AbstractTestCase
{
    public function testDefault(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'test_table';

        $dbi = $this->createDatabaseInterface();
        DatabaseInterface::$instance = $dbi;
        $response = new ResponseRenderer();
        $template = new Template();

        $relation = new Relation($dbi);
        $controller = new FirstStepController(
            $response,
            new Normalization($dbi, $relation, new Transformations($dbi, $relation), $template),
        );
        $controller(self::createStub(ServerRequest::class));

        self::assertSame([
            'legendText' => 'Step 2.1 Find partial dependencies',
            'headText' => 'No partial dependencies possible as the primary key ( id ) has just one column.<br>',
            'subText' => '',
            'extra' => '<h3>Table is already in second normal form.</h3>',
            'primary_key' => 'id',
        ], $response->getJSONResult());
    }
}
