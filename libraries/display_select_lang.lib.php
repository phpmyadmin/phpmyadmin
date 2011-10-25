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
 * Sorts available languages by their true english names
 *
 * @param array   the array to be sorted
 * @param mixed   a required parameter
 * @return  the sorted array
 * @access  private
 */
function PMA_language_cmp(&$a, &$b)
{
    return (strcmp($a[1], $b[1]));
} // end of the 'PMA_language_cmp()' function

/**
 * Displays for for language selection
 *
 * @access  public
 */
function PMA_select_language($use_fieldset = false, $show_doc = true)
{
    global $cfg, $lang;
    ?>

<form method="post" action="index.php" target="_parent">
    <?php
    $_form_params = array(
        'db' => $GLOBALS['db'],
        'table' => $GLOBALS['table'],
    );
    echo PMA_generate_common_hidden_inputs($_form_params);

    // For non-English, display "Language" with emphasis because it's
    // not a proper word in the current language; we show it to help
    // people recognize the dialog
    $language_title = __('Language')
        . (__('Language') != 'Language' ? ' - <em>Language</em>' : '');
    if ($show_doc) {
        $language_title .= PMA_showDocu('faq7_2');
    }
    if ($use_fieldset) {
        echo '<fieldset><legend xml:lang="en" dir="ltr">' . $language_title . '</legend>';
    } else {
        echo '<bdo xml:lang="en" dir="ltr">' . $language_title . ':</bdo>';
    }
    ?>

    <select name="lang" class="autosubmit" xml:lang="en" dir="ltr">
    <?php

    uasort($GLOBALS['available_languages'], 'PMA_language_cmp');
    foreach ($GLOBALS['available_languages'] as $id => $tmplang) {
        $lang_name = PMA_langName($tmplang);

        //Is current one active?
        if ($lang == $id) {
            $selected = ' selected="selected"';
        } else {
            $selected = '';
        }

        echo '        ';
        echo '<option value="' . $id . '"' . $selected . '>' . $lang_name
            . '</option>' . "\n";
    }
    ?>

    </select>
    <?php
    if ($use_fieldset) {
        echo '</fieldset>';
    }
    ?>

    <noscript>
    <?php
    if ($use_fieldset) {
        echo '<fieldset class="tblFooters">';
    }
    ?>

        <input type="submit" value="Go" />
    <?php
    if ($use_fieldset) {
        echo '</fieldset>';
    }
    ?>

    </noscript>
</form>
    <?php
} // End of function PMA_select_language
?>
