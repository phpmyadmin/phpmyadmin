/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * function used in or for navigation frame
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

    // Fire up the resize and scroll handlers
    new ResizeHandler();
    ScrollHandler.init();

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
		        $children.show('fast', function () {
                    ScrollHandler.displayScrollbar();
                });
	        } else {
		        $icon.removeClass('ic_b_minus').addClass('ic_b_plus');
		        $children.hide('fast', function () {
                    ScrollHandler.displayScrollbar();
                });
	        }
        } else {
            var $destination = $this.closest('li');
            var $throbber = $('#pma_navigation .throbber')
                .first()
                .clone()
                .css('visibility', 'visible');
            $icon.hide();
            $throbber.insertBefore($icon);

            var $filterContainer = $(this).closest('div.list_container');
            var $filterInput = $([]);
            while (1) {
                if ($filterContainer.find('li.fast_filter input.searchClause').length != 0) {
                    $filterInput = $filterContainer.find('li.fast_filter input.searchClause');
                    break;
                } else if (! $filterContainer.is('div.list_container')) {
                    break;
                }
                $filterContainer = $filterContainer
                    .parent()
                    .closest('div.list_container');
            }
            var searchClause = '';
            if ($filterInput.length != 0
                && $filterInput.val() != $filterInput[0].defaultValue
            ) {
                searchClause = $filterInput.val();
            }

            var params = {
                aPath: $(this).find('span.aPath').text(),
                vPath: $(this).find('span.vPath').text(),
                pos: $(this).find('span.pos').text(),
                pos2_name: $(this).find('span.pos2_name').text(),
                pos2_value: $(this).find('span.pos2_value').text(),
                searchClause: searchClause
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
                        .show(
                            'fast',
                            function () {
                                ScrollHandler.displayScrollbar();
                            }
                        );
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
    $('#pma_navigation_reload').click(function () {
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
        var $this = $(this);
        var isDbSelector = $this.closest('.pageselector').is('.dbselector');
        var $msgbox = PMA_ajaxShowMessage();
        var params = {ajax_request: true};
        if (isDbSelector) {
            params['full'] = true;
        } else {
            var $input = $this
                .closest('div.list_container')
                .find('li.fast_filter input.searchClause');
            if ($input.length && $input.val() != $input[0].defaultValue) {
                params['searchClause'] = $input.val();
            }
        }
        $.get($this.attr('href'), params, function (data) {
            PMA_ajaxRemoveMessage($msgbox);
            if (data.success) {
                if (isDbSelector) {
                    $('#pma_navigation_tree')
                        .html(data.message)
                        .children('div')
                        .show();
                } else {
                    var $parent = $this.closest('div.list_container').parent();
                    var $input = $this
                        .closest('div.list_container')
                        .find('li.fast_filter input.searchClause');
                    var val = '';
                    if ($input.length) {
                        val = $input.val();
                    }
                    $this.closest('div.list_container').html(
                        $(data.message).children().show()
                    );
                    $parent.find('li.fast_filter input.searchClause').val(val);
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
    });

    /**
     * Node highlighting
     */
	$('#pma_navigation_tree.highlight li:not(.fast_filter)').live(
        'mouseover',
        function () {
            if ($('li:visible', this).length == 0) {
                $(this).css('background', '#ddd');
            }
        }
    );
	$('#pma_navigation_tree.highlight li:not(.fast_filter)').live(
        'mouseout',
        function () {
            $(this).css('background', '');
        }
    );

    /**
     * Jump to recent table
     */
    $('#recentTable').change(function() {
        if (this.value != '') {
            var arr = jQuery.parseJSON(this.value);
            var $form = $(this).closest('form');
            $form.find('input[name=db]').val(arr['db']);
            $form.find('input[name=table]').val(arr['table']);
            $form.submit();
        }
    });
});

/**
 * Reloads the whole navigation tree while preserving its state
 *
 * @return void
 */
function PMA_reloadNavigation() {
    var $throbber = $('#pma_navigation .throbber')
        .first()
        .css('visibility', 'visible');
    var params = {
        reload: true,
        pos: $('#pma_navigation_tree').find('a.expander:first > span.pos').text()
    };
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
        } else {
            PMA_ajaxShowMessage(data.error);
        }
    });
};

/**
 * @var ScrollHandler Custom object that manages the scrolling of the navigation
 */
var ScrollHandler = {
    sanitize: function (value) {
        if (value < 0) {
            value = 0;
        } else if (value > 1) {
            value = 1;
        }
        return value;
    },
    setScrollbar: function (value) {
        value = ScrollHandler.sanitize(value);
        var elms = ScrollHandler.elms;
        var height = elms.$scrollbar.height() - elms.$handle.height() - elms.$scrollbar.offset().top;
        var offset = Math.floor(
            value * height
        );
        elms.$handle.css('top', offset);
    },
    setContent: function (value) {
        value = ScrollHandler.sanitize(value);
        var elms = ScrollHandler.elms;
        var diff = elms.$content.height() - $(window).height();
        var offset = Math.floor(
            value * diff
        );
        elms.$content.css('top', -offset);
    },
    displayScrollbar: function () {
        var elms = ScrollHandler.elms;
        if (elms.$content.height() > $(window).height()) {
            elms.$scrollbar.show().data('active', 1);
            var visibleRatio = (
                $(window).height() - elms.$scrollbar.offset().top
            ) / elms.$content.height();
            elms.$handle.height(
                Math.floor(
                    visibleRatio * $(window).height()
                )
            );
        } else {
            elms.$scrollbar.hide().data('active', 0);
            elms.$content.css('top', 0);
        }
    },
    init: function () {
        this.elms = {
            $content: $('#pma_navigation_content'),
            $scrollbar: $('#pma_navigation_scrollbar'),
            $handle: $('#pma_navigation_scrollbar_handle')
        };
        this.displayScrollbar();
        $(window).bind('resize', this.displayScrollbar);
        this.elms.$handle.bind('drag', function (event, drag) {
            var elms = ScrollHandler.elms;
            var scrollbarOffset = elms.$scrollbar.offset().top;
            var pos = drag.offsetY - scrollbarOffset;
            var height = $(window).height() - scrollbarOffset - elms.$handle.height();
            value = ScrollHandler.sanitize(pos / height);
            ScrollHandler.setScrollbar(value);
            ScrollHandler.setContent(value);
        });
        this.elms.$scrollbar.bind('click', function (event) {
            if($(event.target).attr('id') === $(this).attr('id')) {
                var $scrollbar = ScrollHandler.elms.$scrollbar;
                var $handle = ScrollHandler.elms.$handle;
                var pos = event.pageY - $scrollbar.offset().top - ($handle.height() / 2);
                var height = $scrollbar.height() - $scrollbar.offset().top - $handle.height();
                var target = pos / height;
                ScrollHandler.setScrollbar(target);
                ScrollHandler.setContent(target);
            }
        });
        $('#pma_navigation').bind(
            'mousewheel',
            function(event, delta, deltaX, deltaY) {
                event.preventDefault();
                var elms = ScrollHandler.elms;
                if (elms.$scrollbar.data('active')) {
                    var elms = ScrollHandler.elms;
                    var pixelValue = 1 / (elms.$content.height() - $(window).height());
                    var offset = -deltaY * 20 * pixelValue;
                    var pos = Math.abs(elms.$content.offset().top);
                    var diff = elms.$content.height() - $(window).height();
                    var target = ScrollHandler.sanitize((pos / diff) + offset);
                    ScrollHandler.setScrollbar(target);
                    ScrollHandler.setContent(target);
                }
            }
        );
    }
};

/**
 * @var ResizeHandler Custom object that manages the resizing of the navigation
 *
 * XXX: Must only be ever instanciated once
 * XXX: Inside event handlers the 'this' object is accessed as 'event.data.this'
 */
var ResizeHandler = function () {
    /**
     * Whether we are busy
     */
    this.active = false;
    /**
     * @var int goto Used by the collapser to know where to go
     *               back to when uncollapsing the panel
     */
    this.goto = 0;
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
        var resizer_width = $('#pma_navigation_resizer').width();
        var $collapser = $('#pma_navigation_collapser');
        $('#pma_navigation').width(pos);
        $('body').css('margin-' + this.left, pos + 'px');
        $("#floating_menubar")
            .css('margin-' + this.left, (pos + resizer_width) + 'px');
        $('#pma_navigation_resizer').css(this.left, pos + 'px');
        if (pos === 0) {
            $collapser
                .css(this.left, pos + resizer_width)
                .html(this.getSymbol(pos))
                .prop('title', PMA_messages['strShowPanel']);
            $('#serverinfo').css('padding-' + this.left, '2.2em');
        } else {
            $collapser
                .css(this.left, pos - $collapser.width())
                .html(this.getSymbol(pos))
                .prop('title', PMA_messages['strHidePanel']);
            $('#serverinfo').css('padding-' + this.left, '0.9em');
        }

        $('#pma_navigation_scrollbar').css(
            this.left,
            (pos - $('#pma_navigation_scrollbar').width()) + 'px'
        );

        menuResize();
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
        if (this.left != 'left') {
            pos = $(window).width() - event.pageX;
        }
        if (pos < 0) {
            pos = 0;
        } else if (pos + 100 >= $(window).width()) {
            pos = $(window).width() - 100;
        } else {
            this.goto = 0;
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
        event.data.this.active = true;
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
        if (event.data.this.active) {
            event.data.this.active = false;
            $('body').css('cursor', '');
            $.cookie('pma_navi_width', event.data.this.getPos(event));
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
        if (event.data.this.active) {
            event.preventDefault();
            var pos = event.data.this.getPos(event);
            event.data.this.setWidth(pos);
            menuResize();
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
        var goto = event.data.this.goto;
        var width = $('#pma_navigation').width();
        if (width === 0 && goto === 0) {
            goto = 240;
        }
        event.data.this.setWidth(goto);
        event.data.this.goto = width;
    };
    /* Initialisation section begins here */
    if ($.cookie('pma_navi_width')) {
        // If we have a cookie, set the width of the panel to its value
        var pos = Math.abs(parseInt($.cookie('pma_navi_width'), 10));
        this.setWidth(pos);
        menuResize();
    }
    // Register the events for the resizer and the collapser
    $('#pma_navigation_resizer')
        .bind('mousedown', {'this':this}, this.mousedown);
    $(document)
        .bind('mouseup', {'this':this}, this.mouseup)
        .bind('mousemove', {'this':this}, this.mousemove);
    var $collapser = $('#pma_navigation_collapser');
    $collapser.bind('click', {'this':this}, this.collapse);
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
     * @var hash events A list of functions that are further
     *                  down the page bound to DOM events
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
            ScrollHandler.displayScrollbar();
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
        },
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
 * After a delay of 500mS, initiates a request to retrieve search results
 * Multiple calls to this function will always abort the previous request
 *
 * @return void
 */
PMA_fastFilter.filter.prototype.request = function ()
{
    var that = this;
    clearTimeout(this.timeout);
    this.timeout = setTimeout(function () {
        if (that.xhr) {
            that.xhr.abort();
        }
        var url = $('#pma_navigation').find('a.navigation_url').attr('href');
        var results = that.$this.find('li:visible:not(.fast_filter)').length;
        var params = that.$this.find('form.fast_filter').serialize() + "&results=" + results;
        that.xhr = $.ajax({
            url: url,
            type: 'post',
            dataType: 'json',
            data: params,
            complete: function (jqXHR) {
                var data = $.parseJSON(jqXHR.responseText);
                if (data && data.results) {
                    var $listItem = $('<li />', {'class':'moreResults'})
                        .appendTo(that.$this.find('li.fast_filter'));
                    var $link = $('<a />', {href:'#'})
                        .text(data.results)
                        .appendTo($listItem)
                        .click(function (event) {
                            event.preventDefault();
                            that.swap.apply(that, [data.message]);
                        });
                }
            }
        });
    }, 500);
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
    ScrollHandler.displayScrollbar();
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
    ScrollHandler.displayScrollbar();
};
