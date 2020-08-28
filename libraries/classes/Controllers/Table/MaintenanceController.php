<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Index;
use PhpMyAdmin\Util;
use function implode;
use function sprintf;

final class MaintenanceController extends AbstractController
{
    public function analyze(): void
    {
        /** @var string[] $selected */
        $selected = $_POST['selected_tbl'] ?? [];

        if (empty($selected)) {
            $this->response->setRequestStatus(false);
            $this->response->addJSON('message', __('No table selected.'));

            return;
        }

        $tables = Util::backquote($selected);
        $query = 'ANALYZE TABLE ' . implode(', ', $tables) . ';';

        $this->dbi->selectDb($this->db);
        $rows = $this->dbi->fetchResult($query);

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

        $tables = Util::backquote($selected);
        $query = 'CHECK TABLE ' . implode(', ', $tables) . ';';

        $this->dbi->selectDb($this->db);
        $rows = $this->dbi->fetchResult($query);

        $message = Generator::getMessage(
            __('Your SQL query has been executed successfully.'),
            $query,
            'success'
        );

        $indexesProblems = '';
        foreach ($selected as $table) {
            $check = Index::findDuplicates($table, $this->db);

            if (empty($check)) {
                continue;
            }

            $indexesProblems .= sprintf(__('Problems with indexes of table `%s`'), $table);
            $indexesProblems .= $check;
        }

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

        $tables = Util::backquote($selected);
        $query = 'CHECKSUM TABLE ' . implode(', ', $tables) . ';';

        $this->dbi->selectDb($this->db);
        $rows = $this->dbi->fetchResult($query);

        $message = Generator::getMessage(
            __('Your SQL query has been executed successfully.'),
            $query,
            'success'
        );

        $warnings = $this->dbi->getWarnings();

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

        $tables = Util::backquote($selected);
        $query = 'OPTIMIZE TABLE ' . implode(', ', $tables) . ';';

        $this->dbi->selectDb($this->db);
        $rows = $this->dbi->fetchResult($query);

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

        $tables = Util::backquote($selected);
        $query = 'REPAIR TABLE ' . implode(', ', $tables) . ';';

        $this->dbi->selectDb($this->db);
        $rows = $this->dbi->fetchResult($query);

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
