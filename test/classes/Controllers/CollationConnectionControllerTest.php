<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Config;
use PhpMyAdmin\Controllers\CollationConnectionController;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Url;

/**
 * @covers \PhpMyAdmin\Controllers\CollationConnectionController
 */
class CollationConnectionControllerTest extends AbstractTestCase
{
    public function testInvoke(): void
    {
        $_POST['collation_connection'] = 'utf8mb4_general_ci';

        $response = $this->createMock(ResponseRenderer::class);
        $response->expects($this->once())->method('header')
            ->with('Location: index.php?route=/' . Url::getCommonRaw([], '&'));

        $config = $this->createMock(Config::class);
        $config->expects($this->once())->method('setUserValue')
            ->with(null, 'DefaultConnectionCollation', 'utf8mb4_general_ci', 'utf8mb4_unicode_ci');

        (new CollationConnectionController($response, new Template(), $config))();
    }
}
