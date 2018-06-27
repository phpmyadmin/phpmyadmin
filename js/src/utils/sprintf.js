import sprintf from 'sprintf-js';

/**
 * @param string string message to display
 *
 * @return string      A concated string of aguments passed
 */

export function PMA_sprintf () {
    /**
     * This package can be implemented in two ways
     *
     * 1) sprintf.sprintf("A %s is %s", "string", "string");
     *
     * 2) sprintf.vsprintf("A %s is %s", ["string", "string"]);
     */
    return sprintf.sprintf(...arguments);
}
