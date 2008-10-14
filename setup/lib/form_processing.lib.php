<?php
/**
 * Formset processing library
 *
 * @package    phpMyAdmin-setup
 * @author     Piotr Przybylski <piotrprz@gmail.com>
 * @license    http://www.gnu.org/licenses/gpl.html GNU GPL 2.0
 * @version    $Id$
 */

/**
 * Processes forms registered in $form_display, handles error correction
 *
 * @param FormDisplay $form_display
 */
function process_formset(FormDisplay $form_display) {
	if (filter_input(INPUT_GET, 'mode') == 'revert') {
        // revert erroneous fields to their default values
        $form_display->fixErrors();
        // drop post data
        header('HTTP/1.1 303 See Other');
        header('Location: index.php');
        exit;
    }
    if (!$form_display->process(false)) {
        // handle form view and failed POST
        $form_display->display(true, true);
    } else {
        // check for form errors
        if ($form_display->hasErrors()) {
            // form has errors, show warning
            $separator = PMA_get_arg_separator('html');
            $page = filter_input(INPUT_GET, 'page');
            $formset = filter_input(INPUT_GET, 'formset');
            $formset = $formset ? "{$separator}formset=$formset" : '';
            $id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
            if ($id === null && $page == 'servers') {
            	// we've just added a new server, get it's id
            	$id = ConfigFile::getInstance()->getServerCount();
            }
            $id = $id ? "{$separator}id=$id" : '';
            ?>
            <div class="warning">
                <h4><?php echo $GLOBALS['strSetupWarning'] ?></h4>
                <?php echo PMA_lang('error_form') ?><br />
                <a href="?page=<?php echo $page . $formset . $id . $separator ?>mode=revert"><?php echo PMA_lang('RevertErroneousFields') ?></a>
            </div>
            <?php $form_display->displayErrors() ?>
            <a class="btn" href="index.php"><?php echo PMA_lang('IgnoreErrors') ?></a>
            &nbsp;
            <a class="btn" href="?page=<?php echo $page . $formset . $id . $separator ?>mode=edit"><?php echo PMA_lang('ShowForm') ?></a>
            <?php
        } else {
            // drop post data
            header('HTTP/1.1 303 See Other');
            header('Location: index.php');
            exit;
        }
    }
}
?>