<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database\Structure;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\Database\Structure\RealRowCountController;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\DbTableExists;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseStub;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(RealRowCountController::class)]
class RealRowCountControllerTest extends AbstractTestCase
{
    public function testRealRowCount(): void
    {
        Config::getInstance()->selectedServer['DisableIS'] = true;
        Current::$database = 'world';
        $_REQUEST['table'] = 'City';

        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addSelectDb('world');
        $dbiDummy->addSelectDb('world');
        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;

        $response = new ResponseStub();

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/')
            ->withQueryParams(['db' => 'world', 'table' => 'City', 'ajax_request' => '1']);

        (new RealRowCountController($response, $dbi, new DbTableExists($dbi)))($request);

        $json = $response->getJSONResult();
        self::assertSame('4,079', $json['real_row_count']);

        $_REQUEST['real_row_count_all'] = 'on';

        (new RealRowCountController($response, $dbi, new DbTableExists($dbi)))($request);

        $json = $response->getJSONResult();
        $expected = [
            ['table' => 'City', 'row_count' => '4,079'],
            ['table' => 'Country', 'row_count' => '239'],
            ['table' => 'CountryLanguage', 'row_count' => '984'],
        ];
        self::assertSame($expected, $json['real_row_count_all']);

        $dbiDummy->assertAllSelectsConsumed();
    }
}
