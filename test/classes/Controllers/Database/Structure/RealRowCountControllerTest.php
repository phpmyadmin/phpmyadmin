<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\Database\Structure\RealRowCountController;
use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\DbiDummy;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseStub;

use function json_encode;

/** @covers \PhpMyAdmin\Controllers\Database\Structure\RealRowCountController */
class RealRowCountControllerTest extends AbstractTestCase
{
    protected DatabaseInterface $dbi;

    protected DbiDummy $dummyDbi;

    protected function setUp(): void
    {
        parent::setUp();

        $this->dummyDbi = $this->createDbiDummy();
        $this->dbi = $this->createDatabaseInterface($this->dummyDbi);
        $GLOBALS['dbi'] = $this->dbi;
    }

    public function testRealRowCount(): void
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
        $GLOBALS['is_db'] = true;
        $GLOBALS['db'] = 'world';

        $response = new ResponseStub();
        $response->setAjax(true);

        $_REQUEST['table'] = 'City';

        (new RealRowCountController($response, new Template(), $this->dbi))($this->createStub(ServerRequest::class));

        $json = $response->getJSONResult();
        $this->assertEquals('4,079', $json['real_row_count']);

        $_REQUEST['real_row_count_all'] = 'on';

        (new RealRowCountController($response, new Template(), $this->dbi))($this->createStub(ServerRequest::class));

        $json = $response->getJSONResult();
        $expected = [
            ['table' => 'City', 'row_count' => 4079],
            ['table' => 'Country', 'row_count' => 239],
            ['table' => 'CountryLanguage', 'row_count' => 984],
        ];
        $this->assertEquals(json_encode($expected), $json['real_row_count_all']);
    }
}
