<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\BrowseForeigners;
use PhpMyAdmin\Config;
use PhpMyAdmin\ConfigStorage\ForeignData;
use PhpMyAdmin\Template;
use PhpMyAdmin\Theme\ThemeManager;
use PHPUnit\Framework\Attributes\CoversClass;

#[CoversClass(BrowseForeigners::class)]
class BrowseForeignersTest extends AbstractTestCase
{
    private BrowseForeigners $browseForeigners;

    /**
     * Setup for test cases
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->browseForeigners = new BrowseForeigners(new Template(), new Config(), new ThemeManager());
    }

    /**
     * Test for BrowseForeigners::getForeignLimit
     */
    public function testGetForeignLimit(): void
    {
        self::assertSame(
            '',
            $this->browseForeigners->getForeignLimit('Show all', 0),
        );

        self::assertSame(
            'LIMIT 0, 25 ',
            $this->browseForeigners->getForeignLimit(null, 0),
        );

        self::assertSame(
            'LIMIT 10, 25 ',
            $this->browseForeigners->getForeignLimit(null, 10),
        );

        $config = new Config();
        $config->set('MaxRows', 50);
        $browseForeigners = new BrowseForeigners(new Template(), $config, new ThemeManager());

        self::assertSame(
            'LIMIT 10, 50 ',
            $browseForeigners->getForeignLimit(null, 10),
        );

        self::assertSame(
            'LIMIT 10, 50 ',
            $browseForeigners->getForeignLimit('xyz', 10),
        );
    }

    /**
     * Test for BrowseForeigners::getHtmlForGotoPage
     */
    public function testGetHtmlForGotoPage(): void
    {
        $foreignData = new ForeignData(false, 5, '', null, '');
        self::assertSame(
            '',
            $this->callFunction(
                $this->browseForeigners,
                BrowseForeigners::class,
                'getHtmlForGotoPage',
                [$foreignData, 0],
            ),
        );

        $foreignData = new ForeignData(false, 5, '', [], '');

        self::assertSame(
            '',
            $this->callFunction(
                $this->browseForeigners,
                BrowseForeigners::class,
                'getHtmlForGotoPage',
                [$foreignData, 15],
            ),
        );

        $foreignData = new ForeignData(false, 30, '', [], '');
        $result = $this->callFunction(
            $this->browseForeigners,
            BrowseForeigners::class,
            'getHtmlForGotoPage',
            [$foreignData, 15],
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

        self::assertSame(
            ['foobar<baz', ''],
            $this->callFunction(
                $this->browseForeigners,
                BrowseForeigners::class,
                'getDescriptionAndTitle',
                [$desc],
            ),
        );

        $config = new Config();
        $config->set('LimitChars', 5);
        $browseForeigners = new BrowseForeigners(new Template(), $config, new ThemeManager());

        self::assertSame(
            ['fooba...', 'foobar<baz'],
            $this->callFunction(
                $browseForeigners,
                BrowseForeigners::class,
                'getDescriptionAndTitle',
                [$desc],
            ),
        );
    }

    /**
     * Test for BrowseForeigners::getHtmlForRelationalFieldSelection
     */
    public function testGetHtmlForRelationalFieldSelection(): void
    {
        $db = '';
        $table = '';
        $field = 'foo';
        $foreignData = new ForeignData(false, 0, '', null, '');
        $fieldkey = 'bar';
        $currentValue = '';
        $rownumber = '1';
        $foreignFilter = '5';
        $result = $this->browseForeigners->getHtmlForRelationalFieldSelection(
            $db,
            $table,
            $field,
            $foreignData,
            $fieldkey,
            $currentValue,
            0,
            $foreignFilter,
            $rownumber,
        );

        self::assertStringContainsString(
            '<form class="ajax" '
            . 'id="browse_foreign_form" name="browse_foreign_from" '
            . 'action="index.php?route=/browse-foreigners',
            $result,
        );
        self::assertStringContainsString('" method="post">', $result);

        self::assertStringContainsString('<fieldset class="row g-3 align-items-center mb-3">', $result);

        self::assertStringContainsString('<input type="hidden" name="field" value="foo">', $result);

        self::assertStringContainsString('<input type="hidden" name="fieldkey" value="bar">', $result);

        self::assertStringContainsString('<input type="hidden" name="rownumber" value="1">', $result);

        self::assertStringContainsString('<div class="col-auto">', $result);
        self::assertStringContainsString('<label class="form-label" for="input_foreign_filter">', $result);
        self::assertStringContainsString(
            '<input class="form-control" type="text" name="foreign_filter" '
            . 'id="input_foreign_filter" value="5" data-old="5">',
            $result,
        );

        self::assertStringContainsString(
            '<input class="btn btn-primary" type="submit" name="submit_foreign_filter" value="Go">',
            $result,
        );

        self::assertStringContainsString(
            '<table class="table table-striped table-hover" id="browse_foreign_table">',
            $result,
        );

        $foreignData = new ForeignData(false, 5, '', [], '');
        $result = $this->browseForeigners->getHtmlForRelationalFieldSelection(
            $db,
            $table,
            $field,
            $foreignData,
            $fieldkey,
            $currentValue,
            0,
            $foreignFilter,
            $rownumber,
        );

        self::assertStringContainsString(
            '<table class="table table-striped table-hover" id="browse_foreign_table">',
            $result,
        );

        self::assertStringContainsString('<th>', $result);
    }
}
