<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Controllers;

use PhpMyAdmin\Controllers\VersionCheckController;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Tests\Stubs\ResponseRenderer;
use PhpMyAdmin\VersionInformation;

use function json_encode;
use function time;

use const PHP_VERSION_ID;

/**
 * @covers \PhpMyAdmin\Controllers\VersionCheckController
 */
class VersionCheckControllerTest extends AbstractTestCase
{
    public function testInvoke(): void
    {
        $_GET = [];
        $GLOBALS['cfg']['VersionCheck'] = true;
        $versionInfo = [
            'date' => '2022-02-11',
            'version' => '5.1.3',
            'releases' => [
                [
                    'date' => '2022-02-11',
                    'php_versions' => '>=7.1,<8.1',
                    'version' => '5.1.3',
                    'mysql_versions' => '>=5.5',
                ],
                [
                    'date' => '2022-02-11',
                    'php_versions' => '>=5.5,<8.0',
                    'version' => '4.9.10',
                    'mysql_versions' => '>=5.5',
                ],
            ],
        ];
        $_SESSION['cache'] = [];
        $_SESSION['cache']['version_check'] = [
            'response' => json_encode($versionInfo),
            'timestamp' => time(),
        ];

        (new VersionCheckController(new ResponseRenderer(), new Template(), new VersionInformation()))();

        $output = $this->getActualOutputForAssertion();
        $this->assertTrue(isset($_GET['ajax_request']));
        if (PHP_VERSION_ID < 80100) {
            $this->assertSame('{"version":"5.1.3","date":"2022-02-11"}', $output);
        } else {
            $this->assertSame('{"version":"","date":""}', $output);
        }
    }
}
