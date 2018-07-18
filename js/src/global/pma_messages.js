/* vim: set expandtab sw=4 ts=4 sts=4: */

/**
 * Takes parameters defined in messages.php file like messages, validations,
 * jquery-ui-timepicker edits using global functions
 */

export const PMA_messages = (function () {
    /**
     * @var obj params An associate array having key value pairs
     * of messages to show in js files.
     *
     *  @access private
     */
    let messages = new Array();
    /**
     * @var obj params Associative array having global configurations
     *
     *  @access private
     */
    let globalVars = new Array();
    /**
     * @var obj params Associative array having timepicker edits
     *
     *  @access private
     */
    let timePickerVars = new Array();
    /**
     *
     *  @var obj params Object having validation edits for jQuery
     */
    let validationVars = {};
    return {
        /**
         * Retrieves the messages array
         *
         *  @return array
         */
        getMessages: () => {
            return messages;
        },
        /**
         * Retrieves the globalVars array
         *
         *  @return array
         */
        getGlobalVars: () => {
            return globalVars;
        },
        /**
         * Retrieves the timePickerVars array
         *
         *  @return array
         */
        getTimePickerVars: () => {
            return timePickerVars;
        },
        /**
         * Retrieves the validationVars array
         *
         *  @return array
         */
        getValidatorMessages: () => {
            return validationVars;
        },
        /**
         * Saves the key value pair provided in input
         *
         *  @param obj array The input array of messages
         *
         *  @return void
         */
        setAllMessages: (obj) => {
            for (var i in obj) {
                messages[i] = obj[i];
            }
        },
        /**
         * Saves the key value pair provided in input
         *
         *  @param obj array The input array of global variables
         *
         *  @return void
         */
        setGlobalVars: (obj) => {
            for (var i in obj) {
                globalVars[i] = obj[i];
            }
        },
        /**
         * Saves the key value pair provided in input
         *
         * @param obj array The input array of timepicker edits
         *
         *  @return void
         */
        setTimePickerVars: (obj) => {
            for (var i in obj) {
                timePickerVars[i] = obj[i];
            }
        },
        /**
         * Saves the key value pair provided in input
         *
         * @param obj array The input array jQuery validation edits
         *
         * @return void
         */
        setValidatorMessages: (obj) => {
            for (var i in obj) {
                validationVars[i] = obj[i];
            }
        },
    };
}());
