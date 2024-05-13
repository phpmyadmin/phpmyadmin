<?php

declare(strict_types=1);

namespace PhpMyAdmin\Setup;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\Setup\SetupFormList;

use function in_array;

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

    /** @return string[][] */
    public static function getPages(): array
    {
        $ignored = ['Config', 'Servers'];
        $pages = [];
        foreach (SetupFormList::getAllFormNames() as $formset) {
            if (in_array($formset, $ignored, true)) {
                continue;
            }

            /** @var BaseForm $formClass */
            $formClass = SetupFormList::get($formset);

            $pages[$formset] = ['name' => $formClass::getName(), 'formset' => $formset];
        }

        return $pages;
    }
}
