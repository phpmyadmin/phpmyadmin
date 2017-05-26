<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Formset processing library
 *
 * @package PhpMyAdmin-Setup
 */
use PMA\libraries\config\FormDisplay;
use PMA\libraries\URL;
use PMA\libraries\Response;

/**
 * Processes forms registered in $form_display, handles error correction
 *
 * @param FormDisplay $form_display Form to display
 *
 * @return void
 */
function PMA_Process_formset(FormDisplay $form_display)
{
    if (isset($_GET['mode']) && $_GET['mode'] == 'revert') {
        // revert erroneous fields to their default values
        $form_display->fixErrors();
        PMA_generateHeader303();
    }

    if (!$form_display->process(false)) {
        // handle form view and failed POST
        echo $form_display->getDisplay(true, true);
        return;
    }

    // check for form errors
    if (!$form_display->hasErrors()) {
        PMA_generateHeader303();
        return;
    }

    // form has errors, show warning
    $page = isset($_GET['page']) ? $_GET['page'] : '';
    $formset = isset($_GET['formset']) ? $_GET['formset'] : '';
    $formId = PMA_isValid($_GET['id'], 'numeric') ? $_GET['id'] : '';
    if ($formId === null && $page == 'servers') {
        // we've just added a new server, get its id
        $formId = $form_display->getConfigFile()->getServerCount();
    }
    ?>
    <div class="error">
        <h4><?php echo __('Warning') ?></h4>
        <?php echo __('Submitted form contains errors') ?><br />
        <a href="<?php echo URL::getCommon(array('page' => $page, 'formset' => $formset, 'id' => $formId, 'mode' => 'revert')) ?>">
            <?php echo __('Try to revert erroneous fields to their default values') ?>
        </a>
    </div>
    <?php echo $form_display->displayErrors() ?>
    <a class="btn" href="index.php<?php echo URL::getCommon() ?>">
        <?php echo __('Ignore errors') ?>
    </a>
    &nbsp;
    <a class="btn" href="<?php echo URL::getCommon(array('page' => $page, 'formset' => $formset, 'id' => $formId, 'mode' => 'edit')) ?>">
        <?php echo __('Show form') ?>
    </a>
    <?php
}

/**
 * Generate header for 303
 *
 * @return void
 */
function PMA_generateHeader303()
{
    $response = Response::getInstance();

    // drop post data
    $response->header('HTTP/1.1 303 See Other');
    $response->header('Location: index.php' . URL::getCommonRaw());

    if (!defined('TESTSUITE')) {
        exit;
    }
}
