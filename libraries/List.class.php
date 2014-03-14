<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold the PMA_List base class
 *
 * @package PhpMyAdmin
 */
if (! defined('PHPMYADMIN')) {
    exit;
}

/**
 * Generic list class
 *
 * @todo add caching
 * @abstract
 * @package PhpMyAdmin
 * @since   phpMyAdmin 2.9.10
 */
abstract class PMA_List extends ArrayObject
{
    /**
     * @var mixed   empty item
     */
    protected $item_empty = '';

    /**
     * PMA_List constructor
     *
     * @param array  $array          The input parameter accepts an array or an
     *                               Object.
     * @param int    $flags          Flags to control the behaviour of the
     *                               ArrayObject object.
     * @param string $iterator_class Specify the class that will be used for
     *                               iteration of the ArrayObject object.
     *                               ArrayIterator is the default class used.
     */
    public function __construct(
        $array = array(), $flags = 0, $iterator_class = "ArrayIterator"
    ) {
        parent::__construct($array, $flags, $iterator_class);
    }

    /**
     * defines what is an empty item (0, '', false or null)
     *
     * @return mixed   an empty item
     */
    public function getEmpty()
    {
        return $this->item_empty;
    }

    /**
     * checks if the given db names exists in the current list, if there is
     * missing at least one item it returns false otherwise true
     *
     * @return boolean true if all items exists, otheriwse false
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
     * @param mixed   $selected                   the selected db or true for
     *                                            selecting current db
     * @param boolean $include_information_schema whether include information schema
     *
     * @return string  HTML option tags
     */
    public function getHtmlOptions(
        $selected = '', $include_information_schema = true
    ) {
        if (true === $selected) {
            $selected = $this->getDefault();
        }

        $options = '';
        foreach ($this as $each_item) {
            if (false === $include_information_schema
                && $GLOBALS['dbi']->isSystemSchema($each_item)
            ) {
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
     * @return string  default item
     */
    public function getDefault()
    {
        return $this->getEmpty();
    }

    /**
     * builds up the list
     *
     * @return void
     */
    abstract public function build();
}
?>
