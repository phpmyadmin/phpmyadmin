<?php
/**
 * Functionality for the navigation tree
 */

declare(strict_types=1);

namespace PhpMyAdmin\Navigation\Nodes;

use PhpMyAdmin\Config;

use function __;
use function strlen;
use function substr;

/**
 * Represents a columns node in the navigation tree
 */
class NodeColumn extends Node
{
    /** @param mixed[] $item array to identify the column node */
    public function __construct(Config $config, array $item)
    {
        $this->displayName = $this->getDisplayName($item);

        parent::__construct($config, $item['name']);

        $this->icon = ['image' => $this->getColumnIcon($item['key']), 'title' => __('Column')];
        $this->links = [
            'text' => [
                'route' => '/table/structure/change',
                'params' => ['change_column' => 1, 'db' => null, 'table' => null, 'field' => null],
            ],
            'icon' => [
                'route' => '/table/structure/change',
                'params' => ['change_column' => 1, 'db' => null, 'table' => null, 'field' => null],
            ],
            'title' => __('Structure'),
        ];
        $this->urlParamName = 'field';
    }

    /**
     * Get customized Icon for columns in navigation tree
     *
     * @param string $key The key type - (primary, foreign etc.)
     *
     * @return string Icon name for required key.
     */
    private function getColumnIcon(string $key): string
    {
        return match ($key) {
            'PRI' => 'b_primary',
            'UNI' => 'bd_primary',
            default => 'pause',
        };
    }

    /**
     * Get displayable name for navigation tree (key_type, data_type, default)
     *
     * @param array<string, mixed> $item Item is array containing required info
     *
     * @return string Display name for navigation tree
     */
    private function getDisplayName(array $item): string
    {
        $retval = $item['name'];
        $isFirst = true;
        foreach ($item as $key => $value) {
            if (empty($value) || $key === 'name') {
                continue;
            }

            $isFirst ? $retval .= ' (' : $retval .= ', ';
            $isFirst = false;
            $retval .= $this->getTruncateValue($key, $value);
        }

        return $retval . ')';
    }

    /**
     * Get truncated value for display in node column view
     *
     * @param string $key   key to identify default,datatype etc
     * @param string $value value corresponding to key
     *
     * @return string truncated value
     */
    private function getTruncateValue(string $key, string $value): string
    {
        if ($key === 'default' && strlen($value) > 6) {
            return substr($value, 0, 6) . '...';
        }

        return $value;
    }
}
