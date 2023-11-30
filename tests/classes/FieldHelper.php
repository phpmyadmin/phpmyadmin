<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\FieldMetadata;

class FieldHelper
{
    /**
     * @param array<string,string|int> $metadata
     * @psalm-param array{
     *     name?: non-empty-string,
     *     orgname?: string,
     *     table?: string,
     *     orgtable?: string,
     *     max_length?: int,
     *     length?: int,
     *     charsetnr?: int,
     *     flags?: int,
     *     type: int,
     *     decimals?: int,
     *     db?: string,
     *     def?: string,
     *     catalog?: string,
     * } $metadata
     */
    public static function fromArray(array $metadata): FieldMetadata
    {
        return new FieldMetadata((object) ($metadata + [
            'name' => 'c',
            'orgname' => '',
            'table' => '',
            'orgtable' => '',
            'max_length' => 0,
            'length' => 0,
            'charsetnr' => -1,
            'flags' => 0,
            // 'type' => MYSQLI_TYPE_STRING,
            'decimals' => 0,
            'catalog' => 'def',
            'db' => '',
            'def' => '',
        ]));
    }
}
