<?php
/**
 * Base class for the plot data type classes.
 *
 * @package PhpMyAdmin
 */
abstract class PMA_SVG_Data_Element
{
    protected $label = '';

    protected $dataRow = array();

    /**
     * Store user specified label and dataRow
     * @param string $label users specified label
     * @param array $dataRow A data row from the query result
     */
    function __construct($label,$dataRow)
    {
        $this->label = $label;
        $this->dataRow = $dataRow;
    }

    /**
     * Handles the generation of each Data Row/Element as a SVG element
     * @return the code related to a row in the GIS dataset
     */
    public abstract function prepareRowAsSVG($options);

    public function getLabel()
    {
        return $this->label;
    }

    public function setLabel($label)
    {
        $this->label = $label;
    }

    public function getDataRow()
    {
        return $this->dataRow;
    }

    public function setDataRow($dataRow)
    {
        $this->dataRow = $dataRow;
    }
}
?>
