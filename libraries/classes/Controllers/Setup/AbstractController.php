<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use PhpMyAdmin\Config\ConfigFile;
use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\Setup\SetupFormList;
use PhpMyAdmin\Template;

use function in_array;

abstract class AbstractController
{
    public function __construct(protected ConfigFile $config, protected Template $template)
    {
    }

    /** @return mixed[] */
    protected function getPages(): array
    {
        $ignored = ['Config', 'Servers'];
        $pages = [];
        foreach (SetupFormList::getAllFormNames() as $formset) {
            if (in_array($formset, $ignored)) {
                continue;
            }

            /** @var BaseForm $formClass */
            $formClass = SetupFormList::get($formset);

            $pages[$formset] = ['name' => $formClass::getName(), 'formset' => $formset];
        }

        return $pages;
    }
}
