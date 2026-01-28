<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config;

use PhpMyAdmin\Clock\Clock;
use PhpMyAdmin\Config;
use PhpMyAdmin\Config\UserPreferences;
use PhpMyAdmin\Config\UserPreferencesHandler;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\I18n\LanguageManager;
use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Theme\ThemeManager;
use PHPUnit\Framework\Attributes\CoversClass;

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
        $userPreferencesHandler->setUserValue(null, 'Lang', 'cs', 'en');
        $userPreferencesHandler->setUserValue('TEST_COOKIE_USER_VAL', 'Servers/1/hide_db', 'cfg_val_1');
        self::assertSame('cfg_val_1', $userPreferencesHandler->getUserValue('TEST_COOKIE_USER_VAL', 'fail'));
        $userPreferencesHandler->setUserValue(null, 'NavigationWidth', 300);
        self::assertSame(300, $config->settings['NavigationWidth']);
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
}
