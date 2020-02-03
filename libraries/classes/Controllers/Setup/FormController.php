<?php
/**
 * Holds the PhpMyAdmin\Controllers\Setup\FormController
 */
declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use PhpMyAdmin\Config\Forms\BaseForm;
use PhpMyAdmin\Config\Forms\Setup\SetupFormList;
use PhpMyAdmin\Core;
use PhpMyAdmin\Setup\FormProcessing;
use function ob_get_clean;
use function ob_start;

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

        $formset = Core::isValid($params['formset'], 'scalar') ? $params['formset'] : null;

        /** @var BaseForm $formClass */
        $formClass = SetupFormList::get($formset);
        if ($formClass === null) {
            Core::fatalError(__('Incorrect form specified!'));
        }

        ob_start();
        FormProcessing::process(new $formClass($this->config));
        $page = ob_get_clean();

        return $this->template->render('setup/form/index', [
            'formset' => $params['formset'] ?? '',
            'pages' => $pages,
            'name' => $formClass::getName(),
            'page' => $page,
        ]);
    }
}
