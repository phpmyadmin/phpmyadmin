import CommonParams from '../variables/common_params';
import { AJAX } from '../ajax';

/**
 * Holds common parameters such as server, db, table, etc
 *
 * The content for this is normally loaded from Header.php or
 * Response.php and executed by ajax.js
 */
export var PMA_commonActions = {
    /**
     * Saves the database name when it's changed
     * and reloads the query window, if necessary
     *
     * @param {string} newDb newDb The name of the new database
     *
     * @return {void}
     */
    setDb: function (newDb) {
        if (newDb !== CommonParams.get('db')) {
            CommonParams.setAll({ 'db': newDb, 'table': '' });
        }
    },
    /**
     * Opens a database in the main part of the page
     *
     * @param {string} newDb The name of the new database
     *
     * @return void
     */
    openDb: function (newDb) {
        CommonParams
            .set('db', newDb)
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
        $('<a />', { href: url })
            .appendTo('body')
            .trigger('click')
            .remove();
        AJAX._callback = callback;
    }
};
