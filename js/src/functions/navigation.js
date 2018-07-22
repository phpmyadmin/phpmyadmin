/**
 * Expand the navigation and highlight the current database or table/view
 *
 * @returns void
 */
import { PMA_commonParams } from '../variables/common_params';
export function PMA_showCurrentNavigation () {
    var db = PMA_commonParams.get('db');
    var table = PMA_commonParams.get('table');

    $('#pma_navigation_tree').find('li.selected').removeClass('selected');
    if (db) {
        var $dbItem = findLoadedItem($('#pma_navigation_tree').find('> div'), db, 'database', !table);
        if ($('#navi_db_select').length && $('option:selected', $('#navi_db_select')).length) {
            if (!PMA_selectCurrentDb()) {
                return;
            }
            // If loaded database in navigation is not same as current one
            if ($('#pma_navigation_tree_content').find('span.loaded_db:first').text() !== $('#navi_db_select').val()) {
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
            if (!$expander.hasClass('loaded') || $expander.find('img').is('.ic_b_plus')) {
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

    function handleTableOrDb (table, $dbItem) {
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

    function findLoadedItem ($container, name, clazz, doSelect) {
        var ret = false;
        $container.children('ul').children('li').each(function () {
            var $li = $(this);
            // this is a navigation group, recurse
            if ($li.is('.navGroup')) {
                var $container = $li.children('div.list_container');
                var $childRet = findLoadedItem($container, name, clazz, doSelect);
                if ($childRet) {
                    ret = $childRet;
                    return false;
                }
            } else {
                // this is a real navigation item
                // name and class matches
                if ((clazz && $li.is('.' + clazz) || !clazz) && $li.children('a').text() === name) {
                    if (doSelect) {
                        $li.addClass('selected');
                    }
                    // taverse up and expand and parent navigation groups
                    $li.parents('.navGroup').each(function () {
                        var $cont = $(this).children('div.list_container');
                        if (!$cont.is(':visible')) {
                            $(this).children('div:first').children('a.expander').click();
                        }
                    });
                    ret = $li;
                    return false;
                }
            }
        });
        return ret;
    }

    function loadAndHighlightTableOrView ($dbItem, itemName) {
        var $container = $dbItem.children('div.list_container');
        var $expander;
        var $whichItem = isItemInContainer($container, itemName, 'li.table, li.view');
        // If item already there in some container
        if ($whichItem) {
            // get the relevant container while may also be a subcontainer
            var $relatedContainer = $whichItem.closest('li.subContainer').length ? $whichItem.closest('li.subContainer') : $dbItem;
            $whichItem = findLoadedItem($relatedContainer.children('div.list_container'), itemName, null, true);
            // Show directly
            showTableOrView($whichItem, $relatedContainer.children('div:first').children('a.expander'));
            // else if item not there, try loading once
        } else {
            var $sub_containers = $dbItem.find('.subContainer');
            // If there are subContainers i.e. tableContainer or viewContainer
            if ($sub_containers.length > 0) {
                var $containers = [];
                $sub_containers.each(function (index) {
                    $containers[index] = $(this);
                    $expander = $containers[index].children('div:first').children('a.expander');
                    if (!$expander.hasClass('loaded')) {
                        loadAndShowTableOrView($expander, $containers[index], itemName);
                    }
                });
                // else if no subContainers
            } else {
                $expander = $dbItem.children('div:first').children('a.expander');
                if (!$expander.hasClass('loaded')) {
                    loadAndShowTableOrView($expander, $dbItem, itemName);
                }
            }
        }
    }

    function loadAndShowTableOrView ($expander, $relatedContainer, itemName) {
        loadChildNodes(true, $expander, function (data) {
            var $whichItem = findLoadedItem($relatedContainer.children('div.list_container'), itemName, null, true);
            if ($whichItem) {
                showTableOrView($whichItem, $expander);
            }
        });
    }

    function showTableOrView ($whichItem, $expander) {
        expandTreeNode($expander, function (data) {
            if ($whichItem) {
                scrollToView($whichItem, false);
            }
        });
    }

    function isItemInContainer ($container, name, clazz) {
        var $whichItem = null;
        $items = $container.find(clazz);
        var found = false;
        $items.each(function () {
            if ($(this).children('a').text() === name) {
                $whichItem = $(this);
                return false;
            }
        });
        return $whichItem;
    }
}
