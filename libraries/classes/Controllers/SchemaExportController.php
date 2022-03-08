<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers;

use PhpMyAdmin\Core;
use PhpMyAdmin\Export;
use PhpMyAdmin\Html\MySQLDocumentation;

use function __;

/**
 * Schema export handler
 */
class SchemaExportController
{
    /** @var Export */
    private $export;

    public function __construct(Export $export)
    {
        $this->export = $export;
    }

    public function __invoke(): void
    {
        if (! isset($_POST['export_type'])) {
            $errorMessage = __('Missing parameter:') . ' export_type'
                . MySQLDocumentation::showDocumentation('faq', 'faqmissingparameters', true)
                . '[br]';
            Core::fatalError($errorMessage);

            return;
        }

        /**
         * Include the appropriate Schema Class depending on $export_type
         * default is PDF
         */
        $this->export->processExportSchema($_POST['export_type']);
    }
}
