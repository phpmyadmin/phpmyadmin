<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Normalization\FirstNormalForm;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Normalization\FirstNormalForm\ThirdStepController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;

/** @covers \PhpMyAdmin\Controllers\Normalization\FirstNormalForm\ThirdStepController */
class ThirdStepControllerTest extends AbstractTestCase
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

        $controller = new ThirdStepController(
            $response,
            $template,
            new Normalization($dbi, new Relation($dbi), new Transformations(), $template),
        );
        $controller($this->createStub(ServerRequest::class));

        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->assertSame([
            'legendText' => 'Step 1.3 Move repeating groups',
            'headText' => 'Do you have a group of two or more columns that are closely related and are all repeating the same attribute? For example, a table that holds data on books might have columns such as book_id, author1, author2, author3 and so on which form a repeating group. In this case a new table (book_id, author) should be created.',
            'subText' => 'Check the columns which form a repeating group. If no such group, click on \'No repeating group\'',
            'extra' => '<input type="checkbox" value="id">id [ int(11) ]<br><input type="checkbox" value="name">name [ varchar(20) ]<br><input type="checkbox" value="datetimefield">datetimefield [ datetime ]<br><br><input class="btn btn-secondary" type="submit" id="moveRepeatingGroup" value="Done"><input class="btn btn-secondary" type="submit" value="No repeating group" onclick="goToStep4();">',
            'primary_key' => '["id"]',
        ], $response->getJSONResult());
        // phpcs:enable
    }
}
