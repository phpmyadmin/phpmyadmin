import { PMA_ajaxShowMessage } from '../utils/show_ajax_messages';
import { AJAX } from '../ajax';
import { PMA_Messages as messages } from '../variables/export_variables';
import CommonParams from '../variables/common_params';
import SetUrlHash from './SetUrlHash';
/**
 * An implementation of a client-side page cache.
 * This object also uses the cache to provide a simple microhistory,
 * that is the ability to use the back and forward buttons in the browser
 */
var MicroHistory = {
    /**
     * @var int The maximum number of pages to keep in the cache
     */
    MAX: 6,
    /**
     * @var object A hash used to prime the cache with data about the initially
     *             loaded page. This is set in the footer, and then loaded
     *             by a double-queued event further down this file.
     */
    primer: {},
    /**
     * @var array Stores the content of the cached pages
     */
    pages: [],
    /**
     * @var int The index of the currently loaded page
     *          This is used to know at which point in the history we are
     */
    current: 0,
    /**
     * Saves a new page in the cache
     *
     * @param string hash    The hash part of the url that is being loaded
     * @param array  scripts A list of scripts that is required for the page
     * @param string menu    A hash that links to a menu stored
     *                       in a dedicated menu cache
     * @param array  params  A list of parameters used by CommonParams()
     * @param string rel     A relationship to the current page:
     *                       'samepage': Forces the response to be treated as
     *                                   the same page as the current one
     *                       'newpage':  Forces the response to be treated as
     *                                   a new page
     *                       undefined:  Default behaviour, 'samepage' if the
     *                                   selflinks of the two pages are the same.
     *                                   'newpage' otherwise
     *
     * @return void
     */
    add: function (hash, scripts, menu, params, rel) {
        if (this.pages.length > MicroHistory.MAX) {
            // Trim the cache, to the maximum number of allowed entries
            // This way we will have a cached menu for every page
            for (var i = 0; i < this.pages.length - this.MAX; i++) {
                delete this.pages[i];
            }
        }
        while (this.current < this.pages.length) {
            // trim the cache if we went back in the history
            // and are now going forward again
            this.pages.pop();
        }
        if (rel === 'newpage' ||
            (
                typeof rel === 'undefined' && (
                    typeof this.pages[this.current - 1] === 'undefined' ||
                    this.pages[this.current - 1].hash !== hash
                )
            )
        ) {
            this.pages.push({
                hash: hash,
                content: $('#page_content').html(),
                scripts: scripts,
                selflink: $('#selflink').html(),
                menu: menu,
                params: params
            });
            SetUrlHash(this.current, hash);
            this.current++;
        }
    },
    /**
     * Restores a page from the cache. This is called when the hash
     * part of the url changes and it's structure appears to be valid
     *
     * @param string index Which page from the history to load
     *
     * @return void
     */
    navigate: function (index) {
        if (typeof this.pages[index] === 'undefined' ||
            typeof this.pages[index].content === 'undefined' ||
            typeof this.pages[index].menu === 'undefined' ||
            ! MicroHistory.menus.get(this.pages[index].menu)
        ) {
            PMA_ajaxShowMessage(
                '<div class="error">' + messages.strInvalidPage + '</div>',
                false
            );
        } else {
            AJAX.active = true;
            var record = this.pages[index];
            AJAX.scriptHandler.reset(function () {
                $('#page_content').html(record.content);
                $('#selflink').html(record.selflink);
                MicroHistory.menus.replace(MicroHistory.menus.get(record.menu));
                CommonParams.setAll(record.params);
                AJAX.scriptHandler.load(record.scripts);
                MicroHistory.current = ++index;
            });
        }
    },
    /**
     * Resaves the content of the current page in the cache.
     * Necessary in order not to show the user some outdated version of the page
     *
     * @return void
     */
    update: function () {
        var page = this.pages[this.current - 1];
        if (page) {
            page.content = $('#page_content').html();
        }
    },
    /**
     * @var object Dedicated menu cache
     */
    menus: {
        /**
         * Returns the number of items in an associative array
         *
         * @return int
         */
        size: function (obj) {
            var size = 0;
            var key;
            for (key in obj) {
                if (obj.hasOwnProperty(key)) {
                    size++;
                }
            }
            return size;
        },
        /**
         * @var hash Stores the content of the cached menus
         */
        data: {},
        /**
         * Saves a new menu in the cache
         *
         * @param string hash    The hash (trimmed md5) of the menu to be saved
         * @param string content The HTML code of the menu to be saved
         *
         * @return void
         */
        add: function (hash, content) {
            if (this.size(this.data) > MicroHistory.MAX) {
                // when the cache grows, we remove the oldest entry
                var oldest;
                var key;
                var init = 0;
                for (var i in this.data) {
                    if (this.data[i]) {
                        if (! init || this.data[i].timestamp.getTime() < oldest.getTime()) {
                            oldest = this.data[i].timestamp;
                            key = i;
                            init = 1;
                        }
                    }
                }
                delete this.data[key];
            }
            this.data[hash] = {
                content: content,
                timestamp: new Date()
            };
        },
        /**
         * Retrieves a menu given its hash
         *
         * @param string hash The hash of the menu to be retrieved
         *
         * @return string
         */
        get: function (hash) {
            if (this.data[hash]) {
                return this.data[hash].content;
            } else {
                // This should never happen as long as the number of stored menus
                // is larger or equal to the number of pages in the page cache
                return '';
            }
        },
        /**
         * Prepares part of the parameter string used during page requests,
         * this is necessary to tell the server which menus we have in the cache
         *
         * @return string
         */
        getRequestParam: function () {
            var param = '';
            var menuHashes = [];
            for (var i in this.data) {
                menuHashes.push(i);
            }
            var menuHashesParam = menuHashes.join('-');
            if (menuHashesParam) {
                param = CommonParams.get('arg_separator') + 'menuHashes=' + menuHashesParam;
            }
            return param;
        },
        /**
         * Replaces the menu with new content
         *
         * @return void
         */
        replace: function (content) {
            $('#floating_menubar').html(content)
                // Remove duplicate wrapper
                // TODO: don't send it in the response
                .children().first().remove();
            $('#topmenu').menuResizer(PMA_mainMenuResizerCallback);
        }
    }
};

export default MicroHistory;
