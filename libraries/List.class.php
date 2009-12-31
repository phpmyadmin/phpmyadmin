<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold the PMA_List base class
 *
 * @version $Id$
 * @package phpMyAdmin
 */

/**
 * @todo add caching
 * @since phpMyAdmin 2.9.10
 * @abstract
 * @package phpMyAdmin
 */
abstract class PMA_List extends ArrayObject
{
    /**
     * @var mixed   empty item
     */
    protected $item_empty = '';

    public function __construct($array = array(), $flags = 0, $iterator_class = "ArrayIterator")
    {
        parent::__construct($array, $flags, $iterator_class);
    }

    /**
     * returns item only if there is only one in the list
     *
     * @uses    count()
     * @uses    reset()
     * @uses    PMA_List::getEmpty() to return it
     * @return  single item
     */
    public function getSingleItem()
    {
        if (count($this) === 1) {
            return reset($this);
        }

        return $this->getEmpty();
    }

    /**
     * defines what is an empty item (0, '', false or null)
     *
     * @uses    PMA_List::$item_empty as return value
     * @return  mixed   an empty item
     */
    public function getEmpty()
    {
        return $this->item_empty;
    }

    /**
     * checks if the given db names exists in the current list, if there is
     * missing at least one item it returns false otherwise true
     *
     * @uses    PMA_List::$items to check for existence of specific item
     * @uses    func_get_args()
     * @uses    in_array() to check if given arguments exists in PMA_List::$items
     * @param   string  $db_name,..     one or more mysql result resources
     * @return  boolean true if all items exists, otheriwse false
     */
    public function exists()
    {
        $this_elements = $this->getArrayCopy();
        foreach (func_get_args() as $result) {
            if (! in_array($result, $this_elements)) {
                return false;
            }
        }

        return true;
    }

    /**
     * returns HTML <option>-tags to be used inside <select></select>
     *
     * @uses    PMA_List::$items to build up the option items
     * @uses    PMA_List::getDefault() to mark this as selected if requested
     * @uses    htmlspecialchars() to escape items
     * @param   mixed   $selected   the selected db or true for selecting current db
     * @param   boolean $include_information_schema
     * @return  string  HTML option tags
     */
    public function getHtmlOptions($selected = '', $include_information_schema = true)
    {
        if (true === $selected) {
            $selected = $this->getDefault();
        }

        $options = '';
        foreach ($this as $each_item) {
            if (false === $include_information_schema && 'information_schema' === $each_item) {
                continue;
            }
            $options .= '<option value="' . htmlspecialchars($each_item) . '"';
            if ($selected === $each_item) {
                $options .= ' selected="selected"';
            }
            $options .= '>' . htmlspecialchars($each_item) . '</option>' . "\n";
        }

        return $options;
    }

    /**
     * returns default item
     *
     * @uses    PMA_List::getEmpty() as fallback
     * @return  string  default item
     */
    public function getDefault()
    {
        return $this->getEmpty();
    }

    /**
     * builds up the list
     *
     */
    abstract public function build();
}
?>
