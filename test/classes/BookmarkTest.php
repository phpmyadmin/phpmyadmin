<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Bookmark;
use PhpMyAdmin\ConfigStorage\Features\BookmarkFeature;
use PhpMyAdmin\Dbal\DatabaseName;
use PhpMyAdmin\Dbal\TableName;

/**
 * @covers \PhpMyAdmin\Bookmark
 */
class BookmarkTest extends AbstractTestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        $GLOBALS['cfg']['Server']['user'] = 'root';
        $GLOBALS['cfg']['Server']['pmadb'] = 'phpmyadmin';
        $GLOBALS['cfg']['Server']['bookmarktable'] = 'pma_bookmark';
        $GLOBALS['cfg']['MaxCharactersInDisplayedSQL'] = 1000;
        $GLOBALS['cfg']['ServerDefault'] = 1;
        $GLOBALS['server'] = 1;
    }

    /**
     * Tests for Bookmark::getList()
     */
    public function testGetList(): void
    {
        $this->dummyDbi->addResult(
            'SELECT * FROM `phpmyadmin`.`pma_bookmark` WHERE ( `user` = \'\' OR `user` = \'root\' )'
                . ' AND dbase = \'sakila\' ORDER BY label ASC',
            [['1', 'sakila', 'root', 'label', 'SELECT * FROM `actor` WHERE `actor_id` < 10;']],
            ['id', 'dbase', 'user', 'label', 'query']
        );
        $actual = Bookmark::getList(
            new BookmarkFeature(DatabaseName::fromValue('phpmyadmin'), TableName::fromValue('pma_bookmark')),
            $GLOBALS['dbi'],
            $GLOBALS['cfg']['Server']['user'],
            'sakila'
        );
        self::assertContainsOnlyInstancesOf(Bookmark::class, $actual);
        $this->assertAllSelectsConsumed();
    }

    /**
     * Tests for Bookmark::get()
     */
    public function testGet(): void
    {
        $this->dummyDbi->addSelectDb('phpmyadmin');
        self::assertNull(Bookmark::get(
            $GLOBALS['dbi'],
            $GLOBALS['cfg']['Server']['user'],
            'phpmyadmin',
            '1'
        ));
        $this->assertAllSelectsConsumed();
    }

    /**
     * Tests for Bookmark::save()
     */
    public function testSave(): void
    {
        $bookmarkData = [
            'bkm_database' => 'phpmyadmin',
            'bkm_user' => 'root',
            'bkm_sql_query' => 'SELECT "phpmyadmin"',
            'bkm_label' => 'bookmark1',
        ];

        $bookmark = Bookmark::createBookmark($GLOBALS['dbi'], $bookmarkData);
        self::assertNotFalse($bookmark);
        $this->dummyDbi->addSelectDb('phpmyadmin');
        self::assertFalse($bookmark->save());
        $this->assertAllSelectsConsumed();
    }

    public function testGetVariableCountWithSequentialVariables(): void
    {
        $bookmark = $this->createBookmarkWithQuery(
            'SELECT * FROM t WHERE a = /* [VARIABLE1] */ 1 AND b = /* [VARIABLE2] */ 2'
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
            'SELECT * FROM t WHERE a = /* [VARIABLE1] */ 1 AND b = /* [VARIABLE3] */ 2'
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
            'SELECT * FROM t WHERE a = /* [VARIABLE1] */ 1 OR a = /* [VARIABLE1] */ 2'
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
            'SELECT * FROM t WHERE a = /* [VARIABLE1] */ 1 AND b = /* [VARIABLE3] */ 2'
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
            'SELECT * FROM t WHERE a = /* [VARIABLE1] */ 1 OR a = /* [VARIABLE1] */ 2'
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
        $bookmark = Bookmark::createBookmark($GLOBALS['dbi'], [
            'bkm_database' => 'phpmyadmin',
            'bkm_user' => 'root',
            'bkm_sql_query' => $query,
            'bkm_label' => 'label',
        ]);
        self::assertNotFalse($bookmark);

        return $bookmark;
    }
}
