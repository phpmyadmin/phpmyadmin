<?php

declare(strict_types=1);

namespace PhpMyAdmin\Engines\Innodb;

use function is_numeric;

/**
 * @see https://dev.mysql.com/doc/refman/en/innodb-buffer-pool.html
 * @see https://mariadb.com/docs/server/server-usage/storage-engines/innodb/innodb-buffer-pool
 */
final readonly class BufferPool
{
    /**
     * @param numeric-string      $innodbPageSize Innodb_page_size
     * @param numeric-string      $pagesData      Innodb_buffer_pool_pages_data
     * @param numeric-string      $pagesDirty     Innodb_buffer_pool_pages_dirty
     * @param numeric-string      $pagesFlushed   Innodb_buffer_pool_pages_flushed
     * @param numeric-string      $pagesFree      Innodb_buffer_pool_pages_free
     * @param numeric-string      $pagesMisc      Innodb_buffer_pool_pages_misc
     * @param numeric-string      $pagesTotal     Innodb_buffer_pool_pages_total
     * @param numeric-string      $readRequests   Innodb_buffer_pool_read_requests
     * @param numeric-string      $reads          Innodb_buffer_pool_reads
     * @param numeric-string      $waitFree       Innodb_buffer_pool_wait_free
     * @param numeric-string      $writeRequests  Innodb_buffer_pool_write_requests
     * @param numeric-string|null $pagesLatched   Innodb_buffer_pool_pages_latched
     */
    public function __construct(
        public string $innodbPageSize,
        public string $pagesData,
        public string $pagesDirty,
        public string $pagesFlushed,
        public string $pagesFree,
        public string $pagesMisc,
        public string $pagesTotal,
        public string $readRequests,
        public string $reads,
        public string $waitFree,
        public string $writeRequests,
        public string|null $pagesLatched,
    ) {
    }

    /** @param array<string|null> $result */
    public static function fromResult(array $result): self
    {
        return new self(
            self::getNumeric($result['Innodb_page_size'] ?? null) ?? '0',
            self::getNumeric($result['Innodb_buffer_pool_pages_data'] ?? null) ?? '0',
            self::getNumeric($result['Innodb_buffer_pool_pages_dirty'] ?? null) ?? '0',
            self::getNumeric($result['Innodb_buffer_pool_pages_flushed'] ?? null) ?? '0',
            self::getNumeric($result['Innodb_buffer_pool_pages_free'] ?? null) ?? '0',
            self::getNumeric($result['Innodb_buffer_pool_pages_misc'] ?? null) ?? '0',
            self::getNumeric($result['Innodb_buffer_pool_pages_total'] ?? null) ?? '0',
            self::getNumeric($result['Innodb_buffer_pool_read_requests'] ?? null) ?? '0',
            self::getNumeric($result['Innodb_buffer_pool_reads'] ?? null) ?? '0',
            self::getNumeric($result['Innodb_buffer_pool_wait_free'] ?? null) ?? '0',
            self::getNumeric($result['Innodb_buffer_pool_write_requests'] ?? null) ?? '0',
            self::getNumeric($result['Innodb_buffer_pool_pages_latched'] ?? null),
        );
    }

    /** @return numeric-string|null */
    private static function getNumeric(string|null $value): string|null
    {
        return is_numeric($value) ? $value : null;
    }
}
