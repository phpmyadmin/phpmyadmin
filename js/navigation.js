/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * function used in or for navigation frame
 *
 * @package phpMyAdmin-Navigation
 */

/**
 * opens/closes (hides/shows) tree elements
 * loads data via ajax
 */
$(document).ready(function() {
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
            var params = {
                a_path: $(this).find('span.a_path').text(),
                v_path: $(this).find('span.v_path').text(),
                pos: $(this).find('span.pos').text()
            };
            var url = $('#pma_navigation').find('a.navigation_url').attr('href');
            $.get(url, params, function (data) {
                if (data.success === true) {
                    $this.addClass('loaded');
                    $destination.find('div.list_container').remove(); // FIXME: Hack, there shouldn't be a list container there
                    $destination.append(data.message);
	                $icon.removeClass('ic_b_plus').addClass('ic_b_minus');
	                $destination.children('div.list_container').show('fast', function () {
                        ScrollHandler.displayScrollbar();
                    });
                    if ($destination.find('ul > li').length == 1) {
                        $destination.find('ul > li')
                            .find('a.expander.container')
                            .click();
                    }
                }
                $icon.show();
                $throbber.remove();
            });
        }
        $(this).blur();
	});

    $('#pma_navigation_reload').click(function () {
        PMA_reloadNavigation();
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
            && $(this).closest('li').find('.list_container .ic_b_minus').length == 0
        ) {
            params['a_path[' + count] = $(this).find('span.a_path').text();
            params['v_path[' + count] = $(this).find('span.v_path').text();
            count++;
        }
    });
    var url = $('#pma_navigation').find('a.navigation_url').attr('href');
    $.post(url, params, function (data) {
        if (data.success) {
            $throbber.css('visibility', 'hidden');
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
            var visibleRatio = ($(window).height() - elms.$scrollbar.offset().top) / elms.$content.height();
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
        $('#pma_navigation').bind('mousewheel', function(event, delta, deltaX, deltaY) {
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
        });
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

        $('#pma_navigation_scrollbar').css(this.left, (pos - $('#pma_navigation_scrollbar').width()) + 'px');

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
            $.cookie('pma_navi_width', event.data.this.getPos(e));
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
            var pos = event.data.this.getPos(e);
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

/* Performed on load */
$(function(){
    if ($('#pma_navigation').length) {
        $('#pma_navigation_tree').children('div').show();
        // Fire up the resize and scroll handlers
        new ResizeHandler();
        ScrollHandler.init();
    }

    // Ajax handler for database pagination
    $('#pma_navigation_tree div.pageselector a.ajax').live('click', function (event) {
        event.preventDefault();
        var $msgbox = PMA_ajaxShowMessage();
        var params = {ajax_request: true, full: true};
        $.get($(this).attr('href'), params, function (data) {
            PMA_ajaxRemoveMessage($msgbox);
            if (data.success) {
                $('#pma_navigation_tree').html(data.message).children('div').show();
            }
        });
    });

    // Node highlighting
	$('#navigation_tree.highlight li:not(.fast_filter)').live('mouseover', function () {
        if ($('li:visible', this).length == 0) {
            $(this).css('background', '#ddd');
        }
    });
	$('#navigation_tree.highlight li:not(.fast_filter)').live('mouseout', function () {
        $(this).css('background', '');
    });

    // FIXME: reintegrate ajax table filtering
    // when the table pagination is implemented

    // Bind "clear fast filter"
    $('#pma_navigation_tree li.fast_filter > span').live('click', function () {
        // Clear the input and apply the fast filter with empty input
        var value = $(this).prev()[0].defaultValue;
        $(this).prev().val(value).trigger('keyup');
    });
    // Bind "fast filter"
    $('#pma_navigation_tree li.fast_filter > input').live('focus', function () {
        if ($(this).val() == this.defaultValue) {
            $(this).val('');
        } else {
            $(this).select();
        }
    });
    $('#pma_navigation_tree li.fast_filter > input').live('blur', function () {
        if ($(this).val() == '') {
            $(this).val(this.defaultValue);
        }
    });
    $('#pma_navigation_tree li.fast_filter > input').live('keyup', function () {
        var $obj = $(this).parent().parent();
        var str = '';
        if ($(this).val() != this.defaultValue) {
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
    });

    // Jump to recent table
    $('#recentTable').change(function() {
        if (this.value != '') {
            var arr = jQuery.parseJSON(this.value);
            var $form = $(this).closest('form');
            $form.find('input[name=db]').val(arr['db']);
            $form.find('input[name=table]').val(arr['table']);
            $form.submit();
        }
    });
});//end of document get ready

