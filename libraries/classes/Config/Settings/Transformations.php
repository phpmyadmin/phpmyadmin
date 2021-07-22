<?php

declare(strict_types=1);

namespace PhpMyAdmin\Config\Settings;

use function in_array;
use function is_array;

// phpcs:disable Squiz.NamingConventions.ValidVariableName.MemberNotCamelCaps

/**
 * @psalm-immutable
 */
final class Transformations
{
    /**
     * Default transformations for Substring
     *
     * @var array<int, int|string>
     * @psalm-var array{0: int, 1: 'all'|int, 2: string}
     */
    public $Substring = [0, 'all', '…'];

    /**
     * Default transformations for Bool2Text
     *
     * @var string[]
     * @psalm-var array{0: string, 1: string}
     */
    public $Bool2Text = ['T', 'F'];

    /**
     * Default transformations for External
     *
     * @var array<int, int|string>
     * @psalm-var array{0: int, 1: string, 2: int, 3: int}
     */
    public $External = [0, '-f /dev/null -i -wrap -q', 1, 1];

    /**
     * Default transformations for PreApPend
     *
     * @var string[]
     * @psalm-var array{0: string, 1: string}
     */
    public $PreApPend = ['', ''];

    /**
     * Default transformations for Hex
     *
     * @var string[]
     * @psalm-var array{0: string}
     */
    public $Hex = ['2'];

    /**
     * Default transformations for DateFormat
     *
     * @var array<int, int|string>
     * @psalm-var array{0: int, 1: string, 2: 'local'|'utc'}
     */
    public $DateFormat = [0, '', 'local'];

    /**
     * Default transformations for Inline
     *
     * @var array<(int|string), (int|string|array<string, string>|null)>
     * @psalm-var array{0: string|int, 1: string|int, wrapper_link: string|null, wrapper_params: array<string, string>}
     */
    public $Inline = ['100', 100, 'wrapper_link' => null, 'wrapper_params' => []];

    /**
     * Default transformations for TextImageLink
     *
     * @var array<int, int|string|null>
     * @psalm-var array{0: string|null, 1: int, 2: int}
     */
    public $TextImageLink = [null, 100, 50];

    /**
     * Default transformations for TextLink
     *
     * @var array<int, string|null>
     * @psalm-var array{0: string|null, 1: string|null, 2: string|null}
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
                $this->Hex[0] = (string) $transformations['Hex'][0];
            }
        }

        if (isset($transformations['DateFormat']) && is_array($transformations['DateFormat'])) {
            if (isset($transformations['DateFormat'][0])) {
                $this->DateFormat[0] = (int) $transformations['DateFormat'][0];
            }

            if (isset($transformations['DateFormat'][1])) {
                $this->DateFormat[1] = (string) $transformations['DateFormat'][1];
            }

            if (
                isset($transformations['DateFormat'][2])
                && in_array($transformations['DateFormat'][2], ['local', 'utc'], true)
            ) {
                $this->DateFormat[2] = $transformations['DateFormat'][2];
            }
        }

        if (isset($transformations['Inline']) && is_array($transformations['Inline'])) {
            if (isset($transformations['Inline'][0])) {
                $this->Inline[0] = (int) $transformations['Inline'][0];
            }

            if (isset($transformations['Inline'][1])) {
                $this->Inline[1] = (int) $transformations['Inline'][1];
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
                 * @var mixed      $value
                 */
                foreach ($transformations['Inline']['wrapper_params'] as $key => $value) {
                    $this->Inline['wrapper_params'][(string) $key] = (string) $value;
                }
            }
        }

        if (isset($transformations['TextImageLink']) && is_array($transformations['TextImageLink'])) {
            if (isset($transformations['TextImageLink'][0])) {
                $this->TextImageLink[0] = (string) $transformations['TextImageLink'][0];
            }

            if (isset($transformations['TextImageLink'][1])) {
                $this->TextImageLink[1] = (int) $transformations['TextImageLink'][1];
            }

            if (isset($transformations['TextImageLink'][2])) {
                $this->TextImageLink[2] = (int) $transformations['TextImageLink'][2];
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

        $this->TextLink[2] = (string) $transformations['TextLink'][2];
    }
}
