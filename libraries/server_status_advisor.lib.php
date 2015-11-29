<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * functions for displaying server status sub item: advisor
 *
 * @usedby  server_status_advisor.php
 *
 * @package PhpMyAdmin
 */

/**
 * Returns html with PMA\libraries\Advisor
 *
 * @return string
 */
function PMA_getHtmlForAdvisor()
{
    $output  = '<a href="#openAdvisorInstructions">';
    $output .= PMA\libraries\Util::getIcon('b_help.png', __('Instructions'));
    $output .= '</a>';
    $output .= '<div id="statustabs_advisor"></div>';
    $output .= '<div id="advisorInstructionsDialog" style="display:none;">';
    $output .= '<p>';
    $output .= __(
        'The Advisor system can provide recommendations '
        . 'on server variables by analyzing the server status variables.'
    );
    $output .= '</p>';
    $output .= '<p>';
    $output .= __(
        'Do note however that this system provides recommendations '
        . 'based on simple calculations and by rule of thumb which may '
        . 'not necessarily apply to your system.'
    );
    $output .= '</p>';
    $output .= '<p>';
    $output .= __(
        'Prior to changing any of the configuration, be sure to know '
        . 'what you are changing (by reading the documentation) and how '
        . 'to undo the change. Wrong tuning can have a very negative '
        . 'effect on performance.'
    );
    $output .= '</p>';
    $output .= '<p>';
    $output .= __(
        'The best way to tune your system would be to change only one '
        . 'setting at a time, observe or benchmark your database, and undo '
        . 'the change if there was no clearly measurable improvement.'
    );
    $output .= '</p>';
    $output .= '</div>';
    $output .= '<div id="advisorData" style="display:none;">';
    $advisor = new PMA\libraries\Advisor();
    $output .= htmlspecialchars(
        json_encode(
            $advisor->run()
        )
    );
    $output .= '</div>';

    return $output;
}

