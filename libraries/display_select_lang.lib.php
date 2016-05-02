<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Code for displaying language selection
 *
 * @package PhpMyAdmin
 */
use PMA\libraries\LanguageManager;

/**
 * Returns HTML code for the language selector
 *
 * @param boolean $use_fieldset whether to use fieldset for selection
 * @param boolean $show_doc     whether to show documentation links
 *
 * @return string
 *
 * @access  public
 */
function PMA_getLanguageSelectorHtml($use_fieldset = false, $show_doc = true)
{
    $retval = '';
    $available_languages = LanguageManager::getInstance()->sortedLanguages();

    // Display language selection only if there
    // is more than one language to choose from
    if (count($available_languages) > 1) {
        $retval .= '<form method="get" action="index.php" class="disableAjax">';

        $_form_params = array(
            'db' => $GLOBALS['db'],
            'table' => $GLOBALS['table'],
        );
        $retval .= PMA_URL_getHiddenInputs($_form_params);

        // For non-English, display "Language" with emphasis because it's
        // not a proper word in the current language; we show it to help
        // people recognize the dialog
        $language_title = __('Language')
            . (__('Language') != 'Language' ? ' - <em>Language</em>' : '');
        if ($show_doc) {
            $language_title .= PMA\libraries\Util::showDocu('faq', 'faq7-2');
        }
        if ($use_fieldset) {
            $retval .= '<fieldset><legend lang="en" dir="ltr">'
                . $language_title . '</legend>';
        } else {
            $retval .= '<bdo lang="en" dir="ltr"><label for="sel-lang">'
                . $language_title . ': </label></bdo>';
        }

        $retval .= '<select name="lang" class="autosubmit" lang="en"'
            . ' dir="ltr" id="sel-lang">';

        foreach ($available_languages as $language) {
            //Is current one active?
            if ($language->isActive()) {
                $selected = ' selected="selected"';
            } else {
                $selected = '';
            }
            $retval .= '<option value="' . strtolower($language->getCode()) . '"' . $selected . '>';
            $retval .= $language->getName();
            $retval .= '</option>';
        }

        $retval .= '</select>';

        if ($use_fieldset) {
            $retval .= '</fieldset>';
        }

        $retval .= '</form>';
    }
    return $retval;
}

