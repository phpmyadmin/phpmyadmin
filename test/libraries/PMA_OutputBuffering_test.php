<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Tests for Output Buffering class
 *
 * @package PhpMyAdmin-test
 */

require_once 'libraries/OutputBuffering.class.php';

/**
 * Tests for Output Buffering class
 *
 * @package PhpMyAdmin-test
 */
class PMA_OutputBuffering_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @return void
     */
    protected function setUp()
    {
        $GLOBALS['cfg']['OBGzip'] = false;
    }

    /**
     * Simple output buffering test
     *
     * @return void
     * @test
     */
    public function testSimpleOutputBuffering()
    {
        $content = 'Hello world!';

        $buffer = PMA_OutputBuffering::getInstance();

        $this->assertEquals(
            0,
            PHPUnit_Framework_Assert::readAttribute($buffer, '_current')
        );

        $buffer->start();

        $this->assertEquals(
            1,
            PHPUnit_Framework_Assert::readAttribute($buffer, '_current')
        );

        echo $content;

        $this->assertTrue($buffer->stop());

        $this->assertEquals(
            0,
            PHPUnit_Framework_Assert::readAttribute($buffer, '_current')
        );

        $output = $buffer->getContents();
        $this->assertEquals($content, $output);
    }

    /**
     * Simple output buffering test
     *
     * @return void
     * @test
     */
    public function testSuccessiveSimpleOutputBuffering()
    {
        $content = 'Hello world!';

        $buffer = PMA_OutputBuffering::getInstance();

        $this->assertEquals(
            0,
            PHPUnit_Framework_Assert::readAttribute($buffer, '_current')
        );

        $buffer->start();

        $this->assertEquals(
            1,
            PHPUnit_Framework_Assert::readAttribute($buffer, '_current')
        );

        echo $content;

        $this->assertTrue($buffer->stop());

        $this->assertEquals(
            0,
            PHPUnit_Framework_Assert::readAttribute($buffer, '_current')
        );

        $output = $buffer->getContents();
        $this->assertEquals($content, $output);
    }

    /**
     * Recursive output buffering test
     *
     * @return void
     * @test
     */
    public function testRecursiveOutputBuffering()
    {
        $content = 'Hello world!';
        $content2 = 'How are you?';

        $buffer = PMA_OutputBuffering::getInstance();
        $buffer->start();

        echo $content;

        $buffer->start();

        $this->assertEquals(
            2,
            PHPUnit_Framework_Assert::readAttribute($buffer, '_current')
        );

        echo $content2;

        $this->assertTrue($buffer->stop());

        $this->assertEquals(
            1,
            PHPUnit_Framework_Assert::readAttribute($buffer, '_current')
        );

        $this->assertTrue($buffer->stop());

        $this->assertEquals(
            0,
            PHPUnit_Framework_Assert::readAttribute($buffer, '_current')
        );

        $output = $buffer->getContents();

        $this->assertEquals($content . $content2, $output);
    }

    /**
     * Recursive output buffering test
     *
     * @return void
     * @test
     */
    public function testRecursiveOutputBufferingGettingAllContents()
    {
        $content = 'Hello world!';
        $content2 = 'How are you?';

        $buffer = PMA_OutputBuffering::getInstance();
        $buffer->start();

        echo $content;

        $buffer->start();

        echo $content2;

        $this->assertTrue($buffer->stop());

        $output = $buffer->getContents();
        $this->assertEquals($content2, $output);

        $this->assertTrue($buffer->stop());

        $output = $buffer->getContents();

        $this->assertEquals($content, $output);
    }

    /**
     * Recursive output buffering test
     *
     * @return void
     * @test
     */
    public function testRecursiveOutputBufferingOtherWayGettingAllContents()
    {
        $content = 'Hello world!';
        $content2 = 'How are you?';

        $buffer = PMA_OutputBuffering::getInstance();
        $buffer->start();

        $buffer->start();

        echo $content2;

        $this->assertTrue($buffer->stop());

        $output = $buffer->getContents();
        $this->assertEquals($content2, $output);

        echo $content;

        $this->assertTrue($buffer->stop());

        $output = $buffer->getContents();

        $this->assertEquals($content, $output);
    }

    /**
     * Recursive output buffering test
     *
     * @return void
     * @test
     */
    public function testRecursiveOutputBufferingBothWaysGettingAllContents()
    {
        $content = 'Hello world!';
        $content2 = 'How are you?';
        $content3 = 'Fine, thanks.';

        $buffer = PMA_OutputBuffering::getInstance();
        $buffer->start();

        echo $content;

        $buffer->start();

        echo $content2;

        $this->assertTrue($buffer->stop());

        $output = $buffer->getContents();
        $this->assertEquals($content2, $output);

        echo $content3;

        $this->assertTrue($buffer->stop());

        $output = $buffer->getContents();

        $this->assertEquals($content . $content3, $output);
    }

    /**
     * Recursive output buffering test
     *
     * @return void
     * @test
     */
    public function testComplexRecursiveOutputBuffering()
    {
        $content = 'Hello world!';
        $content2 = 'How are you?';
        $content3 = 'Fine, thanks.';

        $buffer = PMA_OutputBuffering::getInstance();
        $buffer->start();

        echo $content;

        $buffer->start();

        echo $content2;

        $this->assertTrue($buffer->stop());

        echo $content3;

        $this->assertTrue($buffer->stop());

        $output = $buffer->getContents();

        $this->assertEquals($content . $content2 . $content3, $output);
    }

    /**
     * Recursive output buffering test
     *
     * @return void
     * @test
     */
    public function testMoreComplexRecursiveOutputBuffering()
    {
        $content = 'Hello world!';
        $content2 = 'How are you?';
        $content3 = 'Fine, thanks.';
        $content4 = 'You\'re welcome.';
        $content5 = 'My pleasure!';

        $buffer = PMA_OutputBuffering::getInstance();
        //start 1st
        $buffer->start();

        echo $content;

        //start 2nd
        $buffer->start();

        echo $content2;

        //stop 2nd
        $this->assertTrue($buffer->stop());

        echo $content3;

        //start 2nd
        $buffer->start();

        echo $content4;

        //stop 2nd
        $this->assertTrue($buffer->stop());

        $this->assertEquals($content4, $buffer->getContents());

        //start 2nd
        $buffer->start();

        echo $content5;

        //stop 2nd
        $this->assertTrue($buffer->stop());

        //stop 1st
        $this->assertTrue($buffer->stop());

        $output = $buffer->getContents();

        $this->assertEquals($content . $content2 . $content3 . $content5, $output);
    }
}
