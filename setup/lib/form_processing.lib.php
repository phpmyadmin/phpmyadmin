<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Formset processing library
 *
 * @package PhpMyAdmin-Setup
 */

/**
 * Processes forms registered in $form_display, handles error correction
 *
 * @param FormDisplay $form_display Form to display
 *
 * @return void
 */
function PMA_Process_formset(FormDisplay $form_display)
{
    if (filter_input(INPUT_GET, 'mode') == 'revert') {
        // revert erroneous fields to their default values
        $form_display->fixErrors();
        PMA_generateHeader303();
    }

    if (!$form_display->process(false)) {
        // handle form view and failed POST
        $form_display->display(true, true);
        return;
    }

    // check for form errors
    if (!$form_display->hasErrors()) {
        PMA_generateHeader303();
        return;
    }

    // form has errors, show warning
    $separator = PMA_URL_getArgSeparator('html');
    $page = filter_input(INPUT_GET, 'page');
    $formset = filter_input(INPUT_GET, 'formset');
    $formset = $formset ? "{$separator}formset=$formset" : '';
    $formId = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    if ($formId === null && $page == 'servers') {
        // we've just added a new server, get its id
        $formId = $form_display->getConfigFile()->getServerCount();
    }
    $formId = $formId ? "{$separator}id=$formId" : '';
    ?>
    <div class="error">
        <h4><?php echo __('Warning') ?></h4>
        <?php echo __('Submitted form contains errors') ?><br />
        <a href="?page=<?php echo $page . $formset . $formId . $separator ?>mode=revert">
            <?php echo __('Try to revert erroneous fields to their default values')
            ?>
        </a>
    </div>
    <?php $form_display->displayErrors() ?>
    <a class="btn" href="index.php"><?php echo __('Ignore errors') ?></a>
    &nbsp;
    <a class="btn" href="?page=<?php echo $page . $formset . $formId
        . $separator ?>mode=edit"><?php echo __('Show form') ?></a>
    <?php
}

/**
 * Generate header for 303
 *
 * @return void
 */
function PMA_generateHeader303()
{
    // drop post data
    header('HTTP/1.1 303 See Other');
    header('Location: index.php');

    if (!defined('TESTSUITE')) {
        exit;
    }
}
?>
