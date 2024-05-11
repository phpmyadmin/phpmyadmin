<?php

declare(strict_types=1);

namespace PhpMyAdmin\Setup;

use PhpMyAdmin\Config\ConfigFile;

final class SetupHelper
{
    public static function createConfigFile(): ConfigFile
    {
        $configFile = new ConfigFile();
        $configFile->setPersistKeys([
            'DefaultLang',
            'ServerDefault',
            'UploadDir',
            'SaveDir',
            'Servers/1/verbose',
            'Servers/1/host',
            'Servers/1/port',
            'Servers/1/socket',
            'Servers/1/auth_type',
            'Servers/1/user',
            'Servers/1/password',
        ]);

        return $configFile;
    }
}
