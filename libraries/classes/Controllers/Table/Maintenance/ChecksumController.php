<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table\Maintenance;

use PhpMyAdmin\Controllers\Table\AbstractController;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\ResponseRenderer;
use PhpMyAdmin\Table\Maintenance;
use PhpMyAdmin\Template;

use function __;
use function count;
use function is_array;

final class ChecksumController extends AbstractController
{
    /** @var Maintenance */
    private $model;

    public function __construct(
        ResponseRenderer $response,
        Template $template,
        string $db,
        string $table,
        Maintenance $model
    ) {
        parent::__construct($response, $template, $db, $table);
        $this->model = $model;
    }

    public function __invoke(): void
    {
        global $cfg;

        /** @var string[] $selected */
        $selected = $_POST['selected_tbl'] ?? [];

        if (empty($selected) || ! is_array($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return;
        }

        if ($cfg['DisableMultiTableMaintenance'] && count($selected) > 1) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('Maintenance operations on multiple tables are disabled.'));

            return;
        }

        [$rows, $query, $warnings] = $this->model->getChecksumTableRows($this->db, $selected);

        $message = Generator::getMessage(
            __('Your SQL query has been executed successfully.'),
            $query,
            'success'
        );

        $this->render('table/maintenance/checksum', [
            'message' => $message,
            'rows' => $rows,
            'warnings' => $warnings,
        ]);
    }
}
