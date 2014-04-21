/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * Functionality for communicating with the querywindow
 */
$(function () {
    /**
     * Event handler for click on the open query window link
     * in the top menu of the navigation panel
     */
    $('#pma_open_querywindow').click(function (event) {
        event.preventDefault();
        PMA_querywindow.focus();
    });

    checkNumberOfFields();
});

/**
 * Holds common parameters such as server, db, table, etc
 *
 * The content for this is normally loaded from Header.class.php or
 * Response.class.php and executed by ajax.js
 */
var PMA_commonParams = (function () {
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
         * @param hash obj The input array
         *
         * @return void
         */
        setAll: function (obj) {
            var reload = false;
            var updateNavigation = false;
            for (var i in obj) {
                if (params[i] !== undefined && params[i] !== obj[i]) {
                    reload = true;
                }
                if (i == 'db' || i == 'table') {
                    updateNavigation = true;
                }
                params[i] = obj[i];
            }
            if (updateNavigation) {
                PMA_showCurrentNavigation();
            }
            if (reload) {
                PMA_querywindow.refresh();
            }
        },
        /**
         * Retrieves a value given its key
         * Returns empty string for undefined values
         *
         * @param string name The key
         *
         * @return string
         */
        get: function (name) {
            return params[name] || '';
        },
        /**
         * Saves a single key value pair
         *
         * @param string name  The key
         * @param string value The value
         *
         * @return self For chainability
         */
        set: function (name, value) {
            var updateNavigation = false;
            if (params[name] !== undefined && params[name] !== value) {
                PMA_querywindow.refresh();
            }
            if (name == 'db' || name == 'table') {
                updateNavigation = true;
            }
            params[name] = value;
            if (updateNavigation) {
                PMA_showCurrentNavigation();
            }
            return this;
        },
        /**
         * Returns the url query string using the saved parameters
         *
         * @return string
         */
        getUrlQuery: function () {
            return $.sprintf(
                '?%s&server=%s&db=%s&table=%s',
                this.get('common_query'),
                encodeURIComponent(this.get('server')),
                encodeURIComponent(this.get('db')),
                encodeURIComponent(this.get('table'))
            );
        }
    };
})();

/**
 * Holds common parameters such as server, db, table, etc
 *
 * The content for this is normally loaded from Header.class.php or
 * Response.class.php and executed by ajax.js
 */
var PMA_commonActions = {
    /**
     * Saves the database name when it's changed
     * and reloads the query window, if necessary
     *
     * @param string new_db The name of the new database
     *
     * @return void
     */
    setDb: function (new_db) {
        if (new_db != PMA_commonParams.get('db')) {
            PMA_commonParams.setAll({'db': new_db, 'table': ''});
        }
    },
    /**
     * Opens a database in the main part of the page
     *
     * @param string new_db The name of the new database
     *
     * @return void
     */
    openDb: function (new_db) {
        PMA_commonParams
            .set('db', new_db)
            .set('table', '');
        PMA_querywindow.refresh();
        this.refreshMain(
            PMA_commonParams.get('opendb_url')
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
            url = $('#selflink a').attr('href');
            url = url.substring(0, url.indexOf('?'));
        }
        url += PMA_commonParams.getUrlQuery();
        $('<a />', {href: url})
            .appendTo('body')
            .click()
            .remove();
        AJAX._callback = callback;
    }
};

/**
 * Common functions used for communicating with the querywindow
 */
var PMA_querywindow = (function ($, window) {
    /**
     * @var Object querywindow Reference to the window
     *                         object of the querywindow
     * @access private
     */
    var querywindow = {};
    /**
     * @var string queryToLoad Stores the SQL query that is to be displayed
     *                         in the querywindow when it is ready
     * @access private
     */
    var queryToLoad = '';
    // The returned object is the public part of the module
    return {
        /**
         * Opens the query window
         *
         * @param mixed url Undefined to open the default page
         *                  String to go to a different
         *
         * @return void
         */
        open: function (url, sql_query) {
            if (! url) {
                url = 'querywindow.php' + PMA_commonParams.getUrlQuery();
            }
            if (sql_query) {
                url += '&sql_query=' + encodeURIComponent(sql_query);
            }

            if (! querywindow.closed && querywindow.location) {
                var href = querywindow.location.href;
                if (href != url &&
                    href != PMA_commonParams.get('pma_absolute_uri') + url
                ) {
                    if (PMA_commonParams.get('safari_browser')) {
                        querywindow.location.href = targeturl;
                    } else {
                        querywindow.location.replace(targeturl);
                    }
                    querywindow.focus();
                }
            } else {
                querywindow = window.open(
                    url + '&init=1',
                    '',
                    'toolbar=0,location=0,directories=0,status=1,' +
                    'menubar=0,scrollbars=yes,resizable=yes,' +
                    'width=' + PMA_commonParams.get('querywindow_width') + ',' +
                    'height=' + PMA_commonParams.get('querywindow_height')
                );
            }
            if (! querywindow.opener) {
                querywindow.opener = window.window;
            }
            if (window.focus) {
                querywindow.focus();
            }
        },
        /**
         * Opens, if necessary, focuses the query window
         * and displays an SQL query.
         *
         * @param string sql_query The SQL query to display in
         *                         the query window
         *
         * @return void
         */
        focus: function (sql_query) {
            if (! querywindow || querywindow.closed || ! querywindow.location) {
                // we need first to open the window and cannot pass the query with it
                // as we dont know if the query exceeds max url length
                queryToLoad = sql_query;
                this.open(false, sql_query);
            } else {
                //var querywindow = querywindow;
                var hiddenqueryform = querywindow
                    .document
                    .getElementById('hiddenqueryform');
                if (hiddenqueryform.querydisplay_tab != 'sql') {
                    hiddenqueryform.querydisplay_tab.value = "sql";
                    hiddenqueryform.sql_query.value = sql_query;
                    $(hiddenqueryform).addClass('disableAjax');
                    hiddenqueryform.submit();
                    querywindow.focus();
                } else {
                    querywindow.focus();
                }
            }
        },
        /**
         * Refreshes the query window given a url
         *
         * @param string url Where to go to
         *
         * @return void
         */
        refresh: function (url) {
            if (! querywindow.closed && querywindow.location) {
                var $form = $(querywindow.document).find('#sqlqueryform');
                if ($form.find('#checkbox_lock:checked').length === 0) {
                    PMA_querywindow.open(url);
                }
            }
        },
        /**
         * Reloads the query window given the details
         * of a db, a table and an sql_query
         *
         * @param string db        The name of the database
         * @param string table     The name of the table
         * @param string sql_query The SQL query to be displayed
         *
         * @return void
         */
        reload: function (db, table, sql_query) {
            if (! querywindow.closed && querywindow.location) {
                var $form = $(querywindow.document).find('#sqlqueryform');
                if ($form.find('#checkbox_lock:checked').length === 0) {
                    var $hiddenform = $(querywindow.document)
                        .find('#hiddenqueryform');
                    $hiddenform.find('input[name=db]').val(db);
                    $hiddenform.find('input[name=table]').val(table);
                    if (sql_query) {
                        $hiddenform.find('input[name=sql_query]').val(sql_query);
                    }
                    $hiddenform.addClass('disableAjax').submit();
                }
            }
        }
    };
})(jQuery, window);
