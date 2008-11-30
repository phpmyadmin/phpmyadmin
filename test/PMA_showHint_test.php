<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for showHint function
 *
 * @author Michal Biniek <michal@bystrzyca.pl>
 * @package phpMyAdmin-test
 * @version $Id: PMA_showHint_test.php
 */

/**
 * Tests core.
 */
require_once 'PHPUnit/Framework.php';

/**
 * Include to test.
 */
require_once './libraries/common.lib.php';

/**
 * Test showHint function.
 *
 */
class PMA_showHint_test extends PHPUnit_Framework_TestCase
{

    /**
     * temporary variable for globals array
     */

    protected $tmpGlobals;

    /**
     * temporary variable for session array
     */

    protected $tmpSession;

    /**
     * storing globals and session
     */
    public function setUp() {

        $this->tmpGlobals = $GLOBALS;
        $this->tmpSession = $_SESSION;
        
    }

    /**
     * recovering globals and session
     */
    public function tearDown() {

        $GLOBALS = $this->tmpGlobals;
        $_SESSION = $this->tmpSession;

    }

    /**
     * PMA_showHint with defined GLOBALS
     */

    public function testShowHintWithGlobals() {

        $key = md5('test');
        $nr = 1234;
        $instance = 1;

        $GLOBALS['footnotes'][$key]['nr'] = $nr;
        $GLOBALS['footnotes'][$key]['instance'] = $instance;
        $this->assertEquals(sprintf('<sup class="footnotemarker" id="footnote_sup_%d_%d">%d</sup>', $nr, $instance+1, $nr), PMA_showHint('test'));

    }

    /**
     * PMA_showHint with defined GLOBALS formatted as BB
     */

    public function testShowHintWithGlobalsBbFormat() {

        $key = md5('test');
        $nr = 1234;
        $instance = 1;

        $GLOBALS['footnotes'][$key]['nr'] = $nr;
        $GLOBALS['footnotes'][$key]['instance'] = $instance;
        $this->assertEquals(sprintf('[sup]%d[/sup]', $nr), PMA_showHint('test', true));

    }

    /**
     * PMA_showHint with not defined GLOBALS
     */

    public function testShowHintWithoutGlobals() {

        $key = md5('test');
        $nr = 1;
        $instance = 1;

        $this->assertEquals(sprintf('<sup class="footnotemarker" id="footnote_sup_%d_%d">%d</sup>', $nr, $instance, $nr), PMA_showHint('test', false, 'notice'));

        $expArray = array(
                'note' => 'test',
                'type' => 'notice',
                'nr' => count($GLOBALS['footnotes']),
                'instance' => 1
        );

        $this->assertEquals($expArray, $GLOBALS['footnotes'][$key]);

    }

    /**
     * PMA_showHint with not defined GLOBALS formatted as BB
     */

    public function testShowHintWithoutGlobalsBbFormat() {

        $key = md5('test');
        $nr = 1;
        $instance = 1;

        $this->assertEquals(sprintf('[sup]%d[/sup]', $nr), PMA_showHint('test', true, 'notice'));

        $expArray = array(
                'note' => 'test',
                'type' => 'notice',
                'nr' => count($GLOBALS['footnotes']),
                'instance' => 1
        );

        $this->assertEquals($expArray, $GLOBALS['footnotes'][$key]);

    }

    /**
     * PMA_showHint with defined GLOBALS using PMA_Message object
     */

    public function testShowHintPmaMessageWithGlobals() {
    
        $nr = 1;
        $instance = 1;

        $oMock = $this->getMock('PMA_Message', array('setMessage', 'setNumber', 'getHash', 'getLevel'));
        $oMock->setMessage('test');
        $oMock->setNumber($nr);

        $GLOBALS['footnotes'][$key]['nr'] = $nr;
        $GLOBALS['footnotes'][$key]['instance'] = $instance;

        $this->assertEquals(sprintf('<sup class="footnotemarker" id="footnote_sup_%d_%d">%d</sup>', $nr, $instance+1, $nr), PMA_showHint($oMock));
    }

    /**
     * PMA_showHint with not defined GLOBALS using PMA_Message object
     */

    public function testShowHintPmaMessageWithoutGlobals() {
    
        $nr = 1;
        $instance = 1;

        $oMock = $this->getMock('PMA_Message', array('setMessage', 'setNumber', 'getHash', 'getLevel', 'getNumber'));
        $oMock->setMessage('test');
        $oMock->setNumber($nr);

        $this->assertEquals(sprintf('<sup class="footnotemarker" id="footnote_sup_%d_%d">%d</sup>', $nr, $instance, $nr), PMA_showHint($oMock, false));

        $key = $oMock->getHash();

        $expArray = array(
                'note' => $oMock,
                'type' => $oMock->getLevel(),
                'nr' => count($GLOBALS['footnotes']),
                'instance' => 1
        );

        $this->assertEquals($expArray, $GLOBALS['footnotes'][$key]);
    }

}
?>
