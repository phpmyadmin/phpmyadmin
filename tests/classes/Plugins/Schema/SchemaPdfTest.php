<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Schema;

use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Plugins\Schema\SchemaPdf;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\SelectPropertyItem;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SchemaPdf::class)]
final class SchemaPdfTest extends AbstractTestCase
{
    public function testGetName(): void
    {
        self::assertSame('pdf', (new SchemaPdf())->getName());
    }

    public function testSetProperties(): void
    {
        $properties = (new SchemaPdf())->getProperties();
        self::assertSame('PDF', $properties->getText());
        self::assertSame('pdf', $properties->getExtension());
        self::assertSame('application/pdf', $properties->getMimeType());
        $options = $properties->getOptions();
        self::assertNotNull($options);
        self::assertSame('Format Specific Options', $options->getName());
        $specificOptions = $options->getProperties();
        self::assertCount(1, $specificOptions);
        $specificOption = $specificOptions->current();
        self::assertInstanceOf(OptionsPropertyMainGroup::class, $specificOption);
        self::assertSame('general_opts', $specificOption->getName());
        self::assertCount(8, $specificOption);
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

        $specificOptionProperties->next();
        $specificOptionProperty = $specificOptionProperties->current();
        self::assertInstanceOf(BoolPropertyItem::class, $specificOptionProperty);
        self::assertSame('show_grid', $specificOptionProperty->getName());
        self::assertSame('Show grid', $specificOptionProperty->getText());

        $specificOptionProperties->next();
        $specificOptionProperty = $specificOptionProperties->current();
        self::assertInstanceOf(BoolPropertyItem::class, $specificOptionProperty);
        self::assertSame('with_doc', $specificOptionProperty->getName());
        self::assertSame('Data dictionary', $specificOptionProperty->getText());

        $specificOptionProperties->next();
        $specificOptionProperty = $specificOptionProperties->current();
        self::assertInstanceOf(SelectPropertyItem::class, $specificOptionProperty);
        self::assertSame('table_order', $specificOptionProperty->getName());
        self::assertSame('Order of the tables', $specificOptionProperty->getText());
        self::assertSame(
            ['' => 'None', 'name_asc' => 'Name (Ascending)', 'name_desc' => 'Name (Descending)'],
            $specificOptionProperty->getValues(),
        );
    }

    public function testGetExportInfo(): void
    {
        if (! SchemaPdf::isAvailable()) {
            self::markTestSkipped('SchemaPdf plugin is not available.');
        }

        $_REQUEST['page_number'] = '0';
        $_REQUEST['pdf_table_order'] = '';
        $_REQUEST['pdf_orientation'] = 'L';
        $_REQUEST['pdf_paper'] = 'A4';

        $actual = (new SchemaPdf())->getExportInfo(DatabaseName::from('test_db'));

        self::assertSame('test_db.pdf', $actual['fileName']);
        self::assertSame('application/pdf', $actual['mediaType']);
        self::assertNotEmpty($actual['fileData']);
    }
}
