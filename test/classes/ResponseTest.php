<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Response;

/**
 * @covers \PhpMyAdmin\Response
 */
class ResponseTest extends AbstractTestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $GLOBALS['config']->enableBc();
        $GLOBALS['lang'] = 'en';
        $GLOBALS['server'] = 1;
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
    }

    public function testSetAjax(): void
    {
        $_REQUEST = [];
        $response = Response::getInstance();
        $response->setAjax(true);
        $this->assertTrue($response->isAjax());
        $response->setAjax(false);
        $this->assertFalse($response->isAjax());
    }
}
