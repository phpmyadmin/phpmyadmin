<?php

class PMA_Chart
{
    /*
     * The style of the chart title.
     */
    protected $titleStyle = 'font-size: 12px; font-weight: bold;';

    /*
     * Colors for the different slices in the pie chart.
     */    
    protected $colors = array(
        '#BCE02E',
        '#E0642E',
        '#E0D62E',
        '#2E97E0',
        '#B02EE0',
        '#E02E75',
        '#5CE02E',
        '#E0B02E',
        '#000000',
        '#0022E0',
        '#726CB1',
        '#481A36',
        '#BAC658',
        '#127224',
        '#825119',
        '#238C74',
        '#4C489B',
        '#1D674A',
        '#87C9BF',
    );

    /*
     * Chart background color.
     */
    protected $bgColor = '#E5E5E5';

    /*
     * The width of the chart.
     */
    protected $width = 520;

    /*
     * The height of the chart.
     */
    protected $height = 325;

    function __construct($options = null)
    {
        $this->handleOptions($options);
    }

    /*
     * A function which handles passed parameters. Useful if desired
     * chart needs to be a little bit different from the default one.
     *
     * Option handling could be made more efficient if options would be
     * stored in an array.
     */
    function handleOptions($options)
    {
        if (is_null($options))
            return;

        if (isset($options['bgColor']))
            $this->bgColor = $options['bgColor'];
        if (isset($options['width']))
            $this->width = $options['width'];
        if (isset($options['height']))
            $this->height = $options['height'];
    }

    function getBgColorComp($component)
    {
        return hexdec(substr($this->bgColor, ($component * 2) + 1, 2));
    }
}

?>
