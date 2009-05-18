<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Code for displaying language selection
 *
 * @version $Id$
 * @package phpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Sorts available languages by their true english names
 *
 * @param   array   the array to be sorted
 * @param   mixed   a required parameter
 * @return  the sorted array
 * @access  private
 */
function PMA_language_cmp(&$a, &$b) {
    return (strcmp($a[1], $b[1]));
} // end of the 'PMA_language_cmp()' function

/**
 * Displays for for language selection
 *
 * @access  public
 */
function PMA_select_language($use_fieldset = FALSE, $show_doc = TRUE) {
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
    $language_title = $GLOBALS['strLanguage']
        . ($GLOBALS['strLanguage'] != 'Language' ? ' - <em>Language</em>' : '');
    if ($show_doc) {
        $language_title .= ' <a href="./translators.html" target="documentation">' .
            ($cfg['ReplaceHelpImg']
                ? '<img class="icon" src="' . $GLOBALS['pmaThemeImage'] . 'b_info.png" width="11" height="11" alt="Info" />'
                : '(*)') . '</a>';
    }
    if ($use_fieldset) {
        echo '<fieldset><legend xml:lang="en" dir="ltr">' . $language_title . '</legend>';
    } else {
        echo '<bdo xml:lang="en" dir="ltr">' . $language_title . ':</bdo>';
    }
    ?>

    <select name="lang" onchange="this.form.submit();" xml:lang="en" dir="ltr">
    <?php

    uasort($GLOBALS['available_languages'], 'PMA_language_cmp');
    foreach ($GLOBALS['available_languages'] as $id => $tmplang) {
        $lang_name = ucfirst(substr(strrchr($tmplang[0], '|'), 1));

        // Include native name if non empty
        if (!empty($tmplang[3])) {
            $lang_name = $tmplang[3] . ' - '
                . $lang_name;
        }

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
