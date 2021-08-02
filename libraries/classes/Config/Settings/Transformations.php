<?php

declare(strict_types=1);

namespace PhpMyAdmin\Config\Settings;

use function is_array;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

/**
 * @psalm-immutable
 */
final class Transformations
{
    /**
     * Displays a part of a string.
     * - The first option is the number of characters to skip from the beginning of the string (Default 0).
     * - The second option is the number of characters to return (Default: until end of string).
     * - The third option is the string to append and/or prepend when truncation occurs (Default: "…").
     *
     * @var array<int, int|string>
     * @psalm-var array{0: 0|positive-int, 1: 'all'|int, 2: string}
     */
    public $Substring = [0, 'all', '…'];

    /**
     * Converts Boolean values to text (default 'T' and 'F').
     * - First option is for TRUE, second for FALSE. Nonzero=true.
     *
     * @var string[]
     * @psalm-var array{0: string, 1: string}
     */
    public $Bool2Text = ['T', 'F'];

    /**
     * LINUX ONLY: Launches an external application and feeds it the column data via standard input.
     * Returns the standard output of the application. The default is Tidy, to pretty-print HTML code.
     * For security reasons, you have to manually edit the file
     * libraries/classes/Plugins/Transformations/Abs/ExternalTransformationsPlugin.php and list the tools
     * you want to make available.
     * - The first option is then the number of the program you want to use.
     * - The second option should be blank for historical reasons.
     * - The third option, if set to 1, will convert the output using htmlspecialchars() (Default 1).
     * - The fourth option, if set to 1, will prevent wrapping and ensure that the output appears
     *   all on one line (Default 1).
     *
     * @var array<int, int|string>
     * @psalm-var array{0: int, 1: string, 2: int, 3: int}
     */
    public $External = [0, '-f /dev/null -i -wrap -q', 1, 1];

    /**
     * Prepends and/or Appends text to a string.
     * - First option is text to be prepended. second is appended (enclosed in single quotes, default empty string).
     *
     * @var string[]
     * @psalm-var array{0: string, 1: string}
     */
    public $PreApPend = ['', ''];

    /**
     * Displays hexadecimal representation of data.
     * Optional first parameter specifies how often space will be added (defaults to 2 nibbles).
     *
     * @var string[]
     * @psalm-var array{0: 0|positive-int}
     */
    public $Hex = [2];

    /**
     * Displays a TIME, TIMESTAMP, DATETIME or numeric unix timestamp column as formatted date.
     * - The first option is the offset (in hours) which will be added to the timestamp (Default: 0).
     * - Use second option to specify a different date/time format string.
     * - Third option determines whether you want to see local date or UTC one (use "local" or "utc" strings) for that.
     *   According to that, date format has different value - for "local" see the documentation
     *   for PHP's strftime() function and for "utc" it is done using gmdate() function.
     *
     * @var array<int, int|string>
     * @psalm-var array{0: 0|positive-int, 1: string, 2: 'local'|'utc'}
     */
    public $DateFormat = [0, '', 'local'];

    /**
     * Displays a clickable thumbnail.
     * The options are the maximum width and height in pixels.
     * The original aspect ratio is preserved.
     *
     * @var array<(int|string), (int|string|array<string, string>|null)>
     * @psalm-var array{
     *   0: 0|positive-int,
     *   1: 0|positive-int,
     *   wrapper_link: string|null,
     *   wrapper_params: array<array-key, string>
     * }
     */
    public $Inline = [100, 100, 'wrapper_link' => null, 'wrapper_params' => []];

    /**
     * Displays an image and a link; the column contains the filename.
     * - The first option is a URL prefix like "https://www.example.com/".
     * - The second and third options are the width and the height in pixels.
     *
     * @var array<int, int|string|null>
     * @psalm-var array{0: string|null, 1: 0|positive-int, 2: 0|positive-int}
     */
    public $TextImageLink = [null, 100, 50];

    /**
     * Displays a link; the column contains the filename.
     * - The first option is a URL prefix like "https://www.example.com/".
     * - The second option is a title for the link.
     *
     * @var array<int, string|null>
     * @psalm-var array{0: string|null, 1: string|null, 2: bool|null}
     */
    public $TextLink = [null, null, null];

    /**
     * @param array<int|string, mixed> $transformations
     */
    public function __construct(array $transformations = [])
    {
        if (isset($transformations['Substring']) && is_array($transformations['Substring'])) {
            if (isset($transformations['Substring'][0])) {
                $this->Substring[0] = (int) $transformations['Substring'][0];
            }

            if (isset($transformations['Substring'][1]) && $transformations['Substring'][1] !== 'all') {
                $this->Substring[1] = (int) $transformations['Substring'][1];
            }

            if (isset($transformations['Substring'][2])) {
                $this->Substring[2] = (string) $transformations['Substring'][2];
            }
        }

        if (isset($transformations['Bool2Text']) && is_array($transformations['Bool2Text'])) {
            if (isset($transformations['Bool2Text'][0])) {
                $this->Bool2Text[0] = (string) $transformations['Bool2Text'][0];
            }

            if (isset($transformations['Bool2Text'][1])) {
                $this->Bool2Text[1] = (string) $transformations['Bool2Text'][1];
            }
        }

        if (isset($transformations['External']) && is_array($transformations['External'])) {
            if (isset($transformations['External'][0])) {
                $this->External[0] = (int) $transformations['External'][0];
            }

            if (isset($transformations['External'][1])) {
                $this->External[1] = (string) $transformations['External'][1];
            }

            if (isset($transformations['External'][2])) {
                $this->External[2] = (int) $transformations['External'][2];
            }

            if (isset($transformations['External'][3])) {
                $this->External[3] = (int) $transformations['External'][3];
            }
        }

        if (isset($transformations['PreApPend']) && is_array($transformations['PreApPend'])) {
            if (isset($transformations['PreApPend'][0])) {
                $this->PreApPend[0] = (string) $transformations['PreApPend'][0];
            }

            if (isset($transformations['PreApPend'][1])) {
                $this->PreApPend[1] = (string) $transformations['PreApPend'][1];
            }
        }

        if (isset($transformations['Hex']) && is_array($transformations['Hex'])) {
            if (isset($transformations['Hex'][0])) {
                $hex = (int) $transformations['Hex'][0];
                if ($hex >= 0) {
                    $this->Hex[0] = $hex;
                }
            }
        }

        if (isset($transformations['DateFormat']) && is_array($transformations['DateFormat'])) {
            if (isset($transformations['DateFormat'][0])) {
                $dateFormat = (int) $transformations['DateFormat'][0];
                if ($dateFormat >= 1) {
                    $this->DateFormat[0] = $dateFormat;
                }
            }

            if (isset($transformations['DateFormat'][1])) {
                $this->DateFormat[1] = (string) $transformations['DateFormat'][1];
            }

            if (isset($transformations['DateFormat'][2]) && $transformations['DateFormat'][2] === 'utc') {
                $this->DateFormat[2] = 'utc';
            }
        }

        if (isset($transformations['Inline']) && is_array($transformations['Inline'])) {
            if (isset($transformations['Inline'][0])) {
                $width = (int) $transformations['Inline'][0];
                if ($width >= 0) {
                    $this->Inline[0] = $width;
                }
            }

            if (isset($transformations['Inline'][1])) {
                $height = (int) $transformations['Inline'][1];
                if ($height >= 0) {
                    $this->Inline[1] = $height;
                }
            }

            if (isset($transformations['Inline']['wrapper_link'])) {
                $this->Inline['wrapper_link'] = (string) $transformations['Inline']['wrapper_link'];
            }

            if (
                isset($transformations['Inline']['wrapper_params'])
                && is_array($transformations['Inline']['wrapper_params'])
            ) {
                /**
                 * @var int|string $key
                 * @var mixed $value
                 */
                foreach ($transformations['Inline']['wrapper_params'] as $key => $value) {
                    $this->Inline['wrapper_params'][$key] = (string) $value;
                }
            }
        }

        if (isset($transformations['TextImageLink']) && is_array($transformations['TextImageLink'])) {
            if (isset($transformations['TextImageLink'][0])) {
                $this->TextImageLink[0] = (string) $transformations['TextImageLink'][0];
            }

            if (isset($transformations['TextImageLink'][1])) {
                $width = (int) $transformations['TextImageLink'][1];
                if ($width >= 0) {
                    $this->TextImageLink[1] = $width;
                }
            }

            if (isset($transformations['TextImageLink'][2])) {
                $height = (int) $transformations['TextImageLink'][2];
                if ($height >= 0) {
                    $this->TextImageLink[2] = $height;
                }
            }
        }

        if (! isset($transformations['TextLink']) || ! is_array($transformations['TextLink'])) {
            return;
        }

        if (isset($transformations['TextLink'][0])) {
            $this->TextLink[0] = (string) $transformations['TextLink'][0];
        }

        if (isset($transformations['TextLink'][1])) {
            $this->TextLink[1] = (string) $transformations['TextLink'][1];
        }

        if (! isset($transformations['TextLink'][2])) {
            return;
        }

        $this->TextLink[2] = (bool) $transformations['TextLink'][2];
    }
}
