<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Utils;

use PhpMyAdmin\Tests\Stubs\IniReaderStub;
use PhpMyAdmin\Utils\IniReader;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Group;
use PHPUnit\Framework\TestCase;

#[CoversClass(IniReader::class)]
final class IniReaderTest extends TestCase
{
    #[DataProvider('memoryLimitProvider')]
    #[Group('32bit-incompatible')]
    public function testGetMemoryLimitInBytes(string $value, int $expected): void
    {
        self::assertSame($expected, (new IniReaderStub(['memory_limit' => $value]))->getMemoryLimitInBytes());
    }

    /** @return array<array{string, int}> */
    public static function memoryLimitProvider(): array
    {
        return [
            ['', 0],
            ['0', 0],
            ['-1', -1],
            ['1kb', 1],
            ['1024k', 1024 * 1024],
            ['8m', 8 * 1024 * 1024],
            ['12gb', 12],
            ['1024', 1024],
            ['8000m', 8 * 1000 * 1024 * 1024],
            ['8G', 8 * 1024 * 1024 * 1024],
            ['2048K', 2048 * 1024],
            ['102400K', 102400 * 1024],
        ];
    }

    #[DataProvider('uploadSizeProvider')]
    public function testGetUploadSizeInBytes(int $expected, string $uploadMaxFilesize, string $postMaxSize): void
    {
        $iniReader = new IniReaderStub(['upload_max_filesize' => $uploadMaxFilesize, 'post_max_size' => $postMaxSize]);
        self::assertSame($expected, $iniReader->getUploadSizeInBytes());
    }

    /** @return array<string, array{int, string, string}> */
    public static function uploadSizeProvider(): array
    {
        return [
            'upload limit is lower' => [2 * 1024 * 1024, '2M', '8M'],
            'post limit is lower' => [1024 * 1024, '2M', '1M'],
            'no upload limit uses 5M' => [5 * 1024 * 1024, '', '8M'],
            'no post limit' => [2 * 1024 * 1024, '2M', ''],
            'post limit 0 disables the limit' => [2 * 1024 * 1024, '2M', '0'],
        ];
    }
}
