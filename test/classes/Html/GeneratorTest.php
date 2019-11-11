<?php
/**
 * Test for PhpMyAdmin\Util class
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests\Html;

use PhpMyAdmin\Html\Forms;
use PhpMyAdmin\Html\Generator;
use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\Util;
use Throwable;
use Twig_Error_Loader;
use Twig_Error_Runtime;
use Twig_Error_Syntax;

/**
 * Test for \PhpMyAdmin\Html\MySQLDocumentation class
 *
 * @package PhpMyAdmin-test
 */
class GeneratorTest extends PmaTestCase
{
    /**
     * Test for getDbLink
     *
     * @return void
     *
     * @group medium
     */
    public function testGetDbLinkEmpty(): void
    {
        $GLOBALS['db'] = null;
        $this->assertEmpty(Generator::getDbLink());
    }

    /**
     * Test for getDbLink
     *
     * @return void
     *
     * @group medium
     */
    public function testGetDbLinkNull(): void
    {
        global $cfg;
        $GLOBALS['db'] = 'test_db';
        $GLOBALS['server'] = 99;
        $database = $GLOBALS['db'];
        $this->assertEquals(
            '<a href="'
            . Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabDatabase'],
                'database'
            )
            . '&amp;db=' . $database
            . '&amp;server=99&amp;lang=en" '
            . 'title="Jump to database “'
            . htmlspecialchars($database) . '”.">'
            . htmlspecialchars($database) . '</a>',
            Generator::getDbLink()
        );
    }

    /**
     * Test for getDbLink
     *
     * @return void
     */
    public function testGetDbLink(): void
    {
        global $cfg;
        $GLOBALS['server'] = 99;
        $database = 'test_database';
        $this->assertEquals(
            '<a href="' . Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabDatabase'],
                'database'
            )
            . '&amp;db=' . $database
            . '&amp;server=99&amp;lang=en" title="Jump to database “'
            . htmlspecialchars($database) . '”.">'
            . htmlspecialchars($database) . '</a>',
            Generator::getDbLink($database)
        );
    }

    /**
     * Test for getDbLink
     *
     * @return void
     */
    public function testGetDbLinkWithSpecialChars(): void
    {
        global $cfg;
        $GLOBALS['server'] = 99;
        $database = 'test&data\'base';
        $this->assertEquals(
            '<a href="'
            . Util::getScriptNameForOption(
                $GLOBALS['cfg']['DefaultTabDatabase'],
                'database'
            )
            . '&amp;db='
            . htmlspecialchars(urlencode($database))
            . '&amp;server=99&amp;lang=en" title="Jump to database “'
            . htmlspecialchars($database) . '”.">'
            . htmlspecialchars($database) . '</a>',
            Generator::getDbLink($database)
        );
    }

    /**
     * Test for getDivForSliderEffect
     *
     * @return void
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public function testGetDivForSliderEffectTest(): void
    {
        global $cfg;
        $cfg['InitialSlidersState'] = 'undefined';

        $id = 'test_id';
        $message = 'test_message';

        $this->assertXmlStringEqualsXmlString(
            '<root>' . Generator::getDivForSliderEffect($id, $message) . '</div></root>',
            "<root><div id=\"$id\" class=\"pma_auto_slider\"\ntitle=\""
            . htmlspecialchars($message) . "\" >\n</div></root>"
        );
    }

    /**
     * Test for getDivForSliderEffect
     *
     * @return void
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public function testGetDivForSliderEffectTestClosed(): void
    {
        global $cfg;
        $cfg['InitialSlidersState'] = 'closed';

        $id = 'test_id';
        $message = 'test_message';

        $this->assertXmlStringEqualsXmlString(
            '<root>' . Generator::getDivForSliderEffect($id, $message) . '</div></root>',
            "<root><div id=\"$id\" style=\"display: none; overflow:auto;\" class=\"pma_auto_slider\"\ntitle=\""
            . htmlspecialchars($message) . "\" >\n</div></root>"
        );
    }

    /**
     * Test for getDivForSliderEffect
     *
     * @return void
     * @throws Throwable
     * @throws Twig_Error_Loader
     * @throws Twig_Error_Runtime
     * @throws Twig_Error_Syntax
     */
    public function testGetDivForSliderEffectTestDisabled(): void
    {
        global $cfg;
        $cfg['InitialSlidersState'] = 'disabled';

        $id = 'test_id';
        $message = 'test_message';

        $this->assertXmlStringEqualsXmlString(
            '<root>' . Generator::getDivForSliderEffect($id, $message) . '</div></root>',
            "<root><div id=\"$id\">\n</div></root>"
        );
    }

    /**
     * Test for Util::getIcon
     *
     * @return void
     */
    public function testGetIconWithoutActionLinksMode(): void
    {
        $GLOBALS['cfg']['ActionLinksMode'] = 'text';

        $this->assertEquals(
            '<span class="nowrap"></span>',
            Generator::getIcon('b_comment')
        );
    }

    /**
     * Test for Util::getIcon
     *
     * @return void
     */
    public function testGetIconWithActionLinksMode(): void
    {
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';

        $this->assertEquals(
            '<span class="nowrap"><img src="themes/dot.gif" title="" alt="" class="icon ic_b_comment"></span>',
            Generator::getIcon('b_comment')
        );
    }

    /**
     * Test for Util::getIcon
     *
     * @return void
     */
    public function testGetIconAlternate(): void
    {
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $alternate_text = 'alt_str';

        $this->assertEquals(
            '<span class="nowrap"><img src="themes/dot.gif" title="'
            . $alternate_text . '" alt="' . $alternate_text
            . '" class="icon ic_b_comment"></span>',
            Generator::getIcon('b_comment', $alternate_text)
        );
    }

    /**
     * Test for Util::getIcon
     *
     * @return void
     */
    public function testGetIconWithForceText(): void
    {
        $GLOBALS['cfg']['ActionLinksMode'] = 'icons';
        $alternate_text = 'alt_str';

        // Here we are checking for an icon embedded inside a span (i.e not a menu
        // bar icon
        $this->assertEquals(
            '<span class="nowrap"><img src="themes/dot.gif" title="'
            . $alternate_text . '" alt="' . $alternate_text
            . '" class="icon ic_b_comment">&nbsp;' . $alternate_text . '</span>',
            Generator::getIcon('b_comment', $alternate_text, true, false)
        );
    }

    /**
     * Test for showPHPDocumentation
     *
     * @return void
     */
    public function testShowPHPDocumentation(): void
    {
        $GLOBALS['server'] = 99;
        $GLOBALS['cfg']['ServerDefault'] = 0;

        $target = 'docu';
        $lang = _pgettext('PHP documentation language', 'en');
        $expected = '<a href="./url.php?url=https%3A%2F%2Fsecure.php.net%2Fmanual%2F' . $lang
            . '%2F' . $target . '" target="documentation">'
            . '<img src="themes/dot.gif" title="' . __('Documentation') . '" alt="'
            . __('Documentation') . '" class="icon ic_b_help"></a>';

        $this->assertEquals(
            $expected,
            Generator::showPHPDocumentation($target)
        );
    }

    /**
     * Test for PhpMyAdmin\Util::buildActionTitles
     *
     * @return void
     */
    public function testBuildActionTitles(): void
    {
        $GLOBALS['cfg'] = ['ActionLinksMode' => 'both'];

        $titles = [];
        $titles['Browse']     = Generator::getIcon('b_browse', __('Browse'));
        $titles['NoBrowse']   = Generator::getIcon('bd_browse', __('Browse'));
        $titles['Search']     = Generator::getIcon('b_select', __('Search'));
        $titles['NoSearch']   = Generator::getIcon('bd_select', __('Search'));
        $titles['Insert']     = Generator::getIcon('b_insrow', __('Insert'));
        $titles['NoInsert']   = Generator::getIcon('bd_insrow', __('Insert'));
        $titles['Structure']  = Generator::getIcon('b_props', __('Structure'));
        $titles['Drop']       = Generator::getIcon('b_drop', __('Drop'));
        $titles['NoDrop']     = Generator::getIcon('bd_drop', __('Drop'));
        $titles['Empty']      = Generator::getIcon('b_empty', __('Empty'));
        $titles['NoEmpty']    = Generator::getIcon('bd_empty', __('Empty'));
        $titles['Edit']       = Generator::getIcon('b_edit', __('Edit'));
        $titles['NoEdit']     = Generator::getIcon('bd_edit', __('Edit'));
        $titles['Export']     = Generator::getIcon('b_export', __('Export'));
        $titles['NoExport']   = Generator::getIcon('bd_export', __('Export'));
        $titles['Execute']    = Generator::getIcon('b_nextpage', __('Execute'));
        $titles['NoExecute']  = Generator::getIcon('bd_nextpage', __('Execute'));
        $titles['Favorite']   = Generator::getIcon('b_favorite', '');
        $titles['NoFavorite'] = Generator::getIcon('b_no_favorite', '');

        $this->assertEquals($titles, Util::buildActionTitles());
    }
}
