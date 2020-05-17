<?php

declare(strict_types=1);

namespace PhpMyAdmin\Query;

use PhpMyAdmin\Util;

/**
 * Represents a select query
 */
final class SelectQuery extends Query
{
    use Constraints, Orderable;

    /**
     * @var string[]
     */
    private $expressions = [];

    /**
     * @var string[]
     */
    private $rawExpressions = [];

    /**
     * Make a new SELECT query
     * @param string[] $expressions The columns to select
     */
    public function __construct(array $expressions = ['*'])
    {
        $this->expressions = $expressions;
    }

    public function count(string $column = '*', string $asColumnName = ''): self
    {
        if ($column !== '*') {
            $column = Util::backquote($column);
        }
        if ($asColumnName !== '') {
            $this->rawExpressions[] = 'COUNT(' . $column . ') AS ' . Util::backquote($asColumnName);
            return $this;
        }
        $this->rawExpressions[] = 'COUNT(' . $column . ')';
        return $this;
    }

    public function selectedExpressions(): string
    {
        // Init using raw values
        $expressionParts = $this->rawExpressions;
        foreach ($this->expressions as $expression) {
            $expressionParts[] = Util::backquote($expression);
        }
        return implode(',', $expressionParts);
    }

    public function toSql(): string
    {
        $query = 'SELECT ' . $this->selectedExpressions() .
        ' FROM ' . $this->getFromExpression();
        if ($this->hasConstraintsExpressions()) {
            $query .= ' WHERE ' . $this->buildConstraints();
        }
        if ($this->hasOrderExpressions()) {
            $query .= ' ORDER BY ' . $this->buildOrder();
        }
        return $query;
    }
}
