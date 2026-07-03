<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Bookmarks;

use PhpMyAdmin\Bookmarks\Bookmark;
use PhpMyAdmin\Bookmarks\BookmarkRepository;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\Features\BookmarkFeature;
use PhpMyAdmin\ConfigStorage\Relation;
use PhpMyAdmin\ConfigStorage\RelationParameters;
use PhpMyAdmin\Identifiers\DatabaseName;
use PhpMyAdmin\Identifiers\TableName;
use PhpMyAdmin\Tests\AbstractTestCase;
use PHPUnit\Framework\Attributes\CoversClass;
use ReflectionProperty;

#[CoversClass(Bookmark::class)]
class BookmarkTest extends AbstractTestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $relationParameters = RelationParameters::fromArray([
            RelationParameters::BOOKMARK_WORK => true,
            RelationParameters::DATABASE => 'phpmyadmin',
            RelationParameters::BOOKMARK => 'pma_bookmark',
        ]);
        (new ReflectionProperty(Relation::class, 'cache'))->setValue(null, $relationParameters);
    }

    /**
     * Tests for BookmarkRepository::getList()
     */
    public function testGetList(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            'SELECT * FROM `phpmyadmin`.`pma_bookmark` WHERE (`user` = \'root\' OR `user` = \'\')'
                . ' AND dbase = \'sakila\' ORDER BY label ASC',
            [['1', 'sakila', 'root', 'label', 'SELECT * FROM `actor` WHERE `actor_id` < 10;']],
            ['id', 'dbase', 'user', 'label', 'query'],
        );
        $config = new Config();
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);
        $bookmarkRepository = new BookmarkRepository($dbi, new Relation($dbi, $config), $config);
        $actual = $bookmarkRepository->getList($config->selectedServer['user'], 'sakila');
        self::assertContainsOnlyInstancesOf(Bookmark::class, $actual);
        $dbiDummy->assertAllSelectsConsumed();
    }

    /**
     * Tests for BookmarkRepository::get()
     */
    public function testGet(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            "SELECT * FROM `phpmyadmin`.`pma_bookmark` WHERE `id` = 1 AND (user = 'root' OR user = '') LIMIT 1",
            [],
            ['id', 'dbase', 'user', 'label', 'query'],
        );
        $config = new Config();
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);
        $bookmarkRepository = new BookmarkRepository($dbi, new Relation($dbi, $config), $config);
        self::assertNull($bookmarkRepository->get($config->selectedServer['user'], 1));
    }

    /**
     * Tests for Bookmark::save()
     */
    public function testSave(): void
    {
        $dbiDummy = $this->createDbiDummy();
        $dbiDummy->addResult(
            'INSERT INTO `phpmyadmin`.`pma_bookmark` (id, dbase, user, query, label)' .
            " VALUES (NULL, 'phpmyadmin', 'root', 'SELECT \\\"phpmyadmin\\\"', 'bookmark1')",
            true,
        );
        $config = new Config();
        $dbi = $this->createDatabaseInterface($dbiDummy, $config);
        $relation = new Relation($dbi, $config);
        $bookmarkRepository = new BookmarkRepository($dbi, $relation, $config);
        $bookmark = $bookmarkRepository->createBookmark('SELECT "phpmyadmin"', 'bookmark1', 'root', 'phpmyadmin');
        self::assertNotFalse($bookmark);
        self::assertTrue($bookmark->save());
    }

    public function testGetVariableCountWithSequentialVariables(): void
    {
        $bookmark = $this->createBookmarkWithQuery(
            'SELECT * FROM t WHERE a = /* [VARIABLE1] */ 1 AND b = /* [VARIABLE2] */ 2',
        );

        self::assertSame(2, $bookmark->getVariableCount());
    }

    /**
     * A query referencing [VARIABLE1] and [VARIABLE3] (skipping [VARIABLE2])
     * needs 3 substitution slots, not 2 — getVariableCount() must return the
     * highest number referenced, not the number of placeholder occurrences.
     */
    public function testGetVariableCountWithGapInNumbering(): void
    {
        $bookmark = $this->createBookmarkWithQuery(
            'SELECT * FROM t WHERE a = /* [VARIABLE1] */ 1 AND b = /* [VARIABLE3] */ 2',
        );

        self::assertSame(3, $bookmark->getVariableCount());
    }

    public function testGetVariableCountWithNoVariables(): void
    {
        $bookmark = $this->createBookmarkWithQuery('SELECT 1');

        self::assertSame(0, $bookmark->getVariableCount());
    }

    public function testGetVariableCountWithBareVariablePlaceholder(): void
    {
        $bookmark = $this->createBookmarkWithQuery('SELECT * FROM t WHERE a = /* [VARIABLE] */ 1');

        self::assertSame(1, $bookmark->getVariableCount());
    }

    /**
     * The same number referenced twice must not inflate the count — it is
     * still a single substitution slot, filled in both occurrences.
     */
    public function testGetVariableCountWithDuplicateVariable(): void
    {
        $bookmark = $this->createBookmarkWithQuery(
            'SELECT * FROM t WHERE a = /* [VARIABLE1] */ 1 OR a = /* [VARIABLE1] */ 2',
        );

        self::assertSame(1, $bookmark->getVariableCount());
    }

    /**
     * A query can reference a high variable number without using any of the
     * lower ones at all — getVariableCount() must still report that high
     * number, not be thrown off by the absence of [VARIABLE1]..[VARIABLE4].
     */
    public function testGetVariableCountWithOnlyAHighNumberReferenced(): void
    {
        $bookmark = $this->createBookmarkWithQuery('SELECT * FROM t WHERE a = /* [VARIABLE5] */ 1');

        self::assertSame(5, $bookmark->getVariableCount());
    }

    /**
     * End-to-end regression test for the gap-in-numbering bug: before the
     * fix, [VARIABLE3] was never substituted because the loop in
     * applyVariables() only ran up to getVariableCount() (2 occurrences),
     * leaving the literal placeholder text in the query that gets executed.
     */
    public function testApplyVariablesSubstitutesAllReferencedNumbersEvenWithGap(): void
    {
        $bookmark = $this->createBookmarkWithQuery(
            'SELECT * FROM t WHERE a = /* [VARIABLE1] */ 1 AND b = /* [VARIABLE3] */ 2',
        );

        $query = $bookmark->applyVariables([1 => 'x', 3 => 'z']);

        self::assertSame('SELECT * FROM t WHERE a =  x  1 AND b =  z  2', $query);
    }

    public function testApplyVariablesSupportsBareVariablePlaceholder(): void
    {
        $bookmark = $this->createBookmarkWithQuery('SELECT * FROM t WHERE a = /* [VARIABLE] */ 1');

        $query = $bookmark->applyVariables([1 => 'x']);

        self::assertSame('SELECT * FROM t WHERE a =  x  1', $query);
    }

    public function testApplyVariablesReplacesEveryOccurrenceOfADuplicateNumber(): void
    {
        $bookmark = $this->createBookmarkWithQuery(
            'SELECT * FROM t WHERE a = /* [VARIABLE1] */ 1 OR a = /* [VARIABLE1] */ 2',
        );

        $query = $bookmark->applyVariables([1 => 'x']);

        self::assertSame('SELECT * FROM t WHERE a =  x  1 OR a =  x  2', $query);
    }

    /**
     * With only [VARIABLE5] referenced (no lower numbers in the query), the
     * loop from 1 to 5 has to reach i = 5 without erroring on the absent
     * intermediate placeholders, and substitute using key 5 of $variables.
     */
    public function testApplyVariablesWithOnlyAHighNumberReferenced(): void
    {
        $bookmark = $this->createBookmarkWithQuery('SELECT * FROM t WHERE a = /* [VARIABLE5] */ 1');

        $query = $bookmark->applyVariables([5 => 'z']);

        self::assertSame('SELECT * FROM t WHERE a =  z  1', $query);
    }

    public function testApplyVariablesDefaultsMissingValueToEmptyString(): void
    {
        $bookmark = $this->createBookmarkWithQuery('SELECT * FROM t WHERE a = /* [VARIABLE5] */ 1');

        $query = $bookmark->applyVariables([]);

        self::assertSame('SELECT * FROM t WHERE a =    1', $query);
    }

    /**
     * The bare [VARIABLE] form and a numbered placeholder can coexist in the
     * same query — the "backward compatibility" branch in applyVariables()
     * must fill both independently.
     */
    public function testApplyVariablesWithBareAndNumberedPlaceholdersTogether(): void
    {
        $bookmark = $this->createBookmarkWithQuery('a /* [VARIABLE] */ b /* [VARIABLE2] */ c');

        $query = $bookmark->applyVariables([1 => 'x', 2 => 'y']);

        self::assertSame('a  x  b  y  c', $query);
    }

    private function createBookmarkWithQuery(string $query): Bookmark
    {
        return new Bookmark(
            $this->createDatabaseInterface(),
            new BookmarkFeature(DatabaseName::from('phpmyadmin'), TableName::from('pma_bookmark')),
            'db',
            'user',
            'label',
            $query,
        );
    }
}
