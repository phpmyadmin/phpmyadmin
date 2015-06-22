/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * phpMyAdmin's BigInts library
 */

/**
 * @var BigInts object to handle big integers (in string)
 *      as JS can handle upto 53 bits of precision only.
 */
var BigInts = {

    /**
     * Compares two integer strings
     *
     * @param int1 the string representation of 1st integer
     * @param int2 the string representation of 2nd integer
     *
     * @return int 0 if equal, < 0 if int1 < int2, else > 0
     */
    compare: function(int1, int2) {
        // trim integers
        int1 = int1.trim();
        int2 = int2.trim();
        // length of integer strings
        var len1 = int1.length;
        var len2 = int2.length;
        // integer is -ve or not
        var isNeg1 = (int1[0] === '-');
        var isNeg2 = (int2[0] === '-');
        // Sign of int1 != int2 then no actual comparison
        // is needed we can return result directly
        if (isNeg1 !== isNeg2) {
            return (isNeg1 === true ? -1 : 1);
        }
        // replace - sign with 0
        int1[0] = isNeg1 ? '0' : int1[0];
        int2[0] = isNeg2 ? '0' : int2[0];
        // pad integers with 0 to make them
        // equal length
        int1 = BigInts.lpad(int1, len2);
        int2 = BigInts.lpad(int2, len1);
        // Now they are good to compare as strings
        if (int1 !== int2) {
            return (int1 < int2 ? -1 : 1);
        }
        return 0;
    },

    /**
     * Adds leading zeros to a integer given a total length
     *
     * @param int   the string representation of the integer
     * @param total the total length required
     *
     * @return int the integer of length given with added leading
     *             zeros if necessary
     */
    lpad: function(int, total){
        var len = int.length;
        var pad = '';
        while(len < total) {
            pad += '0';
            len++;
        }
        return (pad + int);
    }
};
