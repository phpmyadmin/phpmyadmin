/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * function used in or for navigation panel
 *
 * @package phpMyAdmin-Navigation
 */

/**
 * Executed on page load
 */
$(function() {
    if (! $('#pma_navigation').length) {
        // Don't bother running any code if the navigation is not even on the page
        return;
    }

    // Do not let the page reload on submitting the fast filter
    $(document).on('submit', '.fast_filter', function(event) {
        event.preventDefault();
    });

    // Fire up the resize handlers
    new ResizeHandler();

    /**
     * opens/closes (hides/shows) tree elements
     * loads data via ajax
     */
    $('#pma_navigation_tree a.expander').live('click', function(event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        var $this = $(this);
        var $children = $this.closest('li').children('div.list_container');
        var $icon = $this.find('img');
        if ($this.hasClass('loaded')) {
            if ($icon.is('.ic_b_plus')) {
                $icon.removeClass('ic_b_plus').addClass('ic_b_minus');
                $children.show('fast');
            } else {
                $icon.removeClass('ic_b_minus').addClass('ic_b_plus');
                $children.hide('fast');
            }
        } else {
            var $destination = $this.closest('li');
            var $throbber = $('#pma_navigation .throbber')
                .first()
                .clone()
                .css('visibility', 'visible');
            $icon.hide();
            $throbber.insertBefore($icon);

            var searchClause = PMA_fastFilter.getSearchClause();
            var searchClause2 = PMA_fastFilter.getSearchClause2($(this));

            var params = {
                aPath: $(this).find('span.aPath').text(),
                vPath: $(this).find('span.vPath').text(),
                pos: $(this).find('span.pos').text(),
                pos2_name: $(this).find('span.pos2_name').text(),
                pos2_value: $(this).find('span.pos2_value').text(),
                searchClause: searchClause,
                searchClause2: searchClause2
            };
            var url = $('#pma_navigation').find('a.navigation_url').attr('href');
            $.get(url, params, function (data) {
                if (data.success === true) {
                    $this.addClass('loaded');
                    $destination.find('div.list_container').remove(); // FIXME: Hack, there shouldn't be a list container there
                    $destination.append(data.message);
                    $icon.removeClass('ic_b_plus').addClass('ic_b_minus');
                    $destination
                        .children('div.list_container')
                        .show('fast');
                    if ($destination.find('ul > li').length == 1) {
                        $destination.find('ul > li')
                            .find('a.expander.container')
                            .click();
                    }
                } else {
                    PMA_ajaxShowMessage(data.error, false);
                }
                $icon.show();
                $throbber.remove();
            });
        }
        $(this).blur();
    });

    /**
     * Register event handler for click on the reload
     * navigation icon at the top of the panel
     */
    $('#pma_navigation_reload').live('click', function (event) {
        event.preventDefault();
        PMA_reloadNavigation();
    });

    /**
     * Bind all "fast filter" events
     */
    $('#pma_navigation_tree li.fast_filter span')
        .live('click', PMA_fastFilter.events.clear);
    $('#pma_navigation_tree li.fast_filter input.searchClause')
        .live('focus', PMA_fastFilter.events.focus)
        .live('blur', PMA_fastFilter.events.blur)
        .live('keyup', PMA_fastFilter.events.keyup);

    /**
     * Ajax handler for pagination
     */
    $('#pma_navigation_tree div.pageselector a.ajax').live('click', function (event) {
        event.preventDefault();
        PMA_navigationTreePagination($(this));
    });

    /**
     * Node highlighting
     */
    $('#pma_navigation_tree.highlight li:not(.fast_filter)').live(
        'mouseover',
        function () {
            if ($('li:visible', this).length == 0) {
                $(this).addClass('activePointer');
            }
        }
    );
    $('#pma_navigation_tree.highlight li:not(.fast_filter)').live(
        'mouseout',
        function () {
            $(this).removeClass('activePointer');
        }
    );

    /**
     * Jump to recent table
     */
    $('#recentTable').live('change', function() {
        if (this.value != '') {
            var arr = jQuery.parseJSON(this.value);
            var $form = $(this).closest('form');
            $form.find('input[name=db]').val(arr['db']);
            $form.find('input[name=table]').val(arr['table']);
            $form.submit();
        }
    });

    /** Create a Routine, Trigger or Event */
    $('li.new_procedure a.ajax, li.new_function a.ajax').live('click', function (event) {
        event.preventDefault();
        var dialog = new RTE.object('routine');
        dialog.editorDialog(1, $(this))
    });
    $('li.new_trigger a.ajax').live('click', function (event) {
        event.preventDefault();
        var dialog = new RTE.object('trigger');
        dialog.editorDialog(1, $(this))
    });
    $('li.new_event a.ajax').live('click', function (event) {
        event.preventDefault();
        var dialog = new RTE.object('event');
        dialog.editorDialog(1, $(this))
    });

    /** Edit Routines, Triggers and Events */
    $('li.procedure > a.ajax, li.function > a.ajax').live('click', function (event) {
        event.preventDefault();
        var dialog = new RTE.object('routine');
        dialog.editorDialog(0, $(this))
    });
    $('li.trigger > a.ajax').live('click', function (event) {
        event.preventDefault();
        var dialog = new RTE.object('trigger');
        dialog.editorDialog(0, $(this))
    });
    $('li.event > a.ajax').live('click', function (event) {
        event.preventDefault();
        var dialog = new RTE.object('event');
        dialog.editorDialog(0, $(this))
    });

    /** Export Routines, Triggers and Events */
    $('li.procedure a.ajax img, li.function a.ajax img, li.trigger a.ajax img, li.event a.ajax img').live('click', function (event) {
        event.preventDefault();
        var dialog = new RTE.object();
        dialog.exportDialog($(this).parent())
    });

    /** New index */
    $('li.new_index a.ajax').live('click', function (event) {
        event.preventDefault();
        var url = $(this).attr('href').substr(
            $(this).attr('href').indexOf('?') + 1
        ) + '&ajax_request=true';
        var title = PMA_messages['strAddIndex'];
        indexEditorDialog(url, title);
    });

    /** Edit index */
    $('li.index a.ajax').live('click', function (event) {
        event.preventDefault();
        var url = $(this).attr('href').substr(
            $(this).attr('href').indexOf('?') + 1
        ) + '&ajax_request=true';
        var title = PMA_messages['strEditIndex'];
        indexEditorDialog(url, title);
    });

    /** New view */
    $('li.new_view a.ajax').live('click', function (event) {
        event.preventDefault();
        PMA_createViewDialog($(this));
    });
});

/**
 * Reloads the whole navigation tree while preserving its state
 *
 * @param  function     the callback function 
 * @return void
 */
function PMA_reloadNavigation(callback) {
    var $throbber = $('#pma_navigation .throbber')
        .first()
        .css('visibility', 'visible');
    var params = {
        reload: true,
        pos: $('#pma_navigation_tree').find('a.expander:first > span.pos').text()
    };
    // Traverse the navigation tree backwards to generate all the actual
    // and virtual paths, as well as the positions in the pagination at
    // various levels, if necessary.
    var count = 0;
    $('#pma_navigation_tree').find('a.expander:visible').each(function () {
        if ($(this).find('img').is('.ic_b_minus')
            && $(this).closest('li').find('div.list_container .ic_b_minus').length == 0
        ) {
            params['n' + count + '_aPath'] = $(this).find('span.aPath').text();
            params['n' + count + '_vPath'] = $(this).find('span.vPath').text();

            var pos2_name = $(this).find('span.pos2_name').text();
            if (! pos2_name) {
                pos2_name = $(this)
                    .parent()
                    .parent()
                    .find('span.pos2_name:last')
                    .text();
            }
            var pos2_value = $(this).find('span.pos2_value').text();
            if (! pos2_value) {
                pos2_value = $(this)
                    .parent()
                    .parent()
                    .find('span.pos2_value:last')
                    .text();
            }

            params['n' + count + '_pos2_name'] = pos2_name;
            params['n' + count + '_pos2_value'] = pos2_value;

            params['n' + count + '_pos3_name'] = $(this).find('span.pos3_name').text();
            params['n' + count + '_pos3_value'] = $(this).find('span.pos3_value').text();
            count++;
        }
    });
    var url = $('#pma_navigation').find('a.navigation_url').attr('href');
    $.post(url, params, function (data) {
        $throbber.css('visibility', 'hidden');
        if (data.success) {
            $('#pma_navigation_tree').html(data.message).children('div').show();
            // Fire the callback, if any
            if (typeof callback === 'function') {
                callback.call();
            }
        } else {
            PMA_ajaxShowMessage(data.error);
        }
    });
}

/**
 * Handles any requests to change the page in a branch of a tree
 *
 * This can be called from link click or select change event handlers
 *
 * @param object $this A jQuery object that points to the element that
 * initiated the action of changing the page
 *
 * @return void
 */
function PMA_navigationTreePagination($this)
{
    var $msgbox = PMA_ajaxShowMessage();
    var isDbSelector = $this.closest('div.pageselector').is('.dbselector');
    if ($this[0].tagName == 'A') {
        var url = $this.attr('href');
        var params = 'ajax_request=true';
    } else { // tagName == 'SELECT'
        var url = 'navigation.php';
        var params = $this.closest("form").serialize() + '&ajax_request=true';
    }
    var searchClause = PMA_fastFilter.getSearchClause();
    if (searchClause) {
        params += '&searchClause=' + encodeURIComponent(searchClause);
    }
    if (isDbSelector) {
        params += '&full=true';
    } else {
        var searchClause2 = PMA_fastFilter.getSearchClause2($this);
        if (searchClause2) {
            params += '&searchClause2=' + encodeURIComponent(searchClause2);
        }
    }
    $.post(url, params, function (data) {
        PMA_ajaxRemoveMessage($msgbox);
        if (data.success) {
            if (isDbSelector) {
                var val = PMA_fastFilter.getSearchClause();
                $('#pma_navigation_tree')
                    .html(data.message)
                    .children('div')
                    .show();
                if (val) {
                    $('#pma_navigation_tree')
                        .find('li.fast_filter input.searchClause')
                        .val(val);
                }
            } else {
                var $parent = $this.closest('div.list_container').parent();
                var val = PMA_fastFilter.getSearchClause2($this);
                $this.closest('div.list_container').html(
                    $(data.message).children().show()
                );
                if (val) {
                    $parent.find('li.fast_filter input.searchClause').val(val);
                }
                $parent.find('span.pos2_value:first').text(
                    $parent.find('span.pos2_value:last').text()
                );
                $parent.find('span.pos3_value:first').text(
                    $parent.find('span.pos3_value:last').text()
                );
            }
        } else {
            PMA_ajaxShowMessage(data.error);
        }
    });
}

/**
 * @var ResizeHandler Custom object that manages the resizing of the navigation
 *
 * XXX: Must only be ever instanciated once
 * XXX: Inside event handlers the 'this' object is accessed as 'event.data.resize_handler'
 */
var ResizeHandler = function () {
    /**
     * Whether the user has initiated a resize operation
     */
    this.active = false;
    /**
     * @var int panel_width Used by the collapser to know where to go
     *                      back to when uncollapsing the panel
     */
    this.panel_width = 0;
    /**
     * @var string left Used to provide support for RTL languages
     */
    this.left = $('html').attr('dir') == 'ltr' ? 'left' : 'right';
    /**
     * Adjusts the width of the navigation panel to the specified value
     *
     * @param int pos Navigation width in pixels
     *
     * @return void
     */
    this.setWidth = function (pos) {
        var $resizer = $('#pma_navigation_resizer');
        var resizer_width = $resizer.width();
        var $collapser = $('#pma_navigation_collapser');
        $('#pma_navigation').width(pos);
        $('body').css('margin-' + this.left, pos + 'px');
        $("#floating_menubar")
            .css('margin-' + this.left, (pos + resizer_width) + 'px');
        $resizer.css(this.left, pos + 'px');
        if (pos === 0) {
            $collapser
                .css(this.left, pos + resizer_width)
                .html(this.getSymbol(pos))
                .prop('title', PMA_messages['strShowPanel']);
        } else {
            $collapser
                .css(this.left, pos)
                .html(this.getSymbol(pos))
                .prop('title', PMA_messages['strHidePanel']);
        }
        setTimeout(function (){
            $(window).trigger('resize');
        }, 4);
    };
    /**
     * Returns the horizontal position of the mouse,
     * relative to the outer side of the navigation panel
     *
     * @param int pos Navigation width in pixels
     *
     * @return void
     */
    this.getPos = function (event) {
        var pos = event.pageX;
        var windowWidth = $(window).width();
        if (this.left != 'left') {
            pos = windowWidth - event.pageX;
        }
        if (pos < 0) {
            pos = 0;
        } else if (pos + 100 >= windowWidth) {
            pos = windowWidth - 100;
        } else {
            this.panel_width = 0;
        }
        return pos;
    };
    /**
     * Returns the HTML code for the arrow symbol used in the collapser
     *
     * @param int width The width of the panel
     *
     * @return string
     */
    this.getSymbol = function (width) {
        if (this.left == 'left') {
            if (width == 0) {
                return '&rarr;';
            } else {
                return '&larr;';
            }
        } else {
            if (width == 0) {
                return '&larr;';
            } else {
                return '&rarr;';
            }
        }
    };
    /**
     * Event handler for initiating a resize of the panel
     *
     * @param object e Event data (contains a reference to resizeHandler)
     *
     * @return void
     */
    this.mousedown = function (event) {
        event.preventDefault();
        event.data.resize_handler.active = true;
        $('body').css('cursor', 'col-resize');
    };
    /**
     * Event handler for terminating a resize of the panel
     *
     * @param object e Event data (contains a reference to resizeHandler)
     *
     * @return void
     */
    this.mouseup = function (event) {
        if (event.data.resize_handler.active) {
            event.data.resize_handler.active = false;
            $('body').css('cursor', '');
            $.cookie('pma_navi_width', event.data.resize_handler.getPos(event));
            $('#topmenu').menuResizer('resize');
        }
    };
    /**
     * Event handler for updating the panel during a resize operation
     *
     * @param object e Event data (contains a reference to resizeHandler)
     *
     * @return void
     */
    this.mousemove = function (event) {
        if (event.data && event.data.resize_handler && event.data.resize_handler.active) {
            event.preventDefault();
            var pos = event.data.resize_handler.getPos(event);
            event.data.resize_handler.setWidth(pos);
        }
    };
    /**
     * Event handler for collapsing the panel
     *
     * @param object e Event data (contains a reference to resizeHandler)
     *
     * @return void
     */
    this.collapse = function (event) {
        event.preventDefault();
        event.data.active = false;
        var panel_width = event.data.resize_handler.panel_width;
        var width = $('#pma_navigation').width();
        if (width === 0 && panel_width === 0) {
            panel_width = 240;
        }
        event.data.resize_handler.setWidth(panel_width);
        event.data.resize_handler.panel_width = width;
    };
    /* Initialisation section begins here */
    if ($.cookie('pma_navi_width')) {
        // If we have a cookie, set the width of the panel to its value
        var pos = Math.abs(parseInt($.cookie('pma_navi_width'), 10) || 0);
        this.setWidth(pos);
        $('#topmenu').menuResizer('resize');
    }
    // Register the events for the resizer and the collapser
    $('#pma_navigation_resizer')
        .live('mousedown', {'resize_handler':this}, this.mousedown);
    $(document)
        .bind('mouseup', {'resize_handler':this}, this.mouseup)
        .bind('mousemove', {'resize_handler':this}, $.throttle(this.mousemove, 4));
    var $collapser = $('#pma_navigation_collapser');
    $collapser.live('click', {'resize_handler':this}, this.collapse);
    // Add the correct arrow symbol to the collapser
    $collapser.html(this.getSymbol($('#pma_navigation').width()));
}; // End of ResizeHandler

/**
 * @var object PMA_fastFilter Handles the functionality that allows filtering
 *                            of the items in a branch of the navigation tree
 */
var PMA_fastFilter = {
    /**
     * Construct for the asynchronous fast filter functionality
     *
     * @param object $this        A jQuery object pointing to the list container
     *                            which is the nearest parent of the fast filter
     * @param string searchClause The query string for the filter
     *
     * @return new PMA_fastFilter.filter object
     */
    filter: function ($this, searchClause) {
        /**
         * @var object $this A jQuery object pointing to the list container
         *                   which is the nearest parent of the fast filter
         */
        this.$this = $this;
        /**
         * @var bool searchClause The query string for the filter
         */
        this.searchClause = searchClause;
        /**
         * @var object $clone A clone of the original contents
         *                    of the navigation branch before
         *                    the fast filter was applied
         */
        this.$clone = $this.clone();
        /**
         * @var bool swapped Whether the user clicked on the "N other results" link
         */
        this.swapped = false;
        /**
         * @var object xhr A reference to the ajax request that is currently running
         */
        this.xhr = null;
        /**
         * @var int timeout Used to delay the request for asynchronous search
         */
        this.timeout = null;

        var $filterInput = $this.find('li.fast_filter input.searchClause');
        if (   $filterInput.length != 0
            && $filterInput.val() != ''
            && $filterInput.val() != $filterInput[0].defaultValue
        ) {
            this.request();
        }
    },
    /**
     * Gets the query string from the database fast filter form
     *
     * @return string
     */
    getSearchClause: function () {
        var retval = '';
        var $input = $('#pma_navigation_tree')
            .find('li.fast_filter.db_fast_filter input.searchClause');
        if ($input.length && $input.val() != $input[0].defaultValue) {
            retval = $input.val();
        }
        return retval;
    },
    /**
     * Gets the query string from a second level item's fast filter form
     * The retrieval is done by trasversing the navigation tree backwards
     *
     * @return string
     */
    getSearchClause2: function ($this) {
        var $filterContainer = $this.closest('div.list_container');
        var $filterInput = $([]);
        while (1) {
            if ($filterContainer.find('li.fast_filter:not(.db_fast_filter) input.searchClause').length != 0) {
                $filterInput = $filterContainer.find('li.fast_filter:not(.db_fast_filter) input.searchClause');
                break;
            } else if (! $filterContainer.is('div.list_container')) {
                break;
            }
            $filterContainer = $filterContainer
                .parent()
                .closest('div.list_container');
        }
        var searchClause2 = '';
        if ($filterInput.length != 0
            && $filterInput.first().val() != $filterInput[0].defaultValue
        ) {
            searchClause2 = $filterInput.val();
        }
        return searchClause2;
    },
    /**
     * @var hash events A list of functions that are bound to DOM events
     *                  at the top of this file
     */
    events: {
        focus: function (event) {
            var $obj = $(this).closest('div.list_container');
            if (! $obj.data('fastFilter')) {
                $obj.data(
                    'fastFilter',
                    new PMA_fastFilter.filter($obj, $(this).val())
                );
            }
            if ($(this).val() == this.defaultValue) {
                $(this).val('');
            } else {
                $(this).select();
            }
        },
        blur: function (event) {
            if ($(this).val() == '') {
                $(this).val(this.defaultValue);
            }
            var $obj = $(this).closest('div.list_container');
            if ($(this).val() == this.defaultValue && $obj.data('fastFilter')) {
                $obj.data('fastFilter').restore();
            }
        },
        keyup: function (event) {
            var $obj = $(this).closest('div.list_container');
            var str = '';
            if ($(this).val() != this.defaultValue && $(this).val() != '') {
                $obj.find('div.pageselector').hide();
                str = $(this).val().toLowerCase();
            }
            $obj.find('li > a').not('.container').each(function () {
                if ($(this).text().toLowerCase().indexOf(str) != -1) {
                    $(this).parent().show().removeClass('hidden');
                } else {
                    $(this).parent().hide().addClass('hidden');
                }
            });
            var container_filter = function ($curr, str) {
                $curr.children('li').children('a.container').each(function () {
                    var $group = $(this).parent().children('ul');
                    if ($group.children('li').children('a.container').length > 0) {
                        container_filter($group); // recursive
                    }
                    $group.parent().show().removeClass('hidden');
                    if ($group.children().not('.hidden').length == 0) {
                        $group.parent().hide().addClass('hidden');
                    }
                });
            };
            container_filter($obj, str);
            if ($(this).val() != this.defaultValue && $(this).val() != '') {
                if (! $obj.data('fastFilter')) {
                    $obj.data(
                        'fastFilter',
                        new PMA_fastFilter.filter($obj, $(this).val())
                    );
                } else {
                    $obj.data('fastFilter').update($(this).val());
                }
            } else if ($obj.data('fastFilter')) {
                $obj.data('fastFilter').restore(true);
            }
        },
        clear: function (event) {
            event.stopPropagation();
            // Clear the input and apply the fast filter with empty input
            var filter = $(this).closest('div.list_container').data('fastFilter');
            if (filter) {
                filter.restore();
            }
            var value = $(this).prev()[0].defaultValue;
            $(this).prev().val(value).trigger('keyup');
        }
    }
};
/**
 * Handles a change in the search clause
 *
 * @param string searchClause The query string for the filter
 *
 * @return void
 */
PMA_fastFilter.filter.prototype.update = function (searchClause)
{
    if (this.searchClause != searchClause) {
        this.searchClause = searchClause;
        this.$this.find('.moreResults').remove();
        this.request();
    }
};
/**
 * After a delay of 250mS, initiates a request to retrieve search results
 * Multiple calls to this function will always abort the previous request
 *
 * @return void
 */
PMA_fastFilter.filter.prototype.request = function ()
{
    var self = this;
    clearTimeout(self.timeout);
    if (self.$this.find('li.fast_filter').find('img.throbber').length == 0) {
        self.$this.find('li.fast_filter').append(
            $('<div class="throbber"></div>').append(
                $('#pma_navigation_content')
                    .find('img.throbber')
                    .clone()
                    .css('visibility', 'visible')
            )
        );
    }
    self.timeout = setTimeout(function () {
        if (self.xhr) {
            self.xhr.abort();
        }
        var url = $('#pma_navigation').find('a.navigation_url').attr('href');
        var results = self.$this.find('li:not(.hidden):not(.fast_filter):not(.navGroup)').not('[class^=new]').length;
        var params = self.$this.find('> ul > li > form.fast_filter').first().serialize() + "&results=" + results;
        if (self.$this.find('> ul > li > form.fast_filter:first input[name=searchClause]').length == 0) {
            var $input = $('#pma_navigation_tree').find('li.fast_filter.db_fast_filter input.searchClause');
            if ($input.length && $input.val() != $input[0].defaultValue) {
                params += '&searchClause=' + encodeURIComponent($input.val());
            }
        }
        self.xhr = $.ajax({
            url: url,
            type: 'post',
            dataType: 'json',
            data: params,
            complete: function (jqXHR) {
                var data = $.parseJSON(jqXHR.responseText);
                self.$this.find('li.fast_filter').find('div.throbber').remove();
                if (data && data.results) {
                    var $listItem = $('<li />', {'class':'moreResults'})
                        .appendTo(self.$this.find('li.fast_filter'));
                    var $link = $('<a />', {href:'#'})
                        .text(data.results)
                        .appendTo($listItem)
                        .click(function (event) {
                            event.preventDefault();
                            self.swap.apply(self, [data.message]);
                        });
                }
            }
        });
    }, 250);
};
/**
 * Replaces the contents of the navigation branch with the search results
 *
 * @param string list The search results
 *
 * @return void
 */
PMA_fastFilter.filter.prototype.swap = function (list)
{
    this.swapped = true;
    this.$this
        .html($(list).html())
        .children()
        .show()
        .end()
        .find('li.fast_filter input.searchClause')
        .val(this.searchClause);
    this.$this.data('fastFilter', this);
};
/**
 * Restores the navigation to the original state after the fast filter is cleared
 *
 * @param bool focus Whether to also focus the input box of the fast filter
 *
 * @return void
 */
PMA_fastFilter.filter.prototype.restore = function (focus)
{
    if (this.swapped) {
        this.swapped = false;
        this.$this.html(this.$clone.html()).children().show();
        this.$this.data('fastFilter', this);
        if (focus) {
            this.$this.find('li.fast_filter input.searchClause').focus();
        }
    }
    this.searchClause = '';
    this.$this.find('.moreResults').remove();
    this.$this.find('div.pageselector').show();
    this.$this.find('div.throbber').remove();
};
