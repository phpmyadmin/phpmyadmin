<?php

declare(strict_types=1);

namespace PhpMyAdmin\Tests\Html;

use PhpMyAdmin\Template;
use PhpMyAdmin\Tests\AbstractTestCase;
use PhpMyAdmin\Url;

class SecurityTest extends AbstractTestCase
{
    /** @var Template */
    protected $template;

    protected function setUp(): void
    {
        parent::setUp();
        $this->template = new Template();
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        unset($this->template);
    }

    public function testInjectCodeUsingTemplate(): void
    {
        $this->assertSame(
            '?db=%3Cscript%3Ealert%28%27%26%3D%21%3A%3B%27%29%3B%3C%2Fscr'
            . 'ipt%3E&amp;table=%26mytable%3E1%3F&amp;server=12'
            . "\n"
            . '?db=%22%27%22%3E%3Ciframe+onload%3Dalert%281%29%3E%D1%88%D0%B5%D0%BB%D0%BB%D1%8B'
            . '&amp;table=%26mytable%3E1%3F&amp;server=12&amp;%3Cscript%3E%26%3D=%3C%2Fscript%3E'
            . "\n",
            $this->template->render('test/add_data', [
                'variable1' => Url::getCommon([
                    'db' => '<script>alert(\'&=!:;\');</script>',
                    'table' => '&mytable>1?',
                    'server' => 12,
                ]),
                'variable2' => Url::getCommonRaw([
                    'db' => '"\'"><iframe onload=alert(1)>шеллы',
                    'table' => '&mytable>1?',
                    'server' => 12,
                    '<script>&=' => '</script>',
                ]),
            ])
        );
        $url1 = Url::getCommon([
            'db' => '<script>alert(\'&=!:;\');</script>',
            'table' => '&mytable>1?',
            'server' => 12,
        ]);
        $this->assertSame(
            '?db=%3Cscript%3Ealert%28%27%26%3D%21%3A%3B%27%29%3B%3C%2Fscr'
            . 'ipt%3E&table=%26mytable%3E1%3F&server=12',
            $url1
        );
        $this->assertSame(
            $url1
            . "\n"
            . '?db=%22%27%22%3E%3Ciframe+onload%3Dalert%281%29%3E%D1%88%D0%B5%D0%BB%D0%BB%D1%8B'
            . '&table=%26mytable%3E1%3F&server=12&%3Cscript%3E%26%3D=%3C%2Fscript%3E'
            . "\n",
            $this->template->render('test/raw_output', [
                'variable1' => $url1,
                'variable2' => Url::getCommonRaw([
                    'db' => '"\'"><iframe onload=alert(1)>шеллы',
                    'table' => '&mytable>1?',
                    'server' => 12,
                    '<script>&=' => '</script>',
                ]),
            ])
        );
    }
}
