<?php

declare(strict_types=1);

namespace PhpMyAdmin\Table;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\Query\Compatibility;
use PhpMyAdmin\Query\Generator as QueryGenerator;

use function __;

final class Indexes
{
    public function __construct(
        private DatabaseInterface $dbi,
    ) {
    }

    /**
     * Process the data from the edit/create index form,
     * run the query to build the new index
     * and moves back to /table/sql
     *
     * @param Index $index      An Index instance.
     * @param bool  $renameMode Rename the Index mode
     */
    public function doSaveData(
        Index $index,
        bool $renameMode,
        string $db,
        string $table,
        bool $previewSql,
        string $oldIndexName = '',
    ): string|Message {
        $error = false;
        if ($renameMode && Compatibility::isCompatibleRenameIndex($this->dbi->getVersion())) {
            if ($oldIndexName === 'PRIMARY') {
                if ($index->getName() === '') {
                    $index->setName('PRIMARY');
                } elseif ($index->getName() !== 'PRIMARY') {
                    $error = Message::error(
                        __('The name of the primary key must be "PRIMARY"!'),
                    );
                }
            }

            $sqlQuery = QueryGenerator::getSqlQueryForIndexRename(
                $db,
                $table,
                $oldIndexName,
                $index->getName(),
            );
        } else {
            $sqlQuery = $this->dbi->getTable($db, $table)->getSqlQueryForIndexCreateOrEdit($index, $error);
        }

        // If there is a request for SQL previewing.
        if ($previewSql) {
            return $sqlQuery;
        }

        if ($error instanceof Message) {
            return $error;
        }

        $this->dbi->query($sqlQuery);

        return $sqlQuery;
    }

    public function executeAddIndexSql(string|DatabaseName $db, string $sql): Message
    {
        $this->dbi->selectDb($db);
        $result = $this->dbi->tryQuery($sql);

        if (! $result) {
            return Message::error($this->dbi->getError());
        }

        return Message::success();
    }
}
