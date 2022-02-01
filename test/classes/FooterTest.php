<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use ArrayIterator;
use PhpMyAdmin\ErrorHandler;
use PhpMyAdmin\Footer;
use PhpMyAdmin\Template;

use function json_encode;

/**
 * @covers \PhpMyAdmin\Footer
 */
class FooterTest extends AbstractTestCase
{
    /** @var array store private attributes of PhpMyAdmin\Footer */
    public $privates = [];

    /** @var Footer */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     */
    protected function setUp(): void
    {
        parent::setUp();
        parent::setLanguage();
        parent::setGlobalConfig();
        parent::setTheme();
        $_SERVER['SCRIPT_NAME'] = 'index.php';
        $GLOBALS['PMA_PHP_SELF'] = 'index.php';
        $GLOBALS['db'] = '';
        $GLOBALS['table'] = '';
        $GLOBALS['text_dir'] = 'ltr';
        $GLOBALS['cfg']['Server']['DisableIS'] = false;
        $GLOBALS['cfg']['Server']['verbose'] = 'verbose host';
        $GLOBALS['server'] = '1';
        $_GET['reload_left_frame'] = '1';
        $GLOBALS['focus_querywindow'] = 'main_pane_left';
        $this->object = new Footer();
        unset($GLOBALS['error_message']);
        unset($GLOBALS['sql_query']);
        $GLOBALS['errorHandler'] = new ErrorHandler();
        unset($_POST);
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     */
    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->object);
    }

    /**
     * Test for getDebugMessage
     *
     * @group medium
     */
    public function testGetDebugMessage(): void
    {
        $GLOBALS['cfg']['DBG']['sql'] = true;
        $_SESSION['debug']['queries'] = [
            [
                'count' => 1,
                'time' => 0.2,
                'query' => 'SELECT * FROM `pma_bookmark` WHERE 1',
            ],
            [
                'count' => 1,
                'time' => 2.5,
                'query' => 'SELECT * FROM `db` WHERE 1',
            ],
        ];

        $this->assertEquals(
            '{"queries":[{"count":1,"time":0.2,"query":"SELECT * FROM `pma_bookmark` WHERE 1"},'
            . '{"count":1,"time":2.5,"query":"SELECT * FROM `db` WHERE 1"}]}',
            $this->object->getDebugMessage()
        );
    }

    /**
     * Test for removeRecursion
     */
    public function testRemoveRecursion(): void
    {
        $object = (object) [];
        $object->child = (object) [];
        $object->childIterator = new ArrayIterator();
        $object->child->parent = $object;

        $this->callFunction($this->object, Footer::class, 'removeRecursion', [&$object]);
        $this->assertEquals(
            '{"child":{"parent":"***RECURSION***"},"childIterator":"***ITERATOR***"}',
            json_encode($object)
        );
    }

    /**
     * Test for disable
     */
    public function testDisable(): void
    {
        $footer = new Footer();
        $footer->disable();
        $this->assertEquals(
            '',
            $footer->getDisplay()
        );
    }

    /**
     * Test for footer when ajax enabled
     */
    public function testAjax(): void
    {
        $footer = new Footer();
        $footer->setAjax(true);
        $template = new Template();
        $this->assertEquals(
            $template->render('modals/function_confirm') . "\n"
            . $template->render('modals/add_index') . "\n"
            . $template->render('modals/page_settings') . "\n",
            $footer->getDisplay()
        );
    }

    /**
     * Test for footer get Scripts
     */
    public function testGetScripts(): void
    {
        $footer = new Footer();
        $this->assertStringContainsString(
            '<script data-cfasync="false" type="text/javascript">',
            $footer->getScripts()->getDisplay()
        );
    }

    /**
     * Test for displaying footer
     *
     * @group medium
     */
    public function testDisplay(): void
    {
        $footer = new Footer();
        $this->assertStringContainsString(
            'Open new phpMyAdmin window',
            $footer->getDisplay()
        );
    }

    /**
     * Test for minimal footer
     */
    public function testMinimal(): void
    {
        $footer = new Footer();
        $footer->setMinimal();
        $template = new Template();
        $this->assertEquals(
            $template->render('modals/function_confirm') . "\n"
            . $template->render('modals/add_index') . "\n"
            . $template->render('modals/page_settings')
            . "\n  </div>\n  </body>\n</html>\n",
            $footer->getDisplay()
        );
    }
}
