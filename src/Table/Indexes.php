<?php

declare(strict_types=1);

namespace PhpMyAdmin\Table;

use PhpMyAdmin\DatabaseInterface;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Index;
use PhpMyAdmin\Message;
use PhpMyAdmin\Query\Compatibility;
use PhpMyAdmin\Query\Generator as QueryGenerator;
use PhpMyAdmin\Util;

use function __;
use function implode;
use function in_array;
use function sprintf;

final class Indexes
{
    private Message|null $error = null;

    public function __construct(private readonly DatabaseInterface $dbi)
    {
    }

    public function getError(): Message|null
    {
        return $this->error;
    }

    /**
     * Function to get the sql query for index creation or edit
     *
     * @param Index $index current index
     */
    public function getSqlQueryForIndexCreateOrEdit(
        string|null $oldIndexName,
        Index $index,
        string $dbName,
        string $tableName,
    ): string {
        // $sql_query is the one displayed in the query box
        $sqlQuery = sprintf(
            'ALTER TABLE %s.%s',
            Util::backquote($dbName),
            Util::backquote($tableName),
        );

        // Drops the old index
        if ($oldIndexName !== null) {
            if ($oldIndexName === 'PRIMARY') {
                $sqlQuery .= ' DROP PRIMARY KEY,';
            } else {
                $sqlQuery .= sprintf(
                    ' DROP INDEX %s,',
                    Util::backquote($oldIndexName),
                );
            }
        }

        // Builds the new one
        switch ($index->getChoice()) {
            case 'PRIMARY':
                if ($index->getName() == '') {
                    $index->setName('PRIMARY');
                } elseif ($index->getName() !== 'PRIMARY') {
                    $this->error = Message::error(
                        __('The name of the primary key must be "PRIMARY"!'),
                    );
                }

                $sqlQuery .= ' ADD PRIMARY KEY';
                break;
            case 'FULLTEXT':
            case 'UNIQUE':
            case 'INDEX':
            case 'SPATIAL':
                if ($index->getName() === 'PRIMARY') {
                    $this->error = Message::error(
                        __('Can\'t rename index to PRIMARY!'),
                    );
                }

                $sqlQuery .= sprintf(
                    ' ADD %s',
                    $index->getChoice(),
                );
                if ($index->getName() !== '') {
                    $sqlQuery .= ' ' . Util::backquote($index->getName());
                }

                break;
        }

        $indexFields = [];
        foreach ($index->getColumns() as $key => $column) {
            $indexFields[$key] = Util::backquote($column->getName());
            if (! $column->getSubPart()) {
                continue;
            }

            $indexFields[$key] .= '(' . $column->getSubPart() . ')';
        }

        if ($indexFields === []) {
            $this->error = Message::error(__('No index parts defined!'));
        } else {
            $sqlQuery .= ' (' . implode(', ', $indexFields) . ')';
        }

        $keyBlockSizes = $index->getKeyBlockSize();
        if ($keyBlockSizes !== 0) {
            $sqlQuery .= ' KEY_BLOCK_SIZE = ' . $keyBlockSizes;
        }

        // specifying index type is allowed only for primary, unique and index only
        // TokuDB is using Fractal Tree, Using Type is not useless
        // Ref: https://mariadb.com/kb/en/storage-engine-index-types/
        $type = $index->getType();
        if (
            $index->getChoice() !== 'SPATIAL'
            && $index->getChoice() !== 'FULLTEXT'
            && in_array($type, Index::getIndexTypes(), true)
            && ! $this->dbi->getTable($dbName, $tableName)->isEngine('TOKUDB')
        ) {
            $sqlQuery .= ' USING ' . $type;
        }

        $parser = $index->getParser();
        if ($index->getChoice() === 'FULLTEXT' && $parser !== '') {
            $sqlQuery .= ' WITH PARSER ' . $parser;
        }

        $comment = $index->getComment();
        if ($comment !== '') {
            $sqlQuery .= sprintf(
                ' COMMENT %s',
                $this->dbi->quoteString($comment),
            );
        }

        $sqlQuery .= ';';

        return $sqlQuery;
    }

    public function getSqlQueryForRename(string $oldIndexName, Index $index, string $db, string $table): string
    {
        if (! Compatibility::isCompatibleRenameIndex($this->dbi->getVersion())) {
            return $this->getSqlQueryForIndexCreateOrEdit($oldIndexName, $index, $db, $table);
        }

        if ($oldIndexName === 'PRIMARY') {
            if ($index->getName() === '') {
                $index->setName('PRIMARY');
            } elseif ($index->getName() !== 'PRIMARY') {
                $this->error = Message::error(
                    __('The name of the primary key must be "PRIMARY"!'),
                );
            }
        }

        if ($index->getName() === 'PRIMARY') {
            $this->error = Message::error(
                __('Can\'t rename index to PRIMARY!'),
            );
        }

        return QueryGenerator::getSqlQueryForIndexRename(
            $db,
            $table,
            $oldIndexName,
            $index->getName(),
        );
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
