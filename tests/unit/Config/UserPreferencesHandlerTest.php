<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Config;

use PhpMyAdmin\Config;
use PhpMyAdmin\Config\UserPreferencesHandler;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(UserPreferencesHandler::class)]
final class UserPreferencesHandlerTest extends AbstractTestCase
{
    public function testSetUserValue(): void
    {
        $config = new Config();
        $userPreferencesHandler = new UserPreferencesHandler($config);
        $userPreferencesHandler->setUserValue(null, 'lang', 'cs', 'en');
        $userPreferencesHandler->setUserValue('TEST_COOKIE_USER_VAL', '', 'cfg_val_1');
        self::assertSame('cfg_val_1', $userPreferencesHandler->getUserValue('TEST_COOKIE_USER_VAL', 'fail'));
        $userPreferencesHandler->setUserValue(null, 'NavigationWidth', 300);
        self::assertSame(300, $config->settings['NavigationWidth']);
    }

    public function testGetUserValue(): void
    {
        $userPreferencesHandler = new UserPreferencesHandler(new Config());
        self::assertSame('val', $userPreferencesHandler->getUserValue('test_val', 'val'));
    }
}
