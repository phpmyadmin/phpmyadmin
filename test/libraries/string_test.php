<?php
/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * tests for string library
 *
 * @package PhpMyAdmin-test
 */

require_once 'test/libraries/string_test_abstract.php';

require_once 'libraries/string.lib.php';
if (MULTIBYTES_STATUS === MULTIBYTES_OFF) {
    /**
     * tests for string library
     *
     * @package PhpMyAdmin-test
     */
    class PMA_StringNativeTest extends PMA_StringTest
    {
    }
} else {
    /**
     * tests for multibytes string library
     *
     * @package PhpMyAdmin-test
     */
    class PMA_StringMbTest extends PMA_StringTest
    {
        /**
         * Data provider for testStrlen
         *
         * @return array Test data
         */
        public function providerStrlen()
        {
            return array_merge(
                parent::providerStrlen(),
                array(array(13, "chaîne testée"))
            );
        }

        /**
         * Data provider for testSubStr
         *
         * @return array Test data
         */
        public function providerSubstr()
        {
            return array_merge(
                parent::providerSubstr(),
                array(
                    array("rçon", "garçon", 2, 4),
                    array("de ", "garçon de café", 7, 3)
                )
            );
        }

        /**
         * Data provider for testSubstrCount
         *
         * @return array Test data
         */
        public function providerSubstrCount()
        {
            return array_merge(
                parent::providerSubstrCount(),
                array(
                    array(2, "garçon de café", "a"),
                    array(1, "garçon de café attristé", "ç"),
                    array(2, "garçon de café attristé", "é"),
                    array(1, "garçon de café attristé", "fé"),
                )
            );
        }

        //providerSubstrCountException

        /**
         * Data provider for testStrpos
         *
         * @return array Test data
         */
        public function providerStrpos()
        {
            return array_merge(
                parent::providerStrpos(),
                array(
                    array(16, "garçon de café attristé", "t"),
                    array(13, "garçon de café attristé", "é"),
                    array(22, "garçon de café attristé", "é", 15),
                )
            );
        }

        //providerStrpos
        //providerStrrchr
        //providerStrtolower
    }
}
