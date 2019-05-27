/* vim: set expandtab sw=4 ts=4 sts=4: */

$(function () {
    Functions.checkNumberOfFields();
});

/**
 * Holds common parameters such as server, db, table, etc
 *
 * The content for this is normally loaded from Header.php or
 * Response.php and executed by ajax.js
 */
var CommonParams = (function () {
    /**
     * @var hash params An associative array of key value pairs
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
         * @return void
         */
        setAll: function (obj) {
            var reload = false;
            var updateNavigation = false;
            for (var i in obj) {
                if (params[i] !== undefined && params[i] !== obj[i]) {
                    if (i === 'db' || i === 'table') {
                        updateNavigation = true;
                    }
                    reload = true;
                }
                params[i] = obj[i];
            }
            if (updateNavigation &&
                    $('#pma_navigation_tree').hasClass('synced')
            ) {
                Navigation.showCurrent();
            }
        },
        /**
         * Retrieves a value given its key
         * Returns empty string for undefined values
         *
         * @param name string The key
         *
         * @return string
         */
        get: function (name) {
            return params[name];
        },
        /**
         * Saves a single key value pair
         *
         * @param name  string The key
         * @param value string The value
         *
         * @return self For chainability
         */
        set: function (name, value) {
            var updateNavigation = false;
            if (name === 'db' || name === 'table' &&
                params[name] !== value
            ) {
                updateNavigation = true;
            }
            params[name] = value;
            if (updateNavigation &&
                    $('#pma_navigation_tree').hasClass('synced')
            ) {
                Navigation.showCurrent();
            }
            return this;
        },
        /**
         * Returns the url query string using the saved parameters
         *
         * @return string
         */
        getUrlQuery: function () {
            var common = this.get('common_query');
            var separator = '?';
            var argsep = CommonParams.get('arg_separator');
            if (common.length > 0) {
                separator = argsep;
            }
            return Functions.sprintf(
                '%s%sserver=%s' + argsep + 'db=%s' + argsep + 'table=%s',
                this.get('common_query'),
                separator,
                encodeURIComponent(this.get('server')),
                encodeURIComponent(this.get('db')),
                encodeURIComponent(this.get('table'))
            );
        }
    };
}());

/**
 * Holds common parameters such as server, db, table, etc
 *
 * The content for this is normally loaded from Header.php or
 * Response.php and executed by ajax.js
 */
var CommonActions = {
    /**
     * Saves the database name when it's changed
     * and reloads the query window, if necessary
     *
     * @param new_db string new_db The name of the new database
     *
     * @return void
     */
    setDb: function (new_db) {
        if (new_db !== CommonParams.get('db')) {
            CommonParams.setAll({ 'db': new_db, 'table': '' });
        }
    },
    /**
     * Opens a database in the main part of the page
     *
     * @param new_db string The name of the new database
     *
     * @return void
     */
    openDb: function (new_db) {
        CommonParams
            .set('db', new_db)
            .set('table', '');
        this.refreshMain(
            CommonParams.get('opendb_url')
        );
    },
    /**
     * Refreshes the main frame
     *
     * @param mixed url Undefined to refresh to the same page
     *                  String to go to a different page, e.g: 'index.php'
     *
     * @return void
     */
    refreshMain: function (url, callback) {
        if (! url) {
            url = $('#selflink').find('a').attr('href');
            url = url.substring(0, url.indexOf('?'));
        }
        url += CommonParams.getUrlQuery();
        $('<a></a>', { href: url })
            .appendTo('body')
            .trigger('click')
            .remove();
        AJAX._callback = callback;
    }
};
