<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\Setup\ConfigGenerator;
use PhpMyAdmin\Setup\SetupHelper;
use PhpMyAdmin\Template;

use function is_string;

final class ConfigController
{
    public function __construct(private readonly Template $template)
    {
    }

    public function __invoke(ServerRequest $request): string
    {
        $pages = SetupHelper::getPages();

        static $hasCheckPageRefresh = false;
        if (! $hasCheckPageRefresh) {
            $hasCheckPageRefresh = true;
        }

        $configFile = SetupHelper::createConfigFile();

        $config = ConfigGenerator::getConfigFile($configFile);

        return $this->template->render('setup/config/index', [
            'formset' => $this->getFormSetParam($request->getQueryParam('formset')),
            'pages' => $pages,
            'eol' => $this->getEolParam($request->getQueryParam('eol')),
            'config' => $config,
            'has_check_page_refresh' => $hasCheckPageRefresh,
        ]);
    }

    private function getFormSetParam(mixed $formSetParam): string
    {
        return is_string($formSetParam) ? $formSetParam : '';
    }

    /** @psalm-return 'win'|'unix' */
    private function getEolParam(mixed $eolParam): string
    {
        return $eolParam === 'win' ? 'win' : 'unix';
    }
}
