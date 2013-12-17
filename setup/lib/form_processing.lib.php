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
 * @param FormDisplay $form_display
 *
 * @return void
 */
function process_formset(FormDisplay $form_display)
{
    if (filter_input(INPUT_GET, 'mode') == 'revert') {
        // revert erroneous fields to their default values
        $form_display->fixErrors();
        // drop post data
        header('HTTP/1.1 303 See Other');
        header('Location: index.php');

        if (!defined('TESTSUITE')) {
            exit;
        }
    }
    if (!$form_display->process(false)) {
        // handle form view and failed POST
        $form_display->display(true, true);
    } else {
        // check for form errors
        if ($form_display->hasErrors()) {
            // form has errors, show warning
            $separator = PMA_URL_getArgSeparator('html');
            $page = filter_input(INPUT_GET, 'page');
            $formset = filter_input(INPUT_GET, 'formset');
            $formset = $formset ? "{$separator}formset=$formset" : '';
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if ($id === null && $page == 'servers') {
                // we've just added a new server, get it's id
                $id = $form_display->getConfigFile()->getServerCount();
            }
            $id = $id ? "{$separator}id=$id" : '';
            ?>
            <div class="error">
                <h4><?php echo __('Warning') ?></h4>
                <?php echo __('Submitted form contains errors') ?><br />
                <a href="?page=<?php echo $page . $formset . $id . $separator ?>mode=revert"><?php echo __('Try to revert erroneous fields to their default values') ?></a>
            </div>
            <?php $form_display->displayErrors() ?>
            <a class="btn" href="index.php"><?php echo __('Ignore errors') ?></a>
            &nbsp;
            <a class="btn" href="?page=<?php echo $page . $formset . $id . $separator ?>mode=edit"><?php echo __('Show form') ?></a>
            <?php
        } else {
            // drop post data
            header('HTTP/1.1 303 See Other');
            header('Location: index.php');
            if (!defined('TESTSUITE')) {
                exit;
            }
        }
    }
}
?>
