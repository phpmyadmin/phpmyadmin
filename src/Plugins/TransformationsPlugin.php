<?php
/**
 * Abstract class for the transformations plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins;

/**
 * Provides a common interface that will have to
 * be implemented by all of the transformations plugins.
 */
abstract class TransformationsPlugin implements TransformationsInterface
{
    /**
     * Returns true if the element requires no wrapping
     *
     * @param mixed[] $options transformation options
     */
    public function applyTransformationNoWrap(array $options = []): bool
    {
        return false;
    }

    /**
     * Returns passed options or default values if they were not set
     *
     * @param mixed[] $options  List of passed options
     * @param mixed[] $defaults List of default values
     *
     * @return mixed[] List of options possibly filled in by defaults.
     */
    public function getOptions(array $options, array $defaults): array
    {
        $result = [];
        foreach ($defaults as $key => $value) {
            if (isset($options[$key]) && $options[$key] !== '') {
                $result[$key] = $options[$key];
            } else {
                $result[$key] = $value;
            }
        }

        return $result;
    }
}
