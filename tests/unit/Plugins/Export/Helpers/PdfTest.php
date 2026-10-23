<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Export\Helpers;

use PhpMyAdmin\Column;
use PhpMyAdmin\ConfigStorage\Foreigners;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Dbal\DatabaseInterface;
use PhpMyAdmin\Plugins\Export\Helpers\Pdf;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Transformations;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

use function array_slice;
use function count;

#[CoversClass(Pdf::class)]
final class PdfTest extends AbstractTestCase
{
    /** @param list<string> $expected */
    #[DataProvider('providerForTestGetTableDef')]
    public function testGetTableDef(Foreigners $foreigners, array $expected): void
    {
        $dbi = self::createStub(DatabaseInterface::class);
        $dbi->method('getColumns')->willReturn([
            new Column('id', 'int(11)', null, false, 'PRI', null, '', '', 'Primary key'),
        ]);
        $relation = self::createStub(Relation::class);
        $relation->method('getRelationParameters')->willReturn(RelationParameters::fromArray([]));
        $relation->method('getForeigners')->willReturn($foreigners);
        $relation->method('getComments')->willReturn(['id' => 'Primary key']);

        /** @psalm-suppress PropertyNotSetInConstructor */
        $pdf = new class extends Pdf {
            /** @var list<mixed> */
            public array $cells = [];

            // phpcs:ignore PSR1.Methods.CamelCapsMethodName.NotCamelCaps
            public function MultiCell(
                mixed $w,
                mixed $h,
                mixed $txt,
                mixed $border = 0,
                mixed $align = 'J',
                mixed $fill = false,
                mixed $ln = 1,
                mixed $x = null,
                mixed $y = null,
                mixed $reseth = true,
                mixed $stretch = 0,
                mixed $ishtml = false,
                mixed $autopadding = true,
                mixed $maxh = 0,
                mixed $valign = 'T',
                mixed $fitcell = false,
            ): int {
                $this->cells[] = $txt;

                return 1;
            }
        };

        $pdf->getTableDef(
            $dbi,
            $relation,
            self::createStub(Transformations::class),
            'db',
            'table',
            true,
            true,
            true,
        );

        self::assertSame($expected, array_slice($pdf->cells, -count($expected)));
    }

    /** @return iterable<string, array{Foreigners, list<string>}> */
    public static function providerForTestGetTableDef(): iterable
    {
        yield 'without foreign keys' => [new Foreigners(), ['id', 'int(11)', 'No', '', 'Primary key']];
        yield 'with foreign keys' => [
            new Foreigners(['id' => ['foreign_table' => 'other', 'foreign_field' => 'other_id']]),
            ['id', 'int(11)', 'No', '', 'other (other_id)', 'Primary key'],
        ];
    }
}
