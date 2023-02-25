<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Normalization\FirstNormalForm;

use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Controllers\Normalization\FirstNormalForm\SecondStepController;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Normalization;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\Transformations;

/** @covers \PhpMyAdmin\Controllers\Normalization\FirstNormalForm\SecondStepController */
class SecondStepControllerTest extends AbstractTestCase
{
    public function testDefault(): void
    {
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['table'] = 'test_table';

        $dbi = $this->createDatabaseInterface();
        $GLOBALS['dbi'] = $dbi;
        $response = new ResponseRenderer();
        $template = new Template();

        $controller = new SecondStepController(
            $response,
            $template,
            new Normalization($dbi, new Relation($dbi), new Transformations(), $template),
        );
        $controller($this->createStub(ServerRequest::class));

        $this->assertSame([
            'legendText' => 'Step 1.2 Have a primary key',
            'headText' => 'Primary key already exists.',
            'subText' => 'Taking you to next step…',
            'hasPrimaryKey' => '1',
            'extra' => '',
        ], $response->getJSONResult());
    }
}
