<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functionality for the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Util;

/**
 * Represents a columns node in the navigation tree
 *
 * @package PhpMyAdmin-Navigation
 */
class NodeColumn extends Node
{
    /**
     * Initialises the class
     *
     * @param array $item    array to identify the column node
     * @param int   $type    Type of node, may be one of CONTAINER or OBJECT
     * @param bool  $isGroup Whether this object has been created
     *                       while grouping nodes
     */
    public function __construct($item, $type = Node::OBJECT, $isGroup = false)
    {
        $this->displayName = $this->getDisplayName($item);

        parent::__construct($item['name'], $type, $isGroup);
        $this->icon = Util::getImage($this->getColumnIcon($item['key']), __('Column'));
        $this->links = [
            'text'  => 'tbl_structure.php?server=' . $GLOBALS['server']
                . '&amp;db=%3$s&amp;table=%2$s&amp;field=%1$s'
                . '&amp;change_column=1',
            'icon'  => 'tbl_structure.php?server=' . $GLOBALS['server']
                . '&amp;db=%3$s&amp;table=%2$s&amp;field=%1$s'
                . '&amp;change_column=1',
            'title' => __('Structure'),
        ];
    }

    /**
     * Get customized Icon for columns in navigation tree
     *
     * @param string $key The key type - (primary, foreign etc.)
     *
     * @return string Icon name for required key.
     */
    private function getColumnIcon($key)
    {
        switch ($key) {
            case 'PRI':
                $retval = 'b_primary';
                break;
            case 'UNI':
                $retval = 'bd_primary';
                break;
            default:
                $retval = 'pause';
                break;
        }
        return $retval;
    }

    /**
     * Get displayable name for navigation tree (key_type, data_type, default)
     *
     * @param array $item Item is array containing required info
     *
     * @return string Display name for navigation tree
     */
    private function getDisplayName($item)
    {
        $retval = $item['name'];
        $flag = 0;
        foreach ($item as $key => $value) {
            if (! empty($value) && $key != 'name') {
                $flag == 0 ? $retval .= ' (' : $retval .= ', ';
                $flag = 1;
                $retval .= $this->getTruncateValue($key, $value);
            }
        }
        $retval .= ')';
        return $retval;
    }

    /**
     * Get truncated value for display in node column view
     *
     * @param string $key   key to identify default,datatype etc
     * @param string $value value corresponding to key
     *
     * @return string truncated value
     */
    public function getTruncateValue($key, $value)
    {
        $retval = '';

        switch ($key) {
            case 'default':
                strlen($value) > 6 ?
                    $retval .= substr($value, 0, 6) . '...' :
                    $retval = $value;
                break;
            default:
                $retval = $value;
                break;
        }

        return $retval;
    }
}
