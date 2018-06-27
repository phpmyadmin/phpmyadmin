/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Takes parameters defined in messages.php file like messages, validations,
 * jquery-ui-timepicker edits
 *
 * @module
 */

class PmaVariables {
    /**
     * @constructor
     */
    constructor () {
        /**
         * @var obj params An associate array having key value pairs
         * of messages to show in js files.
         *
         * @access private
         */
        this.pmaMessages = new Array();
        /**
         * @var obj params Associative array having global configurations
         *
         * @access private
         */
        this.globalVariables = new Array();
        /**
         * @var obj params Associative array having timepicker edits
         *
         * @access private
         */
        this.timePickerVars = new Array();
        /**
         *
         *  @var obj params Object having validation edits for jQuery
         */
        this.validationVars = {};
    }
    /**
     * Retrieves the messages array
     *
     *  @return array
     */
    getMessages () {
        return this.pmaMessages;
    }
    /**
     * Retrieves the globalVars array
     *
     *  @return array
     */
    getGlobalVars () {
        return this.globalVariables;
    }
    /**
     * Retrieves the timePickerVars array
     *
     *  @return array
     */
    getTimePickerVars () {
        return this.timePickerVars;
    }
    /**
     * Retrieves the validationVars array
     *
     *  @return array
     */
    getValidatorMessages () {
        return this.validationVars;
    }
    /**
     * Saves the key value pair provided in input
     *
     *  @param obj array The input array of messages
     *
     *  @return void
     */
    setAllMessages (obj) {
        for (var i in obj) {
            this.pmaMessages[i] = obj[i];
        }
    }
    /**
     * Saves the key value pair provided in input
     *
     *  @param obj array The input array of global variables
     *
     *  @return void
     */
    setGlobalVars (obj) {
        for (var i in obj) {
            this.globalVariables[i] = obj[i];
        }
    }
    /**
     * Saves the key value pair provided in input
     *
     * @param obj array The input array of timepicker edits
     *
     *  @return void
     */
    setTimePickerVars (obj) {
        for (var i in obj) {
            this.timePickerVars[i] = obj[i];
        }
    }
    /**
     * Saves the key value pair provided in input
     *
     * @param obj array The input array jQuery validation edits
     *
     * @return void
     */
    setValidatorMessages (obj) {
        for (var i in obj) {
            this.validationVars[i] = obj[i];
        }
    }
}

/**
 * @type {Object} Variables
 */
let Variables = new PmaVariables();

/**
 * Module export
 */
export default Variables;
