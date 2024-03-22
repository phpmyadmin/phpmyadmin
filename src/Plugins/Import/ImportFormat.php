<?php

declare(strict_types=1);

namespace PhpMyAdmin\Plugins\Import;

use PhpMyAdmin\Plugins\ImportPlugin;

enum ImportFormat: string
{
    case Csv = 'csv';
    case Ldi = 'ldi';
    case Mediawiki = 'mediawiki';
    case Ods = 'ods';
    case Shp = 'shp';
    case Sql = 'sql';
    case Xml = 'xml';

    /** @return class-string<ImportPlugin> */
    public function getClassName(): string
    {
        return match ($this) {
            ImportFormat::Csv => ImportCsv::class,
            ImportFormat::Ldi => ImportLdi::class,
            ImportFormat::Mediawiki => ImportMediawiki::class,
            ImportFormat::Ods => ImportOds::class,
            ImportFormat::Shp => ImportShp::class,
            ImportFormat::Sql => ImportSql::class,
            ImportFormat::Xml => ImportXml::class,
        };
    }
}
