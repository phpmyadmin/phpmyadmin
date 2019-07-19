<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * hold the ListAbstract base class
 *
 * @package PhpMyAdmin
 */
declare(strict_types=1);

namespace PhpMyAdmin;

use ArrayObject;

/**
 * Generic list class
 *
 * @todo add caching
 * @abstract
 * @package PhpMyAdmin
 * @since   phpMyAdmin 2.9.10
 */
abstract class ListAbstract extends ArrayObject
{
    /**
     * @var mixed   empty item
     */
    protected $item_empty = '';

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
     * @param mixed[] ...$params params
     * @return bool true if all items exists, otherwise false
     */
    public function exists(...$params)
    {
        $this_elements = $this->getArrayCopy();
        foreach ($params as $result) {
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
        $selected = '',
        $include_information_schema = true
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
