<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers\Database\Structure;

use PhpMyAdmin\Controllers\Database\Structure\RealRowCountController;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer as ResponseStub;

/**
 * @covers \PhpMyAdmin\Controllers\Database\Structure\RealRowCountController
 */
class RealRowCountControllerTest extends AbstractTestCase
{
    public function testRealRowCount(): void
    {
        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['cfg']['Server']['DisableIS'] = true;
        $GLOBALS['is_db'] = true;
        $GLOBALS['db'] = 'world';

        $response = new ResponseStub();
        $response->setAjax(true);

        $_REQUEST['table'] = 'City';

        (new RealRowCountController($response, new Template(), 'world', $this->dbi))();

        $json = $response->getJSONResult();
        self::assertEquals('4,079', $json['real_row_count']);

        $_REQUEST['real_row_count_all'] = 'on';

        (new RealRowCountController($response, new Template(), 'world', $this->dbi))();

        $json = $response->getJSONResult();
        $expected = [
            ['table' => 'City', 'row_count' => '4,079'],
            ['table' => 'Country', 'row_count' => '239'],
            ['table' => 'CountryLanguage', 'row_count' => '984'],
        ];
        self::assertEquals($expected, $json['real_row_count_all']);
    }
}
