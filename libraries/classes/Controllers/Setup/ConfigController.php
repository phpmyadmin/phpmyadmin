<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use PhpMyAdmin\Config\FormDisplayTemplate;
use PhpMyAdmin\Setup\ConfigGenerator;
use function is_string;

class ConfigController extends AbstractController
{
    /**
     * @param array $params Request parameters
     *
     * @return string HTML
     */
    public function index(array $params): string
    {
        $formset = isset($params['formset']) && is_string($params['formset']) ? $params['formset'] : '';
        $eol = isset($params['eol']) && $params['eol'] === 'win' ? 'win' : 'unix';

        $pages = $this->getPages();

        $formDisplayTemplate = new FormDisplayTemplate($GLOBALS['PMA_Config']);

        $formTop = $formDisplayTemplate->displayFormTop('config.php');
        $fieldsetTop = $formDisplayTemplate->displayFieldsetTop(
            'config.inc.php',
            '',
            null,
            ['class' => 'simple']
        );
        $formBottom = $formDisplayTemplate->displayFieldsetBottom(false);
        $fieldsetBottom = $formDisplayTemplate->displayFormBottom();

        $config = ConfigGenerator::getConfigFile($this->config);

        return $this->template->render('setup/config/index', [
            'formset' => $formset,
            'pages' => $pages,
            'form_top_html' => $formTop,
            'fieldset_top_html' => $fieldsetTop,
            'form_bottom_html' => $formBottom,
            'fieldset_bottom_html' => $fieldsetBottom,
            'eol' => $eol,
            'config' => $config,
        ]);
    }
}
