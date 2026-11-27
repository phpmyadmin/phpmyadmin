<?php

declare(strict_types=1);

namespace PhpMyAdmin\Table;

use PhpMyAdmin\ConfigStorage\Foreigners;

/**
 * Columns of a table as shown in the table search forms.
 */
final readonly class SearchColumns
{
    /**
     * @param list<string> $names         Names of columns
     * @param list<string> $types         Types of columns, reformatted for display
     * @param list<string> $originalTypes Types of columns without any replacement
     * @param list<string> $collations    Collations of columns
     * @param list<bool>   $nullFlags     Null flags of columns
     * @param bool         $hasGeometry   Whether a geometry column is present
     */
    public function __construct(
        public array $names,
        public array $types,
        public array $originalTypes,
        public array $collations,
        public array $nullFlags,
        public bool $hasGeometry,
        public Foreigners $foreigners,
    ) {
    }
}
