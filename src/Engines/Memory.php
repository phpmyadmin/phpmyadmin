<?php
/**
 * The MEMORY (HEAP) storage engine
 */

declare(strict_types=1);

namespace PhpMyAdmin\Engines;

use PhpMyAdmin\StorageEngine;

/**
 * The MEMORY (HEAP) storage engine
 */
class Memory extends StorageEngine
{
    /**
     * Returns array with variable names dedicated to MEMORY storage engine
     *
     * @return array<string, array{title?: string, desc?: string, type?: int}> variable names
     */
    public function getVariables(): array
    {
        return ['max_heap_table_size' => ['type' => StorageEngine::DETAILS_TYPE_SIZE]];
    }
}
