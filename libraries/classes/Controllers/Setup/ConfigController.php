<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use PhpMyAdmin\Setup\ConfigGenerator;

use function is_scalar;

class ConfigController extends AbstractController
{
    /**
     * @param array $params Request parameters
     *
     * @return string HTML
     */
    public function __invoke(array $params): string
    {
        $pages = $this->getPages();

        static $hasCheckPageRefresh = false;
        if (! $hasCheckPageRefresh) {
            $hasCheckPageRefresh = true;
        }

        $config = ConfigGenerator::getConfigFile($this->config);

        return $this->template->render('setup/config/index', [
            'formset' => $params['formset'] ?? '',
            'pages' => $pages,
            'eol' => isset($params['eol']) && is_scalar($params['eol']) ? $params['eol'] : 'unix',
            'config' => $config,
            'has_check_page_refresh' => $hasCheckPageRefresh,
        ]);
    }
}
