/* vim: set expandtab sw=4 ts=4 sts=4: */
import { expandTreeNode,
    navTreeStateUpdate,
    PMA_showCurrentNavigation,
    navFilterStateRestore,
    collapseTreeNode,
    PMA_fastFilter,
    ResizeHandler,
    PMA_reloadNavigation,
    PMA_navigationTreePagination } from './functions/navigation';
import { PMA_Messages as PMA_messages } from './variables/export_variables';
import CommonParams from './variables/common_params';
import { PMA_ajaxShowMessage, PMA_ajaxRemoveMessage, PMA_tooltip } from './utils/show_ajax_messages';
import { isStorageSupported } from './functions/config';
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

    $(document).on('change', '#navi_db_select',  function () {
        if (! $(this).val()) {
            CommonParams.set('db', '');
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
        $('#pma_navigation_tree').find('a.expander').each(function () {
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
        ) + CommonParams.get('arg_separator') + 'ajax_request=true';
        var title = PMA_messages.strAddIndex;
        indexEditorDialog(url, title);
    });

    /** Edit index */
    $(document).on('click', 'li.index a.ajax', function (event) {
        event.preventDefault();
        var url = $(this).attr('href').substr(
            $(this).attr('href').indexOf('?') + 1
        ) + CommonParams.get('arg_separator') + 'ajax_request=true';
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
            data: {
                server: CommonParams.get('server'),
            },
            url: $(this).attr('href') + CommonParams.get('arg_separator') + 'ajax_request=true',
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
        $.get($(this).attr('href') + CommonParams.get('arg_separator') + 'ajax_request=1', function (data) {
            if (typeof data !== 'undefined' && data.success === true) {
                PMA_ajaxRemoveMessage($msg);
                var buttonOptions = {};
                buttonOptions[PMA_messages.strClose] = function () {
                    $(this).dialog('close');
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
            data: {
                server: CommonParams.get('server'),
            },
            url: $(this).attr('href') + CommonParams.get('arg_separator') + 'ajax_request=true',
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
    $(document).on('click', '.favorite_table_anchor', function (event) {
        event.preventDefault();
        var $self = $(this);
        var anchor_id = $self.attr('id');
        if ($self.data('favtargetn') !== null) {
            if ($('a[data-favtargets="' + $self.data('favtargetn') + '"]').length > 0) {
                $('a[data-favtargets="' + $self.data('favtargetn') + '"]').trigger('click');
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
                    : '',
                server: CommonParams.get('server'),
            },
            success: function (data) {
                if (data.changes) {
                    $('#pma_favorite_list').html(data.list);
                    $('#' + anchor_id).parent().html(data.anchor);
                    PMA_tooltip(
                        $('#' + anchor_id),
                        'a',
                        $('#' + anchor_id).attr('title')
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
        $(document).on('submit', 'form.config-form', function () {
            storage.removeItem('navTreePaths');
        });
        // Initialize if no previous state is defined
        if ($('#pma_navigation_tree_content').length &&
            typeof storage.navTreePaths === 'undefined'
        ) {
            PMA_reloadNavigation();
        } else if (CommonParams.get('server').toString() === storage.server.toString() &&
            CommonParams.get('token').toString() === storage.token.toString()
        ) {
            // Reload the tree to the state before page refresh
            PMA_reloadNavigation(navFilterStateRestore, JSON.parse(storage.navTreePaths));
        } else {
            // If the user is different
            navTreeStateUpdate();
        }
    }
});
