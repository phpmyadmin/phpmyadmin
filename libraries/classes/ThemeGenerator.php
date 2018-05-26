<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Holds the PhpMyAdmin\ThemeGenerator class
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin;

/**
 * Set of functions for Automated Theme Generator in phpMyAdmin
 *
 * @package PhpMyAdmin
 */
class ThemeGenerator
{

    /**
     * Colour Picker HTML file
     *
     * @return string HTML for the color picker tool
     */
    public function colorPicker()
    {
        $output = '<div id="container">';
        $output .= '<div id="palette" class="block">';
        $output .= '<div id="color-palette"></div>';
        $output .= '</div>';
        $output .= '<div id="picker" class="block">';
        $output .= '<div class="ui-color-picker" data-topic="picker" data-mode="HSB"></div>';
        $output .= '<div id="picker-samples" sample-id="master"></div>';
        $output .= '</div>';
        $output .= '</div>';
        return $output;
    }

    /**
     * File creation form
     *
     * @return string HTML for the form submission
     */
    public function form()
    {
        $output = '<form action="#" method="post" id="save">';
        $output .= '<select name="type" id="theme">';
        $output .= '<option value="0">Triadic</option>';
        $output .= '<option value="1">Complementary</option>';
        $output .= '<option value="2">Adjacent</option>';
        $output .= '<option value="3">Monochrome</option>';
        $output .= '</select>';
        $output .= '<br><br>';
        $output .= '<input type="submit">';
        $output .= '</form>';
        return $output;
    }
}