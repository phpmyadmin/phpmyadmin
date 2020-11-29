<?php

declare(strict_types=1);

namespace PhpMyAdmin\Controllers\Table;

use PhpMyAdmin\Controllers\SqlController;
use PhpMyAdmin\Util;
use function sprintf;
use function strlen;

final class PartitionController extends AbstractController
{
    public function analyze(): void
    {
        global $containerBuilder, $sql_query;

        $partitionName = $_POST['partition_name'] ?? '';

        if (strlen($partitionName) === 0) {
            return;
        }

        $sql_query = sprintf(
            'ALTER TABLE %s ANALYZE PARTITION %s;',
            Util::backquote($this->table),
            Util::backquote($partitionName)
        );

        /** @var SqlController $controller */
        $controller = $containerBuilder->get(SqlController::class);
        $controller->index();
    }

    public function check(): void
    {
        global $containerBuilder, $sql_query;

        $partitionName = $_POST['partition_name'] ?? '';

        if (strlen($partitionName) === 0) {
            return;
        }

        $sql_query = sprintf(
            'ALTER TABLE %s CHECK PARTITION %s;',
            Util::backquote($this->table),
            Util::backquote($partitionName)
        );

        /** @var SqlController $controller */
        $controller = $containerBuilder->get(SqlController::class);
        $controller->index();
    }

    public function optimize(): void
    {
        global $containerBuilder, $sql_query;

        $partitionName = $_POST['partition_name'] ?? '';

        if (strlen($partitionName) === 0) {
            return;
        }

        $sql_query = sprintf(
            'ALTER TABLE %s OPTIMIZE PARTITION %s;',
            Util::backquote($this->table),
            Util::backquote($partitionName)
        );

        /** @var SqlController $controller */
        $controller = $containerBuilder->get(SqlController::class);
        $controller->index();
    }
}
