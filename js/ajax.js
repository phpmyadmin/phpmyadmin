/**
 * This object handles ajax requests for pages. It also
 * handles the reloading of the main menu and scripts (TODO: navigation).
 */
var AJAX = {
    /**
     * @var bool active Whether we are busy
     */
    active: false,
    /**
     * @var bool _debug Makes noise in your Firebug console
     */
    _debug: false,
    /**
     * Given the filename of a script, returns a string to be
     * used to refer to all the events registered for the file
     *
     * @param string key The filename for which to get the event name
     *
     * @return string
     */
    hash: function (key){
        /* http://burtleburtle.net/bob/hash/doobs.html#one */
        key += "";
        var len = key.length, hash=0, i=0;
        for (; i<len; ++i) {
            hash += key.charCodeAt(i);
            hash += (hash << 10);
            hash ^= (hash >> 6);
        }
        hash += (hash << 3);
        hash ^= (hash >> 11);
        hash += (hash << 15);
        return Math.abs(hash);
    },
    /**
     * Registers an onload event for a file
     *
     * @param string   file The filename for which to register the event
     * @param function func The function to execute when the page is ready
     *
     * @return self For chaining
     */
    registerOnload: function (file, func) {
        eventName = 'onload_' + AJAX.hash(file);
        $(document).bind(eventName, func);
        this._debug && console.log(
            "Registered event " + eventName + " for file " + file // no need to translate
        );
        return this;
    },
    /**
     * Registers a teardown event for a file. This is useful to execute functions
     * that unbind events for page elements that are about to be removed.
     *
     * @param string   file The filename for which to register the event
     * @param function func The function to execute when
     *                      the page is about to be torn down
     *
     * @return self For chaining
     */
    registerTeardown: function (file, func) {
        eventName = 'teardown_' + AJAX.hash(file);
        $(document).bind(eventName, func);
        this._debug && console.log(
            "Registered event " + eventName + " for file " + file // no need to translate
        );
        return this;
    },
    /**
     * Called when a page has finished loading, once for every
     * file that registered to the onload event of that file.
     *
     * @param string file The filename for which to fire the event
     *
     * @return void
     */
    fireOnload: function (file) {
        eventName = 'onload_' + AJAX.hash(file);
        $(document).trigger(eventName);
        this._debug && console.log(
            "Fired event " + eventName + " for file " + file // no need to translate
        );
    },
    /**
     * Called just before a page is torn down, once for every
     * file that registered to the teardown event of that file.
     *
     * @param string file The filename for which to fire the event
     *
     * @return void
     */
    fireTeardown: function (file) {
        eventName = 'teardown_' + AJAX.hash(file);
        $(document).trigger(eventName);
        this._debug && console.log(
            "Fired event " + eventName + " for file " + file // no need to translate
        );
    },
    /**
     * Event handler for clicks on links and form submissions
     *
     * @param eventData e
     *
     * @return void
     */
    requestHandler: function (e) {
        if ($(this).attr('target')) {
            return true;
        } else if ($(this).hasClass('ajax') || $(this).hasClass('disableAjax')) {
            return true;
        } else if ($(this).attr('href') && $(this).attr('href').match(/^#/)) {
            return true;
        } else if ($(this).attr('href') && $(this).attr('href').match(/^mailto/)) {
            return true;
        }

        e.preventDefault();
        e.stopImmediatePropagation();
        if (AJAX.active == true) {
            return false;
        } else {
            AJAX.active = true;
        }

        this._debug && console.log("Loading: " + url); // no need to translate
        this.$msgbox = PMA_ajaxShowMessage();
        $('html, body').animate({scrollTop: 0}, 'fast');

        var isLink = !! $(this).attr('href') || false;
        var url = isLink ? $(this).attr('href') : $(this).attr('action');
        var params = 'ajax_request=true&ajax_page_request=true';
        if (! isLink) {
            params += '&' + $(this).serialize();
        }

        if (isLink) {
            $.get(url, params, AJAX.responseHandler);
        } else {
            $.post(url, params, AJAX.responseHandler);
        }
    },

    $msgbox: null,

    responseHandler: function (data) {
        if (data.success) {
            $table_clone = false;
            PMA_ajaxRemoveMessage(AJAX.$msgbox);

            if (data._redirect) {
                PMA_ajaxShowMessage(data._redirect, false);
                AJAX.active = false;
                return;
            }

            AJAX.scriptHandler.reset();

            if (data._reloadNavigation) {
                PMA_reloadNavigation();
            }
            if (data._reloadQuerywindow) {
                var params = data._reloadQuerywindow;
                PMA_querywindow.reload(
                    params.db,
                    params.table,
                    params.sql_query
                );
            }

            if (data._focusQuerywindow) {
                PMA_querywindow.focus(
                    data._focusQuerywindow
                );
            }

            if (data._title) {
                $('title').replaceWith(data._title);
            }

            if (data._menu) {
                $('#floating_menubar').html(data._menu)
                    .children().first().remove(); // Remove duplicate wrapper (TODO: don't send it in the response)
                menuPrepare();
                menuResize();
            }

            $('body').children().not('#pma_navigation').not('#floating_menubar').not('#page_content').not('#selflink').remove();
            $('#page_content').replaceWith("<div id='page_content'>" + data.message + "</div>");
            if (data.sql_query) {
                var $sql = $(data.sql_query);
                $sql
                    .find('div.error, div.success, div.notice')
                    .remove();
                $('#page_content').append($sql);
            }

            if (data._selflink) {
                $('#selflink > a').attr('href', data._selflink);
            }

            if (data._scripts) {
                AJAX.scriptHandler.load(data._scripts, 1);
            }

            if (data._params) {
                PMA_commonParams.setAll(data._params);
            }
            $('#pma_errors').remove();
            if (data._errors) {
                $('<div/>', {id:'pma_errors'})
                    .insertAfter('#selflink')
                    .append(data._errors);
            }
        } else {
            PMA_ajaxShowMessage(data.error, false);
            AJAX.active = false;
        }
    },
    /**
     * This object is in charge of downloading scripts,
     * keeping track of what's downloaded and firing
     * the onload event for them when the page is ready.
     */
    scriptHandler: {
        /**
         * @var array _scripts The list of files already downloaded
         */
        _scripts: [],
        /**
         * @var array _scriptsToBeLoaded The list of files that
         *                               need to be downloaded
         */
        _scriptsToBeLoaded: [],
        /**
         * @var array _scriptsToBeFired The list of files for which
         *                              to fire the onload event
         */
        _scriptsToBeFired: [],
        /**
         * Records that a file has been downloaded
         *
         * @param string file The filename
         * @param string fire Whether this file will be registering
         *                    onload/teardown events
         *
         * @return self For chaining
         */
        add: function (file, fire) {
            this._scripts.push(file);
            if (fire) {
                // Record whether to fire any events for the file
                // This is necessary to correctly tear down the initial page
                this._scriptsToBeFired.push(file);
            }
            return this;
        },
        /**
         * Queues up an array of files to be downloaded
         *
         * @param array files An array of filenames and flags
         *
         * @return void
         */
        load: function (files, reset) {
            if (reset) {
                this._scriptsToBeLoaded = [];
                this._scriptsToBeFired = [];
            }
            for (var i in files) {
                this._scriptsToBeLoaded.push(files[i].name);
                if (files[i].fire) {
                    this._scriptsToBeFired.push(files[i].name);
                }
            }
            this.callback();
        },
        /**
         * Called whenever a file is loaded.
         * Will queue up another file, or call done();
         *
         * @return void
         */
        callback: function () {
            var scripts = this._scriptsToBeLoaded;
            if (scripts.length > 0) {
                var script = scripts.shift();
                if ($.inArray(script, this._scripts) == -1) {
                    this.add(script);
                    var self = this;
                    $.getScript('js/' + script, function () {
                        self.callback();
                    });
                } else {
                   this.callback();
                }
            } else {
                this.done();
            }
        },
        /**
         * Called whenever all files are loaded
         *
         * @return void
         */
        done: function () {
            for (var i in this._scriptsToBeFired) {
                AJAX.fireOnload(this._scriptsToBeFired[i]);
            }
            AJAX.active = false;
        },
        reset: function () {
            for (var i in this._scriptsToBeFired) {
                AJAX.fireTeardown(this._scriptsToBeFired[i]);
            }
            this._scriptsToBeFired = [];
            /**
             * Re-attach a generic event handler to clicks
             * on pages and submissions of forms
             */
            $('a').die('click').live('click', AJAX.requestHandler);
            $('form').die('submit').live('submit', AJAX.requestHandler);
        }
    }
};
/**
 * Attach a generic event handler to clicks
 * on pages and submissions of forms
 */
$('a').live('click', AJAX.requestHandler);
$('form').live('submit', AJAX.requestHandler);
