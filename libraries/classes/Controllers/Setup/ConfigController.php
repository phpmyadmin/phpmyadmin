<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use PhpMyAdmin\Config\FormDisplayTemplate;
use PhpMyAdmin\Core;
use PhpMyAdmin\Setup\ConfigGenerator;

class ConfigController extends AbstractController
{
    /**
     * @param array $params Request parameters
     *
     * @return string HTML
     */
    public function index(array $params): string
    {
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
            'formset' => $params['formset'] ?? '',
            'pages' => $pages,
            'form_top_html' => $formTop,
            'fieldset_top_html' => $fieldsetTop,
            'form_bottom_html' => $formBottom,
            'fieldset_bottom_html' => $fieldsetBottom,
            'eol' => Core::ifSetOr($params['eol'], 'unix'),
            'config' => $config,
        ]);
    }
}
