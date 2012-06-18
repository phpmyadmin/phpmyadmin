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
    getEventName: function (key){
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
        return "onload_" + Math.abs(hash);
    },
    /**
     * Registers an onload event for a file
     *
     * @param string   file The filename for which to register the event
     * @param function func The function to execute when the page is ready
     *
     * @return void
     */
    registerOnload: function (file, func) {
        eventName = AJAX.getEventName(file);
        $(document).bind(eventName, func);
        this._debug && console.log("Registered event " + eventName + " for file " + file); // no need to translate
    },
    /**
     * Called when a page has finished loading, once for every
     * file that registered to the onload event of that file.
     *
     * @param string   file The filename for which to fire the event
     *
     * @return void
     */
    fireOnload: function (file) {
        eventName = AJAX.getEventName(file);
        $(document).trigger(eventName);
        this._debug && console.log("Fired event " + eventName + " for file " + file); // no need to translate
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
        } else {
            e.preventDefault();
            e.stopImmediatePropagation();
            if (AJAX.active == true) {
                return false;
            } else {
                AJAX.active = true;
            }
        }

        var url = $(this).attr('href') || $(this).attr('action') + '?' + $(this).serialize();
        this._debug && console.log("Loading: " + url); // no need to translate
        var $msgbox = PMA_ajaxShowMessage();
        $('html, body').animate({scrollTop: 0}, 'fast');
        $.get(url, {'ajax_request': true, 'ajax_page_request': true}, function (data) {
            if (data.success) {
                PMA_ajaxRemoveMessage($msgbox);

                if (data._menu) {
                    $('#floating_menubar').html(data._menu)
                        .children().first().remove(); // Remove duplicate wrapper (TODO: don't send it in the response)
                    menuPrepare();
                    menuResize();
                }

                $('*').unbind().die();

                $('body').children().not('#floating_menubar').not('#page_content').not('#selflink').remove();

                $('#page_content').replaceWith("<div id='page_content'>" + data.message + "</div>");

                if (data._scripts) {
                    AJAX.scriptHandler.load(data._scripts);
                }
            } else {
                PMA_ajaxShowMessage(data.error, false);
                AJAX.active = false;
            }
        });
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
         *
         * @return self For chaining
         */
        add: function (file) {
            this._scripts.push(file);
            return this;
        },
        /**
         * Queues up an array of files to be downloaded
         *
         * @param array files An array of filenames and flags
         *
         * @return void
         */
        load: function (files) {
            this._scriptsToBeLoaded = [];
            this._scriptsToBeFired = [];
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
                if (this._scripts.indexOf(script) === -1) {
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
        }
    }
};

/**
 * Attach a generic event handler to clicks
 * on pages and submissions of forms
 */
$('a').live('click', AJAX.requestHandler);
$('form').live('submit', AJAX.requestHandler);

