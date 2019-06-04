<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Formset processing library
 *
 * @package PhpMyAdmin-Setup
 */
declare(strict_types=1);

namespace PhpMyAdmin\Setup;

use PhpMyAdmin\Config\FormDisplay;
use PhpMyAdmin\Core;
use PhpMyAdmin\Response;
use PhpMyAdmin\Template;
use PhpMyAdmin\Url;

/**
 * PhpMyAdmin\Setup\FormProcessing class
 *
 * @package PhpMyAdmin-Setup
 */
class FormProcessing
{
    /**
     * Processes forms registered in $form_display, handles error correction
     *
     * @param FormDisplay $form_display Form to display
     *
     * @return void
     */
    public static function process(FormDisplay $form_display)
    {
        if (isset($_GET['mode']) && $_GET['mode'] == 'revert') {
            // revert erroneous fields to their default values
            $form_display->fixErrors();
            $response = Response::getInstance();
            $response->disable();
            $response->generateHeader303('index.php' . Url::getCommonRaw());
        }

        if (! $form_display->process(false)) {
            // handle form view and failed POST
            echo $form_display->getDisplay(true, true);
            return;
        }

        // check for form errors
        if (! $form_display->hasErrors()) {
            $response = Response::getInstance();
            $response->disable();
            $response->generateHeader303('index.php' . Url::getCommonRaw());
            return;
        }

        // form has errors, show warning
        $page = isset($_GET['page']) ? $_GET['page'] : '';
        $formset = isset($_GET['formset']) ? $_GET['formset'] : '';
        $formId = Core::isValid($_GET['id'], 'numeric') ? $_GET['id'] : '';
        if ($formId === null && $page == 'servers') {
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
