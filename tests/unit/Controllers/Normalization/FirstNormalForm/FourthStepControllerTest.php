<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Normalization\FirstNormalForm;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Normalization\FirstNormalForm\FourthStepController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(FourthStepController::class)]
class FourthStepControllerTest extends AbstractTestCase
{
    public function testDefault(): void
    {
        Current::$database = 'test_db';
        Current::$table = 'test_table';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');

        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;
        $response = new ResponseRenderer();
        $template = new Template();

        $relation = new Relation($dbi);
        $controller = new FourthStepController(
            $response,
            new Normalization($dbi, $relation, new Transformations($dbi, $relation), $template),
        );
        $controller(self::createStub(ServerRequest::class));

        // phpcs:disable Generic.Files.LineLength.TooLong
        self::assertSame([
            'legendText' => 'Step 1.4 Remove redundant columns',
            'headText' => 'Do you have a group of columns which on combining gives an existing column? For example, if you have first_name, last_name and full_name then combining first_name and last_name gives full_name which is redundant.',
            'subText' => 'Check the columns which are redundant and click on remove. If no redundant column, click on \'No redundant column\'',
            'extra' => '<input type="checkbox" value="id">id [ int(11) ]<br><input type="checkbox" value="name">name [ varchar(20) ]<br><input type="checkbox" value="datetimefield">datetimefield [ datetime ]<br><br><input class="btn btn-secondary" type="submit" id="removeRedundant" value="Remove selected"><input class="btn btn-secondary" type="submit" id="noRedundantColumn" value="No redundant column">',
        ], $response->getJSONResult());
        // phpcs:enable
    }
}
