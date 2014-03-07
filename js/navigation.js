/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * function used in or for navigation panel
 *
 * @package phpMyAdmin-Navigation
 */

/**
 * Executed on page load
 */
$(function () {
    if (! $('#pma_navigation').length) {
        // Don't bother running any code if the navigation is not even on the page
        return;
    }

    // Do not let the page reload on submitting the fast filter
    $(document).on('submit', '.fast_filter', function (event) {
        event.preventDefault();
    });

    // Fire up the resize handlers
    new ResizeHandler();

    /**
     * opens/closes (hides/shows) tree elements
     * loads data via ajax
     */
    $('#pma_navigation_tree a.expander').live('click', function (event) {
        event.preventDefault();
        event.stopImmediatePropagation();
        var $icon = $(this).find('img');
        if ($icon.is('.ic_b_plus')) {
            expandTreeNode($(this));
        } else {
            collapseTreeNode($(this));
        }
    });

    /**
     * Register event handler for click on the reload
     * navigation icon at the top of the panel
     */
    $('#pma_navigation_reload').live('click', function (event) {
        event.preventDefault();
        // reload icon object
        var $icon = $(this).find('img');
        // source of the hidden throbber icon
        var icon_throbber_src = $('#pma_navigation .throbber').attr('src');
        // source of the reload icon
        var icon_reload_src = $icon.attr('src');
        // replace the source of the reload icon with the one for throbber
        $icon.attr('src', icon_throbber_src);
        PMA_reloadNavigation();
        // after one second, put back the reload icon
        setTimeout(function () {
            $icon.attr('src', icon_reload_src);
        }, 1000);
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
            if ($('li:visible', this).length === 0) {
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
    $('#recentTable').live('change', function () {
        if (this.value !== '') {
            var arr = jQuery.parseJSON(this.value);
            var $form = $(this).closest('form');
            $form.find('input[name=db]').val(arr.db);
            $form.find('input[name=table]').val(arr.table);
            $form.submit();
        }
    });

    /** Create a Routine, Trigger or Event */
    $('li.new_procedure a.ajax, li.new_function a.ajax').live('click', function (event) {
        event.preventDefault();
        var dialog = new RTE.object('routine');
        dialog.editorDialog(1, $(this));
    });
    $('li.new_trigger a.ajax').live('click', function (event) {
        event.preventDefault();
        var dialog = new RTE.object('trigger');
        dialog.editorDialog(1, $(this));
    });
    $('li.new_event a.ajax').live('click', function (event) {
        event.preventDefault();
        var dialog = new RTE.object('event');
        dialog.editorDialog(1, $(this));
    });

    /** Edit Routines, Triggers and Events */
    $('li.procedure > a.ajax, li.function > a.ajax').live('click', function (event) {
        event.preventDefault();
        var dialog = new RTE.object('routine');
        dialog.editorDialog(0, $(this));
    });
    $('li.trigger > a.ajax').live('click', function (event) {
        event.preventDefault();
        var dialog = new RTE.object('trigger');
        dialog.editorDialog(0, $(this));
    });
    $('li.event > a.ajax').live('click', function (event) {
        event.preventDefault();
        var dialog = new RTE.object('event');
        dialog.editorDialog(0, $(this));
    });

    /** Export Routines, Triggers and Events */
    $('li.procedure div:eq(1) a.ajax img,' +
        ' li.function div:eq(1) a.ajax img,' +
        ' li.trigger div:eq(1) a.ajax img,' +
        ' li.event div:eq(1) a.ajax img'
        ).live('click', function (event) {
        event.preventDefault();
        var dialog = new RTE.object();
        dialog.exportDialog($(this).parent());
    });

    /** New index */
    $('li.new_index a.ajax').live('click', function (event) {
        event.preventDefault();
        var url = $(this).attr('href').substr(
            $(this).attr('href').indexOf('?') + 1
        ) + '&ajax_request=true';
        var title = PMA_messages.strAddIndex;
        indexEditorDialog(url, title);
    });

    /** Edit index */
    $('li.index a.ajax').live('click', function (event) {
        event.preventDefault();
        var url = $(this).attr('href').substr(
            $(this).attr('href').indexOf('?') + 1
        ) + '&ajax_request=true';
        var title = PMA_messages.strEditIndex;
        indexEditorDialog(url, title);
    });

    /** New view */
    $('li.new_view a.ajax').live('click', function (event) {
        event.preventDefault();
        PMA_createViewDialog($(this));
    });

    /** Hide navigation tree item */
    $('a.hideNavItem.ajax').live('click', function (event) {
        event.preventDefault();
        $.ajax({
            url: $(this).attr('href') + '&ajax_request=true',
            success: function (data) {
                if (data.success === true) {
                    PMA_reloadNavigation();
                } else {
                    PMA_ajaxShowMessage(data.error);
                }
            }
        });
    });

    /** Display a dialog to choose hidden navigation items to show */
    $('a.showUnhide.ajax').live('click', function (event) {
        event.preventDefault();
        var $msg = PMA_ajaxShowMessage();
        $.get($(this).attr('href') + '&ajax_request=1', function (data) {
            if (data.success === true) {
                PMA_ajaxRemoveMessage($msg);
                var buttonOptions = {};
                buttonOptions[PMA_messages.strClose] = function () {
                    $(this).dialog("close");
                };
                var $dialog = $('<div/>')
                    .attr('id', 'unhideNavItemDialog')
                    .append(data.message)
                    .dialog({
                        width: 400,
                        minWidth: 200,
                        modal: true,
                        buttons: buttonOptions,
                        title: PMA_messages.strUnhideNavItem,
                        close: function () {
                            $(this).remove();
                        }
                    });
            } else {
                PMA_ajaxShowMessage(data.error);
            }
        });
    });

    /** Show a hidden navigation tree item */
    $('a.unhideNavItem.ajax').live('click', function (event) {
        event.preventDefault();
        var $tr = $(this).parents('tr');
        var $msg = PMA_ajaxShowMessage();
        $.ajax({
            url: $(this).attr('href') + '&ajax_request=true',
            success: function (data) {
                PMA_ajaxRemoveMessage($msg);
                if (data.success === true) {
                    $tr.remove();
                    PMA_reloadNavigation();
                } else {
                    PMA_ajaxShowMessage(data.error);
                }
            }
        });
    });

    PMA_showCurrentNavigation();
});

/**
 * Expands a node in navigation tree.
 *
 * @param $expandElem expander
 * @param callback    callback function
 *
 * @returns void
 */
function expandTreeNode($expandElem, callback)
{
    var $children = $expandElem.closest('li').children('div.list_container');
    var $icon = $expandElem.find('img');
    if ($expandElem.hasClass('loaded')) {
        if ($icon.is('.ic_b_plus')) {
            $icon.removeClass('ic_b_plus').addClass('ic_b_minus');
            $children.show('fast');
        }
        if (callback && typeof callback == 'function') {
            callback.call();
        }
    } else {
        var $throbber = $('#pma_navigation .throbber')
            .first()
            .clone()
            .css({visibility: 'visible', display: 'block'})
            .click(false);
        $icon.hide();
        $throbber.insertBefore($icon);

        loadChildNodes($expandElem, function (data) {
            if (data.success === true) {
                var $destination = $expandElem.closest('li');
                $icon.removeClass('ic_b_plus').addClass('ic_b_minus');
                $destination
                    .children('div.list_container')
                    .show('fast');
                if ($destination.find('ul > li').length == 1) {
                    $destination.find('ul > li')
                        .find('a.expander.container')
                        .click();
                }
                if (callback && typeof callback == 'function') {
                    callback.call();
                }
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
            $icon.show();
            $throbber.remove();
        });
    }
    $expandElem.blur();
}

/**
 * Auto-scrolls the newly chosen database
 *
 * @param  object   $element    The element to set to view
 * @param  boolean  $forceToTop Whether to force scroll to top
 *
 */
function scrollToView($element, $forceToTop) {
    var $container = $('#pma_navigation_tree_content');
    var elemTop = $element.offset().top - $container.offset().top;
    var textHeight = 20;
    var scrollPadding = 20; // extra padding from top of bottom when scrolling to view
    if (elemTop < 0 || $forceToTop) {
        $container.stop().animate({
            scrollTop: elemTop + $container.scrollTop() - scrollPadding
        });
    } else if (elemTop + textHeight > $container.height()) {
        $container.stop().animate({
            scrollTop: elemTop + textHeight - $container.height() + $container.scrollTop() + scrollPadding
        });
    }
}

/**
 * Collapses a node in navigation tree.
 *
 * @param $expandElem expander
 *
 * @returns void
 */
function collapseTreeNode($expandElem) {
    var $children = $expandElem.closest('li').children('div.list_container');
    var $icon = $expandElem.find('img');
    if ($expandElem.hasClass('loaded')) {
        if ($icon.is('.ic_b_minus')) {
            $icon.removeClass('ic_b_minus').addClass('ic_b_plus');
            $children.hide('fast');
        }
    }
    $expandElem.blur();
}

/**
 * Loads child items of a node and executes a given callback
 *
 * @param $expandElem expander
 * @param callback    callback function
 *
 * @returns void
 */
function loadChildNodes($expandElem, callback) {
    var $destination = $expandElem.closest('li');

    var searchClause = PMA_fastFilter.getSearchClause();
    var searchClause2 = PMA_fastFilter.getSearchClause2($expandElem);

    var params = {
        aPath: $expandElem.find('span.aPath').text(),
        vPath: $expandElem.find('span.vPath').text(),
        pos: $expandElem.find('span.pos').text(),
        pos2_name: $expandElem.find('span.pos2_name').text(),
        pos2_value: $expandElem.find('span.pos2_value').text(),
        searchClause: searchClause,
        searchClause2: searchClause2
    };

    var url = $('#pma_navigation').find('a.navigation_url').attr('href');
    $.get(url, params, function (data) {
        if (data.success === true) {
            $expandElem.addClass('loaded');
            $destination.find('div.list_container').remove(); // FIXME: Hack, there shouldn't be a list container there
            $destination.append(data.message);
            if (callback && typeof callback == 'function') {
                callback(data);
            }
        } else {
            var $throbber = $expandElem.find('img.throbber');
            $throbber.hide();
            $icon = $expandElem.find('img.ic_b_plus');
            $icon.show();
            PMA_ajaxShowMessage(data.error, false);
        }
    });
}

/**
 * Expand the navigation and highlight the current database or table/view
 *
 * @returns void
 */
function PMA_showCurrentNavigation()
{
    var db = PMA_commonParams.get('db');
    var table = PMA_commonParams.get('table');
    $('#pma_navigation_tree')
        .find('li.selected')
        .removeClass('selected');
    if (db) {
        var $dbItem = findLoadedItem(
            $('#pma_navigation_tree > div'), db, 'database', !table
        );
        if ($dbItem) {
            var $expander = $dbItem.children('div:first').children('a.expander');
            // if not loaded or loaded but collapsed
            if (! $expander.hasClass('loaded') ||
                $expander.find('img').is('.ic_b_plus')
            ) {
                expandTreeNode($expander, function () {
                    handleTableOrDb(table, $dbItem);
                });
            } else {
                handleTableOrDb(table, $dbItem);
            }
        }
    }

    function handleTableOrDb(table, $dbItem) {
        if (table) {
            loadAndHighlightTableOrView($dbItem, table);
        } else {
            var $container = $dbItem.children('div.list_container');
            var $tableContainer = $container.children('ul').children('li.tableContainer');
            if ($tableContainer.length > 0) {
                var $expander = $tableContainer.children('div:first').children('a.expander');
                expandTreeNode($expander, function () {
                    scrollToView($dbItem, true);
                });
            } else {
                scrollToView($dbItem, true);
            }
        }
    }

    function findLoadedItem($container, name, clazz, doSelect) {
        var ret = false;
        $container.children('ul').children('li').each(function () {
            var $li = $(this);
            // this is a navigation group, recurse
            if ($li.is('.navGroup')) {
                var $container = $li.children('div.list_container');
                var $childRet = findLoadedItem(
                    $container, name, clazz, doSelect
                );
                if ($childRet) {
                    ret = $childRet;
                    return false;
                }
            } else { // this is a real navigation item
                // name and class matches
                if (((clazz && $li.is('.' + clazz)) || ! clazz) &&
                        $li.children('a').text() == name) {
                    if (doSelect) {
                        $li.addClass('selected');
                    }
                    // taverse up and expand and parent navigation groups
                    $li.parents('.navGroup').each(function () {
                        $cont = $(this).children('div.list_container');
                        if (! $cont.is(':visible')) {
                            $(this)
                                .children('div:first')
                                .children('a.expander')
                                .click();
                        }
                    });
                    ret = $li;
                    return false;
                }
            }
        });
        return ret;
    }

    function loadAndHighlightTableOrView($dbItem, itemName) {
        var $container = $dbItem.children('div.list_container');
        var $expander;
        var $whichItem = isItemInContainer($container, itemName, 'li.table, li.view');
        //If item already there in some container
        if ($whichItem) {
            //get the relevant container while may also be a subcontainer
            var $relatedContainer = $whichItem.closest('li.subContainer').length
                ? $whichItem.closest('li.subContainer')
                : $dbItem;
            $whichItem = findLoadedItem(
                $relatedContainer.children('div.list_container'),
                itemName, null, true
            );
            //Show directly
            showTableOrView($whichItem, $relatedContainer.children('div:first').children('a.expander'));
        //else if item not there, try loading once
        } else {
            var $sub_containers = $dbItem.find('.subContainer');
            //If there are subContainers i.e. tableContainer or viewContainer
            if($sub_containers.length > 0) {
                var $containers = new Array();
                $sub_containers.each(function (index) {
                    $containers[index] = $(this);
                    $expander = $containers[index]
                        .children('div:first')
                        .children('a.expander');
                    collapseTreeNode($expander);
                    loadAndShowTableOrView($expander, $containers[index], itemName);
                });
            // else if no subContainers
            } else {
                $expander = $dbItem
                    .children('div:first')
                    .children('a.expander');
                collapseTreeNode($expander);
                loadAndShowTableOrView($expander, $dbItem, itemName);
            }
        }
    }

    function loadAndShowTableOrView($expander, $relatedContainer, itemName) {
        loadChildNodes($expander, function (data) {
            var $whichItem = findLoadedItem(
                $relatedContainer.children('div.list_container'),
                itemName, null, true
            );
            if ($whichItem) {
                showTableOrView($whichItem, $expander);
            }
        });
    }

    function showTableOrView($whichItem, $expander) {
        expandTreeNode($expander, function (data) {
            if ($whichItem) {
                scrollToView($whichItem, false);
            }
        });
    }

    function isItemInContainer($container, name, clazz)
    {
        var $whichItem = null;
        $items = $container.find(clazz);
        var found = false;
        $items.each(function () {
            if ($(this).children('a').text() == name) {
                $whichItem = $(this);
                return false;
            }
        });
        return $whichItem;
    }
}

/**
 * Reloads the whole navigation tree while preserving its state
 *
 * @param  function     the callback function
 * @return void
 */
function PMA_reloadNavigation(callback) {
    var params = {
        reload: true,
        pos: $('#pma_navigation_tree').find('a.expander:first > span.pos').text()
    };
    // Traverse the navigation tree backwards to generate all the actual
    // and virtual paths, as well as the positions in the pagination at
    // various levels, if necessary.
    var count = 0;
    $('#pma_navigation_tree').find('a.expander:visible').each(function () {
        if ($(this).find('img').is('.ic_b_minus') &&
            $(this).closest('li').find('div.list_container .ic_b_minus').length === 0
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
        if (data.success) {
            $('#pma_navigation_tree').html(data.message).children('div').show();
            PMA_showCurrentNavigation();
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
    var url, params;
    if ($this[0].tagName == 'A') {
        url = $this.attr('href');
        params = 'ajax_request=true';
    } else { // tagName == 'SELECT'
        url = 'navigation.php';
        params = $this.closest("form").serialize() + '&ajax_request=true';
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
                .prop('title', PMA_messages.strShowPanel);
        } else {
            $collapser
                .css(this.left, pos)
                .html(this.getSymbol(pos))
                .prop('title', PMA_messages.strHidePanel);
        }
        setTimeout(function () {
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
        var windowScroll = $(window).scrollLeft();
        pos = pos - windowScroll;
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
            if (width === 0) {
                return '&rarr;';
            } else {
                return '&larr;';
            }
        } else {
            if (width === 0) {
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
    /**
     * Event handler for resizing the navigation tree height on window resize
     *
     * @return void
     */
    this.treeResize = function (event) {
        var $nav        = $("#pma_navigation"),
            $nav_tree   = $("#pma_navigation_tree"),
            $nav_header = $("#pma_navigation_header"),
            $nav_tree_content = $("#pma_navigation_tree_content");
        $nav_tree.height($nav.height() - $nav_header.height());
        if ($nav_tree_content.length > 0) {
            $nav_tree_content.height($nav_tree.height() - $nav_tree_content.position().top);
            $nav_tree.css({
                'overflow': 'hidden'
            });
        } else {
            $nav_tree.css({
                'overflow': 'hidden',
                'overflow-y': 'auto'
            });
        }
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
        .live('mousedown', {'resize_handler': this}, this.mousedown);
    $(document)
        .bind('mouseup', {'resize_handler': this}, this.mouseup)
        .bind('mousemove', {'resize_handler': this}, $.throttle(this.mousemove, 4));
    var $collapser = $('#pma_navigation_collapser');
    $collapser.live('click', {'resize_handler': this}, this.collapse);
    // Add the correct arrow symbol to the collapser
    $collapser.html(this.getSymbol($('#pma_navigation').width()));
    // Fix navigation tree height
    $(window).on('resize', this.treeResize);
    // need to call this now and then, browser might decide
    // to show/hide horizontal scrollbars depending on page content width
    setInterval(this.treeResize, 2000);
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
        if ($filterInput.length !== 0 &&
            $filterInput.val() !== '' &&
            $filterInput.val() != $filterInput[0].defaultValue
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
            if ($filterContainer.find('li.fast_filter:not(.db_fast_filter) input.searchClause').length !== 0) {
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
        if ($filterInput.length !== 0 &&
            $filterInput.first().val() != $filterInput[0].defaultValue
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
            if ($(this).val() === '') {
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
            if ($(this).val() != this.defaultValue && $(this).val() !== '') {
                $obj.find('div.pageselector').hide();
                str = $(this).val();
            }

            /**
             * FIXME at the server level a value match is done while on
             * the client side it is a regex match. These two should be aligned
             */

            // regex used for filtering.
            var regex;
            try {
                regex = new RegExp(str, 'i');
            } catch (err) {
                return;
            }

            // this is the div that houses the items to be filtered by this filter.
            var outerContainer;
            if ($(this).closest('li.fast_filter').is('.db_fast_filter')) {
                outerContainer = $('#pma_navigation_tree_content');
            } else {
                outerContainer = $obj;
            }

            // filters items that are directly under the div as well as grouped in
            // groups. Does not filter child items (i.e. a database search does
            // not filter tables)
            var item_filter = function($curr) {
                $curr.children('ul').children('li.navGroup').each(function() {
                    $(this).children('div.list_container').each(function() {
                        item_filter($(this)); // recursive
                    });
                });
                $curr.children('ul').children('li').children('a').not('.container').each(function() {
                    if (regex.test($(this).text())) {
                        $(this).parent().show().removeClass('hidden');
                    } else {
                        $(this).parent().hide().addClass('hidden');
                    }
                });
            };
            item_filter(outerContainer);

            // hides containers that does not have any visible children
            var container_filter = function ($curr) {
                $curr.children('ul').children('li.navGroup').each(function() {
                    var $group = $(this);
                    $group.children('div.list_container').each(function() {
                        container_filter($(this)); // recursive
                    });
                    $group.show().removeClass('hidden');
                    if ($group.children('div.list_container').children('ul')
                            .children('li').not('.hidden').length === 0) {
                        $group.hide().addClass('hidden');
                    }
                });
            };
            container_filter(outerContainer);

            if ($(this).val() != this.defaultValue && $(this).val() !== '') {
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
    if (self.$this.find('li.fast_filter').find('img.throbber').length === 0) {
        self.$this.find('li.fast_filter').append(
            $('<div class="throbber"></div>').append(
                $('#pma_navigation_content')
                    .find('img.throbber')
                    .clone()
                    .css({visibility: 'visible', display: 'block'})
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
        if (self.$this.find('> ul > li > form.fast_filter:first input[name=searchClause]').length === 0) {
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
                    var $listItem = $('<li />', {'class': 'moreResults'})
                        .appendTo(self.$this.find('li.fast_filter'));
                    var $link = $('<a />', {href: '#'})
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
