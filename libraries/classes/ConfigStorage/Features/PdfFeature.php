<?php

declare(strict_types=1);

namespace PhpMyAdmin\ConfigStorage\Features;

use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;

/** @psalm-immutable */
final class PdfFeature
{
    public DatabaseName $database;

    public TableName $pdfPages;

    public TableName $tableCoords;

    public function __construct(DatabaseName $database, TableName $pdfPages, TableName $tableCoords)
    {
        $this->database = $database;
        $this->pdfPages = $pdfPages;
        $this->tableCoords = $tableCoords;
    }
}
