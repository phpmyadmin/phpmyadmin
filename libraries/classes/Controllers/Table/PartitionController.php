<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Message;
use PhpMyAdmin\Response;
use PhpMyAdmin\Table\Partition;
use PhpMyAdmin\Template;
use Throwable;

use function strlen;

final class PartitionController extends AbstractController
{
    /** @var Partition */
    private $model;

    /**
     * @param Response  $response
     * @param string    $db
     * @param string    $table
     * @param Partition $partition
     */
    public function __construct($response, Template $template, $db, $table, $partition)
    {
        parent::__construct($response, $template, $db, $table);
        $this->model = $partition;
    }

    public function analyze(): void
    {
        $partitionName = $_POST['partition_name'] ?? '';

        if (strlen($partitionName) === 0) {
            return;
        }

        try {
            [$rows, $query] = $this->model->analyze(new DatabaseName($this->db), $this->table, $partitionName);
        } catch (Throwable $e) {
            $message = Message::error($e->getMessage());
            $this->response->addHTML($message->getDisplay());

            return;
        }

        $message = Generator::getMessage(
            __('Your SQL query has been executed successfully.'),
            $query,
            'success'
        );

        $this->render('table/partition/analyze', [
            'partition_name' => $partitionName,
            'message' => $message,
            'rows' => $rows,
        ]);
    }

    public function check(): void
    {
        $partitionName = $_POST['partition_name'] ?? '';

        if (strlen($partitionName) === 0) {
            return;
        }

        try {
            [$rows, $query] = $this->model->check(new DatabaseName($this->db), $this->table, $partitionName);
        } catch (Throwable $e) {
            $message = Message::error($e->getMessage());
            $this->response->addHTML($message->getDisplay());

            return;
        }

        $message = Generator::getMessage(
            __('Your SQL query has been executed successfully.'),
            $query,
            'success'
        );

        $this->render('table/partition/check', [
            'partition_name' => $partitionName,
            'message' => $message,
            'rows' => $rows,
        ]);
    }

    public function drop(): void
    {
        $partitionName = $_POST['partition_name'] ?? '';

        if (strlen($partitionName) === 0) {
            return;
        }

        try {
            [$result, $query] = $this->model->drop(new DatabaseName($this->db), $this->table, $partitionName);
        } catch (Throwable $e) {
            $message = Message::error($e->getMessage());
            $this->response->addHTML($message->getDisplay());

            return;
        }

        if ($result) {
            $message = Generator::getMessage(
                __('Your SQL query has been executed successfully.'),
                $query,
                'success'
            );
        } else {
            $message = Generator::getMessage(
                __('Error'),
                $query,
                'error'
            );
        }

        $this->render('table/partition/drop', [
            'partition_name' => $partitionName,
            'message' => $message,
        ]);
    }

    public function optimize(): void
    {
        $partitionName = $_POST['partition_name'] ?? '';

        if (strlen($partitionName) === 0) {
            return;
        }

        try {
            [$rows, $query] = $this->model->optimize(new DatabaseName($this->db), $this->table, $partitionName);
        } catch (Throwable $e) {
            $message = Message::error($e->getMessage());
            $this->response->addHTML($message->getDisplay());

            return;
        }

        $message = Generator::getMessage(
            __('Your SQL query has been executed successfully.'),
            $query,
            'success'
        );

        $this->render('table/partition/optimize', [
            'partition_name' => $partitionName,
            'message' => $message,
            'rows' => $rows,
        ]);
    }

    public function rebuild(): void
    {
        $partitionName = $_POST['partition_name'] ?? '';

        if (strlen($partitionName) === 0) {
            return;
        }

        try {
            [$result, $query] = $this->model->rebuild(new DatabaseName($this->db), $this->table, $partitionName);
        } catch (Throwable $e) {
            $message = Message::error($e->getMessage());
            $this->response->addHTML($message->getDisplay());

            return;
        }

        if ($result) {
            $message = Generator::getMessage(
                __('Your SQL query has been executed successfully.'),
                $query,
                'success'
            );
        } else {
            $message = Generator::getMessage(
                __('Error'),
                $query,
                'error'
            );
        }

        $this->render('table/partition/rebuild', [
            'partition_name' => $partitionName,
            'message' => $message,
        ]);
    }

    public function repair(): void
    {
        $partitionName = $_POST['partition_name'] ?? '';

        if (strlen($partitionName) === 0) {
            return;
        }

        try {
            [$rows, $query] = $this->model->repair(new DatabaseName($this->db), $this->table, $partitionName);
        } catch (Throwable $e) {
            $message = Message::error($e->getMessage());
            $this->response->addHTML($message->getDisplay());

            return;
        }

        $message = Generator::getMessage(
            __('Your SQL query has been executed successfully.'),
            $query,
            'success'
        );

        $this->render('table/partition/repair', [
            'partition_name' => $partitionName,
            'message' => $message,
            'rows' => $rows,
        ]);
    }

    public function truncate(): void
    {
        $partitionName = $_POST['partition_name'] ?? '';

        if (strlen($partitionName) === 0) {
            return;
        }

        try {
            [$result, $query] = $this->model->truncate(new DatabaseName($this->db), $this->table, $partitionName);
        } catch (Throwable $e) {
            $message = Message::error($e->getMessage());
            $this->response->addHTML($message->getDisplay());

            return;
        }

        if ($result) {
            $message = Generator::getMessage(
                __('Your SQL query has been executed successfully.'),
                $query,
                'success'
            );
        } else {
            $message = Generator::getMessage(
                __('Error'),
                $query,
                'error'
            );
        }

        $this->render('table/partition/truncate', [
            'partition_name' => $partitionName,
            'message' => $message,
        ]);
    }
}
