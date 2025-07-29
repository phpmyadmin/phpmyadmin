<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Engines\Innodb;

use PhpMyAdmin\Engines\Innodb\BufferPool;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(BufferPool::class)]
final class BufferPoolTest extends TestCase
{
    /**
     * @param array<string|null>  $result
     * @param numeric-string      $innodbPageSize
     * @param numeric-string      $pagesData
     * @param numeric-string      $pagesDirty
     * @param numeric-string      $pagesFlushed
     * @param numeric-string      $pagesFree
     * @param numeric-string      $pagesMisc
     * @param numeric-string      $pagesTotal
     * @param numeric-string      $readRequests
     * @param numeric-string      $reads
     * @param numeric-string      $waitFree
     * @param numeric-string      $writeRequests
     * @param numeric-string|null $pagesLatched
     */
    #[DataProvider('resultProvider')]
    public function testCreateFromResult(
        array $result,
        string $innodbPageSize,
        string $pagesData,
        string $pagesDirty,
        string $pagesFlushed,
        string $pagesFree,
        string $pagesMisc,
        string $pagesTotal,
        string $readRequests,
        string $reads,
        string $waitFree,
        string $writeRequests,
        string|null $pagesLatched,
    ): void {
        $bufferPool = BufferPool::fromResult($result);
        self::assertSame($innodbPageSize, $bufferPool->innodbPageSize);
        self::assertSame($pagesData, $bufferPool->pagesData);
        self::assertSame($pagesDirty, $bufferPool->pagesDirty);
        self::assertSame($pagesFlushed, $bufferPool->pagesFlushed);
        self::assertSame($pagesFree, $bufferPool->pagesFree);
        self::assertSame($pagesMisc, $bufferPool->pagesMisc);
        self::assertSame($pagesTotal, $bufferPool->pagesTotal);
        self::assertSame($readRequests, $bufferPool->readRequests);
        self::assertSame($reads, $bufferPool->reads);
        self::assertSame($waitFree, $bufferPool->waitFree);
        self::assertSame($writeRequests, $bufferPool->writeRequests);
        self::assertSame($pagesLatched, $bufferPool->pagesLatched);
    }

    /**
     * @return iterable<array-key, array{
     *     array<string|null>,
     *     numeric-string,
     *     numeric-string,
     *     numeric-string,
     *     numeric-string,
     *     numeric-string,
     *     numeric-string,
     *     numeric-string,
     *     numeric-string,
     *     numeric-string,
     *     numeric-string,
     *     numeric-string,
     *     numeric-string|null
     * }>
     */
    public static function resultProvider(): iterable
    {
        yield [[], '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', null];

        $result = [
            'Innodb_page_size' => '1',
            'Innodb_buffer_pool_pages_data' => '2',
            'Innodb_buffer_pool_pages_dirty' => '3',
            'Innodb_buffer_pool_pages_flushed' => '4',
            'Innodb_buffer_pool_pages_free' => '5',
            'Innodb_buffer_pool_pages_misc' => '6',
            'Innodb_buffer_pool_pages_total' => '7',
            'Innodb_buffer_pool_read_requests' => '8',
            'Innodb_buffer_pool_reads' => '9',
            'Innodb_buffer_pool_wait_free' => '10',
            'Innodb_buffer_pool_write_requests' => '11',
            'Innodb_buffer_pool_pages_latched' => '12',
        ];

        yield [$result, '1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];

        $result = [
            'Innodb_page_size' => null,
            'Innodb_buffer_pool_pages_data' => null,
            'Innodb_buffer_pool_pages_dirty' => null,
            'Innodb_buffer_pool_pages_flushed' => null,
            'Innodb_buffer_pool_pages_free' => null,
            'Innodb_buffer_pool_pages_misc' => null,
            'Innodb_buffer_pool_pages_total' => null,
            'Innodb_buffer_pool_read_requests' => null,
            'Innodb_buffer_pool_reads' => null,
            'Innodb_buffer_pool_wait_free' => null,
            'Innodb_buffer_pool_write_requests' => null,
            'Innodb_buffer_pool_pages_latched' => null,
        ];

        yield [$result, '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', '0', null];
    }
}
