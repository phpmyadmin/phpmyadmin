<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Export;

use PhpMyAdmin\Export\Template;
use PhpMyAdmin\Plugins\ExportType;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

#[CoversClass(Template::class)]
final class TemplateTest extends TestCase
{
    /** @param array<string, int|string> $state */
    #[DataProvider('fromArrayProvider')]
    public function testFromArray(
        int $id,
        string $username,
        ExportType $exportType,
        string $name,
        string $data,
        array $state,
    ): void {
        $template = Template::fromArray($state);
        self::assertSame($id, $template->getId());
        self::assertSame($username, $template->getUsername());
        self::assertSame($exportType, $template->getExportType());
        self::assertSame($name, $template->getName());
        self::assertSame($data, $template->getData());
    }

    /** @return iterable<int, array{int, string, ExportType, string, string, array<string, int|string>}> */
    public static function fromArrayProvider(): iterable
    {
        yield [0, '', ExportType::Server, '', '', ['username' => '', 'data' => '']];
        yield [0, '', ExportType::Server, '', '', ['username' => '', 'data' => '', 'exportType' => 'server']];
        yield [0, '', ExportType::Server, '', '', ['username' => '', 'data' => '', 'exportType' => 'invalid']];
        yield [0, '', ExportType::Database, '', '', ['username' => '', 'data' => '', 'exportType' => 'database']];
        yield [0, '', ExportType::Table, '', '', ['username' => '', 'data' => '', 'exportType' => 'table']];
        yield [0, '', ExportType::Raw, '', '', ['username' => '', 'data' => '', 'exportType' => 'raw']];
        yield [
            1,
            'test_user',
            ExportType::Database,
            'Template name',
            '{"template":"data"}',
            [
                'id' => 1,
                'username' => 'test_user',
                'exportType' => 'database',
                'name' => 'Template name',
                'data' => '{"template":"data"}',
            ],
        ];
    }
}
