<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Setup;

use PhpMyAdmin\Setup\SetupHelper;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SetupHelper::class)]
final class SetupHelperTest extends AbstractTestCase
{
    public function testCreateConfigFile(): void
    {
        $expected = [
            'DefaultLang' => 0,
            'ServerDefault' => 1,
            'UploadDir' => 2,
            'SaveDir' => 3,
            'Servers/1/verbose' => 4,
            'Servers/1/host' => 5,
            'Servers/1/port' => 6,
            'Servers/1/socket' => 7,
            'Servers/1/auth_type' => 8,
            'Servers/1/user' => 9,
            'Servers/1/password' => 10,
        ];

        $configFile = SetupHelper::createConfigFile();
        self::assertSame($expected, $configFile->getPersistKeysMap());
        self::assertNotSame($configFile, SetupHelper::createConfigFile());
    }

    public function testGetPages(): void
    {
        $expected = [
            'Export' => ['name' => 'Export', 'formset' => 'Export'],
            'Features' => ['name' => 'Features', 'formset' => 'Features'],
            'Import' => ['name' => 'Import', 'formset' => 'Import'],
            'Main' => ['name' => 'Main panel', 'formset' => 'Main'],
            'Navi' => ['name' => 'Navigation panel', 'formset' => 'Navi'],
            'Sql' => ['name' => 'SQL queries', 'formset' => 'Sql'],
        ];
        self::assertSame($expected, SetupHelper::getPages());
    }
}
