<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * functions for displaying server status sub item: advisor
 *
 * @package PhpMyAdmin
 */
namespace PhpMyAdmin\Server\Status;

use PhpMyAdmin\Advisor as PmaAdvisor;
use PhpMyAdmin\Util;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

/**
 * PhpMyAdmin\Server\Status\Advisor class
 *
 * @package PhpMyAdmin
 */
class Advisor
{
    /**
     * Returns html with PhpMyAdmin\Advisor
     *
     * @return string
     */
    public static function getHtml()
    {
        $output  = '<a href="#openAdvisorInstructions">';
        $output .= Util::getIcon('b_help', __('Instructions'));
        $output .= '</a>';
        $output .= '<div id="statustabs_advisor"></div>';
        $output .= '<div id="advisorInstructionsDialog" class="hide">';
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
        $output .= '<div id="advisorData" class="hide">';
        $advisor = new PmaAdvisor($GLOBALS['dbi'], new ExpressionLanguage());
        $output .= htmlspecialchars(
            json_encode(
                $advisor->run()
            )
        );
        $output .= '</div>';

        return $output;
    }
}
