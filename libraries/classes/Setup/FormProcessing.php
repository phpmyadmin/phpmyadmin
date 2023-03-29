<?php
/**
 * Formset processing library
 */

declare(strict_types=1);

namespace PhpMyAdmin\Setup;

use PhpMyAdmin\Config\FormDisplay;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

use function in_array;
use function is_numeric;
use function is_string;

/**
 * PhpMyAdmin\Setup\FormProcessing class
 */
class FormProcessing
{
    /**
     * Processes forms registered in $form_display, handles error correction
     *
     * @param FormDisplay $formDisplay Form to display
     */
    public static function process(FormDisplay $formDisplay): void
    {
        if (isset($_GET['mode']) && $_GET['mode'] === 'revert') {
            // revert erroneous fields to their default values
            $formDisplay->fixErrors();
            $response = ResponseRenderer::getInstance();
            $response->disable();
            $response->generateHeader303('../setup/index.php' . Url::getCommonRaw(['route' => '/setup']));
        }

        if (! $formDisplay->process(false)) {
            // handle form view and failed POST
            echo $formDisplay->getDisplay();

            return;
        }

        // check for form errors
        if (! $formDisplay->hasErrors()) {
            $response = ResponseRenderer::getInstance();
            $response->disable();
            $response->generateHeader303('../setup/index.php' . Url::getCommonRaw(['route' => '/setup']));

            return;
        }

        // form has errors, show warning
        $page = 'index';
        if (isset($_GET['page']) && in_array($_GET['page'], ['form', 'config', 'servers'], true)) {
            $page = $_GET['page'];
        }

        $formset = isset($_GET['formset']) && is_string($_GET['formset']) ? $_GET['formset'] : '';
        $formId = isset($_GET['id']) && is_numeric($_GET['id']) && (int) $_GET['id'] >= 1 ? (int) $_GET['id'] : 0;
        if ($formId === 0 && $page === 'servers') {
            // we've just added a new server, get its id
            $formId = $formDisplay->getConfigFile()->getServerCount();
        }

        $urlParams = ['page' => $page, 'formset' => $formset, 'id' => $formId];

        $template = new Template();
        echo $template->render('setup/error', ['url_params' => $urlParams, 'errors' => $formDisplay->displayErrors()]);
    }
}
