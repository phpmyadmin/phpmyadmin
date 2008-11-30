<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for generating localised date or timespan expression
 *
 * @author Michal Biniek <michal@bystrzyca.pl>
 * @package phpMyAdmin-test
 * @version $Id: PMA_localisedDateTimespan_test.php
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
 * Test localised date or timespan expression.
 *
 */
class PMA_localisedDateTimespan_test extends PHPUnit_Framework_TestCase
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
     * data provider for localised date test
     */

    public function localisedDateDataProvider() {
        return array(
            array(1227451958, '', 'Nov 23, 2008 at 03:52 PM'),
            array(1227451958, '%Y-%m-%d %H:%M:%S %a', '2008-11-23 15:52:38 Sun')
        );
    }

    /**
     * localised date test, globals are defined
     * @dataProvider localisedDateDataProvider
     */

    public function testLocalisedDate($a, $b, $e) {
        $GLOBALS['day_of_week'] = array('Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat');
        $GLOBALS['month'] = array('Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec');
        $GLOBALS['datefmt'] = '%B %d, %Y at %I:%M %p';

        $this->assertEquals($e, PMA_localisedDate($a, $b));
    }

    /**
     * data provider for localised timestamp test
     */

    public function timespanFormatDataProvider() {
        return array(
            array(1258, '0 days, 0 hours, 20 minutes and 58 seconds'),
            array(821958, '9 days, 12 hours, 19 minutes and 18 seconds')
        );
    }

    /**
     * localised timestamp test, globals are defined
     * @dataProvider timespanFormatDataProvider
     */

    public function testTimespanFormat($a, $e) {
        $GLOBALS['timespanfmt'] = '%s days, %s hours, %s minutes and %s seconds';

        $this->assertEquals($e, PMA_timespanFormat($a));
    }
}
?>
