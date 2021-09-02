<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\Setup\SetupFormList;
use PhpMyAdmin\Core;
use PhpMyAdmin\Setup\FormProcessing;

use function __;
use function is_scalar;
use function ob_get_clean;
use function ob_start;

class FormController extends AbstractController
{
    /**
     * @param array $params Request parameters
     *
     * @return string HTML
     */
    public function __invoke(array $params): string
    {
        $pages = $this->getPages();

        $formset = isset($params['formset']) && is_scalar($params['formset']) ? (string) $params['formset'] : '';

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
            'formset' => $params['formset'] ?? '',
            'pages' => $pages,
            'name' => $form::getName(),
            'page' => $page,
        ]);
    }
}
