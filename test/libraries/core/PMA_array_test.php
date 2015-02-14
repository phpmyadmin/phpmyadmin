<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_arrayRead(), PMA_arrayWrite(), PMA_arrayRemove(),
 * PMA_arrayMergeRecursive(),
 * PMA_arrayWalkRecursive() from libraries/core.lib.php
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */
require_once 'libraries/core.lib.php';

/**
 * Test for PMA_arrayRead(), PMA_arrayWrite(), PMA_arrayRemove(),
 * PMA_arrayMergeRecursive(),
 * PMA_arrayWalkRecursive() from libraries/core.lib.php
 *
 * @package PhpMyAdmin-test
 */
class PMA_Array_Test extends PHPUnit_Framework_TestCase
{
    /**
     * Test for PMA_arrayRead
     *
     * @return void
     */
    function testRead()
    {
        $arr = array(
            "int" => 1,
            "str" => "str_val",
            "arr" => array('val1', 'val2', 'val3'),
            "sarr" => array(
                'arr1' => array(1, 2, 3),
                array(3, array('a', 'b', 'c'), 4)
            )
        );

        $this->assertEquals(
            PMA_arrayRead('int', $arr),
            $arr['int']
        );

        $this->assertEquals(
            PMA_arrayRead('str', $arr),
            $arr['str']
        );

        $this->assertEquals(
            PMA_arrayRead('arr/0', $arr),
            $arr['arr'][0]
        );

        $this->assertEquals(
            PMA_arrayRead('arr/1', $arr),
            $arr['arr'][1]
        );

        $this->assertEquals(
            PMA_arrayRead('arr/2', $arr),
            $arr['arr'][2]
        );

        $this->assertEquals(
            PMA_arrayRead('sarr/arr1/0', $arr),
            $arr['sarr']['arr1'][0]
        );

        $this->assertEquals(
            PMA_arrayRead('sarr/arr1/1', $arr),
            $arr['sarr']['arr1'][1]
        );

        $this->assertEquals(
            PMA_arrayRead('sarr/arr1/2', $arr),
            $arr['sarr']['arr1'][2]
        );

        $this->assertEquals(
            PMA_arrayRead('sarr/0/0', $arr),
            $arr['sarr'][0][0]
        );

        $this->assertEquals(
            PMA_arrayRead('sarr/0/1', $arr),
            $arr['sarr'][0][1]
        );

        $this->assertEquals(
            PMA_arrayRead('sarr/0/1/2', $arr),
            $arr['sarr'][0][1][2]
        );

        $this->assertEquals(
            PMA_arrayRead('sarr/not_exiting/1', $arr),
            null
        );

        $this->assertEquals(
            PMA_arrayRead('sarr/not_exiting/1', $arr, 0),
            0
        );

        $this->assertEquals(
            PMA_arrayRead('sarr/not_exiting/1', $arr, 'default_val'),
            'default_val'
        );
    }

    /**
     * Test for PMA_arrayWrite
     *
     * @return void
     */
    function testWrite()
    {
        $arr = array(
            "int" => 1,
            "str" => "str_val",
            "arr" => array('val1', 'val2', 'val3'),
            "sarr" => array(
                'arr1' => array(1, 2, 3),
                array(3, array('a', 'b', 'c'), 4)
            )
        );

        PMA_arrayWrite('int', $arr, 5);
        $this->assertEquals($arr['int'], 5);

        PMA_arrayWrite('str', $arr, '_str');
        $this->assertEquals($arr['str'], '_str');

        PMA_arrayWrite('arr/0', $arr, 'val_arr_0');
        $this->assertEquals($arr['arr'][0], 'val_arr_0');

        PMA_arrayWrite('arr/1', $arr, 'val_arr_1');
        $this->assertEquals($arr['arr'][1], 'val_arr_1');

        PMA_arrayWrite('arr/2', $arr, 'val_arr_2');
        $this->assertEquals($arr['arr'][2], 'val_arr_2');

        PMA_arrayWrite('sarr/arr1/0', $arr, 'val_sarr_arr_0');
        $this->assertEquals($arr['sarr']['arr1'][0], 'val_sarr_arr_0');

        PMA_arrayWrite('sarr/arr1/1', $arr, 'val_sarr_arr_1');
        $this->assertEquals($arr['sarr']['arr1'][1], 'val_sarr_arr_1');

        PMA_arrayWrite('sarr/arr1/2', $arr, 'val_sarr_arr_2');
        $this->assertEquals($arr['sarr']['arr1'][2], 'val_sarr_arr_2');

        PMA_arrayWrite('sarr/0/0', $arr, 5);
        $this->assertEquals($arr['sarr'][0][0], 5);

        PMA_arrayWrite('sarr/0/1/0', $arr, 'e');
        $this->assertEquals($arr['sarr'][0][1][0], 'e');

        PMA_arrayWrite('sarr/not_existing/1', $arr, 'some_val');
        $this->assertEquals($arr['sarr']['not_existing'][1], 'some_val');

        PMA_arrayWrite('sarr/0/2', $arr, null);
        $this->assertNull($arr['sarr'][0][2]);
    }

    /**
     * Test for PMA_arrayRemove
     *
     * @return void
     */
    function testRemove()
    {
        $arr = array(
            "int" => 1,
            "str" => "str_val",
            "arr" => array('val1', 'val2', 'val3'),
            "sarr" => array(
                'arr1' => array(1, 2, 3),
                array(3, array('a', 'b', 'c'), 4)
            )
        );

        PMA_arrayRemove('int', $arr);
        $this->assertArrayNotHasKey('int', $arr);

        PMA_arrayRemove('str', $arr);
        $this->assertArrayNotHasKey('str', $arr);

        PMA_arrayRemove('arr/0', $arr);
        $this->assertArrayNotHasKey(0, $arr['arr']);

        PMA_arrayRemove('arr/1', $arr);
        $this->assertArrayNotHasKey(1, $arr['arr']);

        PMA_arrayRemove('arr/2', $arr);
        $this->assertArrayNotHasKey('arr', $arr);

        $tmp_arr = $arr;
        PMA_arrayRemove('sarr/not_existing/1', $arr);
        $this->assertEquals($tmp_arr, $arr);

        PMA_arrayRemove('sarr/arr1/0', $arr);
        $this->assertArrayNotHasKey(0, $arr['sarr']['arr1']);

        PMA_arrayRemove('sarr/arr1/1', $arr);
        $this->assertArrayNotHasKey(1, $arr['sarr']['arr1']);

        PMA_arrayRemove('sarr/arr1/2', $arr);
        $this->assertArrayNotHasKey('arr1', $arr['sarr']);

        PMA_arrayRemove('sarr/0/0', $arr);
        $this->assertArrayNotHasKey(0, $arr['sarr'][0]);

        PMA_arrayRemove('sarr/0/1/0', $arr);
        $this->assertArrayNotHasKey(0, $arr['sarr'][0][1]);

        PMA_arrayRemove('sarr/0/1/1', $arr);
        $this->assertArrayNotHasKey(1, $arr['sarr'][0][1]);

        PMA_arrayRemove('sarr/0/1/2', $arr);
        $this->assertArrayNotHasKey(1, $arr['sarr'][0]);

        PMA_arrayRemove('sarr/0/2', $arr);

        $this->assertEmpty($arr);
    }

    /**
     * Test for PMA_arrayMergeRecursive
     *
     * @return void
     */
    function testMergeRecursive()
    {
        $arr1 = array('key1' => 1, 'key2' => 2.3, 'key3' => 'str3');
        $arr2 = array('key1' => 4, 'key2' => 5, 'key3' => 6);
        $arr3 = array('key4' => 7, 'key5' => 'str8', 'key6' => 9);
        $arr4 = array(1, 2, 3);

        $this->assertFalse(PMA_arrayMergeRecursive());

        $this->assertEquals(PMA_arrayMergeRecursive($arr1), $arr1);

        $this->assertEquals(PMA_arrayMergeRecursive($arr1, 'str'), 'str');

        $this->assertEquals(PMA_arrayMergeRecursive('str1', $arr2), $arr2);

        $this->assertEquals(
            PMA_arrayMergeRecursive($arr1, $arr2),
            array('key1' => 4, 'key2' => 5, 'key3' => 6)
        );

        $this->assertEquals(
            PMA_arrayMergeRecursive($arr1, $arr3),
            array(
                'key1' => 1,
                'key2' => 2.3,
                'key3' => 'str3',
                'key4' => 7,
                'key5' => 'str8',
                'key6' => 9
            )
        );

        $this->assertEquals(PMA_arrayMergeRecursive($arr2, $arr4), array(1, 2, 3));

        $this->assertEquals(
            PMA_arrayMergeRecursive($arr1, $arr2, $arr3),
            array(
                'key1' => 4,
                'key2' => 5,
                'key3' => 6,
                'key4' => 7,
                'key5' => 'str8',
                'key6' => 9
            )
        );
    }

    /**
     * Test for PMA_arrayWalkRecursive
     *
     * @return void
     */
    function testWalkRecursive()
    {
        /**
         * Concat a variable to a string
         *
         * @param string $var Variable to concat
         *
         * @return string
         */
        function fConcat($var)
        {
            return 'val: ' . $var . ' processed';
        }

        $arr = array(1, 2, 3, 4);
        $target = array(
            'val: 1 processed',
            'val: 2 processed',
            'val: 3 processed',
            'val: 4 processed'
        );

        PMA_arrayWalkRecursive($arr, 'fConcat');
        $this->assertEquals($arr, $target);
    }

    /**
     * Test for PMA_arrayWalkRecursive
     *
     * @return void
     *
     * @depends testWalkRecursive
     */
    function testWalkRecursiveNotProcessIntKeys()
    {
        /**
         * Increment a variable
         *
         * @param int $var Variable to increment
         *
         * @return int
         */
        function fAdd($var)
        {
            return ++$var;
        }

        $arr = array(1, 2, 3, 4);
        $target = array(2, 3, 4, 5);

        PMA_arrayWalkRecursive($arr, 'fAdd', true);
        $this->assertEquals($arr, $target);
    }

    /**
     * Test for PMA_arrayWalkRecursive
     *
     * @return void
     *
     * @depends testWalkRecursiveNotProcessIntKeys
     */
    function testWalkRecursiveSubArray()
    {
        $arr = array(
            "key1"=>'val1',
            'key2'=>array('skey1'=>'sval1', 'skey2'=>'sval2'),
            'key3'=>'val3'
        );
        $target = array(
            'key1'=>'val: val1 processed',
            'key2'=> array(
                'skey1'=>'val: sval1 processed', 'skey2'=>'val: sval2 processed'
            ),
            'key3'=>'val: val3 processed'
        );

        PMA_arrayWalkRecursive($arr, 'fConcat');
        $this->assertEquals($arr, $target);
    }

    /**
     * Test for PMA_arrayWalkRecursive
     *
     * @return void
     */
    function testWalkRecursiveApplyToKeysStripSlashes()
    {
        $arr = array(
            "key\\1"=>'v\\\\al1',
            'k\\ey2'=>array('s\\\\key1'=>'sval\\1', 's\\k\\ey2'=>'s\\v\\al2'),
            'key3'=>'val3'
        );
        $second = $arr;
        $target = array(
            "key1"=>'v\\al1',
            'key2'=>array('s\\key1'=>'sval1', 'skey2'=>'sval2'),
            'key3'=>'val3'
        );

        PMA_arrayWalkRecursive($arr, 'stripslashes', true);
        $this->assertEquals($arr, $target);
        PMA_arrayWalkRecursive($second, 'stripslashes', true);
        $this->assertEquals($second, $target);
    }
}
