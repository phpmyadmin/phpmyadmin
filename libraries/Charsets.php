<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * MySQL charset metadata and manipulations
 *
 * @package PhpMyAdmin
 */
namespace PMA\libraries;

/**
 * Class used to manage MySQL charsets
 *
 * @package PhpMyAdmin
 */
class Charsets
{

    /**
     * MySQL charsets map
     *
     * @var array
     */
    public static $mysql_charset_map = array(
        'big5'         => 'big5',
        'cp-866'       => 'cp866',
        'euc-jp'       => 'ujis',
        'euc-kr'       => 'euckr',
        'gb2312'       => 'gb2312',
        'gbk'          => 'gbk',
        'iso-8859-1'   => 'latin1',
        'iso-8859-2'   => 'latin2',
        'iso-8859-7'   => 'greek',
        'iso-8859-8'   => 'hebrew',
        'iso-8859-8-i' => 'hebrew',
        'iso-8859-9'   => 'latin5',
        'iso-8859-13'  => 'latin7',
        'iso-8859-15'  => 'latin1',
        'koi8-r'       => 'koi8r',
        'shift_jis'    => 'sjis',
        'tis-620'      => 'tis620',
        'utf-8'        => 'utf8',
        'windows-1250' => 'cp1250',
        'windows-1251' => 'cp1251',
        'windows-1252' => 'latin1',
        'windows-1256' => 'cp1256',
        'windows-1257' => 'cp1257',
    );
}
