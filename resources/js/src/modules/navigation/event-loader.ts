import $ from 'jquery';
import { CommonParams } from '../common.ts';
import { Navigation } from '../navigation.ts';
import handleCreateViewModal from '../functions/handleCreateViewModal.ts';
import { ajaxRemoveMessage, ajaxShowMessage } from '../ajax-message.ts';
import isStorageSupported from '../functions/isStorageSupported.ts';
import tooltip from '../tooltip.ts';

/**
 * @return {function}
 */
export default function onloadNavigation () {
    return function () {
        if (! $('#pma_navigation').length) {
            // Don't bother running any code if the navigation is not even on the page
            return;
        }

        // Do not let the page reload on submitting the fast filter
        $(document).on('submit', '.fast_filter', function (event) {
            event.preventDefault();
        });

        // Fire up the resize handlers
        new Navigation.ResizeHandler();

        /**
         * opens/closes (hides/shows) tree elements
         * loads data via ajax
         */
        $(document).on('click', '#pma_navigation_tree a.expander', function (event) {
            event.preventDefault();
            event.stopImmediatePropagation();
            var $icon = $(this).find('img');
            if ($icon.is('.ic_b_plus')) {
                Navigation.expandTreeNode($(this));
            } else {
                Navigation.collapseTreeNode($(this));
            }
        });

        /**
         * Register event handler for click on the reload
         * navigation icon at the top of the panel
         */
        $(document).on('click', '#pma_navigation_reload', function (event) {
            event.preventDefault();

            // Find the loading symbol and show it
            var $iconThrobberSrc = $('#pma_navigation').find('.throbber');
            $iconThrobberSrc.show();
            // TODO Why is a loading symbol both hidden, and invisible?
            $iconThrobberSrc.css('visibility', '');

            // Callback to be used to hide the loading symbol when done reloading
            function hideNav () {
                $iconThrobberSrc.hide();
            }

            // Reload the navigation
            Navigation.reload(hideNav);
        });

        $(document).on('change', '#navi_db_select', function () {
            if (! $(this).val()) {
                Navigation.update(CommonParams.set('db', ''));
                Navigation.reload();
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
                    $(this).trigger('click');
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
                    .attr('alt', window.Messages.linkWithMain)
                    .attr('title', window.Messages.linkWithMain);

                $('#pma_navigation_tree')
                    .removeClass('synced')
                    .find('li.selected')
                    .removeClass('selected');
            } else {
                $img
                    .removeClass('ic_s_link')
                    .addClass('ic_s_unlink')
                    .attr('alt', window.Messages.unlinkWithMain)
                    .attr('title', window.Messages.unlinkWithMain);

                $('#pma_navigation_tree').addClass('synced');
                Navigation.showCurrent();
            }
        });

        /**
         * Bind all "fast filter" events
         */
        $('#pma_navigation_tree').on('click', 'li.fast_filter button.searchClauseClear', Navigation.FastFilter.events.clear);
        $('#pma_navigation_tree').on('focus', 'li.fast_filter input.searchClause', Navigation.FastFilter.events.focus);
        $('#pma_navigation_tree').on('blur', 'li.fast_filter input.searchClause', Navigation.FastFilter.events.blur);
        $('#pma_navigation_tree').on('keyup', 'li.fast_filter input.searchClause', Navigation.FastFilter.events.keyup);

        /**
         * Ajax handler for pagination
         */
        $('#pma_navigation_tree').on('click', 'div.pageselector a.ajax', function (event) {
            event.preventDefault();
            Navigation.treePagination($(this));
        });

        /**
         * Node highlighting
         */
        $('#pma_navigation_tree.highlight').on(
            'mouseover',
            'li:not(.fast_filter)',
            function () {
                if ($('li:visible', this).length === 0) {
                    $(this).addClass('activePointer');
                }
            }
        );

        $('#pma_navigation_tree.highlight').on(
            'mouseout',
            'li:not(.fast_filter)',
            function () {
                $(this).removeClass('activePointer');
            }
        );

        /** New view */
        $(document).on('click', 'li.new_view a.ajax', function (event) {
            event.preventDefault();
            handleCreateViewModal($(this));
        });

        /** Hide navigation tree item */
        $(document).on('click', 'a.hideNavItem.ajax', function (event) {
            event.preventDefault();
            var argSep = CommonParams.get('arg_separator');
            var params = $(this).getPostData();
            params += argSep + 'ajax_request=true' + argSep + 'server=' + CommonParams.get('server');
            $.ajax({
                type: 'POST',
                data: params,
                url: $(this).attr('href'),
                success: function (data) {
                    if (typeof data !== 'undefined' && data.success === true) {
                        Navigation.reload();
                    } else {
                        ajaxShowMessage(data.error);
                    }
                }
            });
        });

        /** Display a dialog to choose hidden navigation items to show */
        $(document).on('click', 'a.showUnhide.ajax', function (event) {
            event.preventDefault();
            var $msg = ajaxShowMessage();
            var argSep = CommonParams.get('arg_separator');
            var params = $(this).getPostData();
            params += argSep + 'ajax_request=true';
            $.post($(this).attr('href'), params, function (data) {
                if (typeof data !== 'undefined' && data.success === true) {
                    ajaxRemoveMessage($msg);
                    $('#unhideNavItemModal').modal('show');
                    $('#unhideNavItemModal').find('.modal-body').first().html(data.message);
                } else {
                    ajaxShowMessage(data.error);
                }
            });
        });

        /** Show a hidden navigation tree item */
        $(document).on('click', 'a.unhideNavItem.ajax', function (event) {
            event.preventDefault();
            var $tr = $(this).parents('tr');
            var $hiddenTableCount = $tr.parents('tbody').children().length;
            var $hideDialogBox = $tr.closest('div.ui-dialog');
            var $msg = ajaxShowMessage();
            var argSep = CommonParams.get('arg_separator');
            var params = $(this).getPostData();
            params += argSep + 'ajax_request=true' + argSep + 'server=' + CommonParams.get('server');
            $.ajax({
                type: 'POST',
                data: params,
                url: $(this).attr('href'),
                success: function (data) {
                    ajaxRemoveMessage($msg);
                    if (typeof data !== 'undefined' && data.success === true) {
                        $tr.remove();
                        if ($hiddenTableCount === 1) {
                            $hideDialogBox.remove();
                        }

                        Navigation.reload();
                    } else {
                        ajaxShowMessage(data.error);
                    }
                }
            });
        });

        // Add/Remove favorite table using Ajax.
        $(document).on('click', '.favorite_table_anchor', function (event) {
            event.preventDefault();
            var $self = $(this);
            var anchorId = $self.attr('id');
            if ($self.data('favtargetn') !== null) {
                var $dataFavTargets = $('a[data-favtargets="' + $self.data('favtargetn') + '"]');
                if ($dataFavTargets.length > 0) {
                    $dataFavTargets.trigger('click');

                    return;
                }
            }

            var hasLocalStorage = isStorageSupported('localStorage') &&
                typeof window.localStorage.favoriteTables !== 'undefined';
            $.ajax({
                url: $self.attr('href'),
                cache: false,
                type: 'POST',
                data: {
                    'favoriteTables': hasLocalStorage ? window.localStorage.favoriteTables : '',
                    'server': CommonParams.get('server'),
                },
                success: function (data) {
                    if (data.changes) {
                        $('#pma_favorite_list').html(data.list);
                        $('#' + anchorId).parent().html(data.anchor);
                        tooltip($('#' + anchorId), 'a', $('#' + anchorId).attr('title'));
                        // Update localStorage.
                        if (isStorageSupported('localStorage')) {
                            window.localStorage.favoriteTables = data.favoriteTables;
                        }
                    } else {
                        ajaxShowMessage(data.message);
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
                Navigation.reload();
            } else if (CommonParams.get('server') === storage.server &&
                CommonParams.get('token') === storage.token
            ) {
                // Reload the tree to the state before page refresh
                Navigation.reload(Navigation.filterStateRestore, JSON.parse(storage.navTreePaths));
            } else {
                // If the user is different
                Navigation.treeStateUpdate();
                Navigation.reload();
            }
        }
    };
}
