<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for generating localised date or timespan expression
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */

/*
 * Include to test.
 */


/**
 * Test for generating localised date or timespan expression
 *
 * @package PhpMyAdmin-test
 * @group common.lib-tests
 */
class PMA_LocalisedDateTimespan_Test extends PHPUnit_Framework_TestCase
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
     * temporary variable for timezone info
     */
    protected $tmpTimezone;

    /**
     * storing globals and session
     *
     * @return void
     */
    public function setUp()
    {
        $this->tmpGlobals = $GLOBALS;
        $this->tmpSession = $_SESSION;
        $this->tmpTimezone = date_default_timezone_get();
        date_default_timezone_set('Europe/London');
    }

    /**
     * recovering globals and session
     *
     * @return void
     */
    public function tearDown()
    {
        $GLOBALS = $this->tmpGlobals;
        $_SESSION = $this->tmpSession;
        date_default_timezone_set($this->tmpTimezone);

    }

    /**
     * data provider for localised date test
     *
     * @return array
     */
    public function localisedDateDataProvider()
    {
        return array(
            array(1227455558, '', 'Nov 23, 2008 at 03:52 PM'),
            array(1227455558, '%Y-%m-%d %H:%M:%S %a', '2008-11-23 15:52:38 Sun')
        );
    }

    /**
     * localised date test, globals are defined
     *
     * @param string $a Current timestamp
     * @param string $b Format
     * @param string $e Expected output
     *
     * @return void
     *
     * @dataProvider localisedDateDataProvider
     */
    public function testLocalisedDate($a, $b, $e)
    {
        $this->assertEquals(
            $e, PMA\libraries\Util::localisedDate($a, $b)
        );
    }

    /**
     * data provider for localised timestamp test
     *
     * @return array
     */
    public function timespanFormatDataProvider()
    {
        return array(
            array(1258, '0 days, 0 hours, 20 minutes and 58 seconds'),
            array(821958, '9 days, 12 hours, 19 minutes and 18 seconds')
        );
    }

    /**
     * localised timestamp test, globals are defined
     *
     * @param int    $a Timespan in seconds
     * @param string $e Expected output
     *
     * @return void
     *
     * @dataProvider timespanFormatDataProvider
     */
    public function testTimespanFormat($a, $e)
    {
        $GLOBALS['timespanfmt'] = '%s days, %s hours, %s minutes and %s seconds';

        $this->assertEquals(
            $e, PMA\libraries\Util::timespanFormat($a)
        );
    }
}
