<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for methods in PhpMyAdmin\VersionInformation class
 *
 * @package PhpMyAdmin-test
 */
namespace PhpMyAdmin\Tests;

use PhpMyAdmin\Tests\PmaTestCase;
use PhpMyAdmin\VersionInformation;
use stdClass;

/**
 * Tests for methods in PhpMyAdmin\VersionInformation class
 *
 * @package PhpMyAdmin-test
 */
class VersionInformationTest extends PmaTestCase
{
    private $_releases;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp()
    {
        $this->_releases = array();

        $release = new stdClass();
        $release->date = "2015-09-08";
        $release->php_versions = ">=5.3,<7.1";
        $release->version = "4.4.14.1";
        $release->mysql_versions = ">=5.5";
        $this->_releases[] = $release;

        $release = new stdClass();
        $release->date = "2015-09-09";
        $release->php_versions = ">=5.3,<7.0";
        $release->version = "4.4.13.3";
        $release->mysql_versions = ">=5.5";
        $this->_releases[] = $release;

        $release = new stdClass();
        $release->date = "2015-05-13";
        $release->php_versions = ">=5.2,<5.3";
        $release->version = "4.0.10.10";
        $release->mysql_versions = ">=5.0";
        $this->_releases[] = $release;
    }

    /**
     * Test version checking
     *
     * @return void
     *
     * @group large
     * @group network
     */
    public function testGetLatestVersion()
    {
        $GLOBALS['cfg']['ProxyUrl'] = PROXY_URL;
        $GLOBALS['cfg']['ProxyUser'] = PROXY_USER;
        $GLOBALS['cfg']['ProxyPass'] = PROXY_PASS;
        $GLOBALS['cfg']['VersionCheck'] = true;
        $versionInformation = new VersionInformation();
        $version = $versionInformation->getLatestVersion();
        $this->assertNotEmpty($version->version);
        $this->assertNotEmpty($version->date);
    }

    /**
     * Test version to int conversion.
     *
     * @param string $version Version string
     * @param int    $numeric Integer matching version
     *
     * @return void
     *
     * @dataProvider dataVersions
     */
    public function testVersionToInt($version, $numeric)
    {
        $versionInformation = new VersionInformation();
        $this->assertEquals(
            $numeric,
            $versionInformation->versionToInt($version)
        );
    }


    /**
     * Data provider for version parsing
     *
     * @return array with test data
     */
    public function dataVersions()
    {
        return array(
            array('1.0.0', 1000050),
            array('2.0.0.2-dev', 2000002),
            array('3.4.2.1', 3040251),
            array('3.4.2-dev3', 3040203),
            array('3.4.2-dev', 3040200),
            array('3.4.2-pl', 3040260),
            array('3.4.2-pl3', 3040263),
            array('4.4.2-rc22', 4040252),
            array('4.4.2-rc', 4040230),
            array('4.4.22-beta22', 4042242),
            array('4.4.22-beta', 4042220),
            array('4.4.21-alpha22', 4042132),
            array('4.4.20-alpha', 4042010),
            array('4.40.20-alpha-dev', 4402010),
            array('4.4a', 4000050),
            array('4.4.4-test', 4040400),
            array('4.1.0', 4010050),
            array('4.0.1.3', 4000153),
            array('4.1-dev', 4010000),
        );
    }

    /**
     * Tests getLatestCompatibleVersion() when there is only one server confgiured
     *
     * @return void
     */
    public function testGetLatestCompatibleVersionWithSingleServer()
    {
        $GLOBALS['cfg']['Servers'] = array(
            array()
        );

        $mockVersionInfo = $this->getMockBuilder('PhpMyAdmin\VersionInformation')
            ->setMethods(array('evaluateVersionCondition'))
            ->getMock();

        $mockVersionInfo->expects($this->at(0))
            ->method('evaluateVersionCondition')
            ->with('PHP', '>=5.3')
            ->will($this->returnValue(true));

        $mockVersionInfo->expects($this->at(1))
            ->method('evaluateVersionCondition')
            ->with('PHP', '<7.1')
            ->will($this->returnValue(true));

        $mockVersionInfo->expects($this->at(2))
            ->method('evaluateVersionCondition')
            ->with('MySQL', '>=5.5')
            ->will($this->returnValue(true));

        $compatible = $mockVersionInfo
            ->getLatestCompatibleVersion($this->_releases);
        $this->assertEquals('4.4.14.1', $compatible['version']);

    }

    /**
     * Tests getLatestCompatibleVersion() when there are multiple servers configured
     *
     * @return void
     */
    public function testGetLaestCompatibleVersionWithMultipleServers()
    {
        $GLOBALS['cfg']['Servers'] = array(
            array(),
            array()
        );

        $mockVersionInfo = $this->getMockBuilder('PhpMyAdmin\VersionInformation')
            ->setMethods(array('evaluateVersionCondition'))
            ->getMock();

        $mockVersionInfo->expects($this->at(0))
            ->method('evaluateVersionCondition')
            ->with('PHP', '>=5.3')
            ->will($this->returnValue(true));

        $mockVersionInfo->expects($this->at(1))
            ->method('evaluateVersionCondition')
            ->with('PHP', '<7.1')
            ->will($this->returnValue(true));

        $compatible = $mockVersionInfo
            ->getLatestCompatibleVersion($this->_releases);
        $this->assertEquals('4.4.14.1', $compatible['version']);
    }

    /**
     * Tests getLatestCompatibleVersion() with an old PHP version
     *
     * @return void
     */
    public function testGetLaestCompatibleVersionWithOldPHPVersion()
    {
        $GLOBALS['cfg']['Servers'] = array(
            array(),
            array()
        );

        $mockVersionInfo = $this->getMockBuilder('PhpMyAdmin\VersionInformation')
            ->setMethods(array('evaluateVersionCondition'))
            ->getMock();

        $mockVersionInfo->expects($this->at(0))
            ->method('evaluateVersionCondition')
            ->with('PHP', '>=5.3')
            ->will($this->returnValue(false));

        $mockVersionInfo->expects($this->at(1))
            ->method('evaluateVersionCondition')
            ->with('PHP', '>=5.3')
            ->will($this->returnValue(false));

        $mockVersionInfo->expects($this->at(2))
            ->method('evaluateVersionCondition')
            ->with('PHP', '>=5.2')
            ->will($this->returnValue(true));

        $mockVersionInfo->expects($this->at(3))
            ->method('evaluateVersionCondition')
            ->with('PHP', '<5.3')
            ->will($this->returnValue(true));

        $compatible = $mockVersionInfo
            ->getLatestCompatibleVersion($this->_releases);
        $this->assertEquals('4.0.10.10', $compatible['version']);
    }

    /**
     * Tests evaluateVersionCondition() method
     *
     * @return void
     */
    public function testEvaluateVersionCondition()
    {
        $mockVersionInfo = $this->getMockBuilder('PhpMyAdmin\VersionInformation')
            ->setMethods(array('getPHPVersion'))
            ->getMock();

        $mockVersionInfo->expects($this->any())
            ->method('getPHPVersion')
            ->will($this->returnValue('5.2.4'));

        $this->assertTrue($mockVersionInfo->evaluateVersionCondition('PHP', '<=5.3'));
        $this->assertTrue($mockVersionInfo->evaluateVersionCondition('PHP', '<5.3'));
        $this->assertTrue($mockVersionInfo->evaluateVersionCondition('PHP', '>=5.2'));
        $this->assertTrue($mockVersionInfo->evaluateVersionCondition('PHP', '>5.2'));
        $this->assertTrue($mockVersionInfo->evaluateVersionCondition('PHP', '!=5.3'));

        $this->assertFalse($mockVersionInfo->evaluateVersionCondition('PHP', '<=5.2'));
        $this->assertFalse($mockVersionInfo->evaluateVersionCondition('PHP', '<5.2'));
        $this->assertFalse($mockVersionInfo->evaluateVersionCondition('PHP', '>=7.0'));
        $this->assertFalse($mockVersionInfo->evaluateVersionCondition('PHP', '>7.0'));
        $this->assertTrue($mockVersionInfo->evaluateVersionCondition('PHP', '!=5.2'));
    }
}
