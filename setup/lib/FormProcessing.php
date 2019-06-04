<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Formset processing library
 *
 * @package PhpMyAdmin-Setup
 */
namespace PhpMyAdmin\Setup;

use PhpMyAdmin\Config\FormDisplay;
use PhpMyAdmin\Core;
use PhpMyAdmin\Url;
use PhpMyAdmin\Response;

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

        if (!$form_display->process(false)) {
            // handle form view and failed POST
            echo $form_display->getDisplay(true, true);
            return;
        }

        // check for form errors
        if (!$form_display->hasErrors()) {
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
        ?>
        <div class="error">
            <h4><?php echo __('Warning') ?></h4>
            <?php echo __('Submitted form contains errors') ?><br />
            <a href="<?php echo Url::getCommon(array('page' => $page, 'formset' => $formset, 'id' => $formId, 'mode' => 'revert')) ?>">
                <?php echo __('Try to revert erroneous fields to their default values') ?>
            </a>
        </div>
        <?php echo $form_display->displayErrors() ?>
        <a class="btn" href="index.php<?php echo Url::getCommon() ?>">
            <?php echo __('Ignore errors') ?>
        </a>
        &nbsp;
        <a class="btn" href="<?php echo Url::getCommon(array('page' => $page, 'formset' => $formset, 'id' => $formId, 'mode' => 'edit')) ?>">
            <?php echo __('Show form') ?>
        </a>
        <?php
    }
}
