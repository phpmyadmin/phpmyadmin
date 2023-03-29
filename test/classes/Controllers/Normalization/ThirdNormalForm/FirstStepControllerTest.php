<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Normalization\ThirdNormalForm;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Normalization\ThirdNormalForm\FirstStepController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;

/** @covers \PhpMyAdmin\Controllers\Normalization\ThirdNormalForm\FirstStepController */
class FirstStepControllerTest extends AbstractTestCase
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
        $request = $this->createStub(ServerRequest::class);
        $request->method('getParsedBodyParam')->willReturnMap([['tables', null, ['test_table']]]);

        $controller = new FirstStepController(
            $response,
            $template,
            new Normalization($dbi, new Relation($dbi), new Transformations(), $template),
        );
        $controller($request);

        // phpcs:disable Generic.Files.LineLength.TooLong
        $this->assertSame([
            'legendText' => 'Step 3.1 Find transitive dependencies',
            'headText' => 'Please answer the following question(s) carefully to obtain a correct normalization.',
            'subText' => 'For each column below, please select the <b>minimal set</b> of columns among given set whose values combined together are sufficient to determine the value of the column.<br>Note: A column may have no transitive dependency, in that case you don\'t have to select any.',
            'extra' => '<b>\'name\' depends on:</b><br><form id="td_1" data-colname="name" data-tablename="test_table" class="smallIndent"><input type="checkbox" name="pd" value="name"><span>name</span><input type="checkbox" name="pd" value="datetimefield"><span>datetimefield</span></form><br><br><b>\'datetimefield\' depends on:</b><br><form id="td_2" data-colname="datetimefield" data-tablename="test_table" class="smallIndent"><input type="checkbox" name="pd" value="name"><span>name</span><input type="checkbox" name="pd" value="datetimefield"><span>datetimefield</span></form><br><br>',
        ], $response->getJSONResult());
        // phpcs:enable
    }
}
