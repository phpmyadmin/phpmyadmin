<?php

declare(strict_types=1);

namespace PhpMyAdmin;

use function __;
use function array_pop;
use function count;
use function htmlspecialchars;
use function strlen;

/**
 * Index manipulation class
 */
class Index
{
    public const PRIMARY = 1;
    public const UNIQUE = 2;
    public const INDEX = 4;
    public const SPATIAL = 8;
    public const FULLTEXT = 16;

    /**
     * Class-wide storage container for indexes (caching, singleton)
     *
     * @var array<string, array<string, array<string, Index>>>
     */
    private static array $registry = [];

    /** @var string The name of the schema */
    private string $schema = '';

    /** @var string The name of the table */
    private string $table = '';

    /** @var string The name of the index */
    private string $name = '';

    /**
     * Columns in index
     *
     * @var array<string, IndexColumn>
     */
    private array $columns = [];

    /**
     * The index method used (BTREE, HASH, RTREE).
     */
    private string $type = '';

    /**
     * The index choice (PRIMARY, UNIQUE, INDEX, SPATIAL, FULLTEXT)
     */
    private string $choice = '';

    /**
     * Various remarks.
     */
    private string $remarks = '';

    /**
     * Any comment provided for the index with a COMMENT attribute when the
     * index was created.
     */
    private string $comment = '';

    /** @var bool false if the index cannot contain duplicates, true if it can. */
    private bool $nonUnique = false;

    /**
     * Indicates how the key is packed. NULL if it is not.
     */
    private string|null $packed = null;

    /**
     * Block size for the index
     */
    private int $keyBlockSize = 0;

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
     * Creates (if not already created) and returns the corresponding Index object
     *
     * @return Index corresponding Index object
     */
    public static function singleton(
        DatabaseInterface $dbi,
        string $schema,
        string $table,
        string $indexName = '',
    ): Index {
        self::loadIndexes($dbi, $table, $schema);
        if (isset(self::$registry[$schema][$table][$indexName])) {
            return self::$registry[$schema][$table][$indexName];
        }

        $index = new Index();
        if ($indexName !== '') {
            $index->setName($indexName);
            self::$registry[$schema][$table][$index->getName()] = $index;
        }

        return $index;
    }

    /**
     * returns an array with all indexes from the given table
     *
     * @return Index[]
     */
    public static function getFromTable(DatabaseInterface $dbi, string $table, string $schema): array
    {
        self::loadIndexes($dbi, $table, $schema);

        if (isset(self::$registry[$schema][$table])) {
            return self::$registry[$schema][$table];
        }

        return [];
    }

    /**
     * Returns an array with all indexes from the given table of the requested types
     *
     * @param string $table   table
     * @param string $schema  schema
     * @param int    $choices choices
     *
     * @return Index[] array of indexes
     */
    public static function getFromTableByChoice(string $table, string $schema, int $choices = 31): array
    {
        $indexes = [];
        foreach (self::getFromTable($GLOBALS['dbi'], $table, $schema) as $index) {
            if (($choices & self::PRIMARY) && $index->getChoice() === 'PRIMARY') {
                $indexes[] = $index;
            }

            if (($choices & self::UNIQUE) && $index->getChoice() === 'UNIQUE') {
                $indexes[] = $index;
            }

            if (($choices & self::INDEX) && $index->getChoice() === 'INDEX') {
                $indexes[] = $index;
            }

            if (($choices & self::SPATIAL) && $index->getChoice() === 'SPATIAL') {
                $indexes[] = $index;
            }

            if ((! ($choices & self::FULLTEXT)) || $index->getChoice() !== 'FULLTEXT') {
                continue;
            }

            $indexes[] = $index;
        }

        return $indexes;
    }

    public static function getPrimary(DatabaseInterface $dbi, string $table, string $schema): Index|null
    {
        self::loadIndexes($dbi, $table, $schema);

        return self::$registry[$schema][$table]['PRIMARY'] ?? null;
    }

    /**
     * Load index data for table
     */
    private static function loadIndexes(DatabaseInterface $dbi, string $table, string $schema): void
    {
        if (isset(self::$registry[$schema][$table])) {
            return;
        }

        $rawIndexes = $dbi->getTableIndexes($schema, $table);
        foreach ($rawIndexes as $eachIndex) {
            $eachIndex['Schema'] = $schema;
            $keyName = $eachIndex['Key_name'];
            if (! isset(self::$registry[$schema][$table][$keyName])) {
                $key = new Index($eachIndex);
                self::$registry[$schema][$table][$keyName] = $key;
            } else {
                $key = self::$registry[$schema][$table][$keyName];
            }

            $key->addColumn($eachIndex);
        }
    }

    /**
     * Add column to index
     *
     * @param array<string, string|null> $params column params
     */
    public function addColumn(array $params): void
    {
        $key = $params['Column_name'] ?? $params['Expression'] ?? '';
        if (isset($params['Expression'])) {
            // The Expression only does not make the key unique, add a sequence number
            $key .= $params['Seq_in_index'];
        }

        if (strlen($key) <= 0) {
            return;
        }

        $this->columns[$key] = new IndexColumn($params);
    }

    /**
     * Adds a list of columns to the index
     *
     * @param mixed[] $columns array containing details about the columns
     */
    public function addColumns(array $columns): void
    {
        $addedColumns = [];

        if (isset($columns['names'])) {
            // coming from form
            // $columns[names][]
            // $columns[sub_parts][]
            foreach ($columns['names'] as $key => $name) {
                $subPart = $columns['sub_parts'][$key] ?? '';
                $addedColumns[] = ['Column_name' => $name, 'Sub_part' => $subPart];
            }
        } else {
            // coming from SHOW INDEXES
            // $columns[][name]
            // $columns[][sub_part]
            // ...
            $addedColumns = $columns;
        }

        foreach ($addedColumns as $column) {
            $this->addColumn($column);
        }
    }

    /**
     * Returns true if $column indexed in this index
     *
     * @param string $column the column
     */
    public function hasColumn(string $column): bool
    {
        return isset($this->columns[$column]);
    }

    /**
     * Sets index details
     *
     * @param mixed[] $params index details
     */
    public function set(array $params): void
    {
        if (isset($params['columns'])) {
            $this->addColumns($params['columns']);
        }

        if (isset($params['Schema'])) {
            $this->schema = $params['Schema'];
        }

        if (isset($params['Table'])) {
            $this->table = $params['Table'];
        }

        if (isset($params['Key_name'])) {
            $this->name = $params['Key_name'];
        }

        if (isset($params['Index_type'])) {
            $this->type = $params['Index_type'];
        }

        if (isset($params['Comment'])) {
            $this->remarks = $params['Comment'];
        }

        if (isset($params['Index_comment'])) {
            $this->comment = $params['Index_comment'];
        }

        if (isset($params['Non_unique'])) {
            $this->nonUnique = (bool) $params['Non_unique'];
        }

        if (isset($params['Packed'])) {
            $this->packed = $params['Packed'];
        }

        if (isset($params['Index_choice'])) {
            $this->choice = $params['Index_choice'];
        } elseif ($this->name === 'PRIMARY') {
            $this->choice = 'PRIMARY';
        } elseif ($this->type === 'FULLTEXT') {
            $this->choice = 'FULLTEXT';
            $this->type = '';
        } elseif ($this->type === 'SPATIAL') {
            $this->choice = 'SPATIAL';
            $this->type = '';
        } elseif (! $this->nonUnique) {
            $this->choice = 'UNIQUE';
        } else {
            $this->choice = 'INDEX';
        }

        if (isset($params['Key_block_size'])) {
            $this->keyBlockSize = (int) $params['Key_block_size'];
        }

        if (! isset($params['Parser'])) {
            return;
        }

        $this->parser = $params['Parser'];
    }

    /**
     * Returns the number of columns of the index
     *
     * @return int the number of the columns
     */
    public function getColumnCount(): int
    {
        return count($this->columns);
    }

    /**
     * Returns the index comment
     *
     * @return string index comment
     */
    public function getComment(): string
    {
        return $this->comment;
    }

    /**
     * Returns index remarks
     *
     * @return string index remarks
     */
    public function getRemarks(): string
    {
        return $this->remarks;
    }

    /**
     * Return the key block size
     */
    public function getKeyBlockSize(): int
    {
        return $this->keyBlockSize;
    }

    /**
     * Return the parser
     */
    public function getParser(): string
    {
        return $this->parser;
    }

    /**
     * Returns concatenated remarks and comment
     *
     * @return string concatenated remarks and comment
     */
    public function getComments(): string
    {
        $comments = $this->getRemarks();
        if (strlen($comments) > 0) {
            $comments .= "\n";
        }

        $comments .= $this->getComment();

        return $comments;
    }

    /**
     * Returns index type (BTREE, HASH, RTREE)
     *
     * @return string index type
     */
    public function getType(): string
    {
        return $this->type;
    }

    /**
     * Returns index choice (PRIMARY, UNIQUE, INDEX, SPATIAL, FULLTEXT)
     *
     * @return string index choice
     */
    public function getChoice(): string
    {
        return $this->choice;
    }

    /**
     * Returns a lit of all index types
     *
     * @return string[] index types
     */
    public static function getIndexTypes(): array
    {
        return ['BTREE', 'HASH'];
    }

    public function hasPrimary(): bool
    {
        return self::getPrimary($GLOBALS['dbi'], $this->table, $this->schema) !== null;
    }

    /**
     * Returns how the index is packed
     *
     * @return string|null how the index is packed
     */
    public function getPacked(): string|null
    {
        return $this->packed;
    }

    /**
     * Returns 'No' if the index is not packed,
     * how the index is packed if packed
     */
    public function isPacked(): string
    {
        if ($this->packed === null) {
            return __('No');
        }

        return htmlspecialchars($this->packed);
    }

    /**
     * Returns bool false if the index cannot contain duplicates, true if it can
     *
     * @return bool false if the index cannot contain duplicates, true if it can
     */
    public function getNonUnique(): bool
    {
        return $this->nonUnique;
    }

    /**
     * Returns whether the index is a 'Unique' index
     *
     * @param bool $asText whether to output should be in text
     *
     * @return string|bool whether the index is a 'Unique' index
     */
    public function isUnique(bool $asText = false): string|bool
    {
        if ($asText) {
            return $this->nonUnique ? __('No') : __('Yes');
        }

        return ! $this->nonUnique;
    }

    /**
     * Returns the name of the index
     *
     * @return string the name of the index
     */
    public function getName(): string
    {
        return $this->name;
    }

    /**
     * Sets the name of the index
     */
    public function setName(string $name): void
    {
        $this->name = $name;
    }

    /**
     * Returns the columns of the index
     *
     * @return IndexColumn[]
     */
    public function getColumns(): array
    {
        return $this->columns;
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
    public function getCompareData(): array
    {
        $data = ['Packed' => $this->packed, 'Index_choice' => $this->choice];

        foreach ($this->columns as $column) {
            $data['columns'][] = $column->getCompareData();
        }

        return $data;
    }

    /**
     * Function to check over array of indexes and look for common problems
     *
     * @param string $table  table name
     * @param string $schema schema name
     *
     * @return string  Output HTML
     */
    public static function findDuplicates(string $table, string $schema): string
    {
        $indexes = self::getFromTable($GLOBALS['dbi'], $table, $schema);

        $output = '';

        // count($indexes) < 2:
        //   there is no need to check if there less than two indexes
        if (count($indexes) < 2) {
            return $output;
        }

        // remove last index from stack and ...
        while ($whileIndex = array_pop($indexes)) {
            // ... compare with every remaining index in stack
            foreach ($indexes as $eachIndex) {
                if ($eachIndex->getCompareData() !== $whileIndex->getCompareData()) {
                    continue;
                }

                // did not find any difference
                // so it makes no sense to have this two equal indexes

                $message = Message::notice(
                    __(
                        'The indexes %1$s and %2$s seem to be equal and one of them could possibly be removed.',
                    ),
                );
                $message->addParam($eachIndex->getName());
                $message->addParam($whileIndex->getName());
                $output .= $message->getDisplay();

                // there is no need to check any further indexes if we have already
                // found that this one has a duplicate
                continue 2;
            }
        }

        return $output;
    }
}
