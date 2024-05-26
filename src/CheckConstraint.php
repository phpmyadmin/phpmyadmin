<?php

declare(strict_types=1);

namespace PhpMyAdmin;

/**
 * Check constraint manipulation class
 */
class CheckConstraint
{
    public const COLUMN = "Column";
    public const TABLE = "Table";

    /**
     * Class-wide storage container for check constraints (caching, singleton)
     *
     * @var array<string, array<string, array<string, CheckConstraint>>>
     */
    private static array $registry = [];

    /** @var string The name of the schema */
    private string $schema = '';

    /** @var string The name of the table */
    private string $table = '';

    /** @var string The name of the check constraint */
    private string $name = '';

    /**
     * The check constraint level (Column, Table).
     * This isn't supported by MySQL and MariaDB <= 10.5.10
     */
    private string $level = '';

    /**
     * The check constraint clause
     */
    private string $clause = '';


    /** @param string[] $params parameters */
    public function __construct(array $params = [])
    {
        $this->set($params);
    }

    /**
     * Creates (if not already created) and returns the corresponding CheckConstraint object
     *
     * @return CheckConstraint corresponding CheckConstraint object
     */
    public static function singleton(
        DatabaseInterface $dbi,
        string $schema,
        string $table,
        string $constraintName = '',
    ): CheckConstraint {
        self::loadCheckConstraints($dbi, $table, $schema);
        if (isset(self::$registry[$schema][$table][$constraintName])) {
            return self::$registry[$schema][$table][$constraintName];
        }

        $constraint = new CheckConstraint();
        if ($constraintName !== '') {
            $constraint->setName($constraintName);
            self::$registry[$schema][$table][$constraint->getName()] = $constraint;
        }

        return $constraint;
    }

    /**
     * returns an array with all check constraints from the given table
     *
     * @return CheckConstraint[]
     */
    public static function getFromTable(DatabaseInterface $dbi, string $table, string $schema): array
    {
        self::loadCheckConstraints($dbi, $table, $schema);

        return self::$registry[$schema][$table] ?? [];
    }

    /**
     * Load constraint data for table
     */
    private static function loadCheckConstraints(DatabaseInterface $dbi, string $table, string $schema): void
    {
        if (isset(self::$registry[$schema][$table])) {
            return;
        }

        $rawCheckConstraints = $dbi->getTableCheckConstraints($schema, $table);
        foreach ($rawCheckConstraints as $eachConstraint) {
            $eachConstraint['Schema'] = $schema;
            $eachConstraint['Table'] = $table;
            $name = $eachConstraint['CONSTRAINT_NAME'];
            if (! isset(self::$registry[$schema][$table][$name])) {
                self::$registry[$schema][$table][$name] = new CheckConstraint($eachConstraint);
            }
        }
    }


    /**
     * Sets check constraint details
     *
     * @param string[] $params check constraint details
     */
    public function set(array $params): void
    {
        if (isset($params['Schema'])) {
            $this->schema = $params['Schema'];
        }

        if (isset($params['Table'])) {
            $this->table = $params['Table'];
        }

        if (isset($params['CONSTRAINT_NAME'])) {
            $this->name = $params['CONSTRAINT_NAME'];
        }

        if (isset($params['LEVEL'])) {
            $this->level = $params['LEVEL'];
        }

        if (isset($params['CHECK_CLAUSE'])) {
            $this->clause = $params['CHECK_CLAUSE'];
        }
    }


    public function getClause(): string
    {
        return $this->clause;
    }

    public function getLevel(): string
    {
        return $this->level;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }
}
