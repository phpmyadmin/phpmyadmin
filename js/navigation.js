/* vim: set expandtab sw=4 ts=4 sts=4: */
/**
 * function used in or for navigation panel
 *
 * @package phpMyAdmin-Navigation
 */

/**
 * updates the tree state in sessionStorage
 *
 * @returns void
 */
function navTreeStateUpdate() {
    // update if session storage is supported
    if (isStorageSupported('sessionStorage')) {
        var storage = window.sessionStorage;
        // try catch necessary here to detect whether
        // content to be stored exceeds storage capacity
        try {
            storage.setItem('navTreePaths', JSON.stringify(traverseNavigationForPaths()));
            storage.setItem('server', PMA_commonParams.get('server'));
            storage.setItem('token', PMA_commonParams.get('token'));
        } catch(error) {
            // storage capacity exceeded & old navigation tree
            // state is no more valid, so remove it
            storage.removeItem('navTreePaths');
            storage.removeItem('server');
            storage.removeItem('token');
        }
    }
}

/**
 * Loads child items of a node and executes a given callback
 *
 * @param isNode
 * @param $expandElem expander
 * @param callback    callback function
 *
 * @returns void
 */
function loadChildNodes(isNode, $expandElem, callback) {

    var $destination = null;
    var params = null;

    if (isNode) {
        if (!$expandElem.hasClass('expander')) {
            return;
        }
        $destination = $expandElem.closest('li');
        params = {
            aPath: $expandElem.find('span.aPath').text(),
            vPath: $expandElem.find('span.vPath').text(),
            pos: $expandElem.find('span.pos').text(),
            pos2_name: $expandElem.find('span.pos2_name').text(),
            pos2_value: $expandElem.find('span.pos2_value').text(),
            searchClause: '',
            searchClause2: ''
        };
        if ($expandElem.closest('ul').hasClass('search_results')) {
            params.searchClause = PMA_fastFilter.getSearchClause();
            params.searchClause2 = PMA_fastFilter.getSearchClause2($expandElem);
        }
    } else {
        $destination = $('#pma_navigation_tree_content');
        params = {
            aPath: $expandElem.attr('aPath'),
            vPath: $expandElem.attr('vPath'),
            pos: $expandElem.attr('pos'),
            pos2_name: '',
            pos2_value: '',
            searchClause: '',
            searchClause2: ''
        };
    }

    var url = $('#pma_navigation').find('a.navigation_url').attr('href');
    $.get(url, params, function (data) {
        if (typeof data !== 'undefined' && data.success === true) {
            $destination.find('div.list_container').remove(); // FIXME: Hack, there shouldn't be a list container there
            if (isNode) {
                $destination.append(data.message);
                $expandElem.addClass('loaded');
            } else {
                $destination.html(data.message);
                $destination.children()
                    .first()
                    .css({
                        border: '0px',
                        margin: '0em',
                        padding : '0em'
                    })
                    .slideDown('slow');
            }
            if (data._errors) {
                var $errors = $(data._errors);
                if ($errors.children().length > 0) {
                    $('#pma_errors').replaceWith(data._errors);
                }
            }
            if (callback && typeof callback == 'function') {
                callback(data);
            }
        } else if(data.redirect_flag == "1") {
            if (window.location.href.indexOf('?') === -1) {
                window.location.href += '?session_expired=1';
            } else {
                window.location.href += '&session_expired=1';
            }
            window.location.reload();
        } else {
            var $throbber = $expandElem.find('img.throbber');
            $throbber.hide();
            var $icon = $expandElem.find('img.ic_b_plus');
            $icon.show();
            PMA_ajaxShowMessage(data.error, false);
        }
    });
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
            $children.slideUp('fast');
        }
    }
    $expandElem.blur();
    $children.promise().done(navTreeStateUpdate);
}

/**
 * Traverse the navigation tree backwards to generate all the actual
 * and virtual paths, as well as the positions in the pagination at
 * various levels, if necessary.
 *
 * @return Object
 */
function traverseNavigationForPaths() {
    var params = {
        pos: $('#pma_navigation_tree').find('div.dbselector select').val()
    };
    if ($('#navi_db_select').length) {
        return params;
    }
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
    return params;
}

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
    $(document).on('click', '#pma_navigation_tree a.expander', function (event) {
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
    $(document).on('click', '#pma_navigation_reload', function (event) {
        event.preventDefault();
        // reload icon object
        var $icon = $(this).find('img');
        // source of the hidden throbber icon
        var icon_throbber_src = $('#pma_navigation').find('.throbber').attr('src');
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

    $(document).on("change", '#navi_db_select',  function (event) {
        if (! $(this).val()) {
            PMA_commonParams.set('db', '');
            PMA_reloadNavigation();
        }
        $(this).closest('form').trigger('submit');
    });

    /**
     * Register event handler for click on the collapse all
     * navigation icon at the top of the navigation tree
     */
    $(document).on('click', '#pma_navigation_collapse', function (event) {
        event.preventDefault();
        $('#pma_navigation_tree').find('a.expander').each(function() {
            var $icon = $(this).find('img');
            if ($icon.is('.ic_b_minus')) {
                $(this).click();
            }
        });
    });

    /**
     * Register event handler to toggle
     * the 'link with main panel' icon on mouseenter.
     */
    $(document).on('mouseenter', '#pma_navigation_sync', function (event) {
        event.preventDefault();
        var synced = $('#pma_navigation_tree').hasClass('synced');
        var $img = $('#pma_navigation_sync').children('img');
        if (synced) {
            $img.removeClass('ic_s_link').addClass('ic_s_unlink');
        } else {
            $img.removeClass('ic_s_unlink').addClass('ic_s_link');
        }
    });

    /**
     * Register event handler to toggle
     * the 'link with main panel' icon on mouseout.
     */
    $(document).on('mouseout', '#pma_navigation_sync', function (event) {
        event.preventDefault();
        var synced = $('#pma_navigation_tree').hasClass('synced');
        var $img = $('#pma_navigation_sync').children('img');
        if (synced) {
            $img.removeClass('ic_s_unlink').addClass('ic_s_link');
        } else {
            $img.removeClass('ic_s_link').addClass('ic_s_unlink');
        }
    });

    /**
     * Register event handler to toggle
     * the linking with main panel behavior
     */
    $(document).on('click', '#pma_navigation_sync', function (event) {
        event.preventDefault();
        var synced = $('#pma_navigation_tree').hasClass('synced');
        var $img = $('#pma_navigation_sync').children('img');
        if (synced) {
            $img
                .removeClass('ic_s_unlink')
                .addClass('ic_s_link')
                .attr('alt', PMA_messages.linkWithMain)
                .attr('title', PMA_messages.linkWithMain);
            $('#pma_navigation_tree')
                .removeClass('synced')
                .find('li.selected')
                .removeClass('selected');
        } else {
            $img
                .removeClass('ic_s_link')
                .addClass('ic_s_unlink')
                .attr('alt', PMA_messages.unlinkWithMain)
                .attr('title', PMA_messages.unlinkWithMain);
            $('#pma_navigation_tree').addClass('synced');
            PMA_showCurrentNavigation();
        }
    });

    /**
     * Bind all "fast filter" events
     */
    $(document).on('click', '#pma_navigation_tree li.fast_filter span', PMA_fastFilter.events.clear);
    $(document).on('focus', '#pma_navigation_tree li.fast_filter input.searchClause', PMA_fastFilter.events.focus);
    $(document).on('blur', '#pma_navigation_tree li.fast_filter input.searchClause', PMA_fastFilter.events.blur);
    $(document).on('keyup', '#pma_navigation_tree li.fast_filter input.searchClause', PMA_fastFilter.events.keyup);

    /**
     * Ajax handler for pagination
     */
    $(document).on('click', '#pma_navigation_tree div.pageselector a.ajax', function (event) {
        event.preventDefault();
        PMA_navigationTreePagination($(this));
    });

    /**
     * Node highlighting
     */
    $(document).on(
        'mouseover',
        '#pma_navigation_tree.highlight li:not(.fast_filter)',
        function () {
            if ($('li:visible', this).length === 0) {
                $(this).addClass('activePointer');
            }
        }
    );
    $(document).on(
        'mouseout',
        '#pma_navigation_tree.highlight li:not(.fast_filter)',
        function () {
            $(this).removeClass('activePointer');
        }
    );

    /** Create a Routine, Trigger or Event */
    $(document).on('click', 'li.new_procedure a.ajax, li.new_function a.ajax', function (event) {
        event.preventDefault();
        var dialog = new RTE.object('routine');
        dialog.editorDialog(1, $(this));
    });
    $(document).on('click', 'li.new_trigger a.ajax', function (event) {
        event.preventDefault();
        var dialog = new RTE.object('trigger');
        dialog.editorDialog(1, $(this));
    });
    $(document).on('click', 'li.new_event a.ajax', function (event) {
        event.preventDefault();
        var dialog = new RTE.object('event');
        dialog.editorDialog(1, $(this));
    });

    /** Edit Routines, Triggers or Events */
    $(document).on('click', 'li.procedure > a.ajax, li.function > a.ajax', function (event) {
        event.preventDefault();
        var dialog = new RTE.object('routine');
        dialog.editorDialog(0, $(this));
    });
    $(document).on('click', 'li.trigger > a.ajax', function (event) {
        event.preventDefault();
        var dialog = new RTE.object('trigger');
        dialog.editorDialog(0, $(this));
    });
    $(document).on('click', 'li.event > a.ajax', function (event) {
        event.preventDefault();
        var dialog = new RTE.object('event');
        dialog.editorDialog(0, $(this));
    });

    /** Execute Routines */
    $(document).on('click', 'li.procedure div a.ajax img,' +
        ' li.function div a.ajax img', function (event) {
        event.preventDefault();
        var dialog = new RTE.object('routine');
        dialog.executeDialog($(this).parent());
    });
    /** Export Triggers and Events */
    $(document).on('click', 'li.trigger div:eq(1) a.ajax img,' +
        ' li.event div:eq(1) a.ajax img', function (event) {
        event.preventDefault();
        var dialog = new RTE.object();
        dialog.exportDialog($(this).parent());
    });

    /** New index */
    $(document).on('click', '#pma_navigation_tree li.new_index a.ajax', function (event) {
        event.preventDefault();
        var url = $(this).attr('href').substr(
            $(this).attr('href').indexOf('?') + 1
        ) + '&ajax_request=true';
        var title = PMA_messages.strAddIndex;
        indexEditorDialog(url, title);
    });

    /** Edit index */
    $(document).on('click', 'li.index a.ajax', function (event) {
        event.preventDefault();
        var url = $(this).attr('href').substr(
            $(this).attr('href').indexOf('?') + 1
        ) + '&ajax_request=true';
        var title = PMA_messages.strEditIndex;
        indexEditorDialog(url, title);
    });

    /** New view */
    $(document).on('click', 'li.new_view a.ajax', function (event) {
        event.preventDefault();
        PMA_createViewDialog($(this));
    });

    /** Hide navigation tree item */
    $(document).on('click', 'a.hideNavItem.ajax', function (event) {
        event.preventDefault();
        $.ajax({
            type: 'POST',
            url: $(this).attr('href') + '&ajax_request=true',
            success: function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    PMA_reloadNavigation();
                } else {
                    PMA_ajaxShowMessage(data.error);
                }
            }
        });
    });

    /** Display a dialog to choose hidden navigation items to show */
    $(document).on('click', 'a.showUnhide.ajax', function (event) {
        event.preventDefault();
        var $msg = PMA_ajaxShowMessage();
        $.get($(this).attr('href') + '&ajax_request=1', function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                PMA_ajaxRemoveMessage($msg);
                var buttonOptions = {};
                buttonOptions[PMA_messages.strClose] = function () {
                    $(this).dialog("close");
                };
                $('<div/>')
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
    $(document).on('click', 'a.unhideNavItem.ajax', function (event) {
        event.preventDefault();
        var $tr = $(this).parents('tr');
        var $msg = PMA_ajaxShowMessage();
        $.ajax({
            type: 'POST',
            url: $(this).attr('href') + '&ajax_request=true',
            success: function (data) {
                PMA_ajaxRemoveMessage($msg);
                if (typeof data !== 'undefined' && data.success === true) {
                    $tr.remove();
                    PMA_reloadNavigation();
                } else {
                    PMA_ajaxShowMessage(data.error);
                }
            }
        });
    });

    // Add/Remove favorite table using Ajax.
    $(document).on("click", ".favorite_table_anchor", function (event) {
        event.preventDefault();
        $self = $(this);
        var anchor_id = $self.attr("id");
        if($self.data("favtargetn") !== null) {
            if($('a[data-favtargets="' + $self.data("favtargetn") + '"]').length > 0)
            {
                $('a[data-favtargets="' + $self.data("favtargetn") + '"]').trigger('click');
                return;
            }
        }

        $.ajax({
            url: $self.attr('href'),
            cache: false,
            type: 'POST',
            data: {
                favorite_tables: (isStorageSupported('localStorage') && typeof window.localStorage.favorite_tables !== 'undefined')
                    ? window.localStorage.favorite_tables
                    : ''
            },
            success: function (data) {
                if (data.changes) {
                    $('#pma_favorite_list').html(data.list);
                    $('#' + anchor_id).parent().html(data.anchor);
                    PMA_tooltip(
                        $('#' + anchor_id),
                        'a',
                        $('#' + anchor_id).attr("title")
                    );
                    // Update localStorage.
                    if (isStorageSupported('localStorage')) {
                        window.localStorage.favorite_tables = data.favorite_tables;
                    }
                } else {
                    PMA_ajaxShowMessage(data.message);
                }
            }
        });
    });
    // Check if session storage is supported
    if (isStorageSupported('sessionStorage')) {
        var storage = window.sessionStorage;
        // remove tree from storage if Navi_panel config form is submitted
        $(document).on('submit', 'form.config-form', function(event) {
            storage.removeItem('navTreePaths');
        });
        // Initialize if no previous state is defined
        if ($('#pma_navigation_tree_content').length &&
            typeof storage.navTreePaths === 'undefined'
        ) {
            PMA_reloadNavigation();
        } else if (PMA_commonParams.get('server') === storage.server &&
            PMA_commonParams.get('token') === storage.token
        ) {
            // Reload the tree to the state before page refresh
            PMA_reloadNavigation(null, JSON.parse(storage.navTreePaths));
        } else {
            // If the user is different
            navTreeStateUpdate();
        }
    }
});

/**
 * Expands a node in navigation tree.
 *
 * @param $expandElem expander
 * @param callback    callback function
 *
 * @returns void
 */
function expandTreeNode($expandElem, callback) {
    var $children = $expandElem.closest('li').children('div.list_container');
    var $icon = $expandElem.find('img');
    if ($expandElem.hasClass('loaded')) {
        if ($icon.is('.ic_b_plus')) {
            $icon.removeClass('ic_b_plus').addClass('ic_b_minus');
            $children.slideDown('fast');
        }
        if (callback && typeof callback == 'function') {
            callback.call();
        }
        $children.promise().done(navTreeStateUpdate);
    } else {
        var $throbber = $('#pma_navigation').find('.throbber')
            .first()
            .clone()
            .css({visibility: 'visible', display: 'block'})
            .click(false);
        $icon.hide();
        $throbber.insertBefore($icon);

        loadChildNodes(true, $expandElem, function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                var $destination = $expandElem.closest('li');
                $icon.removeClass('ic_b_plus').addClass('ic_b_minus');
                $children = $destination.children('div.list_container');
                $children.slideDown('fast');
                if ($destination.find('ul > li').length == 1) {
                    $destination.find('ul > li')
                        .find('a.expander.container')
                        .click();
                }
                if (callback && typeof callback == 'function') {
                    callback.call();
                }
                PMA_showFullName($destination);
            } else {
                PMA_ajaxShowMessage(data.error, false);
            }
            $icon.show();
            $throbber.remove();
            $children.promise().done(navTreeStateUpdate);
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
 * Expand the navigation and highlight the current database or table/view
 *
 * @returns void
 */
function PMA_showCurrentNavigation() {
    var db = PMA_commonParams.get('db');
    var table = PMA_commonParams.get('table');
    $('#pma_navigation_tree')
        .find('li.selected')
        .removeClass('selected');
    if (db) {
        var $dbItem = findLoadedItem(
            $('#pma_navigation_tree').find('> div'), db, 'database', !table
        );
        if ($('#navi_db_select').length &&
            $('option:selected', $('#navi_db_select')).length
        ) {
            if (! PMA_selectCurrentDb()) {
                return;
            }
            // If loaded database in navigation is not same as current one
            if ($('#pma_navigation_tree_content').find('span.loaded_db:first').text()
                !== $('#navi_db_select').val()
            ) {
                loadChildNodes(false, $('option:selected', $('#navi_db_select')), function (data) {
                    handleTableOrDb(table, $('#pma_navigation_tree_content'));
                    var $children = $('#pma_navigation_tree_content').children('div.list_container');
                    $children.promise().done(navTreeStateUpdate);
                });
            } else {
                handleTableOrDb(table, $('#pma_navigation_tree_content'));
            }
        } else if ($dbItem) {
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
    } else if ($('#navi_db_select').length && $('#navi_db_select').val()) {
        $('#navi_db_select').val('').hide().trigger('change');
    }
    PMA_showFullName($('#pma_navigation_tree'));

    function handleTableOrDb(table, $dbItem) {
        if (table) {
            loadAndHighlightTableOrView($dbItem, table);
        } else {
            var $container = $dbItem.children('div.list_container');
            var $tableContainer = $container.children('ul').children('li.tableContainer');
            if ($tableContainer.length > 0) {
                var $expander = $tableContainer.children('div:first').children('a.expander');
                $tableContainer.addClass('selected');
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
                        var $cont = $(this).children('div.list_container');
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
                var $containers = [];
                $sub_containers.each(function (index) {
                    $containers[index] = $(this);
                    $expander = $containers[index]
                        .children('div:first')
                        .children('a.expander');
                    if (! $expander.hasClass('loaded')) {
                        loadAndShowTableOrView($expander, $containers[index], itemName);
                    }
                });
            // else if no subContainers
            } else {
                $expander = $dbItem
                    .children('div:first')
                    .children('a.expander');
                if (! $expander.hasClass('loaded')) {
                    loadAndShowTableOrView($expander, $dbItem, itemName);
                }
            }
        }
    }

    function loadAndShowTableOrView($expander, $relatedContainer, itemName) {
        loadChildNodes(true, $expander, function (data) {
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
 * Disable navigation panel settings
 *
 * @return void
 */
function PMA_disableNaviSettings() {
    $('#pma_navigation_settings_icon').addClass('hide');
    $('#pma_navigation_settings').remove();
}

/**
 * Ensure that navigation panel settings is properly setup.
 * If not, set it up
 *
 * @return void
 */
function PMA_ensureNaviSettings(selflink) {
    $('#pma_navigation_settings_icon').removeClass('hide');

    if (!$('#pma_navigation_settings').length) {
        var params = {
            getNaviSettings: true
        };
        var url = $('#pma_navigation').find('a.navigation_url').attr('href');
        $.post(url, params, function (data) {
            if (typeof data !== 'undefined' && data.success) {
                $('#pma_navi_settings_container').html(data.message);
                setupRestoreField();
                setupValidation();
                setupConfigTabs();
                $('#pma_navigation_settings').find('form').attr('action', selflink);
            } else {
                PMA_ajaxShowMessage(data.error);
            }
        });
    } else {
        $('#pma_navigation_settings').find('form').attr('action', selflink);
    }
}

/**
 * Reloads the whole navigation tree while preserving its state
 *
 * @param  function     the callback function
 * @param  Object       stored navigation paths
 *
 * @return void
 */
function PMA_reloadNavigation(callback, paths) {
    var params = {
        reload: true,
        no_debug: true
    };
    paths = paths || traverseNavigationForPaths();
    $.extend(params, paths);
    if ($('#navi_db_select').length) {
        params.db = PMA_commonParams.get('db');
        requestNaviReload(params);
        return;
    }
    requestNaviReload(params);

    function requestNaviReload(params) {
        var url = $('#pma_navigation').find('a.navigation_url').attr('href');
        $.post(url, params, function (data) {
            if (typeof data !== 'undefined' && data.success) {
                $('#pma_navigation_tree').html(data.message).children('div').show();
                if ($('#pma_navigation_tree').hasClass('synced')) {
                    PMA_selectCurrentDb();
                    PMA_showCurrentNavigation();
                }
                // Fire the callback, if any
                if (typeof callback === 'function') {
                    callback.call();
                }
                navTreeStateUpdate();
            } else {
                PMA_ajaxShowMessage(data.error);
            }
        });
    }
}

function PMA_selectCurrentDb() {
    var $naviDbSelect = $('#navi_db_select');

    if (!$naviDbSelect.length) {
        return false;
    }

    if (PMA_commonParams.get('db')) { // db selected
        $naviDbSelect.show();
    }

    $naviDbSelect.val(PMA_commonParams.get('db'));
    return $naviDbSelect.val() === PMA_commonParams.get('db');

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
function PMA_navigationTreePagination($this) {
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
        if (typeof data !== 'undefined' && data.success) {
            PMA_ajaxRemoveMessage($msgbox);
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
            PMA_handleRedirectAndReload(data);
        }
        navTreeStateUpdate();
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
        $("#floating_menubar, #pma_console")
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
        $(document)
            .bind('mousemove', {'resize_handler': event.data.resize_handler},
                $.throttle(event.data.resize_handler.mousemove, 4))
            .bind('mouseup', {'resize_handler': event.data.resize_handler},
                event.data.resize_handler.mouseup);
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
        $('body').css('cursor', '');
        $.cookie('pma_navi_width', event.data.resize_handler.getPos(event));
        $('#topmenu').menuResizer('resize');
        $(document)
            .unbind('mousemove')
            .unbind('mouseup');
    };
    /**
     * Event handler for updating the panel during a resize operation
     *
     * @param object e Event data (contains a reference to resizeHandler)
     *
     * @return void
     */
    this.mousemove = function (event) {
        event.preventDefault();
        var pos = event.data.resize_handler.getPos(event);
        event.data.resize_handler.setWidth(pos);
        if ($('.sticky_columns').length !== 0) {
            handleAllStickyColumns();
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
        } else {
            //TODO: in fast filter search response there is no #pma_navigation_tree_content, needs to be added in php
            $nav_tree.css({
                'overflow-y': 'auto'
            });
        }
        // Set content bottom space beacuse of console
        $('body').css('margin-bottom', $('#pma_console').height() + 'px');
    };
    /* Initialisation section begins here */
    if ($.cookie('pma_navi_width')) {
        // If we have a cookie, set the width of the panel to its value
        var pos = Math.abs(parseInt($.cookie('pma_navi_width'), 10) || 0);
        this.setWidth(pos);
        $('#topmenu').menuResizer('resize');
    }
    // Register the events for the resizer and the collapser
    $(document).on('mousedown', '#pma_navigation_resizer', {'resize_handler': this}, this.mousedown);
    $(document).on('click', '#pma_navigation_collapser', {'resize_handler': this}, this.collapse);

    // Add the correct arrow symbol to the collapser
    $('#pma_navigation_collapser').html(this.getSymbol($('#pma_navigation').width()));
    // Fix navigation tree height
    $(window).on('resize', this.treeResize);
    // need to call this now and then, browser might decide
    // to show/hide horizontal scrollbars depending on page content width
    setInterval(this.treeResize, 2000);
    this.treeResize();
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
        if ($filterContainer
            .find('li.fast_filter:not(.db_fast_filter) input.searchClause')
            .length !== 0) {
            $filterInput = $filterContainer
                .find('li.fast_filter:not(.db_fast_filter) input.searchClause');
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
                    if (event.keyCode == 13) {
                        $obj.data('fastFilter').update($(this).val());
                    }
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
PMA_fastFilter.filter.prototype.update = function (searchClause) {
    if (this.searchClause != searchClause) {
        this.searchClause = searchClause;
        this.request();
    }
};
/**
 * After a delay of 250mS, initiates a request to retrieve search results
 * Multiple calls to this function will always abort the previous request
 *
 * @return void
 */
PMA_fastFilter.filter.prototype.request = function () {
    var self = this;
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
    if (self.xhr) {
        self.xhr.abort();
    }
    var url = $('#pma_navigation').find('a.navigation_url').attr('href');
    var params = self.$this.find('> ul > li > form.fast_filter').first().serialize();
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
        complete: function (jqXHR, status) {
            if (status != 'abort') {
                var data = $.parseJSON(jqXHR.responseText);
                self.$this.find('li.fast_filter').find('div.throbber').remove();
                if (data && data.results) {
                    self.swap.apply(self, [data.message]);
                }
            }
        }
    });
};
/**
 * Replaces the contents of the navigation branch with the search results
 *
 * @param string list The search results
 *
 * @return void
 */
PMA_fastFilter.filter.prototype.swap = function (list) {
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
PMA_fastFilter.filter.prototype.restore = function (focus) {
    if(this.$this.children('ul').first().hasClass('search_results')) {
        this.$this.html(this.$clone.html()).children().show();
        this.$this.data('fastFilter', this);
        if (focus) {
            this.$this.find('li.fast_filter input.searchClause').focus();
        }
    }
    this.searchClause = '';
    this.$this.find('div.pageselector').show();
    this.$this.find('div.throbber').remove();
};

/**
 * Show full name when cursor hover and name not shown completely
 *
 * @param object $containerELem Container element
 *
 * @return void
 */
function PMA_showFullName($containerELem) {

    $containerELem.find('.hover_show_full').mouseenter(function() {
        /** mouseenter */
        var $this = $(this);
        var thisOffset = $this.offset();
        if($this.text() === '') {
            return;
        }
        var $parent = $this.parent();
        if(  ($parent.offset().left + $parent.outerWidth())
           < (thisOffset.left + $this.outerWidth()))
        {
            var $fullNameLayer = $('#full_name_layer');
            if($fullNameLayer.length === 0)
            {
                $('body').append('<div id="full_name_layer" class="hide"></div>');
                $('#full_name_layer').mouseleave(function() {
                    /** mouseleave */
                    $(this).addClass('hide')
                           .removeClass('hovering');
                }).mouseenter(function() {
                    /** mouseenter */
                    $(this).addClass('hovering');
                });
                $fullNameLayer = $('#full_name_layer');
            }
            $fullNameLayer.removeClass('hide');
            $fullNameLayer.css({left: thisOffset.left, top: thisOffset.top});
            $fullNameLayer.html($this.clone());
            setTimeout(function() {
                if(! $fullNameLayer.hasClass('hovering'))
                {
                    $fullNameLayer.trigger('mouseleave');
                }
            }, 200);
        }
    });
}
