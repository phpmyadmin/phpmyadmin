function Show_tables_in_landing_page(db)
{
    Load_first_page(db, function (page) {
        if (page) {
            Load_HTML_for_page(page.pg_nr);
            selected_page = page.pg_nr;
        } else {
            Show_new_page_tables(true);
        }
    });
}

function Save_to_new_page(db, page_name, table_positions, callback)
{
    Create_new_page(db, page_name, function (page) {
        if (page) {
            var tbl_cords = [];
            for (var pos = 0; pos < table_positions.length; pos++) {
                table_positions[pos].pdf_pg_nr = page.pg_nr;
                Save_table_positions(table_positions[pos], function (id) {
                    tbl_cords.push(id);
                    if (table_positions.length === tbl_cords.length) {
                        page.tbl_cords = tbl_cords;
                        DesignerOfflineDB.addObject('pdf_pages', page);
                    }
                });
            }
            if (typeof callback !== 'undefined') {
                callback(page);
            }
        }
    });
}

function Save_to_selected_page(db, page_id, page_name, table_positions, callback)
{
    Delete_page(page_id);
    Save_to_new_page(db, page_name, table_positions, function (page) {
        if (typeof callback !== 'undefined') {
            callback(page);
        }
        selected_page = page.pg_nr;
    });
}

function Create_new_page(db, page_name, callback)
{
    var newPage = new PDFPage(db, page_name);
    DesignerOfflineDB.addObject('pdf_pages', newPage, function (pg_nr) {
        newPage.pg_nr = pg_nr;
        if (typeof callback !== 'undefined') {
            callback(newPage);
        }
    });
}

function Save_table_positions(positions, callback)
{
    DesignerOfflineDB.addObject('table_coords', positions, callback);
}

function Create_page_list(db, callback)
{
    DesignerOfflineDB.loadAllObjects('pdf_pages', function (pages) {
        var html = "";
        for (var p = 0; p < pages.length; p++) {
            var page = pages[p];
            if (page.db_name == db) {
                html += '<option value="' + page.pg_nr + '">';
                html += escapeHtml(page.page_descr) + '</option>';
            }
        }
        if (typeof callback !== 'undefined') {
            callback(html);
        }
    });
}

function Delete_page(page_id, callback)
{
    DesignerOfflineDB.loadObject('pdf_pages', page_id, function (page) {
        if (page) {
            for (var i = 0; i < page.tbl_cords.length; i++) {
                DesignerOfflineDB.deleteObject('table_coords', page.tbl_cords[i]);
            }
            DesignerOfflineDB.deleteObject('pdf_pages', page_id, callback);
        }
    });
}

function Load_first_page(db, callback)
{
    DesignerOfflineDB.loadAllObjects('pdf_pages', function (pages) {
        var firstPage = null;
        for (var i = 0; i < pages.length; i++) {
            var page = pages[i];
            if (page.db_name == db) {
                // give preference to a page having same name as the db
                if (page.page_descr == db) {
                    callback(page);
                    return;
                }
                if (firstPage == null) {
                    firstPage = page;
                }
            }
        }
        callback(firstPage);
    });
}

function Show_new_page_tables(check)
{
    var all_tables = $("#id_scroll_tab").find("td input:checkbox");
    all_tables.prop('checked', check);
    for (var tab = 0; tab < all_tables.length; tab++) {
        var input = all_tables[tab];
        if (input.value) {
            var element = document.getElementById(input.value);
            element.style.top = Get_random(550, 20) + 'px';
            element.style.left = Get_random(700, 20) + 'px';
            VisibleTab(input, input.value);
        }
    }
    selected_page = -1;
    $("#page_name").text(PMA_messages.strUntitled);
    MarkUnsaved();
}

function Load_HTML_for_page(page_id)
{
    Show_new_page_tables(false);
    Load_page_objects(page_id, function (page, tbl_cords) {
        $("#name-panel").find("#page_name").text(page.page_descr);
        MarkSaved();
        for (var t = 0; t < tbl_cords.length; t++) {
            var tb_id = db + '.' + tbl_cords[t].table_name;
            var table = document.getElementById(tb_id);
            table.style.top = tbl_cords[t].y + 'px';
            table.style.left = tbl_cords[t].x + 'px';

            var checkbox = document.getElementById("check_vis_" + tb_id);
            checkbox.checked = true;
            VisibleTab(checkbox, checkbox.value);
        }
        selected_page = page.pg_nr;
    });
}

function Load_page_objects(page_id, callback)
{
    DesignerOfflineDB.loadObject('pdf_pages', page_id, function (page) {
        var tbl_cords = [];
        var count = page.tbl_cords.length;
        for (var i = 0; i < count; i++) {
            DesignerOfflineDB.loadObject('table_coords', page.tbl_cords[i], function (tbl_cord) {
                tbl_cords.push(tbl_cord);
                if (tbl_cords.length == count) {
                    if (typeof callback !== 'undefined') {
                        callback(page, tbl_cords);
                    }
                }
            });
        }
    });
}

function Get_random(max, min)
{
    var val = Math.random() * (max - min) + min;
    return Math.floor(val);
}
