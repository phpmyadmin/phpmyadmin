<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Response;
use PhpMyAdmin\Table\Maintenance;
use PhpMyAdmin\Template;

final class MaintenanceController extends AbstractController
{
    /** @var Maintenance */
    private $model;

    /**
     * @param Response          $response
     * @param DatabaseInterface $dbi
     * @param string            $db
     * @param string            $table
     */
    public function __construct(
        $response,
        $dbi,
        Template $template,
        $db,
        $table,
        Maintenance $model
    ) {
        parent::__construct($response, $dbi, $template, $db, $table);
        $this->model = $model;
    }

    public function analyze(): void
    {
        /** @var string[] $selected */
        $selected = $_POST['selected_tbl'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return;
        }

        [$rows, $query] = $this->model->getAnalyzeTableRows($this->db, $selected);

        $message = Generator::getMessage(
            __('Your SQL query has been executed successfully.'),
            $query,
            'success'
        );

        $this->render('table/maintenance/analyze', [
            'message' => $message,
            'rows' => $rows,
        ]);
    }

    public function check(): void
    {
        /** @var string[] $selected */
        $selected = $_POST['selected_tbl'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return;
        }

        [$rows, $query] = $this->model->getCheckTableRows($this->db, $selected);

        $message = Generator::getMessage(
            __('Your SQL query has been executed successfully.'),
            $query,
            'success'
        );

        $indexesProblems = $this->model->getIndexesProblems($this->db, $selected);

        $this->render('table/maintenance/check', [
            'message' => $message,
            'rows' => $rows,
            'indexes_problems' => $indexesProblems,
        ]);
    }

    public function checksum(): void
    {
        /** @var string[] $selected */
        $selected = $_POST['selected_tbl'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

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

    public function optimize(): void
    {
        /** @var string[] $selected */
        $selected = $_POST['selected_tbl'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return;
        }

        [$rows, $query] = $this->model->getOptimizeTableRows($this->db, $selected);

        $message = Generator::getMessage(
            __('Your SQL query has been executed successfully.'),
            $query,
            'success'
        );

        $this->render('table/maintenance/optimize', [
            'message' => $message,
            'rows' => $rows,
        ]);
    }

    public function repair(): void
    {
        /** @var string[] $selected */
        $selected = $_POST['selected_tbl'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return;
        }

        [$rows, $query] = $this->model->getRepairTableRows($this->db, $selected);

        $message = Generator::getMessage(
            __('Your SQL query has been executed successfully.'),
            $query,
            'success'
        );

        $this->render('table/maintenance/repair', [
            'message' => $message,
            'rows' => $rows,
        ]);
    }
}
