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

use function is_numeric;

/**
 * PhpMyAdmin\Setup\FormProcessing class
 */
class FormProcessing
{
    /**
     * Processes forms registered in $form_display, handles error correction
     *
     * @param FormDisplay $form_display Form to display
     */
    public static function process(FormDisplay $form_display): void
    {
        if (isset($_GET['mode']) && $_GET['mode'] === 'revert') {
            // revert erroneous fields to their default values
            $form_display->fixErrors();
            $response = ResponseRenderer::getInstance();
            $response->disable();
            $response->generateHeader303('index.php' . Url::getCommonRaw());
        }

        if (! $form_display->process(false)) {
            // handle form view and failed POST
            echo $form_display->getDisplay();

            return;
        }

        // check for form errors
        if (! $form_display->hasErrors()) {
            $response = ResponseRenderer::getInstance();
            $response->disable();
            $response->generateHeader303('index.php' . Url::getCommonRaw());

            return;
        }

        // form has errors, show warning
        $page = $_GET['page'] ?? '';
        $formset = $_GET['formset'] ?? '';
        $formId = isset($_GET['id']) && is_numeric($_GET['id']) ? (int) $_GET['id'] : null;
        if ($formId === null && $page === 'servers') {
            // we've just added a new server, get its id
            $formId = $form_display->getConfigFile()->getServerCount();
        }

        $urlParams = [
            'page' => $page,
            'formset' => $formset,
            'id' => $formId,
        ];

        $template = new Template();
        echo $template->render('setup/error', [
            'url_params' => $urlParams,
            'errors' => $form_display->displayErrors(),
        ]);
    }
}
