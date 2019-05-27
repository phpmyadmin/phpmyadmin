var DesignerPage = {};

DesignerPage.showTablesInLandingPage = function (db) {
    DesignerPage.loadFirstPage(db, function (page) {
        if (page) {
            DesignerPage.loadHtmlForPage(page.pg_nr);
            selectedPage = page.pg_nr;
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
                    page.tbl_cords = tblCords;
                    DesignerOfflineDB.addObject('pdf_pages', page);
                }
            };
            for (var pos = 0; pos < tablePositions.length; pos++) {
                tablePositions[pos].pdf_pg_nr = page.pg_nr;
                DesignerPage.saveTablePositions(tablePositions[pos], saveCallback);
            }
            if (typeof callback !== 'undefined') {
                callback(page);
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
        selectedPage = page.pg_nr;
    });
};

DesignerPage.createNewPage = function (db, pageName, callback) {
    var newPage = new PDFPage(db, pageName);
    DesignerOfflineDB.addObject('pdf_pages', newPage, function (pgNr) {
        newPage.pg_nr = pgNr;
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
            if (page.db_name === db) {
                html += '<option value="' + page.pg_nr + '">';
                html += Functions.escapeHtml(page.page_descr) + '</option>';
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
            for (var i = 0; i < page.tbl_cords.length; i++) {
                DesignerOfflineDB.deleteObject('table_coords', page.tbl_cords[i]);
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
            if (page.db_name === db) {
                // give preference to a page having same name as the db
                if (page.page_descr === db) {
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
    var allTables = $('#id_scroll_tab').find('td input:checkbox');
    allTables.prop('checked', check);
    for (var tab = 0; tab < allTables.length; tab++) {
        var input = allTables[tab];
        if (input.value) {
            var element = document.getElementById(input.value);
            element.style.top = DesignerPage.getRandom(550, 20) + 'px';
            element.style.left = DesignerPage.getRandom(700, 20) + 'px';
            DesignerMove.visibleTab(input, input.value);
        }
    }
    selectedPage = -1;
    $('#page_name').text(Messages.strUntitled);
    DesignerMove.markUnsaved();
};

DesignerPage.loadHtmlForPage = function (pageId) {
    DesignerPage.showNewPageTables(false);
    DesignerPage.loadPageObjects(pageId, function (page, tblCords) {
        $('#name-panel').find('#page_name').text(page.page_descr);
        DesignerMove.markSaved();
        for (var t = 0; t < tblCords.length; t++) {
            var tbId = db + '.' + tblCords[t].table_name;
            var table = document.getElementById(tbId);
            table.style.top = tblCords[t].y + 'px';
            table.style.left = tblCords[t].x + 'px';

            var checkbox = document.getElementById('check_vis_' + tbId);
            checkbox.checked = true;
            DesignerMove.visibleTab(checkbox, checkbox.value);
        }
        selectedPage = page.pg_nr;
    });
};

DesignerPage.loadPageObjects = function (pageId, callback) {
    DesignerOfflineDB.loadObject('pdf_pages', pageId, function (page) {
        var tblCords = [];
        var count = page.tbl_cords.length;
        for (var i = 0; i < count; i++) {
            DesignerOfflineDB.loadObject('table_coords', page.tbl_cords[i], function (tblCord) {
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
