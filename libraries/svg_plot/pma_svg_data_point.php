<?php
/**
 * Handles the visualization of Data Point objects.
 *
 * @package PhpMyAdmin
 */

require_once 'pma_svg_data_element.php';

class PMA_SVG_Data_Point extends PMA_SVG_Data_Element
{
    /*
     * X-Coordinate of the point
     */
    private $cx;

    /*
     * Y-Coordinate of the point
     */
    private $cy;

    /*
     * A private constructor; prevents direct creation of object.
     */
    public function __construct($cx, $cy, $label, $dataRow)
    {
        parent::__construct($label, $dataRow);
        $this->cx = $cx;
        $this->cy = $cy;
    }

    public function prepareRowAsSVG($options)
    {
         return $this->prepareSvg($options);
    }

    /**
     * Prepares and returns the code related to a row in the query result as SVG.
     *
     * @param array  $options  Array containing options related to properties of the point
     * @return the code related to a row in the query result.
     */
    protected function prepareSvg($options)
    {
        $point_options = array(
            'name'        => $this->label . '_' .$options['id'],
            'id'          => $this->label . 'id' . '_' . $options['id'],
            'class'       => 'point',
            'fill'        => 'white',
            'stroke'      => $options['color'],
            'stroke-width'=> 2,
        );

        $row = '<circle cx="' . $this->cx . '" cy="' . $this->cy . '" r=".1"';
        foreach ($point_options as $option => $val) {
            $row .= ' ' . $option . '="' . trim($val) . '"';
        }
        $row .= '/>';

        return $row;
    }

    public function getCx()
    {
        return $this->cx;
    }

    public function setCx($cx)
    {
        $this->cx = $cx;
    }

    public function getCy()
    {
        return $this->cy;
    }

    public function setCy($cy)
    {
        $this->cy = $cy;
    }
}
?>
