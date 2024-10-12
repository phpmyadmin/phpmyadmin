<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\BrowseForeigners;
use PhpMyAdmin\Template;

/**
 * @covers \PhpMyAdmin\BrowseForeigners
 */
class BrowseForeignersTest extends AbstractTestCase
{
    /** @var BrowseForeigners */
    private $browseForeigners;

    /**
     * Setup for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setTheme();
        $GLOBALS['cfg']['LimitChars'] = 50;
        $GLOBALS['cfg']['MaxRows'] = 25;
        $GLOBALS['cfg']['RepeatCells'] = 100;
        $GLOBALS['cfg']['ShowAll'] = false;
        $this->browseForeigners = new BrowseForeigners(new Template());
    }

    /**
     * Test for BrowseForeigners::getForeignLimit
     */
    public function testGetForeignLimit(): void
    {
        self::assertNull($this->browseForeigners->getForeignLimit('Show all'));

        self::assertSame('LIMIT 0, 25 ', $this->browseForeigners->getForeignLimit(null));

        $_POST['pos'] = 10;

        self::assertSame('LIMIT 10, 25 ', $this->browseForeigners->getForeignLimit(null));

        $GLOBALS['cfg']['MaxRows'] = 50;
        $browseForeigners = new BrowseForeigners(new Template());

        self::assertSame('LIMIT 10, 50 ', $browseForeigners->getForeignLimit(null));

        self::assertSame('LIMIT 10, 50 ', $browseForeigners->getForeignLimit('xyz'));
    }

    /**
     * Test for BrowseForeigners::getHtmlForGotoPage
     */
    public function testGetHtmlForGotoPage(): void
    {
        self::assertSame('', $this->callFunction(
            $this->browseForeigners,
            BrowseForeigners::class,
            'getHtmlForGotoPage',
            [null]
        ));

        $_POST['pos'] = 15;
        $foreignData = [];
        $foreignData['disp_row'] = [];
        $foreignData['the_total'] = 5;

        self::assertSame('', $this->callFunction(
            $this->browseForeigners,
            BrowseForeigners::class,
            'getHtmlForGotoPage',
            [$foreignData]
        ));

        $foreignData['the_total'] = 30;
        $result = $this->callFunction(
            $this->browseForeigners,
            BrowseForeigners::class,
            'getHtmlForGotoPage',
            [$foreignData]
        );

        self::assertStringStartsWith('Page number:', $result);

        self::assertStringEndsWith('</select>', $result);

        self::assertStringContainsString('<select class="pageselector ajax" name="pos"', $result);

        self::assertStringContainsString('<option selected="selected" style="font-weight: bold" value="0">', $result);

        self::assertStringContainsString('<option  value="25"', $result);
    }

    /**
     * Test for BrowseForeigners::getDescriptionAndTitle
     */
    public function testGetDescriptionAndTitle(): void
    {
        $desc = 'foobar<baz';

        self::assertSame([
            'foobar<baz',
            '',
        ], $this->callFunction(
            $this->browseForeigners,
            BrowseForeigners::class,
            'getDescriptionAndTitle',
            [$desc]
        ));

        $GLOBALS['cfg']['LimitChars'] = 5;
        $browseForeigners = new BrowseForeigners(new Template());

        self::assertSame([
            'fooba...',
            'foobar<baz',
        ], $this->callFunction(
            $browseForeigners,
            BrowseForeigners::class,
            'getDescriptionAndTitle',
            [$desc]
        ));
    }

    /**
     * Test for BrowseForeigners::getHtmlForRelationalFieldSelection
     */
    public function testGetHtmlForRelationalFieldSelection(): void
    {
        $db = '';
        $table = '';
        $field = 'foo';
        $foreignData = [];
        $foreignData['disp_row'] = '';
        $fieldkey = 'bar';
        $current_value = '';
        $_POST['rownumber'] = 1;
        $_POST['foreign_filter'] = '5';
        $result = $this->browseForeigners->getHtmlForRelationalFieldSelection(
            $db,
            $table,
            $field,
            $foreignData,
            $fieldkey,
            $current_value
        );

        self::assertStringContainsString('<form class="ajax" '
        . 'id="browse_foreign_form" name="browse_foreign_from" '
        . 'action="index.php?route=/browse-foreigners', $result);
        self::assertStringContainsString('" method="post">', $result);

        self::assertStringContainsString('<fieldset class="row g-3 align-items-center mb-3">', $result);

        self::assertStringContainsString('<input type="hidden" name="field" value="foo">', $result);

        self::assertStringContainsString('<input type="hidden" name="fieldkey" value="bar">', $result);

        self::assertStringContainsString('<input type="hidden" name="rownumber" value="1">', $result);

        self::assertStringContainsString('<div class="col-auto">', $result);
        self::assertStringContainsString('<label class="form-label" for="input_foreign_filter">', $result);
        self::assertStringContainsString('<input class="form-control" type="text" name="foreign_filter" '
        . 'id="input_foreign_filter" value="5" data-old="5">', $result);

        self::assertStringContainsString(
            '<input class="btn btn-primary" type="submit" name="submit_foreign_filter" value="Go">',
            $result
        );

        self::assertStringContainsString(
            '<table class="table table-striped table-hover" id="browse_foreign_table">',
            $result
        );

        $foreignData['disp_row'] = [];
        $foreignData['the_total'] = 5;
        $result = $this->browseForeigners->getHtmlForRelationalFieldSelection(
            $db,
            $table,
            $field,
            $foreignData,
            $fieldkey,
            $current_value
        );

        self::assertStringContainsString(
            '<table class="table table-striped table-hover" id="browse_foreign_table">',
            $result
        );

        self::assertStringContainsString('<th>', $result);
    }
}
