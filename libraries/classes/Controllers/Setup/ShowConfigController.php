<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Setup;

use PhpMyAdmin\Config\Forms\Setup\ConfigForm;
use PhpMyAdmin\Core;
use PhpMyAdmin\Http\ServerRequest;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Setup\ConfigGenerator;
use PhpMyAdmin\Url;

use function is_scalar;

final class ShowConfigController
{
    public function __invoke(ServerRequest $request): void
    {
        $form_display = new ConfigForm($GLOBALS['ConfigFile']);
        $form_display->save('Config');

        $response = ResponseRenderer::getInstance();
        $response->disable();

        if (isset($_POST['eol'])) {
            $_SESSION['eol'] = $_POST['eol'] === 'unix' ? 'unix' : 'win';
        }

        if (isset($_POST['submit_clear']) && is_scalar($_POST['submit_clear']) ? $_POST['submit_clear'] : '') {
            // Clear current config and return to main page
            $GLOBALS['ConfigFile']->resetConfigData();
            // drop post data
            $response->generateHeader303('index.php' . Url::getCommonRaw(['route' => '/setup']));

            return;
        }

        if (isset($_POST['submit_download']) && is_scalar($_POST['submit_download']) ? $_POST['submit_download'] : '') {
            // Output generated config file
            Core::downloadHeader('config.inc.php', 'text/plain');
            $response->disable();
            echo ConfigGenerator::getConfigFile($GLOBALS['ConfigFile']);

            return;
        }

        // Show generated config file in a <textarea>
        $response->generateHeader303('index.php' . Url::getCommonRaw(['route' => '/setup', 'page' => 'config']));
    }
}
