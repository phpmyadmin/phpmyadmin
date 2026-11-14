<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Stubs;

use PhpMyAdmin\Utils\IniReader;

use function array_key_exists;

final class IniReaderStub extends IniReader
{
    /** @param array<string, string> $options */
    public function __construct(private readonly array $options)
    {
    }

    protected function get(string $option): string
    {
        if (array_key_exists($option, $this->options)) {
            return $this->options[$option];
        }

        return parent::get($option);
    }
}
