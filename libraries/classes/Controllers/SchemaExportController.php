<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Export;
use PhpMyAdmin\Relation;
use PhpMyAdmin\Util;

/**
 * Schema export handler
 */
class SchemaExportController
{
    /** @var Export */
    private $export;

    /** @var Relation */
    private $relation;

    /**
     * @param Export   $export   A Export instance.
     * @param Relation $relation A Relation instance.
     */
    public function __construct(Export $export, Relation $relation)
    {
        $this->export = $export;
        $this->relation = $relation;
    }

    public function index(): void
    {
        global $cfgRelation;

        /**
         * get all variables needed for exporting relational schema
         * in $cfgRelation
         */
        $cfgRelation = $this->relation->getRelationsParam();

        if (! isset($_POST['export_type'])) {
            Util::checkParameters(['export_type']);
        }

        /**
         * Include the appropriate Schema Class depending on $export_type
         * default is PDF
         */
        $this->export->processExportSchema($_POST['export_type']);
    }
}
