<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Plugins\Schema;

use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Plugins\Schema\SchemaEps;
use PhpMyAdmin\Properties\Options\Groups\OptionsPropertyMainGroup;
use PhpMyAdmin\Properties\Options\Items\BoolPropertyItem;
use PhpMyAdmin\Properties\Options\Items\SelectPropertyItem;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(SchemaEps::class)]
final class SchemaEpsTest extends AbstractTestCase
{
    public function testGetName(): void
    {
        self::assertSame('eps', (new SchemaEps())->getName());
    }

    public function testSetProperties(): void
    {
        $properties = (new SchemaEps())->getProperties();
        self::assertSame('EPS', $properties->getText());
        self::assertSame('eps', $properties->getExtension());
        self::assertSame('application/eps', $properties->getMimeType());
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
        self::assertInstanceOf(BoolPropertyItem::class, $specificOptionProperty);
        self::assertSame('all_tables_same_width', $specificOptionProperty->getName());
        self::assertSame('Same width for all tables', $specificOptionProperty->getText());

        $specificOptionProperties->next();
        $specificOptionProperty = $specificOptionProperties->current();
        self::assertInstanceOf(SelectPropertyItem::class, $specificOptionProperty);
        self::assertSame('orientation', $specificOptionProperty->getName());
        self::assertSame('Orientation', $specificOptionProperty->getText());
        self::assertSame(['L' => 'Landscape', 'P' => 'Portrait'], $specificOptionProperty->getValues());
    }

    public function testGetExportInfo(): void
    {
        $_REQUEST['page_number'] = '0';
        $_REQUEST['eps_orientation'] = 'L';

        $actual = (new SchemaEps())->getExportInfo(DatabaseName::from('test_db'));

        self::assertSame('test_db.eps', $actual['fileName']);
        self::assertSame('image/x-eps', $actual['mediaType']);
        self::assertStringStartsWith('%!PS-Adobe-3.0 EPSF-3.0', $actual['fileData']);
    }
}
