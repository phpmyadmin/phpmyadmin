<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\Setup\SetupFormList;
use PhpMyAdmin\Core;
use PhpMyAdmin\Setup\FormProcessing;
use function ob_get_clean;
use function ob_start;
use function is_string;

class FormController extends AbstractController
{
    /**
     * @param array $params Request parameters
     *
     * @return string HTML
     */
    public function index(array $params): string
    {
        $pages = $this->getPages();

        $formset = isset($params['formset']) && is_string($params['formset']) ? $params['formset'] : '';

        $formClass = SetupFormList::get($formset);
        if ($formClass === null) {
            Core::fatalError(__('Incorrect form specified!'));
        }

        ob_start();
        /** @var BaseForm $form */
        $form = new $formClass($this->config);
        FormProcessing::process($form);
        $page = ob_get_clean();

        return $this->template->render('setup/form/index', [
            'formset' => $formset,
            'pages' => $pages,
            'name' => $form::getName(),
            'page' => $page,
        ]);
    }
}
