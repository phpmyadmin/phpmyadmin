<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statement;

/** @psalm-immutable */
class StatementInfo
{
    /**
     * @param array<int, array<int, string|null>> $selectTables
     * @param array<int, string|null>             $selectExpression
     * @psalm-param list<array{string|null, string|null}> $selectTables
     * @psalm-param list<string|null> $selectExpression
     */
    private function __construct(
        public bool $distinct,
        public bool $dropDatabase,
        public bool $group,
        public bool $having,
        public bool $isAffected,
        public bool $isAnalyse,
        public bool $isCount,
        public bool $isDelete,
        public bool $isExplain,
        public bool $isExport,
        public bool $isFunction,
        public bool $isGroup,
        public bool $isInsert,
        public bool $isMaint,
        public bool $isProcedure,
        public bool $isReplace,
        public bool $isSelect,
        public bool $isShow,
        public bool $isSubquery,
        public bool $join,
        public bool $limit,
        public bool $offset,
        public bool $order,
        public string|false $queryType,
        public bool $reload,
        public bool $selectFrom,
        public bool $union,
        public Parser|null $parser,
        public Statement|null $statement,
        public array $selectTables,
        public array $selectExpression,
    ) {
    }

    /**
     * @param array<string, array<int, array<int, string|null>|string|null>|bool|string|Parser|Statement> $info
     * @psalm-param array{
     *   distinct: bool,
     *   drop_database: bool,
     *   group: bool,
     *   having: bool,
     *   is_affected: bool,
     *   is_analyse: bool,
     *   is_count: bool,
     *   is_delete: bool,
     *   is_explain: bool,
     *   is_export: bool,
     *   is_func: bool,
     *   is_group: bool,
     *   is_insert: bool,
     *   is_maint: bool,
     *   is_procedure: bool,
     *   is_replace: bool,
     *   is_select: bool,
     *   is_show: bool,
     *   is_subquery: bool,
     *   join: bool,
     *   limit: bool,
     *   offset: bool,
     *   order: bool,
     *   querytype: (
     *     'ALTER'|'ANALYZE'|'CALL'|'CHECK'|'CHECKSUM'|'CREATE'|'DELETE'|'DROP'|
     *     'EXPLAIN'|'INSERT'|'LOAD'|'OPTIMIZE'|'REPAIR'|'REPLACE'|'SELECT'|'SET'|'SHOW'|'UPDATE'|false
     *   ),
     *   reload: bool,
     *   select_from: bool,
     *   union: bool,
     *   parser?: Parser,
     *   statement?: Statement,
     *   select_tables?: list<array{string|null, string|null}>,
     *   select_expr?: list<string|null>
     * } $info
     */
    public static function fromArray(array $info): self
    {
        return new self(
            $info['distinct'],
            $info['drop_database'],
            $info['group'],
            $info['having'],
            $info['is_affected'],
            $info['is_analyse'],
            $info['is_count'],
            $info['is_delete'],
            $info['is_explain'],
            $info['is_export'],
            $info['is_func'],
            $info['is_group'],
            $info['is_insert'],
            $info['is_maint'],
            $info['is_procedure'],
            $info['is_replace'],
            $info['is_select'],
            $info['is_show'],
            $info['is_subquery'],
            $info['join'],
            $info['limit'],
            $info['offset'],
            $info['order'],
            $info['querytype'],
            $info['reload'],
            $info['select_from'],
            $info['union'],
            $info['parser'] ?? null,
            $info['statement'] ?? null,
            $info['select_tables'] ?? [],
            $info['select_expr'] ?? [],
        );
    }
}
