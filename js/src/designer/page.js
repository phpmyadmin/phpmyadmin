/* global DesignerOfflineDB */ // js/designer/database.js
// eslint-disable-next-line no-unused-vars
/* global db, selectedPage:writable, jTabs */ // js/designer/init.js
/* global DesignerMove */ // js/designer/move.js
/* global DesignerObjects */ // js/designer/objects.js

var DesignerPage = {};

DesignerPage.showTablesInLandingPage = function (db) {
    DesignerPage.loadFirstPage(db, function (page) {
        if (page) {
            DesignerPage.loadHtmlForPage(page.pgNr);
            selectedPage = page.pgNr;
        } else {
            DesignerPage.showNewPageTables(true);
        }
    });
};

DesignerPage.saveToNewPage = function (db, pageName, tablePositions, callback) {
    DesignerPage.createNewPage(db, pageName, function (page) {
        if (page) {
            var tblCords = [];
            var saveCallback = function (id) {
                tblCords.push(id);
                if (tablePositions.length === tblCords.length) {
                    page.tblCords = tblCords;
                    DesignerOfflineDB.addObject('pdf_pages', page);
                    if (typeof callback !== 'undefined') {
                        callback(page);
                    }
                }
            };
            for (var pos = 0; pos < tablePositions.length; pos++) {
                tablePositions[pos].pdfPgNr = page.pgNr;
                DesignerPage.saveTablePositions(tablePositions[pos], saveCallback);
            }
        }
    });
};

DesignerPage.saveToSelectedPage = function (db, pageId, pageName, tablePositions, callback) {
    DesignerPage.deletePage(pageId);
    DesignerPage.saveToNewPage(db, pageName, tablePositions, function (page) {
        if (typeof callback !== 'undefined') {
            callback(page);
        }
        selectedPage = page.pgNr;
    });
};

DesignerPage.createNewPage = function (db, pageName, callback) {
    var newPage = new DesignerObjects.PdfPage(db, pageName);
    DesignerOfflineDB.addObject('pdf_pages', newPage, function (pgNr) {
        newPage.pgNr = pgNr;
        if (typeof callback !== 'undefined') {
            callback(newPage);
        }
    });
};

DesignerPage.saveTablePositions = function (positions, callback) {
    DesignerOfflineDB.addObject('table_coords', positions, callback);
};

DesignerPage.createPageList = function (db, callback) {
    DesignerOfflineDB.loadAllObjects('pdf_pages', function (pages) {
        var html = '';
        for (var p = 0; p < pages.length; p++) {
            var page = pages[p];
            if (page.dbName === db) {
                html += '<option value="' + page.pgNr + '">';
                html += Functions.escapeHtml(page.pageDescr) + '</option>';
            }
        }
        if (typeof callback !== 'undefined') {
            callback(html);
        }
    });
};

DesignerPage.deletePage = function (pageId, callback) {
    DesignerOfflineDB.loadObject('pdf_pages', pageId, function (page) {
        if (page) {
            for (var i = 0; i < page.tblCords.length; i++) {
                DesignerOfflineDB.deleteObject('table_coords', page.tblCords[i]);
            }
            DesignerOfflineDB.deleteObject('pdf_pages', pageId, callback);
        }
    });
};

DesignerPage.loadFirstPage = function (db, callback) {
    DesignerOfflineDB.loadAllObjects('pdf_pages', function (pages) {
        var firstPage = null;
        for (var i = 0; i < pages.length; i++) {
            var page = pages[i];
            if (page.dbName === db) {
                // give preference to a page having same name as the db
                if (page.pageDescr === db) {
                    callback(page);
                    return;
                }
                if (firstPage === null) {
                    firstPage = page;
                }
            }
        }
        callback(firstPage);
    });
};

DesignerPage.showNewPageTables = function (check) {
    var allTables = $('.scroll_tab_checkbox:checkbox');
    allTables.prop('checked', check);
    var tableSize = allTables.length;
    for (var tab = 0; tab < tableSize; tab++) {
        var input = allTables[tab];
        if (input.value) {
            // Remove check_visible_ from input.value
            var val = input.value.replace('check_visible_','');
            var element = document.getElementById('designer_table_' + val);
            element.style.top = DesignerPage.getRandom(550, 20) + 'px';
            element.style.left = DesignerPage.getRandom(700, 20) + 'px';
            DesignerMove.visibleTab(input, 'designer_table_' + val);
        }
    }
    selectedPage = -1;
    $('#page_name').text(Messages.strUntitled);
    DesignerMove.markUnsaved();
};

DesignerPage.getTableFromData = function (data) {
    var $newTableDom = $(data.message);
    $newTableDom.find('a').first().remove();
    var table = null;
    for (var i = 0; i < $newTableDom.length; i++) {
        if ($newTableDom[i].tagName === 'TABLE') {
            table = $newTableDom[i];
            break;
        }
    }
    var dbTableNameUrl = $($newTableDom).find('.small_tab_pref').attr('unique_id');

    return [table, dbTableNameUrl];
};

DesignerPage.loadHtmlForPage = function (pageId) {
    DesignerPage.showNewPageTables(true);
    DesignerPage.loadPageObjects(pageId, function (page, tblCords) {
        $('#name-panel').find('#page_name').text(page.pageDescr);
        var tableMissing = false;
        for (var t = 0; t < tblCords.length; t++) {
            var tbId = btoa(tblCords[t].dbName + '.' + tblCords[t].tableName);
            var table = document.getElementById('designer_table_' + tbId);
            var yCord = tblCords[t].y + 'px';
            var xCord = tblCords[t].x + 'px';
            if (!table) {
                $.ajax({
                    type: 'POST',
                    async: false,
                    url: 'index.php?route=/database/designer',
                    data: {
                        'ajax_request' : true,
                        'dialog' : 'add_table',
                        'db' : tblCords[t].dbName,
                        'table' : tblCords[t].tableName,
                        'server': CommonParams.get('server')
                    },
                    // eslint-disable-next-line no-loop-func
                    success: function (data) {
                        var [table, dbTableNameUrl] = DesignerPage.getTableFromData(data);
                        if (typeof dbTableNameUrl === 'string' && table) { // Do not try to add if attr not found !
                            $('#container-form').append(table);
                            DesignerMove.enableTableEvents(null, table);
                            DesignerMove.addTableToTablesList(null, table);
                            table.style.top = yCord;
                            table.style.left = xCord;
                            jTabs[dbTableNameUrl] = 1;
                            var checkbox = document.getElementById('check_vis_' + tbId);
                            checkbox.checked = true;
                            var val = checkbox.value.replace('check_visible_','');
                            DesignerMove.visibleTab(checkbox, 'designer_table_' + val);
                        }
                    }
                });
            } else {
                table.style.top = yCord;
                table.style.left = xCord;

                var checkbox = document.getElementById('check_vis_' + tbId);
                checkbox.checked = true;
                var val = checkbox.value.replace('check_visible_','');
                DesignerMove.visibleTab(checkbox, 'designer_table_' + val);
            }
        }
        DesignerMove.markSaved();
        if (tableMissing === true) {
            DesignerMove.markUnsaved();
            Functions.ajaxShowMessage(Messages.strSavedPageTableMissing);
        }
        selectedPage = page.pgNr;
    });
};

DesignerPage.loadPageObjects = function (pageId, callback) {
    DesignerOfflineDB.loadObject('pdf_pages', pageId, function (page) {
        var tblCords = [];
        var count = page.tblCords.length;
        for (var i = 0; i < count; i++) {
            DesignerOfflineDB.loadObject('table_coords', page.tblCords[i], function (tblCord) {
                tblCords.push(tblCord);
                if (tblCords.length === count) {
                    if (typeof callback !== 'undefined') {
                        callback(page, tblCords);
                    }
                }
            });
        }
    });
};

DesignerPage.getRandom = function (max, min) {
    var val = Math.random() * (max - min) + min;
    return Math.floor(val);
};
