/**
 * Holds common parameters such as server, db, table, etc
 *
 * The content for this is normally loaded from Header.php or
 * Response.php and executed by ajax.js
 *
 * @test-module CommonParams
 */
const CommonParams = (function () {
    /**
     * @var {Object} params An associative array of key value pairs
     * @access private
     */
    var params = {};

    // The returned object is the public part of the module
    return {
        /**
         * Saves all the key value pair that
         * are provided in the input array
         *
         * @param obj hash The input array
         *
         * @return {boolean}
         */
        setAll: function (obj) {
            let updateNavigation = false;
            for (var i in obj) {
                if (params[i] !== undefined && params[i] !== obj[i]) {
                    if (i === 'db' || i === 'table') {
                        updateNavigation = true;
                    }
                }

                params[i] = obj[i];
            }

            return updateNavigation;
        },
        /**
         * Retrieves a value given its key
         * Returns empty string for undefined values
         *
         * @param {string} name The key
         *
         * @return {string}
         */
        get: function (name) {
            return params[name];
        },
        /**
         * Saves a single key value pair
         *
         * @param {string} name  The key
         * @param {string} value The value
         *
         * @return {boolean}
         */
        set: function (name, value) {
            let updateNavigation = false;
            if (name === 'db' || name === 'table' &&
                params[name] !== value
            ) {
                updateNavigation = true;
            }

            params[name] = value;

            return updateNavigation;
        },
        /**
         * Returns the url query string using the saved parameters
         *
         * @param {string} separator New separator
         *
         * @return {string}
         */
        getUrlQuery: function (separator) {
            var sep = (typeof separator !== 'undefined') ? separator : '?';
            var common = this.get('common_query');
            var argsep = CommonParams.get('arg_separator');
            if (typeof common === 'string' && common.length > 0) {
                // If the last char is the separator, do not add it
                // Else add it
                common = common.endsWith(argsep) ? common : common + argsep;
            }

            return window.sprintf(
                '%s%sserver=%s' + argsep + 'db=%s' + argsep + 'table=%s',
                sep,
                common,
                encodeURIComponent(this.get('server')),
                encodeURIComponent(this.get('db')),
                encodeURIComponent(this.get('table'))
            );
        }
    };
}());

declare global {
    interface Window {
        CommonParams: typeof CommonParams;
    }
}

window.CommonParams = CommonParams;

export { CommonParams };
