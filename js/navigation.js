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
        var $icon = $this.parent().find('img');
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
            var $throbber = $('.throbber').first().clone().show();
            $icon.hide();
            $throbber.insertBefore($icon);
            var params = {
                a_path: $(this).find('span.a_path').text(),
                v_path: $(this).find('span.v_path').text()
            };
            var url = $('#pma_navigation').find('a.navigation_url').attr('href');
            $.get(url, params, function (data) {
                if (data.success === true) {
                    $this.addClass('loaded');
                    $destination.find('div.list_container').remove(); // FIXME: Hack, there shouldn't be a list container there
                    $destination.append(data.message);
	                $icon.removeClass('ic_b_plus').addClass('ic_b_minus');
	                $destination.children('div.list_container').show('fast');
                    if ($destination.find('ul > li').length == 1) {
                        $destination.find('ul > li').find('a.expander.container').click();
                    }
                }
                $icon.show();
                $throbber.remove();
            });
        }
        $(this).blur();
	});
});

/**
 * @var ResizeHandler Custom object that manages the resizing of the navigation
 *
 * XXX: Must only be ever instanciated once
 * XXX: Inside event handlers the 'this' object is accessed as 'e.data.this'
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
        $("#floating_menubar").css('margin-' + this.left, (pos + resizer_width) + 'px');
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
    this.getPos = function (e) {
        var pos = e.pageX;
        if (this.left != 'left') {
            pos = $(window).width() - e.pageX;
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
    this.mousedown = function (e) {
        e.preventDefault();
        e.data.this.active = true;
        $('body').css('cursor', 'col-resize');
    };
    /**
     * Event handler for terminating a resize of the panel
     *
     * @param object e Event data (contains a reference to resizeHandler)
     *
     * @return void
     */
    this.mouseup = function (e) {
        if (e.data.this.active) {
            e.data.this.active = false;
            $('body').css('cursor', '');
            $.cookie('pma_navi_width', e.data.this.getPos(e));
        }
    };
    /**
     * Event handler for updating the panel during a resize operation
     *
     * @param object e Event data (contains a reference to resizeHandler)
     *
     * @return void
     */
    this.mousemove = function (e) {
        if (e.data.this.active) {
            e.preventDefault();
            var pos = e.data.this.getPos(e);
            e.data.this.setWidth(pos);
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
    this.collapse = function (e) {
        e.preventDefault();
        e.data.active = false;
        var goto = e.data.this.goto;
        var width = $('#pma_navigation').width();
        if (width === 0 && goto === 0) {
            goto = 240;
        }
        e.data.this.setWidth(goto);
        e.data.this.goto = width;
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
    /* Instanciate the resize handler */
    if ($('#pma_navigation').length) {
        new ResizeHandler();
    }

    // Ajax handler for database pagination
    $('#pma_navigation_tree div.pageselector a.ajax').live('click', function (e) {
        e.preventDefault();
        var $msgbox = PMA_ajaxShowMessage();
        $.get($(this).attr('href'), {ajax_request: true, full: true}, function (data) {
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
    $('li.fast_filter > span').live('click', function () {
        // Clear the input and apply the fast filter with empty input
        var value = $(this).prev()[0].defaultValue;
        $(this).prev().val(value).trigger('keyup');
    });
    // Bind "fast filter"
    $('li.fast_filter > input').live('focus', function () {
        if ($(this).val() == this.defaultValue) {
            $(this).val('');
        } else {
            $(this).select();
        }
    });
    $('li.fast_filter > input').live('blur', function () {
        if ($(this).val() == '') {
            $(this).val(this.defaultValue);
        }
    });
    $('li.fast_filter > input').live('keyup', function () {
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

