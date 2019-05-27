var DesignerPage = {};

DesignerPage.showTablesInLandingPage = function (db) {
    DesignerPage.loadFirstPage(db, function (page) {
        if (page) {
            DesignerPage.loadHtmlForPage(page.pg_nr);
            selected_page = page.pg_nr;
        } else {
            DesignerPage.showNewPageTables(true);
        }
    });
};

DesignerPage.saveToNewPage = function (db, page_name, table_positions, callback) {
    DesignerPage.createNewPage(db, page_name, function (page) {
        if (page) {
            var tbl_cords = [];
            var saveCallback = function (id) {
                tbl_cords.push(id);
                if (table_positions.length === tbl_cords.length) {
                    page.tbl_cords = tbl_cords;
                    DesignerOfflineDB.addObject('pdf_pages', page);
                }
            };
            for (var pos = 0; pos < table_positions.length; pos++) {
                table_positions[pos].pdf_pg_nr = page.pg_nr;
                DesignerPage.saveTablePositions(table_positions[pos], saveCallback);
            }
            if (typeof callback !== 'undefined') {
                callback(page);
            }
        }
    });
};

DesignerPage.saveToSelectedPage = function (db, page_id, page_name, table_positions, callback) {
    DesignerPage.deletePage(page_id);
    DesignerPage.saveToNewPage(db, page_name, table_positions, function (page) {
        if (typeof callback !== 'undefined') {
            callback(page);
        }
        selected_page = page.pg_nr;
    });
};

DesignerPage.createNewPage = function (db, page_name, callback) {
    var newPage = new PDFPage(db, page_name);
    DesignerOfflineDB.addObject('pdf_pages', newPage, function (pg_nr) {
        newPage.pg_nr = pg_nr;
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

DesignerPage.deletePage = function (page_id, callback) {
    DesignerOfflineDB.loadObject('pdf_pages', page_id, function (page) {
        if (page) {
            for (var i = 0; i < page.tbl_cords.length; i++) {
                DesignerOfflineDB.deleteObject('table_coords', page.tbl_cords[i]);
            }
            DesignerOfflineDB.deleteObject('pdf_pages', page_id, callback);
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
    var all_tables = $('#id_scroll_tab').find('td input:checkbox');
    all_tables.prop('checked', check);
    for (var tab = 0; tab < all_tables.length; tab++) {
        var input = all_tables[tab];
        if (input.value) {
            var element = document.getElementById(input.value);
            element.style.top = DesignerPage.getRandom(550, 20) + 'px';
            element.style.left = DesignerPage.getRandom(700, 20) + 'px';
            DesignerMove.visibleTab(input, input.value);
        }
    }
    selected_page = -1;
    $('#page_name').text(Messages.strUntitled);
    DesignerMove.markUnsaved();
};

DesignerPage.loadHtmlForPage = function (page_id) {
    DesignerPage.showNewPageTables(false);
    DesignerPage.loadPageObjects(page_id, function (page, tbl_cords) {
        $('#name-panel').find('#page_name').text(page.page_descr);
        DesignerMove.markSaved();
        for (var t = 0; t < tbl_cords.length; t++) {
            var tb_id = db + '.' + tbl_cords[t].table_name;
            var table = document.getElementById(tb_id);
            table.style.top = tbl_cords[t].y + 'px';
            table.style.left = tbl_cords[t].x + 'px';

            var checkbox = document.getElementById('check_vis_' + tb_id);
            checkbox.checked = true;
            DesignerMove.visibleTab(checkbox, checkbox.value);
        }
        selected_page = page.pg_nr;
    });
};

DesignerPage.loadPageObjects = function (page_id, callback) {
    DesignerOfflineDB.loadObject('pdf_pages', page_id, function (page) {
        var tbl_cords = [];
        var count = page.tbl_cords.length;
        for (var i = 0; i < count; i++) {
            DesignerOfflineDB.loadObject('table_coords', page.tbl_cords[i], function (tbl_cord) {
                tbl_cords.push(tbl_cord);
                if (tbl_cords.length === count) {
                    if (typeof callback !== 'undefined') {
                        callback(page, tbl_cords);
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
