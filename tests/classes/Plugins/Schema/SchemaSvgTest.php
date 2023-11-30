<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Schema;

use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Plugins\Schema\SchemaSvg;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SchemaSvg::class)]
final class SchemaSvgTest extends AbstractTestCase
{
    public function testGetName(): void
    {
        self::assertSame('svg', (new SchemaSvg())->getName());
    }

    public function testSetProperties(): void
    {
        $properties = (new SchemaSvg())->getProperties();
        self::assertSame('SVG', $properties->getText());
        self::assertSame('svg', $properties->getExtension());
        self::assertSame('application/svg', $properties->getMimeType());
        $options = $properties->getOptions();
        self::assertNotNull($options);
        self::assertSame('Format Specific Options', $options->getName());
        $specificOptions = $options->getProperties();
        self::assertCount(1, $specificOptions);
        $specificOption = $specificOptions->current();
        self::assertInstanceOf(OptionsPropertyMainGroup::class, $specificOption);
        self::assertSame('general_opts', $specificOption->getName());
        self::assertCount(3, $specificOption);
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
        self::assertInstanceOf(BoolPropertyItem::class, $specificOptionProperty);
        self::assertSame('all_tables_same_width', $specificOptionProperty->getName());
        self::assertSame('Same width for all tables', $specificOptionProperty->getText());
    }

    public function testGetExportInfo(): void
    {
        $_REQUEST['page_number'] = '0';

        $actual = (new SchemaSvg())->getExportInfo(DatabaseName::from('test_db'));

        self::assertSame('test_db.svg', $actual['fileName']);
        self::assertSame('image/svg+xml', $actual['mediaType']);
        self::assertStringStartsWith('<?xml version="1.0" encoding="UTF-8"?>', $actual['fileData']);
    }
}
