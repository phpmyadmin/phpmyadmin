<?php

declare(strict_types=1);

namespace PhpMyAdmin;

/**
 * Check constraint manipulation class
 */
class CheckConstraint
{
    public const COLUMN = 1;
    public const TABLE = 2;

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

    /** @var string The name of the index */
    private string $name = '';

    /**
     * The check constraint level (Column, Table).
     */
    private string $level = '';

    /**
     * The check constraint clause
     */
    private string $clause = '';

    /**
     * Parser option for the index
     */
    private string $parser = '';

    /** @param mixed[] $params parameters */
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
     * returns an array with all indexes from the given table
     *
     * @return Index[]
     */
    public static function getFromTable(DatabaseInterface $dbi, string $table, string $schema): array
    {
        self::loadCheckConstraints($dbi, $table, $schema);

        return self::$registry[$schema][$table] ?? [];
    }

    /**
     * Returns an array with all indexes from the given table of the requested types
     *
     * @param string $table   table
     * @param string $schema  schema
     * @param int    $choices choices
     *
     * @return CheckConstraint[] array of indexes
     */
    public static function getFromTableByChoice(string $table, string $schema, int $choices = 31): array
    {
        $indexes = [];
        // foreach (self::getFromTable(DatabaseInterface::getInstance(), $table, $schema) as $index) {
        //     if (($choices & self::PRIMARY) && $index->getChoice() === 'PRIMARY') {
        //         $indexes[] = $index;
        //     }

        //     if (($choices & self::UNIQUE) && $index->getChoice() === 'UNIQUE') {
        //         $indexes[] = $index;
        //     }

        //     if (($choices & self::INDEX) && $index->getChoice() === 'INDEX') {
        //         $indexes[] = $index;
        //     }

        //     if (($choices & self::SPATIAL) && $index->getChoice() === 'SPATIAL') {
        //         $indexes[] = $index;
        //     }

        //     if ((($choices & self::FULLTEXT) === 0) || $index->getChoice() !== 'FULLTEXT') {
        //         continue;
        //     }

        //     $indexes[] = $index;
        // }

        return $indexes;
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
     * @param mixed[] $params check constraint details
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

        // if (! isset($params['Parser'])) {
        //     return;
        // }

        // $this->parser = $params['Parser'];
    }


    /**
     * Returns check constraint clause
     *
     * @return string check constraint clause
     */
    public function getClause(): string
    {
        return $this->clause;
    }

    /**
     * Return the parser
     */
    public function getParser(): string
    {
        return $this->parser;
    }

    /**
     * Returns check constraint level (Column, Table)
     *
     * @return string check constraint level
     */
    public function getLevel(): string
    {
        return $this->level;
    }


    /**
     * Returns the name of the check constraint
     *
     * @return string the name of the check constraint
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the name of the check constraint
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Gets the properties in an array for comparison purposes
     *
     * @return array<string, array<int, array<string, int|string|null>>|string|null>
     * @psalm-return array{
     *   Packed: string|null,
     *   Index_choice: string,
     *   columns?: list<array{
     *     Column_name: string,
     *     Seq_in_index: int,
     *     Collation: string|null,
     *     Sub_part: int|null,
     *     Null: string
     *   }>
     * }
     */
    // public function getCompareData(): array
    // {
    //     $data = ['Packed' => $this->packed, 'Index_choice' => $this->choice];

    //     foreach ($this->columns as $column) {
    //         $data['columns'][] = $column->getCompareData();
    //     }

    //     return $data;
    // }
}
