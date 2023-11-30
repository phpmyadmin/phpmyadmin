<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Exceptions\ExitException;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Plugins\AuthenticationPlugin;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

#[CoversClass(AuthenticationPlugin::class)]
final class AuthenticationPluginTest extends AbstractTestCase
{
    public function testCheckTwoFactor(): void
    {
        $GLOBALS['lang'] = 'en';
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult('SHOW TABLES FROM `phpmyadmin`;', [['pma__userconfig'], ['Tables_in_phpmyadmin']]);
        $dbiDummy->addSelectDb('phpmyadmin');
        $dbi = $this->createDatabaseInterface($dbiDummy);
        DatabaseInterface::$instance = $dbi;

        $object = new class extends AuthenticationPlugin {
            public function showLoginForm(): void
            {
            }

            public function readCredentials(): bool
            {
                return false;
            }
        };

        $_SESSION['two_factor_check'] = false;

        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);
        $responseRenderer = ResponseRenderer::getInstance();
        $responseRenderer->setAjax(false);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/');

        $object->user = 'test_user';
        try {
            $object->checkTwoFactor($request);
        } catch (ExitException) {
        }

        $response = $responseRenderer->response();
        self::assertStringContainsString(
            'You have enabled two factor authentication, please confirm your login.',
            (string) $response->getBody(),
        );
    }
}
