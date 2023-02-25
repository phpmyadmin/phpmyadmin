<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Normalization\SecondNormalForm;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Normalization\SecondNormalForm\FirstStepController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;

/** @covers \PhpMyAdmin\Controllers\Normalization\SecondNormalForm\FirstStepController */
class FirstStepControllerTest extends AbstractTestCase
{
    public function testDefault(): void
    {
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';

        $dbi = $this->createDatabaseInterface();
        $GLOBALS['dbi'] = $dbi;
        $response = new ResponseRenderer();
        $template = new Template();

        $controller = new FirstStepController(
            $response,
            $template,
            new Normalization($dbi, new Relation($dbi), new Transformations(), $template),
        );
        $controller($this->createStub(ServerRequest::class));

        $this->assertSame([
            'legendText' => 'Step 2.1 Find partial dependencies',
            'headText' => 'No partial dependencies possible as the primary key ( id ) has just one column.<br>',
            'subText' => '',
            'extra' => '<h3>Table is already in second normal form.</h3>',
            'primary_key' => 'id',
        ], $response->getJSONResult());
    }
}
