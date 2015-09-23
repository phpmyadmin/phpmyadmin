<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Code for displaying language selection
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Compares the names of two languages.
 * Used by uasort in PMA_getLanguageSelectorHtml()
 *
 * @param array $a The first language being compared
 * @param array $b The second language being compared
 *
 * @return int the sorted array
 */
function PMA_languageCmp($a, $b)
{
    return strcmp($a[1], $b[1]);
}

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
    global $lang;

    $retval = '';

    // Display language selection only if there
    // is more than one language to choose from
    if (count($GLOBALS['available_languages']) > 1) {
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
            $language_title .= PMA_Util::showDocu('faq', 'faq7-2');
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

        uasort($GLOBALS['available_languages'], 'PMA_languageCmp');
        foreach ($GLOBALS['available_languages'] as $id => $tmplang) {
            $lang_name = PMA_languageName($tmplang);

            //Is current one active?
            if ($lang == $id) {
                $selected = ' selected="selected"';
            } else {
                $selected = '';
            }
            $retval .= '<option value="' . $id . '"' . $selected . '>';
            $retval .= $lang_name;
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

