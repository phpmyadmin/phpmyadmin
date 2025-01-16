<?php
/**
 * Abstract class for the download transformations plugins
 */

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Transformations\Abs;

use PhpMyAdmin\FieldMetadata;
use PhpMyAdmin\Plugins\TransformationsPlugin;
use PhpMyAdmin\Url;

use function __;
use function array_merge;
use function htmlspecialchars;
use function is_string;

/**
 * Provides common methods for all of the download transformations plugins.
 */
abstract class DownloadTransformationsPlugin extends TransformationsPlugin
{
    /**
     * Gets the transformation description of the specific plugin
     */
    public static function getInfo(): string
    {
        return __(
            'Displays a link to download the binary data of the column.'
            . ' You can use the option to specify the filename.',
        );
    }

    /**
     * Does the actual work of each specific transformations plugin.
     *
     * @param string             $buffer  text to be transformed
     * @param mixed[]            $options transformation options
     * @param FieldMetadata|null $meta    meta information
     */
    public function applyTransformation(string $buffer, array $options = [], FieldMetadata|null $meta = null): string
    {
        $cn = 'binary_file.dat';
        if (isset($options[0]) && is_string($options[0]) && $options[0] !== '') {
            $cn = $options[0];
        }

        $link = '<a href="' . Url::getFromRoute(
            '/transformation/wrapper',
            array_merge($options['wrapper_params'], ['ct' => 'application/octet-stream', 'cn' => $cn]),
        );
        $link .= '" title="' . htmlspecialchars($cn);
        $link .= '" class="disableAjax">' . htmlspecialchars($cn);
        $link .= '</a>';

        return $link;
    }

    /* ~~~~~~~~~~~~~~~~~~~~ Getters and Setters ~~~~~~~~~~~~~~~~~~~~ */

    /**
     * Gets the transformation name of the specific plugin
     */
    public static function getName(): string
    {
        return 'Download';
    }
}
