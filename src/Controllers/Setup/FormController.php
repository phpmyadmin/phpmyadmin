<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use PhpMyAdmin\Config\Forms\Setup\SetupFormList;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\LanguageManager;
use PhpMyAdmin\Setup\FormProcessing;
use PhpMyAdmin\Setup\SetupHelper;
use PhpMyAdmin\Template;

use function __;
use function is_string;
use function ob_get_clean;
use function ob_start;

final class FormController
{
    public function __construct(private readonly Template $template)
    {
    }

    public function __invoke(ServerRequest $request): string
    {
        $pages = SetupHelper::getPages();

        $formSet = $this->getFormSetParam($request->getQueryParam('formset'));

        $formClass = SetupFormList::get($formSet);
        if ($formClass === null) {
            return $this->template->render('error/generic', [
                'lang' => $GLOBALS['lang'] ?? 'en',
                'dir' => LanguageManager::$textDir,
                'error_message' => __('Incorrect form specified!'),
            ]);
        }

        $configFile = SetupHelper::createConfigFile();

        ob_start();
        $form = new $formClass($configFile);
        FormProcessing::process($form);
        $page = ob_get_clean();

        return $this->template->render('setup/form/index', [
            'formset' => $formSet,
            'pages' => $pages,
            'name' => $form::getName(),
            'page' => $page,
        ]);
    }

    private function getFormSetParam(mixed $formSetParam): string
    {
        return is_string($formSetParam) ? $formSetParam : '';
    }
}
