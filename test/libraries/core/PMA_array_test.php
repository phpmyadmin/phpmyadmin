<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Test for PMA_arrayRead(), PMA_arrayWrite(), PMA_arrayRemove()
 *
 * @package PhpMyAdmin-test
 */

/*
 * Include to test.
 */

/**
 * Test for PMA_arrayRead(), PMA_arrayWrite(), PMA_arrayRemove()
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
}
