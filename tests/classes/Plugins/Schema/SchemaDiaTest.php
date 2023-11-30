<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Schema;

use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Plugins\Schema\SchemaDia;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\SelectPropertyItem;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SchemaDia::class)]
final class SchemaDiaTest extends AbstractTestCase
{
    public function testGetName(): void
    {
        self::assertSame('dia', (new SchemaDia())->getName());
    }

    public function testSetProperties(): void
    {
        $properties = (new SchemaDia())->getProperties();
        self::assertSame('Dia', $properties->getText());
        self::assertSame('dia', $properties->getExtension());
        self::assertSame('application/dia', $properties->getMimeType());
        $options = $properties->getOptions();
        self::assertNotNull($options);
        self::assertSame('Format Specific Options', $options->getName());
        $specificOptions = $options->getProperties();
        self::assertCount(1, $specificOptions);
        $specificOption = $specificOptions->current();
        self::assertInstanceOf(OptionsPropertyMainGroup::class, $specificOption);
        self::assertSame('general_opts', $specificOption->getName());
        self::assertCount(4, $specificOption);
        $specificOptionProperties = $specificOption->getProperties();

        $specificOptionProperty = $specificOptionProperties->current();
        self::assertInstanceOf(BoolPropertyItem::class, $specificOptionProperty);
        self::assertSame('show_color', $specificOptionProperty->getName());
        self::assertSame('Show color', $specificOptionProperty->getText());

        $specificOptionProperties->next();
        $specificOptionProperty = $specificOptionProperties->current();
        self::assertInstanceOf(BoolPropertyItem::class, $specificOptionProperty);
        self::assertSame('show_keys', $specificOptionProperty->getName());
        self::assertSame('Only show keys', $specificOptionProperty->getText());

        $specificOptionProperties->next();
        $specificOptionProperty = $specificOptionProperties->current();
        self::assertInstanceOf(SelectPropertyItem::class, $specificOptionProperty);
        self::assertSame('orientation', $specificOptionProperty->getName());
        self::assertSame('Orientation', $specificOptionProperty->getText());
        self::assertSame(['L' => 'Landscape', 'P' => 'Portrait'], $specificOptionProperty->getValues());

        $specificOptionProperties->next();
        $specificOptionProperty = $specificOptionProperties->current();
        self::assertInstanceOf(SelectPropertyItem::class, $specificOptionProperty);
        self::assertSame('paper', $specificOptionProperty->getName());
        self::assertSame('Paper size', $specificOptionProperty->getText());
        self::assertSame(
            ['A3' => 'A3', 'A4' => 'A4', 'A5' => 'A5', 'letter' => 'letter', 'legal' => 'legal'],
            $specificOptionProperty->getValues(),
        );
    }

    public function testGetExportInfo(): void
    {
        $_REQUEST['page_number'] = '0';
        $_REQUEST['dia_orientation'] = 'L';
        $_REQUEST['dia_paper'] = 'A4';

        $actual = (new SchemaDia())->getExportInfo(DatabaseName::from('test_db'));

        self::assertSame('test_db.dia', $actual['fileName']);
        self::assertSame('application/x-dia-diagram', $actual['mediaType']);
        self::assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $actual['fileData']);
    }
}
