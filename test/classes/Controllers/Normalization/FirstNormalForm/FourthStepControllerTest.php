<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Normalization\FirstNormalForm;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Normalization\FirstNormalForm\FourthStepController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;

/** @covers \PhpMyAdmin\Controllers\Normalization\FirstNormalForm\FourthStepController */
class FourthStepControllerTest extends AbstractTestCase
{
    public function testDefault(): void
    {
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('test_db');

        $dbi = $this->createDatabaseInterface($dbiDummy);
        $GLOBALS['dbi'] = $dbi;
        $response = new ResponseRenderer();
        $template = new Template();

        $controller = new FourthStepController(
            $response,
            $template,
            new Normalization($dbi, new Relation($dbi), new Transformations(), $template),
        );
        $controller($this->createStub(ServerRequest::class));

        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->assertSame([
            'legendText' => 'Step 1.4 Remove redundant columns',
            'headText' => 'Do you have a group of columns which on combining gives an existing column? For example, if you have first_name, last_name and full_name then combining first_name and last_name gives full_name which is redundant.',
            'subText' => 'Check the columns which are redundant and click on remove. If no redundant column, click on \'No redundant column\'',
            'extra' => '<input type="checkbox" value="id">id [ int(11) ]<br><input type="checkbox" value="name">name [ varchar(20) ]<br><input type="checkbox" value="datetimefield">datetimefield [ datetime ]<br><br><input class="btn btn-secondary" type="submit" id="removeRedundant" value="Remove selected"><input class="btn btn-secondary" type="submit" value="No redundant column" onclick="goToFinish1NF();">',
        ], $response->getJSONResult());
        // phpcs:enable
    }
}
