<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold the PMA_List base class
 *
 * @version $Id$
 */

/**
 * @todo add caching
 * @since phpMyAdmin 2.9.10
 * @abstract
 */
/* abstract public */ class PMA_List
{
    /**
     * @var array   the list items
     * @access public
     */
    var $items = array();

    /**
     * @var array   details for list items
     * @access public
     */
    var $details = array();

    /**
     * @var bool    whether we need to re-index the database list for consistency keys
     * @access protected
     */
    var $_need_to_reindex = false;

    /**
     * @var mixed   empty item
     */
    var $item_empty = '';

    /**
     * returns first item from list
     *
     * @uses    PMA_List::$items to get first item
     * @uses    reset() to retrive first item from PMA_List::$items array
     * @return  string  value of first item
     */
    function getFirst()
    {
        return reset($this->items);
    }

    /**
     * returns item only if there is only one in the list
     *
     * @uses    PMA_List::count() to decide what to return
     * @uses    PMA_List::getFirst() to return it
     * @uses    PMA_List::getEmpty() to return it
     * @return  single item
     */
    function getSingleItem()
    {
        if ($this->count() === 1) {
            return $this->getFirst();
        }

        return $this->getEmpty();
    }

    /**
     * returns list item count
     *
     * @uses    PMA_List::$items to count it items
     * @uses    count() to count items in PMA_List::$items
     * @return  integer PMA_List::$items count
     */
    function count()
    {
        return count($this->items);
    }

    /**
     * defines what is an empty item (0, '', false or null)
     *
     * @uses    PMA_List::$item_empty as return value
     * @return  mixed   an empty item
     */
    function getEmpty()
    {
        return $this->item_empty;
    }

    /**
     * checks if the given db names exists in the current list, if there is
     * missing at least one item it reutrns false other wise true
     *
     * @uses    PMA_List::$items to check for existence of specific item
     * @uses    func_get_args()
     * @uses    in_array() to check if given arguments exists in PMA_List::$items
     * @param   string  $db_name,..     one or more mysql result resources
     * @return  boolean true if all items exists, otheriwse false
     */
    function exists()
    {
        foreach (func_get_args() as $result) {
            if (! in_array($result, $this->items)) {
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
    function getHtmlOptions($selected = '', $include_information_schema = true)
    {
        if (true === $selected) {
            $selected = $this->getDefault();
        }

        $options = '';
        foreach ($this->items as $each_db) {
            if (false === $include_information_schema && 'information_schema' === $each_db) {
                continue;
            }
            $options .= '<option value="' . htmlspecialchars($each_db) . '"';
            if ($selected === $each_db) {
                $options .= ' selected="selected"';
            }
            $options .= '>' . htmlspecialchars($each_db) . '</option>' . "\n";
        }

        return $options;
    }

    /**
     * returns default item
     *
     * @uses    PMA_List::getEmpty() as fallback
     * @return  string  default item
     */
    function getDefault()
    {
        return $this->getEmpty();
    }

    /**
     * builds up the list
     *
     * @abstract
     */
    /* abstract public */ function build() {}
}
?>
