<?php

declare(strict_types=1);

namespace PhpMyAdmin\Query;

/**
 * Represents some constraints
 */
trait Constraints
{
    /**
     * @var array<int,array<string|int|float,string|int|float,true>>
     */
    private $constraintsExpressions = [];

    /**
     * Add a normal safe where
     * @param string           $key      The key name
     * @param string           $operator The operator (<,>,=)
     * @param string|int|float $value    The value to bind
     */
    public function where(string $key, string $operator, $value): self
    {
        $this->constraintsExpressions[] = [$key, $operator, $value];
        return $this;
    }

    /**
     * Add a simple safe where
     * @param string|int|float $value The value to bind
     */
    public function whereSimple($value): self
    {
        $this->constraintsExpressions[] = [$value];
        return $this;
    }

    /**
     * Add a raw where
     * @param string           $key      The key name
     * @param string           $operator The operator (<,>,=)
     * @param string|int|float $value    The value to bind
     */
    public function whereRaw(string $key, string $operator, $value): self
    {
        $this->constraintsExpressions[] = [$key, $operator, $value, true];
        return $this;
    }

    /**
     * @return array<array<int,string|int|float,true>>
     */
    public function getConstraintsExpressions(): array
    {
        return $this->constraintsExpressions;
    }

    public function hasConstraintsExpressions(): bool
    {
        return count($this->constraintsExpressions) > 0;
    }

    public function buildPlaceHolders(): string
    {
        $query = '';
        foreach ($this->constraintsExpressions as $data) {
            $nbr = count($data);
            $isRaw = $nbr > 3;
            if ($nbr === 3) {
                $query .= $data[0] . ' ' . $data[1] . ' ' . ($isRaw ? $data[2] : '?');
            }
            if ($nbr === 1) {
                $query .= '?';
            }
        }
        return $query;
    }

    public function getPlaceHolderValues(): array
    {
        $placeHolders = [];
        foreach ($this->constraintsExpressions as $data) {
            $nbr = count($data);
            $isRaw = $nbr > 3;
            if ($isRaw) {
                continue;
            }
            if ($nbr === 3) {
                $placeHolders[] = $data[2];
            } elseif ($nbr === 1) {
                $placeHolders[] = $data[0];
            }
        }
        return $placeHolders;
    }
}
