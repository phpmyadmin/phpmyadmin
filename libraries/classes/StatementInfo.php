<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use PhpMyAdmin\SqlParser\Parser;
use PhpMyAdmin\SqlParser\Statement;

/**
 * @psalm-immutable
 */
class StatementInfo
{
    /** @var bool */
    public $distinct;
    /** @var bool */
    public $dropDatabase;
    /** @var bool */
    public $group;
    /** @var bool */
    public $having;
    /** @var bool */
    public $isAffected;
    /** @var bool */
    public $isAnalyse;
    /** @var bool */
    public $isCount;
    /** @var bool */
    public $isDelete;
    /** @var bool */
    public $isExplain;
    /** @var bool */
    public $isExport;
    /** @var bool */
    public $isFunction;
    /** @var bool */
    public $isGroup;
    /** @var bool */
    public $isInsert;
    /** @var bool */
    public $isMaint;
    /** @var bool */
    public $isProcedure;
    /** @var bool */
    public $isReplace;
    /** @var bool */
    public $isSelect;
    /** @var bool */
    public $isShow;
    /** @var bool */
    public $isSubquery;
    /** @var bool */
    public $join;
    /** @var bool */
    public $limit;
    /** @var bool */
    public $offset;
    /** @var bool */
    public $order;
    /** @var string|false */
    public $queryType;
    /** @var bool */
    public $reload;
    /** @var bool */
    public $selectFrom;
    /** @var bool */
    public $union;
    /** @var Parser|null */
    public $parser;
    /** @var Statement|null */
    public $statement;
    /**
     * @var array<int, array<int, string|null>>
     * @psalm-var list<array{string|null, string|null}>
     */
    public $selectTables;
    /**
     * @var array<int, string|null>
     * @psalm-var list<string|null>
     */
    public $selectExpression;

    /**
     * @param string|false                        $queryType
     * @param array<int, array<int, string|null>> $selectTables
     * @param array<int, string|null>             $selectExpression
     * @psalm-param list<array{string|null, string|null}> $selectTables
     * @psalm-param list<string|null> $selectExpression
     */
    private function __construct(
        bool $distinct,
        bool $dropDatabase,
        bool $group,
        bool $having,
        bool $isAffected,
        bool $isAnalyse,
        bool $isCount,
        bool $isDelete,
        bool $isExplain,
        bool $isExport,
        bool $isFunction,
        bool $isGroup,
        bool $isInsert,
        bool $isMaint,
        bool $isProcedure,
        bool $isReplace,
        bool $isSelect,
        bool $isShow,
        bool $isSubquery,
        bool $join,
        bool $limit,
        bool $offset,
        bool $order,
        $queryType,
        bool $reload,
        bool $selectFrom,
        bool $union,
        ?SqlParser\Parser $parser,
        ?SqlParser\Statement $statement,
        array $selectTables,
        array $selectExpression
    ) {
        $this->distinct = $distinct;
        $this->dropDatabase = $dropDatabase;
        $this->group = $group;
        $this->having = $having;
        $this->isAffected = $isAffected;
        $this->isAnalyse = $isAnalyse;
        $this->isCount = $isCount;
        $this->isDelete = $isDelete;
        $this->isExplain = $isExplain;
        $this->isExport = $isExport;
        $this->isFunction = $isFunction;
        $this->isGroup = $isGroup;
        $this->isInsert = $isInsert;
        $this->isMaint = $isMaint;
        $this->isProcedure = $isProcedure;
        $this->isReplace = $isReplace;
        $this->isSelect = $isSelect;
        $this->isShow = $isShow;
        $this->isSubquery = $isSubquery;
        $this->join = $join;
        $this->limit = $limit;
        $this->offset = $offset;
        $this->order = $order;
        $this->queryType = $queryType;
        $this->reload = $reload;
        $this->selectFrom = $selectFrom;
        $this->union = $union;
        $this->parser = $parser;
        $this->statement = $statement;
        $this->selectTables = $selectTables;
        $this->selectExpression = $selectExpression;
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
            $info['select_expr'] ?? []
        );
    }
}
