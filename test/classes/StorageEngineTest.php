<?php
/**
 * Tests for StorageEngine.php
 *
 * @package PhpMyAdmin-test
 */
declare(strict_types=1);

namespace PhpMyAdmin\Tests;

use PhpMyAdmin\StorageEngine;
use PhpMyAdmin\Tests\PmaTestCase;

/**
 * Tests for StorageEngine.php
 *
 * @package PhpMyAdmin-test
 */
class StorageEngineTest extends PmaTestCase
{
    /**
     * @access protected
     */
    protected $object;

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function setUp(): void
    {
        $GLOBALS['server'] = 1;
        $this->object = $this->getMockForAbstractClass(
            'PhpMyAdmin\StorageEngine',
            ['dummy']
        );
    }

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     * @return void
     */
    protected function tearDown(): void
    {
        unset($this->object);
    }

    /**
     * Test for getStorageEngines
     *
     * @return void
     */
    public function testGetStorageEngines()
    {

        $this->assertEquals(
            [
                'dummy' => [
                    'Engine' => 'dummy',
                    'Support' => 'YES',
                    'Comment' => 'dummy comment',
                ],
                'dummy2' => [
                    'Engine' => 'dummy2',
                    'Support' => 'NO',
                    'Comment' => 'dummy2 comment',
                ],
                'FEDERATED' => [
                    'Engine' => 'FEDERATED',
                    'Support' => 'NO',
                    'Comment' => 'Federated MySQL storage engine',
                ],
                'Pbxt' => [
                    'Engine'  => 'Pbxt',
                    'Support' => 'NO',
                    'Comment' => 'Pbxt storage engine',
                ],
            ],
            $this->object->getStorageEngines()
        );
    }

    /**
     * Test for getHtmlSelect
     *
     * @return void
     *
     * @group medium
     */
    public function testGetHtmlSelect()
    {
        $html = $this->object->getHtmlSelect();

        $this->assertStringContainsString(
            '<option value="dummy" title="dummy comment">',
            $html
        );
    }

    /**
     * Test for StorageEngine::getEngine
     *
     * @param string $expectedClass Class that should be selected
     * @param string $engineName    Engine name
     *
     * @return void
     *
     * @dataProvider providerGetEngine
     */
    public function testGetEngine($expectedClass, $engineName): void
    {
        $this->assertInstanceOf(
            $expectedClass,
            StorageEngine::getEngine($engineName)
        );
    }

    /**
     * Provider for testGetEngine
     *
     * @return array
     */
    public function providerGetEngine()
    {
        return [
            [
                'PhpMyAdmin\StorageEngine',
                'unknown engine',
            ],
            [
                'PhpMyAdmin\Engines\Bdb',
                'Bdb',
            ],
            [
                'PhpMyAdmin\Engines\Berkeleydb',
                'Berkeleydb',
            ],
            [
                'PhpMyAdmin\Engines\Binlog',
                'Binlog',
            ],
            [
                'PhpMyAdmin\Engines\Innobase',
                'Innobase',
            ],
            [
                'PhpMyAdmin\Engines\Innodb',
                'Innodb',
            ],
            [
                'PhpMyAdmin\Engines\Memory',
                'Memory',
            ],
            [
                'PhpMyAdmin\Engines\Merge',
                'Merge',
            ],
            [
                'PhpMyAdmin\Engines\MrgMyisam',
                'Mrg_Myisam',
            ],
            [
                'PhpMyAdmin\Engines\Myisam',
                'Myisam',
            ],
            [
                'PhpMyAdmin\Engines\Ndbcluster',
                'Ndbcluster',
            ],
            [
                'PhpMyAdmin\Engines\Pbxt',
                'Pbxt',
            ],
            [
                'PhpMyAdmin\Engines\PerformanceSchema',
                'Performance_Schema',
            ],
        ];
    }

    /**
     * Test for isValid
     *
     * @return void
     */
    public function testIsValid()
    {

        $this->assertTrue(
            $this->object->isValid('PBMS')
        );
        $this->assertTrue(
            $this->object->isValid('dummy')
        );
        $this->assertTrue(
            $this->object->isValid('dummy2')
        );
        $this->assertFalse(
            $this->object->isValid('invalid')
        );
    }

    /**
     * Test for getPage
     *
     * @return void
     */
    public function testGetPage()
    {

        $this->assertEquals(
            '',
            $this->object->getPage('Foo')
        );
    }

    /**
     * Test for getInfoPages
     *
     * @return void
     */
    public function testGetInfoPages()
    {

        $this->assertEquals(
            [],
            $this->object->getInfoPages()
        );
    }

    /**
     * Test for getVariablesLikePattern
     *
     * @return void
     */
    public function testGetVariablesLikePattern()
    {

        $this->assertEquals(
            '',
            $this->object->getVariablesLikePattern()
        );
    }

    /**
     * Test for getMysqlHelpPage
     *
     * @return void
     */
    public function testGetMysqlHelpPage()
    {

        $this->assertEquals(
            'dummy-storage-engine',
            $this->object->getMysqlHelpPage()
        );
    }

    /**
     * Test for getVariables
     *
     * @return void
     */
    public function testGetVariables()
    {

        $this->assertEquals(
            [],
            $this->object->getVariables()
        );
    }

    /**
     * Test for getSupportInformationMessage
     *
     * @return void
     */
    public function testGetSupportInformationMessage()
    {
        $this->assertEquals(
            'dummy is available on this MySQL server.',
            $this->object->getSupportInformationMessage()
        );

        $this->object->support = 1;
        $this->assertEquals(
            'dummy has been disabled for this MySQL server.',
            $this->object->getSupportInformationMessage()
        );

        $this->object->support = 2;
        $this->assertEquals(
            'dummy is available on this MySQL server.',
            $this->object->getSupportInformationMessage()
        );

        $this->object->support = 3;
        $this->assertEquals(
            'dummy is the default storage engine on this MySQL server.',
            $this->object->getSupportInformationMessage()
        );
    }

    /**
     * Test for getComment
     *
     * @return void
     */
    public function testGetComment()
    {

        $this->assertEquals(
            'dummy comment',
            $this->object->getComment()
        );
    }

    /**
     * Test for getTitle
     *
     * @return void
     */
    public function testGetTitle()
    {

        $this->assertEquals(
            'dummy',
            $this->object->getTitle()
        );
    }

    /**
     * Test for resolveTypeSize
     *
     * @return void
     */
    public function testResolveTypeSize()
    {

        $this->assertEquals(
            [
                0 => 12,
                1 => 'B',
            ],
            $this->object->resolveTypeSize(12)
        );
    }
}
