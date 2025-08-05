<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins;

use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Current;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Exceptions\AuthenticationFailure;
use PhpMyAdmin\Exceptions\ExitException;
use PhpMyAdmin\Http\Factory\ServerRequestFactory;
use PhpMyAdmin\Http\Response;
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
        /** @psalm-suppress DeprecatedMethod */
        $config = Config::getInstance();
        /** @psalm-suppress InaccessibleProperty */
        $config->config->debug->simple2fa = true;

        Current::$lang = 'en';
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            // phpcs:ignore Generic.Files.LineLength.TooLong
            "SELECT `config_data`, UNIX_TIMESTAMP(`timevalue`) ts FROM `db_pma`.`pma__userconfig` WHERE `username` = 'test_user'",
            [['{"2fa":{"backend":"simple","settings":[]}}', '1724620722']],
            ['config_data', 'ts'],
        );
        $dbiDummy->addResult('SELECT CURRENT_USER();', [['test_user@localhost']]);
        DatabaseInterface::$instance = $this->createDatabaseInterface($dbiDummy);

        $object = new class extends AuthenticationPlugin {
            public function showLoginForm(): Response|null
            {
                return null;
            }

            public function readCredentials(): bool
            {
                return false;
            }

            public function showFailure(AuthenticationFailure $failure): Response
            {
                throw new ExitException();
            }
        };

        $_SESSION['two_factor_check'] = false;

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::USER => 'test_user',
            RelationParameters::DATABASE => 'db_pma',
            RelationParameters::USER_CONFIG_WORK => true,
            RelationParameters::USER_CONFIG => 'pma__userconfig',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);
        $responseRenderer = ResponseRenderer::getInstance();
        $responseRenderer->setAjax(false);

        $request = ServerRequestFactory::create()->createServerRequest('GET', 'http://example.com/');

        $object->user = 'test_user';
        $response = $object->checkTwoFactor($request);

        self::assertNotNull($response);
        self::assertStringContainsString(
            'You have enabled two factor authentication, please confirm your login.',
            (string) $response->getBody(),
        );

        $dbiDummy->assertAllQueriesConsumed();
        $dbiDummy->assertAllSelectsConsumed();
    }

    public function testCheckTwoFactorConfirmation(): void
    {
        /** @psalm-suppress DeprecatedMethod */
        $config = Config::getInstance();
        /** @psalm-suppress InaccessibleProperty */
        $config->config->debug->simple2fa = true;

        Current::$lang = 'en';
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            // phpcs:ignore Generic.Files.LineLength.TooLong
            "SELECT `config_data`, UNIX_TIMESTAMP(`timevalue`) ts FROM `db_pma`.`pma__userconfig` WHERE `username` = 'test_user'",
            [['{"2fa":{"backend":"simple","settings":[]}}', '1724620722']],
            ['config_data', 'ts'],
        );
        DatabaseInterface::$instance = $this->createDatabaseInterface($dbiDummy);

        $object = new class extends AuthenticationPlugin {
            public function showLoginForm(): Response|null
            {
                return null;
            }

            public function readCredentials(): bool
            {
                return false;
            }

            public function showFailure(AuthenticationFailure $failure): Response
            {
                throw new ExitException();
            }
        };

        $_SESSION['two_factor_check'] = false;

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::USER => 'test_user',
            RelationParameters::DATABASE => 'db_pma',
            RelationParameters::USER_CONFIG_WORK => true,
            RelationParameters::USER_CONFIG => 'pma__userconfig',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);
        (new ReflectionProperty(ResponseRenderer::class, 'instance'))->setValue(null, null);
        $responseRenderer = ResponseRenderer::getInstance();
        $responseRenderer->setAjax(false);

        $request = ServerRequestFactory::create()->createServerRequest('POST', 'http://example.com/')
            ->withParsedBody(['2fa_confirm' => '1']);

        $object->user = 'test_user';
        $response = $object->checkTwoFactor($request);

        self::assertNull($response);

        $dbiDummy->assertAllQueriesConsumed();
        $dbiDummy->assertAllSelectsConsumed();
    }
}
