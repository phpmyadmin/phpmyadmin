<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config;

use PhpMyAdmin\Clock\Clock;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\UserPreferences;
use PhpMyAdmin\Config\UserPreferencesHandler;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\Error\Error;
use PhpMyAdmin\Error\ErrorHandler;
use PhpMyAdmin\I18n\LanguageManager;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Theme\ThemeManager;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

use function array_map;
use function array_values;

#[CoversClass(UserPreferencesHandler::class)]
final class UserPreferencesHandlerTest extends AbstractTestCase
{
    public function testSetUserValue(): void
    {
        $config = new Config();
        $dbi = $this->createDatabaseInterface();
        $userPreferencesHandler = new UserPreferencesHandler(
            $config,
            $dbi,
            new UserPreferences($dbi, new Relation($dbi, $config), new Template($config), $config, new Clock()),
            new LanguageManager($config),
            new ThemeManager(),
        );
        $userPreferencesHandler->setUserValue('Lang', 'cs', 'en');
        $userPreferencesHandler->setUserValue('Servers/1/hide_db', 'cfg_val_1', cookieName: 'TEST_COOKIE_USER_VAL');
        self::assertSame('cfg_val_1', $userPreferencesHandler->getUserValue('TEST_COOKIE_USER_VAL', 'fail'));
    }

    public function testUpdateConfigValue(): void
    {
        $config = new Config();
        $dbi = $this->createDatabaseInterface();
        $userPreferencesHandler = new UserPreferencesHandler(
            $config,
            $dbi,
            new UserPreferences($dbi, new Relation($dbi, $config), new Template($config), $config, new Clock()),
            new LanguageManager($config),
            new ThemeManager(),
        );
        $userPreferencesHandler->updateConfigValue('NavigationWidth', 300);
        self::assertSame(300, $config->config->NavigationWidth);
    }

    public function testGetUserValue(): void
    {
        $config = new Config();
        $dbi = $this->createDatabaseInterface();
        $userPreferencesHandler = new UserPreferencesHandler(
            $config,
            $dbi,
            new UserPreferences($dbi, new Relation($dbi, $config), new Template($config), $config, new Clock()),
            new LanguageManager($config),
            new ThemeManager(),
        );
        self::assertSame('val', $userPreferencesHandler->getUserValue('test_val', 'val'));
    }

    public function testLoadUserPreferencesReportsRejectedCollation(): void
    {
        $errorHandlerInstance = new ReflectionProperty(ErrorHandler::class, 'instance');
        $errorHandlerInstance->setValue(null, new ErrorHandler());

        $config = new Config();
        $dummyDbi = $this->createDbiDummy();
        $dummyDbi->addResult('SET collation_connection = \'unknown_ci\';', false);
        $dbi = $this->createDatabaseInterface($dummyDbi);
        $themeManager = new ThemeManager();

        $_SESSION['cache'] = [
            'server_1' => [
                'userprefs' => [
                    'DefaultConnectionCollation' => 'unknown_ci',
                    'ThemeDefault' => $themeManager->theme->getId(),
                ],
                'userprefs_type' => 'session',
                'config_mtime' => $config->sourceMtime,
            ],
        ];

        $userPreferencesHandler = new UserPreferencesHandler(
            $config,
            $dbi,
            new UserPreferences($dbi, new Relation($dbi, $config), new Template($config), $config, new Clock()),
            new LanguageManager($config),
            $themeManager,
        );
        $userPreferencesHandler->loadUserPreferences();

        $errorHandler = ErrorHandler::getInstance();
        $errorHandlerInstance->setValue(null, null);

        $dummyDbi->assertAllQueriesConsumed();
        self::assertSame(
            ['Failed to set configured collation connection!'],
            array_values(array_map(
                static fn (Error $error): string => $error->getMessage(),
                $errorHandler->getCurrentErrors(),
            )),
        );
    }
}
